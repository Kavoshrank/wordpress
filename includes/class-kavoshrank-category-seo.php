<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * KavoshRank WooCommerce & WordPress Category SEO Studio
 * 
 * Injects top-of-archive engaging introductions, bottom-of-archive buying guides,
 * category FAQ accordions, and automated FAQPage JSON-LD schema.
 */
class KavoshRank_Category_SEO {

    public function init() {
        // WooCommerce Archive Hooks
        add_action( 'woocommerce_archive_description', array( $this, 'render_top_intro' ), 15 );
        add_action( 'woocommerce_after_shop_loop', array( $this, 'render_bottom_buying_guide' ), 20 );
        add_action( 'woocommerce_after_shop_loop', array( $this, 'render_category_faqs' ), 25 );

        // Standard WordPress Category Archives Fallback
        add_action( 'category_template', array( $this, 'register_archive_filters' ) );

        // Schema JSON-LD Head Injection
        add_action( 'wp_head', array( $this, 'inject_faq_schema_jsonld' ), 30 );
    }

    public function register_archive_filters( $template ) {
        if ( is_category() || is_tax( 'product_cat' ) ) {
            add_filter( 'the_archive_description', array( $this, 'filter_archive_description' ) );
        }
        return $template;
    }

    /**
     * Filter standard archive description to prepend top intro if available
     */
    public function filter_archive_description( $description ) {
        if ( is_category() || is_tax( 'product_cat' ) ) {
            $term_id = get_queried_object_id();
            if ( $term_id ) {
                $intro = get_term_meta( $term_id, '_kavoshrank_category_intro', true );
                if ( ! empty( $intro ) ) {
                    return '<div class="kavoshrank-category-intro mb-6 leading-relaxed">' . wp_kses_post( $intro ) . '</div>' . $description;
                }
            }
        }
        return $description;
    }

    private function is_product_cat() {
        return function_exists( 'is_product_category' ) && is_product_category();
    }

    /**
     * Render Top Intro above product loop
     */
    public function render_top_intro() {
        if ( ! $this->is_product_cat() ) {
            return;
        }

        $term_id = get_queried_object_id();
        if ( ! $term_id ) return;

        $intro = get_term_meta( $term_id, '_kavoshrank_category_intro', true );
        if ( ! empty( $intro ) ) {
            echo '<div class="kavoshrank-category-seo-intro my-4 p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 text-sm text-slate-700 leading-relaxed">';
            echo wp_kses_post( $intro );
            echo '</div>';
        }
    }

    /**
     * Render Bottom Comprehensive Buying Guide below product loop
     */
    public function render_bottom_buying_guide() {
        if ( ! $this->is_product_cat() && ! is_category() ) {
            return;
        }

        $term_id = get_queried_object_id();
        if ( ! $term_id ) return;

        $guide = get_term_meta( $term_id, '_kavoshrank_category_guide', true );
        if ( ! empty( $guide ) ) {
            echo '<div class="kavoshrank-category-buying-guide mt-12 p-6 sm:p-8 rounded-2xl bg-white border border-slate-200/90 shadow-sm text-slate-800 space-y-4">';
            echo '<div class="prose prose-slate max-w-none text-sm leading-relaxed">';
            echo wp_kses_post( $guide );
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * Render Category FAQ Accordions below the buying guide
     */
    public function render_category_faqs() {
        if ( ! $this->is_product_cat() && ! is_category() ) {
            return;
        }

        $term_id = get_queried_object_id();
        if ( ! $term_id ) return;

        $faqs_raw = get_term_meta( $term_id, '_kavoshrank_category_faqs', true );
        if ( empty( $faqs_raw ) ) return;

        $faqs = is_array( $faqs_raw ) ? $faqs_raw : json_decode( $faqs_raw, true );
        if ( empty( $faqs ) || ! is_array( $faqs ) ) return;

        echo '<div class="kavoshrank-category-faqs mt-8 space-y-3">';
        echo '<h3 class="text-lg font-bold text-slate-900 mb-4">سوالات متداول کاربران در این دسته‌بندی</h3>';
        
        foreach ( $faqs as $faq ) {
            if ( empty( $faq['q'] ) || empty( $faq['a'] ) ) continue;
            echo '<details class="group p-4 rounded-xl bg-slate-50 border border-slate-200/80 transition-all [&_summary::-webkit-details-marker]:hidden">';
            echo '<summary class="flex cursor-pointer items-center justify-between gap-1.5 font-semibold text-slate-800 text-sm list-none select-none">';
            echo '<span>' . esc_html( $faq['q'] ) . '</span>';
            echo '<span class="text-xs font-mono text-slate-400 group-open:rotate-180 transition-transform duration-200">▼</span>';
            echo '</summary>';
            echo '<div class="mt-3 pt-3 border-t border-slate-200/60 text-xs text-slate-600 leading-relaxed">';
            echo wp_kses_post( $faq['a'] );
            echo '</div>';
            echo '</details>';
        }

        echo '</div>';
    }

    /**
     * Inject FAQPage Schema JSON-LD in head
     */
    public function inject_faq_schema_jsonld() {
        if ( ! $this->is_product_cat() && ! is_category() ) {
            return;
        }

        $term_id = get_queried_object_id();
        if ( ! $term_id ) return;

        $faqs_raw = get_term_meta( $term_id, '_kavoshrank_category_faqs', true );
        if ( empty( $faqs_raw ) ) return;

        $faqs = is_array( $faqs_raw ) ? $faqs_raw : json_decode( $faqs_raw, true );
        if ( empty( $faqs ) || ! is_array( $faqs ) ) return;

        $main_entities = array();
        foreach ( $faqs as $faq ) {
            if ( empty( $faq['q'] ) || empty( $faq['a'] ) ) continue;
            $main_entities[] = array(
                '@type'          => 'Question',
                'name'           => wp_strip_all_tags( $faq['q'] ),
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text'  => wp_strip_all_tags( $faq['a'] ),
                ),
            );
        }

        if ( empty( $main_entities ) ) return;

        $schema = array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $main_entities,
        );

        echo "\n<!-- KavoshRank Category FAQ Schema -->\n";
        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "</script>\n";
    }

    /**
     * REST Handler: Get Category SEO Metadata
     */
    public static function handle_get_category_seo( WP_REST_Request $request ) {
        $term_id = (int) $request->get_param( 'term_id' );
        if ( ! $term_id ) {
            return new WP_Error( 'invalid_term_id', 'شناسه دسته‌بندی الزامی است', array( 'status' => 400 ) );
        }

        $term = get_term( $term_id );
        if ( ! $term || is_wp_error( $term ) ) {
            return new WP_Error( 'not_found', 'دسته‌بندی مورد نظر یافت نشد', array( 'status' => 404 ) );
        }

        $intro = get_term_meta( $term_id, '_kavoshrank_category_intro', true );
        $guide = get_term_meta( $term_id, '_kavoshrank_category_guide', true );
        $faqs_raw = get_term_meta( $term_id, '_kavoshrank_category_faqs', true );
        $faqs = is_array( $faqs_raw ) ? $faqs_raw : ( json_decode( $faqs_raw, true ) ?: array() );

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array(
                'term_id'     => $term_id,
                'name'        => $term->name,
                'slug'        => $term->slug,
                'taxonomy'    => $term->taxonomy,
                'intro_html'  => $intro ?: '',
                'guide_html'  => $guide ?: '',
                'faqs'        => $faqs,
                'term_link'   => get_term_link( $term ),
            ),
        ), 200 );
    }

    /**
     * REST Handler: Update Category SEO Metadata
     */
    public static function handle_update_category_seo( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $term_id = isset( $params['term_id'] ) ? (int) $params['term_id'] : 0;

        if ( ! $term_id ) {
            return new WP_Error( 'invalid_term_id', 'شناسه دسته‌بندی الزامی است', array( 'status' => 400 ) );
        }

        $term = get_term( $term_id );
        if ( ! $term || is_wp_error( $term ) ) {
            return new WP_Error( 'not_found', 'دسته‌بندی مورد نظر یافت نشد', array( 'status' => 404 ) );
        }

        if ( isset( $params['intro_html'] ) ) {
            update_term_meta( $term_id, '_kavoshrank_category_intro', wp_kses_post( $params['intro_html'] ) );
        }

        if ( isset( $params['guide_html'] ) ) {
            update_term_meta( $term_id, '_kavoshrank_category_guide', wp_kses_post( $params['guide_html'] ) );
        }

        if ( isset( $params['faqs'] ) && is_array( $params['faqs'] ) ) {
            $clean_faqs = array();
            foreach ( $params['faqs'] as $faq ) {
                if ( ! empty( $faq['q'] ) && ! empty( $faq['a'] ) ) {
                    $clean_faqs[] = array(
                        'q' => sanitize_text_field( $faq['q'] ),
                        'a' => wp_kses_post( $faq['a'] ),
                    );
                }
            }
            update_term_meta( $term_id, '_kavoshrank_category_faqs', $clean_faqs );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'محتوای سئو، راهنمای خرید و اسکیما برای دسته‌بندی با موفقیت ذخیره و منتشر شد.',
            'data'    => array(
                'term_id'   => $term_id,
                'name'      => $term->name,
                'term_link' => get_term_link( $term ),
            ),
        ), 200 );
    }
}
