<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * KavoshRank Diagnostics Handler
 * 
 * Provides server limits verification, memory telemetry, and REST ping endpoint.
 */
class KavoshRank_Diagnostics {

    /**
     * Collect comprehensive system diagnostics and server health metrics (for authenticated admin/backend only)
     */
    public static function get_system_diagnostics() {
        global $wpdb;

        $memory_limit = ini_get( 'memory_limit' );
        $memory_bytes = wp_convert_hr_to_bytes( $memory_limit );
        $memory_mb    = round( $memory_bytes / 1024 / 1024 );

        $max_exec_time = (int) ini_get( 'max_execution_time' );
        $php_version   = phpversion();

        // Loopback REST Ping with TLS verification
        $start_time = microtime( true );
        $loopback_res = wp_safe_remote_get( rest_url( 'kavoshrank/v1/ping' ), array(
            'timeout'   => 5,
            'sslverify' => true,
        ) );
        $latency_ms = round( ( microtime( true ) - $start_time ) * 1000 );
        $loopback_ok = ! is_wp_error( $loopback_res ) && wp_remote_retrieve_response_code( $loopback_res ) === 200;

        // Warnings / Recommendations
        $warnings = array();
        if ( $memory_mb < 256 ) {
            $warnings[] = sprintf( 'PHP memory limit is %s. Recommended minimum is 256MB.', $memory_limit );
        }
        if ( $max_exec_time > 0 && $max_exec_time < 60 ) {
            $warnings[] = sprintf( 'PHP max execution time is %d seconds. Recommended minimum is 60 seconds.', $max_exec_time );
        }
        if ( version_compare( $php_version, '7.4', '<' ) ) {
            $warnings[] = sprintf( 'PHP version (%s) is outdated. Recommended is 8.1 or higher.', $php_version );
        }

        return array(
            'plugin_version'       => defined( 'KAVOSHRANK_VERSION' ) ? KAVOSHRANK_VERSION : '1.0.0',
            'site_title'           => get_bloginfo( 'name' ),
            'site_url'             => home_url(),
            'php_version'          => $php_version,
            'php_sapi'             => php_sapi_name(),
            'memory_limit'         => $memory_limit,
            'memory_mb'            => $memory_mb,
            'memory_usage_mb'      => round( memory_get_usage( true ) / 1024 / 1024, 2 ),
            'max_execution_time'   => $max_exec_time,
            'upload_max_filesize'  => ini_get( 'upload_max_filesize' ),
            'post_max_size'        => ini_get( 'post_max_size' ),
            'curl_version'         => function_exists( 'curl_version' ) ? curl_version()['version'] : 'N/A',
            'openssl_version'      => defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT : 'N/A',
            'mysql_version'        => $wpdb->db_version(),
            'db_charset'           => $wpdb->charset,
            'db_collate'           => $wpdb->collate,
            'rest_loopback_ok'     => $loopback_ok,
            'rest_latency_ms'      => $latency_ms,
            'warnings'             => $warnings,
            'is_healthy'           => empty( $warnings ) && $loopback_ok,
        );
    }

    /**
     * REST handler for diagnostics (Authenticated endpoint)
     */
    public static function handle_get_diagnostics( WP_REST_Request $request ) {
        $diag = self::get_system_diagnostics();
        return new WP_REST_Response( array(
            'success' => true,
            'data'    => $diag,
        ), 200 );
    }

    /**
     * REST handler for public ping (Returns minimal non-sensitive status info)
     */
    public static function handle_ping( WP_REST_Request $request ) {
        return new WP_REST_Response( array(
            'ok'             => true,
            'plugin_version' => defined( 'KAVOSHRANK_VERSION' ) ? KAVOSHRANK_VERSION : '1.0.0',
        ), 200 );
    }
}
