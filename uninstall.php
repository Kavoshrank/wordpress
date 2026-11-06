<?php
/**
 * KavoshRank Plugin Uninstallation
 *
 * Runs when the plugin is deleted via the WordPress admin plugins screen.
 * Respects the 'kavoshrank_erase_data_on_uninstall' setting before purging.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Verify if admin opted-in to wipe all data on uninstall
$erase_data = get_option( 'kavoshrank_erase_data_on_uninstall', 0 );

if ( ! empty( $erase_data ) ) {
    // 1. Clear scheduled cron tasks
    $cron_hooks = array(
        'kavoshrank_scheduled_health_check',
        'kavoshrank_cron_cleanup',
        'kavoshrank_bot_buffer_flush',
    );
    foreach ( $cron_hooks as $hook ) {
        $timestamp = wp_next_scheduled( $hook );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, $hook );
        }
        wp_clear_scheduled_hook( $hook );
    }

    // 2. Drop custom tables
    $custom_tables = array(
        $wpdb->prefix . 'kavoshrank_social_logs',
        $wpdb->prefix . 'tsw_social_logs',
    );
    foreach ( $custom_tables as $table ) {
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    // 3. Remove postmeta
    $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\_kavoshrank\_%' OR meta_key LIKE '\_tsw\_%'" );

    // 4. Remove commentmeta
    $wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '\_kavoshrank\_%' OR meta_key LIKE '\_tsw\_%'" );

    // 5. Remove transients
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_kavoshrank\_%' OR option_name LIKE '\_transient\_timeout\_kavoshrank\_%'" );

    // 6. Remove options
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'kavoshrank\_%' OR option_name LIKE 'tsw\_%'" );

    // 7. Flush rewrite rules
    if ( function_exists( 'flush_rewrite_rules' ) ) {
        flush_rewrite_rules( false );
    }
}
