<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * BuildX Popularity Analytics (admin-only dashboard)
 * - Aggregates _bx_lc_hist / _bx_adu_hist
 * - Tools → Popularity Analytics
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// Require popularity core to be loaded first.
if ( ! function_exists( 'buildx_pop_today' ) ) {
  // Popularity module not present; do not register dashboard.
  return;
}

/**
 * Aggregate history rows for a given history meta key.
 *
 * @param string $meta_key  e.g. BUILDX_POP_META_LC_HIST or BUILDX_POP_META_ADU_HIST
 * @return array { labels:[], views:[], clicks:[], score:[] }
 */
function buildx_pop_aggregate_hist_for_meta( $meta_key ) {
  // Reuse core popularity window helpers so we stay in sync.
  $cutoff = buildx_pop_cutoff();   // 'Y-m-d'
  $today  = buildx_pop_today();    // 'Y-m-d'

  // Initialize buckets for all days in the window (so chart has a continuous x-axis).
  $dates  = array();
  $cursor = strtotime( $cutoff );
  $end    = strtotime( $today );

  while ( $cursor <= $end ) {
    $d = date( 'Y-m-d', $cursor );
    $dates[ $d ] = array(
      'views'  => 0,
      'clicks' => 0,
    );
    $cursor = strtotime( '+1 day', $cursor );
  }

  // Query all posts that have this history meta key.
  $q = new WP_Query( array(
    'post_type'      => 'any',
    'posts_per_page' => -1,
    'no_found_rows'  => true,
    'fields'         => 'ids',
    'meta_query'     => array(
      array(
        'key'     => $meta_key,
        'compare' => 'EXISTS',
      ),
    ),
  ) );

  if ( $q->have_posts() ) {
    foreach ( $q->posts as $pid ) {
      $hist = get_post_meta( $pid, $meta_key, true );
      if ( ! is_array( $hist ) ) {
        continue;
      }

      foreach ( $hist as $row ) {
        if ( empty( $row['d'] ) ) {
          continue;
        }
        $d = $row['d'];

        // Only include dates within the current window.
        if ( $d < $cutoff || $d > $today ) {
          continue;
        }

        if ( ! isset( $dates[ $d ] ) ) {
          $dates[ $d ] = array( 'views' => 0, 'clicks' => 0 );
        }

        $dates[ $d ]['views']  += intval( $row['v'] ?? 0 );
        $dates[ $d ]['clicks'] += intval( $row['c'] ?? 0 );
      }
    }
  }
  wp_reset_postdata();

  // Turn associative map into ordered arrays.
  $labels = array();
  $views  = array();
  $clicks = array();
  $score  = array();

  foreach ( $dates as $d => $totals ) {
    $labels[] = $d;
    $v        = intval( $totals['views'] );
    $c        = intval( $totals['clicks'] );
    $views[]  = $v;
    $clicks[] = $c;

    // Score uses the same weights as the popularity engine.
    $score[]  = ( $v * BUILDX_POP_VIEW_WT ) + ( $c * BUILDX_POP_CLICK_WT );
  }

  return array(
    'labels' => $labels,
    'views'  => $views,
    'clicks' => $clicks,
    'score'  => $score,
  );
}

/**
 * Get the top X posts for a given meta score key.
 *
 * @param string $score_meta_key e.g. BUILDX_POP_META_LC_SCORE or BUILDX_POP_META_ADU_SCORE
 * @param int    $limit          Number of posts to retrieve.
 * @return array Array of objects with post_title, permalink, and score.
 */
function buildx_pop_get_top_posts( $score_meta_key, $limit = 5 ) {
    $q = new WP_Query( array(
        'post_type'      => 'any',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'meta_key'       => $score_meta_key,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    $results = array();
    if ( $q->have_posts() ) {
        foreach ( $q->posts as $pid ) {
            $score = get_post_meta( $pid, $score_meta_key, true );
            $post = get_post( $pid );
            if ( $post && $score > 0 ) {
                $results[] = (object) array(
                    'title'     => get_the_title( $post ),
                    'permalink' => get_permalink( $post ),
                    'score'     => intval( $score ),
                );
            }
        }
    }
    wp_reset_postdata();
    return $results;
}


/**
 * Convenience wrappers for LC & ADU datasets.
 */
function buildx_pop_get_lc_aggregate() {
  return buildx_pop_aggregate_hist_for_meta( BUILDX_POP_META_LC_HIST );
}

function buildx_pop_get_adu_aggregate() {
  return buildx_pop_aggregate_hist_for_meta( BUILDX_POP_META_ADU_HIST );
}

/**
 * Register the Tools → Popularity Analytics page.
 */
add_action( 'admin_menu', function () {
  add_management_page(
    'Popularity Analytics',
    'Popularity Analytics',
    'manage_options',
    'buildx-popularity-analytics',
    'buildx_pop_analytics_page_render'
  );
} );

/**
 * Render the analytics page (HTML + data bootstrap).
 */
function buildx_pop_analytics_page_render() {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  $lc_data  = buildx_pop_get_lc_aggregate();
  $adu_data = buildx_pop_get_adu_aggregate();

  // New: Get top posts data
  $lc_top_posts = buildx_pop_get_top_posts( BUILDX_POP_META_LC_SCORE, 5 );
  $adu_top_posts = buildx_pop_get_top_posts( BUILDX_POP_META_ADU_SCORE, 5 );

  // Bundle all datasets into one payload for JS.
  $payload = array(
    'lc'  => array_merge( $lc_data, [ 'top_posts' => $lc_top_posts ] ),
    'adu' => array_merge( $adu_data, [ 'top_posts' => $adu_top_posts ] ),
    // Human labels for the dropdown.
    'labels' => array(
      'lc'  => 'Learning Center',
      'adu' => 'ADU Floor Plans',
    ),
  );

  ?>
  <div class="wrap">
    <h1>Popularity Analytics (Last <?php echo esc_html( BUILDX_POP_WINDOW_DAYS ); ?> Days)</h1>
    <p>
      These charts aggregate <strong>views</strong> and <strong>clicks</strong> per day
      across all posts tracked by the popularity engine.
    </p>

    <div style="margin:16px 0;">
      <label for="buildx-pop-dataset">
        Dataset:
      </label>
      <select id="buildx-pop-dataset">
        <option value="lc"><?php echo esc_html( $payload['labels']['lc'] ); ?></option>
        <option value="adu"><?php echo esc_html( $payload['labels']['adu'] ); ?></option>
      </select>
    </div>

    <div style="max-width: 900px; background:#fff; padding:16px; border:1px solid #ddd; border-radius:8px;">
      <canvas id="buildx-pop-chart" width="900" height="400">
        Your browser does not support the HTML5 canvas tag.
      </canvas>
    </div>

    <p style="margin-top:16px; color:#555;">
      Views are drawn as a solid line. Clicks are drawn as a dashed line.
      Combined score (views × <?php echo esc_html( BUILDX_POP_VIEW_WT ); ?> +
      clicks × <?php echo esc_html( BUILDX_POP_CLICK_WT ); ?>) is available to
      the script but not shown to keep the chart readable.
    </p>

    <h2 style="margin-top: 30px; margin-bottom: 10px;">Top 5 Posts (Last <?php echo esc_html( BUILDX_POP_WINDOW_DAYS ); ?> Days)</h2>
    <div id="buildx-pop-top-list" class="widefat" style="max-width: 900px;">
      <div style="padding: 10px; text-align: center; color: #888;">Loading...</div>
    </div>
    </div>

  <script>
    window.buildxPopAnalytics = <?php echo wp_json_encode( $payload ); ?>;
  </script>
  <?php
}

/**
 * Enqueue the dashboard JS only on our Tools page.
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
  if ( $hook !== 'tools_page_buildx-popularity-analytics' ) {
    return;
  }

  // Ensure Chart.js is registered/available if you decide to use it in the future,
  // even though you are currently drawing with canvas directly.
  
  wp_enqueue_script(
    'buildx-pop-analytics',
    get_stylesheet_directory_uri() . '/assets/js/popularity-dashboard.js',
    array(),
    '1.0',
    true
  );

} );
