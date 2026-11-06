<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Page_Optimizer {

    public function init() {
        // Intercept upload to generate WebP automatically
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'generate_webp_on_upload' ), 10, 2 );

        // Frontend output WebP image tag rewriting
        if ( ! is_admin() && get_option( 'kavoshrank_enable_webp_rewrite', '1' ) === '1' ) {
            add_filter( 'the_content', array( $this, 'rewrite_images_to_webp' ), 99 );
            add_filter( 'post_thumbnail_html', array( $this, 'rewrite_images_to_webp' ), 99 );
        }
    }

    /**
     * Generate WebP versions for uploaded JPEG and PNG images using GD or Imagick
     */
    public function generate_webp_on_upload( $metadata, $attachment_id ) {
        $file = get_attached_file( $attachment_id );
        if ( ! $file || ! file_exists( $file ) ) {
            return $metadata;
        }

        $mime = get_post_mime_type( $attachment_id );
        if ( ! in_array( $mime, array( 'image/jpeg', 'image/jpg', 'image/png' ), true ) ) {
            return $metadata;
        }

        // Convert original full file
        $this->convert_file_to_webp( $file, $mime );

        // Convert intermediate sizes (thumbnails, medium, large, etc.)
        if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
            $upload_dir = wp_upload_dir();
            $base_dir   = trailingslashit( dirname( $file ) );

            foreach ( $metadata['sizes'] as $size_info ) {
                if ( ! empty( $size_info['file'] ) ) {
                    $sub_file = $base_dir . $size_info['file'];
                    if ( file_exists( $sub_file ) ) {
                        $this->convert_file_to_webp( $sub_file, $size_info['mime-type'] ?? $mime );
                    }
                }
            }
        }

        return $metadata;
    }

    /**
     * Convert a single image file on disk to .webp
     */
    public function convert_file_to_webp( $file_path, $mime = 'image/jpeg', $quality = 82 ) {
        $webp_path = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file_path );
        if ( file_exists( $webp_path ) ) {
            return $webp_path;
        }

        if ( function_exists( 'imagewebp' ) ) {
            $image = null;
            if ( $mime === 'image/png' ) {
                $image = @imagecreatefrompng( $file_path );
                if ( $image ) {
                    imagepalettetotruecolor( $image );
                    imagealphablending( $image, true );
                    imagesavealpha( $image, true );
                }
            } else {
                $image = @imagecreatefromjpeg( $file_path );
            }

            if ( $image ) {
                @imagewebp( $image, $webp_path, $quality );
                @imagedestroy( $image );
                if ( file_exists( $webp_path ) ) {
                    return $webp_path;
                }
            }
        }

        if ( class_exists( 'Imagick' ) ) {
            try {
                $imagick = new Imagick( $file_path );
                $imagick->setImageFormat( 'webp' );
                $imagick->setImageCompressionQuality( $quality );
                $imagick->writeImage( $webp_path );
                $imagick->clear();
                $imagick->destroy();
                if ( file_exists( $webp_path ) ) {
                    return $webp_path;
                }
            } catch ( Exception $e ) {
                // Silently fallback
            }
        }

        return false;
    }

    /**
     * Rewrite HTML <img> tags to use WebP source where available on disk
     */
    public function rewrite_images_to_webp( $html ) {
        if ( empty( $html ) || ! is_string( $html ) ) {
            return $html;
        }

        $upload_dir = wp_upload_dir();
        $base_url   = $upload_dir['baseurl'];
        $base_dir   = $upload_dir['basedir'];

        return preg_replace_callback( '/<img\s+([^>]*?)src=["\']([^"\']+\.(?:jpe?g|png))["\']([^>]*?)>/i', function( $matches ) use ( $base_url, $base_dir ) {
            $full_tag    = $matches[0];
            $before_src  = $matches[1];
            $src_url     = $matches[2];
            $after_src   = $matches[3];

            if ( strpos( $src_url, $base_url ) === 0 ) {
                $rel_path    = substr( $src_url, strlen( $base_url ) );
                $local_file  = $base_dir . $rel_path;
                $webp_file   = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $local_file );
                $webp_url    = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $src_url );

                if ( file_exists( $webp_file ) ) {
                    // Also rewrite srcset if present
                    $combined_attrs = $before_src . ' ' . $after_src;
                    if ( preg_match( '/srcset=["\']([^"\']+)["\']/i', $combined_attrs, $srcsets ) ) {
                        $new_srcset = preg_replace( '/\.(jpe?g|png)(\s+[0-9]+[wx])?/i', '.webp$2', $srcsets[1] );
                        $after_src = preg_replace( '/srcset=["\'][^"\']+["\']/i', 'srcset="' . esc_attr( $new_srcset ) . '"', $after_src );
                        $before_src = preg_replace( '/srcset=["\'][^"\']+["\']/i', '', $before_src );
                    }
                    return sprintf( '<img %ssrc="%s" %s>', $before_src, esc_url( $webp_url ), $after_src );
                }
            }

            return $full_tag;
        }, $html );
    }

    /**
     * Get statistics on WebP images vs total images in Media Library
     */
    public function get_webp_stats() {
        $upload_dir = wp_upload_dir();
        $basedir    = $upload_dir['basedir'];

        $total_jpg_png   = 0;
        $total_webp      = 0;
        $original_bytes  = 0;
        $webp_bytes      = 0;

        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'post_mime_type' => array( 'image/jpeg', 'image/png' ),
            'post_status'    => 'inherit',
            'posts_per_page' => 1000,
            'fields'         => 'ids',
        ) );

        foreach ( $attachments as $att_id ) {
            $file = get_attached_file( $att_id );
            if ( $file && file_exists( $file ) ) {
                $total_jpg_png++;
                $fsize = filesize( $file );
                $original_bytes += $fsize;

                $webp = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file );
                if ( file_exists( $webp ) ) {
                    $total_webp++;
                    $webp_bytes += filesize( $webp );
                }
            }
        }

        $saved_bytes = ( $original_bytes > 0 && $webp_bytes > 0 && $original_bytes > $webp_bytes ) ? ( $original_bytes - $webp_bytes ) : 0;
        $saved_mb    = round( $saved_bytes / ( 1024 * 1024 ), 2 );
        $savings_pct = ( $original_bytes > 0 ) ? round( ( $saved_bytes / $original_bytes ) * 100, 1 ) : 0;

        return array(
            'total_legacy_images' => $total_jpg_png,
            'converted_webp_count' => $total_webp,
            'unconverted_count'   => max( 0, $total_jpg_png - $total_webp ),
            'original_size_mb'    => round( $original_bytes / ( 1024 * 1024 ), 2 ),
            'webp_size_mb'        => round( $webp_bytes / ( 1024 * 1024 ), 2 ),
            'saved_space_mb'      => $saved_mb,
            'savings_percentage'  => $savings_pct,
            'is_gd_available'     => function_exists( 'imagewebp' ),
            'is_imagick_available'=> class_exists( 'Imagick' ),
        );
    }

    /**
     * Bulk convert existing media library images with chunking and timeout resilience
     */
    public function convert_bulk_media( $limit = 15, $offset = 0 ) {
        $start_time = KavoshRank_REST_Controller::begin_batch_request();

        $limit  = max( 1, min( KavoshRank_REST_Controller::MAX_BATCH_ITEMS, absint( $limit ) ) );
        $offset = max( 0, absint( $offset ) );

        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'post_mime_type' => array( 'image/jpeg', 'image/png' ),
            'post_status'    => 'inherit',
            'posts_per_page' => $limit,
            'offset'         => $offset,
        ) );

        $converted_count = 0;
        $processed_count = 0;

        foreach ( $attachments as $att ) {
            // Stop before the host kills the request on time or memory; the
            // unprocessed attachments are picked up again from next_offset.
            if ( $processed_count > 0 && KavoshRank_REST_Controller::batch_budget_exceeded( $start_time ) ) {
                break;
            }
            $processed_count++;

            $file = get_attached_file( $att->ID );
            if ( $file && file_exists( $file ) ) {
                $res = $this->convert_file_to_webp( $file, get_post_mime_type( $att->ID ) );
                if ( $res ) {
                    $converted_count++;
                }
            }
        }

        $stats = $this->get_webp_stats();

        return array(
            'processed'        => $processed_count,
            'converted'        => $converted_count,
            'batch_size'       => $limit,
            'offset'           => $offset,
            'next_offset'      => $offset + $processed_count,
            'has_more'         => $processed_count < count( $attachments ) || count( $attachments ) === $limit,
            'stats'            => $stats,
        );
    }
}
