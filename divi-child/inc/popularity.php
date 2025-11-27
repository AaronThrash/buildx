<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Popularity Engines for ADU Floor Plans and Learning Center.


/* -----------------------------------------------------------
 * Floor Plans: Popularity Engine (views + clicks, rolling 7 days)
 * ----------------------------------------------------------- */

if ( ! defined('ADU_POP_DAYS') )  define('ADU_POP_DAYS', 7);   // rolling window
if ( ! defined('ADU_VIEW_WEIGHT') )  define('ADU_VIEW_WEIGHT', 1);
if ( ! defined('ADU_CLICK_WEIGHT') ) define('ADU_CLICK_WEIGHT', 2);
if ( ! defined('ADU_KEEP_DAYS') )    define('ADU_KEEP_DAYS', 30); // history cap
// Floor Plans live as posts in category "floor-plans"
if ( ! defined('ADU_CPT') )          define('ADU_CPT', 'post');


// ---- core storage: bump today's counter and keep last 30 days ----
function adu_fp_bump_event( $post_id, $kind = 'view' ){
    if ( get_post_type($post_id) !== ADU_CPT ) return;
    // Only track posts that are part of the Floor Plans library
    if ( ! has_category( 'floor-plans', $post_id ) ) return;

    $key = 'adu_daily_events';

    $data = get_post_meta($post_id, $key, true);
    if (!is_array($data)) $data = [];

    $today = wp_date('Y-m-d'); // site TZ
    if (!isset($data[$today])) $data[$today] = ['view'=>0,'click'=>0];

    if ($kind === 'click') $data[$today]['click']++;
    else $data[$today]['view']++;

    // trim > ADU_KEEP_DAYS
    krsort($data, SORT_STRING);
    $data = array_slice($data, 0, ADU_KEEP_DAYS, true);
    update_post_meta($post_id, $key, $data);
}

// ---- auto-track single plan views ----
add_action('wp', function(){
    if (is_singular(ADU_CPT)) {
        // optional: de-dup same user for 2 hours
        $pid = get_the_ID();
        $cookie = 'adu_seen_' . $pid;
        if (empty($_COOKIE[$cookie])) {
            adu_fp_bump_event($pid, 'view');
            setcookie($cookie, '1', time()+2*HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }
    }
});

// ---- nightly recalc of 7-day weighted popularity ----
function adu_fp_recalc_popularity(){
    $floor_cat = get_category_by_slug('floor-plans');
    $cat_in    = $floor_cat ? [ $floor_cat->term_id ] : [];

    $q = new WP_Query([
        'post_type'      => ADU_CPT,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'category__in'   => $cat_in,
    ]);

    if (!$q->have_posts()) return;

    $since = new DateTimeImmutable( wp_date('Y-m-d') );
    $days = [];
    for ($i=0; $i<ADU_POP_DAYS; $i++) {
        $days[] = $since->modify("-{$i} day")->format('Y-m-d');
    }

    foreach ($q->posts as $pid){
        $data = get_post_meta($pid, 'adu_daily_events', true);
        $score = 0;
        if (is_array($data)) {
            foreach ($days as $d) {
                if (!empty($data[$d])) {
                    $v = intval($data[$d]['view']  ?? 0);
                    $c = intval($data[$d]['click'] ?? 0);
                    $score += ($v * ADU_VIEW_WEIGHT) + ($c * ADU_CLICK_WEIGHT);
                }
            }
        }
        update_post_meta($pid, 'adu_pop_score', intval($score));
    }
}
add_action('adu_fp_recalc_popularity', 'adu_fp_recalc_popularity');

// ---- schedule daily cron (first run within ~10min, then daily) ----
add_action('init', function(){
    if (!wp_next_scheduled('adu_fp_recalc_popularity')) {
        wp_schedule_event( time() + 600, 'daily', 'adu_fp_recalc_popularity' );
    }
});

// ---- AJAX: track grid clicks ----
function adu_fp_ajax_click(){
    check_ajax_referer('adu_fp', 'nonce');
    $pid = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if ($pid && get_post_type($pid) === ADU_CPT){
        adu_fp_bump_event($pid, 'click');
        wp_send_json_success(['ok'=>true]);
    }
    wp_send_json_error(['ok'=>false]);
}
add_action('wp_ajax_adu_fp_click', 'adu_fp_ajax_click');
add_action('wp_ajax_nopriv_adu_fp_click', 'adu_fp_ajax_click');

// ---- enqueue tiny inline JS to send click events ----
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_script('jquery');
    $inline = "
    (function($){
      $(document).on('click','.bx-view-link[data-postid]',function(){
        var pid = $(this).data('postid');
        if(!pid) return;
        try{
          navigator.sendBeacon
            ? (function(){
                var fd = new FormData();
                fd.append('action','adu_fp_click');
                fd.append('nonce','" . wp_create_nonce('adu_fp') . "');
                fd.append('post_id', pid);
                navigator.sendBeacon('" . esc_url(admin_url('admin-ajax.php')) . "', fd);
              })()
            : $.post('" . esc_url(admin_url('admin-ajax.php')) . "', {action:'adu_fp_click', nonce:'" . wp_create_nonce('adu_fp') . "', post_id: pid});
        }catch(e){}
      });
    })(jQuery);
    ";
    wp_add_inline_script('jquery', $inline, 'after');
});


/* ================================================================
   BuildX Popularity Core (views/clicks → 30d score)
   ================================================================= */

if ( ! function_exists('buildx_pop_today') ) {

  /* ---------- Config (adjust without touching code below) ---------- */
  define('BUILDX_POP_LC_CAT_SLUG', 'learning-center');   // posts that belong to LC
  define('BUILDX_POP_ADU_LOCATOR', 'adu');               // fuzzy locator for ADU posts (post type or taxonomy slug substring)
  define('BUILDX_POP_WINDOW_DAYS', 30);                  // rolling window
  define('BUILDX_POP_VIEW_WT', 1);
  define('BUILDX_POP_CLICK_WT', 3);

  // meta keys (kept separate so ADU and LC can evolve independently)
  define('BUILDX_POP_META_LC_HIST',   '_bx_lc_hist');    // [{d:'YYYY-MM-DD', v:int, c:int}, ...]
  define('BUILDX_POP_META_LC_SCORE',  'lc_pop_score');
  define('BUILDX_POP_META_ADU_HIST',  '_bx_adu_hist');
  define('BUILDX_POP_META_ADU_SCORE', 'adu_pop_score');

  /* ---------- Small helpers ---------- */
  function buildx_pop_today(){ return current_time('Y-m-d'); }
  function buildx_pop_cutoff(){ return gmdate('Y-m-d', strtotime('-'.BUILDX_POP_WINDOW_DAYS.' days', current_time('timestamp'))); }

  function buildx_pop_is_lc($post){
    return has_category(BUILDX_POP_LC_CAT_SLUG, $post);
  }

  // Heuristic for ADU plans: match CPT or tax slugs containing “adu”
  function buildx_pop_is_adu($post){
    $pt = get_post_type($post);
    if (strpos($pt ?: '', BUILDX_POP_ADU_LOCATOR) !== false) return true;
    $taxes = get_post_taxonomies($post);
    foreach ($taxes as $tx){
      if (strpos($tx, BUILDX_POP_ADU_LOCATOR)!==false){
        $terms = wp_get_post_terms($post->ID, $tx, ['fields'=>'slugs']);
        foreach($terms as $slug){ if (strpos($slug, BUILDX_POP_ADU_LOCATOR)!==false) return true; }
      }
    }
    // common fallback: page category like “ADU Floor Plans”
    if (has_category('adu-floor-plans', $post) || has_category('floor-plans', $post)) return true;
    return false;
  }

  // read+prune hist (array of day rows with keys d,v,c)
  function buildx_pop_get_hist($post_id, $is_lc){
    $key  = $is_lc ? BUILDX_POP_META_LC_HIST : BUILDX_POP_META_ADU_HIST;
    $hist = get_post_meta($post_id, $key, true);
    if (!is_array($hist)) $hist = [];
    $cut  = buildx_pop_cutoff();
    // keep only last WINDOW_DAYS + today
    $out = [];
    foreach ($hist as $row){
      if (!empty($row['d']) && $row['d'] >= $cut) $out[] = ['d'=>$row['d'], 'v'=>intval($row['v']??0), 'c'=>intval($row['c']??0)];
    }
    return $out;
  }

  function buildx_pop_save_hist($post_id, $is_lc, $hist){
    $key = $is_lc ? BUILDX_POP_META_LC_HIST : BUILDX_POP_META_ADU_HIST;
    update_post_meta($post_id, $key, array_values($hist));
  }

  function buildx_pop_bump($post_id, $is_lc, $field /* 'v' or 'c' */){
    $today = buildx_pop_today();
    $hist  = buildx_pop_get_hist($post_id, $is_lc);
    $found = false;
    foreach ($hist as &$row){
      if ($row['d'] === $today){
        $row[$field] = intval($row[$field] ?? 0) + 1;
        $found = true;
        break;
      }
    }
    if (!$found){
      $hist[] = ['d'=>$today, 'v'=> ($field==='v'?1:0), 'c'=> ($field==='c'?1:0)];
    }
    buildx_pop_save_hist($post_id, $is_lc, $hist);
  }

  function buildx_pop_score_from_hist($hist){
    $v=0; $c=0;
    foreach ($hist as $row){ $v += intval($row['v']??0); $c += intval($row['c']??0); }
    return ($v * BUILDX_POP_VIEW_WT) + ($c * BUILDX_POP_CLICK_WT);
  }

  function buildx_pop_recompute_post($post_id){
    $is_lc  = buildx_pop_is_lc($post_id);
    $is_adu = buildx_pop_is_adu(get_post($post_id));
    if (! $is_lc && ! $is_adu) return;

    if ($is_lc){
      $hist  = buildx_pop_get_hist($post_id, true);
      $score = buildx_pop_score_from_hist($hist);
      update_post_meta($post_id, BUILDX_POP_META_LC_SCORE, $score);
    }
    if ($is_adu){
      $hist  = buildx_pop_get_hist($post_id, false);
      $score = buildx_pop_score_from_hist($hist);
      update_post_meta($post_id, BUILDX_POP_META_ADU_SCORE, $score);
    }
  }

  /* ---------- Track VIEWS (server-side) ---------- */
  add_action('template_redirect', function(){
    if (!is_singular()) return;
    $post = get_queried_object();
    if (!$post || empty($post->ID)) return;

    if ( buildx_pop_is_lc($post) ){
      buildx_pop_bump($post->ID, true, 'v');
    } elseif ( buildx_pop_is_adu($post) ){
      buildx_pop_bump($post->ID, false, 'v');
    }
  });

    /* ---------- Track CLICKS (AJAX beacon from LC JS) ---------- */
  add_action('wp_ajax_buildx_pop_click',    'buildx_pop_ajax_click');
  add_action('wp_ajax_nopriv_buildx_pop_click', 'buildx_pop_ajax_click');
  function buildx_pop_ajax_click(){
    // Security: verify nonce from the request. The JS must send a "nonce" field.
    check_ajax_referer( 'buildx_pop_click', 'nonce' );

    // Sanitize and normalize the post ID coming from $_POST.
    $pid = isset( $_POST['post_id'] )
      ? absint( wp_unslash( $_POST['post_id'] ) )
      : 0;

    if ( ! $pid ) {
      wp_send_json_error( array( 'ok' => false ), 400 );
    }

    $is_lc = buildx_pop_is_lc( $pid );

    // If beacon arrives from LC grid, it’ll be LC; if not, no-op (keeps ADU clean)
    if ( $is_lc ) {
      buildx_pop_bump( $pid, true, 'c' );
    }

    wp_send_json_success( array( 'ok' => true ) );
  }

  /* ---------- Recompute (batch) ---------- */

  function buildx_pop_query_all_lc(){
    return new WP_Query([
      'posts_per_page' => -1,
      'post_type'      => 'any',
      'tax_query'      => [[ 'taxonomy'=>'category', 'field'=>'slug', 'terms'=>[BUILDX_POP_LC_CAT_SLUG] ]],
      'no_found_rows'  => true,
      'fields'         => 'ids',
    ]);
  }
  function buildx_pop_query_all_adu(){
    // Attempt several heuristics so this “just works” on your stack
    $args = [
      'posts_per_page' => -1,
      'post_type'      => 'any',
      'no_found_rows'  => true,
      'fields'         => 'ids',
      'tax_query'      => [
        'relation' => 'OR',
        // common: a taxonomy containing "adu"
        [
          'taxonomy' => 'category',
          'field'    => 'slug',
          'terms'    => ['adu-floor-plans','adu','floor-plans'],
          'include_children' => true,
        ],
      ],
    ];
    return new WP_Query($args);
  }

  function buildx_lc_recalc_popularity(){
    $q = buildx_pop_query_all_lc();
    foreach($q->posts as $pid){ buildx_pop_recompute_post($pid); }
    wp_reset_postdata();
  }
  function buildx_adu_recalc_popularity(){
    $q = buildx_pop_query_all_adu();
    foreach($q->posts as $pid){ buildx_pop_recompute_post($pid); }
    wp_reset_postdata();
  }

  // Public hooks so other code (or Tools buttons) can call them
  add_action('buildx_lc_recalc_popularity',  'buildx_lc_recalc_popularity');
  add_action('buildx_adu_recalc_popularity', 'buildx_adu_recalc_popularity');

  /* ---------- Tools → Rebuild buttons ---------- */
  if (! defined('BUILDX_POP_TOOLS')) {
    define('BUILDX_POP_TOOLS', true);

    add_action('admin_menu', function () {
      add_management_page(
        'Rebuild Popular ADU Plans',
        'Rebuild Popular ADU Plans',
        'manage_options',
        'buildx-adu-rebuild',
        'buildx_adu_tools_page_render'
      );
      add_management_page(
        'Rebuild Popular Learning Center',
        'Rebuild Popular Learning Center',
        'manage_options',
        'buildx-lc-rebuild',
        'buildx_lc_tools_page_render'
      );
    });

    // ADU page
    function buildx_adu_tools_page_render(){
      if (! current_user_can('manage_options')) return;
      if ( isset($_POST['buildx_adu_recalc_submit']) ){
        check_admin_referer('buildx_adu_recalc_nonce');
        do_action('buildx_adu_recalc_popularity');
        echo '<div class="notice notice-success"><p>ADU popularity recomputed.</p></div>';
      }
      echo '<div class="wrap"><h1>Rebuild Popular ADU Plans</h1>';
      echo '<p>Recompute <code>'.esc_html(BUILDX_POP_META_ADU_SCORE).'</code> for all ADU posts from their last '.BUILDX_POP_WINDOW_DAYS.' days of views & clicks.</p>';
      echo '<form method="post">'; wp_nonce_field('buildx_adu_recalc_nonce');
      echo '<p><button class="button button-primary" name="buildx_adu_recalc_submit" type="submit">Recalculate Now</button></p>';
      echo '</form></div>';
    }

    // LC page
    function buildx_lc_tools_page_render(){
      if (! current_user_can('manage_options')) return;
      if ( isset($_POST['buildx_lc_recalc_submit']) ){
        check_admin_referer('buildx_lc_recalc_nonce');
        do_action('buildx_lc_recalc_popularity');
        echo '<div class="notice notice-success"><p>Learning Center popularity recomputed.</p></div>';
      }
      echo '<div class="wrap"><h1>Rebuild Popular Learning Center</h1>';
      echo '<p>Recompute <code>'.esc_html(BUILDX_POP_META_LC_SCORE).'</code> for all posts in the <code>'.esc_html(BUILDX_POP_LC_CAT_SLUG).'</code> category from their last '.BUILDX_POP_WINDOW_DAYS.' days of views & clicks.</p>';
      echo '<form method="post">'; wp_nonce_field('buildx_lc_recalc_nonce');
      echo '<p><button class="button button-primary" name="buildx_lc_recalc_submit" type="submit">Recalculate Now</button></p>';
      echo '</form></div>';
    }
  }

  /* ---------- JS localization for click beacon ---------- */
  add_action('wp_enqueue_scripts', function(){
    // If your LC JS is enqueued with another handle, adjust the handle string below
    $handle = 'buildx-learning-center'; // try to match your enqueued LC script handle
    if (wp_script_is($handle, 'enqueued') || wp_script_is($handle, 'registered')){
      wp_localize_script($handle, 'buildxPop', [ 'ajax' => admin_url('admin-ajax.php') ]);
    } else {
      // Safe fallback: expose a tiny inline object once in the footer
      add_action('wp_footer', function(){
        echo '<script>window.buildxPop=window.buildxPop||{ajax:"'.esc_js( admin_url('admin-ajax.php') ).'"};</script>';
      }, 5);
    }
  });

} // end if core not defined


// (no PHP close tags in functions; safe on all PHP 7+)


