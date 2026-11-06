<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KavoshRank Storefront AI Copilot
 *
 * A floating chat widget for pre-purchase questions. The widget calls the
 * public REST route kavoshrank/v1/copilot on this site (no nonce, so it works
 * on page-cached sites, and rate-limited per IP). That route sends the
 * question with the current product's data from WooCommerce to the KavoshRank
 * backend, authenticated by the site token. The widget stays hidden until the
 * site owner enables it in the KavoshRank panel; its settings are synced from
 * the backend in the background.
 */
class KavoshRank_Copilot {

    const SETTINGS_OPTION   = 'kavoshrank_copilot_widget';
    const SYNC_HOOK         = 'kavoshrank_copilot_sync_settings';
    // How long synced widget settings are used before they are refreshed.
    const SYNC_TTL          = 900;
    // Retry delay after the backend could not be reached.
    const SYNC_RETRY        = 300;
    // Messages one IP may send per minute through this site.
    const RATE_LIMIT        = 10;
    const MAX_MESSAGE_CHARS = 1000;
    const MAX_HISTORY       = 6;
    const MAX_RELATED       = 10;
    const REQUEST_TIMEOUT   = 20;

    const UNAVAILABLE_MESSAGE = 'دستیار در حال حاضر در دسترس نیست. لطفاً بعداً دوباره تلاش کنید.';
    const RATE_LIMIT_MESSAGE  = 'تعداد پیام‌ها بیش از حد مجاز است. لطفاً کمی بعد دوباره تلاش کنید.';
    const DISCLOSURE          = 'پاسخ‌ها با هوش مصنوعی و فقط بر اساس اطلاعات محصولات این فروشگاه تولید می‌شوند. پیام شما برای پردازش به سرویس هوش مصنوعی ارسال می‌شود.';

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'wp_footer', array( $this, 'render_widget_markup' ) );

        // Widget settings come from the KavoshRank panel.
        add_action( 'init', array( $this, 'maybe_schedule_sync' ) );
        add_action( self::SYNC_HOOK, array( $this, 'sync_settings' ) );
        add_action( 'update_option_kavoshrank_secret_token', array( $this, 'schedule_sync_now' ) );
        add_action( 'update_option_kavoshrank_golang_api_url', array( $this, 'schedule_sync_now' ) );

        add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
    }

    public function init() {
        // Hooks are registered in the constructor.
    }

    /**
     * The widget is shown only after the site owner enabled it in the panel
     * and the site is connected to KavoshRank. Off by default.
     */
    public function is_enabled() {
        $settings = self::get_widget_settings();
        return ! empty( $settings['is_enabled'] ) && KavoshRank_API_Client::is_configured();
    }

    /**
     * The last widget settings synced from the backend, with safe defaults.
     */
    public static function get_widget_settings() {
        $stored = get_option( self::SETTINGS_OPTION, array() );
        return self::sanitize_widget_settings( is_array( $stored ) ? $stored : array() );
    }

    public static function sanitize_widget_settings( $raw ) {
        $color = isset( $raw['brand_color'] ) ? sanitize_hex_color( (string) $raw['brand_color'] ) : '';
        $questions = array();
        if ( isset( $raw['suggested_questions'] ) && is_array( $raw['suggested_questions'] ) ) {
            foreach ( $raw['suggested_questions'] as $q ) {
                $q = sanitize_text_field( (string) $q );
                if ( '' !== $q ) {
                    $questions[] = $q;
                }
            }
        }

        return array(
            'is_enabled'              => ! empty( $raw['is_enabled'] ),
            'bot_name'                => isset( $raw['bot_name'] ) && '' !== trim( (string) $raw['bot_name'] ) ? sanitize_text_field( (string) $raw['bot_name'] ) : 'مشاور هوشمند خرید',
            'brand_color'             => $color ? $color : '#10b981',
            'position'                => isset( $raw['position'] ) && 'bottom-left' === $raw['position'] ? 'bottom-left' : 'bottom-right',
            'greeting_message'        => isset( $raw['greeting_message'] ) ? sanitize_textarea_field( (string) $raw['greeting_message'] ) : '',
            'suggested_questions'     => array_slice( $questions, 0, 6 ),
            'show_add_to_cart_button' => ! isset( $raw['show_add_to_cart_button'] ) || ! empty( $raw['show_add_to_cart_button'] ),
            'show_product_cards'      => ! isset( $raw['show_product_cards'] ) || ! empty( $raw['show_product_cards'] ),
            'fetched_at'              => isset( $raw['fetched_at'] ) ? (int) $raw['fetched_at'] : 0,
        );
    }

    /**
     * Queues a background refresh of the widget settings when they are older
     * than SYNC_TTL. Never calls the backend during the request itself.
     */
    public function maybe_schedule_sync() {
        if ( ! KavoshRank_API_Client::is_configured() ) {
            return;
        }
        $settings = self::get_widget_settings();
        if ( time() - $settings['fetched_at'] < self::SYNC_TTL ) {
            return;
        }
        if ( ! wp_next_scheduled( self::SYNC_HOOK ) ) {
            wp_schedule_single_event( time(), self::SYNC_HOOK );
        }
    }

    public function schedule_sync_now() {
        wp_clear_scheduled_hook( self::SYNC_HOOK );
        wp_schedule_single_event( time(), self::SYNC_HOOK );
    }

    /**
     * Fetches the widget settings from GET /api/v1/plugin/copilot/settings.
     * A rejected token turns the widget off; a network error keeps the last
     * settings and retries after SYNC_RETRY.
     */
    public function sync_settings() {
        if ( ! KavoshRank_API_Client::is_configured() ) {
            return;
        }

        $response = wp_remote_get( rtrim( KavoshRank_API_Client::get_api_url(), '/' ) . '/api/v1/plugin/copilot/settings', array(
            'timeout' => 10,
            'headers' => array( 'X-KavoshRank-Secret-Token' => KavoshRank_API_Client::get_secret_token() ),
        ) );

        $code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
        if ( 200 === $code ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
                $settings               = self::sanitize_widget_settings( $body['data'] );
                $settings['fetched_at'] = time();
                update_option( self::SETTINGS_OPTION, $settings );
                return;
            }
        }

        if ( in_array( $code, array( 401, 403, 404 ), true ) ) {
            update_option( self::SETTINGS_OPTION, array( 'is_enabled' => false, 'fetched_at' => time() ) );
            return;
        }

        $settings               = self::get_widget_settings();
        $settings['fetched_at'] = time() - self::SYNC_TTL + self::SYNC_RETRY;
        update_option( self::SETTINGS_OPTION, $settings );
    }

    public function register_rest_routes() {
        // Public: shoppers are anonymous, and a nonce would go stale on cached pages.
        register_rest_route( 'kavoshrank/v1', '/copilot', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_consult' ),
            'permission_callback' => '__return_true',
        ) );

        // For the panel simulator, authenticated by the site token.
        register_rest_route( 'kavoshrank/v1', '/copilot/sample-products', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_sample_products' ),
            'permission_callback' => array( new KavoshRank_REST_Controller(), 'verify_token_permission' ),
        ) );
    }

    public function enqueue_assets() {
        if ( is_admin() || ! $this->is_enabled() ) {
            return;
        }

        $settings = self::get_widget_settings();

        wp_enqueue_style( 'kavoshrank-copilot-style', KAVOSHRANK_URL . 'assets/css/copilot.css', array(), KAVOSHRANK_VERSION );
        wp_enqueue_script( 'kavoshrank-copilot-script', KAVOSHRANK_URL . 'assets/js/copilot.js', array(), KAVOSHRANK_VERSION, true );

        wp_localize_script( 'kavoshrank-copilot-script', 'kavoshrankCopilotConfig', array(
            'rest_url'            => esc_url_raw( rest_url( 'kavoshrank/v1/copilot' ) ),
            'product_id'          => is_singular( 'product' ) ? (int) get_the_ID() : 0,
            'brand_color'         => $settings['brand_color'],
            'bot_name'            => $settings['bot_name'],
            'greeting_message'    => $settings['greeting_message'],
            'position'            => $settings['position'],
            'suggested_questions' => $settings['suggested_questions'],
            'unavailable_message' => self::UNAVAILABLE_MESSAGE,
        ) );
    }

    public function render_widget_markup() {
        if ( is_admin() || ! $this->is_enabled() ) {
            return;
        }

        $settings  = self::get_widget_settings();
        $pos_class = ( 'bottom-left' === $settings['position'] ) ? 'kr-pos-left' : 'kr-pos-right';
        $bot_name  = $settings['bot_name'];
        ?>
        <div id="kr-copilot-widget" class="kr-copilot-widget <?php echo esc_attr( $pos_class ); ?>" style="--kr-brand-color: <?php echo esc_attr( $settings['brand_color'] ); ?>;">
            <!-- Floating Launcher -->
            <button id="kr-copilot-launcher" class="kr-copilot-launcher" aria-label="<?php echo esc_attr( $bot_name ); ?>">
                <span class="kr-copilot-launcher-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span class="kr-copilot-badge-pulse"></span>
            </button>

            <!-- Chat Window -->
            <div id="kr-copilot-card" class="kr-copilot-card">
                <!-- Header -->
                <div class="kr-copilot-header">
                    <div class="kr-copilot-header-info">
                        <div class="kr-copilot-avatar">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                                <circle cx="12" cy="5" r="2"></circle>
                                <path d="M12 7v4"></path>
                                <line x1="8" y1="16" x2="8" y2="16"></line>
                                <line x1="16" y1="16" x2="16" y2="16"></line>
                            </svg>
                        </div>
                        <div>
                            <div class="kr-copilot-title"><?php echo esc_html( $bot_name ); ?></div>
                            <div class="kr-copilot-subtitle">
                                <span class="kr-copilot-status-dot"></span>
                                <span>دستیار هوش مصنوعی</span>
                            </div>
                        </div>
                    </div>
                    <button id="kr-copilot-close" class="kr-copilot-close" aria-label="بستن">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <!-- Messages -->
                <div id="kr-copilot-messages" class="kr-copilot-messages"></div>

                <!-- Suggested Questions Pills -->
                <div id="kr-copilot-pills" class="kr-copilot-pills"></div>

                <!-- Footer & Input -->
                <div class="kr-copilot-footer">
                    <div class="kr-copilot-input-wrap">
                        <input type="text" id="kr-copilot-input" class="kr-copilot-input" maxlength="<?php echo esc_attr( self::MAX_MESSAGE_CHARS ); ?>" placeholder="سوال خود را درباره محصول بپرسید..." />
                        <button id="kr-copilot-send" class="kr-copilot-send" aria-label="ارسال">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </button>
                    </div>
                    <div class="kr-copilot-disclosure" style="font-size: 10px; color: #94a3b8; text-align: center; margin-top: 4px;">
                        <?php echo esc_html( self::DISCLOSURE ); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * The shopper's IP, used only for the per-IP limits here and on the backend.
     */
    private static function client_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $ip = (string) apply_filters( 'kavoshrank_copilot_client_ip', $ip );
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
    }

    /**
     * Counts one message for the IP and reports whether it is over the limit.
     */
    private static function over_rate_limit( $ip ) {
        $key   = 'kr_copilot_rate_' . md5( '' !== $ip ? $ip : 'unknown' );
        $count = (int) get_transient( $key );
        if ( $count >= self::RATE_LIMIT ) {
            return true;
        }
        set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
        return false;
    }

    private static function error_response( $message, $status ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => $message,
        ), $status );
    }

    public function rest_consult( WP_REST_Request $request ) {
        if ( ! $this->is_enabled() ) {
            return self::error_response( self::UNAVAILABLE_MESSAGE, 403 );
        }

        $ip = self::client_ip();
        if ( self::over_rate_limit( $ip ) ) {
            return self::error_response( self::RATE_LIMIT_MESSAGE, 429 );
        }

        $params  = $request->get_json_params();
        $params  = is_array( $params ) ? $params : array();
        $message = isset( $params['message'] ) ? trim( sanitize_textarea_field( (string) $params['message'] ) ) : '';
        $message = function_exists( 'mb_substr' ) ? mb_substr( $message, 0, self::MAX_MESSAGE_CHARS ) : substr( $message, 0, self::MAX_MESSAGE_CHARS );
        if ( '' === $message ) {
            return self::error_response( 'لطفاً سوال خود را بنویسید.', 400 );
        }

        $product_id      = isset( $params['product_id'] ) ? absint( $params['product_id'] ) : 0;
        $product_context = $product_id > 0 ? self::build_product_context( $product_id, true ) : null;

        $body = array(
            'message' => $message,
            'history' => self::sanitize_history( isset( $params['history'] ) ? $params['history'] : array() ),
        );
        if ( $product_context ) {
            $body['product_id']      = $product_context['id'];
            $body['product_context'] = $product_context;
        }

        $headers = array(
            'Content-Type'              => 'application/json',
            'X-KavoshRank-Secret-Token' => KavoshRank_API_Client::get_secret_token(),
        );
        if ( '' !== $ip ) {
            $headers['X-KavoshRank-Shopper-IP'] = $ip;
        }

        $response = wp_remote_post( rtrim( KavoshRank_API_Client::get_api_url(), '/' ) . '/api/v1/plugin/copilot/consult', array(
            'timeout' => self::REQUEST_TIMEOUT,
            'headers' => $headers,
            'body'    => wp_json_encode( $body ),
        ) );

        $code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
        if ( 429 === $code ) {
            return self::error_response( self::RATE_LIMIT_MESSAGE, 429 );
        }
        if ( 200 !== $code ) {
            return self::error_response( self::UNAVAILABLE_MESSAGE, 503 );
        }

        $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
        $data    = isset( $decoded['data'] ) && is_array( $decoded['data'] ) ? $decoded['data'] : array();
        $answer  = isset( $data['answer'] ) ? trim( sanitize_textarea_field( (string) $data['answer'] ) ) : '';
        if ( '' === $answer ) {
            return self::error_response( self::UNAVAILABLE_MESSAGE, 503 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array(
                'answer'          => $answer,
                'recommendations' => self::filter_recommendations( isset( $data['recommendations'] ) ? $data['recommendations'] : array() ),
            ),
        ), 200 );
    }

    /**
     * Keeps earlier turns as plain role and text, bounded in count and size.
     */
    public static function sanitize_history( $history ) {
        $out = array();
        if ( ! is_array( $history ) ) {
            return $out;
        }
        foreach ( array_slice( $history, -self::MAX_HISTORY ) as $turn ) {
            if ( ! is_array( $turn ) || ! isset( $turn['role'], $turn['content'] ) ) {
                continue;
            }
            $role = 'assistant' === $turn['role'] ? 'assistant' : 'user';
            $text = trim( sanitize_textarea_field( (string) $turn['content'] ) );
            if ( '' === $text ) {
                continue;
            }
            $out[] = array(
                'role'    => $role,
                'content' => function_exists( 'mb_substr' ) ? mb_substr( $text, 0, 500 ) : substr( $text, 0, 500 ),
            );
        }
        return $out;
    }

    /**
     * Applies the owner's card settings to the recommended products.
     */
    private static function filter_recommendations( $recs ) {
        $settings = self::get_widget_settings();
        if ( ! $settings['show_product_cards'] || ! is_array( $recs ) ) {
            return array();
        }
        $out = array();
        foreach ( $recs as $rec ) {
            if ( ! is_array( $rec ) || empty( $rec['id'] ) || empty( $rec['title'] ) ) {
                continue;
            }
            $out[] = array(
                'id'              => absint( $rec['id'] ),
                'title'           => sanitize_text_field( (string) $rec['title'] ),
                'price'           => isset( $rec['price'] ) ? sanitize_text_field( (string) $rec['price'] ) : '',
                'permalink'       => isset( $rec['permalink'] ) ? esc_url_raw( (string) $rec['permalink'] ) : '',
                'add_to_cart_url' => $settings['show_add_to_cart_button'] && isset( $rec['add_to_cart_url'] ) ? esc_url_raw( (string) $rec['add_to_cart_url'] ) : '',
            );
        }
        return $out;
    }

    /**
     * The grounding data of a published, visible product: name, prices, stock,
     * attributes, short description and links, plus up to MAX_RELATED related
     * products when $with_related is set. Returns null for anything else.
     */
    public static function build_product_context( $product_id, $with_related ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return null;
        }
        $product = wc_get_product( $product_id );
        if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
            return null;
        }

        $context = array(
            'id'                => (int) $product->get_id(),
            'title'             => $product->get_name(),
            'price'             => self::plain_price( $product->get_price() ),
            'regular_price'     => self::plain_price( $product->get_regular_price() ),
            'sale_price'        => $product->is_on_sale() ? self::plain_price( $product->get_sale_price() ) : '',
            'stock_status'      => $product->get_stock_status(),
            'short_description' => self::plain_text( $product->get_short_description(), $with_related ? 600 : 200 ),
            'permalink'         => get_permalink( $product->get_id() ),
            'add_to_cart_url'   => self::add_to_cart_url( $product ),
            'image_url'         => (string) wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
        );

        $attributes = self::product_attributes( $product );
        if ( ! empty( $attributes ) ) {
            // An object, so an empty set never becomes a JSON array.
            $context['attributes'] = (object) $attributes;
        }

        if ( $with_related ) {
            $related = array();
            $ids     = array_merge( $product->get_upsell_ids(), function_exists( 'wc_get_related_products' ) ? wc_get_related_products( $product->get_id(), self::MAX_RELATED ) : array() );
            foreach ( array_unique( array_map( 'absint', $ids ) ) as $rel_id ) {
                if ( count( $related ) >= self::MAX_RELATED ) {
                    break;
                }
                $rel = self::build_product_context( $rel_id, false );
                if ( $rel ) {
                    $related[] = $rel;
                }
            }
            if ( ! empty( $related ) ) {
                $context['related_products'] = $related;
            }
        }

        return $context;
    }

    private static function product_attributes( $product ) {
        $out = array();
        foreach ( $product->get_attributes() as $attribute ) {
            if ( ! is_a( $attribute, 'WC_Product_Attribute' ) || ! $attribute->get_visible() ) {
                continue;
            }
            $name = wc_attribute_label( $attribute->get_name(), $product );
            if ( $attribute->is_taxonomy() ) {
                $values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
            } else {
                $values = $attribute->get_options();
            }
            $values = array_filter( array_map( 'wp_strip_all_tags', (array) $values ) );
            if ( '' !== $name && ! empty( $values ) ) {
                $out[ $name ] = implode( '، ', $values );
            }
            if ( count( $out ) >= 20 ) {
                break;
            }
        }
        return $out;
    }

    private static function add_to_cart_url( $product ) {
        if ( ! $product->is_type( 'simple' ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
            return '';
        }
        return esc_url_raw( add_query_arg( 'add-to-cart', $product->get_id(), get_permalink( $product->get_id() ) ) );
    }

    private static function plain_price( $amount ) {
        if ( '' === (string) $amount ) {
            return '';
        }
        // wc_price separates the amount and currency with &nbsp;.
        $text = html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
        return trim( str_replace( "\xC2\xA0", ' ', $text ) );
    }

    private static function plain_text( $html, $max ) {
        $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $html ) ) );
        return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $max ) : substr( $text, 0, $max );
    }

    /**
     * GET kavoshrank/v1/copilot/sample-products: recent published products
     * with their grounding data, for the simulator in the KavoshRank panel.
     */
    public function rest_sample_products( WP_REST_Request $request ) {
        $products = array();
        if ( function_exists( 'wc_get_products' ) ) {
            $ids = wc_get_products( array(
                'status'  => 'publish',
                'limit'   => 12,
                'orderby' => 'date',
                'order'   => 'DESC',
                'return'  => 'ids',
            ) );
            foreach ( (array) $ids as $id ) {
                $context = self::build_product_context( $id, true );
                if ( $context ) {
                    $products[] = $context;
                }
            }
        }

        return new WP_REST_Response( array( 'products' => $products ), 200 );
    }

    /**
     * Suggested text for the site's privacy policy (Settings > Privacy).
     */
    public function add_privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }

        $content = '<p>' . esc_html( 'دستیار هوشمند فروشگاه: وقتی از دستیار هوشمند خرید سوالی می‌پرسید، متن پیام شما، چند پیام قبلی همان گفت‌وگو، اطلاعات محصولی که مشاهده می‌کنید و نشانی IP شما به سرورهای KavoshRank ارسال می‌شود. KavoshRank متن پیام و اطلاعات محصول را برای تولید پاسخ به یک ارائه‌دهنده هوش مصنوعی شخص ثالث (Google Gemini) می‌فرستد. نشانی IP فقط برای محدود کردن تعداد پیام‌ها استفاده می‌شود و به ارائه‌دهنده هوش مصنوعی داده نمی‌شود. لطفاً اطلاعات شخصی مانند نام، شماره تلفن یا نشانی را در پیام وارد نکنید.' ) . '</p>'
            . '<p>' . esc_html( 'خلاصه دیدگاه‌ها: برای ساخت خلاصه دیدگاه‌های یک محصول، متن و امتیاز دیدگاه‌های تاییدشده، بدون نام و ایمیل نویسنده، به KavoshRank و ارائه‌دهنده هوش مصنوعی ارسال می‌شود.' ) . '</p>'
            . '<p>' . esc_html( 'Storefront assistant: when you ask the shopping assistant a question, your message, the previous messages of that conversation, the product you are viewing and your IP address are sent to KavoshRank. KavoshRank sends the message and product data to a third-party AI provider (Google Gemini) to generate the answer. The IP address is used only to limit the number of messages and is not given to the AI provider. Review summaries: the text and rating of approved reviews, without the author\'s name or email, are sent to KavoshRank and the AI provider to summarize them.' ) . '</p>';

        wp_add_privacy_policy_content( 'KavoshRank', wp_kses_post( $content ) );
    }
}
