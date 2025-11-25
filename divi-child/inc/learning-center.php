<?php
// BuildX Learning Center – core taxonomies, query, and rendering logic.

add_action('init', function () {
  // Content (Article, Podcast, Video, etc.)
  register_taxonomy('format_type', 'post', array(
    'labels' => array(
      'name' => 'Content','singular_name' => 'Content Type','menu_name' => 'Content',
      'search_items' => 'Search Content Types','all_items' => 'All Content Types',
      'edit_item' => 'Edit Content Type','view_item' => 'View Content Type',
      'update_item' => 'Update Content Type','add_new_item' => 'Add New Content Type',
      'new_item_name' => 'New Content Type Name',
    ),
    'public' => true,'hierarchical' => false,'show_ui' => true,
    'show_admin_column' => true,'show_in_rest' => true,'rewrite' => array('slug' => 'content'),
  ));

  // Topics (category-like)
  register_taxonomy('topic', 'post', array(
    'labels' => array(
      'name' => 'Topics','singular_name' => 'Topic','menu_name' => 'Topics',
      'search_items' => 'Search Topics','all_items' => 'All Topics',
      'edit_item' => 'Edit Topic','view_item' => 'View Topic',
      'update_item' => 'Update Topic','add_new_item' => 'Add New Topic',
      'new_item_name' => 'New Topic Name','parent_item' => 'Parent Topic','parent_item_colon' => 'Parent Topic:',
    ),
    'public' => true,'hierarchical' => true,'show_ui' => true,
    'show_admin_column' => true,'show_in_rest' => true,'rewrite' => array('slug' => 'topic'),
  ));

  // Audience
  register_taxonomy('audience', 'post', array(
    'labels' => array(
      'name' => 'Audience','singular_name' => 'Audience','menu_name' => 'Audience',
      'search_items' => 'Search Audiences','all_items' => 'All Audiences',
      'edit_item' => 'Edit Audience','view_item' => 'View Audience',
      'update_item' => 'Update Audience','add_new_item' => 'Add New Audience',
      'new_item_name' => 'New Audience Name',
    ),
    'public' => true,'hierarchical' => false,'show_ui' => true,
    'show_admin_column' => true,'show_in_rest' => true,'rewrite' => array('slug' => 'audience'),
  ));

  // Level
  register_taxonomy('level', 'post', array(
    'labels' => array(
      'name' => 'Level','singular_name' => 'Level','menu_name' => 'Level',
      'search_items' => 'Search Levels','all_items' => 'All Levels',
      'edit_item' => 'Edit Level','view_item' => 'View Level',
      'update_item' => 'Update Level','add_new_item' => 'Add New Level',
      'new_item_name' => 'New Level Name',
    ),
    'public' => true,'hierarchical' => false,'show_ui' => true,
    'show_admin_column' => true,'show_in_rest' => true,'rewrite' => array('slug' => 'level'),
  ));
});


if ( ! function_exists('buildx_lr_get_query_args') ) {
  function buildx_lr_get_query_args($params = null) {
    // Default to the real request when no params are provided
    $params = is_array($params) ? $params : $_GET;

    $tax_query = array();
    $map = array(
      'topic'   => 'topic',
      'format'  => 'format_type',
      'content'  => 'format_type',   // alias
      'audience'=> 'audience',
      'level'   => 'level',
    );

    foreach ($map as $param => $taxonomy) {
      if (!empty($params[$param])) {
        $vals = (array) $params[$param];
        $vals = array_map('sanitize_text_field', $vals);


        // Special rule: Content = "All" means no filter
        if ($param === 'format') {
          $vals = array_values(array_filter($vals, function($v){ return strtolower($v) !== 'all'; }));
        }

        if (!empty($vals)) {
          $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $vals,
            'operator' => 'AND',
          );
        }
      }
    }
    if (count($tax_query) > 1) { $tax_query = array_merge(array('relation' => 'AND'), $tax_query); }

        // Resolve current page from request params first, then fall back to query var
    $paged = 0;
    if (isset($params['paged'])) { $paged = (int) $params['paged']; }
    // WP sometimes uses 'page' in certain contexts; accept it as an alias
    if ($paged < 1 && isset($params['page'])) { $paged = (int) $params['page']; }
    if ($paged < 1) { $paged = (int) get_query_var('paged'); }
    if ($paged < 1) { $paged = 1; }


        // Exclude "Floor Plans" category from Learning Center
    $fp_term = get_term_by('slug', 'floor-plans', 'category');
    if (!$fp_term) { $fp_term = get_term_by('name', 'Floor Plans', 'category'); }
    $cat_not_in = array();
    if ($fp_term && isset($fp_term->term_id)) { $cat_not_in[] = (int) $fp_term->term_id; }

    // Use a custom "q" param (and, if present, legacy "s") for the search term,
    // so the page URL itself never uses ?s= and collides with WP's global search.
    $raw_search = '';
    if (!empty($params['q'])) {
      $raw_search = $params['q'];
    } elseif (!empty($params['s'])) {
      // legacy compatibility if anything still sends ?s=
      $raw_search = $params['s'];
    }

    return array(
      'post_type'      => 'post',
      'post_status'    => 'publish',
      's'              => $raw_search !== '' ? sanitize_text_field($raw_search) : '',
      'tax_query'        => $tax_query ? $tax_query : array(),
      'category__not_in' => $cat_not_in,
      // NEW: order Learning Center by popularity score, newest popular first
      'meta_key'         => defined( 'BUILDX_POP_META_LC_SCORE' )
                            ? BUILDX_POP_META_LC_SCORE
                            : 'lc_pop_score',
      'orderby'          => 'meta_value_num',
      'order'            => 'DESC',
      'posts_per_page' => 12,
      'paged'          => max(1, $paged),
    );
  }
}


if ( ! function_exists('buildx_lr_render_cards') ) {
  function buildx_lr_render_cards($q) {
    ob_start();

    echo '<div id="lr-grid" class="lr-grid">';

    if ($q->have_posts()) {
      while ($q->have_posts()) {
        $q->the_post();

        echo '<article class="lr-card">';

        // Detect "Video" posts and/or find an actual URL from Divi/custom fields/content
        $video_url = buildx_lr_find_video_url(get_the_ID());
        $is_video  = $video_url || has_term('video', 'format_type', get_the_ID());

        echo '<div class="lr-media">';

        // Image still links to the article
        echo '<a class="lr-thumb" href="'.esc_url(get_permalink()).'" aria-label="'.esc_attr(get_the_title()).'">';
        if (has_post_thumbnail()) {
          the_post_thumbnail('medium_large', ['class'=>'lr-img','loading'=>'lazy','decoding'=>'async']);
        } else {
          echo '<div class="lr-thumb-ph" aria-hidden="true"></div>';
        }
        echo '</a>';

        // Play overlay only for videos with a URL
        if ($is_video && $video_url) {
          echo '<button class="lr-play" type="button" aria-label="Play video" data-video="'.esc_url($video_url).'"></button>';
        }

        echo '</div>'; // .lr-media


        echo '<div class="lr-card-body">';
          echo '<div class="lr-badges">';
            $taxes = array('topic','format_type','audience','level');
            foreach ($taxes as $tx) {
              $terms = get_the_terms(get_the_ID(), $tx);
              if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $t) {
                  echo '<span class="lr-badge lr-badge--'.esc_attr($tx).'">'.esc_html($t->name).'</span>';
                }
              }
            }
          echo '</div>';

          echo '<h3 class="lr-title"><a href="'.esc_url(get_permalink()).'">'.esc_html(get_the_title()).'</a></h3>';
          echo '<p class="lr-excerpt">'.esc_html(wp_strip_all_tags(get_the_excerpt())).'</p>';
        echo '</div>'; // body

        echo '</article>';
      }
    } else {
      echo '<p class="lr-empty">No results. Adjust filters.</p>';
    }

    // echo '</div>'; // grid

    $big = 999999999;
    // Derive current from request first; fallback to query var
    $current_paged = 1;
    if (isset($_GET['paged']))       { $current_paged = max(1, (int) $_GET['paged']); }
    elseif (isset($_GET['page']))    { $current_paged = max(1, (int) $_GET['page']); }
    elseif (get_query_var('paged'))  { $current_paged = max(1, (int) get_query_var('paged')); }


    // Recompute total pages from found_posts to avoid disappearing pager
    $ppp         = max(1, (int) $q->get('posts_per_page'));
    $total_pages = max(1, (int) ceil( (int) $q->found_posts / $ppp ));

    // Build base like ?paged=%#% and preserve all other query args
    $base_url = remove_query_arg( array( 'paged', 'page' ) );
    $base     = esc_url( add_query_arg( 'paged', '%#%', $base_url ) );

    $links = paginate_links(array(
      'base'      => $base,
      'format'    => '',
      'current'   => $current_paged,
      'total'     => $total_pages,
      'type'      => 'list',
      'prev_next' => true,
    ));


    if ($links) {
      echo '<nav class="lr-pagination">'.$links.'</nav>';
    }
echo '<script>
(function(){
  var f = document.getElementById("lr-filters");
  if (!f) return;

  // If our main JS is present, let it handle things.
  if (window.buildxLr && window.fetch) return;

  // Fallback: auto-submit on any change + "All" exclusivity for Content.
  f.addEventListener("change", function(e){
    var el = e.target;
    if (el && el.name === "format[]") {
      var all = f.querySelector(\'input[name="format[]"][value="all"]\');
      if (all) {
        if (el.value.toLowerCase() === "all" && el.checked) {
          f.querySelectorAll(\'input[name="format[]"]\').forEach(function(cb){
            if (cb !== all) cb.checked = false;
          });
        } else if (el.checked && all.checked) {
          all.checked = false;
        }
      }
    }
    // submit without full page reload (modern browsers)
    if (f.requestSubmit) f.requestSubmit(); else f.submit();
  }, {passive:true});
})();
</script>';

    wp_reset_postdata();
    return trim(ob_get_clean());
  }
}



if ( ! function_exists('buildx_lr_filters_shortcode') ) {
  function buildx_lr_filters_shortcode() {
    $taxes = array(
      'format'   => array('label' => 'Content',  'taxonomy' => 'format_type'),
      'topic'    => array('label' => 'Topics',   'taxonomy' => 'topic'),
      'audience' => array('label' => 'Audience', 'taxonomy' => 'audience'),
      'level'    => array('label' => 'Level',    'taxonomy' => 'level'),
    );


    ob_start();

        echo '<form id="lr-filters" class="lr-filters" method="get">';

      echo '<div class="lr-search">';
        echo '<label for="lr-q" class="lr-label">Search</label>';
        // Use "q" as the Learning Center search param to avoid triggering
        // WordPress's global ?s= search (which was causing 404s on this page).
        $q = '';
        if (isset($_GET['q'])) {
          $q = esc_attr($_GET['q']);
        } elseif (isset($_GET['s'])) {
          // Legacy URLs: if someone still hits ?s=, at least show it in the box
          $q = esc_attr($_GET['s']);
        }
        echo '<input id="lr-q" type="search" name="q" value="'.$q.'" placeholder="Search Learning Center" />';
      echo '</div>';


      foreach ($taxes as $param => $meta) {
        // Fetch terms (show even if not yet assigned)
        $terms = get_terms(array(
          'taxonomy'   => $meta['taxonomy'],
          'hide_empty' => false,
          'orderby'    => 'name',
          'order'      => 'ASC',
        ));

        // If this is the Content taxonomy, put "All" first
        if ($meta['taxonomy'] === 'format_type' && !is_wp_error($terms) && !empty($terms)) {
          $first = array(); $rest = array();
          foreach ($terms as $t) {
            if (strtolower($t->slug) === 'all') { $first[] = $t; } else { $rest[] = $t; }
          }
          $terms = array_merge($first, $rest);
        }

        // Custom sidebar ordering for Topics (display-only)
        if ($meta['taxonomy'] === 'topic' && !is_wp_error($terms) && !empty($terms)) {
          // Prefer slugs (robust if names change)
          $desired_slugs = [
            'preliminary-planning-phase',
            'financing-cost-planning',
            'design-pre-construction',
            'permitting-approvals',
            'construction-phase',
            'finishing-handover',
            'living-with-your-adu',
            'customer-stories-testimonials',
          ];
          $rank = array_flip($desired_slugs);

          usort($terms, function($a, $b) use ($rank) {
            $ai = $rank[$a->slug] ?? PHP_INT_MAX;
            $bi = $rank[$b->slug] ?? PHP_INT_MAX;
            // unknown terms: keep after ordered ones, alphabetically
            return ($ai <=> $bi) ?: strnatcasecmp($a->name, $b->name);
          });
        }


        if (empty($terms) || is_wp_error($terms)) { continue; }
        $active = isset($_GET[$param]) ? (array) $_GET[$param] : array();
        $active = array_map('sanitize_text_field', $active);

        echo '<fieldset class="lr-fieldset">';
          echo '<legend>'.esc_html($meta['label']).'</legend>';
          echo '<ul class="lr-list">';
            foreach ($terms as $t) {
              $checked = in_array($t->slug, $active, true) ? ' checked' : '';
              echo '<li><label>';
                echo '<input type="checkbox" name="'.esc_attr($param).'[]" value="'.esc_attr($t->slug).'"'.$checked.' />';
                echo '<span>'.esc_html($t->name).'</span>';
              echo '</label></li>';
            }
          echo '</ul>';
        echo '</fieldset>';
      }

      // Remove both the new "q" param and legacy "s" when resetting.
      $keys = array_merge(array_keys($taxes), array('q','s','paged'));
      $reset_url = esc_url( remove_query_arg($keys) );
      echo '<div class="lr-actions">';
        echo '<button type="submit" class="lr-btn">Apply</button>';
        echo '<a class="lr-btn lr-btn--ghost" href="'.$reset_url.'">Reset</a>';
      echo '</div>';


    echo '</form>';

    return trim(ob_get_clean());
  }
}
add_shortcode('lr_filters', 'buildx_lr_filters_shortcode');

if ( ! function_exists('buildx_lr_grid_shortcode') ) {
  function buildx_lr_grid_shortcode() {
    $q = new WP_Query( buildx_lr_get_query_args() );
    return buildx_lr_render_cards($q);
  }
}
add_shortcode('lr_grid', 'buildx_lr_grid_shortcode');


/**
* REST endpoint to return grid HTML for AJAX filtering
*/
add_action('rest_api_init', function () {
register_rest_route('vigilance/v1', '/learning', [
'methods' => 'GET',
  'callback' => function (WP_REST_Request $req) {
    // Build args from the request parameters *without* mutating $_GET
    $params = $req->get_params();
    $q      = new WP_Query( buildx_lr_get_query_args($params) );
    $html   = buildx_lr_render_cards($q);
    return new WP_REST_Response( ['html' => $html], 200 );
  },

'permission_callback' => '__return_true',
]);
});


// Enqueue LC assets reliably (Divi-safe)
add_action('wp_enqueue_scripts', function () {
  if (is_admin()) return;

  $css = get_stylesheet_directory() . '/learning-center.css';
  $js  = get_stylesheet_directory() . '/learning-center.js';

  wp_enqueue_style(
    'buildx-lr',
    get_stylesheet_directory_uri() . '/learning-center.css',
    array(),
    file_exists($css) ? filemtime($css) : '1.0'
  );

  wp_enqueue_script(
    'buildx-lr',
    get_stylesheet_directory_uri() . '/learning-center.js',
    array(),                                   // no deps
    file_exists($js) ? filemtime($js) : '1.0', // cache-bust
    true
  );

  wp_localize_script('buildx-lr', 'buildxLr', array(
    'endpoint' => esc_url_raw(rest_url('vigilance/v1/learning')),
  ));
});


// Sidebar Shortcode for Articles (no badges fetched or rendered)
// [lr_post_list match="topic|format_type|audience|level|none" limit="10" title="" hide_when_empty="0"]
add_shortcode('lr_post_list', function ($atts) {
  if (is_admin()) return '';

  $a = shortcode_atts([
    'limit'           => 10,
    'title'           => '',
    'match'           => 'topic',
    'hide_when_empty' => '0',
  ], $atts, 'lr_post_list');

  $limit = max(1, (int)$a['limit']);
  $match = strtolower(trim($a['match']));

  // Map friendly names -> actual taxonomy slugs
  $tax_map = [
    'topic'       => 'topic',
    'format'      => 'format_type',
    'format_type' => 'format_type',   // "Content"
    'content'     => 'format_type',
    'audience'    => 'audience',
    'level'       => 'level',
    'none'        => false,
  ];
  $taxonomy = $tax_map[$match] ?? 'topic';

  // Default titles per match
  $default_titles = [
    'topic'       => 'More in this topic',
    'format_type' => 'More of this Content',
    'audience'    => 'More in this Audience',
    'level'       => 'More from this Level',
    'none'        => 'More from Learning Center',
  ];
  $title = $a['title'] !== '' ? $a['title'] : ($default_titles[$taxonomy ?: 'none'] ?? 'More');

  $post_id  = get_queried_object_id();
  $tax_query = [];

  if ($taxonomy && $post_id && is_singular('post')) {
    // Match posts that share ANY of the current post's terms in the chosen taxonomy
    $slugs = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'slugs']);
    if (!is_wp_error($slugs) && !empty($slugs)) {
      $tax_query[] = [
        'taxonomy' => $taxonomy,
        'field'    => 'slug',
        'terms'    => array_map('sanitize_title', $slugs),
        'operator' => 'IN',
      ];
    }
  }

  $q = new WP_Query([
    'post_type'           => 'post',
    'posts_per_page'      => $limit,
    'post_status'         => 'publish',
    'ignore_sticky_posts' => true,
    'post__not_in'        => $post_id ? [$post_id] : [],
    'orderby'             => 'date',
    'order'               => 'DESC',
    'tax_query'           => $tax_query,
    'no_found_rows'       => true,
  ]);

  if (!$q->have_posts() && (bool)$a['hide_when_empty']) {
    wp_reset_postdata();
    return '';
  }

  ob_start(); ?>
  <aside class="lr-box lr-postlist-wrap <?php echo $taxonomy ? 'lr-postlist--'.esc_attr($taxonomy) : ''; ?> lr-postlist--no-badges">
    <div class="lr-box-head"><?php echo esc_html($title); ?></div>
    <?php if ($q->have_posts()) : ?>
      <ul class="lr-postlist">
        <?php while ($q->have_posts()) : $q->the_post(); ?>
          <li class="lr-postlist-item">
            <a class="lr-postlink" href="<?php echo esc_url(get_permalink()); ?>">
              <span class="lr-posttitle"><?php echo esc_html(get_the_title()); ?></span>
            </a>
            </li>
        <?php endwhile; ?>
      </ul>
    <?php else: ?>
      <p class="lr-empty" style="margin:4px 6px 0;">No articles yet.</p>
    <?php endif; wp_reset_postdata(); ?>
  </aside>
  <?php
  return trim(ob_get_clean());
});

// (no PHP close tags in functions; safe on all PHP 7+)