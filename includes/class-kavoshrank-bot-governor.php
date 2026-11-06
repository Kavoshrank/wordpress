<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KavoshRank AI Bots Governor & Robots.txt Traffic Firewall
 * Selectively permits generative AI search engines while blocking aggressive scrapers and preserving server CPU/bandwidth.
 */
class KavoshRank_Bot_Governor {

    public function init() {
        if ( get_option( 'kavoshrank_bot_firewall_enabled', '0' ) === '1' ) {
            add_filter( 'robots_txt', array( $this, 'filter_robots_txt' ), 99, 2 );
            add_action( 'init', array( $this, 'enforce_traffic_firewall' ), 1 );
        }
    }

    /**
     * Filter WordPress virtual robots.txt by appending rules without clobbering existing configuration
     */
    public function filter_robots_txt( $output, $public ) {
        if ( '0' === (string) $public ) {
            return $output;
        }

        $preset = get_option( 'kavoshrank_bot_governor_preset', 'balanced' );
        $rules  = $this->generate_robots_txt_content( $preset, (string) $output );

        $output = trim( (string) $output );
        if ( ! empty( $output ) ) {
            return $output . "\n\n" . trim( $rules ) . "\n";
        }

        return trim( $rules ) . "\n";
    }

    /**
     * Detect active sitemap provider URL dynamically
     */
    public function detect_active_sitemap_url() {
        $home_url = trailingslashit( home_url() );

        // 1. Check Yoast SEO
        if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Sitemaps' ) ) {
            return $home_url . 'sitemap_index.xml';
        }

        // 2. Check Rank Math SEO
        if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
            return $home_url . 'sitemap_index.xml';
        }

        // 3. Check All in One SEO
        if ( defined( 'AIOSEO_VERSION' ) ) {
            return $home_url . 'sitemap.xml';
        }

        // 4. Check WordPress Core XML Sitemaps (WP 5.5+)
        if ( function_exists( 'wp_sitemaps_get_server' ) ) {
            $server = wp_sitemaps_get_server();
            if ( $server && method_exists( $server, 'sitemaps_enabled' ) && $server->sitemaps_enabled() ) {
                return $home_url . 'wp-sitemap.xml';
            }
        }

        return '';
    }

    /**
     * Generate robots.txt rules based on selected preset
     */
    public function generate_robots_txt_content( $preset = 'balanced', $existing_robots_txt = '' ) {
        $rules  = "# ========================================================\n";
        $rules .= "# KavoshRank AI Bots Governor & SEO Traffic Firewall\n";
        $rules .= "# Mode: " . strtoupper( $preset ) . "\n";
        $rules .= "# ========================================================\n\n";

        // LLM Discovery endpoints if not already declared
        if ( strpos( $existing_robots_txt, '/llms.txt' ) === false ) {
            $rules .= "User-agent: *\n";
            $rules .= "Allow: /llms.txt\n";
            $rules .= "Allow: /llms-full.txt\n\n";
        }

        if ( 'strict' === $preset ) {
            // Strict Mode: Block all AI crawlers & training bots, allow only standard search engines
            $rules .= "# Block All AI Crawlers and LLM Bots\n";
            $rules .= "User-agent: GPTBot\nDisallow: /\n\n";
            $rules .= "User-agent: ChatGPT-User\nDisallow: /\n\n";
            $rules .= "User-agent: OAI-SearchBot\nDisallow: /\n\n";
            $rules .= "User-agent: ClaudeBot\nDisallow: /\n\n";
            $rules .= "User-agent: anthropic-ai\nDisallow: /\n\n";
            $rules .= "User-agent: PerplexityBot\nDisallow: /\n\n";
            $rules .= "User-agent: Google-Extended\nDisallow: /\n\n";
            $rules .= "User-agent: Applebot-Extended\nDisallow: /\n\n";
            $rules .= "User-agent: Cohere-AI\nDisallow: /\n\n";
            $rules .= "User-agent: ByteSpider\nDisallow: /\n\n";
            $rules .= "User-agent: CCBot\nDisallow: /\n\n";
            $rules .= "User-agent: Amazonbot\nDisallow: /\n\n";
            $rules .= "User-agent: Scrapy\nDisallow: /\n\n";
        } else {
            // Balanced (Default) & Permissive: AI search & citation crawlers allowed by default
            $rules .= "# Permitted Generative AI Search & Citation Engines\n";
            $rules .= "User-agent: PerplexityBot\nAllow: /\n\n";
            $rules .= "User-agent: ChatGPT-User\nAllow: /\n\n";
            $rules .= "User-agent: OAI-SearchBot\nAllow: /\n\n";
            $rules .= "User-agent: GPTBot\nAllow: /\n\n";
            $rules .= "User-agent: ClaudeBot\nAllow: /\n\n";
            $rules .= "User-agent: anthropic-ai\nAllow: /\n\n";
            $rules .= "User-agent: Google-Extended\nAllow: /\n\n";
            $rules .= "User-agent: Applebot-Extended\nAllow: /\n\n";
            $rules .= "User-agent: Cohere-AI\nAllow: /\n\n";

            $rules .= "# Disallow Aggressive Commercial Scrapers (High Server Load, No Citations)\n";
            $rules .= "User-agent: ByteSpider\nDisallow: /\n\n";
            $rules .= "User-agent: CCBot\nDisallow: /\n\n";
            $rules .= "User-agent: Amazonbot\nDisallow: /\n\n";
            $rules .= "User-agent: Scrapy\nDisallow: /\n\n";
        }

        // Detect real active sitemap dynamically if no sitemap was previously listed
        if ( strpos( $existing_robots_txt, 'Sitemap:' ) === false ) {
            $sitemap_url = $this->detect_active_sitemap_url();
            if ( ! empty( $sitemap_url ) ) {
                $rules .= "Sitemap: " . $sitemap_url . "\n";
            }
        }

        return $rules;
    }

    /**
     * Real-time HTTP Traffic Firewall Enforcement
     * Drops connection early for abusive scrapers. Logs in aggregate via transient buffer.
     */
    public function enforce_traffic_firewall() {
        if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }

        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        if ( empty( $user_agent ) ) {
            return;
        }

        $preset = get_option( 'kavoshrank_bot_governor_preset', 'balanced' );

        $blocked_patterns = array(
            'bytespider',
            'ccbot',
            'amazonbot',
            'scrapy',
            'megaindex',
            'ahrefsbot-scraping',
        );

        if ( 'strict' === $preset ) {
            $blocked_patterns = array_merge( $blocked_patterns, array(
                'gptbot',
                'chatgpt-user',
                'oai-searchbot',
                'claudebot',
                'anthropic-ai',
                'perplexitybot',
                'google-extended',
                'applebot-extended',
                'cohere-ai',
            ) );
        }

        $ua_lower = strtolower( $user_agent );
        foreach ( $blocked_patterns as $pattern ) {
            if ( false !== strpos( $ua_lower, $pattern ) ) {
                // Log in transient buffer rather than database write on every hit
                $buffer = (int) get_transient( 'kavoshrank_blocked_bot_buffer' );
                $buffer++;

                if ( $buffer >= 20 ) {
                    $total = (int) get_option( 'kavoshrank_blocked_bot_hits', 0 );
                    update_option( 'kavoshrank_blocked_bot_hits', $total + $buffer, false );
                    delete_transient( 'kavoshrank_blocked_bot_buffer' );
                } else {
                    set_transient( 'kavoshrank_blocked_bot_buffer', $buffer, HOUR_IN_SECONDS );
                }

                status_header( 403 );
                header( 'Content-Type: text/plain; charset=utf-8' );
                header( 'X-Robots-Tag: noindex, nofollow' );
                echo "403 Forbidden - Bot Access Blocked by KavoshRank Traffic Firewall.\n";
                exit;
            }
        }
    }

    /**
     * Get aggregate blocked hits count
     */
    public static function get_total_blocked_hits() {
        $base     = (int) get_option( 'kavoshrank_blocked_bot_hits', 0 );
        $buffered = (int) get_transient( 'kavoshrank_blocked_bot_buffer' );
        return $base + $buffered;
    }

    /**
     * REST Handler: Get Bot Governor Status & Settings
     */
    public static function handle_get_bot_governor() {
        $preset        = get_option( 'kavoshrank_bot_governor_preset', 'balanced' );
        $firewall      = get_option( 'kavoshrank_bot_firewall_enabled', '0' ) === '1';
        $blocked_total = self::get_total_blocked_hits();

        // Flush transient buffer when reporting
        $buffered = (int) get_transient( 'kavoshrank_blocked_bot_buffer' );
        if ( $buffered > 0 ) {
            update_option( 'kavoshrank_blocked_bot_hits', $blocked_total, false );
            delete_transient( 'kavoshrank_blocked_bot_buffer' );
        }

        $governor = new self();
        $robots_txt = $governor->generate_robots_txt_content( $preset );

        return new WP_REST_Response( array(
            'status'           => 'success',
            'preset'           => $preset,
            'firewall_enabled' => $firewall,
            'blocked_hits'     => $blocked_total,
            'robots_txt'       => $robots_txt,
            'url'              => home_url( '/robots.txt' ),
        ), 200 );
    }

    /**
     * REST Handler: Update Bot Governor Settings
     */
    public static function handle_update_bot_governor( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        if ( isset( $params['preset'] ) ) {
            $preset = sanitize_key( $params['preset'] );
            if ( in_array( $preset, array( 'permissive', 'balanced', 'strict' ), true ) ) {
                update_option( 'kavoshrank_bot_governor_preset', $preset );
            }
        }

        if ( isset( $params['firewall_enabled'] ) ) {
            $firewall = ! empty( $params['firewall_enabled'] ) ? '1' : '0';
            update_option( 'kavoshrank_bot_firewall_enabled', $firewall );
        }

        return self::handle_get_bot_governor();
    }
}
