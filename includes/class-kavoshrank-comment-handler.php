<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class KavoshRank_Comment_REST_Handler
 * Publishes generated Q&A as a store-owner FAQ block (product tab and FAQPage schema).
 * On posts the pairs are also added as threaded comments authored by the store.
 */
class KavoshRank_Comment_REST_Handler {

    /**
     * Initialize hooks
     */
    public static function init() {
        add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'add_faq_product_tab' ) );
    }

    /**
     * Add store FAQ tab to WooCommerce product pages
     *
     * @param array $tabs
     * @return array
     */
    public static function add_faq_product_tab( $tabs ) {
        global $post;
        if ( ! $post ) {
            return $tabs;
        }
        $faqs = get_post_meta( $post->ID, '_kavoshrank_faqs', true );
        if ( ! empty( $faqs ) && is_array( $faqs ) ) {
            $tabs['kavoshrank_faq_tab'] = array(
                'title'    => 'پرسش‌های متداول',
                'priority' => 35,
                'callback' => array( __CLASS__, 'render_faq_product_tab' ),
            );
        }
        return $tabs;
    }

    /**
     * Render store FAQ tab content on product page
     */
    public static function render_faq_product_tab() {
        global $post;
        if ( ! $post ) {
            return;
        }
        $faqs = get_post_meta( $post->ID, '_kavoshrank_faqs', true );
        if ( empty( $faqs ) || ! is_array( $faqs ) ) {
            return;
        }
        echo '<div class="kavoshrank-faq-wrapper">';
        echo '<h2>پرسش و پاسخ‌های متداول</h2>';
        echo '<div class="kavoshrank-faq-list">';
        foreach ( $faqs as $item ) {
            $q = esc_html( $item['question'] ?? '' );
            $a = esc_html( $item['answer'] ?? '' );
            if ( ! empty( $q ) && ! empty( $a ) ) {
                echo '<div class="kavoshrank-faq-item" style="margin-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">';
                echo '<h4 style="margin: 0 0 0.5rem; font-weight: 600; color: #1e293b;">' . $q . '</h4>';
                echo '<p style="margin: 0; color: #475569; line-height: 1.6;">' . $a . '</p>';
                echo '</div>';
            }
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Handle REST API request to publish Q&A as store FAQs.
     *
     * Every entry is authored by the store with the current timestamp and no rating.
     * Products get the FAQ tab only, so nothing lands in the WooCommerce reviews list.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function handle_publish_qa_comments( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        $post_id      = absint( $params['post_id'] ?? 0 );
        $target_title = sanitize_text_field( $params['target_title'] ?? '' );
        $pairs        = $params['pairs'] ?? array();

        if ( empty( $pairs ) || ! is_array( $pairs ) ) {
            return new WP_REST_Response( array(
                'status'  => 'error',
                'message' => 'لیست پرسش و پاسخ‌ها خالی یا نامعتبر است.',
                'results' => array(),
            ), 400 );
        }

        // If post_id is not provided, try to find post by title
        if ( ! $post_id && ! empty( $target_title ) ) {
            $found_post = get_page_by_title( $target_title, OBJECT, array( 'post', 'page', 'product' ) );
            if ( $found_post ) {
                $post_id = $found_post->ID;
            } else {
                // Try fuzzy/search lookup
                $search_posts = get_posts( array(
                    's'              => $target_title,
                    'post_type'      => array( 'post', 'page', 'product' ),
                    'posts_per_page' => 1,
                    'post_status'    => 'publish',
                ) );
                if ( ! empty( $search_posts ) ) {
                    $post_id = $search_posts[0]->ID;
                }
            }
        }

        $post = $post_id ? get_post( $post_id ) : null;
        if ( ! $post ) {
            return new WP_REST_Response( array(
                'status'  => 'error',
                'message' => 'نوشته یا محصول مقصد در وردپرس یافت نشد.',
                'results' => array(),
            ), 404 );
        }

        // Admin and store identity resolution
        $admin_users = get_users( array(
            'role'   => 'administrator',
            'number' => 1,
        ) );
        $admin_user  = ! empty( $admin_users ) ? $admin_users[0] : null;
        $admin_id    = $admin_user ? $admin_user->ID : 1;
        $admin_email = $admin_user ? $admin_user->user_email : get_option( 'admin_email' );
        $brand_name  = get_option( 'kavoshrank_brand_name', get_option( 'tsw_brand_name', get_bloginfo( 'name' ) ) );
        if ( empty( $brand_name ) ) {
            $brand_name = 'پشتیبانی فروشگاه';
        }

        // Load existing structured FAQs
        $existing_faqs = get_post_meta( $post_id, '_kavoshrank_faqs', true );
        if ( ! is_array( $existing_faqs ) ) {
            $existing_faqs = array();
        }

        // Product Q&A lives in the FAQ tab and schema only; comments on a product
        // would show up as unrated store "reviews".
        $as_comments = 'product' !== $post->post_type;

        $now     = current_time( 'timestamp' );
        $results = array();
        $index   = 0;

        foreach ( $pairs as $pair ) {
            $question = sanitize_textarea_field( $pair['question'] ?? '' );
            $reply    = sanitize_textarea_field( $pair['reply'] ?? ( $pair['answer'] ?? '' ) );

            if ( empty( $question ) || empty( $reply ) ) {
                $results[] = array(
                    'index'      => $index,
                    'comment_id' => 0,
                    'reply_id'   => 0,
                    'status'     => 'failed',
                    'error'      => 'متن پرسش یا پاسخ خالی است.',
                );
                $index++;
                continue;
            }

            // Current timestamp with sequential increment for ordering (no backdating)
            $q_timestamp      = $now + ( $index * 2 );
            $a_timestamp      = $q_timestamp + 1;
            $q_date_formatted = date( 'Y-m-d H:i:s', $q_timestamp );
            $a_date_formatted = date( 'Y-m-d H:i:s', $a_timestamp );

            // Append to structured store FAQs
            $existing_faqs[] = array(
                'question' => $question,
                'answer'   => $reply,
                'date'     => $q_date_formatted,
            );

            if ( ! $as_comments ) {
                $results[] = array(
                    'index'      => $index,
                    'comment_id' => 0,
                    'reply_id'   => 0,
                    'status'     => 'published',
                );
                $index++;
                continue;
            }

            // 1. Insert Question Comment authored by store / admin - NO fake shopper identities, NO ratings
            $question_data = array(
                'comment_post_ID'      => $post_id,
                'comment_author'       => $brand_name,
                'comment_author_email' => $admin_email,
                'comment_content'      => $question,
                'comment_type'         => 'comment',
                'comment_parent'       => 0,
                'user_id'              => $admin_id,
                'comment_date'         => $q_date_formatted,
                'comment_date_gmt'     => get_gmt_from_date( $q_date_formatted ),
                'comment_approved'     => 1,
            );

            $question_id = wp_insert_comment( $question_data );

            if ( ! $question_id || is_wp_error( $question_id ) ) {
                $results[] = array(
                    'index'      => $index,
                    'comment_id' => 0,
                    'reply_id'   => 0,
                    'status'     => 'failed',
                    'error'      => 'خطا در ثبت دیدگاه پرسش در دیتابیس وردپرس.',
                );
                $index++;
                continue;
            }

            // Mark question as store FAQ, no rating meta
            update_comment_meta( $question_id, '_kavoshrank_generated', 1 );
            update_comment_meta( $question_id, '_kavoshrank_faq', 1 );

            // 2. Insert Admin Reply Comment (Threaded under question_id)
            $reply_data = array(
                'comment_post_ID'      => $post_id,
                'comment_author'       => $brand_name,
                'comment_author_email' => $admin_email,
                'comment_content'      => $reply,
                'comment_type'         => 'comment',
                'comment_parent'       => $question_id,
                'user_id'              => $admin_id,
                'comment_date'         => $a_date_formatted,
                'comment_date_gmt'     => get_gmt_from_date( $a_date_formatted ),
                'comment_approved'     => 1,
            );

            $reply_id = wp_insert_comment( $reply_data );
            if ( $reply_id && ! is_wp_error( $reply_id ) ) {
                update_comment_meta( $reply_id, '_kavoshrank_generated', 1 );
                update_comment_meta( $reply_id, '_kavoshrank_is_admin_reply', 1 );
            }

            $results[] = array(
                'index'      => $index,
                'comment_id' => (int) $question_id,
                'reply_id'   => $reply_id ? (int) $reply_id : 0,
                'status'     => 'published',
            );

            $index++;
        }

        // Persist structured FAQs into post meta
        update_post_meta( $post_id, '_kavoshrank_faqs', $existing_faqs );

        return new WP_REST_Response( array(
            'status'  => 'success',
            'results' => $results,
            'count'   => count( $results ),
            'post_id' => $post_id,
        ), 200 );
    }
}

