<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * KavoshRank LLMs.txt & Generative Engine Optimization (GEO) Generator
 * 
 * Provides automated, structured Markdown standard feeds (/llms.txt and /llms-full.txt)
 * for AI web search agents (SearchGPT, Claude, Perplexity, Gemini).
 */
class KavoshRank_LLMs_Txt {

    public function init() {
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'handle_template_redirect' ) );
    }

    /**
     * Add rewrite rules for /llms.txt and /llms-full.txt
     */
    public function add_rewrite_rules() {
        add_rewrite_rule( '^llms\.txt$', 'index.php?kavoshrank_llms=1', 'top' );
        add_rewrite_rule( '^llms-full\.txt$', 'index.php?kavoshrank_llms=2', 'top' );
    }

    /**
     * Register query vars
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'kavoshrank_llms';
        return $vars;
    }

    /**
     * Handle template redirect for llms.txt requests
     */
    public function handle_template_redirect() {
        $llms_flag = get_query_var( 'kavoshrank_llms' );
        if ( ! $llms_flag ) {
            return;
        }

        $is_full = ( $llms_flag == 2 || $llms_flag === '2' );
        $content = self::generate_llms_txt( $is_full );

        header( 'Content-Type: text/plain; charset=UTF-8' );
        header( 'X-Robots-Tag: all, index, follow' );
        header( 'Cache-Control: public, max-age=3600' );

        echo $content;
        exit;
    }

    /**
     * Generate standard llms.txt Markdown format
     */
    public static function generate_llms_txt( $is_full = false ) {
        $site_name = get_bloginfo( 'name' );
        $site_desc = get_bloginfo( 'description' );
        $site_url  = home_url();

        $custom_summary = get_option( 'kavoshrank_llms_custom_summary', '' );
        $include_posts  = get_option( 'kavoshrank_llms_include_posts', 1 );
        $include_wc     = get_option( 'kavoshrank_llms_include_wc', 1 );
        $max_items      = (int) get_option( 'kavoshrank_llms_max_items', 30 );
        if ( $max_items <= 0 ) {
            $max_items = 30;
        }

        $md = array();

        // 1. Title & Header Block
        $md[] = "# " . $site_name;
        if ( ! empty( $custom_summary ) ) {
            $md[] = "> " . trim( $custom_summary );
        } elseif ( ! empty( $site_desc ) ) {
            $md[] = "> " . trim( $site_desc );
        }
        $md[] = "";
        $md[] = "- **Website**: " . $site_url;
        $md[] = "- **Last Updated**: " . gmdate( 'Y-m-d H:i:s \U\T\C' );
        $md[] = "- **Language**: " . get_locale();
        $md[] = "";

        // 2. Pillar & Key Articles Section
        if ( $include_posts ) {
            $posts = get_posts( array(
                'numberposts' => $max_items,
                'post_status' => 'publish',
                'post_type'   => 'post',
                'orderby'     => 'date',
                'order'       => 'DESC',
            ) );

            if ( ! empty( $posts ) ) {
                $md[] = "## Key Articles & Educational Guides";
                $md[] = "";
                foreach ( $posts as $post ) {
                    $permalink = get_permalink( $post->ID );
                    $excerpt = wp_strip_all_tags( $post->post_excerpt );
                    if ( empty( $excerpt ) ) {
                        $excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 25 );
                    }
                    $md[] = sprintf( "- [%s](%s): %s", $post->post_title, $permalink, $excerpt );
                }
                $md[] = "";
            }
        }

        // 3. WooCommerce Core Products & Catalog
        if ( $include_wc && class_exists( 'WooCommerce' ) ) {
            $products = get_posts( array(
                'numberposts' => $max_items,
                'post_status' => 'publish',
                'post_type'   => 'product',
                'orderby'     => 'date',
                'order'       => 'DESC',
            ) );

            if ( ! empty( $products ) ) {
                $md[] = "## Products & Commercial Catalog";
                $md[] = "";
                foreach ( $products as $prod_post ) {
                    $prod = function_exists( 'wc_get_product' ) ? wc_get_product( $prod_post->ID ) : null;
                    $price_str = '';
                    if ( $prod ) {
                        $price = $prod->get_price();
                        if ( ! empty( $price ) ) {
                            $curr = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'IRT';
                            $price_str = ' - ' . number_format( (float) $price ) . ' ' . $curr;
                        }
                    }
                    $permalink = get_permalink( $prod_post->ID );
                    $short_desc = wp_strip_all_tags( $prod_post->post_excerpt );
                    if ( empty( $short_desc ) ) {
                        $short_desc = wp_trim_words( wp_strip_all_tags( $prod_post->post_content ), 20 );
                    }
                    $md[] = sprintf( "- [%s](%s)%s: %s", $prod_post->post_title, $permalink, $price_str, $short_desc );
                }
                $md[] = "";
            }
        }

        // 4. Content Taxonomy Silos
        $categories = get_categories( array( 'hide_empty' => true, 'number' => 15 ) );
        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            $md[] = "## Topics & Knowledge Silos";
            $md[] = "";
            foreach ( $categories as $cat ) {
                $cat_link = get_category_link( $cat->term_id );
                $cat_desc = ! empty( $cat->description ) ? ' - ' . wp_strip_all_tags( $cat->description ) : '';
                $md[] = sprintf( "- [%s](%s)%s (%d articles)", $cat->name, $cat_link, $cat_desc, $cat->count );
            }
            $md[] = "";
        }

        // 5. Full Markdown Content Expansion (if llms-full.txt)
        if ( $is_full ) {
            $md[] = "---";
            $md[] = "# Full Content Corpus";
            $md[] = "";

            $full_posts = get_posts( array(
                'numberposts' => 15,
                'post_status' => 'publish',
                'post_type'   => 'post',
                'orderby'     => 'date',
                'order'       => 'DESC',
            ) );

            foreach ( $full_posts as $post ) {
                $md[] = "### " . $post->post_title;
                $md[] = "**URL**: " . get_permalink( $post->ID );
                $md[] = "";
                $clean_body = self::html_to_clean_markdown( $post->post_content );
                $md[] = $clean_body;
                $md[] = "";
                $md[] = "---";
                $md[] = "";
            }
        }

        // 6. Direct Full Manifest Pointer
        if ( ! $is_full ) {
            $md[] = "## Full Manifest";
            $md[] = "- For comprehensive raw content and complete text corpus, see: " . home_url( '/llms-full.txt' );
            $md[] = "";
        }

        return implode( "\n", $md );
    }

    /**
     * Convert HTML content to clean, token-efficient Markdown
     */
    private static function html_to_clean_markdown( $html ) {
        if ( empty( $html ) ) {
            return '';
        }

        // Strip scripts, styles, iframes
        $html = preg_replace( '/<(script|style|iframe|noscript)\b[^>]*>(.*?)<\/\1>/is', '', $html );

        // Convert common tags to markdown equivalents
        $html = preg_replace( '/<h1\b[^>]*>(.*?)<\/h1>/i', "\n# $1\n", $html );
        $html = preg_replace( '/<h2\b[^>]*>(.*?)<\/h2>/i', "\n## $1\n", $html );
        $html = preg_replace( '/<h3\b[^>]*>(.*?)<\/h3>/i', "\n### $1\n", $html );
        $html = preg_replace( '/<h4\b[^>]*>(.*?)<\/h4>/i', "\n#### $1\n", $html );
        $html = preg_replace( '/<strong\b[^>]*>(.*?)<\/strong>/i', '**$1**', $html );
        $html = preg_replace( '/<b\b[^>]*>(.*?)<\/b>/i', '**$1**', $html );
        $html = preg_replace( '/<em\b[^>]*>(.*?)<\/em>/i', '*$1*', $html );
        $html = preg_replace( '/<i\b[^>]*>(.*?)<\/i>/i', '*$1*', $html );
        $html = preg_replace( '/<li\b[^>]*>(.*?)<\/li>/i', "- $1\n", $html );
        $html = preg_replace( '/<p\b[^>]*>(.*?)<\/p>/i', "$1\n\n", $html );
        $html = preg_replace( '/<br\s*\/?>/i', "\n", $html );

        // Strip remaining tags
        $clean = wp_strip_all_tags( $html );
        // Normalize extra whitespace
        $clean = preg_replace( "/\n{3,}/", "\n\n", $clean );

        return trim( $clean );
    }

    /**
     * REST endpoint: Get llms.txt preview & configuration
     */
    public static function handle_get_llms_txt( WP_REST_Request $request ) {
        $llms_txt = self::generate_llms_txt( false );
        $llms_full_txt = self::generate_llms_txt( true );

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array(
                'llms_txt_url'          => home_url( '/llms.txt' ),
                'llms_full_txt_url'     => home_url( '/llms-full.txt' ),
                'custom_summary'        => get_option( 'kavoshrank_llms_custom_summary', '' ),
                'include_posts'         => (bool) get_option( 'kavoshrank_llms_include_posts', 1 ),
                'include_wc'            => (bool) get_option( 'kavoshrank_llms_include_wc', 1 ),
                'max_items'             => (int) get_option( 'kavoshrank_llms_max_items', 30 ),
                'preview_llms_txt'      => $llms_txt,
                'preview_llms_full_txt' => $llms_full_txt,
            ),
        ), 200 );
    }

    /**
     * REST endpoint: Update llms.txt configuration
     */
    public static function handle_update_llms_txt( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        if ( isset( $params['custom_summary'] ) ) {
            update_option( 'kavoshrank_llms_custom_summary', sanitize_textarea_field( $params['custom_summary'] ) );
        }
        if ( isset( $params['include_posts'] ) ) {
            update_option( 'kavoshrank_llms_include_posts', $params['include_posts'] ? 1 : 0 );
        }
        if ( isset( $params['include_wc'] ) ) {
            update_option( 'kavoshrank_llms_include_wc', $params['include_wc'] ? 1 : 0 );
        }
        if ( isset( $params['max_items'] ) ) {
            update_option( 'kavoshrank_llms_max_items', max( 5, min( 100, (int) $params['max_items'] ) ) );
        }

        // Flush rewrite rules in case they were not active
        flush_rewrite_rules( false );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'تنظیمات llms.txt با موفقیت ذخیره شد.',
            'data'    => array(
                'llms_txt_url'      => home_url( '/llms.txt' ),
                'llms_full_txt_url' => home_url( '/llms-full.txt' ),
                'custom_summary'    => get_option( 'kavoshrank_llms_custom_summary', '' ),
            ),
        ), 200 );
    }
}
