<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Learning Center Video Modal and Utility
 *
 * Contains functions for finding video URLs and outputting the modal HTML.
 *
 * @package buildx
 */

// Print the LC video modal once in the footer.
add_action('wp_footer', function () {
  // If your slug differs, change 'learning-center' or remove the if() to print globally.
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


/**
 * Finds a YouTube/Vimeo URL for a given post ID.
 *
 * Searches custom fields, the Divi video module shortcode, and finally
 * scans the post content for a valid video URL.
 *
 * @param int $post_id The ID of the post to check.
 * @return string The escaped video URL or an empty string.
 */
if (!function_exists('buildx_lr_find_video_url')) {
  function buildx_lr_find_video_url($post_id) {
    $post_id = (int) $post_id;
    // 1) Custom fields you might use
    foreach (['lr_video_url', 'et_video_url', '_et_pb_video_url'] as $key) {
      // NOTE: get_post_meta output is generally safe, but rely on esc_url_raw later.
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
// No PHP closing tag