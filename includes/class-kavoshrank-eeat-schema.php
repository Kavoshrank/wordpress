<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/class-kavoshrank-schema.php';

/**
 * KavoshRank_EEAT_Schema alias for backward compatibility and unified graph operations
 */
class KavoshRank_EEAT_Schema extends KavoshRank_Schema {
    // Inherits all unified @graph semantic capabilities
}
