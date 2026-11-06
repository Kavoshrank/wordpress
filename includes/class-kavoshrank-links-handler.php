<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Link_REST_Handler {

    /**
     * Helper to normalize Persian and Arabic characters for reliable matching.
     */
    public static function normalize_persian( $str ) {
        $find    = array( 'ي', 'ك', '‌', 'آ', 'إ', 'أ' );
        $replace = array( 'ی', 'ک', ' ', 'ا', 'ا', 'ا' );
        $str = str_replace( $find, $replace, $str );
        return trim( preg_replace( '/\s+/u', ' ', $str ) );
    }

    /**
     * Safely replace a keyword with HTML link using Persian Unicode word boundaries
     * and ignoring existing <a> tags, headings <h1>-<h6>, <pre>, <code>, and HTML attributes.
     */
    public static function replace_keyword_safely( $content, $keyword, $replacement_html ) {
        if ( empty( $content ) || empty( $keyword ) || empty( $replacement_html ) ) {
            return $content;
        }

        $chars = preg_split( '//u', $keyword, -1, PREG_SPLIT_NO_EMPTY );
        $regex_pattern = '';
        foreach ( $chars as $char ) {
            if ( $char === 'ی' || $char === 'ي' ) {
                $regex_pattern .= '[یي]';
            } elseif ( $char === 'ک' || $char === 'ك' ) {
                $regex_pattern .= '[کك]';
            } elseif ( $char === 'ا' || $char === 'آ' || $char === 'إ' || $char === 'أ' ) {
                $regex_pattern .= '[اآإأ]';
            } elseif ( $char === ' ' || $char === '‌' ) {
                $regex_pattern .= '[\s‌]+';
            } else {
                $regex_pattern .= preg_quote( $char, '/' );
            }
        }

        $pattern = '/<a[^>]*>.*?<\/a>|<h[1-6][^>]*>.*?<\/h[1-6]>|<pre[^>]*>.*?<\/pre>|<code[^>]*>.*?<\/code>|<[^>]+>|(?<![\p{L}\p{N}\x{200c}])(' . $regex_pattern . ')(?![\p{L}\p{N}\x{200c}])/isu';

        $replaced = false;
        $new_content = preg_replace_callback( $pattern, function( $matches ) use ( $replacement_html, &$replaced ) {
            if ( $matches[0][0] === '<' ) {
                return $matches[0];
            }
            if ( $replaced ) {
                return $matches[0];
            }
            $replaced = true;
            return $replacement_html;
        }, $content );

        if ( $new_content !== null && $replaced ) {
            return $new_content;
        }

        return $content;
    }

    /**
     * Handle manual/targeted internal link replacements from the backend.
     */
    public static function handle_update_links( WP_REST_Request $request ) {
        $start_time = KavoshRank_REST_Controller::begin_batch_request();

        $params = $request->get_json_params();
        $links  = $params['links'] ?? array();

        if ( empty( $links ) || ! is_array( $links ) ) {
            return new WP_REST_Response( array( 'error' => 'Empty or invalid links array' ), 400 );
        }

        $links           = array_values( $links );
        $applied_count   = 0;
        $processed_count = 0;
        $total_items     = count( $links );

        foreach ( $links as $item ) {
            // Always make progress on at least one item, then stop early once the
            // item cap or the time/memory budget is reached. The caller re-sends
            // the unprocessed tail in its next request.
            if ( $processed_count > 0 && (
                $processed_count >= KavoshRank_REST_Controller::MAX_BATCH_ITEMS ||
                KavoshRank_REST_Controller::batch_budget_exceeded( $start_time )
            ) ) {
                break;
            }
            $processed_count++;

            if ( ! is_array( $item ) ) {
                continue;
            }

            // The backend sends source_post_id; source_id is kept for older callers.
            $source_id        = absint( $item['source_id'] ?? ( $item['source_post_id'] ?? 0 ) );
            $keyword          = $item['keyword'] ?? '';
            $replacement_html = $item['replacement_html'] ?? '';
            $new_content_raw  = $item['new_content'] ?? '';

            if ( ! $source_id ) {
                continue;
            }

            $source_post = get_post( $source_id );
            if ( ! $source_post ) {
                continue;
            }

            if ( ! empty( $new_content_raw ) ) {
                $updated_content = $new_content_raw;
            } elseif ( ! empty( $keyword ) && ! empty( $replacement_html ) ) {
                $updated_content = self::replace_keyword_safely( $source_post->post_content, $keyword, $replacement_html );
            } else {
                continue;
            }

            if ( $updated_content === $source_post->post_content ) {
                continue;
            }

            // Save post revision prior to content mutation to guarantee safe atomic revert
            if ( function_exists( 'wp_save_post_revision' ) ) {
                wp_save_post_revision( $source_post );
            }

            $res = wp_update_post( array(
                'ID'           => $source_id,
                'post_content' => $updated_content,
            ) );

            if ( $res && ! is_wp_error( $res ) ) {
                $applied_count++;
            }
        }

        return new WP_REST_Response( array(
            'status'          => 'success',
            'links_applied'   => $applied_count,
            'updated_count'   => $applied_count,
            'total_items'     => $total_items,
            'processed_items' => $processed_count,
            'remaining_items' => $total_items - $processed_count,
        ), 200 );
    }

    /**
     * Extract matchable keyword phrases from a product title.
     */
    public static function get_product_keywords( $title ) {
        $keywords = array();
        $title_clean = trim( $title );
        $keywords[] = $title_clean;

        // Strip common descriptive suffixes/modifiers in Persian e-commerce & content
        $modifiers = array(
            'اصلی', 'شرکتی', 'اورجینال', 'فابریک', 'جفت', 'دست', 'عدد', 'عددی', 
            'چپ', 'راست', 'درجه یک', 'تضمینی', 'خرید', 'فروش', 'قیمت', 'ارزان', 'گارانتی'
        );

        $temp = $title_clean;
        foreach ( $modifiers as $mod ) {
            $temp = trim( str_replace( $mod, '', $temp ) );
        }

        if ( mb_strlen( $temp ) > 3 ) {
            $keywords[] = $temp;
        }

        $words = preg_split( '/\s+/u', $title_clean );
        if ( count( $words ) >= 3 ) {
            $keywords[] = implode( ' ', array_slice( $words, 0, 3 ) );
        }
        if ( count( $words ) >= 4 ) {
            $keywords[] = implode( ' ', array_slice( $words, 0, 4 ) );
        }

        $final_keywords = array();
        foreach ( $keywords as $kw ) {
            $kw = trim( preg_replace( '/\s+/u', ' ', $kw ) );
            $kw = trim( preg_replace( '/[^\p{L}\p{N}\s]/u', '', $kw ) );
            $kw = self::normalize_persian( $kw );
            if ( mb_strlen( $kw ) > 3 ) {
                $final_keywords[] = $kw;
            }
        }

        return array_unique( $final_keywords );
    }

    /**
     * Category-Guarded Product Auto-Linker
     */
    public static function auto_link_products( $content, $post_id = 0, $category_id = 0 ) {
        if ( empty( $content ) ) {
            return array( 'content' => $content, 'links_count' => 0, 'products' => array() );
        }

        if ( ! $category_id && $post_id ) {
            $categories = wp_get_post_categories( $post_id );
            if ( ! empty( $categories ) ) {
                $category_id = $categories[0];
            }
        }

        $category_name = '';
        if ( $category_id ) {
            $term = get_term( $category_id, 'category' );
            if ( $term && ! is_wp_error( $term ) ) {
                $category_name = $term->name;
            } else {
                $term = get_term( $category_id, 'product_cat' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $category_name = $term->name;
                }
            }
        }

        $category_name_lower = mb_strtolower( $category_name );

        $products = get_posts( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'fields'         => 'ids',
        ) );

        if ( empty( $products ) ) {
            return array( 'content' => $content, 'links_count' => 0, 'products' => array() );
        }

        $generic_words = array( 'سایت', 'فروشگاه', 'محصول', 'کالا', 'خدمات', 'مطلب', 'صفحه' );
        $keywords_pool = array();

        foreach ( $products as $product_id ) {
            $title = get_the_title( $product_id );
            $url   = get_permalink( $product_id );

            $title_clean = trim( $title );
            if ( mb_strlen( $title_clean ) < 3 || in_array( mb_strtolower( $title_clean ), $generic_words, true ) ) {
                continue;
            }

            $prod_candidates = self::get_product_keywords( $title_clean );
            foreach ( $prod_candidates as $cand ) {
                $keywords_pool[] = array(
                    'product_id'   => $product_id,
                    'name'         => $title_clean,
                    'match_phrase' => $cand,
                    'url'          => $url,
                );
            }
        }

        // Sort by length descending
        usort( $keywords_pool, function( $a, $b ) {
            return mb_strlen( $b['match_phrase'] ) - mb_strlen( $a['match_phrase'] );
        } );

        $normalized_content = self::normalize_persian( $content );
        $linked_products = array();

        foreach ( $keywords_pool as $kw ) {
            if ( in_array( $kw['url'], array_column( $linked_products, 'url' ), true ) ) {
                continue;
            }

            if ( mb_stripos( $content, $kw['url'] ) !== false ) {
                continue;
            }

            if ( mb_stripos( $normalized_content, $kw['match_phrase'] ) === false ) {
                continue;
            }

            $link_html = '<a href="' . esc_url( $kw['url'] ) . '" title="' . esc_attr( $kw['name'] ) . '" rel="product">' . esc_html( $kw['match_phrase'] ) . '</a>';
            $updated_content = self::replace_keyword_safely( $content, $kw['match_phrase'], $link_html );

            if ( $updated_content !== $content ) {
                $content = $updated_content;
                $linked_products[] = $kw;
            }
        }

        return array(
            'content'     => $content,
            'links_count' => count( $linked_products ),
            'products'    => $linked_products,
        );
    }

    /**
     * REST Callback: Auto-link WooCommerce products with category taxonomy guards.
     */
    public static function handle_auto_link_products( WP_REST_Request $request ) {
        $params      = $request->get_json_params();
        $post_id     = absint( $params['post_id'] ?? 0 );
        $content     = $params['content'] ?? '';
        $category_id = absint( $params['category_id'] ?? 0 );

        if ( $post_id > 0 && empty( $content ) ) {
            $post = get_post( $post_id );
            if ( $post ) {
                $content = $post->post_content;
            }
        }

        if ( empty( $content ) ) {
            return new WP_REST_Response( array( 'error' => 'Content or valid post_id is required' ), 400 );
        }

        $result = self::auto_link_products( $content, $post_id, $category_id );

        if ( $post_id > 0 && $result['links_count'] > 0 ) {
            wp_update_post( array(
                'ID'           => $post_id,
                'post_content' => $result['content'],
            ) );
        }

        return new WP_REST_Response( array(
            'status'         => 'success',
            'updated_content'=> $result['content'],
            'links_count'    => $result['links_count'],
            'products'       => $result['products'],
        ), 200 );
    }

    /**
     * Automatic Internal Linking for Site Articles, Categories & Resources.
     * Injects 2 to 5 relevant internal links into blog post content.
     */
    public static function auto_link_site_content( $content, $exclude_post_id = 0, $max_links = 4 ) {
        if ( empty( $content ) || ! is_string( $content ) ) {
            return $content;
        }

        // Check if auto internal linking is enabled
        if ( get_option( 'kavoshrank_enable_auto_linking', '1' ) !== '1' ) {
            return $content;
        }

        // Pool of potential internal link targets
        $targets = array();

        // 1. Fetch recent published articles (exclude current post)
        $posts_query_args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 40,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        );
        if ( $exclude_post_id > 0 ) {
            $posts_query_args['post__not_in'] = array( (int) $exclude_post_id );
        }

        $post_ids = get_posts( $posts_query_args );
        foreach ( $post_ids as $pid ) {
            $title = trim( get_the_title( $pid ) );
            $url   = get_permalink( $pid );
            if ( empty( $title ) || mb_strlen( $title ) < 4 || empty( $url ) ) {
                continue;
            }

            // Primary keyword from title (e.g. without punctuation)
            $clean_title = trim( preg_replace( '/[^\p{L}\p{N}\s]/u', ' ', $title ) );
            $targets[] = array(
                'text'  => $title,
                'match' => $title,
                'url'   => $url,
            );

            // Shorter phrases from title (3-4 words)
            $words = preg_split( '/\s+/u', $clean_title );
            if ( count( $words ) >= 3 ) {
                $phrase = implode( ' ', array_slice( $words, 0, 3 ) );
                if ( mb_strlen( $phrase ) >= 6 ) {
                    $targets[] = array(
                        'text'  => $title,
                        'match' => $phrase,
                        'url'   => $url,
                    );
                }
            }
        }

        // 2. Fetch Categories as fallback taxonomy links
        $categories = get_categories( array( 'hide_empty' => true, 'number' => 20 ) );
        foreach ( $categories as $cat ) {
            if ( $cat->slug === 'uncategorized' || mb_strlen( $cat->name ) < 3 ) {
                continue;
            }
            $cat_url = get_category_link( $cat->term_id );
            if ( ! empty( $cat_url ) ) {
                $targets[] = array(
                    'text'  => $cat->name,
                    'match' => $cat->name,
                    'url'   => $cat_url,
                );
            }
        }

        // 3. Include products if WooCommerce is available
        if ( class_exists( 'WooCommerce' ) ) {
            $prod_res = self::auto_link_products( $content, $exclude_post_id );
            if ( ! empty( $prod_res['content'] ) && $prod_res['content'] !== $content ) {
                $content = $prod_res['content'];
                $max_links -= (int) $prod_res['links_count'];
                if ( $max_links <= 0 ) {
                    return $content;
                }
            }
        }

        if ( empty( $targets ) ) {
            return $content;
        }

        // Sort targets by match length descending (longest phrase match first)
        usort( $targets, function( $a, $b ) {
            return mb_strlen( $b['match'] ) - mb_strlen( $a['match'] );
        } );

        $normalized_content = self::normalize_persian( $content );
        $linked_urls = array();
        $links_applied = 0;

        foreach ( $targets as $target ) {
            if ( $links_applied >= $max_links ) {
                break;
            }

            if ( in_array( $target['url'], $linked_urls, true ) ) {
                continue;
            }

            // Do not link if already present in content
            if ( mb_stripos( $content, $target['url'] ) !== false ) {
                continue;
            }

            $match_clean = self::normalize_persian( $target['match'] );
            if ( mb_strlen( $match_clean ) < 4 ) {
                continue;
            }

            if ( mb_stripos( $normalized_content, $match_clean ) === false ) {
                continue;
            }

            $link_html = '<a href="' . esc_url( $target['url'] ) . '" title="' . esc_attr( $target['text'] ) . '" class="kavoshrank-internal-link">' . esc_html( $target['match'] ) . '</a>';
            $updated = self::replace_keyword_safely( $content, $target['match'], $link_html );

            if ( $updated !== $content ) {
                $content = $updated;
                $linked_urls[] = $target['url'];
                $links_applied++;
                $normalized_content = self::normalize_persian( $content );
            }
        }

        return $content;
    }
}
