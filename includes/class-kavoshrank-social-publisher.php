<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Social_Publisher {

    public function init() {
        // Intercept social click queries and track redirection
        add_action( 'template_redirect', array( $this, 'track_social_clicks' ), 1 );

        // Automatically broadcast newly published posts if auto-sharing is enabled
        add_action( 'publish_post', array( __CLASS__, 'share_post' ), 10, 1 );
        add_action( 'publish_product', array( __CLASS__, 'share_post' ), 10, 1 );
    }

    /**
     * Intercept clicks on URLs containing ?tsw_social_click or ?kavoshrank_social_click.
     */
    public function track_social_clicks() {
        $track_code = '';
        if ( ! empty( $_GET['tsw_social_click'] ) ) {
            $track_code = sanitize_text_field( $_GET['tsw_social_click'] );
        } elseif ( ! empty( $_GET['kavoshrank_social_click'] ) ) {
            $track_code = sanitize_text_field( $_GET['kavoshrank_social_click'] );
        }

        if ( empty( $track_code ) ) {
            return;
        }

        // Notify central KavoshRank engine
        KavoshRank_API_Client::notify_social_click( $track_code );

        // If local legacy table exists, increment local counter
        if ( is_numeric( $track_code ) ) {
            global $wpdb;
            $table = $wpdb->prefix . 'tsw_social_logs';
            if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table ) {
                $log_id = absint( $track_code );
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$table} SET clicks = clicks + 1 WHERE id = %d",
                    $log_id
                ) );
            }
        }
    }

    /**
     * Share a post to social networks through KavoshRank API client.
     *
     * @param int $post_id
     * @return bool
     */
    public static function share_post( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || 'publish' !== $post->post_status ) {
            return false;
        }

        if ( get_post_meta( $post_id, '_kavoshrank_social_shared', true ) || get_post_meta( $post_id, '_tsw_social_shared', true ) ) {
            return false;
        }

        $title = get_the_title( $post_id );
        $link  = get_permalink( $post_id );
        $image = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'large' ) : '';
        $desc  = wp_strip_all_tags( get_the_excerpt( $post_id ) );
        if ( empty( $desc ) ) {
            $desc = mb_substr( wp_strip_all_tags( $post->post_content ), 0, 150 ) . '...';
        }

        $channels = array( 'telegram', 'bale', 'rubika' );
        foreach ( $channels as $channel ) {
            KavoshRank_API_Client::broadcast_social( array(
                'post_id'   => $post_id,
                'network'   => $channel,
                'title'     => $title,
                'excerpt'   => $desc,
                'link'      => $link,
                'image_url' => $image,
            ) );
        }

        update_post_meta( $post_id, '_kavoshrank_social_shared', 1 );
        update_post_meta( $post_id, '_tsw_social_shared', 1 );

        return true;
    }
}
