<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Post_REST_Handler {

    public static function register_kses_allowed_html() {
        add_filter( 'wp_kses_allowed_html', array( __CLASS__, 'filter_allowed_html' ), 10, 2 );
    }

    public static function filter_allowed_html( $allowed, $context ) {
        if ( $context === 'post' ) {
            $allowed['iframe'] = array(
                'src'             => array(),
                'height'          => array(),
                'width'           => array(),
                'frameborder'     => array(),
                'allowfullscreen' => array(),
                'class'           => array(),
                'style'           => array(),
            );
            $allowed['figure']     = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['figcaption'] = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['details']    = array( 'class' => array(), 'open' => array(), 'style' => array(), 'id' => array() );
            $allowed['summary']    = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['table']      = array( 'class' => array(), 'style' => array(), 'id' => array(), 'border' => array(), 'cellpadding' => array(), 'cellspacing' => array() );
            $allowed['thead']      = array( 'class' => array(), 'style' => array() );
            $allowed['tbody']      = array( 'class' => array(), 'style' => array() );
            $allowed['tfoot']      = array( 'class' => array(), 'style' => array() );
            $allowed['tr']         = array( 'class' => array(), 'style' => array() );
            $allowed['th']         = array( 'class' => array(), 'scope' => array(), 'style' => array(), 'colspan' => array(), 'rowspan' => array() );
            $allowed['td']         = array( 'class' => array(), 'colspan' => array(), 'rowspan' => array(), 'style' => array() );
            $allowed['section']    = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['article']    = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['header']     = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['footer']     = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['nav']        = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['aside']      = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['main']       = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['div']        = array( 'class' => array(), 'style' => array(), 'id' => array(), 'dir' => array() );
            $allowed['p']          = array( 'class' => array(), 'style' => array(), 'id' => array(), 'dir' => array() );
            $allowed['span']       = array( 'class' => array(), 'style' => array(), 'id' => array(), 'dir' => array() );
            $allowed['h1']         = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['h2']         = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['h3']         = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['h4']         = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['h5']         = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['h6']         = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['ul']         = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['ol']         = array( 'class' => array(), 'style' => array(), 'id' => array(), 'start' => array(), 'reversed' => array() );
            $allowed['li']         = array( 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['a']          = array( 'href' => array(), 'title' => array(), 'target' => array(), 'rel' => array(), 'class' => array(), 'style' => array(), 'id' => array() );
            $allowed['img']        = array( 'src' => array(), 'alt' => array(), 'title' => array(), 'width' => array(), 'height' => array(), 'class' => array(), 'style' => array(), 'loading' => array(), 'id' => array() );
            $allowed['blockquote'] = array( 'class' => array(), 'style' => array(), 'cite' => array(), 'id' => array() );
            $allowed['code']       = array( 'class' => array(), 'style' => array() );
            $allowed['pre']        = array( 'class' => array(), 'style' => array() );
            $allowed['strong']     = array( 'class' => array(), 'style' => array() );
            $allowed['b']          = array( 'class' => array(), 'style' => array() );
            $allowed['em']         = array( 'class' => array(), 'style' => array() );
            $allowed['i']          = array( 'class' => array(), 'style' => array() );
            $allowed['u']          = array( 'class' => array(), 'style' => array() );
            $allowed['br']         = array( 'class' => array() );
            $allowed['hr']         = array( 'class' => array(), 'style' => array() );
            $allowed['mark']       = array( 'class' => array(), 'style' => array() );
        }
        return $allowed;
    }

    /**
     * kses cannot restrict an attribute by host, so drop every iframe opening
     * tag whose src is not an http(s) aparat.com URL before the content is
     * saved. Each opening tag is checked on its own, so an iframe nested in an
     * allowed one is still checked. A leftover closing tag renders nothing.
     */
    public static function strip_disallowed_iframes( $html ) {
        $html = (string) $html;
        if ( false === stripos( $html, 'iframe' ) ) {
            return $html;
        }

        return (string) preg_replace_callback(
            '#<\s*iframe\b[^>]*>#i',
            array( __CLASS__, 'filter_iframe_match' ),
            $html
        );
    }

    public static function filter_iframe_match( $match ) {
        // Parse attributes the same way kses does, so the src checked here is
        // the src kses keeps.
        if ( ! preg_match( '#^<\s*iframe\b([^>]*)>#i', $match[0], $tag ) ) {
            return '';
        }
        $attrs = wp_kses_hair( rtrim( $tag[1], '/' ), array( 'http', 'https' ) );
        if ( empty( $attrs['src']['value'] ) ) {
            return '';
        }

        $url    = html_entity_decode( $attrs['src']['value'], ENT_QUOTES );
        $scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
        $host   = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

        $allowed_host = 'aparat.com' === $host || '.aparat.com' === substr( $host, -11 );
        if ( in_array( $scheme, array( 'http', 'https' ), true ) && $allowed_host ) {
            return $match[0];
        }

        return '';
    }

    /**
     * Callback for GET /wp-json/kavoshrank/v1/posts
     */
    public static function handle_get_posts( WP_REST_Request $request ) {
        $limit          = absint( $request->get_param( 'limit' ) ?: 100 );
        $search         = sanitize_text_field( $request->get_param( 'search' ) ?: '' );
        $status         = sanitize_text_field( $request->get_param( 'status' ) ?: 'any' );
        $requested_type = sanitize_text_field( $request->get_param( 'post_type' ) ?: 'all' );

        $posts_limit = $limit > 0 ? $limit : 100;
        $post_status = ( $status !== 'any' && ! empty( $status ) ) ? $status : array( 'publish', 'draft', 'future', 'pending' );

        $posts = array();
        if ( $requested_type === 'post' ) {
            $args = array(
                'numberposts' => $posts_limit,
                'post_type'   => 'post',
                'post_status' => $post_status,
                'orderby'     => 'date',
                'order'       => 'DESC',
            );
            if ( ! empty( $search ) ) {
                $args['s'] = $search;
            }
            $posts = get_posts( $args );
        } elseif ( $requested_type === 'product' ) {
            $args = array(
                'numberposts' => $posts_limit,
                'post_type'   => 'product',
                'post_status' => $post_status,
                'orderby'     => 'date',
                'order'       => 'DESC',
            );
            if ( ! empty( $search ) ) {
                $args['s'] = $search;
            }
            $posts = get_posts( $args );
        } else {
            // Balanced query: fetch posts and products separately so neither starves the other
            $post_args = array(
                'numberposts' => $posts_limit,
                'post_type'   => 'post',
                'post_status' => $post_status,
                'orderby'     => 'date',
                'order'       => 'DESC',
            );
            $prod_args = array(
                'numberposts' => $posts_limit,
                'post_type'   => 'product',
                'post_status' => $post_status,
                'orderby'     => 'date',
                'order'       => 'DESC',
            );
            if ( ! empty( $search ) ) {
                $post_args['s'] = $search;
                $prod_args['s'] = $search;
            }

            $blog_posts = get_posts( $post_args );
            $prod_posts = post_type_exists( 'product' ) ? get_posts( $prod_args ) : array();

            $merged = array_merge( $blog_posts, $prod_posts );
            usort( $merged, function( $a, $b ) {
                $tA = strtotime( $a->post_date ?: $a->post_modified );
                $tB = strtotime( $b->post_date ?: $b->post_modified );
                if ( $tA === $tB ) {
                    return $b->ID - $a->ID;
                }
                return $tB - $tA;
            } );

            $posts = array_slice( $merged, 0, $posts_limit );
        }

        $results = array();

        foreach ( $posts as $p ) {
            $cat_name = '';
            if ( $p->post_type === 'product' ) {
                $prod_cats = wp_get_post_terms( $p->ID, 'product_cat', array( 'fields' => 'names' ) );
                if ( ! empty( $prod_cats ) && ! is_wp_error( $prod_cats ) ) {
                    $cat_name = $prod_cats[0];
                }
            }
            if ( empty( $cat_name ) ) {
                $cat_names = wp_get_post_terms( $p->ID, 'category', array( 'fields' => 'names' ) );
                if ( ! empty( $cat_names ) && ! is_wp_error( $cat_names ) ) {
                    $cat_name = $cat_names[0];
                }
            }
            if ( empty( $cat_name ) ) {
                $cat_name = $p->post_type === 'product' ? 'محصولات عمومی' : 'دسته‌بندی عمومی';
            }
            
            $thumb_id = get_post_thumbnail_id( $p->ID );
            $thumb_url = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';
            if ( empty( $thumb_url ) ) {
                if ( preg_match( '/<img\b[^>]*?\bsrc=["\']([^"\']+)["\']/i', $p->post_content, $m ) ) {
                    $thumb_url = $m[1];
                }
            }

            $clean_preview = wp_strip_all_tags( $p->post_content );
            if ( mb_strlen( $clean_preview ) > 220 ) {
                $clean_preview = mb_substr( $clean_preview, 0, 220 ) . '...';
            }

            $words = count( preg_split( '/\s+/u', wp_strip_all_tags( $p->post_content ), -1, PREG_SPLIT_NO_EMPTY ) );
            $token_cost = max( 450, intval( $words * 0.75 ) );

            $item_data = array(
                'id'              => $p->ID,
                'post_id'         => $p->ID,
                'project_id'      => 1,
                'topic'           => $p->post_title,
                'title'           => $p->post_title,
                'slug'            => $p->post_name,
                'content'         => $p->post_content,
                'content_type'    => $p->post_type === 'product' ? 'product' : 'article',
                'category'        => (string) $cat_name,
                'focus_keyword'   => (string) get_post_meta( $p->ID, '_yoast_wpseo_focuskw', true ),
                'meta_title'      => (string) get_post_meta( $p->ID, '_yoast_wpseo_title', true ),
                'meta_description'=> (string) get_post_meta( $p->ID, '_yoast_wpseo_metadesc', true ),
                'word_count'      => $words,
                'token_cost'      => $token_cost,
                'status'          => $p->post_status === 'publish' ? 'published' : $p->post_status,
                'featured_image'  => (string) $thumb_url,
                'content_preview' => $clean_preview,
                'created_at'      => get_the_date( 'Y/m/d - H:i', $p->ID ),
            );

            if ( $p->post_type === 'product' ) {
                $wc_prod = function_exists( 'wc_get_product' ) ? wc_get_product( $p->ID ) : null;
                $item_data['short_description'] = (string) $p->post_excerpt;
                $item_data['sku']               = $wc_prod ? (string) $wc_prod->get_sku() : (string) get_post_meta( $p->ID, '_sku', true );
                $item_data['regular_price']     = $wc_prod ? (string) $wc_prod->get_regular_price() : (string) get_post_meta( $p->ID, '_regular_price', true );
                $item_data['sale_price']        = $wc_prod ? (string) $wc_prod->get_sale_price() : (string) get_post_meta( $p->ID, '_sale_price', true );
                $item_data['price']             = $wc_prod ? (string) $wc_prod->get_price() : (string) get_post_meta( $p->ID, '_price', true );
                $item_data['catalog_mode']      = get_post_meta( $p->ID, '_kavoshrank_catalog_mode', true ) === 'yes';
                $item_data['button_label']      = (string) get_post_meta( $p->ID, '_kavoshrank_button_label', true );
                $prod_specs                     = get_post_meta( $p->ID, '_kavoshrank_product_specs', true );
                if ( empty( $prod_specs ) || ! is_array( $prod_specs ) ) {
                    $prod_specs = get_post_meta( $p->ID, '_tsw_product_specs', true );
                }
                $item_data['specs']             = ( is_array( $prod_specs ) && ! empty( $prod_specs ) ) ? $prod_specs : (object) array();
            }

            $results[] = $item_data;
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'posts'    => $results,
            'articles' => $results,
            'total'    => count( $results ),
        ), 200 );
    }

    /**
     * Callback for POST /wp-json/kavoshrank/v1/posts
     */
    public static function handle_create_or_update_post( WP_REST_Request $request ) {
        self::register_kses_allowed_html();

        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        $allowed_post_types = array( 'post', 'product' );
        $post_type = sanitize_text_field( $params['post_type'] ?? 'post' );
        if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
            return new WP_REST_Response( array( 'error' => 'Disallowed post_type. Only post and product are permitted.' ), 400 );
        }

        $allowed_statuses = array( 'publish', 'draft', 'future' );
        $status = sanitize_text_field( $params['status'] ?? $params['post_status'] ?? $params['publish_status'] ?? 'publish' );
        if ( ! in_array( $status, $allowed_statuses, true ) ) {
            $status = 'publish';
        }

        $id            = absint( $params['id'] ?? ( $params['post_id'] ?? 0 ) );
        $title         = sanitize_text_field( $params['title'] ?? ( $params['post_title'] ?? '' ) );
        $slug          = sanitize_title( $params['slug'] ?? ( $params['post_name'] ?? '' ) );
        $lock_slug     = ! empty( $params['lock_slug'] );
        $content       = self::strip_disallowed_iframes( $params['content'] ?? ( $params['content_html'] ?? '' ) );
        $excerpt       = sanitize_textarea_field( $params['excerpt'] ?? '' );
        $category_id   = absint( $params['category_id'] ?? 0 );
        $category_name = sanitize_text_field( $params['category_name'] ?? ( $params['category'] ?? '' ) );
        $focus_kw      = sanitize_text_field( $params['focus_keyword'] ?? '' );
        $meta_desc     = sanitize_text_field( $params['meta_description'] ?? '' );
        $matched_title = sanitize_text_field( $params['matched_title'] ?? '' );
        $taxonomy      = sanitize_text_field( $params['taxonomy'] ?? 'category' );
        $pillar_cat    = sanitize_text_field( $params['pillar_category'] ?? '' );
        $sub_cat       = sanitize_text_field( $params['sub_category'] ?? '' );
        $matrix_dim    = sanitize_text_field( $params['content_matrix_dimension'] ?? '' );
        $is_expansion  = ! empty( $params['is_expansion'] );
        $featured_media_id = absint( $params['featured_media_id'] ?? ( $params['featured_media'] ?? ( $params['thumbnail_id'] ?? 0 ) ) );
        $featured_img  = $params['featured_image'] ?? ( $params['featured_image_url'] ?? ( $params['image_url'] ?? ( $params['thumbnail_url'] ?? '' ) ) );
        if ( ( empty( $featured_img ) || strpos( (string) $featured_img, 'blob:' ) === 0 ) && ! empty( $content ) ) {
            if ( preg_match( '/<img\b[^>]*?\bsrc=["\']([^"\']+)["\']/i', $content, $img_match ) ) {
                if ( strpos( $img_match[1], 'blob:' ) !== 0 ) {
                    $featured_img = $img_match[1];
                } else {
                    $featured_img = '';
                }
            }
        }

        // Dynamic Category Resolution & Auto-Creation if missing
        $target_cat_name = ! empty( $category_name ) ? $category_name : $pillar_cat;
        if ( $category_id <= 0 && ! empty( $target_cat_name ) ) {
            $term = term_exists( $target_cat_name, $taxonomy );
            if ( ! $term ) {
                $inserted = wp_insert_term( $target_cat_name, $taxonomy );
                if ( ! is_wp_error( $inserted ) && isset( $inserted['term_id'] ) ) {
                    $category_id = (int) $inserted['term_id'];
                }
            } else {
                $category_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
            }
        }

        // Scenario A: Pillar Article Expansion (by ID or matched title)
        if ( $id > 0 || ! empty( $matched_title ) || $is_expansion ) {
            $existing_post = null;
            if ( $id > 0 ) {
                $existing_post = get_post( $id );
                if ( ! $existing_post || ! in_array( $existing_post->post_type, $allowed_post_types, true ) ) {
                    return new WP_REST_Response( array( 'error' => 'Target post not found or invalid post type' ), 400 );
                }
            }

            if ( ! $existing_post && ! empty( $matched_title ) ) {
                $posts = get_posts( array(
                    'title'          => $matched_title,
                    'post_type'      => $post_type,
                    'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
                    'posts_per_page' => 1,
                ) );

                if ( empty( $posts ) ) {
                    $posts = get_posts( array(
                        's'              => $matched_title,
                        'post_type'      => $post_type,
                        'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
                        'posts_per_page' => 1,
                    ) );
                }

                if ( ! empty( $posts ) ) {
                    $existing_post = $posts[0];
                }
            }

            if ( $existing_post ) {
                $existing_post_id = $existing_post->ID;
                $updated_content  = $content;
                if ( $is_expansion && strpos( $content, $existing_post->post_content ) === false ) {
                    $updated_content = $existing_post->post_content . "\n\n" . $content;
                }

                // Auto internal linking for expansion
                if ( class_exists( 'KavoshRank_Link_REST_Handler' ) ) {
                    $linked = KavoshRank_Link_REST_Handler::auto_link_site_content( $updated_content, $existing_post_id );
                    if ( ! empty( $linked ) && is_string( $linked ) ) {
                        $updated_content = $linked;
                    }
                }

                // Strip or replace any browser blob: URLs so WordPress visual editor never sees local browser blob URLs
                if ( preg_match( '/src=["\']blob:[^"\']+["\']/i', $updated_content ) ) {
                    if ( ! empty( $featured_img ) && strpos( (string) $featured_img, 'blob:' ) !== 0 && ( strpos( (string) $featured_img, 'http' ) === 0 || strpos( (string) $featured_img, '/' ) === 0 ) ) {
                        $cleaned = preg_replace( '/src=["\']blob:[^"\']+["\']/u', 'src="' . esc_url( $featured_img ) . '"', $updated_content );
                        if ( $cleaned !== null ) {
                            $updated_content = $cleaned;
                        }
                    } else {
                        $cleaned = preg_replace( '/<figure\b[^>]*kavoshrank-article-figure[^>]*>\s*<img\b[^>]*?\bsrc=["\']blob:[^"\']+["\'][^>]*>.*?<\/figure>/isu', '', $updated_content );
                        if ( $cleaned !== null ) {
                            $updated_content = $cleaned;
                        }
                        $cleaned2 = preg_replace( '/<img\b[^>]*?\bsrc=["\']blob:[^"\']+["\'][^>]*>/u', '', $updated_content );
                        if ( $cleaned2 !== null ) {
                            $updated_content = $cleaned2;
                        }
                    }
                }

                if ( empty( trim( (string) $updated_content ) ) && ! empty( trim( (string) $content ) ) ) {
                    $updated_content = $content;
                }

                $update_args = array(
                    'ID'           => $existing_post_id,
                    'post_content' => wp_slash( $updated_content ),
                );

                // Strictly lock original slug during expansion to protect ranking & PageRank
                if ( ! $lock_slug && ! empty( $title ) && $title !== $existing_post->post_title ) {
                    $update_args['post_title'] = $title;
                }
                if ( ! empty( $slug ) ) {
                    $update_args['post_name'] = $slug;
                }

                wp_update_post( $update_args );

                // Assign category & featured image if provided
                if ( $category_id > 0 ) {
                    wp_set_post_terms( $existing_post_id, array( $category_id ), $taxonomy );
                }
                if ( $featured_media_id > 0 ) {
                    set_post_thumbnail( $existing_post_id, $featured_media_id );
                    update_post_meta( $existing_post_id, '_thumbnail_id', $featured_media_id );
                    $att = get_post( $featured_media_id );
                    if ( $att && empty( $att->post_parent ) ) {
                        wp_update_post( array( 'ID' => $featured_media_id, 'post_parent' => $existing_post_id ) );
                    }
                    self::inject_featured_image_into_content( $existing_post_id, $featured_media_id, $title );
                } elseif ( ! empty( $featured_img ) && strpos( (string) $featured_img, 'blob:' ) !== 0 ) {
                    self::sideload_and_set_featured_image( $existing_post_id, $featured_img, $title );
                }

                $current_thumb_id = get_post_thumbnail_id( $existing_post_id );
                if ( $current_thumb_id > 0 ) {
                    self::inject_featured_image_into_content( $existing_post_id, (int) $current_thumb_id, $title );
                }

                // Update Yoast SEO & Matrix metadata
                if ( ! empty( $focus_kw ) ) {
                    update_post_meta( $existing_post_id, '_yoast_wpseo_focuskw', $focus_kw );
                }
                if ( ! empty( $meta_desc ) ) {
                    update_post_meta( $existing_post_id, '_yoast_wpseo_metadesc', $meta_desc );
                }
                if ( ! empty( $matrix_dim ) ) {
                    update_post_meta( $existing_post_id, '_kavoshrank_content_matrix_dimension', $matrix_dim );
                }
                if ( ! empty( $pillar_cat ) ) {
                    update_post_meta( $existing_post_id, '_kavoshrank_pillar_category', $pillar_cat );
                }

                return new WP_REST_Response( array(
                    'status'             => 'success',
                    'action'             => 'expanded',
                    'post_id'            => $existing_post_id,
                    'id'                 => $existing_post_id,
                    'url'                => get_permalink( $existing_post_id ),
                    'slug'               => $existing_post->post_name,
                    'slug_locked'        => true,
                ), 200 );
            }
        }

        // Scenario B: Create New Post
        $scheduled_date = sanitize_text_field( $params['scheduled_date'] ?? ( $params['date'] ?? ( $params['post_date'] ?? '' ) ) );
        if ( ! empty( $scheduled_date ) ) {
            // Check if format is Jalali: YYYY/MM/DD or YYYY-MM-DD or YYYY/MM/DD HH:MM
            if ( preg_match( '/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})(?:\s+(\d{1,2}):(\d{1,2}))?/', $scheduled_date, $matches ) ) {
                $jy   = intval( $matches[1] );
                $jm   = intval( $matches[2] );
                $jd   = intval( $matches[3] );
                $hour = isset( $matches[4] ) ? intval( $matches[4] ) : 9;
                $min  = isset( $matches[5] ) ? intval( $matches[5] ) : 0;
                if ( $jy >= 1300 && $jy < 1600 ) {
                    if ( class_exists( 'KavoshRank_Jalali' ) ) {
                        $greg = KavoshRank_Jalali::jalali_to_gregorian( $jy, $jm, $jd );
                        $scheduled_date = sprintf( '%04d-%02d-%02d %02d:%02d:00', $greg[0], $greg[1], $greg[2], $hour, $min );
                    }
                }
            }
            if ( $status === 'publish' || empty( $status ) ) {
                $status = 'future';
            }
        }

        // Automatically inject internal links to other site content and categories
        if ( class_exists( 'KavoshRank_Link_REST_Handler' ) ) {
            $linked = KavoshRank_Link_REST_Handler::auto_link_site_content( $content );
            if ( ! empty( $linked ) && is_string( $linked ) ) {
                $content = $linked;
            }
        }

        // Strip or replace any browser blob: URLs so WordPress visual editor never sees local browser blob URLs
        if ( preg_match( '/src=["\']blob:[^"\']+["\']/i', $content ) ) {
            if ( ! empty( $featured_img ) && strpos( (string) $featured_img, 'blob:' ) !== 0 && ( strpos( (string) $featured_img, 'http' ) === 0 || strpos( (string) $featured_img, '/' ) === 0 ) ) {
                $cleaned = preg_replace( '/src=["\']blob:[^"\']+["\']/u', 'src="' . esc_url( $featured_img ) . '"', $content );
                if ( $cleaned !== null ) {
                    $content = $cleaned;
                }
            } else {
                $cleaned = preg_replace( '/<figure\b[^>]*kavoshrank-article-figure[^>]*>\s*<img\b[^>]*?\bsrc=["\']blob:[^"\']+["\'][^>]*>.*?<\/figure>/isu', '', $content );
                if ( $cleaned !== null ) {
                    $content = $cleaned;
                }
                $cleaned2 = preg_replace( '/<img\b[^>]*?\bsrc=["\']blob:[^"\']+["\'][^>]*>/u', '', $content );
                if ( $cleaned2 !== null ) {
                    $content = $cleaned2;
                }
            }
        }

        // Failsafe: Ensure content is never empty if input content was provided
        $raw_input_content = (string) ( $params['content'] ?? ( $params['content_html'] ?? '' ) );
        if ( empty( trim( (string) $content ) ) && ! empty( trim( $raw_input_content ) ) ) {
            $content = $raw_input_content;
        }

        $post_data = array(
            'post_title'   => $title,
            'post_content' => wp_slash( $content ),
            'post_excerpt' => wp_slash( $excerpt ),
            'post_status'  => $status,
            'post_type'    => $post_type,
        );

        if ( ! empty( $scheduled_date ) ) {
            $post_data['post_date']     = $scheduled_date;
            $post_data['post_date_gmt'] = get_gmt_from_date( $scheduled_date );
        }

        if ( ! empty( $slug ) ) {
            $post_data['post_name'] = $slug;
        }

        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            return new WP_REST_Response( array( 'error' => $post_id->get_error_message() ), 500 );
        }

        if ( empty( $post_id ) || (int) $post_id <= 0 ) {
            return new WP_REST_Response( array( 'error' => 'Failed to create post in WordPress' ), 500 );
        }

        // Tag post as KavoshRank managed to prevent collisions with third-party legacy plugins
        update_post_meta( $post_id, '_kavoshrank_article_id', (string) $post_id );

        // Set Category Term (Created or Resolved)
        if ( $category_id > 0 ) {
            wp_set_post_terms( $post_id, array( $category_id ), $taxonomy );
        }

        // Sideload and assign Featured Image
        if ( $featured_media_id > 0 ) {
            set_post_thumbnail( $post_id, $featured_media_id );
            update_post_meta( $post_id, '_thumbnail_id', $featured_media_id );
            $att = get_post( $featured_media_id );
            if ( $att && empty( $att->post_parent ) ) {
                wp_update_post( array( 'ID' => $featured_media_id, 'post_parent' => $post_id ) );
            }
            self::inject_featured_image_into_content( $post_id, $featured_media_id, $title );
        } elseif ( ! empty( $featured_img ) && strpos( (string) $featured_img, 'blob:' ) !== 0 ) {
            self::sideload_and_set_featured_image( $post_id, $featured_img, $title );
        }

        // Post-creation safety: if post has a thumbnail or featured image, ensure content has clean WordPress media URL and no lingering blob: URLs
        $current_thumb_id = get_post_thumbnail_id( $post_id );
        if ( $current_thumb_id > 0 ) {
            self::inject_featured_image_into_content( $post_id, (int) $current_thumb_id, $title );
        }

        // Set Yoast SEO Metadata
        if ( ! empty( $focus_kw ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_kw );
        }
        if ( ! empty( $meta_desc ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_desc );
        }
        if ( ! empty( $matrix_dim ) ) {
            update_post_meta( $post_id, '_kavoshrank_content_matrix_dimension', $matrix_dim );
        }
        if ( ! empty( $pillar_cat ) ) {
            update_post_meta( $post_id, '_kavoshrank_pillar_category', $pillar_cat );
        }
        if ( ! empty( $sub_cat ) ) {
            update_post_meta( $post_id, '_kavoshrank_sub_category', $sub_cat );
        }

        return new WP_REST_Response( array(
            'status'  => 'success',
            'action'  => 'created',
            'post_id' => $post_id,
            'id'      => $post_id,
            'url'     => get_permalink( $post_id ),
            'slug'    => get_post_field( 'post_name', $post_id ),
        ), 200 );
    }

    /**
     * Sideload an image from Base64 or URL and assign it as the WordPress post thumbnail.
     */
    public static function sideload_and_set_featured_image( $post_id, $image_source, $title = '' ) {
        if ( empty( $post_id ) || empty( $image_source ) || strpos( (string) $image_source, 'blob:' ) === 0 ) {
            return 0;
        }

        // 1. Direct Attachment ID
        if ( is_numeric( $image_source ) && (int) $image_source > 0 ) {
            set_post_thumbnail( $post_id, (int) $image_source );
            self::inject_featured_image_into_content( $post_id, (int) $image_source, $title );
            return (int) $image_source;
        }

        $image_source = trim( (string) $image_source );

        // 2. Base64 Data URL (data:image/...;base64,...)
        if ( preg_match( '/^data:image\/(\w+);base64,(.*)$/s', $image_source, $matches ) ) {
            $ext = strtolower( $matches[1] );
            if ( $ext === 'jpeg' ) {
                $ext = 'jpg';
            }
            $img_data = base64_decode( $matches[2] );
            if ( ! empty( $img_data ) ) {
                $filename = 'post-' . $post_id . '-featured-' . time() . '.' . $ext;
                $upload   = wp_upload_bits( $filename, null, $img_data );
                if ( empty( $upload['error'] ) && ! empty( $upload['file'] ) ) {
                    require_once( ABSPATH . 'wp-admin/includes/image.php' );
                    require_once( ABSPATH . 'wp-admin/includes/file.php' );
                    require_once( ABSPATH . 'wp-admin/includes/media.php' );

                    $file_type  = wp_check_filetype( $filename, null );
                    $attachment = array(
                        'post_mime_type' => $file_type['type'] ?: ( 'image/' . $ext ),
                        'post_title'     => sanitize_text_field( $title ?: pathinfo( $filename, PATHINFO_FILENAME ) ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    );
                    $attach_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );
                    if ( ! is_wp_error( $attach_id ) && (int) $attach_id > 0 ) {
                        $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                        wp_update_attachment_metadata( $attach_id, $attach_data );
                        set_post_thumbnail( $post_id, (int) $attach_id );
                        self::inject_featured_image_into_content( $post_id, (int) $attach_id, $title );
                        return (int) $attach_id;
                    }
                }
            }
            return 0;
        }

        // 3. Attachment resolution by URL, path, or filename (covers http://, https://, and relative /wp-content/...)
        $filename = basename( (string) wp_parse_url( $image_source, PHP_URL_PATH ) );
        if ( ! empty( $filename ) ) {
            global $wpdb;
            $existing_att_id = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
                '%' . $wpdb->esc_like( $filename )
            ) );
            if ( $existing_att_id > 0 ) {
                set_post_thumbnail( $post_id, (int) $existing_att_id );
                self::inject_featured_image_into_content( $post_id, (int) $existing_att_id, $title );
                return (int) $existing_att_id;
            }
        }

        // 4. Remote URL Sideloading (if not already in media library)
        if ( strpos( $image_source, 'http://' ) === 0 || strpos( $image_source, 'https://' ) === 0 ) {
            $existing_att_id = attachment_url_to_postid( $image_source );
            if ( $existing_att_id > 0 ) {
                set_post_thumbnail( $post_id, (int) $existing_att_id );
                self::inject_featured_image_into_content( $post_id, (int) $existing_att_id, $title );
                return (int) $existing_att_id;
            }

            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $desc = sanitize_text_field( $title ?: get_the_title( $post_id ) );

            // Attempt A: Standard WordPress sideload
            $attach_id = media_sideload_image( $image_source, $post_id, $desc, 'id' );
            if ( ! is_wp_error( $attach_id ) && (int) $attach_id > 0 ) {
                set_post_thumbnail( $post_id, (int) $attach_id );
                self::inject_featured_image_into_content( $post_id, (int) $attach_id, $title );
                return (int) $attach_id;
            }

            // Attempt B: Resilient wp_safe_remote_get with TLS verification enabled
            $response = wp_safe_remote_get( $image_source, array(
                'timeout'   => 25,
                'sslverify' => true,
            ) );

            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $body = wp_remote_retrieve_body( $response );
                if ( strlen( $body ) > 15728640 ) { // 15 MB limit
                    return 0;
                }
                $content_type = (string) wp_remote_retrieve_header( $response, 'content-type' );
                $ext          = 'jpg';
                if ( strpos( $content_type, 'png' ) !== false ) {
                    $ext = 'png';
                } elseif ( strpos( $content_type, 'webp' ) !== false ) {
                    $ext = 'webp';
                } elseif ( strpos( $content_type, 'gif' ) !== false ) {
                    $ext = 'gif';
                }

                $filename = 'post-' . $post_id . '-thumb-' . time() . '.' . $ext;
                $upload   = wp_upload_bits( $filename, null, $body );
                if ( empty( $upload['error'] ) && ! empty( $upload['file'] ) ) {
                    $file_check = wp_check_filetype_and_ext( $upload['file'], $filename );
                    $checked_ext = strtolower( (string) $file_check['ext'] );
                    if ( ! in_array( $checked_ext, array( 'jpg', 'jpeg', 'png', 'webp', 'gif' ), true ) ) {
                        @unlink( $upload['file'] );
                        return 0;
                    }

                    $attachment = array(
                        'post_mime_type' => $file_check['type'] ?: 'image/jpeg',
                        'post_title'     => $desc,
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    );
                    $att_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );
                    if ( ! is_wp_error( $att_id ) && (int) $att_id > 0 ) {
                        $att_data = wp_generate_attachment_metadata( $att_id, $upload['file'] );
                        wp_update_attachment_metadata( $att_id, $att_data );
                        set_post_thumbnail( $post_id, (int) $att_id );
                        self::inject_featured_image_into_content( $post_id, (int) $att_id, $title );
                        return (int) $att_id;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Injects or updates the in-content image with the real WordPress media URL
     */
    public static function inject_featured_image_into_content( $post_id, $attach_id, $title = '' ) {
        $post = get_post( $post_id );
        if ( ! $post || empty( $post->post_content ) ) {
            return;
        }

        $image_url = wp_get_attachment_url( $attach_id );
        if ( empty( $image_url ) ) {
            return;
        }

        $content = $post->post_content;

        // 1. Check if there are any blob: or placeholder images in content
        $placeholder_pattern = '/<img\b[^>]*?\bsrc=["\'](blob:[^"\']+|[^"\']*placeholder[^"\']*|[^"\']*dummy[^"\']*)["\'][^>]*>/i';
        if ( preg_match( $placeholder_pattern, $content ) ) {
            $content = preg_replace( '/(<img\b[^>]*?\bsrc=["\'])(?:blob:[^"\']+|[^"\']*placeholder[^"\']*|[^"\']*dummy[^"\']*)(["\'][^>]*>)/i', '${1}' . esc_url( $image_url ) . '${2}', $content, 1 );
            wp_update_post( array(
                'ID'           => $post_id,
                'post_content' => wp_slash( $content ),
            ) );
            return;
        }

        // If the content already has real valid images, do not swap them for the featured image
        if ( preg_match( '/<img\b[^>]*?\bsrc=["\'][^"\']+["\'][^>]*>/i', $content ) ) {
            return;
        }

        // 2. If no <img> tag exists in content, inject a styled figure
        $alt_text = esc_attr( $title ?: $post->post_title );
        $figure_html = '
        <figure class="kavoshrank-article-figure" style="margin: 32px auto; text-align: center; max-width: 100%;">
            <img src="' . esc_url( $image_url ) . '" alt="' . $alt_text . '" class="aligncenter size-large rounded-2xl shadow-md" style="border-radius: 14px; max-width: 100%; height: auto; display: block; margin: 0 auto; box-shadow: 0 8px 24px rgba(0,0,0,0.08);" loading="lazy" />
            <figcaption style="font-size: 13px; color: #64748b; margin-top: 10px; font-weight: 500;">' . esc_html( $alt_text ) . '</figcaption>
        </figure>';

        $h2_pos = strpos( $content, '</h2>' );
        if ( $h2_pos !== false ) {
            $content = substr_replace( $content, '</h2>' . "\n" . $figure_html, $h2_pos, 5 );
        } else {
            $paragraphs = explode( '</p>', $content );
            if ( count( $paragraphs ) > 2 ) {
                $paragraphs[1] .= '</p>' . "\n" . $figure_html;
                $content = implode( '</p>', $paragraphs );
            } else {
                $content = $figure_html . "\n\n" . $content;
            }
        }

        wp_update_post( array(
            'ID'           => $post_id,
            'post_content' => wp_slash( $content ),
        ) );
    }

    /**
     * Callback for POST /wp-json/kavoshrank/v1/posts/<id>/publish
     *
     * Publishes an existing draft, pending or scheduled post. Only the status
     * changes; the content is left exactly as stored.
     */
    public static function handle_publish_post( WP_REST_Request $request ) {
        $id = (int) $request->get_param( 'id' );
        if ( ! $id ) {
            return new WP_Error( 'invalid_post_id', 'Invalid post ID provided', array( 'status' => 400 ) );
        }
        $post = get_post( $id );
        if ( ! $post || ! in_array( $post->post_type, array( 'post', 'product' ), true ) ) {
            return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
        }
        if ( 'trash' === $post->post_status ) {
            return new WP_Error( 'post_trashed', 'Trashed posts cannot be published', array( 'status' => 409 ) );
        }

        if ( 'future' === $post->post_status ) {
            // wp_update_post would keep a future-dated post scheduled.
            wp_publish_post( $id );
        } elseif ( 'publish' !== $post->post_status ) {
            $result = wp_update_post( array(
                'ID'          => $id,
                'post_status' => 'publish',
            ), true );
            if ( is_wp_error( $result ) ) {
                return new WP_Error( 'publish_failed', $result->get_error_message(), array( 'status' => 500 ) );
            }
        }
        if ( 'publish' !== get_post_status( $id ) ) {
            return new WP_Error( 'publish_failed', 'Post could not be published', array( 'status' => 500 ) );
        }

        return rest_ensure_response( array(
            'success' => true,
            'id'      => $id,
            'post_id' => $id,
            'status'  => get_post_status( $id ),
            'url'     => get_permalink( $id ),
        ) );
    }

    /**
     * Callback for DELETE /wp-json/kavoshrank/v1/posts/<id>
     */
    public static function handle_delete_post( WP_REST_Request $request ) {
        $id = (int) $request->get_param( 'id' );
        if ( ! $id ) {
            return new WP_Error( 'invalid_post_id', 'Invalid post ID provided', array( 'status' => 400 ) );
        }
        $post = get_post( $id );
        // Same post-type allow-list as create/update: pages, attachments and
        // other core types are not ours to trash.
        if ( ! $post || ! in_array( $post->post_type, array( 'post', 'product' ), true ) ) {
            return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
        }
        $result = wp_trash_post( $id );
        if ( ! $result ) {
            return new WP_Error( 'delete_failed', 'Failed to trash post', array( 'status' => 500 ) );
        }
        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Post trashed successfully',
            'id'      => $id,
        ) );
    }
}
