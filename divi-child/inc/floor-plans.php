<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Floor Plans: taxonomies, shortcodes, and endpoint.

add_action('init', function () {
  // Attach to 'post' so you can keep using posts
  $common = [
    'public'            => true,
    'hierarchical'      => false,
    'show_ui'           => true,
    'show_admin_column' => true,
    'show_in_rest'      => true,
  ];

  register_taxonomy('plan_bedrooms', ['post'], array_merge($common, [
    'labels'  => ['name'=>'Bedrooms','singular_name'=>'Bedroom'],
    'rewrite' => ['slug' => 'bedrooms'],
  ]));

  register_taxonomy('plan_bathrooms', ['post'], array_merge($common, [
    'labels'  => ['name'=>'Bathrooms','singular_name'=>'Bathroom'],
    'rewrite' => ['slug' => 'bathrooms'],
  ]));

  // Use range terms like: under-600, 600-800, 800-1000, over-1000
  register_taxonomy('plan_sqft', ['post'], array_merge($common, [
    'labels'  => ['name'=>'Square Footage','singular_name'=>'Square Foot Range'],
    'rewrite' => ['slug' => 'sqft'],
  ]));

  // Porch/Garage feature flags (non-exclusive is fine): porch, garage, porch-garage, none
  register_taxonomy('plan_porch_garage', ['post'], array_merge($common, [
    'labels'  => ['name'=>'Porch / Garage','singular_name'=>'Porch/Garage'],
    'rewrite' => ['slug' => 'porch-garage'],
  ]));
});

/** Build args for Floor Plans (category "floor-plans" + our 4 taxonomies) */
function buildx_plans_get_query_args() {
  $tax_query = [];

  $map = [
    'bed'  => 'plan_bedrooms',
    'bath' => 'plan_bathrooms',
    'sqft' => 'plan_sqft',
    'pg'   => 'plan_porch_garage',
  ];

  foreach ($map as $param => $taxonomy) {
    if (!empty($_GET[$param])) {
      $vals = array_map('sanitize_text_field', (array) $_GET[$param]);
      $tax_query[] = [
        'taxonomy' => $taxonomy,
        'field'    => 'slug',
        'terms'    => $vals,
        'operator' => 'AND',
      ];
    }
  }
  if (count($tax_query) > 1) $tax_query = array_merge(['relation'=>'AND'], $tax_query);

  // Lock results to the "Floor Plans" category
  $floor_cat = get_category_by_slug('floor-plans');
  $in_cat    = $floor_cat ? [$floor_cat->term_id] : [];

  // Pagination
  $paged = 1;
  if ($qv = get_query_var('paged')) { $paged = (int)$qv; }
  elseif (isset($_GET['paged']))     { $paged = (int)$_GET['paged']; }

  return [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'category__in'   => $in_cat,          // only floor plan posts
    's'              => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
    'tax_query'      => $tax_query ?: [],
    'orderby'        => 'date',
    'order'          => 'DESC',
    'posts_per_page' => 9,                 // 3 x 3
    'paged'          => max(1, $paged),
  ];
}

/** Render cards (reuse LC classes so the same CSS applies) */
function buildx_plans_render_cards(WP_Query $q) {
  ob_start();

  echo '<div id="lr-grid" class="lr-grid">';
  if ($q->have_posts()) {
    while ($q->have_posts()) { $q->the_post();

      // Collect badges from the four plan taxonomies
      $badge_sets = [
        'plan_bedrooms'      => 'lr-badge lr-badge--format_type', // green chip
        'plan_bathrooms'     => 'lr-badge lr-badge--audience',    // purple chip
        'plan_sqft'          => 'lr-badge lr-badge--topic',       // blue chip
        'plan_porch_garage'  => 'lr-badge lr-badge--level',       // orange chip
      ];

      echo '<article class="lr-card">';
        echo '<div class="lr-media">';
          echo '<a class="lr-thumb" href="'.esc_url(get_permalink()).'" aria-label="'.esc_attr(get_the_title()).'">';
            if (has_post_thumbnail()) {
              the_post_thumbnail('medium_large', ['class'=>'lr-img','loading'=>'lazy','decoding'=>'async']);
            } else {
              echo '<div class="lr-thumb-ph" aria-hidden="true"></div>';
            }
          echo '</a>';
        echo '</div>';

        echo '<div class="lr-card-body">';
          echo '<div class="lr-badges">';
            foreach ($badge_sets as $tax => $cls) {
              $terms = get_the_terms(get_the_ID(), $tax);
              if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $t) echo '<span class="'.esc_attr($cls).'">'.esc_html($t->name).'</span>';
              }
            }
          echo '</div>';

          echo '<h3 class="lr-title"><a href="'.esc_url(get_permalink()).'">'.esc_html(get_the_title()).'</a></h3>';
          $excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words(strip_shortcodes(get_the_content()), 22);
          echo '<p class="lr-excerpt">'.esc_html($excerpt).'</p>';
        echo '</div>';
      echo '</article>';
    }
    wp_reset_postdata();
  } else {
    echo '<p class="lr-empty">No floor plans match those filters.</p>';
  }
  echo '</div>';

  // Pagination styled like LC
  $links = paginate_links([
    'current' => max(1, get_query_var('paged') ? (int)get_query_var('paged') : (isset($_GET['paged']) ? (int)$_GET['paged'] : 1)),
    'total'   => $q->max_num_pages,
    'type'    => 'list',
  ]);
  if ($links) echo '<nav class="lr-pagination">'.$links.'</nav>';

  return trim(ob_get_clean());
}

/** Force LR endpoint to the Floor Plans route when FP shortcodes are used */
function buildx_plans_force_endpoint_override() {
  $endpoint = esc_url_raw( rest_url('vigilance/v1/plans') );
  $js = 'window.buildxLr = window.buildxLr || {}; window.buildxLr.endpoint = "'.$endpoint.'";';
  if ( wp_script_is('buildx-lr','enqueued') ) {
    wp_add_inline_script('buildx-lr', $js, 'after');
  } else {
    add_action('wp_print_footer_scripts', function() use ($js){ echo "<script>{$js}</script>"; }, 99);
  }
}


/** Filters UI (same IDs/structure used by LC JS) */
function buildx_plans_filters_shortcode() {
  if (function_exists('buildx_plans_force_endpoint_override')) { buildx_plans_force_endpoint_override(); }
  $taxes = [

    'bed'  => ['label'=>'Bedrooms',       'taxonomy'=>'plan_bedrooms'],
    'bath' => ['label'=>'Bathrooms',      'taxonomy'=>'plan_bathrooms'],
    'sqft' => ['label'=>'Square Footage', 'taxonomy'=>'plan_sqft'],
    'pg'   => ['label'=>'Porch / Garage', 'taxonomy'=>'plan_porch_garage'],
  ];

  ob_start();
  echo '<form id="lr-filters" class="lr-filters" method="get">';
    echo '<div class="lr-search">';
      echo '<label for="lr-q" class="lr-label">Search</label>';
      $q = isset($_GET['s']) ? esc_attr($_GET['s']) : '';
      echo '<input id="lr-q" type="search" name="s" placeholder="Search plans…" value="'.$q.'">';
    echo '</div>';

    foreach ($taxes as $param => $meta) {
      $terms = get_terms(['taxonomy'=>$meta['taxonomy'],'hide_empty'=>true,'orderby'=>'name','order'=>'ASC']);
      if (empty($terms) || is_wp_error($terms)) continue;
      $active = isset($_GET[$param]) ? array_map('sanitize_text_field', (array)$_GET[$param]) : [];

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

    // Reset link mirrors LC behavior
    $keys = array_merge(array_keys($taxes), ['s','paged']);
    $reset_url = esc_url(remove_query_arg($keys));
    echo '<div class="lr-actions">';
      echo '<button type="submit" class="lr-btn">Apply</button>';
      echo '<a class="lr-btn lr-btn--ghost" href="'.$reset_url.'">Reset</a>';
    echo '</div>';

  echo '</form>';
  return trim(ob_get_clean());
}
add_shortcode('fp_filters', 'buildx_plans_filters_shortcode');

/** Grid shortcode */
function buildx_plans_grid_shortcode() {
  if (function_exists('buildx_plans_force_endpoint_override')) { buildx_plans_force_endpoint_override(); }
  $q = new WP_Query(buildx_plans_get_query_args());

  return buildx_plans_render_cards($q);
}
add_shortcode('fp_grid', 'buildx_plans_grid_shortcode');

/** Convenience wrapper (optional): [fp_center] prints filters+grid in one shot */
add_shortcode('fp_center', function(){
  return '<div class="lr-wrap">'.buildx_plans_filters_shortcode().buildx_plans_grid_shortcode().'</div>';
});

/** REST endpoint for AJAX filtering on Floor Plans */
add_action('rest_api_init', function () {
  register_rest_route('vigilance/v1', '/plans', [
    'methods'  => 'GET',
    'callback' => function (WP_REST_Request $req) {
      $_GET = array_merge($_GET, $req->get_params());         // mirror GET
      $q    = new WP_Query(buildx_plans_get_query_args());
      return new WP_REST_Response(['html' => buildx_plans_render_cards($q)], 200);
    },
    'permission_callback' => '__return_true',
  ]);
});

/** Use the same JS/CSS but point the endpoint at /plans on the Floor Plans page */
add_action('wp_enqueue_scripts', function () {
  if (is_page('floor-plans')) { // change to your slug/ID if needed
    wp_localize_script('buildx-lr', 'buildxLr', [
      'endpoint' => esc_url_raw(rest_url('vigilance/v1/plans')),
    ]);
  }
}, 20);


/* -----------------------------------------------------------
 * Shortcode: Popular ADU Plans (3-card strip by adu_pop_score)
 * ----------------------------------------------------------- */

function buildx_popular_adu_plans_shortcode( $atts = [] ) {

  // ADU_CPT is defined in popularity.php, which is required_once before this module in functions.php
  if ( ! defined('ADU_CPT') ) define('ADU_CPT', 'post');
  
    $atts = shortcode_atts([
    'limit'               => 3,
    'title'               => 'Our Most Popular ADU Plans',
    'see_all_url'         => 'https://buildx.com/adu-floor-plans/',
    'see_all_label'       => 'View All ADU Floor Plans',
    'primary_cta_url'     => 'https://buildx.com/adu-home-tour/',
    'primary_cta_label'   => 'Book Tour to Walk One',
    'secondary_cta_url'   => 'https://buildx.com/learning-center/',
    'secondary_cta_label' => 'Visit Our Learning Center',
    'layout'              => 'horizontal', // <-- NEW DEFAULT ATTRIBUTE
    
  ], $atts, 'buildx_popular_adu_plans' );

  // How many cards to actually show (default 3, never less than 1)
  $display_limit = intval( $atts['limit'] );
  if ( $display_limit <= 0 ) {
    $display_limit = 3;
  }

  // How many posts to pull into the pool (we want some extra so we can randomize)
  // Minimum 3; if display_limit is 3, pull at least 8 so the 3rd card can rotate.
  $pool_limit = max( $display_limit, ( $display_limit === 3 ? 8 : 3 ) );

  // Base query limited to Floor Plans category
  $floor_cat = get_category_by_slug('floor-plans');

  $cat_in    = $floor_cat ? [ $floor_cat->term_id ] : [];

    $base_args = [
    'post_type'      => ADU_CPT,
    'post_status'    => 'publish',
    'posts_per_page' => $pool_limit,
    'no_found_rows'  => true,
    'category__in'   => $cat_in,
  ];


  // Popularity-first query
  $args = $base_args;
  // Fallback if the core popularity meta key BUILDX_POP_META_ADU_SCORE isn't defined
  $adu_pop_meta_key = defined( 'BUILDX_POP_META_ADU_SCORE' ) ? BUILDX_POP_META_ADU_SCORE : 'adu_pop_score';
  
  $args['meta_key'] = $adu_pop_meta_key;
  $args['orderby']  = 'meta_value_num';
  $args['order']    = 'DESC';

  $q = new WP_Query($args);

  // Fallback to latest plans if no popularity data yet
  if ( ! $q->have_posts() ) {
    $args = $base_args;
    $args['orderby'] = 'date';
    $args['order']   = 'DESC';
    $q = new WP_Query($args);
  }

    if ( ! $q->have_posts() ) {
    return '';
  }

  // Build the pool of posts and then select cards:
  //   - First card: top 1 by score
  //   - Second card: top 2 by score (if available)
  //   - Remaining needed cards: random from the rest of the pool
  $selected_posts = [];
  $all_posts      = $q->posts; // array of WP_Post objects

  if ( ! empty( $all_posts ) ) {
    // Always take the first (most popular)
    $selected_posts[] = $all_posts[0];

    // Second card: next most popular, if available and we should show at least 2
    if ( $display_limit >= 2 && isset( $all_posts[1] ) ) {
      $selected_posts[] = $all_posts[1];
    }

    // If we still need more cards and have a remaining pool, pick them at random
    if ( $display_limit > count( $selected_posts ) && count( $all_posts ) > 2 ) {
      $pool   = array_slice( $all_posts, 2 ); // everything after top 2
      $needed = $display_limit - count( $selected_posts );

      if ( $needed >= count( $pool ) ) {
        // Not many in the pool – just append them all.
        $selected_posts = array_merge( $selected_posts, $pool );
      } else {
        $rand_keys = array_rand( $pool, $needed );
        if ( ! is_array( $rand_keys ) ) {
          $rand_keys = [ $rand_keys ];
        }
        foreach ( $rand_keys as $rk ) {
          $selected_posts[] = $pool[ $rk ];
        }
      }
    }
  }

  // Enforce the display limit in case anything above overshot
  $selected_posts = array_slice( $selected_posts, 0, $display_limit );

  // Choose layout class based on shortcode attribute (default: horizontal)
  $layout = isset($atts['layout']) ? strtolower(trim($atts['layout'])) : 'horizontal';


  switch ($layout) {
    case 'vertical':
      $layout_class = 'bx-layout--vertical';
      break;
    case 'vertical-mobile':
      $layout_class = 'bx-layout--vertical-mobile';
      break;
    default:
      $layout_class = 'bx-layout--horizontal';
      break;
  }

  ob_start(); ?>
  <section class="bx-popular-adu-wrap <?php echo esc_attr($layout_class); ?>">

    <div class="bx-popular-adu-header">
      <h2 class="bx-popular-adu-heading"><?php echo esc_html($atts['title']); ?></h2>
      <?php if ( ! empty($atts['see_all_url']) ) : ?>
        <a class="bx-popular-adu-seeall" href="<?php echo esc_url($atts['see_all_url']); ?>">
          <?php echo esc_html($atts['see_all_label']); ?>
        </a>
      <?php endif; ?>
    </div>

        <div class="bx-popular-adu-grid">
      <?php
      global $post;
      foreach ( $selected_posts as $post ) :
        setup_postdata( $post );
        $pid = get_the_ID();


        // Collect plan taxonomies for badge row (match Floor Plans layout)
        $bed_terms   = get_the_terms($pid, 'plan_bedrooms');
        $bath_terms  = get_the_terms($pid, 'plan_bathrooms');
        $sqft_terms  = get_the_terms($pid, 'plan_sqft');
        $extra_terms = get_the_terms($pid, 'plan_porch_garage'); ?>

        <article class="bx-popular-adu-card">
          <a href="<?php the_permalink(); ?>"
             class="bx-popular-adu-image bx-view-link"
             data-postid="<?php echo esc_attr($pid); ?>">
            <?php if ( has_post_thumbnail() ) :
              the_post_thumbnail('large', ['class' => 'bx-popular-adu-thumb']);
            endif; ?>
          </a>

          <div class="bx-popular-adu-body">

            <?php if (
              ( ! empty($bed_terms)   && ! is_wp_error($bed_terms) ) ||
              ( ! empty($bath_terms)  && ! is_wp_error($bath_terms) ) ||
              ( ! empty($sqft_terms)  && ! is_wp_error($sqft_terms) ) ||
              ( ! empty($extra_terms) && ! is_wp_error($extra_terms) )
            ) : ?>
              <div class="bx-popular-adu-badges">
                <?php if ( ! empty($bed_terms) && ! is_wp_error($bed_terms) ) :
                  foreach ( $bed_terms as $term ) : ?>
                    <span class="bx-fp-badge bx-fp-badge--beds">
                      <?php echo esc_html($term->name); ?>
                    </span>
                  <?php endforeach;
                endif; ?>

                <?php if ( ! empty($bath_terms) && ! is_wp_error($bath_terms) ) :
                  foreach ( $bath_terms as $term ) : ?>
                    <span class="bx-fp-badge bx-fp-badge--baths">
                      <?php echo esc_html($term->name); ?>
                    </span>
                  <?php endforeach;
                endif; ?>

                <?php if ( ! empty($sqft_terms) && ! is_wp_error($sqft_terms) ) :
                  foreach ( $sqft_terms as $term ) : ?>
                    <span class="bx-fp-badge bx-fp-badge--sqft">
                      <?php echo esc_html($term->name); ?>
                    </span>
                  <?php endforeach;
                endif; ?>

                <?php if ( ! empty($extra_terms) && ! is_wp_error($extra_terms) ) :
                  foreach ( $extra_terms as $term ) : ?>
                    <span class="bx-fp-badge bx-fp-badge--extra">
                      <?php echo esc_html($term->name); ?>
                    </span>
                  <?php endforeach;
                endif; ?>
              </div>
            <?php endif; ?>

            <h3 class="bx-popular-adu-title">
              <a href="<?php the_permalink(); ?>"
                 class="bx-view-link"
                 data-postid="<?php echo esc_attr($pid); ?>">
                <?php the_title(); ?>
              </a>
            </h3>

          </div>
        </article>


            <?php endforeach; wp_reset_postdata(); ?>
    </div>


    <div class="bx-popular-adu-actions">
      <?php if ( ! empty($atts['primary_cta_url']) ) : ?>
        <a class="bx-btn bx-btn-primary" href="<?php echo esc_url($atts['primary_cta_url']); ?>">
          <?php echo esc_html($atts['primary_cta_label']); ?>
        </a>
      <?php endif; ?>

      <?php if ( ! empty($atts['secondary_cta_url']) ) : ?>
        <a class="bx-btn bx-btn-secondary" href="<?php echo esc_url($atts['secondary_cta_url']); ?>">
          <?php echo esc_html($atts['secondary_cta_label']); ?>
        </a>
      <?php endif; ?>
    </div>
  </section>
  <?php

  return trim( ob_get_clean() );
}
add_shortcode( 'buildx_popular_adu_plans', 'buildx_popular_adu_plans_shortcode' );


// (no PHP close tags in functions; safe on all PHP 7+)
