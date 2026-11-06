<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_API_Client {

    public static function get_api_url() {
        $url = trim( (string) get_option( 'kavoshrank_golang_api_url', get_option( 'tsw_golang_api_url', 'https://backend.kavoshrank.ir' ) ) );
        if ( empty( $url ) ) {
            $url = 'https://backend.kavoshrank.ir';
        }
        return $url;
    }

    /**
     * Known default tokens shipped by earlier releases. They are public, so
     * they never authenticate anything.
     */
    const LEGACY_TOKENS = array( 'tara-seo-secret-token' );

    /**
     * Minimum token length. Generated tokens are 64 hex characters.
     */
    const MIN_TOKEN_LENGTH = 32;

    public static function get_secret_token() {
        return trim( (string) get_option( 'kavoshrank_secret_token', get_option( 'tsw_secret_token', '' ) ) );
    }

    /**
     * A token is usable only if it is long enough and not a legacy default.
     */
    public static function is_strong_token( $token ) {
        $token = (string) $token;
        return strlen( $token ) >= self::MIN_TOKEN_LENGTH && ! in_array( $token, self::LEGACY_TOKENS, true );
    }

    /**
     * Generates a new site token from a CSPRNG.
     */
    public static function generate_token() {
        return bin2hex( random_bytes( 32 ) );
    }

    public static function is_configured() {
        $api_url = self::get_api_url();
        $token   = self::get_secret_token();
        return ! empty( $api_url ) && ! empty( $token );
    }

    public static function post( $endpoint, $body = array() ) {
        if ( ! self::is_configured() ) {
            return array(
                'error'  => 'KavoshRank API is not configured. Please configure Golang API URL and Secret Token.',
                'status' => 400,
            );
        }

        $url = rtrim( self::get_api_url(), '/' ) . '/' . ltrim( $endpoint, '/' );

        $response = wp_remote_post( $url, array(
            'headers' => array(
                'Content-Type'              => 'application/json',
                'X-KavoshRank-Secret-Token' => self::get_secret_token(),
                'X-TSW-Secret-Token'        => self::get_secret_token(),
            ),
            'body'    => json_encode( $body ),
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'error' => $response->get_error_message() );
        }

        $response_body = wp_remote_retrieve_body( $response );
        return json_decode( $response_body, true );
    }

    public static function get( $endpoint, $query_params = array() ) {
        if ( ! self::is_configured() ) {
            return array(
                'error'  => 'KavoshRank API is not configured. Please configure Golang API URL and Secret Token.',
                'status' => 400,
            );
        }

        $url = rtrim( self::get_api_url(), '/' ) . '/' . ltrim( $endpoint, '/' );
        if ( ! empty( $query_params ) ) {
            $url = add_query_arg( $query_params, $url );
        }

        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Content-Type'              => 'application/json',
                'X-KavoshRank-Secret-Token' => self::get_secret_token(),
                'X-TSW-Secret-Token'        => self::get_secret_token(),
            ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'error' => $response->get_error_message() );
        }

        $response_body = wp_remote_retrieve_body( $response );
        return json_decode( $response_body, true );
    }

    public static function broadcast_social( $data ) {
        return self::post( 'api/v1/social/broadcast', $data );
    }

    public static function notify_social_click( $track_code ) {
        return self::get( 'api/v1/social/click/' . rawurlencode( $track_code ) );
    }
}
