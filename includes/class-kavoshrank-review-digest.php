<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KavoshRank WooCommerce Product Review Digest
 *
 * Shows an AI summary of a product's approved customer reviews, with pros and
 * cons that cite the reviews they come from. The digest is generated in the
 * background (Action Scheduler or WP-Cron) when reviews change, never while a
 * page renders. A product page shows only a stored, successful digest whose
 * review-count stamp still matches the product, and nothing otherwise.
 */
class KavoshRank_Review_Digest {

    const META_KEY_DIGEST       = '_kavoshrank_review_digest';
    const META_KEY_STATE        = '_kavoshrank_review_digest_state';
    const MIN_REVIEWS_THRESHOLD = 5;
    const MAX_REVIEWS           = 50;
    const CRON_HOOK             = 'kavoshrank_generate_review_digest';
    const SCHEDULE_GROUP        = 'kavoshrank';
    // Delay before generating, so several approvals in a row cost one call.
    const SCHEDULE_DELAY        = 60;
    // A failed attempt for the same review count is not retried sooner.
    const RETRY_AFTER           = 21600;
    const REQUEST_TIMEOUT       = 30;

    public function __construct() {
        // Render the stored digest on single WooCommerce product pages.
        add_action( 'woocommerce_single_product_summary', array( $this, 'render_product_review_digest' ), 25 );

        add_shortcode( 'kavoshrank_review_digest', array( $this, 'render_shortcode' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

        // Schedule a new digest whenever the product's approved reviews change.
        add_action( 'comment_post', array( $this, 'on_comment_change' ), 10, 3 );
        add_action( 'transition_comment_status', array( $this, 'on_comment_status_transition' ), 10, 3 );
        add_action( 'edit_comment', array( $this, 'on_comment_edit' ), 10, 1 );

        // Background generation.
        add_action( self::CRON_HOOK, array( $this, 'run_scheduled_generation' ), 10, 1 );

        // Manual refresh from the admin, with nonce and capability checks.
        add_action( 'wp_ajax_kavoshrank_generate_review_digest', array( $this, 'ajax_generate_digest' ) );
    }

    public function init() {
        // Initialized via constructor
    }

    public function is_enabled() {
        return (bool) get_option( 'kavoshrank_review_digest_enabled', 1 );
    }

    public function on_comment_change( $comment_id, $approved, $commentdata ) {
        if ( 1 === (int) $approved && isset( $commentdata['comment_post_ID'] ) ) {
            $this->schedule_generation( (int) $commentdata['comment_post_ID'] );
        }
    }

    public function on_comment_status_transition( $new_status, $old_status, $comment ) {
        if ( $new_status === $old_status || ! $comment || ! isset( $comment->comment_post_ID ) ) {
            return;
        }
        // Only a change into or out of "approved" changes the review set.
        if ( 'approved' === $new_status || 'approved' === $old_status ) {
            $this->schedule_generation( (int) $comment->comment_post_ID );
        }
    }

    public function on_comment_edit( $comment_id ) {
        $comment = get_comment( $comment_id );
        if ( ! $comment || '1' !== (string) $comment->comment_approved ) {
            return;
        }
        $product_id = (int) $comment->comment_post_ID;
        if ( 'product' !== get_post_type( $product_id ) ) {
            return;
        }
        // An edited text keeps the count, so drop the digest that quoted it.
        delete_post_meta( $product_id, self::META_KEY_DIGEST );
        delete_post_meta( $product_id, self::META_KEY_STATE );
        $this->schedule_generation( $product_id );
    }

    /**
     * Queue one background generation for a product. Action Scheduler (shipped
     * with WooCommerce) is used when present, WP-Cron otherwise.
     */
    public function schedule_generation( $product_id ) {
        $product_id = (int) $product_id;
        if ( $product_id <= 0 || ! $this->is_enabled() || 'product' !== get_post_type( $product_id ) ) {
            return;
        }

        $args = array( $product_id );
        if ( function_exists( 'as_schedule_single_action' ) && function_exists( 'as_has_scheduled_action' ) ) {
            if ( ! as_has_scheduled_action( self::CRON_HOOK, $args, self::SCHEDULE_GROUP ) ) {
                as_schedule_single_action( time() + self::SCHEDULE_DELAY, self::CRON_HOOK, $args, self::SCHEDULE_GROUP );
            }
            return;
        }

        if ( ! wp_next_scheduled( self::CRON_HOOK, $args ) ) {
            wp_schedule_single_event( time() + self::SCHEDULE_DELAY, self::CRON_HOOK, $args );
        }
    }

    public function run_scheduled_generation( $product_id ) {
        $this->generate_digest( (int) $product_id, false );
    }

    public function enqueue_styles() {
        if ( is_admin() || ! $this->is_enabled() ) {
            return;
        }

        $css = '
        .kr-review-digest-box {
            margin: 20px 0;
            padding: 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #cbd5e1;
            direction: rtl;
            text-align: right;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
            font-size: 13px;
            line-height: 1.6;
        }
        .kr-rd-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #cbd5e1;
        }
        .kr-rd-title {
            font-weight: 800;
            font-size: 14px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .kr-rd-score-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 12px;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .kr-rd-summary {
            color: #334155;
            font-size: 12px;
            margin-bottom: 14px;
            line-height: 1.7;
        }
        .kr-rd-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        @media (min-width: 640px) {
            .kr-rd-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .kr-rd-col {
            padding: 12px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }
        .kr-rd-col-title {
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .kr-rd-pros-title { color: #166534; }
        .kr-rd-cons-title { color: #991b1b; }
        .kr-rd-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .kr-rd-list li {
            position: relative;
            padding-right: 20px;
            font-size: 11px;
            color: #475569;
            line-height: 1.5;
        }
        .kr-rd-icon {
            position: absolute;
            right: 0;
            top: 2px;
            width: 14px;
            height: 14px;
        }
        .kr-rd-icon-pro { color: #10b981; }
        .kr-rd-icon-con { color: #ef4444; }
        .kr-rd-footer {
            margin-top: 10px;
            padding-top: 8px;
            font-size: 10px;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        ';

        wp_register_style( 'kavoshrank-review-digest', false );
        wp_enqueue_style( 'kavoshrank-review-digest' );
        wp_add_inline_style( 'kavoshrank-review-digest', $css );
    }

    public function render_product_review_digest() {
        if ( ! $this->is_enabled() || ! is_singular( 'product' ) ) {
            return;
        }

        global $product;
        if ( ! is_a( $product, 'WC_Product' ) ) {
            return;
        }

        $digest = $this->get_displayable_digest( $product );
        if ( $digest ) {
            echo $this->build_html( $digest, $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in build_html.
        }
    }

    public function render_shortcode( $atts ) {
        if ( ! $this->is_enabled() ) {
            return '';
        }

        $atts = shortcode_atts( array( 'product_id' => 0 ), $atts, 'kavoshrank_review_digest' );
        $product_id = (int) $atts['product_id'];

        if ( $product_id === 0 && is_singular( 'product' ) ) {
            $product_id = get_the_ID();
        }

        if ( $product_id === 0 || ! function_exists( 'wc_get_product' ) ) {
            return '';
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return '';
        }

        $digest = $this->get_displayable_digest( $product );
        if ( ! $digest ) {
            return '';
        }

        return $this->build_html( $digest, $product );
    }

    /**
     * Returns the stored digest when it is a successful one built from the
     * product's current reviews, or null. It never calls the backend: a
     * missing or stale digest is queued for background generation instead.
     */
    public function get_displayable_digest( $product ) {
        $product_id   = $product->get_id();
        $review_count = (int) $product->get_review_count();
        if ( $review_count < self::MIN_REVIEWS_THRESHOLD ) {
            return null;
        }

        $digest = get_post_meta( $product_id, self::META_KEY_DIGEST, true );
        if ( self::is_current_digest( $digest, $review_count ) ) {
            return $digest;
        }

        if ( self::should_retry( get_post_meta( $product_id, self::META_KEY_STATE, true ), $review_count, time() ) ) {
            $this->schedule_generation( $product_id );
        }

        return null;
    }

    /**
     * A digest is shown only if the backend generated it successfully and its
     * review-count stamp equals the product's current approved review count.
     */
    public static function is_current_digest( $digest, $review_count ) {
        return is_array( $digest )
            && isset( $digest['status'], $digest['review_count'], $digest['summary'] )
            && 'ok' === $digest['status']
            && '' !== trim( (string) $digest['summary'] )
            && (int) $digest['review_count'] === (int) $review_count;
    }

    /**
     * Whether a generation should be queued for this review count. Not when
     * the last attempt for the same count failed or found too few reviews
     * less than RETRY_AFTER ago, so a broken backend is not called on every
     * page view.
     */
    public static function should_retry( $state, $review_count, $now ) {
        if ( ! is_array( $state ) || ! isset( $state['review_count'], $state['attempted_at'] ) ) {
            return true;
        }
        if ( (int) $state['review_count'] !== (int) $review_count ) {
            return true;
        }

        return ( (int) $now - (int) $state['attempted_at'] ) > self::RETRY_AFTER;
    }

    private function record_attempt( $product_id, $review_count, $result ) {
        update_post_meta( $product_id, self::META_KEY_STATE, array(
            'review_count' => (int) $review_count,
            'attempted_at' => time(),
            'result'       => sanitize_key( $result ),
        ) );
    }

    /**
     * Collects the product's approved reviews without author names (PII) and
     * asks the backend for a digest. Runs from the background job or the admin
     * refresh action only. Returns the stored digest, or a WP_Error.
     */
    public function generate_digest( $product_id, $force_refresh = false ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return new WP_Error( 'kavoshrank_no_woocommerce', 'WooCommerce is not active.' );
        }
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return new WP_Error( 'kavoshrank_no_product', 'Product not found.' );
        }

        $review_count = (int) $product->get_review_count();
        $raw_reviews  = $review_count >= self::MIN_REVIEWS_THRESHOLD ? self::collect_reviews( $product_id ) : array();
        if ( count( $raw_reviews ) < self::MIN_REVIEWS_THRESHOLD ) {
            delete_post_meta( $product_id, self::META_KEY_DIGEST );
            $this->record_attempt( $product_id, $review_count, 'insufficient_reviews' );
            return new WP_Error( 'kavoshrank_insufficient_reviews', 'Insufficient reviews.' );
        }

        if ( ! KavoshRank_API_Client::is_configured() ) {
            $this->record_attempt( $product_id, $review_count, 'not_configured' );
            return new WP_Error( 'kavoshrank_not_configured', 'KavoshRank API is not configured.' );
        }

        $api_url  = rtrim( KavoshRank_API_Client::get_api_url(), '/' ) . '/api/v1/plugin/reviews/digest';
        $response = wp_remote_post( $api_url, array(
            'timeout' => self::REQUEST_TIMEOUT,
            'headers' => array(
                'Content-Type'              => 'application/json',
                'X-KavoshRank-Secret-Token' => KavoshRank_API_Client::get_secret_token(),
            ),
            'body'    => wp_json_encode( array(
                'product_id'    => (int) $product_id,
                'product_title' => $product->get_name(),
                'reviews'       => $raw_reviews,
                'force_refresh' => (bool) $force_refresh,
            ) ),
        ) );

        $data = null;
        if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            $data = isset( $body['data'] ) && is_array( $body['data'] ) ? $body['data'] : null;
        }

        $digest = self::digest_from_response( $data, $review_count );
        if ( null === $data || ! isset( $data['status'] ) ) {
            // Nothing is shown for this review count; retried after RETRY_AFTER.
            $this->record_attempt( $product_id, $review_count, 'failed' );
            return new WP_Error( 'kavoshrank_digest_failed', 'Unable to generate the review digest.' );
        }
        if ( null === $digest ) {
            delete_post_meta( $product_id, self::META_KEY_DIGEST );
            $this->record_attempt( $product_id, $review_count, 'insufficient_reviews' );
            return new WP_Error( 'kavoshrank_insufficient_reviews', 'Insufficient reviews.' );
        }

        update_post_meta( $product_id, self::META_KEY_DIGEST, $digest );
        $this->record_attempt( $product_id, $review_count, 'ok' );

        return $digest;
    }

    /**
     * Turns a backend response into the stored digest, stamped with the
     * review count it was built for. Returns null unless the backend reports
     * a successful digest with a summary.
     */
    public static function digest_from_response( $data, $review_count ) {
        if ( ! is_array( $data ) || ! isset( $data['status'] ) || 'ok' !== $data['status'] ) {
            return null;
        }
        $summary = isset( $data['summary'] ) ? trim( sanitize_textarea_field( (string) $data['summary'] ) ) : '';
        if ( '' === $summary ) {
            return null;
        }

        return array(
            'status'                 => 'ok',
            'summary'                => $summary,
            'pros'                   => self::sanitize_points( isset( $data['pros'] ) ? $data['pros'] : array() ),
            'cons'                   => self::sanitize_points( isset( $data['cons'] ) ? $data['cons'] : array() ),
            'total_reviews_analyzed' => isset( $data['total_reviews_analyzed'] ) ? (int) $data['total_reviews_analyzed'] : 0,
            'reviews_stamp'          => isset( $data['reviews_stamp'] ) ? sanitize_key( (string) $data['reviews_stamp'] ) : '',
            'review_count'           => (int) $review_count,
            'generated_at'           => time(),
        );
    }

    /**
     * The newest approved reviews of a product, as id, rating and text only.
     */
    public static function collect_reviews( $product_id ) {
        $comments = get_comments( array(
            'post_id' => (int) $product_id,
            'status'  => 'approve',
            'type'    => 'review',
            'parent'  => 0,
            'number'  => self::MAX_REVIEWS,
            'orderby' => 'comment_date_gmt',
            'order'   => 'DESC',
        ) );

        $reviews = array();
        foreach ( (array) $comments as $c ) {
            $content = trim( wp_strip_all_tags( $c->comment_content ) );
            if ( '' === $content ) {
                continue;
            }
            $reviews[] = array(
                'id'      => (int) $c->comment_ID,
                'content' => $content,
                'rating'  => (int) get_comment_meta( $c->comment_ID, 'rating', true ),
            );
        }

        return $reviews;
    }

    /**
     * Keeps pros and cons that have text and at least one supporting review.
     */
    public static function sanitize_points( $points ) {
        $out = array();
        if ( ! is_array( $points ) ) {
            return $out;
        }
        foreach ( $points as $p ) {
            if ( ! is_array( $p ) || empty( $p['text'] ) || empty( $p['review_ids'] ) || ! is_array( $p['review_ids'] ) ) {
                continue;
            }
            $ids = array_values( array_filter( array_map( 'absint', $p['review_ids'] ) ) );
            if ( empty( $ids ) ) {
                continue;
            }
            $out[] = array(
                'text'       => sanitize_text_field( (string) $p['text'] ),
                'review_ids' => $ids,
            );
        }

        return array_slice( $out, 0, 5 );
    }

    private function build_html( $digest, $product ) {
        // The rating shown is WooCommerce's own average, never an AI value.
        $avg_rating = $product ? (float) $product->get_average_rating() : 0.0;
        $total      = isset( $digest['total_reviews_analyzed'] ) ? (int) $digest['total_reviews_analyzed'] : 0;
        $pros       = isset( $digest['pros'] ) && is_array( $digest['pros'] ) ? $digest['pros'] : array();
        $cons       = isset( $digest['cons'] ) && is_array( $digest['cons'] ) ? $digest['cons'] : array();

        ob_start();
        ?>
        <div class="kr-review-digest-box">
            <div class="kr-rd-header">
                <div class="kr-rd-title">
                    <span>خلاصه دیدگاه‌های خریداران</span>
                </div>
                <?php if ( $avg_rating > 0 ) : ?>
                    <div class="kr-rd-score-badge">
                        <span>میانگین امتیاز: <?php echo esc_html( number_format_i18n( $avg_rating, 1 ) ); ?> از ۵</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="kr-rd-summary">
                <?php echo esc_html( $digest['summary'] ); ?>
            </div>

            <div class="kr-rd-grid">
                <?php if ( ! empty( $pros ) ) : ?>
                    <div class="kr-rd-col">
                        <div class="kr-rd-col-title kr-rd-pros-title">
                            <span>نکات مثبت از دید خریداران</span>
                        </div>
                        <ul class="kr-rd-list">
                            <?php foreach ( $pros as $p ) : ?>
                                <li>
                                    <svg class="kr-rd-icon kr-rd-icon-pro" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <?php echo esc_html( $p['text'] ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $cons ) ) : ?>
                    <div class="kr-rd-col">
                        <div class="kr-rd-col-title kr-rd-cons-title">
                            <span>نکات منفی از دید خریداران</span>
                        </div>
                        <ul class="kr-rd-list">
                            <?php foreach ( $cons as $c ) : ?>
                                <li>
                                    <svg class="kr-rd-icon kr-rd-icon-con" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <?php echo esc_html( $c['text'] ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <div class="kr-rd-footer">
                <span><?php echo esc_html( sprintf( 'خلاصه %s دیدگاه خریداران، تولیدشده با هوش مصنوعی', number_format_i18n( $total ) ) ); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_digest() {
        check_ajax_referer( 'kavoshrank_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_products' ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden: Insufficient capabilities.' ), 403 );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        if ( $product_id === 0 || ! function_exists( 'wc_get_product' ) || 'product' !== get_post_type( $product_id ) ) {
            wp_send_json_error( array( 'message' => 'Invalid product ID' ), 400 );
        }

        if ( ! current_user_can( 'edit_post', $product_id ) ) {
            wp_send_json_error( array( 'message' => 'Forbidden: Insufficient capabilities.' ), 403 );
        }

        // An explicit admin action, not a page render, so it may wait for the backend.
        $digest = $this->generate_digest( $product_id, true );
        if ( is_wp_error( $digest ) ) {
            $status = 'kavoshrank_insufficient_reviews' === $digest->get_error_code() ? 422 : 502;
            wp_send_json_error( array( 'message' => $digest->get_error_message() ), $status );
        }

        wp_send_json_success( $digest );
    }
}
