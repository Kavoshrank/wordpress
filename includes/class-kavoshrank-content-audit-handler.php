<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Thin content audit and enrichment endpoints used by the Site Healer.
 *
 * The audit returns content signals only (word count, images, headings, product
 * specifications and gallery, empty categories). The enrichment endpoint snapshots
 * the current content before replacing it, so every rewrite can be rolled back.
 */
class KavoshRank_Content_Audit_REST_Handler {

    const BACKUP_META_KEY   = '_kavoshrank_pre_enrich_content';
    const ENRICHED_META_KEY = '_kavoshrank_enriched_at';

    private static function count_words( $html ) {
        $text = wp_strip_all_tags( (string) $html );

        return count( preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY ) );
    }

    private static function allowed_post_types() {
        $types = array( 'post' );
        if ( post_type_exists( 'product' ) ) {
            $types[] = 'product';
        }

        return $types;
    }

    /**
     * Callback for GET /wp-json/kavoshrank/v1/content-audit
     */
    public static function handle_get_audit( WP_REST_Request $request ) {
        $limit = absint( $request->get_param( 'limit' ) ?: 200 );
        $limit = min( max( $limit, 1 ), 500 );

        $posts = get_posts( array(
            'numberposts' => $limit,
            'post_type'   => self::allowed_post_types(),
            'post_status' => 'publish',
            'orderby'     => 'modified',
            'order'       => 'DESC',
        ) );

        $items = array();
        foreach ( $posts as $p ) {
            $content = (string) $p->post_content;
            $item    = array(
                'id'                 => $p->ID,
                'type'               => $p->post_type === 'product' ? 'product' : 'post',
                'title'              => $p->post_title,
                'url'                => get_permalink( $p->ID ),
                'word_count'         => self::count_words( $content ),
                'image_count'        => preg_match_all( '/<img\b[^>]*\bsrc=["\'][^"\']+["\']/i', $content ),
                'heading_count'      => preg_match_all( '/<h[2-3]\b/i', $content ),
                'has_featured_image' => has_post_thumbnail( $p->ID ),
                'modified_at'        => get_post_modified_time( 'c', true, $p->ID ),
                'enriched_at'        => (string) get_post_meta( $p->ID, self::ENRICHED_META_KEY, true ),
            );

            if ( $p->post_type === 'product' && function_exists( 'wc_get_product' ) ) {
                $product = wc_get_product( $p->ID );
                if ( $product ) {
                    $item['gallery_count']   = count( $product->get_gallery_image_ids() );
                    $item['attribute_count'] = count( $product->get_attributes() );
                }
            }

            $items[] = $item;
        }

        $categories = array();
        $taxonomies = array( 'category' );
        if ( taxonomy_exists( 'product_cat' ) ) {
            $taxonomies[] = 'product_cat';
        }
        $terms = get_terms( array(
            'taxonomy'   => $taxonomies,
            'hide_empty' => false,
        ) );
        if ( ! is_wp_error( $terms ) ) {
            $default_cat = (int) get_option( 'default_category' );
            foreach ( $terms as $term ) {
                if ( (int) $term->count > 0 || (int) $term->term_id === $default_cat ) {
                    continue;
                }
                $link         = get_term_link( $term );
                $categories[] = array(
                    'id'       => $term->term_id,
                    'name'     => $term->name,
                    'taxonomy' => $term->taxonomy,
                    'url'      => is_wp_error( $link ) ? '' : $link,
                    'count'    => (int) $term->count,
                );
            }
        }

        return new WP_REST_Response( array(
            'success'          => true,
            'items'            => $items,
            'empty_categories' => $categories,
        ), 200 );
    }

    /**
     * Callback for GET /wp-json/kavoshrank/v1/posts/{id}
     */
    public static function handle_get_post( WP_REST_Request $request ) {
        $post = get_post( absint( $request['id'] ) );
        if ( ! $post || ! in_array( $post->post_type, self::allowed_post_types(), true ) ) {
            return new WP_REST_Response( array( 'error' => 'Post not found' ), 404 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'post'    => array(
                'id'         => $post->ID,
                'type'       => $post->post_type === 'product' ? 'product' : 'post',
                'title'      => $post->post_title,
                'content'    => $post->post_content,
                'url'        => get_permalink( $post->ID ),
                'status'     => $post->post_status,
                'word_count' => self::count_words( $post->post_content ),
            ),
        ), 200 );
    }

    /**
     * Callback for POST /wp-json/kavoshrank/v1/posts/{id}/enrich
     *
     * Stores the current content in post meta (and a revision when the post type
     * supports revisions) before replacing it with the enriched content.
     */
    public static function handle_enrich_post( WP_REST_Request $request ) {
        $post = get_post( absint( $request['id'] ) );
        if ( ! $post || ! in_array( $post->post_type, self::allowed_post_types(), true ) ) {
            return new WP_REST_Response( array( 'error' => 'Post not found' ), 404 );
        }

        $params  = $request->get_json_params();
        $content = isset( $params['content'] ) ? (string) $params['content'] : '';

        KavoshRank_Post_REST_Handler::register_kses_allowed_html();
        $content = wp_kses_post( $content );
        if ( trim( wp_strip_all_tags( $content ) ) === '' ) {
            return new WP_REST_Response( array( 'error' => 'Enriched content is empty' ), 400 );
        }

        update_post_meta( $post->ID, self::BACKUP_META_KEY, wp_slash( $post->post_content ) );
        $revision_id = 0;
        if ( post_type_supports( $post->post_type, 'revisions' ) ) {
            $revision_id = (int) wp_save_post_revision( $post->ID );
        }

        $result = wp_update_post( array(
            'ID'           => $post->ID,
            'post_content' => wp_slash( $content ),
        ), true );
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 500 );
        }

        $enriched_at = current_time( 'c', true );
        update_post_meta( $post->ID, self::ENRICHED_META_KEY, $enriched_at );

        return new WP_REST_Response( array(
            'success'     => true,
            'post_id'     => $post->ID,
            'revision_id' => $revision_id,
            'backup_meta' => self::BACKUP_META_KEY,
            'word_count'  => self::count_words( $content ),
            'url'         => get_permalink( $post->ID ),
            'enriched_at' => $enriched_at,
        ), 200 );
    }
}
