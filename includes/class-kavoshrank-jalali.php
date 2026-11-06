<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KavoshRank Jalali (Shamsi) Calendar Conversion Engine
 *
 * Implements the standard Borkowski astronomical algorithm for bidirectional date conversions
 * between Jalali (Solar Hijri) and Gregorian calendars for scheduling, post publication, and reporting.
 * Completely self-contained with zero external dependencies.
 */
class KavoshRank_Jalali {

    /**
     * Jalaali years that begin a new 33-year leap cycle (Borkowski algorithm breaks).
     */
    private static $breaks = array(
        -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181,
        1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,
    );

    /**
     * Integer division helper.
     */
    private static function div( $a, $b ) {
        return (int) ( $a / $b );
    }

    /**
     * Mathematical modulo helper.
     */
    private static function mod( $a, $b ) {
        return $a - (int) ( $a / $b ) * $b;
    }

    /**
     * Calculates Gregorian year and March starting day for a given Jalali year.
     */
    private static function jal_cal( $jy ) {
        $gy    = (int) $jy + 621;
        $leapJ = -14;
        $jp    = self::$breaks[0];
        $jm    = 0;
        $jump  = 0;

        $bl = count( self::$breaks );
        for ( $i = 1; $i < $bl; $i++ ) {
            $jm   = self::$breaks[ $i ];
            $jump = $jm - $jp;
            if ( $jy < $jm ) {
                break;
            }
            $leapJ = $leapJ + self::div( $jump, 33 ) * 8 + self::div( self::mod( $jump, 33 ), 4 );
            $jp    = $jm;
        }
        $n = $jy - $jp;

        $leapJ = $leapJ + self::div( $n, 33 ) * 8 + self::div( self::mod( $n, 33 ) + 3, 4 );
        if ( self::mod( $jump, 33 ) === 4 && $jump - $n === 4 ) {
            $leapJ += 1;
        }

        $leapG = self::div( $gy, 4 ) - self::div( ( self::div( $gy, 100 ) + 1 ) * 3, 4 ) - 150;
        $march = 20 + $leapJ - $leapG;

        $adjusted = $n;
        if ( $jump - $n < 6 ) {
            $adjusted = $n - $jump + self::div( $jump + 4, 33 ) * 33;
        }
        $leap = self::mod( self::mod( $adjusted + 1, 33 ) - 1, 4 );
        if ( $leap === -1 ) {
            $leap = 4;
        }

        return array(
            'gy'    => $gy,
            'march' => $march,
            'leap'  => $leap,
        );
    }

    /**
     * Converts Gregorian date to Julian Day Number (JDN).
     */
    public static function g2d( $gy, $gm, $gd ) {
        $d = self::div( ( $gy + self::div( $gm - 8, 6 ) + 100100 ) * 1461, 4 )
            + self::div( 153 * self::mod( $gm + 9, 12 ) + 2, 5 )
            + $gd - 34840408;
        $d = $d - self::div( self::div( $gy + 100100 + self::div( $gm - 8, 6 ), 100 ) * 3, 4 ) + 752;
        return $d;
    }

    /**
     * Converts Julian Day Number (JDN) to Gregorian date.
     */
    public static function d2g( $jdn ) {
        $j  = 4 * $jdn + 139361631;
        $j  = $j + self::div( self::div( 4 * $jdn + 183187720, 146097) * 3, 4 ) * 4 - 3908;
        $i  = self::div( self::mod( $j, 1461 ), 4 ) * 5 + 308;
        $gd = self::div( self::mod( $i, 153 ), 5 ) + 1;
        $gm = self::mod( self::div( $i, 153 ), 12 ) + 1;
        $gy = self::div( $j, 1461 ) - 100100 + self::div( 8 - $gm, 6 );
        return array( (int) $gy, (int) $gm, (int) $gd );
    }

    /**
     * Converts Jalali date to Julian Day Number (JDN).
     */
    public static function j2d( $jy, $jm, $jd ) {
        $r = self::jal_cal( $jy );
        return self::g2d( $r['gy'], 3, $r['march'] ) + ( $jm - 1 ) * 31 - self::div( $jm, 7 ) * ( $jm - 7 ) + $jd - 1;
    }

    /**
     * Converts Julian Day Number (JDN) to Jalali date.
     */
    public static function d2j( $jdn ) {
        $g     = self::d2g( $jdn );
        $gy    = $g[0];
        $jy    = min( $gy - 621, 3177 );
        $r     = self::jal_cal( $jy );
        $jdn1f = self::g2d( $r['gy'], 3, $r['march'] );

        $k = $jdn - $jdn1f;
        if ( $k >= 0 ) {
            if ( $k <= 185 ) {
                return array( (int) $jy, (int) ( 1 + self::div( $k, 31 ) ), (int) ( self::mod( $k, 31 ) + 1 ) );
            }
            $k -= 186;
        } else {
            $jy -= 1;
            $k  += 179;
            if ( $r['leap'] === 1 ) {
                $k += 1;
            }
        }
        return array( (int) $jy, (int) ( 7 + self::div( $k, 30 ) ), (int) ( self::mod( $k, 30 ) + 1 ) );
    }

    /**
     * Convert Jalali (Shamsi) Date to Gregorian Date
     *
     * @param int $jy Jalali year (e.g. 1403)
     * @param int $jm Jalali month (1-12)
     * @param int $jd Jalali day (1-31)
     * @return array [year, month, day] in Gregorian
     */
    public static function jalali_to_gregorian( $jy, $jm, $jd ) {
        return self::d2g( self::j2d( (int) $jy, (int) $jm, (int) $jd ) );
    }

    /**
     * Convert Gregorian Date to Jalali (Shamsi) Date
     *
     * @param int $gy Gregorian year (e.g. 2024)
     * @param int $gm Gregorian month (1-12)
     * @param int $gd Gregorian day (1-31)
     * @return array [year, month, day] in Jalali
     */
    public static function gregorian_to_jalali( $gy, $gm, $gd ) {
        return self::d2j( self::g2d( (int) $gy, (int) $gm, (int) $gd ) );
    }

    /**
     * Convert Jalali date/datetime string into MySQL Gregorian format (YYYY-MM-DD HH:MM:SS)
     *
     * @param string $jalali_datetime_str Input string (e.g. "1403/07/14 14:30:00" or "1403-07-14")
     * @return string Formatted Gregorian MySQL datetime or empty string
     */
    public static function parse_to_gregorian_mysql( $jalali_datetime_str ) {
        $str = trim( (string) $jalali_datetime_str );
        if ( empty( $str ) ) {
            return '';
        }

        // Split date and time
        $parts     = preg_split( '/\s+/', $str );
        $date_part = isset( $parts[0] ) ? $parts[0] : '';
        $time_part = isset( $parts[1] ) ? $parts[1] : '00:00:00';

        $delimiter = strpos( $date_part, '/' ) !== false ? '/' : '-';
        $d_parts   = explode( $delimiter, $date_part );

        if ( count( $d_parts ) === 3 ) {
            $jy = (int) $d_parts[0];
            $jm = (int) $d_parts[1];
            $jd = (int) $d_parts[2];

            // If year is Gregorian already (> 1700), return as-is
            if ( $jy > 1700 ) {
                return sprintf( '%04d-%02d-%02d %s', $jy, $jm, $jd, $time_part );
            }

            list( $gy, $gm, $gd ) = self::jalali_to_gregorian( $jy, $jm, $jd );
            return sprintf( '%04d-%02d-%02d %s', $gy, $gm, $gd, $time_part );
        }

        return $str;
    }
}

// Global functions for backward compatibility across theme and plugin code
if ( ! function_exists( 'kavoshrank_jalali_to_gregorian' ) ) {
    function kavoshrank_jalali_to_gregorian( $jy, $jm, $jd ) {
        return KavoshRank_Jalali::jalali_to_gregorian( $jy, $jm, $jd );
    }
}

if ( ! function_exists( 'kavoshrank_gregorian_to_jalali' ) ) {
    function kavoshrank_gregorian_to_jalali( $gy, $gm, $gd ) {
        return KavoshRank_Jalali::gregorian_to_jalali( $gy, $gm, $gd );
    }
}

// Fallback shims for legacy calls
if ( ! function_exists( 'tsw_jalali_to_gregorian' ) ) {
    function tsw_jalali_to_gregorian( $jy, $jm, $jd ) {
        return KavoshRank_Jalali::jalali_to_gregorian( $jy, $jm, $jd );
    }
}

if ( ! function_exists( 'tsw_gregorian_to_jalali' ) ) {
    function tsw_gregorian_to_jalali( $gy, $gm, $gd ) {
        return KavoshRank_Jalali::gregorian_to_jalali( $gy, $gm, $gd );
    }
}
