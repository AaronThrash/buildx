<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Divi Child Theme Loader: BuildX
 *
 * This file acts as the primary loader for all modular features and external includes.
 * All logic has been moved to dedicated files in the /inc/ directory.
 *
 * @package buildx
 */

// === NEW MODULAR INCLUDES (Organized by Concern) ===

require_once get_stylesheet_directory() . '/inc/setup.php';
require_once get_stylesheet_directory() . '/inc/security.php';
require_once get_stylesheet_directory() . '/inc/seo.php';
require_once get_stylesheet_directory() . '/inc/video.php';
require_once get_stylesheet_directory() . '/inc/popularity.php';
require_once get_stylesheet_directory() . '/inc/learning-center.php';
require_once get_stylesheet_directory() . '/inc/floor-plans.php';
require_once get_stylesheet_directory() . '/inc/popularity-dashboard.php';


// (No PHP close tags in functions; safe on all PHP 7+)
