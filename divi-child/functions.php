<?php
// BuildX Learning Center – 4 core taxonomies
add_action('init', function () {

  // Do not cache the Learning Center page
add_action('template_redirect', function () {
  // Change 'learning-center' if your slug differs or use is_page(ID)
  if (is_page('learning-center')) {
    if (!headers_sent()) nocache_headers();
    if (!defined('DONOTCACHEPAGE'))   define('DONOTCACHEPAGE', true);
    if (!defined('DONOTCACHEDB'))     define('DONOTCACHEDB', true);
    if (!defined('DONOTCACHEOBJECT')) define('DONOTCACHEOBJECT', true);
  }
});

// Add no-cache headers to the LC REST endpoint
add_filter('rest_post_dispatch', function ($result, $server, $request) {
  $route = $request->get_route();
  if (strpos($route, '/vigilance/v1/learning') !== false) {
    $server->send_header('Cache-Control', 'no-cache, no-store, must-revalidate');
    $server->send_header('Pragma', 'no-cache');
    $server->send_header('Expires', '0');
  }
  return $result;
}, 10, 3);

// Add no-cache headers for the homepage HTML (front page only).
add_action('send_headers', function () {
  if (is_front_page() && !headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
  }
});

// Print the LC video modal once in the footer.
// If your slug differs, change 'learning-center' or remove the if() to print globally.
add_action('wp_footer', function () {
  if (!is_page('learning-center')) return;
  ?>
  <div id="lr-video-modal" class="lr-modal" hidden>
    <div class="lr-modal__backdrop" data-close></div>
    <div class="lr-modal__dialog" role="dialog" aria-modal="true" aria-label="Video player">
      <button class="lr-modal__close" type="button" data-close aria-label="Close">×</button>
      <div class="lr-modal__player"></div>
    </div>
  </div>
  <?php
}, 99);


// Find a YouTube/Vimeo URL for a post (custom field, Divi video module, or plain link)
if (!function_exists('buildx_lr_find_video_url')) {
  function buildx_lr_find_video_url($post_id) {
    // 1) Custom fields you might use
    foreach (['lr_video_url', 'et_video_url', '_et_pb_video_url'] as $key) {
      $v = trim((string) get_post_meta($post_id, $key, true));
      if ($v !== '') return esc_url_raw($v);
    }

    // 2) Look for Divi video module shortcode: [et_pb_video src="..."]
    $content = get_post_field('post_content', $post_id);
    if ($content) {
      if (preg_match('/\[et_pb_video[^]]*?src="([^"]+)"/i', $content, $m)) {
        return esc_url_raw($m[1]);
      }
      // 3) Fallback: any YouTube/Vimeo URL in content
      if (preg_match('~https?://(?:www\.)?(?:youtube\.com/watch\?v=[^"\s&]+|youtu\.be/[^"\s&]+|vimeo\.com/\d+)~i', $content, $m)) {
        return esc_url_raw($m[0]);
      }
    }

    return '';
  }
}


/**
 * Allow safe SVG uploads for administrators only.
 * - Adds svg mime
 * - Forces proper filetype detection
 * - Rejects SVGs containing script/foreignObject/event handlers/js: urls
 */
add_filter('upload_mimes', function($mimes){
    if (current_user_can('manage_options')) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
    }
    return $mimes;
});

add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes){
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if (in_array(strtolower($ext), ['svg','svgz'])) {
        $data['ext']  = 'svg';
        $data['type'] = 'image/svg+xml';
    }
    return $data;
}, 10, 4);

add_filter('wp_handle_upload_prefilter', function($file){
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['svg','svgz'])) return $file;
    if (!current_user_can('manage_options')) {
        $file['error'] = 'Only administrators may upload SVG files.';
        return $file;
    }
    $raw = @file_get_contents($file['tmp_name']);
    if ($raw === false) {
        $file['error'] = 'SVG could not be read.';
        return $file;
    }
    // Very conservative checks
    $danger = [
        '/<\s*script\b/i',
        '/<\s*foreignObject\b/i',
        '/on\w+\s*=/i',                 // onload=, onclick=, etc
        '/xlink:href\s*=\s*["\']\s*javascript:/i',
        '/href\s*=\s*["\']\s*javascript:/i',
        '/data\s*:\s*text\/html/i'
    ];
    foreach ($danger as $re) {
        if (preg_match($re, $raw)) {
            $file['error'] = 'Unsafe SVG content detected. Please export a clean SVG (no scripts or event handlers).';
            return $file;
        }
    }
    return $file;
});


}); // End initial action, so taxonomies are registered before loading modules below.


// Load independent modules
require_once get_stylesheet_directory() . '/inc/popularity.php';
require_once get_stylesheet_directory() . '/inc/learning-center.php';
require_once get_stylesheet_directory() . '/inc/floor-plans.php';
require_once get_stylesheet_directory() . '/inc/popularity-dashboard.php';

// (no PHP close tags in functions; safe on all PHP 7+)