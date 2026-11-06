<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KavoshRank Unified Semantic Knowledge Graph Schema Engine
 * Emits an interconnected, standards-compliant JSON-LD `@graph` architecture with canonical `@id` identifiers.
 */
class KavoshRank_Schema {

    public function init() {
        // Opt-in: the schema engine stays off until the store owner enables it
        if ( get_option( 'kavoshrank_enable_schema', '0' ) !== '1' ) {
            return;
        }

        // Suppress conflicting default schemas from WooCommerce only if explicitly set to replace
        $override_mode = get_option( 'kavoshrank_override_wc_schema', '0' );
        if ( $override_mode === 'replace' || $override_mode === '1' ) {
            add_filter( 'woocommerce_structured_data_product', '__return_empty_array', 99 );
        }

        add_action( 'wp_head', array( $this, 'render_schema_jsonld' ), 2 );
    }

    /**
     * Render Unified `@graph` Schema.org JSON-LD in <head>
     */
    public function render_schema_jsonld() {
        if ( is_admin() || is_feed() ) {
            return;
        }

        $graph = $this->build_unified_graph();
        if ( empty( $graph ) ) {
            return;
        }

        $payload = array(
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        );

        echo "\n" . '<!-- KavoshRank Semantic Knowledge Graph Schema -->' . "\n";
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        echo "\n" . '</script>' . "\n";
        echo '<!-- /KavoshRank Schema -->' . "\n\n";
    }

    /**
     * Build all interlinked nodes for the unified `@graph`
     */
    public function build_unified_graph() {
        $home_url   = trailingslashit( home_url() );
        $site_name  = get_bloginfo( 'name' );
        $graph      = array();

        // Canonical ID references
        $org_id     = $home_url . '#organization';
        $site_id    = $home_url . '#website';
        $logo_id    = $home_url . '#logo';

        // 1. Organization Node (Entity Root)
        $org_node = array(
            '@type'       => 'Organization',
            '@id'         => $org_id,
            'name'        => $site_name,
            'url'         => $home_url,
            'logo'        => array(
                '@type'      => 'ImageObject',
                '@id'        => $logo_id,
                'url'        => $home_url . 'wp-content/themes/kavoshrank/assets/logo.png',
                'caption'    => $site_name,
                'inLanguage' => 'fa-IR',
            ),
            'image'       => array(
                '@id' => $logo_id,
            ),
        );
        $graph[] = $org_node;

        // 2. WebSite Node with SearchAction
        $site_node = array(
            '@type'           => 'WebSite',
            '@id'             => $site_id,
            'url'             => $home_url,
            'name'            => $site_name,
            'description'     => get_bloginfo( 'description' ),
            'publisher'       => array(
                '@id' => $org_id,
            ),
            'inLanguage'      => 'fa-IR',
            'potentialAction' => array(
                array(
                    '@type'       => 'SearchAction',
                    'target'      => array(
                        '@type'       => 'EntryPoint',
                        'urlTemplate' => $home_url . '?s={search_term_string}',
                    ),
                    'query-input' => 'required name=search_term_string',
                ),
            ),
        );
        $graph[] = $site_node;

        if ( is_singular() ) {
            $post = get_post();
            if ( ! $post ) {
                return $graph;
            }

            $permalink     = get_permalink( $post->ID );
            $page_id       = $permalink . '#webpage';
            $image_id      = $permalink . '#primaryimage';
            $breadcrumb_id = $permalink . '#breadcrumb';
            $article_id    = $permalink . '#article';
            $product_id    = $permalink . '#product';
            $faq_id        = $permalink . '#faq';
            $video_id      = $permalink . '#video';

            $thumb_id  = get_post_thumbnail_id( $post->ID );
            $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'full' ) : '';

            // 3. Primary Image Node (if available)
            if ( ! empty( $thumb_url ) ) {
                $graph[] = array(
                    '@type'      => 'ImageObject',
                    '@id'        => $image_id,
                    'url'        => $thumb_url,
                    'contentUrl' => $thumb_url,
                    'caption'    => get_the_title( $post->ID ),
                    'inLanguage' => 'fa-IR',
                );
            }

            // 4. BreadcrumbList Node
            $breadcrumb_items = $this->build_breadcrumbs( $post, $home_url );
            if ( ! empty( $breadcrumb_items ) ) {
                $graph[] = array(
                    '@type'           => 'BreadcrumbList',
                    '@id'             => $breadcrumb_id,
                    'itemListElement' => $breadcrumb_items,
                );
            }

            // 5. WebPage Node
            $webpage_node = array(
                '@type'         => 'WebPage',
                '@id'           => $page_id,
                'url'           => $permalink,
                'name'          => get_the_title( $post->ID ),
                'isPartOf'      => array(
                    '@id' => $site_id,
                ),
                'datePublished' => get_the_date( 'c', $post->ID ),
                'dateModified'  => get_the_modified_date( 'c', $post->ID ),
                'description'   => get_the_excerpt( $post->ID ) ?: wp_strip_all_tags( $post->post_title ),
                'breadcrumb'    => array(
                    '@id' => $breadcrumb_id,
                ),
                'about'         => array(
                    '@id' => $org_id,
                ),
                'inLanguage'    => 'fa-IR',
            );
            if ( ! empty( $thumb_url ) ) {
                $webpage_node['primaryImageOfPage'] = array( '@id' => $image_id );
            }
            $graph[] = $webpage_node;

            // 6. Author Person Node
            $author_name = get_the_author_meta( 'display_name', $post->post_author ) ?: 'کارشناس تخصصی سئو و محتوا';
            $author_slug = sanitize_title( $author_name );
            $author_id   = $home_url . '#/schema/person/' . $author_slug;

            $graph[] = array(
                '@type'       => 'Person',
                '@id'         => $author_id,
                'name'        => $author_name,
                'jobTitle'    => 'نویسنده و متخصص حوزه دیجیتال',
                'worksFor'    => array(
                    '@id' => $org_id,
                ),
            );

            // 7. Singular Specific Nodes
            if ( function_exists( 'is_product' ) && is_product() ) {
                // Product Node
                $prod_node = $this->build_product_node( $post, $product_id, $page_id, $org_id, $thumb_url );
                if ( ! empty( $prod_node ) ) {
                    $graph[] = $prod_node;
                }
            } elseif ( is_single() ) {
                // TechArticle Node
                $art_node = array(
                    '@type'            => 'TechArticle',
                    '@id'              => $article_id,
                    'isPartOf'         => array(
                        '@id' => $page_id,
                    ),
                    'headline'         => get_the_title( $post->ID ),
                    'datePublished'    => get_the_date( 'c', $post->ID ),
                    'dateModified'     => get_the_modified_date( 'c', $post->ID ),
                    'mainEntityOfPage' => array(
                        '@id' => $page_id,
                    ),
                    'wordCount'        => str_word_count( wp_strip_all_tags( $post->post_content ) ),
                    'author'           => array(
                        '@id' => $author_id,
                    ),
                    'publisher'        => array(
                        '@id' => $org_id,
                    ),
                    'reviewedBy'       => array(
                        '@type'    => 'Organization',
                        'name'     => get_option( 'kavoshrank_eeat_reviewer_title', 'تیم تحریریه و کارشناسان' ),
                        'jobTitle' => 'ارزیاب کیفیت و سئو محتوا',
                    ),
                    'description'      => get_the_excerpt( $post->ID ),
                    'inLanguage'       => 'fa-IR',
                );

                if ( ! empty( $thumb_url ) ) {
                    $art_node['image'] = array( '@id' => $image_id );
                }

                // Category Section
                $cats = get_the_category( $post->ID );
                if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
                    $art_node['articleSection'] = $cats[0]->name;
                }

                $graph[] = $art_node;

                // VideoObject (Aparat / YouTube if found in content)
                $video_node = $this->extract_video_node( $post, $video_id );
                if ( ! empty( $video_node ) ) {
                    $graph[] = $video_node;
                }

                // FAQPage / Questions if found in content
                $faq_node = $this->build_faq_node( $post, $faq_id );
                if ( ! empty( $faq_node ) ) {
                    $graph[] = $faq_node;
                }
            }
        }

        return $graph;
    }

    /**
     * Build Breadcrumb items
     */
    private function build_breadcrumbs( $post, $home_url ) {
        $items = array();
        $pos   = 1;

        // Home
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => 'خانه',
            'item'     => $home_url,
        );

        // Category or Taxonomy
        if ( function_exists( 'is_product' ) && is_product() ) {
            $terms = get_the_terms( $post->ID, 'product_cat' );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                $term = array_shift( $terms );
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $pos++,
                    'name'     => $term->name,
                    'item'     => get_term_link( $term ),
                );
            }
        } else {
            $cats = get_the_category( $post->ID );
            if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
                $cat = array_shift( $cats );
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $pos++,
                    'name'     => $cat->name,
                    'item'     => get_category_link( $cat ),
                );
            }
        }

        // Current Page
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => get_the_title( $post->ID ),
            'item'     => get_permalink( $post->ID ),
        );

        return $items;
    }

    /**
     * Build Product Node
     */
    private function build_product_node( $post, $product_id, $page_id, $org_id, $thumb_url ) {
        global $product;
        if ( ! is_a( $product, 'WC_Product' ) ) {
            $product = wc_get_product( $post->ID );
        }
        if ( ! $product ) {
            return null;
        }

        $raw_price = $product->get_price();
        $price = is_numeric( $raw_price ) ? (float) $raw_price : 0.0;

        $sku = $product->get_sku();
        if ( empty( $sku ) ) {
            $sku = 'KR-' . strtoupper( substr( md5( (string) $product->get_id() ), 0, 8 ) );
        }

        $wc_currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
        list( $currency, $price ) = self::to_iso_currency( $wc_currency, $price );

        $offer = array(
            '@type'           => 'Offer',
            'url'             => get_permalink( $product->get_id() ),
            'priceCurrency'   => $currency,
            'price'           => (string) $price,
            'priceValidUntil' => gmdate( 'Y-12-31', strtotime( '+1 year' ) ),
            'itemCondition'   => 'https://schema.org/NewCondition',
            'availability'    => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'seller'          => array(
                '@id' => $org_id,
            ),
        );

        // Return policy and shipping come only from values the store owner entered.
        $country = self::store_country();

        $return_policy = self::build_return_policy(
            get_option( 'kavoshrank_return_policy_days', '' ),
            get_option( 'kavoshrank_return_policy_fees', '' ),
            $country
        );
        if ( ! empty( $return_policy ) ) {
            $offer['hasMerchantReturnPolicy'] = $return_policy;
        }

        $shipping = self::build_shipping_details(
            get_option( 'kavoshrank_shipping_rate', '' ),
            get_option( 'kavoshrank_shipping_handling_days', '' ),
            get_option( 'kavoshrank_shipping_transit_days', '' ),
            $wc_currency,
            $country
        );
        if ( ! empty( $shipping ) ) {
            $offer['shippingDetails'] = $shipping;
        }

        $product_node = array(
            '@type'            => 'Product',
            '@id'              => $product_id,
            'name'             => $product->get_name(),
            'image'            => $thumb_url ? array( $thumb_url ) : array(),
            'description'      => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
            'sku'              => $sku,
            'mpn'              => $sku,
            'brand'            => array(
                '@id' => $org_id,
            ),
            'mainEntityOfPage' => array(
                '@id' => $page_id,
            ),
            'offers'           => $offer,
        );

        // Same source and condition as WooCommerce's own product schema.
        $ratings_enabled = ! function_exists( 'wc_review_ratings_enabled' ) || wc_review_ratings_enabled();
        $aggregate       = $ratings_enabled ? self::build_aggregate_rating(
            $product->get_rating_count(),
            $product->get_average_rating(),
            $product->get_review_count()
        ) : null;
        if ( ! empty( $aggregate ) ) {
            $product_node['aggregateRating'] = $aggregate;
        }

        return $product_node;
    }

    /**
     * Map a WooCommerce currency code to ISO 4217.
     * Toman (IRT) is not an ISO code: it becomes IRR with the amount multiplied by 10.
     *
     * @return array { 0: currency code, 1: amount }
     */
    public static function to_iso_currency( $wc_currency, $amount ) {
        $code = strtoupper( trim( (string) $wc_currency ) );
        if ( in_array( $code, array( 'IRT', 'TOMAN', 'TMN' ), true ) ) {
            return array( 'IRR', $amount * 10 );
        }
        if ( '' === $code ) {
            return array( 'IRR', $amount );
        }
        return array( $code, $amount );
    }

    /**
     * AggregateRating from real WooCommerce ratings only; null when the product has none.
     */
    public static function build_aggregate_rating( $rating_count, $average_rating, $review_count ) {
        $rating_count = (int) $rating_count;
        if ( $rating_count <= 0 ) {
            return null;
        }

        $rating = array(
            '@type'       => 'AggregateRating',
            'ratingValue' => (string) round( (float) $average_rating, 2 ),
            'ratingCount' => $rating_count,
            'bestRating'  => '5',
            'worstRating' => '1',
        );
        if ( (int) $review_count > 0 ) {
            $rating['reviewCount'] = (int) $review_count;
        }

        return $rating;
    }

    /**
     * MerchantReturnPolicy from the configured return window; null when no window is set.
     *
     * @param string $fees 'free', 'customer' or '' (not stated).
     */
    public static function build_return_policy( $days, $fees, $country ) {
        $days = is_numeric( $days ) ? (int) $days : 0;
        if ( $days <= 0 ) {
            return null;
        }

        $policy = array(
            '@type'                => 'MerchantReturnPolicy',
            'applicableCountry'    => $country,
            'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'merchantReturnDays'   => $days,
        );
        if ( 'free' === $fees ) {
            $policy['returnFees'] = 'https://schema.org/FreeReturn';
        } elseif ( 'customer' === $fees ) {
            $policy['returnFees'] = 'https://schema.org/ReturnFeesCustomerResponsibility';
        }

        return $policy;
    }

    /**
     * OfferShippingDetails from the configured flat rate (in the store currency); null when no rate is set.
     * Delivery time is added only when a transit time is configured.
     */
    public static function build_shipping_details( $rate, $handling_days, $transit_days, $wc_currency, $country ) {
        $rate = trim( (string) $rate );
        if ( '' === $rate || ! is_numeric( $rate ) || (float) $rate < 0 ) {
            return null;
        }
        list( $currency, $amount ) = self::to_iso_currency( $wc_currency, (float) $rate );

        $details = array(
            '@type'               => 'OfferShippingDetails',
            'shippingRate'        => array(
                '@type'    => 'MonetaryAmount',
                'value'    => (string) $amount,
                'currency' => $currency,
            ),
            'shippingDestination' => array(
                '@type'          => 'DefinedRegion',
                'addressCountry' => $country,
            ),
        );

        $transit = is_numeric( $transit_days ) ? (int) $transit_days : 0;
        if ( $transit > 0 ) {
            $handling = is_numeric( $handling_days ) ? max( 0, (int) $handling_days ) : 0;
            $details['deliveryTime'] = array(
                '@type'        => 'ShippingDeliveryTime',
                'handlingTime' => array(
                    '@type'    => 'QuantitativeValue',
                    'minValue' => $handling,
                    'maxValue' => $handling,
                    'unitCode' => 'DAY',
                ),
                'transitTime'  => array(
                    '@type'    => 'QuantitativeValue',
                    'minValue' => $transit,
                    'maxValue' => $transit,
                    'unitCode' => 'DAY',
                ),
            );
        }

        return $details;
    }

    /**
     * ISO country code of the WooCommerce store base location (for example "IR" from "IR:THR").
     */
    private static function store_country() {
        $base    = (string) get_option( 'woocommerce_default_country', '' );
        $country = strtoupper( strtok( $base, ':' ) ?: '' );
        return preg_match( '/^[A-Z]{2}$/', $country ) ? $country : 'IR';
    }

    /**
     * Extract VideoObject from embedded Aparat/YouTube iframes
     */
    private function extract_video_node( $post, $video_id ) {
        if ( empty( $post->post_content ) ) {
            return null;
        }

        // Match Aparat or YouTube embed iframe
        if ( preg_match( '/src=["\'](https?:\/\/(?:www\.)?(?:aparat\.com\/video\/video\/embed\/videohash\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)[^"\']*)["\']/i', $post->post_content, $match ) ) {
            $embed_url = $match[1];
            $thumb_url = get_the_post_thumbnail_url( $post->ID, 'full' ) ?: home_url( '/wp-content/themes/kavoshrank/assets/logo.png' );

            return array(
                '@type'        => 'VideoObject',
                '@id'          => $video_id,
                'name'         => get_the_title( $post->ID ),
                'description'  => get_the_excerpt( $post->ID ) ?: get_the_title( $post->ID ),
                'thumbnailUrl' => array( $thumb_url ),
                'uploadDate'   => get_the_date( 'c', $post->ID ),
                'embedUrl'     => $embed_url,
            );
        }

        return null;
    }

    /**
     * Build FAQPage / Questions Node from Content and Meta
     */
    private function build_faq_node( $post, $faq_id ) {
        $questions = array();

        // 1. Structured store FAQs stored in post/product meta
        $faq_meta = get_post_meta( $post->ID, '_kavoshrank_faqs', true );
        if ( ! empty( $faq_meta ) && is_array( $faq_meta ) ) {
            foreach ( $faq_meta as $item ) {
                $q = trim( wp_strip_all_tags( $item['question'] ?? '' ) );
                $a = trim( wp_strip_all_tags( $item['answer'] ?? '' ) );
                if ( ! empty( $q ) && ! empty( $a ) ) {
                    $questions[] = array(
                        '@type'          => 'Question',
                        'name'           => $q,
                        'acceptedAnswer' => array(
                            '@type' => 'Answer',
                            'text'  => $a,
                        ),
                    );
                }
            }
        }

        // 2. Headings and paragraphs in content
        if ( ! empty( $post->post_content ) && preg_match_all( '/<h3[^>]*>(.*?\?|.*؟)<\/h3>\s*<p[^>]*>(.*?)<\/p>/is', $post->post_content, $matches, PREG_SET_ORDER ) ) {
            foreach ( $matches as $m ) {
                $q = trim( wp_strip_all_tags( $m[1] ) );
                $a = trim( wp_strip_all_tags( $m[2] ) );
                if ( ! empty( $q ) && ! empty( $a ) ) {
                    $questions[] = array(
                        '@type'          => 'Question',
                        'name'           => $q,
                        'acceptedAnswer' => array(
                            '@type' => 'Answer',
                            'text'  => $a,
                        ),
                    );
                }
            }
        }

        if ( empty( $questions ) ) {
            return null;
        }

        return array(
            '@type'      => 'FAQPage',
            '@id'        => $faq_id,
            'mainEntity' => $questions,
        );
    }
}
