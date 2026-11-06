<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Categories_REST_Handler {

    /**
     * Get all categories with detailed hierarchy, description, count, and thumbnail
     */
    public static function handle_get_categories( WP_REST_Request $request ) {
        $taxonomies = array( 'category' );
        if ( taxonomy_exists( 'product_cat' ) ) {
            $taxonomies[] = 'product_cat';
        }
        $categories = get_terms( array(
            'taxonomy'   => $taxonomies,
            'hide_empty' => false,
        ) );

        $cats_list = array();
        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
            foreach ( $categories as $cat ) {
                $image_id  = get_term_meta( $cat->term_id, '_kavoshrank_category_image_id', true );
                $image_url = get_term_meta( $cat->term_id, '_kavoshrank_category_image_url', true );
                if ( ! $image_url && $image_id ) {
                    $image_url = wp_get_attachment_url( $image_id );
                }

                $parent_name = '';
                if ( $cat->parent > 0 ) {
                    $parent_term = get_term( $cat->parent, $cat->taxonomy );
                    if ( $parent_term && ! is_wp_error( $parent_term ) ) {
                        $parent_name = $parent_term->name;
                    }
                }

                $cats_list[] = array(
                    'id'          => $cat->term_id,
                    'name'        => $cat->name,
                    'slug'        => $cat->slug,
                    'taxonomy'    => $cat->taxonomy,
                    'description' => $cat->description,
                    'parent'      => $cat->parent,
                    'parent_name' => $parent_name,
                    'count'       => $cat->count,
                    'image_url'   => $image_url ? (string) $image_url : '',
                    'image_id'    => $image_id ? absint( $image_id ) : 0,
                );
            }
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'site_name'  => get_bloginfo( 'name' ),
            'categories' => $cats_list,
        ), 200 );
    }

    /**
     * Create or update category with parent, description, and thumbnail image
     */
    public static function handle_create_or_update_category( WP_REST_Request $request ) {
        $name        = sanitize_text_field( $request->get_param( 'name' ) );
        $slug        = sanitize_title( $request->get_param( 'slug' ) );
        $description = wp_kses_post( $request->get_param( 'description' ) );
        $parent_id   = absint( $request->get_param( 'parent_id' ) );
        $parent_slug = sanitize_title( $request->get_param( 'parent_slug' ) );
        $parent_name = sanitize_text_field( $request->get_param( 'parent_name' ) );
        $image_url   = esc_url_raw( $request->get_param( 'image_url' ) );
        $image_b64   = $request->get_param( 'image_b64' );

        if ( empty( $name ) ) {
            return new WP_REST_Response( array(
                'error' => 'Category name is required',
            ), 400 );
        }

        // Resolve parent if provided by name or slug
        if ( $parent_id <= 0 ) {
            if ( ! empty( $parent_slug ) ) {
                $p_term = get_term_by( 'slug', $parent_slug, 'category' );
                if ( $p_term && ! is_wp_error( $p_term ) ) {
                    $parent_id = $p_term->term_id;
                }
            } elseif ( ! empty( $parent_name ) ) {
                $p_term = get_term_by( 'name', $parent_name, 'category' );
                if ( $p_term && ! is_wp_error( $p_term ) ) {
                    $parent_id = $p_term->term_id;
                }
            }
        }

        // Check if category already exists by name or slug
        $existing = get_term_by( 'name', $name, 'category' );
        if ( ! $existing && ! empty( $slug ) ) {
            $existing = get_term_by( 'slug', $slug, 'category' );
        }

        $term_id = 0;
        $created = false;

        if ( $existing && ! is_wp_error( $existing ) ) {
            $term_id = $existing->term_id;
            $update_args = array();
            if ( ! empty( $description ) ) {
                $update_args['description'] = $description;
            }
            if ( $parent_id >= 0 ) {
                $update_args['parent'] = $parent_id;
            }
            if ( ! empty( $update_args ) ) {
                wp_update_term( $term_id, 'category', $update_args );
            }
        } else {
            $insert_args = array(
                'description' => $description,
                'parent'      => $parent_id,
            );
            if ( ! empty( $slug ) ) {
                $insert_args['slug'] = $slug;
            }
            $result = wp_insert_term( $name, 'category', $insert_args );
            if ( is_wp_error( $result ) ) {
                return new WP_REST_Response( array(
                    'error' => $result->get_error_message(),
                ), 400 );
            }
            $term_id = $result['term_id'];
            $created = true;
        }

        // Handle category image if provided
        $attached_image_url = '';
        $attached_image_id  = 0;

        if ( ! empty( $image_b64 ) ) {
            $upload_dir = wp_upload_dir();
            $filename   = sanitize_file_name( 'category-' . ( $slug ? $slug : 'img' ) . '-' . time() . '.webp' );
            $file_path  = $upload_dir['path'] . '/' . wp_unique_filename( $upload_dir['path'], $filename );
            $decoded    = base64_decode( $image_b64 );
            if ( $decoded ) {
                file_put_contents( $file_path, $decoded );
                if ( file_exists( $file_path ) && filesize( $file_path ) > 0 ) {
                    $file_type = wp_check_filetype( $filename, null );
                    $attachment = array(
                        'post_mime_type' => $file_type['type'] ? $file_type['type'] : 'image/webp',
                        'post_title'     => $name,
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    );
                    $attach_id = wp_insert_attachment( $attachment, $file_path );
                    if ( ! is_wp_error( $attach_id ) ) {
                        require_once( ABSPATH . 'wp-admin/includes/image.php' );
                        $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
                        wp_update_attachment_metadata( $attach_id, $attach_data );
                        $attached_image_id  = $attach_id;
                        $attached_image_url = wp_get_attachment_url( $attach_id );
                    }
                }
            }
        } elseif ( ! empty( $image_url ) ) {
            $attached_image_url = $image_url;
        }

        if ( ! empty( $attached_image_url ) ) {
            update_term_meta( $term_id, '_kavoshrank_category_image_url', $attached_image_url );
            if ( $attached_image_id > 0 ) {
                update_term_meta( $term_id, '_kavoshrank_category_image_id', $attached_image_id );
            }
        }

        $term = get_term( $term_id, 'category' );

        return new WP_REST_Response( array(
            'success'     => true,
            'created'     => $created,
            'id'          => $term_id,
            'name'        => $term ? $term->name : $name,
            'slug'        => $term ? $term->slug : $slug,
            'description' => $term ? $term->description : $description,
            'parent'      => $term ? $term->parent : $parent_id,
            'image_url'   => $attached_image_url,
            'image_id'    => $attached_image_id,
            'message'     => $created ? 'دسته‌بندی با موفقیت در وردپرس ایجاد شد' : 'دسته‌بندی با موفقیت به‌روزرسانی شد',
        ), 200 );
    }

    /**
     * Change a category slug and redirect its old archive URL (PR-18.6).
     *
     * The slug optimizer used to register a redirect for the bare slug while
     * the slug itself never changed. This renames the term, then registers a
     * 301 from the old archive path to the new one through the redirects
     * engine, with both paths taken from get_term_link().
     */
    public static function handle_update_category_slug( WP_REST_Request $request ) {
        $params   = $request->get_json_params();
        $old_slug = sanitize_title( isset( $params['old_slug'] ) ? $params['old_slug'] : '' );
        $new_slug = sanitize_title( isset( $params['new_slug'] ) ? $params['new_slug'] : '' );
        $taxonomy = isset( $params['taxonomy'] ) && 'product_cat' === $params['taxonomy'] ? 'product_cat' : 'category';

        if ( '' === $old_slug || '' === $new_slug ) {
            return new WP_REST_Response( array( 'error' => 'old_slug and new_slug are required' ), 400 );
        }

        $term = get_term_by( 'slug', $old_slug, $taxonomy );
        if ( ! $term || is_wp_error( $term ) ) {
            return new WP_REST_Response( array( 'error' => 'category not found' ), 404 );
        }

        $old_link = get_term_link( $term );
        if ( $old_slug === $new_slug ) {
            return new WP_REST_Response( array(
                'success'  => true,
                'term_id'  => $term->term_id,
                'old_slug' => $old_slug,
                'new_slug' => $new_slug,
                'changed'  => false,
            ), 200 );
        }

        $result = wp_update_term( $term->term_id, $taxonomy, array( 'slug' => $new_slug ) );
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 400 );
        }

        $updated  = get_term( $term->term_id, $taxonomy );
        $new_link = get_term_link( $updated );
        $saved    = $updated && ! is_wp_error( $updated ) ? $updated->slug : $new_slug;

        $redirected = false;
        if ( is_string( $old_link ) && is_string( $new_link ) && $old_link !== $new_link ) {
            $redirect = new WP_REST_Request( 'POST' );
            $redirect->set_header( 'content-type', 'application/json' );
            $redirect->set_body( wp_json_encode( array(
                'source' => wp_parse_url( $old_link, PHP_URL_PATH ),
                'target' => wp_parse_url( $new_link, PHP_URL_PATH ),
                'type'   => 301,
            ) ) );
            $response   = KavoshRank_Redirect_REST_Handler::handle_register_redirect( $redirect );
            $redirected = $response instanceof WP_REST_Response && $response->get_status() < 300;
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'term_id'    => $term->term_id,
            'old_slug'   => $old_slug,
            'new_slug'   => $saved,
            'old_url'    => is_string( $old_link ) ? $old_link : '',
            'new_url'    => is_string( $new_link ) ? $new_link : '',
            'redirected' => $redirected,
            'changed'    => true,
        ), 200 );
    }
}
