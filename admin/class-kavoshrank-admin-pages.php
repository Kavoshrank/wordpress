<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Admin_Pages {

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_kavoshrank_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_kavoshrank_regenerate_secret_token', array( $this, 'ajax_regenerate_secret_token' ) );
    }

    public function add_menu_pages() {
        add_menu_page(
            __( 'مدیریت هوشمند سئو کاوش‌رنک', 'kavoshrank' ),
            __( 'کاوش‌رنک', 'kavoshrank' ),
            'manage_options',
            'kavoshrank',
            array( $this, 'render_dashboard_page' ),
            'dashicons-chart-area',
            30
        );

        add_submenu_page(
            'kavoshrank',
            __( 'داشبورد و وضعیت اتصال کاوش‌رنک', 'kavoshrank' ),
            __( 'داشبورد', 'kavoshrank' ),
            'manage_options',
            'kavoshrank',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'kavoshrank',
            __( 'تنظیمات اتصال به پلتفرم کاوش‌رنک', 'kavoshrank' ),
            __( 'تنظیمات اتصال', 'kavoshrank' ),
            'manage_options',
            'kavoshrank-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_golang_api_url', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_api_url' ),
            'default'           => 'https://backend.kavoshrank.ir',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_secret_token', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_secret_token' ),
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_disable_gutenberg', array(
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_enable_eeat_box', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_eeat_badge_text', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'بازبینی و ارزیابی محتوا',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_eeat_reviewer_title', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'تیم تحریریه و کارشناسان',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_enable_cro_widgets', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_contact_phone', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_contact_url', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_enable_auto_linking', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_enable_schema', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_override_wc_schema', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_return_policy_days', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_day_count' ),
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_return_policy_fees', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_return_fees' ),
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_shipping_rate', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_shipping_rate' ),
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_shipping_handling_days', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_day_count' ),
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_shipping_transit_days', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_day_count' ),
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_bot_firewall_enabled', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_bot_governor_preset', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'balanced',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_preload_vazirmatn', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_runtime_healer_enabled', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_healer_font_preload', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_healer_lcp_preload', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_healer_smart_alt', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_healer_aria_labels', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_enable_webp_rewrite', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_enable_local_seo', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_name', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_type', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'LocalBusiness',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_address', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_locality', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_region', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_postal_code', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_country', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_phone', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_geo_lat', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_geo_lng', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_opening_hours', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_logo', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_local_business_price_range', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'kavoshrank_settings_group', 'kavoshrank_erase_data_on_uninstall', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ) );
    }

    /**
     * Whole number of days, or '' when left empty (the related schema is then omitted).
     */
    public function sanitize_day_count( $value ) {
        $value = trim( (string) $value );
        return ( '' === $value || ! is_numeric( $value ) ) ? '' : (string) absint( $value );
    }

    public function sanitize_return_fees( $value ) {
        return in_array( $value, array( 'free', 'customer' ), true ) ? $value : '';
    }

    /**
     * Non-negative amount in the store currency, or '' when left empty.
     */
    public function sanitize_shipping_rate( $value ) {
        $value = trim( (string) $value );
        if ( '' === $value || ! is_numeric( $value ) || (float) $value < 0 ) {
            return '';
        }
        return $value;
    }

    public function sanitize_api_url( $url ) {
        $clean = esc_url_raw( trim( (string) $url ) );
        if ( empty( $clean ) ) {
            return 'https://backend.kavoshrank.ir';
        }
        return $clean;
    }

    public function sanitize_secret_token( $token ) {
        $clean = sanitize_text_field( trim( (string) $token ) );
        if ( empty( $clean ) ) {
            add_settings_error( 'kavoshrank_secret_token', 'empty_token', __( 'کلید امنیتی اتصال نمی‌تواند خالی باشد.', 'kavoshrank' ), 'error' );
            return (string) get_option( 'kavoshrank_secret_token', '' );
        }
        if ( ! KavoshRank_API_Client::is_strong_token( $clean ) ) {
            add_settings_error( 'kavoshrank_secret_token', 'weak_token', __( 'کلید امنیتی باید حداقل ۳۲ کاراکتر بوده و نمی‌تواند از مقادیر پیش‌فرض استفاده کند.', 'kavoshrank' ), 'error' );
            return (string) get_option( 'kavoshrank_secret_token', '' );
        }
        return $clean;
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'kavoshrank' ) === false ) {
            return;
        }

        wp_enqueue_style( 'kavoshrank-admin-css', KAVOSHRANK_URL . 'admin/css/admin.css', array(), KAVOSHRANK_VERSION );
        wp_enqueue_script( 'kavoshrank-admin-js', KAVOSHRANK_URL . 'admin/js/admin.js', array( 'jquery' ), KAVOSHRANK_VERSION, true );

        wp_localize_script( 'kavoshrank-admin-js', 'kavoshRankAdmin', array(
            'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
            'nonce'             => wp_create_nonce( 'kavoshrank_admin_nonce' ),
            // Checked by the kavoshrank_generate_review_digest AJAX action.
            'reviewDigestNonce' => wp_create_nonce( 'kavoshrank_nonce' ),
            'golangUrl'         => get_option( 'kavoshrank_golang_api_url', 'https://backend.kavoshrank.ir' ) ?: 'https://backend.kavoshrank.ir',
            'homeUrl'           => trailingslashit( home_url() ),
            'isRtl'             => kavoshrank_is_rtl(),
        ) );
    }

    public function ajax_regenerate_secret_token() {
        check_ajax_referer( 'kavoshrank_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'سطح دسترسی شما برای این عملیات مجاز نیست.', 'kavoshrank' ) ), 403 );
        }

        wp_send_json_success( array( 'token' => KavoshRank_API_Client::generate_token() ) );
    }

    public function ajax_test_connection() {
        check_ajax_referer( 'kavoshrank_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'سطح دسترسی شما برای این عملیات مجاز نیست.', 'kavoshrank' ) ), 403 );
        }

        $api_url = isset( $_POST['api_url'] ) && '' !== trim( (string) $_POST['api_url'] ) ? esc_url_raw( trim( (string) $_POST['api_url'] ) ) : get_option( 'kavoshrank_golang_api_url', 'https://backend.kavoshrank.ir' );
        if ( empty( $api_url ) ) {
            $api_url = 'https://backend.kavoshrank.ir';
        }
        $token   = isset( $_POST['token'] ) ? sanitize_text_field( trim( (string) $_POST['token'] ) ) : get_option( 'kavoshrank_secret_token', '' );

        if ( empty( $api_url ) ) {
            wp_send_json_error( array( 'message' => __( 'آدرس API پلتفرم الزامی است.', 'kavoshrank' ) ) );
        }

        $base_url  = rtrim( $api_url, '/' );
        $endpoints = array(
            $base_url . '/health/live',
            $base_url . '/api/v1/health/check',
            $base_url . '/health',
        );

        $start_time = microtime( true );
        $response   = null;
        $code       = 0;

        foreach ( $endpoints as $endpoint ) {
            $response = wp_safe_remote_get( $endpoint, array(
                'timeout'   => 8,
                'sslverify' => true,
                'headers'   => array(
                    'Accept'                    => 'application/json',
                    'X-KavoshRank-Secret-Token' => $token,
                    'User-Agent'                => 'KavoshRank-WordPress-Client/' . KAVOSHRANK_VERSION,
                ),
            ) );

            if ( ! is_wp_error( $response ) ) {
                $code = wp_remote_retrieve_response_code( $response );
                if ( $code >= 200 && $code < 400 ) {
                    break;
                }
            }
        }

        $latency = round( ( microtime( true ) - $start_time ) * 1000 );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array(
                'message'    => sprintf( __( 'خطا در برقراری اتصال: %s', 'kavoshrank' ), $response->get_error_message() ),
                'latency_ms' => $latency,
            ) );
        }

        if ( $code >= 200 && $code < 400 ) {
            wp_send_json_success( array(
                'message'    => __( 'ارتباط زنده با پلتفرم کاوش‌رنک با موفقیت تایید شد. پلتفرم فعال و آماده به کار است.', 'kavoshrank' ),
                'latency_ms' => $latency,
                'status'     => $code,
            ) );
        } else {
            wp_send_json_error( array(
                'message'    => sprintf( __( 'عدم موفقیت در برقراری ارتباط با سرور (کد وضعیت: HTTP %d).', 'kavoshrank' ), $code ),
                'latency_ms' => $latency,
                'status'     => $code,
            ) );
        }
    }

    public function render_dashboard_page() {
        include KAVOSHRANK_PATH . 'admin/views/dashboard.php';
    }

    public function render_settings_page() {
        include KAVOSHRANK_PATH . 'admin/views/settings.php';
    }

}
