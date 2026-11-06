<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Runtime_Healer {

    public function init() {
        // Default off: only activate output buffering if explicitly enabled in settings
        if ( get_option( 'kavoshrank_runtime_healer_enabled', '0' ) !== '1' ) {
            return;
        }

        if ( ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() ) {
            add_action( 'template_redirect', array( $this, 'start_buffer' ), 1 );
        }
    }

    public function start_buffer() {
        if ( is_feed() || is_trackback() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }

        // Start output buffer with healer callback
        ob_start( array( $this, 'heal_html_output' ) );
    }

    /**
     * Master Runtime Healer Callback
     */
    public function heal_html_output( $html ) {
        if ( empty( $html ) || ! is_string( $html ) || strpos( $html, '<html' ) === false ) {
            return $html;
        }

        // 1. Inject Critical Preconnects & Vazirmatn Font Preload into <head>
        if ( get_option( 'kavoshrank_healer_font_preload', '0' ) === '1' ) {
            $html = $this->heal_head_resource_hints( $html );
        }

        // 2. Optimize Images for Core Web Vitals (LCP fetchpriority="high", Lazyload, Missing Alt)
        if ( get_option( 'kavoshrank_healer_lcp_preload', '0' ) === '1' || get_option( 'kavoshrank_healer_smart_alt', '0' ) === '1' ) {
            $html = $this->heal_images_and_lcp( $html );
        }

        // 3. Patch Missing ARIA-Labels on icon buttons
        if ( get_option( 'kavoshrank_healer_aria_labels', '0' ) === '1' ) {
            $html = $this->heal_accessibility_aria( $html );
        }

        return $html;
    }

    /**
     * Injects Preconnect and Font Preload hints into <head>, only for origins the page already uses
     */
    private function heal_head_resource_hints( $html ) {
        if ( ! preg_match( '/<head\b[^>]*>/i', $html ) ) {
            return $html;
        }

        $hints = '';

        // Preconnect only to CDNs the page actually loads from, and only once
        $origins = array(
            'fonts.gstatic.com'          => true,
            'static.cdn.asset.aparat.com' => false,
            'cdn.jsdelivr.net'           => true,
        );
        foreach ( $origins as $origin_host => $crossorigin ) {
            $host = preg_quote( $origin_host, '/' );
            if ( ! preg_match( '/\/\/' . $host . '\//i', $html ) ) {
                continue;
            }
            if ( preg_match( '/<link\b(?=[^>]*\bpreconnect\b)[^>]*\/\/' . $host . '/i', $html ) ) {
                continue;
            }
            $hints .= '<link rel="preconnect" href="https://' . $origin_host . '"' . ( $crossorigin ? ' crossorigin' : '' ) . '>' . "\n";
        }

        // Preload Vazirmatn only when the page already loads it from the CDN and has no preload for it
        $vazirmatn_url = 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/fonts/webfonts/Vazirmatn-Regular.woff2';
        if ( get_option( 'kavoshrank_preload_vazirmatn', '1' ) === '1'
            && stripos( $html, 'rastikerdar/vazirmatn@v33.003' ) !== false
            && ! preg_match( '/<link\b(?=[^>]*\bpreload\b)[^>]*Vazirmatn[^>]*\.woff2/i', $html ) ) {
            $hints .= '<link rel="preload" href="' . $vazirmatn_url . '" as="font" type="font/woff2" crossorigin>' . "\n";
        }

        if ( '' === $hints ) {
            return $html;
        }

        $hints = "\n" . '<!-- KavoshRank Runtime Healer Resource Hints -->' . "\n" . $hints;

        return preg_replace( '/<head\b([^>]*)>/i', '<head$1>' . $hints, $html, 1 );
    }

    /**
     * LCP Priority Optimization, Missing Alt Repair, and Lazy Loading
     */
    private function heal_images_and_lcp( $html ) {
        $page_title = get_the_title() ? esc_attr( get_the_title() ) : esc_attr( get_bloginfo( 'name' ) );
        $enable_alt = get_option( 'kavoshrank_healer_smart_alt', '0' ) === '1';
        $enable_lcp = get_option( 'kavoshrank_healer_lcp_preload', '0' ) === '1';

        // Images inside the site header (logo, menu icons) are never the LCP candidate and must not be lazy loaded
        $header_end  = stripos( $html, '</header>' );
        $content_pos = false === $header_end ? 0 : $header_end;
        $lcp_found   = false;

        return preg_replace_callback( '/<img\b([^>]*?)\/?>/i', function( $matches ) use ( &$lcp_found, $content_pos, $page_title, $enable_alt, $enable_lcp ) {
            $attrs  = rtrim( $matches[1][0] );
            $offset = $matches[0][1];

            // Skip trust seals and compliance badges (Enamad, Samandehi, etc.)
            $is_trust_badge = preg_match( '/\b(enamad|trustseal|samandehi|logo\.aspx)\b/i', $attrs ) || strpos( $attrs, 'code=' ) !== false;

            // 1. Missing Alt attribute repair (skip trust badges)
            if ( $enable_alt && ! $is_trust_badge && ! preg_match( '/\balt=["\'][^"\']+["\']/i', $attrs ) ) {
                if ( preg_match( '/\balt=["\']\s*["\']/i', $attrs ) ) {
                    $attrs = preg_replace( '/\balt=["\']\s*["\']/i', 'alt="' . $page_title . '"', $attrs );
                } else {
                    $attrs .= ' alt="' . $page_title . '"';
                }
            }

            // 2. LCP Optimization on the first content image (not header images, logos or badges)
            if ( $enable_lcp && $offset >= $content_pos && ! $is_trust_badge ) {
                $is_logo = (bool) preg_match( '/logo/i', $attrs );
                if ( ! $lcp_found && ! $is_logo ) {
                    $lcp_found = true;
                    if ( strpos( $attrs, 'fetchpriority=' ) === false ) {
                        $attrs .= ' fetchpriority="high"';
                    }
                    if ( strpos( $attrs, 'loading=' ) === false ) {
                        $attrs .= ' loading="eager"';
                    } else {
                        $attrs = preg_replace( '/\bloading=["\']lazy["\']/i', 'loading="eager"', $attrs );
                    }
                } elseif ( $lcp_found ) {
                    // Images below the LCP candidate get lazy loading and async decoding
                    if ( strpos( $attrs, 'loading=' ) === false ) {
                        $attrs .= ' loading="lazy"';
                    }
                    if ( strpos( $attrs, 'decoding=' ) === false ) {
                        $attrs .= ' decoding="async"';
                    }
                }
            }

            return '<img ' . trim( $attrs ) . '>';
        }, $html, -1, $count, PREG_OFFSET_CAPTURE );
    }

    /**
     * Accessibility Healer: Missing aria-labels on icon buttons
     */
    private function heal_accessibility_aria( $html ) {
        // Add aria-label attribute to the existing button tag; never wrap or duplicate the button
        return preg_replace_callback( '/<button\b([^>]*)>(\s*(?:<svg\b[^>]*>.*?<\/svg>|<i\b[^>]*><\/i>)\s*)<\/button>/is', function( $match ) {
            $attrs = $match[1];
            if ( preg_match( '/\b(aria-label|aria-labelledby|title)\s*=/i', $attrs ) ) {
                return $match[0];
            }
            return '<button' . rtrim( $attrs ) . ' aria-label="دکمه عملیات">' . $match[2] . '</button>';
        }, $html );
    }

    /**
     * Return Healer System Audit and Activity Metrics
     */
    public function get_healer_metrics() {
        $enabled = get_option( 'kavoshrank_runtime_healer_enabled', '0' ) === '1';
        $lastScan = get_option( 'kavoshrank_healer_last_scan', '' );
        if ( empty( $lastScan ) ) {
            $lastScan = current_time( 'mysql' );
        }

        return array(
            'status'                  => $enabled ? 'active' : 'inactive',
            'buffer_active'           => $enabled,
            'vazirmatn_preload'       => get_option( 'kavoshrank_preload_vazirmatn', '0' ) === '1',
            'lcp_fetchpriority_active'=> get_option( 'kavoshrank_healer_lcp_preload', '0' ) === '1',
            'alt_autorepair_active'   => get_option( 'kavoshrank_healer_smart_alt', '0' ) === '1',
            'aria_autorepair_active'  => get_option( 'kavoshrank_healer_aria_labels', '0' ) === '1',
            'last_healed_timestamp'   => $lastScan,
        );
    }
}

