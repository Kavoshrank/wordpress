<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KavoshRank Plugin Automatic Update Channel
 *
 * Checks the central KavoshRank API endpoint (GET /api/v1/plugin/update-info)
 * for new versions, integrates with WordPress core update transients and plugin
 * modal APIs, and verifies SHA-256 package checksums before installation.
 */
class KavoshRank_Updater {

    const TRANSIENT_KEY   = 'kavoshrank_update_info_cache';
    const CACHE_TTL       = 43200; // 12 hours
    const PLUGIN_BASENAME = 'kavoshrank/kavoshrank.php';
    const PLUGIN_SLUG     = 'kavoshrank';

    /**
     * Initialize updater hooks
     */
    public function init() {
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugins_api_handler' ), 20, 3 );
        add_filter( 'upgrader_pre_download', array( $this, 'verify_package_checksum' ), 10, 4 );
        add_action( 'upgrader_process_complete', array( $this, 'clear_update_cache' ), 10, 2 );
        add_action( 'admin_notices', array( $this, 'render_admin_update_notice' ) );
    }

    /**
     * Retrieve remote update info from KavoshRank backend API
     *
     * @param bool $force_refresh Bypass transient cache if true
     * @return array|null Metadata array or null on failure
     */
    public function get_remote_update_info( $force_refresh = false ) {
        if ( ! $force_refresh ) {
            $cached = get_site_transient( self::TRANSIENT_KEY );
            if ( ! empty( $cached ) && is_array( $cached ) && ! empty( $cached['version'] ) ) {
                return $cached;
            }
        }

        $api_base = get_option( 'kavoshrank_golang_api_url', 'https://backend.kavoshrank.ir' );
        if ( empty( $api_base ) || strpos( $api_base, 'api.kavoshrank.ir' ) !== false ) {
            $api_base = 'https://backend.kavoshrank.ir';
        }

        $endpoint = rtrim( $api_base, '/' ) . '/api/v1/plugin/update-info';

        $response = wp_remote_get( $endpoint, array(
            'timeout'   => 10,
            'sslverify' => true,
            'headers'   => array(
                'Accept'     => 'application/json',
                'User-Agent' => 'KavoshRank-WordPress-Client/' . ( defined( 'KAVOSHRANK_VERSION' ) ? KAVOSHRANK_VERSION : '1.0.0' ),
            ),
        ) );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            // Fallback 1: static release record on the download host
            $fallback_endpoint = 'https://kavoshrank.ir/download/update-info.json';
            $response = wp_remote_get( $fallback_endpoint, array(
                'timeout'   => 10,
                'sslverify' => true,
                'headers'   => array(
                    'Accept'     => 'application/json',
                    'User-Agent' => 'KavoshRank-WordPress-Client/' . ( defined( 'KAVOSHRANK_VERSION' ) ? KAVOSHRANK_VERSION : '1.0.0' ),
                ),
            ) );
            if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                // Fallback 2: official GitHub releases API
                $github_endpoint = 'https://api.github.com/repos/Kavoshrank/wordpress/releases/latest';
                $gh_response = wp_remote_get( $github_endpoint, array(
                    'timeout'   => 10,
                    'sslverify' => true,
                    'headers'   => array(
                        'Accept'     => 'application/vnd.github.v3+json',
                        'User-Agent' => 'KavoshRank-WordPress-Client/' . ( defined( 'KAVOSHRANK_VERSION' ) ? KAVOSHRANK_VERSION : '1.0.0' ),
                    ),
                ) );

                if ( ! is_wp_error( $gh_response ) && wp_remote_retrieve_response_code( $gh_response ) === 200 ) {
                    $gh_body = wp_remote_retrieve_body( $gh_response );
                    $gh_json = json_decode( $gh_body, true );
                    if ( is_array( $gh_json ) && ! empty( $gh_json['tag_name'] ) ) {
                        $gh_version = ltrim( $gh_json['tag_name'], 'vV' );
                        $gh_package = 'https://github.com/Kavoshrank/wordpress/releases/download/latest/kavoshrank.zip';
                        if ( ! empty( $gh_json['assets'] ) && is_array( $gh_json['assets'] ) ) {
                            foreach ( $gh_json['assets'] as $asset ) {
                                if ( isset( $asset['name'] ) && $asset['name'] === 'kavoshrank.zip' && ! empty( $asset['browser_download_url'] ) ) {
                                    $gh_package = $asset['browser_download_url'];
                                    break;
                                }
                            }
                        }

                        $info = array(
                            'version'          => $gh_version,
                            'download_url'     => $gh_package,
                            'package'          => $gh_package,
                            'homepage'         => ! empty( $gh_json['html_url'] ) ? $gh_json['html_url'] : 'https://kavoshrank.ir',
                            'tested_up_to'     => '6.7',
                            'requires_at_least'=> '6.4',
                            'requires_php'     => '8.1',
                            'sections'         => array(
                                'description' => ! empty( $gh_json['body'] ) ? wp_kses_post( $gh_json['body'] ) : 'KavoshRank WordPress AI SEO & Audio Studio Client',
                                'changelog'   => ! empty( $gh_json['body'] ) ? wp_kses_post( $gh_json['body'] ) : '',
                            ),
                        );
                        set_site_transient( self::TRANSIENT_KEY, $info, self::CACHE_TTL );
                        return $info;
                    }
                }

                return null;
            }
        }


        $body = wp_remote_retrieve_body( $response );
        $json = json_decode( $body, true );
        if ( ! is_array( $json ) ) {
            return null;
        }

        // Support envelope format: { "data": { ... } } or flat
        $info = isset( $json['data'] ) && is_array( $json['data'] ) ? $json['data'] : $json;

        if ( empty( $info['version'] ) ) {
            return null;
        }

        set_site_transient( self::TRANSIENT_KEY, $info, self::CACHE_TTL );
        return $info;
    }

    /**
     * Hook: pre_set_site_transient_update_plugins
     * Injects available update details into WordPress site transient.
     *
     * @param object $transient Site transient object
     * @return object
     */
    public function check_for_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }

        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote_info = $this->get_remote_update_info();
        if ( ! $remote_info || empty( $remote_info['version'] ) ) {
            return $transient;
        }

        $current_version = defined( 'KAVOSHRANK_VERSION' ) ? KAVOSHRANK_VERSION : '1.0.0';
        $package_url     = ! empty( $remote_info['package'] ) ? $remote_info['package'] : ( ! empty( $remote_info['download_url'] ) ? $remote_info['download_url'] : '' );

        if ( version_compare( $current_version, $remote_info['version'], '<' ) ) {
            $update = new stdClass();
            $update->id             = self::PLUGIN_BASENAME;
            $update->slug           = self::PLUGIN_SLUG;
            $update->plugin         = self::PLUGIN_BASENAME;
            $update->new_version    = $remote_info['version'];
            $update->url            = ! empty( $remote_info['homepage'] ) ? $remote_info['homepage'] : 'https://kavoshrank.ir';
            $update->package        = $package_url;
            $update->tested         = ! empty( $remote_info['tested_up_to'] ) ? $remote_info['tested_up_to'] : '6.7';
            $update->requires       = ! empty( $remote_info['requires_at_least'] ) ? $remote_info['requires_at_least'] : '6.4';
            $update->requires_php   = ! empty( $remote_info['requires_php'] ) ? $remote_info['requires_php'] : '8.1';
            $update->icons          = array(
                'default' => 'https://kavoshrank.ir/assets/icon.svg',
            );

            $transient->response[ self::PLUGIN_BASENAME ] = $update;
        } else {
            $item = new stdClass();
            $item->id          = self::PLUGIN_BASENAME;
            $item->slug        = self::PLUGIN_SLUG;
            $item->plugin      = self::PLUGIN_BASENAME;
            $item->new_version = $current_version;
            $item->url         = ! empty( $remote_info['homepage'] ) ? $remote_info['homepage'] : 'https://kavoshrank.ir';
            $item->package     = '';

            $transient->no_update[ self::PLUGIN_BASENAME ] = $item;
        }

        return $transient;
    }

    /**
     * Hook: plugins_api
     * Supplies detailed modal metadata (changelog, description, compatibility)
     * when the administrator clicks "View version details".
     *
     * @param false|object|array $result Result from plugins_api
     * @param string $action Action name
     * @param object $args Arguments object
     * @return object|false
     */
    public function plugins_api_handler( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) || self::PLUGIN_SLUG !== $args->slug ) {
            return $result;
        }

        $remote_info = $this->get_remote_update_info();
        if ( ! $remote_info ) {
            return $result;
        }

        $package_url = ! empty( $remote_info['package'] ) ? $remote_info['package'] : ( ! empty( $remote_info['download_url'] ) ? $remote_info['download_url'] : '' );

        $info = new stdClass();
        $info->name           = ! empty( $remote_info['name'] ) ? $remote_info['name'] : 'KavoshRank SEO Client';
        $info->slug           = self::PLUGIN_SLUG;
        $info->version        = ! empty( $remote_info['version'] ) ? $remote_info['version'] : '1.0.0';
        $info->author         = ! empty( $remote_info['author'] ) ? $remote_info['author'] : 'KavoshRank Team';
        $info->author_profile = ! empty( $remote_info['homepage'] ) ? $remote_info['homepage'] : 'https://kavoshrank.ir';
        $info->homepage       = ! empty( $remote_info['homepage'] ) ? $remote_info['homepage'] : 'https://kavoshrank.ir';
        $info->requires       = ! empty( $remote_info['requires_at_least'] ) ? $remote_info['requires_at_least'] : '6.4';
        $info->tested         = ! empty( $remote_info['tested_up_to'] ) ? $remote_info['tested_up_to'] : '6.7';
        $info->requires_php   = ! empty( $remote_info['requires_php'] ) ? $remote_info['requires_php'] : '8.1';
        $info->download_link  = $package_url;
        $info->last_updated   = ! empty( $remote_info['last_updated'] ) ? $remote_info['last_updated'] : date( 'Y-m-d' );
        $info->sections       = ! empty( $remote_info['sections'] ) && is_array( $remote_info['sections'] )
            ? $remote_info['sections']
            : array(
                'description' => 'Lightweight WordPress Client for KavoshRank AI SEO & Content Automation Master Engine.',
                'changelog'   => 'Automated update channel and security enhancements.',
            );

        return $info;
    }

    /**
     * Hook: upgrader_pre_download
     * Verifies the package SHA-256 checksum before allowing WordPress to unpack.
     *
     * @param bool|string|WP_Error $reply Pre-download result
     * @param string $package Package URL
     * @param WP_Upgrader $upgrader Upgrader instance
     * @param array $hook_extra Hook context
     * @return bool|string|WP_Error Local file path or WP_Error
     */
    public function verify_package_checksum( $reply, $package, $upgrader, $hook_extra = array() ) {
        // Intercept only KavoshRank updates
        $is_kavoshrank = false;
        if ( ! empty( $hook_extra['plugin'] ) && $hook_extra['plugin'] === self::PLUGIN_BASENAME ) {
            $is_kavoshrank = true;
        } elseif ( strpos( (string) $package, 'kavoshrank' ) !== false ) {
            $is_kavoshrank = true;
        }

        if ( ! $is_kavoshrank ) {
            return $reply;
        }

        $remote_info = $this->get_remote_update_info();
        $expected_sha256 = ! empty( $remote_info['checksum_sha256'] ) ? trim( (string) $remote_info['checksum_sha256'] ) : '';

        // If no expected checksum is specified in metadata, allow standard download
        if ( empty( $expected_sha256 ) ) {
            return $reply;
        }

        // Download package to verify SHA-256 prior to extraction
        if ( ! function_exists( 'download_url' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $download_file = download_url( $package, 300 );
        if ( is_wp_error( $download_file ) ) {
            return $download_file;
        }

        $actual_sha256 = hash_file( 'sha256', $download_file );
        if ( ! hash_equals( strtolower( $expected_sha256 ), strtolower( (string) $actual_sha256 ) ) ) {
            if ( file_exists( $download_file ) ) {
                @unlink( $download_file );
            }
            return new WP_Error(
                'kavoshrank_checksum_mismatch',
                sprintf(
                    'KavoshRank package SHA-256 verification failed. Expected: %s, got: %s',
                    $expected_sha256,
                    $actual_sha256
                )
            );
        }

        // Verified valid checksum: return path to local file for upgrader
        return $download_file;
    }

    /**
     * Clear update cache transient upon successful plugin upgrade
     *
     * @param WP_Upgrader $upgrader
     * @param array $options
     */
    public function clear_update_cache( $upgrader = null, $options = array() ) {
        delete_site_transient( self::TRANSIENT_KEY );
    }

    /**
     * Render WordPress admin notice when a new plugin version is available.
     */
    public function render_admin_update_notice() {
        if ( ! current_user_can( 'update_plugins' ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && 'update-core' === $screen->id ) {
            return;
        }

        $remote_info = $this->get_remote_update_info();
        if ( ! $remote_info || empty( $remote_info['version'] ) ) {
            return;
        }

        $current_version = defined( 'KAVOSHRANK_VERSION' ) ? KAVOSHRANK_VERSION : '1.0.0';
        if ( ! version_compare( $current_version, $remote_info['version'], '<' ) ) {
            return;
        }

        $package_url = ! empty( $remote_info['package'] ) ? $remote_info['package'] : ( ! empty( $remote_info['download_url'] ) ? $remote_info['download_url'] : 'https://kavoshrank.ir/download/kavoshrank-connector.zip' );
        $update_url  = wp_nonce_url(
            self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . urlencode( self::PLUGIN_BASENAME ) ),
            'upgrade-plugin_' . self::PLUGIN_BASENAME
        );
        ?>
        <div class="notice notice-warning is-dismissible" style="border-right: 4px solid #10b981; border-left: 4px solid #10b981; padding: 12px 18px; margin: 15px 0; background: #f0fdf4; border-radius: 8px;">
            <p style="margin: 0 0 6px 0; font-weight: 800; color: #065f46; font-size: 13px;">
                <?php esc_html_e( 'بروزرسانی جدید افزونه کاوش‌رنک در دسترس است', 'kavoshrank' ); ?>
            </p>
            <p style="margin: 0 0 10px 0; font-size: 12px; color: #374151; line-height: 1.6;">
                <?php
                printf(
                    /* translators: 1: new version, 2: current version */
                    esc_html__( 'نسخه %1$s افزونه کاوش‌رنک منتشر شده است (نسخه فعال شما: %2$s). جهت همگام‌سازی قابلیت‌ها، پایداری اتوماسیون محتوا و اتصال ایمن به پنل، افزونه را به جدیدترین نسخه ارتقا دهید.', 'kavoshrank' ),
                    '<strong style="color: #047857;">' . esc_html( $remote_info['version'] ) . '</strong>',
                    '<code style="background: #e2e8f0; padding: 1px 5px; border-radius: 4px;">' . esc_html( $current_version ) . '</code>'
                );
                ?>
            </p>
            <p style="margin: 0; display: flex; align-items: center; gap: 8px;">
                <a href="<?php echo esc_url( $update_url ); ?>" class="button button-primary" style="background: #059669; border-color: #047857; text-shadow: none; font-weight: 700; box-shadow: 0 1px 3px rgba(5,150,105,0.3);">
                    <?php esc_html_e( 'بروزرسانی خودکار در وردپرس', 'kavoshrank' ); ?>
                </a>
                <a href="<?php echo esc_url( $package_url ); ?>" class="button button-secondary" style="font-weight: 600;" download="kavoshrank-connector.zip">
                    <?php esc_html_e( 'دانلود مستقیم فایل ZIP افزونه', 'kavoshrank' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}

