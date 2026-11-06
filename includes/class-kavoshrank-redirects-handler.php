<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Redirect_REST_Handler {

    const REDIRECTS_OPTION = 'kavoshrank_redirects';
    const ALLOWED_STATUSES = array( 301, 302, 307, 308 );

    public static function init() {
        if ( ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() ) {
            add_action( 'template_redirect', array( __CLASS__, 'handle_frontend_redirect' ), 1 );
        }
    }

    /**
     * Reads the normalized redirect map and issues 301/302 redirects
     */
    public static function handle_frontend_redirect() {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }

        $uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        if ( empty( $uri ) ) {
            return;
        }

        $path = '/' . trim( parse_url( $uri, PHP_URL_PATH ), '/' );
        $redirects = self::get_all_redirects();

        if ( isset( $redirects[ $path ] ) ) {
            $entry = $redirects[ $path ];
            $target = is_array( $entry ) ? ( $entry['target'] ?? '' ) : $entry;
            $status = self::sanitize_status( is_array( $entry ) ? ( $entry['status'] ?? 301 ) : 301 );

            if ( ! empty( $target ) ) {
                wp_redirect( esc_url_raw( $target ), $status );
                exit;
            }
        }
    }

    /**
     * wp_redirect() dies on a non-3xx status, so anything else becomes a 301.
     */
    public static function sanitize_status( $status ) {
        $status = absint( $status );
        return in_array( $status, self::ALLOWED_STATUSES, true ) ? $status : 301;
    }

    /**
     * Returns unified normalized redirect map, migrating legacy option names if needed
     */
    public static function get_all_redirects() {
        $redirects = get_option( self::REDIRECTS_OPTION, null );
        if ( is_array( $redirects ) ) {
            return $redirects;
        }

        $redirects = array();

        // Migrate from kavoshrank_redirects_map
        $map = get_option( 'kavoshrank_redirects_map', array() );
        if ( is_array( $map ) ) {
            foreach ( $map as $k => $v ) {
                $redirects[ '/' . trim( $k, '/' ) ] = $v;
            }
        }

        // Migrate from kavoshrank_custom_301_redirects & tsw_custom_301_redirects
        $custom = get_option( 'kavoshrank_custom_301_redirects', get_option( 'tsw_custom_301_redirects', array() ) );
        if ( is_array( $custom ) ) {
            foreach ( $custom as $k => $v ) {
                $k_clean = '/' . trim( $k, '/' );
                if ( ! isset( $redirects[ $k_clean ] ) ) {
                    $redirects[ $k_clean ] = array(
                        'target' => $v,
                        'status' => 301,
                    );
                }
            }
        }

        update_option( self::REDIRECTS_OPTION, $redirects, false );
        return $redirects;
    }

    public static function handle_register_redirect( WP_REST_Request $request ) {
        $params   = $request->get_json_params();
        $old_slug = sanitize_text_field( $params['old_slug'] ?? $params['source'] ?? $params['source_url'] ?? '' );
        $new_slug = sanitize_text_field( $params['new_slug'] ?? $params['target'] ?? $params['target_url'] ?? '' );
        $type     = self::sanitize_status( $params['type'] ?? $params['status_code'] ?? 301 );

        if ( empty( $old_slug ) || empty( $new_slug ) ) {
            return new WP_REST_Response( array( 'error' => 'Missing old or new slug parameters' ), 400 );
        }

        $old_path = '/' . trim( parse_url( $old_slug, PHP_URL_PATH ), '/' );
        $new_target = $new_slug;
        if ( strpos( $new_target, 'http' ) !== 0 && strpos( $new_target, '/' ) !== 0 ) {
            $new_target = '/' . $new_target;
        }

        // Yoast SEO Premium Redirects Integration
        if ( class_exists( 'WPSEO_Redirect_Option' ) ) {
            $redirect_option = new WPSEO_Redirect_Option();
            $redirect_option->add( $old_slug, $new_slug, $type );
        }

        $redirects = self::get_all_redirects();
        $redirects[ $old_path ] = array(
            'id'          => md5( $old_path ),
            'source'      => $old_path,
            'target'      => $new_target,
            'status'      => $type,
            'created_at'  => current_time( 'mysql' ),
        );
        update_option( self::REDIRECTS_OPTION, $redirects, false );

        return new WP_REST_Response( array(
            'status'     => 'success',
            'old_slug'   => $old_path,
            'new_slug'   => $new_target,
            'redirect'   => $redirects[ $old_path ],
        ), 200 );
    }

    public static function handle_list_redirects( WP_REST_Request $request ) {
        $redirects = self::get_all_redirects();
        $list = array();
        foreach ( $redirects as $path => $entry ) {
            $list[] = array(
                'id'         => is_array( $entry ) ? ( $entry['id'] ?? md5( $path ) ) : md5( $path ),
                'source_url' => $path,
                'target_url' => is_array( $entry ) ? ( $entry['target'] ?? '' ) : $entry,
                'status_code'=> is_array( $entry ) ? intval( $entry['status'] ?? 301 ) : 301,
                'created_at' => is_array( $entry ) ? ( $entry['created_at'] ?? current_time( 'mysql' ) ) : current_time( 'mysql' ),
            );
        }

        return new WP_REST_Response( array(
            'status'    => 'success',
            'redirects' => $list,
        ), 200 );
    }

    public static function handle_delete_redirect( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $id     = sanitize_text_field( $request->get_param( 'id' ) ?? $params['id'] ?? '' );
        $source = sanitize_text_field( $params['source_url'] ?? $params['source'] ?? '' );

        $redirects = self::get_all_redirects();
        $deleted = false;

        if ( ! empty( $source ) ) {
            $source_path = '/' . trim( parse_url( $source, PHP_URL_PATH ), '/' );
            if ( isset( $redirects[ $source_path ] ) ) {
                unset( $redirects[ $source_path ] );
                $deleted = true;
            }
        } elseif ( ! empty( $id ) ) {
            foreach ( $redirects as $path => $entry ) {
                $entryId = is_array( $entry ) ? ( $entry['id'] ?? md5( $path ) ) : md5( $path );
                if ( $entryId === $id ) {
                    unset( $redirects[ $path ] );
                    $deleted = true;
                    break;
                }
            }
        }

        if ( $deleted ) {
            update_option( self::REDIRECTS_OPTION, $redirects, false );
        }

        return new WP_REST_Response( array(
            'status'  => 'success',
            'deleted' => $deleted,
        ), 200 );
    }
}

