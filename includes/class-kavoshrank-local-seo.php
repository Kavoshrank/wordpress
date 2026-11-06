<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KavoshRank_Local_SEO {

    public function init() {
        if ( get_option( 'kavoshrank_enable_local_seo', '0' ) !== '1' ) {
            return;
        }

        add_action( 'wp_head', array( $this, 'render_local_business_schema' ), 3 );
    }

    /**
     * Build LocalBusiness Schema array or null if requirements not met
     */
    public function build_local_business_schema() {
        $name    = trim( (string) get_option( 'kavoshrank_local_business_name', '' ) );
        $address = trim( (string) get_option( 'kavoshrank_local_business_address', '' ) );
        $phone   = trim( (string) get_option( 'kavoshrank_local_business_phone', '' ) );
        if ( empty( $phone ) ) {
            $phone = trim( (string) get_option( 'kavoshrank_contact_phone', '' ) );
        }

        // Requirements per Google specifications and task requirements:
        // Emit LocalBusiness ONLY when business name, address, and phone are explicitly provided.
        if ( empty( $name ) || empty( $address ) || empty( $phone ) ) {
            return null;
        }

        $type = trim( (string) get_option( 'kavoshrank_local_business_type', 'LocalBusiness' ) );
        if ( empty( $type ) ) {
            $type = 'LocalBusiness';
        }

        $schema = array(
            '@context'  => 'https://schema.org',
            '@type'     => $type,
            'name'      => $name,
            'url'       => home_url( '/' ),
            'telephone' => $phone,
        );

        $postal_address = array(
            '@type'         => 'PostalAddress',
            'streetAddress' => $address,
        );

        $locality    = trim( (string) get_option( 'kavoshrank_local_business_locality', '' ) );
        $region      = trim( (string) get_option( 'kavoshrank_local_business_region', '' ) );
        $postal_code = trim( (string) get_option( 'kavoshrank_local_business_postal_code', '' ) );
        $country     = trim( (string) get_option( 'kavoshrank_local_business_country', '' ) );

        if ( ! empty( $locality ) ) {
            $postal_address['addressLocality'] = $locality;
        }
        if ( ! empty( $region ) ) {
            $postal_address['addressRegion'] = $region;
        }
        if ( ! empty( $postal_code ) ) {
            $postal_address['postalCode'] = $postal_code;
        }
        if ( ! empty( $country ) ) {
            $postal_address['addressCountry'] = $country;
        }

        $schema['address'] = $postal_address;

        $lat = trim( (string) get_option( 'kavoshrank_local_business_geo_lat', '' ) );
        $lng = trim( (string) get_option( 'kavoshrank_local_business_geo_lng', '' ) );
        if ( empty( $lat ) || empty( $lng ) ) {
            $legacy_lat = trim( (string) get_option( 'kavoshrank_geo_lat', '' ) );
            $legacy_lng = trim( (string) get_option( 'kavoshrank_geo_lng', '' ) );
            if ( ! empty( $legacy_lat ) && ! empty( $legacy_lng ) ) {
                $lat = $legacy_lat;
                $lng = $legacy_lng;
            }
        }
        if ( ! empty( $lat ) && ! empty( $lng ) && is_numeric( $lat ) && is_numeric( $lng ) ) {
            $schema['geo'] = array(
                '@type'     => 'GeoCoordinates',
                'latitude'  => (string) $lat,
                'longitude' => (string) $lng,
            );
        }

        $logo = trim( (string) get_option( 'kavoshrank_local_business_logo', '' ) );
        if ( empty( $logo ) && function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
            $custom_logo_id = get_theme_mod( 'custom_logo' );
            $logo_data      = wp_get_attachment_image_src( $custom_logo_id, 'full' );
            if ( ! empty( $logo_data[0] ) ) {
                $logo = $logo_data[0];
            }
        }
        if ( empty( $logo ) && function_exists( 'get_site_icon_url' ) ) {
            $site_icon = get_site_icon_url();
            if ( ! empty( $site_icon ) ) {
                $logo = $site_icon;
            }
        }
        if ( ! empty( $logo ) ) {
            $schema['image'] = $logo;
        }

        $price_range = trim( (string) get_option( 'kavoshrank_local_business_price_range', '' ) );
        if ( ! empty( $price_range ) ) {
            $schema['priceRange'] = $price_range;
        }

        $opening_hours = trim( (string) get_option( 'kavoshrank_local_business_opening_hours', '' ) );
        if ( ! empty( $opening_hours ) ) {
            $hours_arr = array_filter( array_map( 'trim', explode( ',', $opening_hours ) ) );
            if ( ! empty( $hours_arr ) ) {
                $schema['openingHours'] = count( $hours_arr ) === 1 ? reset( $hours_arr ) : array_values( $hours_arr );
            }
        }

        return $schema;
    }

    public function render_local_business_schema() {
        if ( is_admin() || is_feed() ) {
            return;
        }

        // Only inject on homepage or contact page to avoid schema pollution
        if ( ! is_front_page() && ! is_home() && ! is_page( array( 'contact', 'contact-us', 'تماس-با-ما', 'درباره-ما', 'about' ) ) ) {
            return;
        }

        $schema = $this->build_local_business_schema();
        if ( empty( $schema ) ) {
            return;
        }

        echo "\n" . '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        echo "\n" . '</script>' . "\n";
    }
}
