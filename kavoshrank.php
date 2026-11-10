<?php
/**
 * Plugin Name:       KavoshRank SEO Client
 * Plugin URI:        https://kavoshrank.ir
 * Description:       Lightweight WordPress Client for KavoshRank AI SEO & Content Automation Master Engine.
 * Version:           1.0.3
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            KavoshRank Team
 * Author URI:        https://kavoshrank.ir
 * Text Domain:       kavoshrank
 * Domain Path:       /languages
 * Update URI:        https://kavoshrank.ir/plugin-updates/kavoshrank
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'KAVOSHRANK_LOADED' ) ) {
    return;
}
define( 'KAVOSHRANK_LOADED', true );

if ( ! defined( 'KAVOSHRANK_VERSION' ) ) {
    define( 'KAVOSHRANK_VERSION', '1.0.3' );
}
if ( ! defined( 'KAVOSHRANK_DB_VERSION' ) ) {
    define( 'KAVOSHRANK_DB_VERSION', '1.0.0' );
}
if ( ! defined( 'KAVOSHRANK_PATH' ) ) {
    define( 'KAVOSHRANK_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'KAVOSHRANK_URL' ) ) {
    define( 'KAVOSHRANK_URL', plugin_dir_url( __FILE__ ) );
}

if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'KavoshRank SEO Client requires PHP 8.1 or higher.', 'kavoshrank' ) . '</p></div>';
    } );
    return;
}

/**
 * Handle language switcher param and cookie for multi-language support.
 */
function kavoshrank_handle_language_cookie() {
    if ( isset( $_GET['kr_lang'] ) && in_array( $_GET['kr_lang'], array( 'en_US', 'fa_IR' ), true ) ) {
        $lang = sanitize_text_field( $_GET['kr_lang'] );
        if ( ! headers_sent() ) {
            setcookie( 'kavoshrank_lang', $lang, time() + ( 365 * 86400 ), COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
        }
        $_COOKIE['kavoshrank_lang'] = $lang;
    }
}
add_action( 'plugins_loaded', 'kavoshrank_handle_language_cookie', 1 );

/**
 * Resolve current active locale for KavoshRank plugin.
 */
function kavoshrank_get_current_locale() {
    if ( isset( $_GET['kr_lang'] ) && in_array( $_GET['kr_lang'], array( 'en_US', 'fa_IR' ), true ) ) {
        return sanitize_text_field( $_GET['kr_lang'] );
    }
    if ( isset( $_COOKIE['kavoshrank_lang'] ) && in_array( $_COOKIE['kavoshrank_lang'], array( 'en_US', 'fa_IR' ), true ) ) {
        return sanitize_text_field( $_COOKIE['kavoshrank_lang'] );
    }
    return function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
}

/**
 * Check if the active KavoshRank admin interface is RTL.
 */
function kavoshrank_is_rtl() {
    $locale = kavoshrank_get_current_locale();
    if ( strpos( $locale, 'en' ) === 0 ) {
        return false;
    }
    return is_rtl();
}

/**
 * Load plugin textdomain for internationalization (en_US & fa_IR).
 */
function kavoshrank_load_textdomain() {
    $locale = kavoshrank_get_current_locale();
    unload_textdomain( 'kavoshrank' );
    $mofile = KAVOSHRANK_PATH . 'languages/kavoshrank-' . $locale . '.mo';
    if ( file_exists( $mofile ) ) {
        load_textdomain( 'kavoshrank', $mofile );
    } else {
        load_plugin_textdomain( 'kavoshrank', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
}
add_action( 'plugins_loaded', 'kavoshrank_load_textdomain', 2 );
add_action( 'init', 'kavoshrank_load_textdomain' );

/**
 * Auto-register rewrite rules and store versions on activation.
 * Secret token is generated and managed centrally from the KavoshRank Panel.
 */
if ( ! function_exists( 'kavoshrank_activate_plugin' ) ) {
    function kavoshrank_activate_plugin() {
        // Register llms.txt rewrite rules and flush rewrite rules on activation
        require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-llms-txt.php';
        $llms = new KavoshRank_LLMs_Txt();
        $llms->add_rewrite_rules();
        flush_rewrite_rules( false );

        // Store versions
        update_option( 'kavoshrank_version', KAVOSHRANK_VERSION );
        update_option( 'kavoshrank_db_version', KAVOSHRANK_DB_VERSION );

        // Default API URL if not already configured
        if ( ! get_option( 'kavoshrank_golang_api_url' ) ) {
            update_option( 'kavoshrank_golang_api_url', 'https://backend.kavoshrank.ir' );
        }
    }
}
if ( function_exists( 'register_activation_hook' ) ) {
    register_activation_hook( __FILE__, 'kavoshrank_activate_plugin' );
}

/**
 * Plugin deactivation hook: clear scheduled cron events and flush rewrite rules.
 */
if ( ! function_exists( 'kavoshrank_deactivate_plugin' ) ) {
    function kavoshrank_deactivate_plugin() {
        // Clear scheduled cron events
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

        // Flush rewrite rules on deactivation to clean up custom endpoints
        flush_rewrite_rules( false );
    }
}
if ( function_exists( 'register_deactivation_hook' ) ) {
    register_deactivation_hook( __FILE__, 'kavoshrank_deactivate_plugin' );
}

/**
 * Handle upgrade routines and rewrite rules flush on version upgrade.
 */
if ( ! function_exists( 'kavoshrank_check_version_upgrade' ) ) {
    function kavoshrank_check_version_upgrade() {
        $installed_ver    = get_option( 'kavoshrank_version', '0.0.0' );
        $installed_db_ver = get_option( 'kavoshrank_db_version', '0.0.0' );

        $needs_ver_upgrade = version_compare( (string) $installed_ver, KAVOSHRANK_VERSION, '<' );
        $needs_db_upgrade  = version_compare( (string) $installed_db_ver, KAVOSHRANK_DB_VERSION, '<' );

        if ( $needs_ver_upgrade || $needs_db_upgrade ) {
            if ( function_exists( 'kavoshrank_run_upgrade_routines' ) ) {
                kavoshrank_run_upgrade_routines( $installed_ver, $installed_db_ver );
            }

            if ( class_exists( 'KavoshRank_LLMs_Txt' ) ) {
                $llms = new KavoshRank_LLMs_Txt();
                $llms->add_rewrite_rules();
            }
            flush_rewrite_rules( false );

            update_option( 'kavoshrank_version', KAVOSHRANK_VERSION );
            update_option( 'kavoshrank_db_version', KAVOSHRANK_DB_VERSION );
        }
    }
}
add_action( 'init', 'kavoshrank_check_version_upgrade', 5 );

if ( ! function_exists( 'kavoshrank_run_upgrade_routines' ) ) {
    function kavoshrank_run_upgrade_routines( $from_ver, $from_db_ver ) {
        if ( function_exists( 'do_action' ) ) {
            do_action( 'kavoshrank_upgraded', KAVOSHRANK_VERSION, $from_ver, $from_db_ver );
        }
    }
}

require_once KAVOSHRANK_PATH . 'includes/class-kavoshrank-client.php';

if ( ! function_exists( 'run_kavoshrank_client' ) ) {
    function run_kavoshrank_client() {
        KavoshRank_Client::instance();
    }
}
run_kavoshrank_client();

/**
 * Frontend styles for KavoshRank Interactive Components (Accordion FAQ, Content Figures, Internal Links)
 */
add_action( 'wp_enqueue_scripts', function() {
    $faq_css = '
    .kavoshrank-faq-container { margin: 36px 0; }
    .kavoshrank-faq-container h3 { font-size: 20px; font-weight: 800; margin-bottom: 18px; color: #0f172a; }
    .kavoshrank-faq-item { margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff; padding: 14px 18px; transition: all 0.25s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .kavoshrank-faq-item[open] { border-color: #10b981; background: #f8fafc; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.08); }
    .kavoshrank-faq-question { font-weight: 700; font-size: 15px; cursor: pointer; color: #1e293b; list-style: none; display: flex; align-items: center; justify-content: space-between; outline: none; user-select: none; }
    .kavoshrank-faq-question::-webkit-details-marker { display: none; }
    .kavoshrank-faq-question::after { content: "+"; font-size: 20px; font-weight: 600; color: #10b981; transition: transform 0.2s ease; margin-right: 12px; }
    .kavoshrank-faq-item[open] .kavoshrank-faq-question::after { content: "−"; transform: rotate(180deg); }
    .kavoshrank-faq-answer { margin-top: 12px; padding-top: 10px; border-top: 1px dashed #e2e8f0; color: #475569; font-size: 14px; line-height: 1.85; }
    .kavoshrank-internal-link { color: #059669; font-weight: 600; text-decoration: underline; text-underline-offset: 3px; }
    .kavoshrank-internal-link:hover { color: #047857; text-decoration: none; }
    ';
    wp_register_style( 'kavoshrank-frontend-style', false );
    wp_enqueue_style( 'kavoshrank-frontend-style' );
    wp_add_inline_style( 'kavoshrank-frontend-style', $faq_css );
} );

/**
 * Disable Gutenberg Block Editor if enabled in KavoshRank settings to use Classic Editor.
 */
add_filter( 'use_block_editor_for_post', function( $use_block_editor, $post ) {
    if ( (bool) get_option( 'kavoshrank_disable_gutenberg', false ) ) {
        return false;
    }
    return $use_block_editor;
}, 10, 2 );

add_filter( 'use_block_editor_for_post_type', function( $use_block_editor, $post_type ) {
    if ( (bool) get_option( 'kavoshrank_disable_gutenberg', false ) ) {
        return false;
    }
    return $use_block_editor;
}, 10, 2 );

/**
 * Automatically clean broken browser blob: URLs from post content in the WordPress editor and frontend.
 */
if ( ! function_exists( 'kavoshrank_clean_blob_urls' ) ) {
    function kavoshrank_clean_blob_urls( $content, $post_id = 0 ) {
        if ( empty( $content ) || strpos( (string) $content, 'blob:' ) === false ) {
            return $content;
        }

        $real_img_url = '';
        if ( ! empty( $post_id ) ) {
            $thumb_id = get_post_thumbnail_id( $post_id );
            if ( $thumb_id ) {
                $real_img_url = wp_get_attachment_url( $thumb_id );
            }
        }

        if ( ! empty( $real_img_url ) ) {
            return preg_replace( '/src=["\']blob:[^"\']+["\']/i', 'src="' . esc_url( $real_img_url ) . '"', $content );
        }

        // Purge broken blob figure or img tags so TinyMCE visual editor never crashes
        $content = preg_replace( '/<figure\b[^>]*kavoshrank-article-figure[^>]*>\s*<img\b[^>]*?\bsrc=["\']blob:[^"\']+["\'][^>]*>.*?<\/figure>/is', '', $content );
        return preg_replace( '/<img\b[^>]*?\bsrc=["\']blob:[^"\']+["\'][^>]*>/i', '', $content );
    }
}

add_filter( 'the_editor_content', function( $content ) {
    global $post;
    $pid = isset( $post->ID ) ? (int) $post->ID : 0;
    return kavoshrank_clean_blob_urls( $content, $pid );
}, 10, 1 );

add_filter( 'content_edit_pre', function( $content, $post_id = 0 ) {
    return kavoshrank_clean_blob_urls( $content, (int) $post_id );
}, 10, 2 );

add_filter( 'content_save_pre', function( $content ) {
    global $post;
    $pid = isset( $post->ID ) ? (int) $post->ID : 0;
    return kavoshrank_clean_blob_urls( $content, $pid );
}, 10, 1 );


