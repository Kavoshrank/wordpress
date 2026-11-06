<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_REST_Controller {

    /**
     * Maximum number of items a single batch request may process. Anything
     * beyond it is reported back as unprocessed so the caller re-sends it.
     */
    const MAX_BATCH_ITEMS = 25;

    /**
     * Prepare the PHP runtime for a chunked batch request: raise (never lower)
     * the memory limit, extend the execution time where the host allows it, and
     * stop the object cache from growing while many posts are touched.
     *
     * @return float Request start timestamp for batch_budget_exceeded().
     */
    public static function begin_batch_request() {
        if ( function_exists( 'wp_raise_memory_limit' ) ) {
            wp_raise_memory_limit( 'admin' );
        }
        if ( function_exists( 'set_time_limit' ) ) {
            @set_time_limit( 120 );
        }
        if ( function_exists( 'wp_suspend_cache_addition' ) ) {
            wp_suspend_cache_addition( true );
        }

        return microtime( true );
    }

    /**
     * Report whether a batch loop should stop before its next item so the
     * request returns before the host kills it on execution time or memory.
     */
    public static function batch_budget_exceeded( $start_time ) {
        $time_budget = 20.0;
        $max_exec    = (int) ini_get( 'max_execution_time' );
        if ( $max_exec > 0 ) {
            $time_budget = min( $time_budget, $max_exec * 0.6 );
        }
        if ( ( microtime( true ) - $start_time ) > $time_budget ) {
            return true;
        }

        $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
        if ( $memory_limit > 0 && memory_get_usage( true ) > $memory_limit * 0.8 ) {
            return true;
        }

        return false;
    }

    public function register_routes() {
        add_filter( 'rest_post_dispatch', function( $response ) {
            if ( $response instanceof WP_REST_Response ) {
                $response->header( 'X-KavoshRank-Plugin-Version', defined( 'KAVOSHRANK_VERSION' ) ? KAVOSHRANK_VERSION : '1.0.1' );
            }
            return $response;
        } );

        $namespaces = array( 'kavoshrank/v1', 'tsw/v1' );

        foreach ( $namespaces as $namespace ) {
            // 1. Get, Create or Update Post Endpoint
            register_rest_route( $namespace, '/posts', array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( 'KavoshRank_Post_REST_Handler', 'handle_get_posts' ),
                    'permission_callback' => array( $this, 'verify_token_permission' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( 'KavoshRank_Post_REST_Handler', 'handle_create_or_update_post' ),
                    'permission_callback' => array( $this, 'verify_token_permission' ),
                ),
            ) );
            register_rest_route( $namespace, '/posts/(?P<id>\d+)', array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( 'KavoshRank_Content_Audit_REST_Handler', 'handle_get_post' ),
                    'permission_callback' => array( $this, 'verify_token_permission' ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( 'KavoshRank_Post_REST_Handler', 'handle_delete_post' ),
                    'permission_callback' => array( $this, 'verify_token_permission' ),
                ),
            ) );
            register_rest_route( $namespace, '/posts/(?P<id>\d+)/publish', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Post_REST_Handler', 'handle_publish_post' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 1a. Site Healer thin content audit and enrichment
            register_rest_route( $namespace, '/content-audit', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( 'KavoshRank_Content_Audit_REST_Handler', 'handle_get_audit' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );
            register_rest_route( $namespace, '/posts/(?P<id>\d+)/enrich', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Content_Audit_REST_Handler', 'handle_enrich_post' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 1b. Manage Categories Endpoint
            register_rest_route( $namespace, '/categories', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( 'KavoshRank_Categories_REST_Handler', 'handle_get_categories' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );
            register_rest_route( $namespace, '/categories', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Categories_REST_Handler', 'handle_create_or_update_category' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );
            register_rest_route( $namespace, '/categories/slug', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Categories_REST_Handler', 'handle_update_category_slug' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 2. Create WooCommerce Product Endpoint
            register_rest_route( $namespace, '/products', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Product_REST_Handler', 'handle_create_product' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 3. Sideload Media Endpoint (Images & Audio Podcasts)
            register_rest_route( $namespace, '/media', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Media_REST_Handler', 'handle_upload_media' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 4. Fetch Site Context Endpoint
            register_rest_route( $namespace, '/site-context', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'handle_get_site_context' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 5. Fetch Candidate Posts for Silo Internal Links Endpoint
            register_rest_route( $namespace, '/candidate-posts', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'handle_get_candidate_posts' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 6. Update Internal Links Endpoint
            register_rest_route( $namespace, '/links', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Link_REST_Handler', 'handle_update_links' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 6b. Auto-Link Products with Category Silo Guards Endpoint
            register_rest_route( $namespace, '/links/auto-products', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Link_REST_Handler', 'handle_auto_link_products' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 7. Register 301 Redirects Endpoint
            register_rest_route( $namespace, '/redirects', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Redirect_REST_Handler', 'handle_register_redirect' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 8. Fetch Published Articles with Audio Status Endpoint
            register_rest_route( $namespace, '/audio-articles', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'handle_get_audio_articles' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 9. QA Comments & Threads Injection Endpoint
            register_rest_route( $namespace, '/qa-comments', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Comment_REST_Handler', 'handle_publish_qa_comments' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 10. Page Optimizer & WebP Conversion Endpoints
            register_rest_route( $namespace, '/page-optimizer/stats', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'handle_get_page_optimizer_stats' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            register_rest_route( $namespace, '/page-optimizer/convert-bulk', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_convert_bulk_webp' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 11. Site Healer Real-Time Buffer Stats Endpoint
            register_rest_route( $namespace, '/site-healer/stats', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'handle_get_site_healer_stats' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 12. 404 Error Monitor & Redirect Endpoints
            register_rest_route( $namespace, '/404-monitor/logs', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'handle_get_404_logs' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            register_rest_route( $namespace, '/404-monitor/redirect', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_create_404_redirect' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            register_rest_route( $namespace, '/404-monitor/clear', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_clear_404_logs' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 13. System Diagnostics Endpoints
            register_rest_route( $namespace, '/ping', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( 'KavoshRank_Diagnostics', 'handle_ping' ),
                'permission_callback' => '__return_true',
            ) );

            register_rest_route( $namespace, '/diagnostics', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( 'KavoshRank_Diagnostics', 'handle_get_diagnostics' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 14. Generative Engine Optimization (GEO) & LLMs.txt Endpoints
            register_rest_route( $namespace, '/llms-txt', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( 'KavoshRank_LLMs_Txt', 'handle_get_llms_txt' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            register_rest_route( $namespace, '/llms-txt', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_LLMs_Txt', 'handle_update_llms_txt' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 15. WooCommerce & Category SEO Studio Endpoints
            register_rest_route( $namespace, '/categories/seo', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( 'KavoshRank_Category_SEO', 'handle_get_category_seo' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            register_rest_route( $namespace, '/categories/seo', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Category_SEO', 'handle_update_category_seo' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 16. AI Bots Governor & Robots.txt Traffic Firewall Endpoints
            register_rest_route( $namespace, '/bot-governor', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( 'KavoshRank_Bot_Governor', 'handle_get_bot_governor' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            register_rest_route( $namespace, '/bot-governor', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Bot_Governor', 'handle_update_bot_governor' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 17. Storefront Features Inventory & Status Endpoints
            register_rest_route( $namespace, '/storefront-features', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( 'KavoshRank_Storefront_Features', 'handle_get_features' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            register_rest_route( $namespace, '/storefront-features', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( 'KavoshRank_Storefront_Features', 'handle_update_features' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );

            // 18. AI Readiness & Live SEO Health Audit Endpoint
            register_rest_route( $namespace, '/ai-readiness', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'handle_get_ai_readiness_audit' ),
                'permission_callback' => array( $this, 'verify_token_permission' ),
            ) );
        }
    }

    /**
     * Permission Callback: the site token is accepted only from the
     * X-KavoshRank-Secret-Token header, never from the query string, the body
     * or other headers, so it does not end up in access logs.
     */
    public function verify_token_permission( WP_REST_Request $request ) {
        $token = (string) $request->get_header( 'x-kavoshrank-secret-token' );

        // Some FastCGI setups only expose the header through $_SERVER.
        if ( '' === $token ) {
            if ( ! empty( $_SERVER['HTTP_X_KAVOSHRANK_SECRET_TOKEN'] ) ) {
                $token = (string) $_SERVER['HTTP_X_KAVOSHRANK_SECRET_TOKEN'];
            } elseif ( ! empty( $_SERVER['REDIRECT_HTTP_X_KAVOSHRANK_SECRET_TOKEN'] ) ) {
                $token = (string) $_SERVER['REDIRECT_HTTP_X_KAVOSHRANK_SECRET_TOKEN'];
            }
        }

        $token       = trim( $token, "\"' \t\n\r\0\x0B" );
        $valid_token = trim( KavoshRank_API_Client::get_secret_token(), "\"' \t\n\r\0\x0B" );

        // Compare keyed digests so the comparison is constant-time and does
        // not depend on the token length.
        if ( '' === $token || ! KavoshRank_API_Client::is_strong_token( $valid_token ) ||
            ! hash_equals( self::token_digest( $valid_token ), self::token_digest( $token ) ) ) {
            return new WP_Error( 'rest_forbidden', 'Unauthorized.', array(
                'status' => 401,
            ) );
        }

        return true;
    }

    private static function token_digest( $token ) {
        return hash_hmac( 'sha256', (string) $token, wp_salt( 'auth' ) );
    }

    /**
     * Handler for GET /site-context
     */
    public function handle_get_site_context( WP_REST_Request $request ) {
        $recent_posts = get_posts( array(
            'numberposts' => 40,
            'post_status' => 'publish',
            'post_type'   => 'post',
        ) );

        $titles = array();
        foreach ( $recent_posts as $p ) {
            $titles[] = $p->post_title;
        }

        $categories = get_terms( array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
        ) );

        $cats_list = array();
        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
            foreach ( $categories as $cat ) {
                $cats_list[] = array(
                    'id'   => $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                );
            }
        }

        $has_woo = class_exists( 'WooCommerce' );

        return new WP_REST_Response( array(
            'success'            => true,
            'site_name'          => get_bloginfo( 'name' ),
            'plugin_version'     => defined( 'KAVOSHRANK_VERSION' ) ? KAVOSHRANK_VERSION : '1.0.0',
            'woocommerce_active' => $has_woo,
            'has_woocommerce'    => $has_woo,
            'recent_post_titles' => $titles,
            'categories'         => $cats_list,
        ), 200 );
    }

    /**
     * Handler for GET /candidate-posts
     */
    public function handle_get_candidate_posts( WP_REST_Request $request ) {
        $exclude_id = absint( $request->get_param( 'exclude_id' ) );

        $args = array(
            'numberposts' => 50,
            'post_status' => 'publish',
            'post_type'   => 'post',
        );

        if ( $exclude_id > 0 ) {
            $args['exclude'] = array( $exclude_id );
        }

        $posts = get_posts( $args );
        $candidates = array();

        foreach ( $posts as $p ) {
            $candidates[] = array(
                'id'      => $p->ID,
                'title'   => $p->post_title,
                'content' => $p->post_content,
                'url'     => get_permalink( $p->ID ),
            );
        }

        return new WP_REST_Response( array(
            'posts' => $candidates,
        ), 200 );
    }

    /**
     * Handler for GET /audio-articles
     */
    public function handle_get_audio_articles( WP_REST_Request $request ) {
        $posts = get_posts( array(
            'numberposts' => 50,
            'post_status' => 'publish',
            'post_type'   => 'post',
        ) );

        $articles = array();
        foreach ( $posts as $p ) {
            $audio_url = get_post_meta( $p->ID, '_kavoshrank_audio_summary_url', true );
            if ( empty( $audio_url ) ) {
                $audio_url = get_post_meta( $p->ID, '_tsw_audio_summary_url', true );
            }

            $has_audio = ! empty( $audio_url );
            $duration  = '';

            if ( $has_audio ) {
                $duration = get_post_meta( $p->ID, '_kavoshrank_audio_duration_str', true );
                if ( empty( $duration ) ) {
                    $sec = (float) get_post_meta( $p->ID, '_kavoshrank_audio_duration', true );
                    if ( $sec > 0 && class_exists( 'KavoshRank_Media_REST_Handler' ) ) {
                        $duration = KavoshRank_Media_REST_Handler::format_duration( $sec );
                        update_post_meta( $p->ID, '_kavoshrank_audio_duration_str', $duration );
                    }
                }

                // If still empty, attempt to resolve from local attachment or local upload file path
                if ( empty( $duration ) && class_exists( 'KavoshRank_Media_REST_Handler' ) ) {
                    $upload_dir = wp_upload_dir();
                    $file_path  = '';
                    if ( ! empty( $upload_dir['baseurl'] ) && strpos( $audio_url, $upload_dir['baseurl'] ) === 0 ) {
                        $relative_path = substr( $audio_url, strlen( $upload_dir['baseurl'] ) );
                        $file_path     = $upload_dir['basedir'] . $relative_path;
                    }

                    if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
                        $sec = KavoshRank_Media_REST_Handler::get_wav_duration_seconds( $file_path );
                        if ( $sec > 0 ) {
                            $duration = KavoshRank_Media_REST_Handler::format_duration( $sec );
                            update_post_meta( $p->ID, '_kavoshrank_audio_duration', $sec );
                            update_post_meta( $p->ID, '_kavoshrank_audio_duration_str', $duration );
                        }
                    }
                }

                if ( empty( $duration ) ) {
                    $duration = '0:00';
                }
            }

            $articles[] = array(
                'id'        => $p->ID,
                'title'     => $p->post_title,
                'has_audio' => $has_audio,
                'audio_url' => $has_audio ? (string) $audio_url : '',
                'duration'  => $duration,
            );
        }

        return new WP_REST_Response( array(
            'articles' => $articles,
        ), 200 );
    }

    /**
     * Handler for GET /page-optimizer/stats
     */
    public function handle_get_page_optimizer_stats( WP_REST_Request $request ) {
        $optimizer = new KavoshRank_Page_Optimizer();
        return new WP_REST_Response( $optimizer->get_webp_stats(), 200 );
    }

    /**
     * Handler for POST /page-optimizer/convert-bulk
     */
    public function handle_convert_bulk_webp( WP_REST_Request $request ) {
        $limit  = absint( $request->get_param( 'limit' ) );
        $offset = absint( $request->get_param( 'offset' ) );
        if ( $limit <= 0 ) {
            $limit = 15;
        }
        $limit     = min( $limit, self::MAX_BATCH_ITEMS );
        $optimizer = new KavoshRank_Page_Optimizer();
        $result    = $optimizer->convert_bulk_media( $limit, $offset );
        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Handler for GET /site-healer/stats
     */
    public function handle_get_site_healer_stats( WP_REST_Request $request ) {
        $healer = new KavoshRank_Runtime_Healer();
        return new WP_REST_Response( $healer->get_healer_metrics(), 200 );
    }

    /**
     * Handler for GET /404-monitor/logs
     */
    public function handle_get_404_logs( WP_REST_Request $request ) {
        $monitor = new KavoshRank_404_Monitor();
        return new WP_REST_Response( array(
            'logs'  => $monitor->get_logs(),
            'total' => count( $monitor->get_logs() ),
        ), 200 );
    }

    /**
     * Handler for POST /404-monitor/redirect
     */
    public function handle_create_404_redirect( WP_REST_Request $request ) {
        $source = sanitize_text_field( $request->get_param( 'source_url' ) );
        $target = esc_url_raw( $request->get_param( 'target_url' ) );
        $code   = absint( $request->get_param( 'status_code' ) );
        if ( $code !== 301 && $code !== 302 ) {
            $code = 301;
        }

        if ( empty( $source ) || empty( $target ) ) {
            return new WP_REST_Response( array(
                'error' => 'Source URL and Target URL are required',
            ), 400 );
        }

        $monitor = new KavoshRank_404_Monitor();
        $success = $monitor->create_redirect( $source, $target, $code );

        return new WP_REST_Response( array(
            'success'    => $success,
            'source_url' => $source,
            'target_url' => $target,
            'code'       => $code,
        ), 200 );
    }

    /**
     * Handler for POST /404-monitor/clear
     */
    public function handle_clear_404_logs( WP_REST_Request $request ) {
        $monitor = new KavoshRank_404_Monitor();
        $monitor->clear_logs();
        return new WP_REST_Response( array(
            'success' => true,
            'message' => '404 logs cleared successfully',
        ), 200 );
    }

    /**
     * Handler for GET /ai-readiness
     * Live comprehensive audit of Schema, Media/WebP, Silos, WooCommerce, and GEO
     */
    public function handle_get_ai_readiness_audit( WP_REST_Request $request ) {
        // 1. Schema & EEAT
        $schema_enabled   = get_option( 'kavoshrank_enable_schema', '0' ) === '1';
        $eeat_box_enabled = get_option( 'kavoshrank_enable_eeat_box', '0' ) === '1';
        $local_seo        = get_option( 'kavoshrank_enable_local_seo', '0' ) === '1';
        $post_counts      = wp_count_posts( 'post' );
        $published_posts  = isset( $post_counts->publish ) ? (int) $post_counts->publish : 0;

        // 2. Media & WebP
        $webp_rewrite   = get_option( 'kavoshrank_enable_webp_rewrite', '1' ) === '1';
        $media_stats    = array();
        if ( class_exists( 'KavoshRank_Page_Optimizer' ) ) {
            $page_optimizer = new KavoshRank_Page_Optimizer();
            $media_stats    = $page_optimizer->get_webp_stats();
        }

        // 3. Silos & Linking
        $auto_linking   = get_option( 'kavoshrank_enable_auto_linking', '1' ) === '1';
        $category_count = (int) wp_count_terms( array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
        ) );

        // 4. WooCommerce
        $has_woo        = class_exists( 'WooCommerce' );
        $woo_products   = 0;
        if ( $has_woo ) {
            $product_counts = wp_count_posts( 'product' );
            $woo_products   = isset( $product_counts->publish ) ? (int) $product_counts->publish : 0;
        }
        $override_wc    = get_option( 'kavoshrank_override_wc_schema', '0' ) === '1';

        // 5. GEO & llms.txt
        $bot_firewall   = get_option( 'kavoshrank_bot_firewall_enabled', '0' ) === '1';
        $bot_preset     = get_option( 'kavoshrank_bot_preset', 'balanced' );

        return new WP_REST_Response( array(
            'success'     => true,
            'audited_at'  => current_time( 'mysql' ),
            'site_title'  => get_bloginfo( 'name' ),
            'site_url'    => home_url(),
            'schema'      => array(
                'schema_enabled'   => $schema_enabled,
                'eeat_box_enabled' => $eeat_box_enabled,
                'local_seo'        => $local_seo,
                'published_posts'  => $published_posts,
            ),
            'media'       => array(
                'webp_rewrite_enabled' => $webp_rewrite,
                'total_legacy_images'  => isset( $media_stats['total_legacy_images'] ) ? (int) $media_stats['total_legacy_images'] : 0,
                'converted_webp_count' => isset( $media_stats['converted_webp_count'] ) ? (int) $media_stats['converted_webp_count'] : 0,
                'unconverted_count'    => isset( $media_stats['unconverted_count'] ) ? (int) $media_stats['unconverted_count'] : 0,
                'savings_percentage'   => isset( $media_stats['savings_percentage'] ) ? (float) $media_stats['savings_percentage'] : 0.0,
            ),
            'silos'       => array(
                'auto_linking_enabled' => $auto_linking,
                'category_count'       => $category_count,
                'published_posts'      => $published_posts,
            ),
            'woocommerce' => array(
                'is_installed'       => $has_woo,
                'products_count'     => $woo_products,
                'override_wc_schema' => $override_wc,
            ),
            'geo'         => array(
                'llms_txt_enabled'     => true,
                'llms_txt_url'         => home_url( '/llms.txt' ),
                'bot_firewall_enabled' => $bot_firewall,
                'bot_preset'           => $bot_preset,
            ),
        ), 200 );
    }
}
