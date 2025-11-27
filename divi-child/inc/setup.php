<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Core Theme Setup and Cache Control
 *
 * Contains all actions necessary for theme initialization and cache management.
 *
 * @package buildx
 */

// 1. Caching Control: Do not cache the Learning Center page.
add_action('template_redirect', function () {
  // Change 'learning-center' if your slug differs or use is_page(ID)
  if (is_page('learning-center')) {
    if (!headers_sent()) nocache_headers();
    if (!defined('DONOTCACHEPAGE'))   define('DONOTCACHEPAGE', true);
    if (!defined('DONOTCACHEDB'))     define('DONOTCACHEDB', true);
    if (!defined('DONOTCACHEOBJECT')) define('DONOTCACHEOBJECT', true);
  }
});

// 2. Caching Control: Add no-cache headers to the LC REST endpoint.
add_filter('rest_post_dispatch', function ($result, $server, $request) {
  $route = $request->get_route();
  if (strpos($route, '/vigilance/v1/learning') !== false) {
    $server->send_header('Cache-Control', 'no-cache, no-store, must-revalidate');
    $server->send_header('Pragma', 'no-cache');
    $server->send_header('Expires', '0');
  }
  return $result;
}, 10, 3);

// 3. Caching Control: Add no-cache headers for the homepage HTML (front page only).
add_action('send_headers', function () {
  if (is_front_page() && !headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
  }
});

// 4. Initial CPT/Taxonomy Registration Hook
// Note: While the original file contained no CPT definitions, this is where
// CPTs would typically be registered if they were moved from the 'init' wrapper.
add_action('init', function () {
    // This hook is now clean and available for core 'init' actions,
    // like defining custom taxonomies, which the original code did not contain.
});

// No PHP closing tag