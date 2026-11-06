<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Admin_Notifier {

    public function init() {
        // 1. Hook for New User Register
        add_action( 'user_register', array( $this, 'hook_new_user_registered' ), 10, 1 );

        // 2. Hook for New Comments (filtering AI-generated comments and admin replies)
        add_action( 'comment_post', array( $this, 'hook_new_comment' ), 20, 3 );

        // 3. Hooks for WooCommerce orders & inquiries
        add_action( 'woocommerce_new_order', array( $this, 'hook_new_woocommerce_order' ), 20, 1 );
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'hook_new_woocommerce_checkout_order' ), 20, 1 );

        // 4. Hooks for Custom Form Builder submissions
        add_action( 'kavoshrank_form_submitted', array( $this, 'hook_form_submitted' ), 20, 3 );

        // 5. Hooks for Newsletter / Lead signups
        add_action( 'kavoshrank_lead_created', array( $this, 'hook_lead_created' ), 20, 3 );
    }

    /**
     * Retrieve local admin Telegram settings from WordPress options.
     */
    private function get_settings() {
        return array(
            'token'          => get_option( 'kavoshrank_admin_tg_bot_token', get_option( 'tsw_admin_tg_bot_token', '' ) ),
            'chat_id'        => get_option( 'kavoshrank_admin_tg_chat_id', get_option( 'tsw_admin_tg_chat_id', '' ) ),
            'alert_comments' => (bool) get_option( 'kavoshrank_admin_alert_comments', get_option( 'tsw_admin_alert_comments', 1 ) ),
            'alert_orders'   => (bool) get_option( 'kavoshrank_admin_alert_orders', get_option( 'tsw_admin_alert_orders', 1 ) ),
            'alert_users'    => (bool) get_option( 'kavoshrank_admin_alert_users', get_option( 'tsw_admin_alert_users', 1 ) ),
            'alert_forms'    => (bool) get_option( 'kavoshrank_admin_alert_forms', get_option( 'tsw_admin_alert_forms', 1 ) ),
            'alert_leads'    => (bool) get_option( 'kavoshrank_admin_alert_leads', get_option( 'tsw_admin_alert_leads', 1 ) ),
        );
    }

    /**
     * Core Delivery Method: Send notification message directly and forward to backend engine.
     */
    public function send_admin_alert( $event, $payload, $formatted_telegram_msg = '' ) {
        // 1. Forward event payload to central KavoshRank engine
        if ( KavoshRank_API_Client::is_configured() ) {
            KavoshRank_API_Client::post( 'api/v1/plugin/events', array(
                'event'   => $event,
                'payload' => $payload,
            ) );
        }

        // 2. Direct Telegram notification if local WP token is configured
        $settings = $this->get_settings();
        if ( ! empty( $settings['token'] ) && ! empty( $settings['chat_id'] ) && ! empty( $formatted_telegram_msg ) ) {
            $api_url = "https://api.telegram.org/bot" . $settings['token'] . "/sendMessage";
            $body = array(
                'chat_id'                  => $settings['chat_id'],
                'text'                     => $formatted_telegram_msg,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
            );

            wp_remote_post( $api_url, array(
                'body'        => $body,
                'timeout'     => 5,
                'blocking'    => false,
                'redirection' => 5,
            ) );
        }

        return true;
    }

    /**
     * Triggered on user_register hook.
     */
    public function hook_new_user_registered( $user_id ) {
        $settings = $this->get_settings();
        if ( ! $settings['alert_users'] ) {
            return;
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return;
        }

        $username     = $user->user_login;
        $email        = $user->user_email;
        $reg_date     = current_time( 'mysql' );
        $persian_date = $this->format_to_shamsi_date( $reg_date );

        $msg = "<b>[ثبت‌نام کاربر جدید در سایت]</b>\n\n";
        $msg .= "<b>نام کاربری / ایمیل:</b> " . esc_html( $username ) . " / " . esc_html( $email ) . "\n";
        $msg .= "<b>تاریخ:</b> " . esc_html( $persian_date ) . "\n";

        $payload = array(
            'user_id'    => $user_id,
            'username'   => $username,
            'user_login' => $username,
            'email'      => $email,
            'user_email' => $email,
            'date'       => $persian_date,
        );

        $this->send_admin_alert( 'user_registered', $payload, $msg );
    }

    /**
     * Triggered on comment_post hook.
     */
    public function hook_new_comment( $comment_ID, $comment_approved, $commentdata ) {
        $settings = $this->get_settings();
        if ( ! $settings['alert_comments'] ) {
            return;
        }

        // Filter out AI-generated comments
        $is_generated = get_comment_meta( $comment_ID, '_kavoshrank_generated', true ) || get_comment_meta( $comment_ID, '_tsw_generated', true );
        if ( $is_generated ) {
            return;
        }

        // Filter out administrator replies
        $user_id = $commentdata['user_id'] ?? 0;
        if ( $user_id > 0 ) {
            $user = get_userdata( $user_id );
            if ( $user && in_array( 'administrator', (array) $user->roles, true ) ) {
                return;
            }
        }

        $post_id    = $commentdata['comment_post_ID'] ?? 0;
        $post_title = get_the_title( $post_id );
        $author     = $commentdata['comment_author'] ?? '';
        $content    = $commentdata['comment_content'] ?? '';
        $admin_link = admin_url( 'comment.php?action=editcomment&c=' . $comment_ID );

        $msg = "<b>[دیدگاه جدید در سایت]</b>\n\n";
        $msg .= "<b>صفحه:</b> " . esc_html( $post_title ) . "\n";
        $msg .= "<b>فرستنده:</b> " . esc_html( $author ) . "\n";
        $msg .= "<b>متن دیدگاه:</b>\n" . esc_html( $content ) . "\n\n";
        $msg .= "<b>لینک مدیریت/پاسخ:</b>\n<a href=\"" . esc_url( $admin_link ) . "\">تایید و پاسخ در وردپرس</a>\n";

        $payload = array(
            'comment_id'      => $comment_ID,
            'post_id'         => $post_id,
            'page_title'      => $post_title,
            'author'          => $author,
            'content'         => $content,
            'admin_link'      => $admin_link,
            'is_generated'    => false,
        );

        $this->send_admin_alert( 'comment_created', $payload, $msg );
    }

    /**
     * Triggered on woocommerce_new_order hook.
     */
    public function hook_new_woocommerce_order( $order_id, $order = null ) {
        static $processed_orders = array();
        if ( in_array( $order_id, $processed_orders, true ) ) {
            return;
        }
        $processed_orders[] = $order_id;

        $settings = $this->get_settings();
        if ( ! $settings['alert_orders'] ) {
            return;
        }

        $this->send_woocommerce_order_alert( $order_id );
    }

    /**
     * Triggered on woocommerce_checkout_order_processed hook.
     */
    public function hook_new_woocommerce_checkout_order( $order_id, $posted_data = null, $order = null ) {
        static $processed_orders_checkout = array();
        if ( in_array( $order_id, $processed_orders_checkout, true ) ) {
            return;
        }
        $processed_orders_checkout[] = $order_id;

        $settings = $this->get_settings();
        if ( ! $settings['alert_orders'] ) {
            return;
        }

        $this->send_woocommerce_order_alert( $order_id );
    }

    /**
     * Handle WooCommerce Order alerting logic.
     */
    private function send_woocommerce_order_alert( $order_id ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $name  = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $phone = $order->get_billing_phone();
        $total = $order->get_total();
        $currency = $order->get_currency();

        $items_desc = array();
        foreach ( $order->get_items() as $item_id => $item ) {
            $items_desc[] = $item->get_name() . ' (تعداد: ' . $item->get_quantity() . ')';
        }
        $items_str = implode( ' | ', $items_desc );

        $msg = "<b>[سفارش / استعلام جدید فروشگاه]</b>\n\n";
        $msg .= "<b>شماره سفارش:</b> #" . $order_id . "\n";
        $msg .= "<b>مشتری:</b> " . esc_html( $name ) . "\n";
        $msg .= "<b>تلفن تماس:</b> " . esc_html( $phone ) . "\n";
        $msg .= "<b>مبلغ کل:</b> " . esc_html( $total . ' ' . $currency ) . "\n";
        $msg .= "<b>اقلام سفارش:</b>\n" . esc_html( $items_str ) . "\n";

        $payload = array(
            'order_id'      => $order_id,
            'customer_name' => $name,
            'phone'         => $phone,
            'total'         => $total,
            'currency'      => $currency,
            'items'         => $items_str,
        );

        $this->send_admin_alert( 'order_created', $payload, $msg );
    }

    /**
     * Triggered on form_submitted hook.
     */
    public function hook_form_submitted( $post_id, $form_id, $data ) {
        $settings = $this->get_settings();
        if ( ! $settings['alert_forms'] ) {
            return;
        }

        $form_title = get_the_title( $form_id );
        if ( empty( $form_title ) ) {
            $form_title = "فرم #" . $form_id;
        }

        $ip           = get_post_meta( $post_id, '_kavoshrank_form_ip', true ) ?: '';
        $date         = current_time( 'mysql' );
        $persian_date = $this->format_to_shamsi_date( $date );

        $msg = "<b>[ارسال جدید فرم اختصاصی]</b>\n\n";
        $msg .= "<b>عنوان فرم:</b> " . esc_html( $form_title ) . "\n";
        $msg .= "<b>تاریخ ارسال:</b> " . esc_html( $persian_date ) . "\n";
        if ( ! empty( $ip ) ) {
            $msg .= "<b>آدرس IP:</b> " . esc_html( $ip ) . "\n\n";
        }
        $msg .= "<b>اطلاعات واردشده:</b>\n";

        if ( is_array( $data ) && ! empty( $data ) ) {
            foreach ( $data as $key => $value ) {
                $msg .= "- <b>" . esc_html( $key ) . ":</b> " . esc_html( is_array( $value ) ? json_encode( $value ) : $value ) . "\n";
            }
        }

        $admin_link = admin_url( 'edit.php?post_type=kavoshrank_form&page=kavoshrank-form-submissions&form_id=' . $form_id );
        $msg .= "\n<a href=\"" . esc_url( $admin_link ) . "\">مشاهده تمامی ارسال‌های این فرم</a>\n";

        $payload = array(
            'post_id'    => $post_id,
            'form_id'    => $form_id,
            'form_title' => $form_title,
            'date'       => $persian_date,
            'ip'         => $ip,
            'data'       => $data,
            'admin_link' => $admin_link,
        );

        $this->send_admin_alert( 'form_submitted', $payload, $msg );
    }

    /**
     * Triggered on lead_created hook (newsletter signup).
     */
    public function hook_lead_created( $post_id, $email, $ip ) {
        $settings = $this->get_settings();
        if ( ! $settings['alert_leads'] ) {
            return;
        }

        $date         = current_time( 'mysql' );
        $persian_date = $this->format_to_shamsi_date( $date );
        $source       = get_post_meta( $post_id, '_kavoshrank_lead_source', true ) ?: 'خبرنامه';

        $msg = "<b>[لید / ثبت‌نام خبرنامه جدید]</b>\n\n";
        $msg .= "<b>ایمیل:</b> " . esc_html( $email ) . "\n";
        $msg .= "<b>منبع ورودی:</b> " . esc_html( $source ) . "\n";
        $msg .= "<b>تاریخ:</b> " . esc_html( $persian_date ) . "\n";
        if ( ! empty( $ip ) ) {
            $msg .= "<b>آدرس IP:</b> " . esc_html( $ip ) . "\n\n";
        }

        $admin_link = admin_url( 'edit.php?post_type=kavoshrank_lead' );
        $msg .= "<a href=\"" . esc_url( $admin_link ) . "\">مدیریت لیدها در وردپرس</a>\n";

        $payload = array(
            'post_id'    => $post_id,
            'email'      => $email,
            'source'     => $source,
            'date'       => $persian_date,
            'ip'         => $ip,
            'admin_link' => $admin_link,
        );

        $this->send_admin_alert( 'lead_created', $payload, $msg );
    }

    /**
     * Convert Gregorian date to Shamsi Jalali format.
     */
    private function format_to_shamsi_date( $datetime_str ) {
        if ( class_exists( 'KavoshRank_Jalali' ) || function_exists( 'kavoshrank_gregorian_to_jalali' ) ) {
            $parts = explode( ' ', (string) $datetime_str );
            $date_parts = explode( '-', $parts[0] );
            if ( count( $date_parts ) === 3 ) {
                $jalali = class_exists( 'KavoshRank_Jalali' )
                    ? KavoshRank_Jalali::gregorian_to_jalali( intval( $date_parts[0] ), intval( $date_parts[1] ), intval( $date_parts[2] ) )
                    : kavoshrank_gregorian_to_jalali( intval( $date_parts[0] ), intval( $date_parts[1] ), intval( $date_parts[2] ) );
                $time_part = isset( $parts[1] ) ? ' ' . $parts[1] : '';
                return sprintf( '%04d/%02d/%02d', $jalali[0], $jalali[1], $jalali[2] ) . $time_part;
            }
        }
        return $datetime_str;
    }

    /**
     * Send 404 resolved redirect alert.
     */
    public function send_404_redirect_alert( $url_404, $target_url ) {
        $msg = "<b>[خطای ۴۰۴ سرچ کنسول برطرف شد]</b>\n\n";
        $msg .= "<b>آدرس ۴۰۴:</b> " . esc_html( $url_404 ) . "\n";
        $msg .= "<b>ریدایرکت ۳۰۱ شد به:</b> " . esc_html( $target_url ) . "\n";
        $msg .= "<b>دلیل:</b> شناسایی خودکار و اتصال به نزدیکترین نوشته/محصول مرتبط.";

        $payload = array(
            'url_404'    => $url_404,
            'target_url' => $target_url,
        );

        return $this->send_admin_alert( '404_redirect', $payload, $msg );
    }

    /**
     * Public static helper for sending error notifications/alerts.
     */
    public static function notify( $message, $type = 'error' ) {
        $formatted = "[KavoshRank - " . strtoupper( $type ) . "] " . wp_strip_all_tags( $message );
        $instance = new self();
        $instance->send_admin_alert( 'custom_alert', array( 'message' => $formatted ), $formatted );
    }
}
