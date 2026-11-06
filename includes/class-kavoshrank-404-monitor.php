<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_404_Monitor {

    const LOG_OPTION = 'kavoshrank_404_logs';

    public function init() {
        if ( get_option( 'kavoshrank_enable_404_monitor', '1' ) === '1' ) {
            add_action( 'template_redirect', array( $this, 'intercept_404_request' ), 10 );
        }
    }

    /**
     * Intercept and record 404 requests in real-time
     */
    public function intercept_404_request() {
        if ( ! is_404() || is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
            return;
        }

        $uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        if ( empty( $uri ) || $uri === '/' ) {
            return;
        }

        // Ignore common bot and asset noise
        if ( preg_match( '/\.(map|xml|txt|ico|png|jpg|jpeg|gif|webp|svg|css|js|woff2?|env|git|bak|php)$/i', $uri ) ) {
            return;
        }

        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        // Skip known search bots & scanners to avoid log pollution
        if ( preg_match( '/(googlebot|bingbot|yandex|baiduspider|duckduckbot|slurp|semrushbot|ahrefsbot|mj12bot|dotbot|crawler|spider|bot)/i', $user_agent ) ) {
            return;
        }

        $referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

        $logs = get_option( self::LOG_OPTION, array() );
        if ( ! is_array( $logs ) ) {
            $logs = array();
        }

        $clean_uri = '/' . trim( parse_url( $uri, PHP_URL_PATH ), '/' );
        $key = md5( strtolower( $clean_uri ) );

        if ( isset( $logs[ $key ] ) ) {
            $logs[ $key ]['hit_count'] = ( $logs[ $key ]['hit_count'] ?? 1 ) + 1;
            $logs[ $key ]['last_hit']  = current_time( 'mysql' );
            if ( ! empty( $referer ) ) {
                $logs[ $key ]['referer'] = $referer;
            }
        } else {
            $suggested = $this->suggest_matching_post( $clean_uri );
            $logs[ $key ] = array(
                'id'            => $key,
                'url'           => $clean_uri,
                'hit_count'     => 1,
                'first_hit'     => current_time( 'mysql' ),
                'last_hit'      => current_time( 'mysql' ),
                'referer'       => $referer,
                'user_agent'    => $user_agent,
                'suggested_url' => $suggested,
            );
        }

        // Cap stored logs at 1000 items
        if ( count( $logs ) > 1000 ) {
            uasort( $logs, function( $a, $b ) {
                return strcmp( $b['last_hit'], $a['last_hit'] );
            } );
            $logs = array_slice( $logs, 0, 1000, true );
        }

        update_option( self::LOG_OPTION, $logs, false );
    }

    /**
     * Finds the closest published post URL for a 404 URL based on slug matching
     */
    public function suggest_matching_post( $uri ) {
        $path  = trim( parse_url( $uri, PHP_URL_PATH ), '/' );
        $parts = explode( '/', $path );
        $slug  = end( $parts );

        if ( empty( $slug ) ) {
            return home_url( '/' );
        }

        $slug_clean = str_replace( array( '-', '_', '+' ), ' ', urldecode( $slug ) );

        // Search published posts with keyword matching
        $posts = get_posts( array(
            'post_type'      => array( 'post', 'product' ),
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            's'              => $slug_clean,
        ) );

        if ( ! empty( $posts ) ) {
            return get_permalink( $posts[0]->ID );
        }

        return home_url( '/' );
    }

    /**
     * Get all recorded 404 error logs
     */
    public function get_logs() {
        $logs = get_option( self::LOG_OPTION, array() );
        if ( ! is_array( $logs ) ) {
            return array();
        }

        uasort( $logs, function( $a, $b ) {
            return ( $b['hit_count'] ?? 1 ) <=> ( $a['hit_count'] ?? 1 );
        } );

        return array_values( $logs );
    }

    /**
     * Register a 301 redirect and clear the resolved 404 log entry
     */
    public function create_redirect( $source_url, $target_url, $status_code = 301 ) {
        if ( empty( $source_url ) || empty( $target_url ) ) {
            return false;
        }

        $redirects = KavoshRank_Redirect_REST_Handler::get_all_redirects();

        $normalized_src = '/' . trim( parse_url( $source_url, PHP_URL_PATH ), '/' );
        $redirects[ $normalized_src ] = array(
            'id'          => md5( $normalized_src ),
            'source'      => $normalized_src,
            'target'      => esc_url_raw( $target_url ),
            'status'      => intval( $status_code ),
            'created_at'  => current_time( 'mysql' ),
        );

        update_option( KavoshRank_Redirect_REST_Handler::REDIRECTS_OPTION, $redirects, false );

        // Remove from 404 logs
        $logs = get_option( self::LOG_OPTION, array() );
        if ( is_array( $logs ) ) {
            $key = md5( strtolower( $normalized_src ) );
            unset( $logs[ $key ] );
            update_option( self::LOG_OPTION, $logs, false );
        }

        return true;
    }

    public function clear_logs() {
        update_option( self::LOG_OPTION, array(), false );
        return true;
    }
}

