<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Product_REST_Handler {

    const ALLOWED_STATUSES = array( 'publish', 'draft', 'pending', 'private' );
    const MAX_IMAGES       = 5;

    public static function init() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Custom Add-to-Cart button label for catalog products
        add_filter( 'woocommerce_product_single_add_to_cart_text', array( __CLASS__, 'filter_cart_button_text' ), 20, 2 );
        add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'filter_cart_button_text' ), 20, 2 );

        // Ensure catalog-mode products are NOT purchasable (cannot be added to cart)
        add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'filter_is_purchasable' ), 20, 2 );

        // Custom empty / catalog price labels
        add_filter( 'woocommerce_empty_price_html', array( __CLASS__, 'filter_empty_price_html' ), 20, 2 );
        add_filter( 'woocommerce_free_price_html', array( __CLASS__, 'filter_empty_price_html' ), 20, 2 );

        // Add related technical article tab to WooCommerce single product tabs
        add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'add_related_article_tab' ) );
    }

    public static function filter_cart_button_text( $text, $product ) {
        if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
            return $text;
        }

        $product_id   = $product->get_id();
        $catalog_mode = get_post_meta( $product_id, '_kavoshrank_catalog_mode', true );
        $custom_label = get_post_meta( $product_id, '_kavoshrank_button_label', true );

        if ( $catalog_mode === 'yes' ) {
            return ! empty( $custom_label ) ? esc_html( $custom_label ) : 'تماس برای قیمت / استعلام';
        }

        return $text;
    }

    public static function filter_is_purchasable( $purchasable, $product ) {
        if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
            return $purchasable;
        }

        $product_id   = $product->get_id();
        $catalog_mode = get_post_meta( $product_id, '_kavoshrank_catalog_mode', true );
        if ( $catalog_mode === 'yes' ) {
            return false; // Catalog mode products are explicitly not purchasable
        }

        return $purchasable;
    }

    public static function filter_empty_price_html( $price_html, $product ) {
        if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
            return $price_html;
        }

        $product_id   = $product->get_id();
        $catalog_mode = get_post_meta( $product_id, '_kavoshrank_catalog_mode', true );
        $custom_label = get_post_meta( $product_id, '_kavoshrank_button_label', true );

        if ( $catalog_mode === 'yes' ) {
            $label = ! empty( $custom_label ) ? esc_html( $custom_label ) : 'تماس بگیرید / استعلام قیمت';
            return '<span class="kavoshrank-catalog-price font-semibold text-blue-600">' . $label . '</span>';
        }

        return $price_html;
    }

    public static function add_related_article_tab( $tabs ) {
        global $post;
        if ( ! $post ) {
            return $tabs;
        }

        $related_post_id = get_post_meta( $post->ID, '_kavoshrank_related_post_id', true );
        if ( ! $related_post_id ) {
            $related_post_id = get_post_meta( $post->ID, '_tsw_related_post_id', true );
        }

        if ( $related_post_id && get_post( $related_post_id ) ) {
            $tabs['kavoshrank_related_guide'] = array(
                'title'    => 'راهنمای فنی و عیب‌یابی مرتبط',
                'priority' => 30,
                'callback' => array( __CLASS__, 'render_related_guide_tab_content' ),
            );
        }

        return $tabs;
    }

    public static function render_related_guide_tab_content() {
        global $post;
        if ( ! $post ) {
            return;
        }

        $related_post_id = absint( get_post_meta( $post->ID, '_kavoshrank_related_post_id', true ) );
        if ( ! $related_post_id ) {
            $related_post_id = absint( get_post_meta( $post->ID, '_tsw_related_post_id', true ) );
        }

        $related_post = get_post( $related_post_id );
        if ( $related_post ) {
            $url = get_permalink( $related_post->ID );
            echo '<div class="kavoshrank-guide-box" style="padding:15px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0;">';
            echo '<h4 style="margin-top:0;">مقاله و راهنمای تخصصی مرتبط</h4>';
            echo '<p>جهت آشنایی با جزئیات، مشخصات تکمیلی و راهنمای جامع این محصول، مقاله «<a href="' . esc_url( $url ) . '" title="' . esc_attr( $related_post->post_title ) . '" style="font-weight:bold; color:#2563eb;">' . esc_html( $related_post->post_title ) . '</a>» را مطالعه نمایید.</p>';
            echo '</div>';
        }
    }

    /**
     * Persian and Arabic-Indic digits to ASCII, without thousands separators.
     */
    public static function normalize_price( $raw ) {
        $digits = array(
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            ','  => '', '٬' => '', ' ' => '',
        );
        return strtr( trim( (string) $raw ), $digits );
    }

    /**
     * A real price is a plain positive number ("require real price").
     */
    public static function is_valid_price( $price ) {
        return '' !== $price && 1 === preg_match( '/^\d+(\.\d+)?$/', $price ) && (float) $price > 0;
    }

    /**
     * Existing product_cat term by ID, or the term named $category_name
     * (created when missing). Returns 0 when no category was requested.
     *
     * @return int|WP_Error
     */
    public static function resolve_category_id( $category_id, $category_name ) {
        if ( $category_id > 0 ) {
            $term = get_term( $category_id, 'product_cat' );
            if ( ! $term || is_wp_error( $term ) ) {
                return new WP_Error( 'invalid_category', 'Product category not found', array( 'status' => 400 ) );
            }
            return (int) $term->term_id;
        }

        if ( '' === $category_name ) {
            return 0;
        }

        $existing = get_term_by( 'name', $category_name, 'product_cat' );
        if ( $existing && ! is_wp_error( $existing ) ) {
            return (int) $existing->term_id;
        }

        $created = wp_insert_term( $category_name, 'product_cat' );
        if ( is_wp_error( $created ) ) {
            if ( 'term_exists' === $created->get_error_code() && (int) $created->get_error_data() > 0 ) {
                return (int) $created->get_error_data();
            }
            return new WP_Error( 'category_create_failed', $created->get_error_message(), array( 'status' => 500 ) );
        }

        return (int) $created['term_id'];
    }

    /**
     * Product ID that already owns $sku, other than $exclude_id.
     */
    public static function sku_owner( $sku, $exclude_id = 0 ) {
        $owner = (int) wc_get_product_id_by_sku( $sku );
        return ( $owner > 0 && $owner !== (int) $exclude_id ) ? $owner : 0;
    }

    /**
     * Suffix a generated SKU until no other product uses it.
     */
    public static function unique_sku( $sku, $exclude_id = 0 ) {
        for ( $i = 2; $i <= 50; $i++ ) {
            $candidate = $sku . '-' . $i;
            if ( ! self::sku_owner( $candidate, $exclude_id ) ) {
                return $candidate;
            }
        }
        return $sku . '-' . strtoupper( wp_generate_password( 6, false ) );
    }

    public static function handle_create_product( WP_REST_Request $request ) {
        if ( ! class_exists( 'WC_Product_Simple' ) || ! function_exists( 'wc_get_product_id_by_sku' ) ) {
            return new WP_REST_Response( array( 'error' => 'WooCommerce is not active on this site', 'code' => 'woocommerce_inactive' ), 400 );
        }

        $params = $request->get_json_params();
        if ( ! is_array( $params ) ) {
            $params = array();
        }

        $existing_id   = absint( $params['id'] ?? ( $params['product_id'] ?? 0 ) );
        $name          = sanitize_text_field( $params['name'] ?? ( $params['product_name'] ?? '' ) );
        $slug          = sanitize_title( $params['slug'] ?? '' );
        $desc          = (string) ( $params['description'] ?? '' );
        $short_desc    = (string) ( $params['short_description'] ?? '' );
        $sku           = sanitize_text_field( $params['sku'] ?? '' );
        $sku_auto      = ! empty( $params['sku_auto'] );
        $price         = self::normalize_price( sanitize_text_field( $params['regular_price'] ?? ( $params['price'] ?? '' ) ) );
        $sale_price    = self::normalize_price( sanitize_text_field( $params['sale_price'] ?? '' ) );
        $focus_kw      = sanitize_text_field( $params['focus_keyword'] ?? '' );
        $meta_title    = sanitize_text_field( $params['meta_title'] ?? '' );
        $meta_desc     = sanitize_text_field( $params['meta_description'] ?? '' );
        $specs         = $params['specs'] ?? array();
        $category_id   = absint( $params['category_id'] ?? 0 );
        $category_name = sanitize_text_field( $params['category_name'] ?? '' );
        $catalog_mode  = ! empty( $params['catalog_mode'] );
        $button_label  = sanitize_text_field( $params['button_label'] ?? ( $params['custom_button_label'] ?? '' ) );
        $related_post_id = absint( $params['related_post_id'] ?? 0 );
        $auto_link     = ! empty( $params['auto_link_mutually'] );
        $status        = sanitize_key( $params['status'] ?? 'publish' );

        if ( empty( $name ) ) {
            return new WP_REST_Response( array( 'error' => 'Product name is required' ), 400 );
        }

        if ( ! in_array( $status, self::ALLOWED_STATUSES, true ) ) {
            return new WP_REST_Response( array( 'error' => 'Unsupported product status', 'code' => 'invalid_status' ), 400 );
        }

        // Publishing a generated product updates it instead of creating a copy.
        $product = null;
        if ( $existing_id > 0 ) {
            $product = wc_get_product( $existing_id );
            if ( ! $product ) {
                return new WP_REST_Response( array( 'error' => 'Product not found', 'code' => 'product_not_found' ), 404 );
            }
        }

        // Only catalog-mode products may go without a price; never fall back to
        // catalog mode (or a zero price) silently.
        if ( $catalog_mode ) {
            $price      = '';
            $sale_price = '';
        } else {
            if ( ! self::is_valid_price( $price ) ) {
                return new WP_REST_Response( array( 'error' => 'A numeric price greater than zero is required unless catalog mode is on', 'code' => 'invalid_price' ), 400 );
            }
            if ( '' !== $sale_price && (float) $sale_price == 0 ) {
                $sale_price = '';
            }
            if ( '' !== $sale_price && ( ! self::is_valid_price( $sale_price ) || (float) $sale_price >= (float) $price ) ) {
                return new WP_REST_Response( array( 'error' => 'Sale price must be a number lower than the regular price', 'code' => 'invalid_sale_price' ), 400 );
            }
        }

        // SKU uniqueness: a typed SKU that another product owns is rejected, a
        // generated one is suffixed.
        $self_id = $product ? $product->get_id() : 0;
        if ( '' === $sku && ! $product ) {
            $sku      = 'KR-' . strtoupper( substr( md5( $name . wp_generate_password( 12, false ) ), 0, 8 ) );
            $sku_auto = true;
        }
        if ( '' !== $sku ) {
            $owner = self::sku_owner( $sku, $self_id );
            if ( $owner > 0 ) {
                if ( ! $sku_auto ) {
                    return new WP_REST_Response( array(
                        'error'               => 'This SKU is already used by another product',
                        'code'                => 'duplicate_sku',
                        'sku'                 => $sku,
                        'existing_product_id' => $owner,
                    ), 409 );
                }
                $sku = self::unique_sku( $sku, $self_id );
            }
        }

        $category_id = self::resolve_category_id( $category_id, $category_name );
        if ( is_wp_error( $category_id ) ) {
            $error_data = $category_id->get_error_data();
            return new WP_REST_Response( array( 'error' => $category_id->get_error_message(), 'code' => $category_id->get_error_code() ), (int) ( $error_data['status'] ?? 400 ) );
        }

        // Clean up and format specifications
        $specs_assoc = array();
        if ( ! empty( $specs ) && is_array( $specs ) ) {
            foreach ( $specs as $k => $v ) {
                if ( is_array( $v ) && isset( $v['key'] ) && isset( $v['value'] ) ) {
                    $specs_assoc[ sanitize_text_field( $v['key'] ) ] = sanitize_text_field( $v['value'] );
                } else {
                    $specs_assoc[ sanitize_text_field( (string) $k ) ] = sanitize_text_field( (string) $v );
                }
            }
        }

        // Append Specs Table to description if specs exist
        if ( ! empty( $specs_assoc ) && strpos( $desc, 'kavoshrank-specs-table' ) === false ) {
            $table_html = '<div class="kavoshrank-specs-table" style="margin-top:20px;"><h3>مشخصات فنی محصول</h3><table style="width:100%; border-collapse:collapse; text-align:right;"><tbody>';
            foreach ( $specs_assoc as $sKey => $sVal ) {
                $table_html .= '<tr style="border-bottom:1px solid #e2e8f0;"><th style="padding:8px; width:35%; background:#f1f5f9;">' . esc_html( $sKey ) . '</th><td style="padding:8px;">' . esc_html( $sVal ) . '</td></tr>';
            }
            $table_html .= '</tbody></table></div>';
            $desc .= "\n\n" . $table_html;
        }

        // Mutually link related article in product description
        if ( $related_post_id > 0 ) {
            $rel_post = get_post( $related_post_id );
            if ( $rel_post && strpos( $desc, 'kavoshrank-related-article-link' ) === false ) {
                $rel_url = get_permalink( $related_post_id );
                $guide_box = '<div class="kavoshrank-related-article-link" style="margin-top:20px; padding:12px; background:#eff6ff; border-radius:8px; border:1px solid #bfdbfe;"><p style="margin:0;">راهنمای فنی مرتبط: <a href="' . esc_url( $rel_url ) . '" title="' . esc_attr( $rel_post->post_title ) . '" style="color:#1d4ed8; font-weight:bold;">' . esc_html( $rel_post->post_title ) . '</a></p></div>';
                $desc .= "\n\n" . $guide_box;
            }
        }

        if ( ! $product ) {
            $product = new WC_Product_Simple();
        }

        try {
            $product->set_name( $name );
            if ( ! empty( $slug ) ) {
                $product->set_slug( $slug );
            }
            if ( '' !== $desc || ! $self_id ) {
                $product->set_description( $desc );
            }
            if ( '' !== $short_desc || ! $self_id ) {
                $product->set_short_description( $short_desc );
            }
            if ( '' !== $sku ) {
                $product->set_sku( $sku );
            }
            $product->set_regular_price( $price );
            $product->set_sale_price( $sale_price );
            $product->set_status( $status );
            if ( $category_id > 0 ) {
                $product->set_category_ids( array( $category_id ) );
            }

            $product_id = $product->save();
        } catch ( WC_Data_Exception $e ) {
            $is_sku_error = false !== strpos( $e->getErrorCode(), 'sku' );
            return new WP_REST_Response( array(
                'error' => $e->getMessage(),
                'code'  => $is_sku_error ? 'duplicate_sku' : $e->getErrorCode(),
            ), $is_sku_error ? 409 : 400 );
        }

        if ( ! $product_id || is_wp_error( $product_id ) ) {
            return new WP_REST_Response( array( 'error' => 'Failed to save product to WooCommerce' ), 500 );
        }
        $sku = $product->get_sku();

        // Product images go through the hardened media handler (size,
        // extension, real-bytes MIME, WebP); failures do not undo the product.
        $image_urls = array();
        if ( ! empty( $params['images'] ) && is_array( $params['images'] ) ) {
            $image_urls = $params['images'];
        }
        if ( ! empty( $params['image_url'] ) ) {
            $image_urls[] = $params['image_url'];
        }
        $image_urls   = array_slice( array_values( array_unique( array_filter( array_map( 'strval', $image_urls ) ) ) ), 0, self::MAX_IMAGES );
        $image_ids    = array();
        $image_errors = array();
        if ( ! empty( $image_urls ) && class_exists( 'KavoshRank_Media_REST_Handler' ) ) {
            foreach ( $image_urls as $image_url ) {
                $attach_id = KavoshRank_Media_REST_Handler::sideload_image_url( $image_url );
                if ( is_wp_error( $attach_id ) ) {
                    $image_errors[] = array( 'url' => esc_url_raw( $image_url ), 'error' => $attach_id->get_error_message() );
                    continue;
                }
                wp_update_post( array( 'ID' => $attach_id, 'post_parent' => $product_id ) );
                $image_ids[] = $attach_id;
            }
            if ( ! empty( $image_ids ) ) {
                $product->set_image_id( $image_ids[0] );
                if ( count( $image_ids ) > 1 ) {
                    $product->set_gallery_image_ids( array_slice( $image_ids, 1 ) );
                }
                $product->save();
            }
        }

        // Meta tags & Yoast SEO
        if ( ! empty( $focus_kw ) ) {
            update_post_meta( $product_id, '_yoast_wpseo_focuskw', $focus_kw );
        }
        if ( ! empty( $meta_title ) ) {
            update_post_meta( $product_id, '_yoast_wpseo_title', $meta_title );
        }
        if ( ! empty( $meta_desc ) ) {
            update_post_meta( $product_id, '_yoast_wpseo_metadesc', $meta_desc );
        }

        if ( ! empty( $specs_assoc ) ) {
            update_post_meta( $product_id, '_kavoshrank_product_specs', $specs_assoc );
            update_post_meta( $product_id, '_tsw_product_specs', $specs_assoc );
        }

        // Catalog mode flags (only set on request; a priced product clears them)
        if ( $catalog_mode ) {
            update_post_meta( $product_id, '_kavoshrank_catalog_mode', 'yes' );
            if ( ! empty( $button_label ) ) {
                update_post_meta( $product_id, '_kavoshrank_button_label', $button_label );
            }
        } else {
            delete_post_meta( $product_id, '_kavoshrank_catalog_mode' );
            delete_post_meta( $product_id, '_kavoshrank_button_label' );
        }

        // Bidirectional Post-Product linking
        if ( $related_post_id > 0 ) {
            update_post_meta( $related_post_id, '_kavoshrank_related_product_id', $product_id );
            update_post_meta( $product_id, '_kavoshrank_related_post_id', $related_post_id );
            update_post_meta( $related_post_id, '_tsw_related_product_id', $product_id );
            update_post_meta( $product_id, '_tsw_related_post_id', $related_post_id );

            if ( $auto_link ) {
                $parent_post = get_post( $related_post_id );
                if ( $parent_post ) {
                    $prod_url = get_permalink( $product_id );
                    $prod_box = "\n\n" . '<div class="kavoshrank-product-box" style="margin-top:24px; padding:16px; background:#f0fdf4; border-radius:12px; border:1px solid #bbf7d0;"><p style="margin:0; font-size:14px;">مشاهده و خرید محصول: <a href="' . esc_url( $prod_url ) . '" title="' . esc_attr( $name ) . '" style="color:#15803d; font-weight:bold;">' . esc_html( $name ) . '</a></p></div>';
                    if ( strpos( $parent_post->post_content, $prod_url ) === false ) {
                        wp_update_post( array(
                            'ID'           => $related_post_id,
                            'post_content' => $parent_post->post_content . $prod_box,
                        ) );
                    }
                }
            }
        }

        return new WP_REST_Response( array(
            'status'         => 'success',
            'product_id'     => $product_id,
            'created'        => ! $self_id,
            'product_status' => $status,
            'sku'            => $sku,
            'name'           => $name,
            'category_id'    => $category_id,
            'image_ids'      => $image_ids,
            'image_errors'   => $image_errors,
            'url'            => get_permalink( $product_id ),
        ), 200 );
    }
}

// Auto-initialize filters
add_action( 'init', array( 'KavoshRank_Product_REST_Handler', 'init' ) );
