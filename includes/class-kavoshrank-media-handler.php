<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Media_REST_Handler {

    const MAX_FILE_SIZE = 15728640; // 15 MB
    const ALLOWED_EXTENSIONS = array( 'jpg', 'jpeg', 'png', 'webp', 'gif', 'mp3', 'wav', 'm4a' );
    const ALLOWED_MIME_TYPES = array(
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'audio/mpeg',
        'audio/mp3',
        'audio/wav',
        'audio/x-wav',
        'audio/m4a',
        'audio/x-m4a',
        'audio/mp4',
    );
    const ALLOWED_META_KEYS = array(
        '_kavoshrank_audio_url',
        '_kavoshrank_audio_summary_url',
        '_tsw_audio_summary_url',
        '_thumbnail_id',
        '_kavoshrank_audio_duration',
        '_kavoshrank_audio_duration_str',
    );

    /**
     * Parse WAV header to calculate audio duration in seconds.
     *
     * @param string $file_path Absolute path to the WAV file.
     * @return float Duration in seconds, or 0.0 on failure.
     */
    public static function get_wav_duration_seconds( $file_path ) {
        if ( empty( $file_path ) || ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return 0.0;
        }

        $fp = @fopen( $file_path, 'rb' );
        if ( ! $fp ) {
            return 0.0;
        }

        // Header must start with RIFF....WAVE
        $header = fread( $fp, 12 );
        if ( strlen( $header ) < 12 ) {
            fclose( $fp );
            return 0.0;
        }

        $riff = substr( $header, 0, 4 );
        $wave = substr( $header, 8, 4 );
        if ( $riff !== 'RIFF' || $wave !== 'WAVE' ) {
            fclose( $fp );
            return 0.0;
        }

        $channels        = 0;
        $sample_rate     = 0;
        $byte_rate       = 0;
        $bits_per_sample = 0;
        $data_size       = 0;

        // Traverse RIFF chunks
        while ( ! feof( $fp ) ) {
            $chunk_header = fread( $fp, 8 );
            if ( strlen( $chunk_header ) < 8 ) {
                break;
            }

            $chunk_id   = substr( $chunk_header, 0, 4 );
            $chunk_data = unpack( 'Vsize', substr( $chunk_header, 4, 4 ) );
            $chunk_size = isset( $chunk_data['size'] ) ? (int) $chunk_data['size'] : 0;

            if ( $chunk_size < 0 || $chunk_size > 10485760 ) {
                break;
            }

            if ( $chunk_id === 'fmt ' ) {
                $fmt_bytes = fread( $fp, $chunk_size );
                if ( strlen( $fmt_bytes ) >= 16 ) {
                    $fmt_info = unpack( 'vformat/vchannels/Vsamplerate/Vbyterate/vblockalign/vbitspersample', substr( $fmt_bytes, 0, 16 ) );
                    $channels        = isset( $fmt_info['channels'] ) ? (int) $fmt_info['channels'] : 0;
                    $sample_rate     = isset( $fmt_info['samplerate'] ) ? (int) $fmt_info['samplerate'] : 0;
                    $byte_rate       = isset( $fmt_info['byterate'] ) ? (int) $fmt_info['byterate'] : 0;
                    $bits_per_sample = isset( $fmt_info['bitspersample'] ) ? (int) $fmt_info['bitspersample'] : 0;
                }
            } elseif ( $chunk_id === 'data' ) {
                $data_size = $chunk_size;
                // Once we have data_size, if fmt was already parsed, we can finish
                if ( $byte_rate > 0 || ( $sample_rate > 0 && $channels > 0 && $bits_per_sample > 0 ) ) {
                    break;
                }
                // Otherwise skip chunk data and keep looking
                fseek( $fp, $chunk_size, SEEK_CUR );
            } else {
                // Skip unknown chunk
                fseek( $fp, $chunk_size, SEEK_CUR );
            }

            // Word alignment padding if chunk_size is odd
            if ( ( $chunk_size % 2 ) !== 0 ) {
                fseek( $fp, 1, SEEK_CUR );
            }
        }

        fclose( $fp );

        if ( $byte_rate > 0 && $data_size > 0 ) {
            return (float) ( $data_size / $byte_rate );
        }

        if ( $sample_rate > 0 && $channels > 0 && $bits_per_sample > 0 && $data_size > 0 ) {
            $calc_byte_rate = $sample_rate * $channels * ( $bits_per_sample / 8 );
            if ( $calc_byte_rate > 0 ) {
                return (float) ( $data_size / $calc_byte_rate );
            }
        }

        return 0.0;
    }

    /**
     * Format duration seconds to MM:SS string.
     *
     * @param float|int $seconds
     * @return string
     */
    public static function format_duration( $seconds ) {
        $total_sec = max( 0, (int) round( (float) $seconds ) );
        $min = (int) floor( $total_sec / 60 );
        $sec = $total_sec % 60;
        return sprintf( '%d:%02d', $min, $sec );
    }

    /**
     * Download a remote image and store it through handle_upload_media, so it
     * gets the same size, extension, real-bytes MIME and WebP handling.
     * download_url() goes through wp_safe_remote_get(), which refuses private
     * and loopback hosts.
     *
     * @param string $url Public http(s) image URL.
     * @return int|WP_Error Attachment ID.
     */
    public static function sideload_image_url( $url ) {
        $url = esc_url_raw( trim( (string) $url ), array( 'http', 'https' ) );
        if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
            return new WP_Error( 'invalid_image_url', 'Image URL must be a public http(s) address' );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $tmp_path = download_url( $url, 30 );
        if ( is_wp_error( $tmp_path ) ) {
            return $tmp_path;
        }

        $image_exts = array(
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        );
        $mime = wp_get_image_mime( $tmp_path );
        if ( empty( $mime ) || ! isset( $image_exts[ $mime ] ) ) {
            @unlink( $tmp_path );
            return new WP_Error( 'unsupported_media_type', 'The downloaded file is not a supported image' );
        }

        $basename = sanitize_file_name( pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_FILENAME ) );
        if ( '' === $basename ) {
            $basename = 'product-image';
        }

        $upload = new WP_REST_Request( 'POST' );
        $upload->set_file_params( array(
            'file' => array(
                'name'     => $basename . '.' . $image_exts[ $mime ],
                'tmp_name' => $tmp_path,
                'size'     => (int) filesize( $tmp_path ),
                'error'    => UPLOAD_ERR_OK,
            ),
        ) );
        $response = self::handle_upload_media( $upload );
        @unlink( $tmp_path );

        $data     = $response->get_data();
        $media_id = is_array( $data ) && isset( $data['media_id'] ) ? $data['media_id'] : 0;
        if ( 200 !== $response->get_status() || is_wp_error( $media_id ) || (int) $media_id <= 0 ) {
            $message = is_array( $data ) && ! empty( $data['error'] ) ? $data['error'] : 'Image upload failed';
            return new WP_Error( 'image_sideload_failed', $message );
        }

        return (int) $media_id;
    }

    public static function handle_upload_media( WP_REST_Request $request ) {
        $post_id     = absint( $request->get_param( 'post_id' ) );
        $meta_key    = sanitize_key( (string) $request->get_param( 'meta_key' ) );
        $files       = $request->get_file_params();
        $params      = $request->get_json_params();

        $upload_dir = wp_upload_dir();
        if ( ! empty( $upload_dir['error'] ) || ! file_exists( $upload_dir['path'] ) ) {
            if ( ! empty( $upload_dir['basedir'] ) && ! file_exists( $upload_dir['basedir'] ) ) {
                wp_mkdir_p( $upload_dir['basedir'] );
            }
            if ( ! empty( $upload_dir['path'] ) && ! file_exists( $upload_dir['path'] ) ) {
                wp_mkdir_p( $upload_dir['path'] );
            }
        }

        $file_path  = '';
        $filename   = '';

        // Mode A: Direct file upload via multipart/form-data
        $file_info = null;
        if ( ! empty( $files['file'] ) && is_array( $files['file'] ) && ( empty( $files['file']['error'] ) || $files['file']['error'] === UPLOAD_ERR_OK ) ) {
            $file_info = $files['file'];
        } elseif ( ! empty( $files['image'] ) && is_array( $files['image'] ) && ( empty( $files['image']['error'] ) || $files['image']['error'] === UPLOAD_ERR_OK ) ) {
            $file_info = $files['image'];
        } elseif ( ! empty( $_FILES['file'] ) && is_array( $_FILES['file'] ) && ( empty( $_FILES['file']['error'] ) || $_FILES['file']['error'] === UPLOAD_ERR_OK ) ) {
            $file_info = $_FILES['file'];
        } elseif ( ! empty( $_FILES['image'] ) && is_array( $_FILES['image'] ) && ( empty( $_FILES['image']['error'] ) || $_FILES['image']['error'] === UPLOAD_ERR_OK ) ) {
            $file_info = $_FILES['image'];
        }

        $upload_err_code = 0;
        if ( empty( $file_info ) ) {
            if ( isset( $files['file']['error'] ) && $files['file']['error'] !== UPLOAD_ERR_OK && $files['file']['error'] !== UPLOAD_ERR_NO_FILE ) {
                $upload_err_code = (int) $files['file']['error'];
            } elseif ( isset( $_FILES['file']['error'] ) && $_FILES['file']['error'] !== UPLOAD_ERR_OK && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE ) {
                $upload_err_code = (int) $_FILES['file']['error'];
            }
        }

        if ( ! empty( $file_info ) && ! empty( $file_info['tmp_name'] ) ) {
            if ( ! empty( $file_info['size'] ) && $file_info['size'] > self::MAX_FILE_SIZE ) {
                return new WP_REST_Response( array( 'error' => 'File size exceeds 15 MB limit' ), 413 );
            }

            $raw_filename = sanitize_file_name( $file_info['name'] );
            $raw_ext      = strtolower( pathinfo( $raw_filename, PATHINFO_EXTENSION ) );

            if ( ! in_array( $raw_ext, self::ALLOWED_EXTENSIONS, true ) ) {
                return new WP_REST_Response( array( 'error' => 'Unsupported media type: extension not permitted' ), 415 );
            }

            $filename  = $raw_filename;
            $file_path = $upload_dir['path'] . '/' . wp_unique_filename( $upload_dir['path'], $filename );

            if ( is_uploaded_file( $file_info['tmp_name'] ) ) {
                if ( ! @move_uploaded_file( $file_info['tmp_name'], $file_path ) ) {
                    @copy( $file_info['tmp_name'], $file_path );
                }
            } else {
                @copy( $file_info['tmp_name'], $file_path );
            }
        }
        // Mode B: Base64 file data (via JSON body or POST form parameter)
        else {
            $file_b64 = $params['file_b64'] ?? ( $request->get_param( 'file_b64' ) ?? '' );
            if ( empty( $file_b64 ) && isset( $_POST['file_b64'] ) ) {
                $file_b64 = (string) $_POST['file_b64'];
            }
            if ( ! empty( $file_b64 ) ) {
                $filename = sanitize_file_name( $params['filename'] ?? ( $request->get_param( 'filename' ) ?? ( 'upload-' . time() . '.jpg' ) ) );
                if ( preg_match( '/^data:(image|audio)\/([\w-]+);base64,(.*)$/s', $file_b64, $m ) ) {
                    $ext = strtolower( $m[2] );
                    if ( $ext === 'jpeg' ) {
                        $ext = 'jpg';
                    } elseif ( $ext === 'x-wav' ) {
                        $ext = 'wav';
                    } elseif ( $ext === 'x-m4a' ) {
                        $ext = 'm4a';
                    } elseif ( $ext === 'mpeg' ) {
                        $ext = 'mp3';
                    }
                    $file_b64 = $m[3];
                    if ( ! preg_match( '/\.\w+$/', $filename ) ) {
                        $filename .= '.' . $ext;
                    }
                }

                $raw_ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
                if ( ! in_array( $raw_ext, self::ALLOWED_EXTENSIONS, true ) ) {
                    return new WP_REST_Response( array( 'error' => 'Unsupported media type: extension not permitted' ), 415 );
                }

                $decoded_bytes = base64_decode( $file_b64 );
                if ( strlen( $decoded_bytes ) > self::MAX_FILE_SIZE ) {
                    return new WP_REST_Response( array( 'error' => 'File size exceeds 15 MB limit' ), 413 );
                }

                $file_path = $upload_dir['path'] . '/' . wp_unique_filename( $upload_dir['path'], $filename );
                @file_put_contents( $file_path, $decoded_bytes );
            } elseif ( $upload_err_code === 1 || $upload_err_code === 2 ) {
                return new WP_REST_Response( array(
                    'error' => 'حجم فایل ارسالی از سقف مجاز آپلود در تنظیمات PHP سرور وردپرس (upload_max_filesize) بیشتر است.',
                    'code'  => 'upload_max_filesize',
                    'upload_error_code' => $upload_err_code
                ), 413 );
            } elseif ( $upload_err_code === 3 ) {
                return new WP_REST_Response( array(
                    'error' => 'فایل به صورت ناقص به سرور وردپرس رسید (upload_partial).',
                    'code'  => 'upload_partial',
                    'upload_error_code' => $upload_err_code
                ), 400 );
            } else {
                return new WP_REST_Response( array( 'error' => 'No file or file_b64 provided in request' ), 400 );
            }
        }

        if ( ! file_exists( $file_path ) || filesize( $file_path ) === 0 ) {
            $err_detail = ! empty( $upload_dir['error'] ) ? $upload_dir['error'] : 'wp-content/uploads is not writable by server process';
            return new WP_REST_Response( array( 
                'error' => 'Failed to write uploaded file to disk: ' . $err_detail,
                'hint'  => 'Please verify write permissions (chmod 755 / 775) on wp-content/uploads and parent directory.'
            ), 500 );
        }

        // Strict real-bytes extension & MIME validation
        $file_check = wp_check_filetype_and_ext( $file_path, $filename );
        $ext        = strtolower( (string) $file_check['ext'] );
        $type       = (string) $file_check['type'];

        if ( empty( $ext ) || empty( $type ) || ! in_array( $ext, self::ALLOWED_EXTENSIONS, true ) || ! in_array( $type, self::ALLOWED_MIME_TYPES, true ) ) {
            @unlink( $file_path );
            return new WP_REST_Response( array( 
                'error' => 'File validation failed: MIME type or extension is invalid or disallowed.',
                'code'  => 'unsupported_media_type'
            ), 415 );
        }

        // Automatic WebP Conversion for Images
        $real_image_info = @getimagesize( $file_path );
        $real_mime       = is_array( $real_image_info ) && ! empty( $real_image_info['mime'] ) ? strtolower( (string) $real_image_info['mime'] ) : '';

        $needs_webp = in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif' ), true )
            || ( strpos( $type, 'image/' ) === 0 && $ext !== 'webp' )
            || ( $ext === 'webp' && ! empty( $real_mime ) && $real_mime !== 'image/webp' );

        if ( $needs_webp ) {
            $webp_path = preg_replace( '/\.(jpe?g|png|gif|webp)$/i', '.webp', $file_path );
            if ( ! preg_match( '/\.webp$/i', $webp_path ) ) {
                $webp_path .= '.webp';
            }
            if ( $webp_path === $file_path ) {
                $webp_path = $upload_dir['path'] . '/c-' . wp_unique_filename( $upload_dir['path'], $filename );
            }
            $converted = false;
            if ( function_exists( 'imagewebp' ) ) {
                $img = null;
                if ( function_exists( 'imagecreatefromstring' ) ) {
                    $raw_content = @file_get_contents( $file_path );
                    if ( ! empty( $raw_content ) ) {
                        $img = @imagecreatefromstring( $raw_content );
                    }
                }
                if ( ! $img ) {
                    if ( $ext === 'png' || strpos( $type, 'png' ) !== false || $real_mime === 'image/png' ) {
                        $img = @imagecreatefrompng( $file_path );
                    } elseif ( $ext === 'gif' || strpos( $type, 'gif' ) !== false || $real_mime === 'image/gif' ) {
                        $img = @imagecreatefromgif( $file_path );
                    } else {
                        $img = @imagecreatefromjpeg( $file_path );
                    }
                }

                if ( $img ) {
                    imagepalettetotruecolor( $img );
                    imagealphablending( $img, true );
                    imagesavealpha( $img, true );
                    if ( @imagewebp( $img, $webp_path, 85 ) && file_exists( $webp_path ) && filesize( $webp_path ) > 0 ) {
                        @imagedestroy( $img );
                        @unlink( $file_path );
                        $file_path  = $webp_path;
                        $filename   = basename( $webp_path );
                        $file_check = array( 'ext' => 'webp', 'type' => 'image/webp' );
                        $converted  = true;
                    } else {
                        @imagedestroy( $img );
                    }
                }
            }

            if ( ! $converted && class_exists( 'Imagick' ) ) {
                try {
                    $imagick = new Imagick( $file_path );
                    $imagick->setImageFormat( 'webp' );
                    $imagick->setImageCompressionQuality( 85 );
                    $imagick->writeImage( $webp_path );
                    $imagick->clear();
                    $imagick->destroy();
                    if ( file_exists( $webp_path ) && filesize( $webp_path ) > 0 ) {
                        @unlink( $file_path );
                        $file_path  = $webp_path;
                        $filename   = basename( $webp_path );
                        $file_check = array( 'ext' => 'webp', 'type' => 'image/webp' );
                    }
                } catch ( Exception $e ) {}
            }
        }

        $attachment = array(
            'post_mime_type' => $file_check['type'] ? $file_check['type'] : 'application/octet-stream',
            'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );

        if ( ! is_wp_error( $attach_id ) ) {
            $is_image = strpos( (string) $file_check['type'], 'image/' ) === 0;
            $is_audio = strpos( (string) $file_check['type'], 'audio' ) !== false;

            if ( file_exists( ABSPATH . 'wp-admin/includes/file.php' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            if ( file_exists( ABSPATH . 'wp-admin/includes/media.php' ) ) {
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }
            if ( file_exists( ABSPATH . 'wp-admin/includes/image.php' ) ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
            }

            try {
                if ( function_exists( 'wp_generate_attachment_metadata' ) && ( ! $is_audio || function_exists( 'wp_read_audio_metadata' ) ) ) {
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
                    if ( ! empty( $attach_data ) && ! is_wp_error( $attach_data ) ) {
                        wp_update_attachment_metadata( $attach_id, $attach_data );
                    }
                }
            } catch ( Throwable $e ) {
                error_log( 'KavoshRank media metadata generation notice: ' . $e->getMessage() );
            }
        }

        $attachment_url = wp_get_attachment_url( $attach_id );

        // If audio file, store audio podcast meta and duration
        if ( strpos( (string) $file_check['type'], 'audio' ) !== false ) {
            $duration_sec = self::get_wav_duration_seconds( $file_path );
            if ( $duration_sec <= 0 && ! empty( $attach_data['length'] ) ) {
                $duration_sec = (float) $attach_data['length'];
            }
            $duration_str = $duration_sec > 0 ? self::format_duration( $duration_sec ) : '';

            if ( ! is_wp_error( $attach_id ) ) {
                if ( $duration_sec > 0 ) {
                    update_post_meta( $attach_id, '_kavoshrank_audio_duration', $duration_sec );
                    update_post_meta( $attach_id, '_kavoshrank_audio_duration_str', $duration_str );
                }
            }

            if ( $post_id > 0 ) {
                update_post_meta( $post_id, '_kavoshrank_audio_summary_url', $attachment_url );
                update_post_meta( $post_id, '_tsw_audio_summary_url', $attachment_url );
                if ( $duration_sec > 0 ) {
                    update_post_meta( $post_id, '_kavoshrank_audio_duration', $duration_sec );
                    update_post_meta( $post_id, '_kavoshrank_audio_duration_str', $duration_str );
                }

                $audio_theme = $request->get_param( 'audio_theme' );
                if ( empty( $audio_theme ) && ! empty( $_POST['audio_theme'] ) ) {
                    $audio_theme = sanitize_text_field( $_POST['audio_theme'] );
                }
                if ( ! empty( $audio_theme ) ) {
                    update_post_meta( $post_id, '_kavoshrank_audio_theme', sanitize_text_field( $audio_theme ) );
                    update_option( 'kavoshrank_audio_player_theme', sanitize_text_field( $audio_theme ) );
                }

                $audio_voice = $request->get_param( 'audio_voice' );
                if ( empty( $audio_voice ) && ! empty( $_POST['audio_voice'] ) ) {
                    $audio_voice = sanitize_text_field( $_POST['audio_voice'] );
                }
                if ( ! empty( $audio_voice ) ) {
                    update_post_meta( $post_id, '_kavoshrank_audio_voice', sanitize_text_field( $audio_voice ) );
                    update_option( 'kavoshrank_audio_speaker_voice', sanitize_text_field( $audio_voice ) );
                }

                $audio_speed = $request->get_param( 'audio_speed' );
                if ( empty( $audio_speed ) && ! empty( $_POST['audio_speed'] ) ) {
                    $audio_speed = sanitize_text_field( $_POST['audio_speed'] );
                }
                if ( ! empty( $audio_speed ) ) {
                    update_post_meta( $post_id, '_kavoshrank_audio_speed', sanitize_text_field( $audio_speed ) );
                    update_option( 'kavoshrank_audio_speech_rate', sanitize_text_field( $audio_speed ) );
                }

                $audio_auto_insert = $request->get_param( 'audio_auto_insert' );
                if ( empty( $audio_auto_insert ) && isset( $_POST['audio_auto_insert'] ) ) {
                    $audio_auto_insert = sanitize_text_field( $_POST['audio_auto_insert'] );
                }
                if ( ! empty( $audio_auto_insert ) || '0' === $audio_auto_insert ) {
                    update_post_meta( $post_id, '_kavoshrank_audio_auto_insert', sanitize_text_field( $audio_auto_insert ) );
                }
            }
        }

        // Only images become the featured image. Audio (podcasts) is attached
        // to the post without replacing its thumbnail or touching the content.
        $is_image = strpos( (string) $file_check['type'], 'image/' ) === 0;
        if ( $post_id > 0 && $is_image ) {
            set_post_thumbnail( $post_id, $attach_id );
            update_post_meta( $post_id, '_thumbnail_id', $attach_id );
            wp_update_post( array(
                'ID'          => $attach_id,
                'post_parent' => $post_id,
            ) );
            if ( class_exists( 'KavoshRank_Post_REST_Handler' ) ) {
                KavoshRank_Post_REST_Handler::inject_featured_image_into_content( $post_id, (int) $attach_id );
            }
        }

        // Only allow strictly allowlisted meta keys
        if ( $post_id > 0 && ! empty( $meta_key ) && in_array( $meta_key, self::ALLOWED_META_KEYS, true ) ) {
            update_post_meta( $post_id, $meta_key, $attachment_url );
        }

        return new WP_REST_Response( array(
            'status'        => 'success',
            'media_id'      => $attach_id,
            'media_url'     => $attachment_url,
            'attachment_id' => $attach_id,
            'id'            => $attach_id,
            'url'           => $attachment_url,
            'source_url'    => $attachment_url,
        ), 200 );
    }
}
