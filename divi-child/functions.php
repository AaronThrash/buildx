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

// === BuildX JSON-LD Schema Output ===
add_action( 'wp_head', 'buildx_output_jsonld_schema', 5 );

function buildx_output_jsonld_schema() {
    if ( is_admin() ) {
        return;
    }

    $schemas = [];

    // --- Organization / LocalBusiness ---
    $schemas[] = [
        '@context' => 'https://schema.org',
        '@type'    => ['Organization', 'LocalBusiness'],
        '@id'      => 'https://buildx.com/#organization',
        'name'     => 'BuildX',
        'url'      => 'https://buildx.com/',
        'telephone'=> '+1-781-627-7000',
        'logo'     => 'https://buildx.com/wp-content/uploads/2024/XX/buildx-header-logo.png',
        'address'  => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => '1 Marion Dr, Unit 2B',
            'addressLocality' => 'Carver',
            'addressRegion'   => 'MA',
            'postalCode'      => '02330',
            'addressCountry'  => 'US',
        ],
        'sameAs'   => [
            'https://www.facebook.com/BuildXUSA/',
            'https://www.instagram.com/buildx_usa/',
            'https://www.youtube.com/@BuildX_USA',
            'https://www.linkedin.com/company/buildxusa',
        ],
    ];

    // --- WebSite (mostly for homepage / global search) ---
    $schemas[] = [
        '@context'       => 'https://schema.org',
        '@type'          => 'WebSite',
        '@id'            => 'https://buildx.com/#website',
        'url'            => 'https://buildx.com/',
        'name'           => 'BuildX | Your Building Experts',
        'publisher'      => [ '@id' => 'https://buildx.com/#organization' ],
        'potentialAction'=> [
            '@type'       => 'SearchAction',
            'target'      => 'https://buildx.com/?s={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ];

    // --- Page / Post specific schema ---
    if ( is_singular() ) {
        $post_id    = get_the_ID();
        $title      = get_the_title( $post_id );
        $permalink  = get_permalink( $post_id );
        $excerpt    = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : '';
        $published  = get_the_date( 'c', $post_id );
        $modified   = get_the_modified_date( 'c', $post_id );
        $author_id  = get_post_field( 'post_author', $post_id );
        $author_name= get_the_author_meta( 'display_name', $author_id );

        // Try to get a featured image
        $image_url = null;
        if ( has_post_thumbnail( $post_id ) ) {
            $image_data = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
            if ( ! empty( $image_data[0] ) ) {
                $image_url = $image_data[0];
            }
        }

        // Default WebPage node
        $page_schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebPage',
            '@id'             => $permalink . '#webpage',
            'url'             => $permalink,
            'headline'        => $title,
            'name'            => $title,
            'description'     => $excerpt,
            'isPartOf'        => [ '@id' => 'https://buildx.com/#website' ],
            'datePublished'   => $published,
            'dateModified'    => $modified,
            'mainEntityOfPage'=> $permalink,
        ];
        if ( $image_url ) {
            $page_schema['image'] = [
                '@type' => 'ImageObject',
                'url'   => $image_url,
            ];
        }
        $schemas[] = $page_schema;

        // For posts (Learning Center, testimonials, etc.), add BlogPosting
        if ( is_singular( 'post' ) ) {
            $blog_schema = [
                '@context'      => 'https://schema.org',
                '@type'         => 'BlogPosting',
                '@id'           => $permalink . '#blogposting',
                'headline'      => $title,
                'description'   => $excerpt,
                'url'           => $permalink,
                'datePublished' => $published,
                'dateModified'  => $modified,
                'author'        => [
                    '@type' => 'Person',
                    'name'  => $author_name,
                ],
                'publisher'     => [
                    '@type' => 'Organization',
                    '@id'   => 'https://buildx.com/#organization',
                ],
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id'   => $permalink . '#webpage',
                ],
            ];
            if ( $image_url ) {
                $blog_schema['image'] = [
                    '@type' => 'ImageObject',
                    'url'   => $image_url,
                ];
            }
            $schemas[] = $blog_schema;
        }

        // Later: add a branch here for your ADU floor-plan CPT (e.g. 'floor_plan')
        // with @type: 'Product' or 'House'.
    }

    // Output all schemas in one JSON-LD block
    echo "\n<script type=\"application/ld+json\">\n";
    echo wp_json_encode( $schemas, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
    echo "\n</script>\n";
}



}); // End initial action, so taxonomies are registered before loading modules below.


// Load independent modules
require_once get_stylesheet_directory() . '/inc/popularity.php';
require_once get_stylesheet_directory() . '/inc/learning-center.php';
require_once get_stylesheet_directory() . '/inc/floor-plans.php';
require_once get_stylesheet_directory() . '/inc/popularity-dashboard.php';

// (no PHP close tags in functions; safe on all PHP 7+)
