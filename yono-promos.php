<?php
/**
 * Plugin Name: Yono Promo Codes & Games
 * Description: Promo codes with scheduling + floating widget & modal, and a Games grid with upcoming countdown, search, filter, sort, CSV import/export. Includes Media Library picker for game logos (with URL fallback) + WIDE responsive game card layout. Adds per-game SEO Review shortcode. Latest Apps list + List/Grid archives with gold stars & badges.
 * Version: 2.4.2
 * Author: Kuya Mecky Pogi
<<<<<<< HEAD
=======
 * Author URI: https://github.com/KuyaMecky
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) exit;

/* ---------------------------------------------------------
<<<<<<< HEAD
 * UTIL HELPERS
=======
 * UTIL HELPERS (shared)
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
 * --------------------------------------------------------- */
if (!function_exists('yono_iso_to_local_value')) {
  function yono_iso_to_local_value($iso){
    if (!$iso) return '';
<<<<<<< HEAD
    $ts = strtotime($iso); if (!$ts) return '';
=======
    $ts = strtotime($iso);
    if (!$ts) return '';
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
    $local = get_date_from_gmt(gmdate('Y-m-d H:i:s', $ts), 'Y-m-d H:i:s');
    return date('Y-m-d\TH:i', strtotime($local));
  }
}
if (!function_exists('yono_local_to_iso_utc')) {
  function yono_local_to_iso_utc($local_dt){
    if (!$local_dt) return '';
    $site_tz = wp_timezone();
    try {
      $dt = new DateTime($local_dt, $site_tz);
      $dt->setTimezone(new DateTimeZone('UTC'));
      return $dt->format('c');
    } catch (Exception $e) { return ''; }
  }
}
<<<<<<< HEAD

/* ---------------------------------------------------------
 * UNIVERSAL STAR RENDERER (pixel perfect, always gold)
 * --------------------------------------------------------- */
if (!function_exists('yono_render_stars')) {
  function yono_render_stars($rating, $size = 14, $wrap_class = 'y-stars'){
    $rating = floatval($rating);
    $pct = max(0, min(100, $rating ? ($rating/5)*100 : 0));
    $size = max(10, intval($size));
    $star = '<svg viewBox="0 0 24 24" width="'.$size.'" height="'.$size.'" aria-hidden="true"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
    return '<span class="'.$wrap_class.'" role="img" aria-label="'.($rating?$rating:0).'/5">'.
             '<span class="y-stars-empty">'.str_repeat($star,5).'</span>'.
             '<span class="y-stars-fill" style="width:'.$pct.'%;">'.str_repeat($star,5).'</span>'.
           '</span>';
  }
}

add_action('wp_head', function(){
  static $once = false; if ($once) return; $once = true;
  echo '<style id="yono-stars-css">
  .y-stars{position:relative;display:inline-block;line-height:0;vertical-align:middle}
  .y-stars svg{display:inline-block;margin-right:1px}
  .y-stars-empty{color:#2b3957}
  .y-stars-fill{position:absolute;inset:0;overflow:hidden;color:#fbbf24 !important}
  .y-score{font-weight:900;color:#e6edf7;font-size:12.5px;margin-left:6px;vertical-align:middle}
  </style>';
});

/* ---------------------------------------------------------
 * Global badge effects (glow / pulse) for all Yono badges
 * --------------------------------------------------------- */
add_action('wp_head', function () {
  static $once = false; if ($once) return; $once = true;
  echo '<style id="yono-badge-effects">
    .yg-badge.is-glow,
    .yl-badge.is-glow,
    .yga-badge.is-glow,
    .yg-ribbon.is-glow{
      box-shadow:0 0 0 1px rgba(251,191,36,.85),
                 0 0 18px rgba(251,191,36,.95);
    }
    .yg-badge.is-pulse,
    .yl-badge.is-pulse,
    .yga-badge.is-pulse,
    .yg-ribbon.is-pulse{
      animation:yonoBadgePulse 1.4s ease-in-out infinite;
    }
    @keyframes yonoBadgePulse{
      0%{box-shadow:0 0 0 0 rgba(251,191,36,.9);transform:translateY(0);}
      70%{box-shadow:0 0 0 10px rgba(251,191,36,0);transform:translateY(-1px);}
      100%{box-shadow:0 0 0 0 rgba(251,191,36,0);transform:translateY(0);}
    }
  </style>';
});

// Helper: returns " is-glow" / " is-pulse" / "" based on badge term meta
if (!function_exists('yono_badge_effect_class_by_slug')) {
  function yono_badge_effect_class_by_slug($slug){
    if (!$slug) return '';
    $term = get_term_by('slug', $slug, 'game_badge');
    if (!$term || is_wp_error($term)) return '';
    $effect = get_term_meta($term->term_id, '_yg_badge_effect', true);
    if ($effect === 'glow')  return ' is-glow';
    if ($effect === 'pulse') return ' is-pulse';
    return '';
  }
}

if (!function_exists('yono_badge_effect_class_by_name')) {
  function yono_badge_effect_class_by_name($name){
    if (!$name) return '';
    $term = get_term_by('name', $name, 'game_badge');
    if (!$term || is_wp_error($term)) return '';
    $effect = get_term_meta($term->term_id, '_yg_badge_effect', true);
    if ($effect === 'glow')  return ' is-glow';
    if ($effect === 'pulse') return ' is-pulse';
    return '';
  }
}

/* =========================================================
 * PROMO CODES + FLOATING WIDGET
 * ========================================================= */
class Yono_Promos {
  const CPT        = 'yono_promo';
  const TAX        = 'promo_period';
  const NONCE      = 'yono_promos_meta_nonce';
  const OPT_WIDGET = 'yono_promos_widget_options';

  public function __construct(){
    add_shortcode('yono_game_image',      [$this,'image_shortcode']);
    add_action('init',                    [$this,'register_cpt_and_tax']);
    add_action('add_meta_boxes',          [$this,'register_meta_boxes']);
    add_action('save_post',               [$this,'save_meta']);
    add_shortcode('yono_promos',          [$this,'shortcode']);
    add_action('wp_enqueue_scripts',      [$this,'enqueue_assets']);
    add_action('admin_enqueue_scripts',   [$this,'admin_assets']);
    add_action('wp_footer',               [$this,'render_floating_widget']);
    // NEW: settings screen for floating widget
    add_action('admin_menu',              [$this,'register_widget_settings_page']);
=======

/* =========================================================
 * PROMO CODES + FLOATING WIDGET
 * ========================================================= */
class Yono_Promos {
  const CPT  = 'yono_promo';
  const TAX  = 'promo_period';   // morning/afternoon/evening
  const NONCE = 'yono_promos_meta_nonce';

  public function __construct(){
    add_action('init',               [$this,'register_cpt_and_tax']);
    add_action('add_meta_boxes',     [$this,'register_meta_boxes']);
    add_action('save_post',          [$this,'save_meta']);
    add_shortcode('yono_promos',     [$this,'shortcode']);
    add_action('wp_enqueue_scripts', [$this,'enqueue_assets']);
    add_action('admin_enqueue_scripts', [$this,'admin_assets']);
    add_action('wp_footer',          [$this,'render_floating_widget']);
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
  }

  public function register_cpt_and_tax(){
    register_post_type(self::CPT, [
<<<<<<< HEAD
      'label'  => 'Yono Promo Codes',
      'labels' => [
        'name'          => 'Promo Codes',
        'singular_name' => 'Promo Code',
        'add_new_item'  => 'Add New Promo Code',
        'edit_item'     => 'Edit Promo Code'
      ],
      'public'        => false,
      'show_ui'       => true,
      'show_in_menu'  => true,
      'menu_icon'     => 'dashicons-tickets-alt',
      'supports'      => ['title'],
    ]);
    register_taxonomy(
      self::TAX,
      self::CPT,
      [
        'label'            => 'Promo Period',
        'public'           => false,
        'show_ui'          => true,
        'hierarchical'     => false,
        'show_admin_column'=> true,
      ]
    );
    foreach (['morning','afternoon','evening'] as $slug){
      if (!term_exists($slug, self::TAX)) {
        wp_insert_term(ucfirst($slug), self::TAX, ['slug'=>$slug]);
      }
=======
      'label' => 'Yono Promo Codes',
      'labels' => [
        'name'=>'Promo Codes','singular_name'=>'Promo Code',
        'add_new_item'=>'Add New Promo Code','edit_item'=>'Edit Promo Code'
      ],
      'public'=>false,'show_ui'=>true,'show_in_menu'=>true,
      'menu_icon'=>'dashicons-tickets-alt','supports'=>['title']
    ]);

    register_taxonomy(self::TAX, self::CPT, [
      'label'=>'Promo Period','public'=>false,'show_ui'=>true,
      'hierarchical'=>false,'show_admin_column'=>true
    ]);

    foreach (['morning','afternoon','evening'] as $slug){
      if (!term_exists($slug, self::TAX)) wp_insert_term(ucfirst($slug), self::TAX, ['slug'=>$slug]);
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
    }
  }

  public function register_meta_boxes(){
    add_meta_box('yono_promos_details','Promo Details',[$this,'render_meta_box'],self::CPT,'normal','default');
  }

  public function admin_assets($hook){
    global $post_type;
    if (($hook === 'post-new.php' || $hook === 'post.php') && $post_type === self::CPT){
      wp_enqueue_style('yono-promos-admin', plugin_dir_url(__FILE__).'assets/css/promos.css',[], '2.4.2');
      wp_enqueue_script('yono-promos-admin', plugin_dir_url(__FILE__).'assets/js/promos.js',['jquery'],'2.4.2', true);
    }
  }

  public function enqueue_assets(){
    wp_enqueue_style('yono-promos', plugin_dir_url(__FILE__).'assets/css/promos.css',[], '2.4.2');
    wp_enqueue_script('yono-promos', plugin_dir_url(__FILE__).'assets/js/promos.js',[], '2.4.2', true);
  }

  public function image_shortcode($atts){
    $atts = shortcode_atts([
      'id'          => '',
      'slug'        => '',
      'class'       => 'yg-review-image',
      'width'       => '320',
      'height'      => '320',
      'alt'         => '',
      'link'        => 'true',
      'placeholder' => '',
    ], $atts, 'yono_game_image');

    $game = null;
    if (!empty($atts['id'])) {
      $game = get_post(intval($atts['id']));
    } elseif (!empty($atts['slug'])) {
      $game = get_page_by_path(sanitize_title($atts['slug']), OBJECT, self::CPT);
    }

    $src = '';
    $href = '';
    $alt  = sanitize_text_field($atts['alt']);

    if ($game && $game->post_type === self::CPT){
      $src = get_post_meta($game->ID, '_yg_logo', true);
      $alt = $alt ?: get_the_title($game->ID);
      $cta = get_post_meta($game->ID, '_yg_cta_url', true);
      $href = $cta ?: get_permalink($game->ID);
    }

    if (!$src){
      $thumb_id = get_post_thumbnail_id(get_the_ID());
      if ($thumb_id){
        $src  = wp_get_attachment_image_url($thumb_id, 'large');
        $alt  = $alt ?: get_the_title(get_the_ID());
        $href = $href ?: get_permalink(get_the_ID());
      }
    }

    if (!$src && !empty($atts['placeholder'])){
      $src  = esc_url_raw($atts['placeholder']);
      $alt  = $alt ?: 'Image';
      $href = $href ?: get_permalink(get_the_ID());
    }

    if (!$src) return '';

    $w     = max(1,intval($atts['width']));
    $h     = max(1,intval($atts['height']));
    $class = esc_attr($atts['class']);
    $alt   = esc_attr($alt);

    $img = sprintf(
      '<img src="%s" alt="%s" width="%d" height="%d" class="%s" style="height:auto;border-radius:14px;display:block;">',
      esc_url($src),
      $alt,
      $w,
      $h,
      $class
    );

    if ( filter_var($atts['link'], FILTER_VALIDATE_BOOLEAN) && $href ){
      $img = sprintf(
        '<a href="%s" target="_blank" rel="nofollow noopener">%s</a>',
        esc_url($href),
        $img
      );
    }

    return $img;
  }

  public function render_meta_box($post){
    wp_nonce_field(self::NONCE, self::NONCE);
    $code  = get_post_meta($post->ID,'_yono_code',true);
    $label = get_post_meta($post->ID,'_yono_label',true);
    $start = get_post_meta($post->ID,'_yono_start',true);
    $end   = get_post_meta($post->ID,'_yono_end',true);
    ?>
    <div class="yono-meta-logo" style="margin:-4px 0 12px;">
<<<<<<< HEAD
      <img src="https://allyonorefer.com/wp-content/uploads/2025/10/cropped-Untitled-design-9.png" alt="Yono Promo Codes" style="width:28px;height:28px;border-radius:4px;margin-right:8px;vertical-align:middle;">
=======
      <img src="https://allyonorefer.com/wp-content/uploads/2025/10/cropped-Untitled-design-9.png"
        alt="Yono Promo Codes" style="width:28px;height:28px;border-radius:4px;margin-right:8px;vertical-align:middle;">
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
      <strong style="vertical-align:middle;">Yono Promo Details</strong>
    </div>

    <div class="yono-meta-wrap">
<<<<<<< HEAD
      <p>
        <label><strong>Promo Code</strong></label><br>
        <input type="text" name="yono_code" value="<?php echo esc_attr($code); ?>" class="widefat" placeholder="e.g., MORNING50" required>
      </p>
      <p>
        <label><strong>Label / Description</strong></label><br>
        <input type="text" name="yono_label" value="<?php echo esc_attr($label); ?>" class="widefat" placeholder="e.g., Morning Bonus ₹50">
      </p>
      <div class="yono-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <p>
          <label><strong>Start (local)</strong></label><br>
          <input type="datetime-local" name="yono_start" value="<?php echo esc_attr(yono_iso_to_local_value($start)); ?>">
        </p>
        <p>
          <label><strong>End (local)</strong></label><br>
          <input type="datetime-local" name="yono_end" value="<?php echo esc_attr(yono_iso_to_local_value($end)); ?>">
        </p>
      </div>
      <p class="description">
        Empty start = available now. Empty end = never expires.
        Assign period (Morning/Afternoon/Evening) in the right sidebar.
      </p>
=======
      <p><label><strong>Promo Code</strong></label><br>
        <input type="text" name="yono_code" value="<?php echo esc_attr($code); ?>" class="widefat" placeholder="e.g., MORNING50" required></p>
      <p><label><strong>Label / Description</strong></label><br>
        <input type="text" name="yono_label" value="<?php echo esc_attr($label); ?>" class="widefat" placeholder="e.g., Morning Bonus ₹50"></p>

      <div class="yono-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <p><label><strong>Start (local)</strong></label><br>
          <input type="datetime-local" name="yono_start" value="<?php echo esc_attr(yono_iso_to_local_value($start)); ?>"></p>
        <p><label><strong>End (local)</strong></label><br>
          <input type="datetime-local" name="yono_end" value="<?php echo esc_attr(yono_iso_to_local_value($end)); ?>"></p>
      </div>
      <p class="description">Empty start = available now. Empty end = never expires. Assign period (Morning/Afternoon/Evening) in the right sidebar.</p>
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
    </div>
    <?php
  }

  public function save_meta($post_id){
    if (!isset($_POST[self::NONCE]) || !wp_verify_nonce($_POST[self::NONCE], self::NONCE)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post',$post_id)) return;

    $code  = isset($_POST['yono_code'])  ? sanitize_text_field($_POST['yono_code'])  : '';
    $label = isset($_POST['yono_label']) ? sanitize_text_field($_POST['yono_label']) : '';
    $start = isset($_POST['yono_start']) ? sanitize_text_field($_POST['yono_start']) : '';
    $end   = isset($_POST['yono_end'])   ? sanitize_text_field($_POST['yono_end'])   : '';

    update_post_meta($post_id,'_yono_code',  $code);
    update_post_meta($post_id,'_yono_label', $label);
    update_post_meta($post_id,'_yono_start', yono_local_to_iso_utc($start));
    update_post_meta($post_id,'_yono_end',   yono_local_to_iso_utc($end));
  }

  public function shortcode($atts){
    $atts = shortcode_atts([
<<<<<<< HEAD
      'period'      => '',
      'status'      => 'active,upcoming',
      'show_expired'=> 'false',
      'limit'       => '100',
      'layout'      => 'cards',
      'columns'     => '1',
      'empty_text'  => 'No promo codes available right now.',
      'show_copy'   => 'true',
      'show_timer'  => 'true',
      'order'       => 'ASC',
      'format'      => 'long',
    ], $atts,'yono_promos');

    $periods      = array_filter(array_map('trim', explode(',', strtolower($atts['period']))));
    $show_expired = filter_var($atts['show_expired'], FILTER_VALIDATE_BOOLEAN);
    $want_status  = array_filter(array_map('trim', explode(',', strtolower($atts['status']))));
=======
      'period'=>'','status'=>'active,upcoming','show_expired'=>'false',
      'limit'=>'100','layout'=>'cards','columns'=>'1','empty_text'=>'No promo codes available right now.',
      'show_copy'=>'true','show_timer'=>'true','order'=>'ASC','format'=>'long',
    ], $atts,'yono_promos');

    $periods = array_filter(array_map('trim', explode(',', strtolower($atts['period']))));
    $show_expired = filter_var($atts['show_expired'], FILTER_VALIDATE_BOOLEAN);
    $want_status = array_filter(array_map('trim', explode(',', strtolower($atts['status']))));
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
    if (!$want_status) $want_status=['active','upcoming'];
    $limit = max(1, intval($atts['limit']));
    $order = strtoupper($atts['order'])==='DESC' ? 'DESC' : 'ASC';

    $tax_query = [];
    if (!empty($periods)){
<<<<<<< HEAD
      $tax_query[] = [
        'taxonomy'=>self::TAX,
        'field'   =>'slug',
        'terms'   =>$periods
      ];
=======
      $tax_query[] = ['taxonomy'=>self::TAX,'field'=>'slug','terms'=>$periods];
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
    }

    $q = new WP_Query([
      'post_type'=>self::CPT,'posts_per_page'=>$limit,'tax_query'=>$tax_query,
      'orderby'=>'title','order'=>$order,'no_found_rows'=>true,
    ]);
<<<<<<< HEAD

    $now_ts = time();
    $items  = [];

    while($q->have_posts()){
      $q->the_post();
      $id   = get_the_ID();
      $code = get_post_meta($id,'_yono_code',true);
=======
    $now_ts = time();
    $items=[];
    while($q->have_posts()){ $q->the_post();
      $id=get_the_ID(); $code=get_post_meta($id,'_yono_code',true);
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
      if (!$code) continue;
      $label=get_post_meta($id,'_yono_label',true);
      $start_iso=get_post_meta($id,'_yono_start',true);
      $end_iso  =get_post_meta($id,'_yono_end',true);
      $st=$start_iso?strtotime($start_iso):0; $et=$end_iso?strtotime($end_iso):0;
      $status='active'; if ($st && $now_ts<$st) $status='upcoming'; if ($et && $now_ts>$et) $status='expired';
      if ($status==='expired' && !$show_expired) continue;
      if (!in_array($status,$want_status,true) && !($status==='expired' && $show_expired)) continue;
      $terms=wp_get_post_terms($id,self::TAX,['fields'=>'slugs']); $period=$terms?$terms[0]:'';
      $items[]=['id'=>$id,'title'=>get_the_title(),'url'=>get_permalink(),'code'=>$code,'label'=>$label,'start'=>$start_iso,'end'=>$end_iso,'period'=>$period,'status'=>$status];
    } wp_reset_postdata();

<<<<<<< HEAD
      $label     = get_post_meta($id,'_yono_label',true);
      $start_iso = get_post_meta($id,'_yono_start',true);
      $end_iso   = get_post_meta($id,'_yono_end',true);
      $st        = $start_iso ? strtotime($start_iso) : 0;
      $et        = $end_iso   ? strtotime($end_iso)   : 0;

      $status = 'active';
      if ($st && $now_ts < $st) $status = 'upcoming';
      if ($et && $now_ts > $et) $status = 'expired';

      if ($status === 'expired' && !$show_expired) continue;
      if (!in_array($status,$want_status,true) && !($status==='expired' && $show_expired)) continue;

      $terms  = wp_get_post_terms($id,self::TAX,['fields'=>'slugs']);
      $period = $terms ? $terms[0] : '';

      $items[] = [
        'id'     => $id,
        'title'  => get_the_title(),
        'url'    => get_permalink(),
        'code'   => $code,
        'label'  => $label,
        'start'  => $start_iso,
        'end'    => $end_iso,
        'period' => $period,
        'status' => $status,
      ];
    }
    wp_reset_postdata();

    if (!$items) {
      return '<div class="yono-promos-empty">'.esc_html($atts['empty_text']).'</div>';
    }

    $json = wp_json_encode([
      'now'       => gmdate('c'),
      'showCopy'  => filter_var($atts['show_copy'], FILTER_VALIDATE_BOOLEAN),
      'showTimer' => filter_var($atts['show_timer'], FILTER_VALIDATE_BOOLEAN),
      'format'    => in_array(strtolower($atts['format']),['long','compact'],true) ? strtolower($atts['format']) : 'long',
    ]);

    ob_start(); ?>
    <div class="yono-promos"
         data-settings='<?php echo esc_attr($json); ?>'
         data-layout="<?php echo esc_attr($atts['layout']); ?>"
         data-columns="<?php echo esc_attr($atts['columns']); ?>">
      <?php foreach($items as $it): ?>
        <article class="yono-promo-card status-<?php echo esc_attr($it['status']); ?>"
                 data-start="<?php echo esc_attr($it['start']); ?>"
                 data-end="<?php echo esc_attr($it['end']); ?>"
                 data-period="<?php echo esc_attr($it['period']); ?>">
          <?php if ($it['period']): ?>
            <span class="promo-badge"><?php echo esc_html(ucfirst($it['period'])); ?></span>
          <?php endif; ?>
          <h3 class="promo-title"><?php echo esc_html($it['title']); ?></h3>
          <div class="promo-code-wrap">
            <code class="promo-code"><?php echo esc_html($it['code']); ?></code>
            <button class="promo-copy" type="button" aria-label="Copy promo code">Copy</button>
          </div>
          <?php if ($it['label']): ?>
            <div class="promo-desc"><?php echo esc_html($it['label']); ?></div>
          <?php endif; ?>
          <div class="promo-timer" aria-live="polite" aria-atomic="true"></div>
        </article>
=======
    if (!$items) return '<div class="yono-promos-empty">'.esc_html($atts['empty_text']).'</div>';

    $json = wp_json_encode([
      'now'=>gmdate('c'),'showCopy'=>filter_var($atts['show_copy'], FILTER_VALIDATE_BOOLEAN),
      'showTimer'=>filter_var($atts['show_timer'], FILTER_VALIDATE_BOOLEAN),
      'format'=> in_array(strtolower($atts['format']),['long','compact'],true) ? strtolower($atts['format']) : 'long',
    ]);

    ob_start(); ?>
    <div class="yono-promos" data-settings='<?php echo esc_attr($json); ?>' data-layout="<?php echo esc_attr($atts['layout']); ?>" data-columns="<?php echo esc_attr($atts['columns']); ?>">
      <?php foreach($items as $it):
        $start_attr = $it['start'] ? esc_attr($it['start']) : '';
        $end_attr   = $it['end']   ? esc_attr($it['end'])   : '';
      ?>
      <article class="yono-promo-card status-<?php echo esc_attr($it['status']); ?>"
               data-start="<?php echo $start_attr; ?>"
               data-end="<?php echo $end_attr; ?>"
               data-period="<?php echo esc_attr($it['period']); ?>">
        <?php if ($it['period']): ?><span class="promo-badge"><?php echo esc_html(ucfirst($it['period'])); ?></span><?php endif; ?>
        <h3 class="promo-title"><?php echo esc_html($it['title']); ?></h3>
        <div class="promo-code-wrap">
          <code class="promo-code"><?php echo esc_html($it['code']); ?></code>
          <button class="promo-copy" type="button" aria-label="Copy promo code">Copy</button>
        </div>
        <?php if ($it['label']): ?><div class="promo-desc"><?php echo esc_html($it['label']); ?></div><?php endif; ?>
        <div class="promo-timer" aria-live="polite" aria-atomic="true"></div>
      </article>
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
      <?php endforeach; ?>
    </div>
    <?php
  }

  /* ---------- Floating widget ---------- */
  public function render_floating_widget(){
    if (is_admin()) return;

    $q = new WP_Query([
      'post_type'      => self::CPT,
      'posts_per_page' => 1,
      'no_found_rows'  => true,
      'orderby'        => 'date',
      'order'          => 'DESC',
    ]);

    $show = false;
    $now  = time();

    while($q->have_posts()){
      $q->the_post();
      $st  = get_post_meta(get_the_ID(),'_yono_start',true);
      $et  = get_post_meta(get_the_ID(),'_yono_end',true);
      $sts = $st ? strtotime($st) : 0;
      $ets = $et ? strtotime($et) : 0;
      $status = 'active';
      if ($sts && $now < $sts) $status = 'upcoming';
      if ($ets && $now > $ets) $status = 'expired';
      if ($status !== 'expired'){
        $show = true;
        break;
      }
    }
    wp_reset_postdata();

    if (!$show) return;

    $defaults = [
      'image'    => 'https://allyonorefer.com/wp-content/uploads/2025/10/Promo-code.webp',
      'title'    => 'Yono Games',
      'subtitle' => 'Free Promo Codes',
    ];
    $opts = wp_parse_args(get_option(self::OPT_WIDGET, []), $defaults);

    $settings_json = esc_attr( wp_json_encode([
      'now'       => gmdate('c'),
      'showCopy'  => true,
      'showTimer' => true,
      'format'    => 'long',
    ]) );
    ?>
    <style>.promo-popup[hidden]{display:none!important}.promo-popup.show{display:flex}</style>
    <div class="promo-widget" aria-hidden="false">
      <button type="button" class="promo-trigger promo-tile"
              aria-controls="promoPopup" aria-expanded="false"
              aria-label="Open promo codes">
        <img class="promo-image"
             src="<?php echo esc_url($opts['image']); ?>"
             alt="<?php echo esc_attr($opts['title'] ?: 'Promo Codes'); ?>"
             decoding="async" loading="lazy"/>
      </button>
    </div>
    <div id="promoPopup" class="promo-popup" role="dialog" aria-modal="true" aria-labelledby="promoTitle" hidden>
      <div class="promo-content" role="document">
        <button class="promo-close" type="button" aria-label="Close">&times;</button>
        <div class="promo-header">
          <h3 id="promoTitle"><?php echo esc_html($opts['title']); ?></h3>
          <p><?php echo esc_html($opts['subtitle']); ?></p>
        </div>
        <div class="tab-navigation" role="tablist" aria-label="Promo periods">
          <button class="tab-button active" role="tab" aria-selected="true" data-period="morning">Morning</button>
          <button class="tab-button" role="tab" aria-selected="false" data-period="afternoon">Afternoon</button>
          <button class="tab-button" role="tab" aria-selected="false" data-period="evening">Evening</button>
        </div>
        <div class="tab-content active" data-period="morning">
          <div class="yono-promos" data-settings="<?php echo $settings_json; ?>">
            <?php echo do_shortcode('[yono_promos period="morning" limit="200" order="ASC" show_timer="true" show_copy="true" format="long"]'); ?>
          </div>
        </div>
        <div class="tab-content" data-period="afternoon">
          <div class="yono-promos" data-settings="<?php echo $settings_json; ?>">
            <?php echo do_shortcode('[yono_promos period="afternoon" limit="200" order="ASC" show_timer="true" show_copy="true" format="long"]'); ?>
          </div>
        </div>
        <div class="tab-content" data-period="evening">
          <div class="yono-promos" data-settings="<?php echo $settings_json; ?>">
            <?php echo do_shortcode('[yono_promos period="evening" limit="200" order="ASC" show_timer="true" show_copy="true" format="long"]'); ?>
          </div>
        </div>
      </div>
    </div>
    <div id="toast-notification" aria-live="polite" aria-atomic="true">Code copied</div>
    <script>
    (function(){
      if (window.__yonoPromoInit) return; window.__yonoPromoInit = true;
      document.addEventListener('DOMContentLoaded', function(){
        var popup=document.getElementById('promoPopup');
        var trigger=document.querySelector('.promo-trigger');
        if(!popup||!trigger) return;
        var closeBtn=popup.querySelector('.promo-close');
        var tabs=[].slice.call(popup.querySelectorAll('.tab-button'));
        var panes=[].slice.call(popup.querySelectorAll('.tab-content'));
        function activate(p){
          tabs.forEach(function(t){
            var on=t.dataset.period===p;
            t.classList.toggle('active',on);
            t.setAttribute('aria-selected',on?'true':'false');
          });
          panes.forEach(function(x){
            x.classList.toggle('active',x.dataset.period===p);
          });
        }
        function openP(){
          popup.hidden=false;
          popup.classList.add('show');
          document.documentElement.style.overflow='hidden';
          trigger.setAttribute('aria-expanded','true');
          activate('morning');
        }
        function closeP(){
          popup.classList.remove('show');
          popup.hidden=true;
          document.documentElement.style.overflow='';
          trigger.setAttribute('aria-expanded','false');
        }
        trigger.addEventListener('click', openP);
        if(closeBtn) closeBtn.addEventListener('click', closeP);
        popup.addEventListener('click', function(e){ if(e.target===popup) closeP(); });
        document.addEventListener('keydown', function(e){ if(e.key==='Escape' && !popup.hidden) closeP(); });

        var toast=document.getElementById('toast-notification');
        popup.addEventListener('click', function(e){
          var btn=e.target.closest && e.target.closest('.promo-copy');
          if(!btn||!toast) return;
          toast.classList.add('show');
          setTimeout(function(){ toast.classList.remove('show'); },1200);
        });
      });
    })();
    </script>
    <?php
  }

  /** Submenu under Promo Codes for configuring the floating widget */
  public function register_widget_settings_page(){
    add_submenu_page(
      'edit.php?post_type=' . self::CPT,
      'Promo Widget Settings',
      'Widget Settings',
      'manage_options',
      'yono_promos_widget',
      [$this, 'widget_settings_page']
    );
  }

  public function widget_settings_page(){
    if (!current_user_can('manage_options')) return;

    // Save on POST
    if (isset($_POST['yono_promos_widget_nonce']) &&
        wp_verify_nonce($_POST['yono_promos_widget_nonce'], 'yono_promos_widget_save')) {

      $opts = [
        'image'    => isset($_POST['yono_promos_widget_image']) ? esc_url_raw($_POST['yono_promos_widget_image']) : '',
        'title'    => isset($_POST['yono_promos_widget_title']) ? sanitize_text_field($_POST['yono_promos_widget_title']) : '',
        'subtitle' => isset($_POST['yono_promos_widget_subtitle']) ? sanitize_text_field($_POST['yono_promos_widget_subtitle']) : '',
      ];
      update_option(self::OPT_WIDGET, $opts);
      echo '<div class="notice notice-success is-dismissible"><p>Widget settings saved.</p></div>';
    }

    $defaults = [
      'image'    => 'https://allyonorefer.com/wp-content/uploads/2025/10/Promo-code.webp',
      'title'    => 'Yono Games',
      'subtitle' => 'Free Promo Codes',
    ];
    $opts = wp_parse_args(get_option(self::OPT_WIDGET, []), $defaults);
    ?>
    <div class="wrap">
      <h1>Promo Floating Widget</h1>
      <form method="post">
        <?php wp_nonce_field('yono_promos_widget_save', 'yono_promos_widget_nonce'); ?>
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="yono_promos_widget_image">Widget Image URL</label></th>
            <td>
              <input type="url" class="regular-text" id="yono_promos_widget_image"
                     name="yono_promos_widget_image"
                     value="<?php echo esc_attr($opts['image']); ?>"
                     placeholder="https://example.com/promo-widget.png">
              <?php if (!empty($opts['image'])): ?>
                <p>
                  <img id="yono_promos_widget_preview"
                       src="<?php echo esc_url($opts['image']); ?>"
                       alt=""
                       style="max-width:180px;border-radius:8px;border:1px solid #ccd;">
                </p>
              <?php endif; ?>
              <p class="description">Square image works best (e.g. 88×88 or 96×96).</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="yono_promos_widget_title">Popup Title</label></th>
            <td>
              <input type="text" class="regular-text" id="yono_promos_widget_title"
                     name="yono_promos_widget_title"
                     value="<?php echo esc_attr($opts['title']); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="yono_promos_widget_subtitle">Popup Subtitle</label></th>
            <td>
              <input type="text" class="regular-text" id="yono_promos_widget_subtitle"
                     name="yono_promos_widget_subtitle"
                     value="<?php echo esc_attr($opts['subtitle']); ?>">
              <p class="description">Short line under the title (e.g. “Today&apos;s Free Promo Codes”).</p>
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
  }

}
new Yono_Promos();

/* =========================================================
 * GAMES + CSV + MEDIA PICKER (+ WIDE LAYOUT + REVIEW)
 * ========================================================= */
class Yono_Games {

  const CPT       = 'yono_game';
  const TAX_CAT   = 'game_cat';
  const TAX_BADGE = 'game_badge';
  const NONCE     = 'yono_games_meta_nonce';

  public function __construct(){
    add_action('init',                   [$this,'register_cpt_tax']);
    add_action('add_meta_boxes',         [$this,'register_meta_boxes']);
    add_action('save_post',              [$this,'save_meta']);
    add_action('admin_menu',             [$this,'register_tools_page']);
    add_shortcode('yono_games',          [$this,'shortcode']);
    add_shortcode('yono_game_review',    [$this,'review_shortcode']);
    add_action('wp_enqueue_scripts',     [$this,'enqueue_assets']);
    add_action('admin_enqueue_scripts',  [$this,'admin_assets']);
    add_action('wp_ajax_yono_games_export',   [$this,'handle_export']);
    add_action('admin_post_yono_games_import',[$this,'handle_import']);

    // NEW: per-badge highlight effect (glow / pulse)
    add_action(self::TAX_BADGE . '_add_form_fields',  [$this, 'badge_add_fields']);
    add_action(self::TAX_BADGE . '_edit_form_fields', [$this, 'badge_edit_fields'], 10, 2);
    add_action('created_' . self::TAX_BADGE,          [$this, 'save_badge_meta']);
    add_action('edited_'  . self::TAX_BADGE,          [$this, 'save_badge_meta']);
  }

  public function register_cpt_tax(){
    register_post_type(self::CPT, [
      'label'  => 'Yono Games',
      'labels' => [
        'name'          => 'Games',
        'singular_name' => 'Game',
        'add_new_item'  => 'Add New Game',
        'edit_item'     => 'Edit Game',
      ],
      'public'              => true,
      'show_ui'             => true,
      'show_in_menu'        => true,
      'publicly_queryable'  => true,
      'exclude_from_search' => false,
      'has_archive'         => false,
      'rewrite'             => [
        'slug'       => 'game',
        'with_front' => false,
        'feeds'      => false,
      ],
      'menu_icon'           => 'dashicons-games',
      'supports'            => ['title'],
    ]);

    // Register taxonomies
    register_taxonomy(
      self::TAX_CAT,
      self::CPT,
      [
        'label'            => 'Game Category',
        'public'           => false,
        'show_ui'          => true,
        'hierarchical'     => true,
        'show_admin_column'=> true,
      ]
    );
    register_taxonomy(
      self::TAX_BADGE,
      self::CPT,
      [
        'label'            => 'Game Badge',
        'public'           => false,
        'show_ui'          => true,
        'hierarchical'     => false,
        'show_admin_column'=> true,
      ]
    );

    // Populate terms if they don't exist
    foreach (['Rummy','Slots','Arcade','Bingo'] as $cat){
      if (!term_exists($cat, self::TAX_CAT)) {
        wp_insert_term($cat, self::TAX_CAT);
      }
    }
    foreach (['New','Coming Soon','Hot'] as $b){
      if (!term_exists($b, self::TAX_BADGE)) {
        wp_insert_term($b, self::TAX_BADGE, ['slug'=>sanitize_title($b)]);
      }
    }
  }

  /** Badge term meta: choose None / Glow / Pulse */
  public function badge_add_fields($taxonomy){
    ?>
    <div class="form-field term-badge-effect-wrap">
      <label for="yg_badge_effect"><?php esc_html_e('Highlight Effect', 'default'); ?></label>
      <select name="yg_badge_effect" id="yg_badge_effect">
        <option value=""><?php esc_html_e('None', 'default'); ?></option>
        <option value="glow"><?php esc_html_e('Gold glow', 'default'); ?></option>
        <option value="pulse"><?php esc_html_e('Gold pulse', 'default'); ?></option>
      </select>
      <p class="description">Optional glow or pulse animation for this badge on game cards.</p>
    </div>
    <?php
  }

  public function badge_edit_fields($term, $taxonomy){
    $effect = get_term_meta($term->term_id, '_yg_badge_effect', true);
    ?>
    <tr class="form-field term-badge-effect-wrap">
      <th scope="row"><label for="yg_badge_effect"><?php esc_html_e('Highlight Effect', 'default'); ?></label></th>
      <td>
        <select name="yg_badge_effect" id="yg_badge_effect">
          <option value="" <?php selected($effect, ''); ?>><?php esc_html_e('None', 'default'); ?></option>
          <option value="glow" <?php selected($effect, 'glow'); ?>><?php esc_html_e('Gold glow', 'default'); ?></option>
          <option value="pulse" <?php selected($effect, 'pulse'); ?>><?php esc_html_e('Gold pulse', 'default'); ?></option>
        </select>
        <p class="description">Controls the glow / pulse effect of this badge on the frontend.</p>
      </td>
    </tr>
    <?php
  }

  public function save_badge_meta($term_id){
    if (!isset($_POST['yg_badge_effect'])) return;
    $effect = sanitize_text_field($_POST['yg_badge_effect']);
    if (!in_array($effect, ['', 'glow', 'pulse'], true)) {
      $effect = '';
    }
    update_term_meta($term_id, '_yg_badge_effect', $effect);
  }

  public function enqueue_assets(){
    wp_enqueue_style('yono-games', plugin_dir_url(__FILE__).'assets/css/games.css',[], '2.4.2');
    wp_enqueue_script('yono-games', plugin_dir_url(__FILE__).'assets/js/games.js',[], '2.4.2', true);
  }

  public function admin_assets($hook){
    if (strpos($hook,'yono_games_tools') !== false){
      wp_enqueue_style('yono-games', plugin_dir_url(__FILE__).'assets/css/games.css',[], '2.4.2');
      wp_enqueue_script('yono-games-admin', plugin_dir_url(__FILE__).'assets/js/games-admin.js',[], '2.4.2', true);
    }
    global $post_type;
    if (($hook==='post-new.php' || $hook==='post.php') && $post_type===self::CPT){
      wp_enqueue_media();
      wp_enqueue_script('yono-media-picker', plugin_dir_url(__FILE__).'assets/js/yono-media-picker.js',['jquery'],'2.4.2', true);
    }
  }

  public function register_meta_boxes(){
    add_meta_box('yono_game_details','Game Details',[$this,'render_meta_box'], self::CPT,'normal','default');
  }

  public function render_meta_box($post){
    wp_nonce_field(self::NONCE, self::NONCE);
    $meta = [
      'logo'     => get_post_meta($post->ID,'_yg_logo',true),
      'subtitle' => get_post_meta($post->ID,'_yg_subtitle',true),
      'bonus'    => get_post_meta($post->ID,'_yg_bonus',true),
      'minwd'    => get_post_meta($post->ID,'_yg_min_withdraw',true),
      'cta_text' => get_post_meta($post->ID,'_yg_cta_text',true) ?: 'Get Started',
      'cta_url'  => get_post_meta($post->ID,'_yg_cta_url',true),
      'download' => get_post_meta($post->ID,'_yg_download_url',true),
      'launch'   => get_post_meta($post->ID,'_yg_launch_at',true),
      'status'   => get_post_meta($post->ID,'_yg_status',true) ?: 'active',
      'desc'     => get_post_meta($post->ID,'_yg_desc',true),
      'dev'      => get_post_meta($post->ID,'_yg_dev',true),
      'version'  => get_post_meta($post->ID,'_yg_version',true),
      'size_mb'  => get_post_meta($post->ID,'_yg_size_mb',true),
      'updated'  => get_post_meta($post->ID,'_yg_updated',true),
      'downloads'=> get_post_meta($post->ID,'_yg_downloads',true),
      'rating'   => get_post_meta($post->ID,'_yg_rating',true),
      'votes'    => get_post_meta($post->ID,'_yg_votes',true),
      'telegram' => get_post_meta($post->ID,'_yg_telegram',true),
    ];
    ?>
    <style>.yono-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}</style>
    <p>
      <label><strong>Logo</strong></label><br>
      <div class="yg-media-row" style="display:flex;gap:10px;align-items:flex-start;">
        <img src="<?php echo $meta['logo'] ? esc_url($meta['logo']) : ''; ?>"
             class="yg-media-preview"
             style="width:56px;height:56px;border-radius:8px;object-fit:cover;background:#0e1726;border:1px solid rgba(148,163,184,.2);<?php echo $meta['logo'] ? '' : 'display:none;'; ?>"
             alt="">
        <div style="flex:1 1 auto;">
          <input type="url" id="yg_logo" name="yg_logo" class="widefat" placeholder="https://…" value="<?php echo esc_attr($meta['logo']); ?>">
          <div style="margin-top:8px;display:flex;gap:8px;">
            <button class="button yg-media-select" type="button" data-target="#yg_logo" data-preview=".yg-media-preview">Select / Upload</button>
            <button class="button yg-media-remove" type="button" data-target="#yg_logo" data-preview=".yg-media-preview" <?php echo $meta['logo'] ? '' : 'style="display:none"'; ?>>Remove</button>
          </div>
          <small class="description">Paste any image URL or click “Select / Upload” to choose from Media Library.</small>
        </div>
      </div>
    </p>

    <div class="yono-grid-2">
      <p>
        <label><strong>Subtitle</strong></label><br>
        <input type="text" name="yg_subtitle" class="widefat" placeholder="Slots Game" value="<?php echo esc_attr($meta['subtitle']); ?>">
      </p>
      <p>
        <label><strong>Welcome bonus (range)</strong></label><br>
        <input type="text" name="yg_bonus" class="widefat" placeholder="₹75–₹150" value="<?php echo esc_attr($meta['bonus']); ?>">
      </p>
      <p>
        <label><strong>Minimum Withdrawal</strong></label><br>
        <input type="text" name="yg_min_withdraw" class="widefat" placeholder="₹100" value="<?php echo esc_attr($meta['minwd']); ?>">
      </p>
      <p>
        <label><strong>CTA Text</strong></label><br>
        <input type="text" name="yg_cta_text" class="widefat" value="<?php echo esc_attr($meta['cta_text']); ?>">
      </p>
      <p>
        <label><strong>CTA URL</strong></label><br>
        <input type="url" name="yg_cta_url" class="widefat" placeholder="https://example.com/download" value="<?php echo esc_attr($meta['cta_url']); ?>">
      </p>
      <p>
        <label><strong>Download URL</strong></label><br>
        <input type="url" name="yg_download_url" class="widefat" placeholder="https://example.com/app.apk" value="<?php echo esc_attr($meta['download']); ?>">
        <small class="description">If provided, the download icon on the card will download/open the file. Leave blank to hide.</small>
      </p>
      <p>
        <label><strong>Launch (local time)</strong></label><br>
        <input type="datetime-local" name="yg_launch_at" value="<?php echo esc_attr(yono_iso_to_local_value($meta['launch'])); ?>">
        <em class="description">If set in the future, card shows “Launches in …” countdown.</em>
      </p>
      <p>
        <label><strong>Status</strong></label><br>
        <select name="yg_status" class="widefat">
          <?php foreach (['active'=>'Active','upcoming'=>'Upcoming','retired'=>'Retired'] as $k=>$v): ?>
            <option value="<?php echo esc_attr($k); ?>" <?php selected($meta['status'],$k); ?>><?php echo esc_html($v); ?></option>
          <?php endforeach; ?>
        </select>
      </p>
    </div>

    <p>
      <label><strong>Description (SEO review body)</strong></label><br>
      <textarea name="yg_desc" class="widefat" rows="4" placeholder="Write the long review shown in the review shortcode"><?php echo esc_textarea($meta['desc']); ?></textarea>
    </p>

    <hr><h3>SEO Review Extras</h3>
    <div class="yono-grid-2">
      <p><label><strong>Developer</strong></label><br><input type="text" name="yg_dev" class="widefat" placeholder="RUMBLE RUMMY YONO" value="<?php echo esc_attr($meta['dev']); ?>"></p>
      <p><label><strong>Version</strong></label><br><input type="text" name="yg_version" class="widefat" placeholder="1.0.1" value="<?php echo esc_attr($meta['version']); ?>"></p>
      <p><label><strong>Size (MB)</strong></label><br><input type="number" step="0.1" name="yg_size_mb" class="widefat" placeholder="65" value="<?php echo esc_attr($meta['size_mb']); ?>"></p>
      <p><label><strong>Updated (Y-m-d)</strong></label><br><input type="date" name="yg_updated" class="widefat" value="<?php echo esc_attr($meta['updated']); ?>"></p>
      <p><label><strong>Downloads (approx.)</strong></label><br><input type="text" name="yg_downloads" class="widefat" placeholder="45K" value="<?php echo esc_attr($meta['downloads']); ?>"></p>
      <p><label><strong>Rating (0–5)</strong></label><br><input type="number" step="0.1" min="0" max="5" name="yg_rating" class="widefat" placeholder="4.4" value="<?php echo esc_attr($meta['rating']); ?>"></p>
      <p><label><strong>Votes</strong></label><br><input type="number" name="yg_votes" class="widefat" placeholder="87" value="<?php echo esc_attr($meta['votes']); ?>"></p>
      <p><label><strong>Telegram URL</strong></label><br><input type="url" name="yg_telegram" class="widefat" placeholder="https://t.me/yourchannel" value="<?php echo esc_url($meta['telegram']); ?>"></p>
    </div>
    <p class="description">Assign Category (Rummy/Slots/Arcade/Bingo) and optional Badge (New/Coming Soon/Hot) in the taxonomy panels on the right.</p>
    <?php
  }

  public function save_meta($post_id){
    if (!isset($_POST[self::NONCE]) || !wp_verify_nonce($_POST[self::NONCE], self::NONCE)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post',$post_id)) return;

    $fields = [
      '_yg_logo'         => isset($_POST['yg_logo']) ? esc_url_raw($_POST['yg_logo']) : '',
      '_yg_subtitle'     => isset($_POST['yg_subtitle']) ? sanitize_text_field($_POST['yg_subtitle']) : '',
      '_yg_bonus'        => isset($_POST['yg_bonus']) ? sanitize_text_field($_POST['yg_bonus']) : '',
      '_yg_min_withdraw' => isset($_POST['yg_min_withdraw']) ? sanitize_text_field($_POST['yg_min_withdraw']) : '',
      '_yg_cta_text'     => isset($_POST['yg_cta_text']) ? sanitize_text_field($_POST['yg_cta_text']) : 'Get Started',
      '_yg_cta_url'      => isset($_POST['yg_cta_url']) ? esc_url_raw($_POST['yg_cta_url']) : '',
      '_yg_download_url' => isset($_POST['yg_download_url']) ? esc_url_raw($_POST['yg_download_url']) : '',
      '_yg_launch_at'    => isset($_POST['yg_launch_at']) ? yono_local_to_iso_utc(sanitize_text_field($_POST['yg_launch_at'])) : '',
      '_yg_status'       => isset($_POST['yg_status']) ? sanitize_text_field($_POST['yg_status']) : 'active',
      '_yg_desc'         => isset($_POST['yg_desc']) ? sanitize_textarea_field($_POST['yg_desc']) : '',
      '_yg_dev'          => isset($_POST['yg_dev']) ? sanitize_text_field($_POST['yg_dev']) : '',
      '_yg_version'      => isset($_POST['yg_version']) ? sanitize_text_field($_POST['yg_version']) : '',
      '_yg_size_mb'      => isset($_POST['yg_size_mb']) ? sanitize_text_field($_POST['yg_size_mb']) : '',
      '_yg_updated'      => isset($_POST['yg_updated']) ? sanitize_text_field($_POST['yg_updated']) : '',
      '_yg_downloads'    => isset($_POST['yg_downloads']) ? sanitize_text_field($_POST['yg_downloads']) : '',
      '_yg_rating'       => isset($_POST['yg_rating']) ? floatval($_POST['yg_rating']) : '',
      '_yg_votes'        => isset($_POST['yg_votes']) ? intval($_POST['yg_votes']) : '',
      '_yg_telegram'     => isset($_POST['yg_telegram']) ? esc_url_raw($_POST['yg_telegram']) : '',
    ];
    foreach($fields as $k=>$v){
      update_post_meta($post_id,$k,$v);
    }
  }

  /* ---------------- GRID/LIST (main) ---------------- */
  public function shortcode($atts){
    $atts = shortcode_atts([
      'style'          => 'cards',   // 'cards' | 'wide'
      'cat'            => '',
      'badge'          => '',
      'per_page'       => '60',
      'columns'        => '3',
      'sort'           => 'name',    // name | launch | latest
      'show_count'     => 'true',
      'show_search'    => 'true',
      'show_filters'   => 'true',
      'upcoming_first' => 'true',
    ], $atts, 'yono_games');

    // Tax filters
    $tax_query=[];
    if (!empty($atts['cat'])){
      $cats = array_map('trim', explode(',', $atts['cat']));
      $tax_query[] = [
        'taxonomy'=>self::TAX_CAT,
        'field'   =>'name',
        'terms'   =>$cats,
      ];
    }
    if (!empty($atts['badge'])){
      $b = array_map('trim', explode(',', $atts['badge']));
      $tax_query[] = [
        'taxonomy'=>self::TAX_BADGE,
        'field'   =>'name',
        'terms'   =>$b,
      ];
    }

    // Sorting
    $orderby = 'title';
    $order   = 'ASC';
    $meta_key = '';
    switch (strtolower($atts['sort'])){
      case 'latest':
        $orderby = 'date';
        $order   = 'DESC';
        break;
      case 'launch':
        $orderby = 'meta_value';
        $order   = 'ASC';
        $meta_key = '_yg_launch_at';
        break;
      default:
        $orderby = 'title';
        $order   = 'ASC';
    }

    $q = new WP_Query([
      'post_type'      => self::CPT,
      'posts_per_page' => intval($atts['per_page']),
      'tax_query'      => $tax_query,
      'orderby'        => $orderby,
      'order'          => $order,
      'no_found_rows'  => true,
      'meta_key'       => $meta_key,
    ]);

    // Collect items
    $items=[];
    while($q->have_posts()){
      $q->the_post();
      $id = get_the_ID();
      $logo     = get_post_meta($id,'_yg_logo',true);
      $subtitle = get_post_meta($id,'_yg_subtitle',true);
      $bonus    = get_post_meta($id,'_yg_bonus',true);
      $minwd    = get_post_meta($id,'_yg_min_withdraw',true);
      $cta_text = get_post_meta($id,'_yg_cta_text',true) ?: 'Get Started';
      $cta_url  = get_post_meta($id,'_yg_cta_url',true);
      $launch   = get_post_meta($id,'_yg_launch_at',true);
      $status   = get_post_meta($id,'_yg_status',true) ?: 'active';
      if ($launch && time() < strtotime($launch)) $status='upcoming';
      $cats  = wp_get_post_terms($id, self::TAX_CAT,   ['fields'=>'names']);
      $badgs = wp_get_post_terms($id, self::TAX_BADGE, ['fields'=>'slugs']);

      $items[] = [
        'id'       => $id,
        'title'    => get_the_title(),
        'logo'     => $logo,
        'subtitle' => $subtitle,
        'bonus'    => $bonus,
        'minwd'    => $minwd,
        'cta_text' => $cta_text,
        'cta_url'  => $cta_url,
        'launch'   => $launch,
        'status'   => $status,
        'cat'      => $cats,
        'badge'    => $badgs,
      ];
    }
    wp_reset_postdata();

    // Upcoming first (then by launch/name like before)
    if ( filter_var($atts['upcoming_first'], FILTER_VALIDATE_BOOLEAN) ) {
      $sortPref = strtolower($atts['sort']);
      usort($items, function($a,$b) use ($sortPref){
        $rank = ['upcoming'=>0, 'active'=>1, 'retired'=>2];
        $ra = isset($rank[$a['status']]) ? $rank[$a['status']] : 99;
        $rb = isset($rank[$b['status']]) ? $rank[$b['status']] : 99;
        if ($ra !== $rb) return $ra - $rb;
        $la = $a['launch'] ? strtotime($a['launch']) : PHP_INT_MAX;
        $lb = $b['launch'] ? strtotime($b['launch']) : PHP_INT_MAX;
        if ($la !== $lb) return $la - $lb;
        if ($sortPref === 'latest') return $b['id'] - $a['id'];
        return strcasecmp($a['title'], $b['title']);
      });
    }

    $count   = count($items);
    $is_wide = strtolower($atts['style']) === 'wide';
    $cols    = max(1, min(6, intval($atts['columns'])));
    $min_by_cols = [
      1=>'100%',
      2=>'520px',
      3=>'360px',
      4=>'280px',
      5=>'220px',
      6=>'190px',
    ];
    $card_min  = isset($min_by_cols[$cols]) ? $min_by_cols[$cols] : '280px';
    $wide_cols = max(1, min(4, intval($atts['columns']))); // honor columns for wide layout (1–4)

    // base CSS printed once (unchanged visuals)
    static $once = false;
    $head = '';
    if (!$once){
      $once = true;
      $head .= '<style>
      /* toolbar + grid basics are in games.css; this only adds wide layout overrides */

      /* WIDE cards – cleaner, denser, nicer */
      .yg-card--wide{
        display:grid;
        grid-template-columns: 220px 1fr;
        gap:20px;
        align-items:start;
        background:#0b1220;
        border:1px solid rgba(148,163,184,.14);
        border-radius:16px;
        padding:16px;
        box-shadow:0 10px 24px rgba(0,0,0,.25);
        transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
      }
      .yg-card--wide:hover{
        transform: translateY(-1px);
        border-color: rgba(59,130,246,.35);
        box-shadow:0 14px 30px rgba(0,0,0,.30);
      }
      @media (max-width:980px){
        .yono-games.yono-games--wide{ --wide-cols: 1; }
        .yg-card--wide{ grid-template-columns:1fr; }
      }

      .yg-wide-left{position:relative}
      .yg-wide-logo{
        width:100%;
        aspect-ratio:1/1;
        border-radius:14px;
        object-fit:cover;
        background:#0e1726;
        border:1px solid rgba(148,163,184,.14);
      }

      .yg-wide-right{
        min-width:0;
        display:grid;
        grid-template-rows:auto auto auto auto 1fr;
        row-gap:8px;
      }

      .yg-wide-badges{
        display:flex;
        gap:8px;
        align-items:center;
        flex-wrap:wrap;
      }
      .yg-badge{
        background:#2a1d00;
        color:#fbbf24;
        border:1px solid #f59e0b;
        padding:3px 7px;
        border-radius:8px;
        font-size:11px;
        font-weight:800;
      }
      .yg-pill{
        background:#102138;
        color:#8ab4ff;
        border:1px solid rgba(57,78,140,.6);
        padding:3px 7px;
        border-radius:8px;
        font-size:11px;
        font-weight:800;
      }
      .yg-pill--up{color:#fff; background:#0f172a; border-color:#334155}

      .yg-wide-title{
        margin:2px 0 0;
        font-size:clamp(20px,2.1vw,28px);
        line-height:1.15;
        color:#e8f0fb;
        font-weight:900;
      }
      .yg-wide-sub{
        color:#9aa4b2;
        font-size:13px;
        margin-top:2px;
      }

      .yg-wide-desc{
        display:grid;
        gap:6px;
        background:#0e1726;
        border:1px solid rgba(148,163,184,.14);
        border-radius:12px;
        padding:10px 12px;
        color:#cbd5e1;
        font-size:14px;
      }

      .yg-wide-meta{
        display:flex;
        gap:10px;
        align-items:center;
        flex-wrap:wrap;
      }
      .yg-cd{
        display:inline-flex;
        align-items:center;
        gap:8px;
        background:linear-gradient(180deg, rgba(30,41,59,.6), rgba(15,23,42,.6));
        border:1px solid rgba(148,163,184,.22);
        color:#dbeafe;
        padding:6px 10px;
        border-radius:10px;
        font-weight:800;
        font-size:12.5px;
      }
      .yg-cd .yg-countdown{font-weight:900; color:#fff}

      .yg-wide-cta-row{
        display:flex;
        align-items:center;
        gap:10px;
        margin-top:4px;
      }
      .yg-cta--wide{
        flex:0 0 auto;
        padding:12px 16px;
        border-radius:12px;
        border:0;
        background:linear-gradient(135deg,#f97316,#ef4444);
        color:#fff;
        font-weight:900;
        text-decoration:none;
      }
      .yg-cta--ghost{
        background:#142235;
        color:#cbd5e1;
        border:1px solid rgba(148,163,184,.25);
      }
      .yg-cta--wide[disabled]{opacity:.65; cursor:not-allowed}
      .yg-save--wide{
        background:#0f172a;
        color:#cbd5e1;
        border:1px solid rgba(148,163,184,.25);
        border-radius:12px;
        padding:10px 12px;
        cursor:pointer;
      }

      .yono-games.yono-games--wide .yg-grid{
        display:grid;
        gap:16px;
        grid-template-columns: repeat(var(--wide-cols, 1), minmax(520px, 1fr));
      }
      </style>';
    }

    if ($is_wide){
      // Slightly stronger wide tweaks
      $head .= '<style>
      .yg-card--wide{
        display:grid;
        grid-template-columns:minmax(220px,32vw) 1fr;
        gap:24px;
        align-items:start;
        background:#0b1220;
        border:1px solid rgba(148,163,184,.14);
        border-radius:18px;
        padding:18px;
        box-shadow:0 10px 28px rgba(0,0,0,.25);
      }
      @media (min-width:1100px){
        .yg-card--wide{ grid-template-columns:300px 1fr; }
      }
      @media (max-width:860px){
        .yg-card--wide{ grid-template-columns:1fr; }
      }
      .yg-wide-logo{
        width:100%;
        aspect-ratio:1/1;
        border-radius:14px;
        object-fit:cover;
        background:#0e1726;
        border:1px solid rgba(148,163,184,.14);
      }
      .yg-badge{
        background:#2a1d00;
        color:#fbbf24;
        border:1px solid #f59e0b;
        padding:4px 8px;
        border-radius:8px;
        font-size:11px;
        font-weight:800;
      }
      .yg-pill{
        background:#102138;
        color:#8ab4ff;
        border:1px solid rgba(57,78,140,.6);
        padding:4px 8px;
        border-radius:8px;
        font-size:11px;
        font-weight:800;
      }
      .yg-wide-title{
        margin:0;
        font-size:clamp(22px,2.4vw,30px);
        line-height:1.15;
        color:#e8f0fb;
        font-weight:800;
      }
      .yg-cta--wide{
        flex:1 1 auto;
        display:inline-flex;
        justify-content:center;
        align-items:center;
        padding:14px 18px;
        border-radius:12px;
        border:0;
        background:linear-gradient(135deg,#f97316,#ef4444);
        color:#fff;
        font-weight:800;
        text-decoration:none;
      }
      .yg-card--wide.is-upcoming{
        border-color:rgba(59,130,246,.35);
      }
      </style>';
    }

    // body
    ob_start(); ?>
    <section class="yono-games<?php echo $is_wide ? ' yono-games--wide' : ''; ?>"
             data-columns="<?php echo $cols; ?>"
             style="--card-min: <?php echo esc_attr($card_min); ?>; --wide-cols: <?php echo intval($wide_cols); ?>">

      <div class="yg-toolbar">
        <?php if (filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN)): ?>
          <input type="search" class="yg-search" placeholder="Search games, categories, badges…">
        <?php endif; ?>

        <?php if (filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN)): ?>
          <div class="yg-count"><?php echo sprintf('Showing %d games', $count); ?></div>
        <?php endif; ?>

        <?php if (filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN)): ?>
          <div class="yg-cat-chips">
            <button class="yg-chip is-active" data-cat="">All</button>
            <?php
            $terms = get_terms(['taxonomy'=>self::TAX_CAT,'hide_empty'=>false]);
            if (!is_wp_error($terms)):
              foreach ($terms as $t): ?>
                <button class="yg-chip" data-cat="<?php echo esc_attr($t->name); ?>"><?php echo esc_html($t->name); ?></button>
              <?php endforeach;
            endif; ?>
          </div>
        <?php endif; ?>

        <div class="yg-sort">
          <label>Sort:</label>
          <select class="yg-sort-select">
            <option value="name"   <?php selected($atts['sort'],'name'); ?>>Name (A–Z)</option>
            <option value="launch" <?php selected($atts['sort'],'launch'); ?>>Launch date</option>
            <option value="latest" <?php selected($atts['sort'],'latest'); ?>>Latest added</option>
          </select>
        </div>
      </div>

      <div class="yg-grid">
        <?php foreach ($items as $g):
          $badges = implode(' ', array_map('sanitize_title',(array)$g['badge']));
          $cats   = implode(' ', array_map('sanitize_title',(array)$g['cat']));
          $is_up  = ($g['status'] === 'upcoming');
          ?>

          <?php if (!$is_wide): ?>
            <article class="yg-card <?php echo $is_up ? 'is-upcoming' : ''; ?>"
                     data-name="<?php echo esc_attr(mb_strtolower($g['title'])); ?>"
                     data-cats="<?php echo esc_attr($cats); ?>"
                     data-badges="<?php echo esc_attr($badges); ?>"
                     data-launch="<?php echo esc_attr($g['launch']); ?>">

              <div class="yg-card-head">
                <?php if (!empty($g['badge'])): ?>
                  <?php foreach ($g['badge'] as $b):
                    $effect_class = yono_badge_effect_class_by_slug($b); ?>
                    <span class="yg-badge<?php echo esc_attr($effect_class); ?>">
                      <?php echo esc_html(ucwords(str_replace('-', ' ', $b))); ?>
                    </span>
                  <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($g['cat'])): ?>
                  <?php foreach ($g['cat'] as $c): ?>
                    <span class="yg-pill"><?php echo esc_html($c); ?></span>
                  <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($is_up): ?>
                  <span class="yg-pill">Upcoming</span>
                <?php endif; ?>
              </div>

              <div class="yg-card-body">
                <?php if ($g['logo']): ?>
                  <img src="<?php echo esc_url($g['logo']); ?>" class="yg-logo" alt="">
                <?php endif; ?>
                <div>
                  <h3 class="yg-title"><?php echo esc_html($g['title']); ?></h3>
                  <?php if ($g['subtitle']): ?>
                    <div class="yg-sub"><?php echo esc_html($g['subtitle']); ?></div>
                  <?php endif; ?>
                  <div class="yg-spec">
                    <div>🎁 <strong>Welcome bonus:</strong> <?php echo esc_html($g['bonus']); ?></div>
                    <div>💳 <strong>Minimum Withdrawal:</strong> <?php echo esc_html($g['minwd']); ?></div>
                  </div>
                  <?php if ($is_up): ?>
                    <div class="yg-coming">
                      <span class="yg-coming-label">Launches in</span>
                      <span class="yg-countdown"
                            data-launch="<?php echo esc_attr($g['launch']); ?>"
                            aria-live="polite">—</span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="yg-card-foot">
                <?php if ($g['cta_url']): ?>
                  <a class="yg-cta"
                     href="<?php echo esc_url($g['cta_url']); ?>"
                     target="_blank" rel="nofollow noopener">
                    <?php echo esc_html($g['cta_text']); ?>
                  </a>
                <?php else: ?>
                  <button class="yg-cta" disabled><?php echo esc_html($g['cta_text']); ?></button>
                <?php endif; ?>
                <button class="yg-save" type="button" title="Save card">📥</button>
              </div>
            </article>

          <?php else: ?>
            <article class="yg-card yg-card--wide <?php echo $is_up ? 'is-upcoming':''; ?>"
                     data-name="<?php echo esc_attr(mb_strtolower($g['title'])); ?>"
                     data-cats="<?php echo esc_attr($cats); ?>"
                     data-badges="<?php echo esc_attr($badges); ?>"
                     data-launch="<?php echo esc_attr($g['launch']); ?>">
              <div class="yg-wide-left">
                <?php if ($g['logo']): ?>
                  <img class="yg-wide-logo" src="<?php echo esc_url($g['logo']); ?>" alt="">
                <?php endif; ?>
              </div>
              <div class="yg-wide-right">
                <div class="yg-wide-badges">
                  <?php if (!empty($g['badge'])): ?>
                    <?php foreach ($g['badge'] as $b):
                      $effect_class = yono_badge_effect_class_by_slug($b); ?>
                      <span class="yg-badge<?php echo esc_attr($effect_class); ?>">
                        <?php echo esc_html(ucwords(str_replace('-', ' ', $b))); ?>
                      </span>
                    <?php endforeach; ?>
                  <?php endif; ?>

                  <?php if (!empty($g['cat'])): ?>
                    <?php foreach ($g['cat'] as $c): ?>
                      <span class="yg-pill"><?php echo esc_html($c); ?></span>
                    <?php endforeach; ?>
                  <?php endif; ?>

                  <?php if ($is_up): ?>
                    <span class="yg-pill">Upcoming</span>
                  <?php endif; ?>
                </div>

                <h2 class="yg-wide-title"><?php echo esc_html($g['title']); ?></h2>
                <?php if ($g['subtitle']): ?>
                  <div class="yg-wide-sub"><?php echo esc_html($g['subtitle']); ?></div>
                <?php endif; ?>

                <div class="yg-wide-desc">
                  <div>🎁 <strong>Welcome bonus:</strong> <?php echo esc_html($g['bonus']); ?></div>
                  <div>💳 <strong>Minimum Withdrawal:</strong> <?php echo esc_html($g['minwd']); ?></div>
                </div>

                <div class="yg-wide-meta">
                  <?php if ($is_up): ?>
                    <span class="yg-pill">
                      Launches in:
                      <span class="yg-countdown"
                            data-launch="<?php echo esc_attr($g['launch']); ?>"
                            aria-live="polite">—</span>
                    </span>
                  <?php endif; ?>
                </div>

                <div class="yg-wide-cta-row">
                  <?php if ($g['cta_url']): ?>
                    <a class="yg-cta yg-cta--wide"
                       href="<?php echo esc_url($g['cta_url']); ?>"
                       target="_blank" rel="nofollow noopener">
                      <?php echo esc_html($g['cta_text']); ?>
                    </a>
                  <?php else: ?>
                    <button class="yg-cta yg-cta--wide" disabled><?php echo esc_html($g['cta_text']); ?></button>
                  <?php endif; ?>
                  <button class="yg-save yg-save--wide" type="button" title="Save card">📥</button>
                </div>
              </div>
            </article>
          <?php endif; ?>

        <?php endforeach; ?>
      </div>
    </section>
    <?php

    $body = ob_get_clean();

    // one small countdown script (printed once)
    static $cd_once = false;
    if(!$cd_once){
      $cd_once = true;
      $head .= '<script>(function(){function pad(n){return n<10?"0"+n:n}function tick(){var now=Date.now();document.querySelectorAll(".yg-countdown[data-launch]").forEach(function(el){var t=Date.parse(el.getAttribute("data-launch"));if(!t||isNaN(t)) return;var d=(t-now);if(d<=0){el.textContent="Live";return;}var s=Math.floor(d/1000),days=Math.floor(s/86400);s%=86400;var h=Math.floor(s/3600);s%=3600;var m=Math.floor(s/60);s%=60;el.textContent=(days>0?days+"d ":"")+pad(h)+":"+pad(m)+":"+pad(s);});}tick();setInterval(tick,1000);}())</script>';
    }

    return $head . $body;
  }

  /* ---------------- Modern Review ---------------- */
  public function review_shortcode($atts){
    $atts = shortcode_atts(['id'=>'','slug'=>''], $atts, 'yono_game_review');
    $post = null;
    if ($atts['id']) {
      $post = get_post(intval($atts['id']));
    } elseif ($atts['slug']) {
      $post = get_page_by_path(sanitize_title($atts['slug']), OBJECT, self::CPT);
    }
    if (!$post || $post->post_type !== self::CPT) {
      return '<div class="yg-review-empty">Game not found.</div>';
    }

    $id        = $post->ID;
    $title     = get_the_title($id);
    $logo      = get_post_meta($id,'_yg_logo',true);
    $subtitle  = get_post_meta($id,'_yg_subtitle',true);
    $dev       = get_post_meta($id,'_yg_dev',true);
    $version   = get_post_meta($id,'_yg_version',true);
    $size_mb   = get_post_meta($id,'_yg_size_mb',true);
    $updated   = get_post_meta($id,'_yg_updated',true);
    $downloads = get_post_meta($id,'_yg_downloads',true);
    $rating    = floatval(get_post_meta($id,'_yg_rating',true));
    $votes     = intval(get_post_meta($id,'_yg_votes',true));
    $bonus     = get_post_meta($id,'_yg_bonus',true);
    $minwd     = get_post_meta($id,'_yg_min_withdraw',true);
    $cta_text  = get_post_meta($id,'_yg_cta_text',true) ?: 'Download';
    $cta_url   = get_post_meta($id,'_yg_cta_url',true);
    $telegram  = get_post_meta($id,'_yg_telegram',true);
    $desc_long = get_post_meta($id,'_yg_desc',true);

    static $css_once=false;
    $head='';
    if (!$css_once){
      $css_once=true;
      $head .= '<style id="yono-game-review-modern">
      .yg-review{--bg:#0b1220;--panel:#0e1726;--line:rgba(148,163,184,.16);--text:#e5e7eb;--muted:#9aa4b2;--chip:#111b2c;--chip-line:rgba(148,163,184,.22);
        --brand1:#10b981;--brand2:#059669;
        background:var(--bg);
        border:1px solid var(--line);
        border-radius:20px;
        padding:18px;
        color:var(--text);
        box-shadow:0 10px 28px rgba(0,0,0,.25)}
      .yg-review__head{display:grid;grid-template-columns:88px 1fr auto;gap:16px;align-items:center}
      .yg-review__logo{width:88px;height:88px;border-radius:18px;object-fit:cover;background:var(--panel);border:1px solid var(--line)}
      .yg-review__title{margin:0;font-weight:900;font-size:clamp(22px,2.4vw,30px);line-height:1.1}
      .yg-review__sub{color:var(--muted);margin-top:4px}
      .yg-review__cta{display:flex;gap:10px;flex-wrap:wrap;justify-self:end}
      .yg-btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:12px;padding:12px 14px;font-weight:800;text-decoration:none;white-space:nowrap}
      .yg-btn--primary{background:linear-gradient(135deg,var(--brand1),var(--brand2));color:#fff}
      .yg-btn--ghost{background:var(--panel);border:1px solid var(--line);color:#d1e3ff}
      .yg-chip{background:var(--chip);border:1px solid var(--chip-line);border-radius:999px;padding:6px 10px;font-size:12.5px;color:#cfe1ff;font-weight:800}
      .yg-review__chips{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0 8px}
      .yg-kv{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin:8px 0 14px}
      .yg-kv .kv{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:12px}
      .yg-kv .kv b{display:block;font-size:12px;color:#9aa4b2;margin-bottom:6px}
      @media (max-width:860px){
        .yg-review__head{grid-template-columns:70px 1fr}
        .yg-review__cta{grid-column:1/-1;justify-self:start}
        .yg-kv{grid-template-columns:repeat(2,minmax(0,1fr))}
      }
      </style>';
    }

    ob_start(); ?>
    <article class="yg-review" itemscope itemtype="https://schema.org/SoftwareApplication">
      <meta itemprop="applicationCategory" content="Game">
      <header class="yg-review__head">
        <?php if ($logo): ?>
          <img class="yg-review__logo"
               src="<?php echo esc_url($logo); ?>"
               alt="<?php echo esc_attr($title); ?>"
               itemprop="image">
        <?php endif; ?>
        <div>
          <h2 class="yg-review__title"><?php echo esc_html($title); ?></h2>
          <?php if ($subtitle): ?>
            <div class="yg-review__sub"><?php echo esc_html($subtitle); ?></div>
          <?php endif; ?>
          <div style="margin-top:6px;">
            <?php echo yono_render_stars($rating, 18, 'y-stars y-stars--review'); ?>
            <span class="y-score">
              <?php echo $rating ? esc_html(number_format($rating,1)).'/5' : '—'; ?>
            </span>
            <?php if ($votes): ?>
              <span class="yg-review__sub">
                (<?php echo esc_html(number_format_i18n($votes)); ?> votes)
              </span>
            <?php endif; ?>
          </div>
        </div>
        <div class="yg-review__cta">
          <?php if ($cta_url): ?>
            <a class="yg-btn yg-btn--primary"
               href="<?php echo esc_url($cta_url); ?>"
               target="_blank" rel="nofollow noopener">
              Download
            </a>
          <?php endif; ?>
          <?php if ($telegram): ?>
            <a class="yg-btn yg-btn--ghost"
               href="<?php echo esc_url($telegram); ?>"
               target="_blank" rel="nofollow noopener">
              Telegram
            </a>
          <?php endif; ?>
        </div>
      </header>

      <div class="yg-review__chips">
        <?php
        $cats = wp_get_post_terms($id,self::TAX_CAT,['fields'=>'names']);
        if (!is_wp_error($cats)):
          foreach ((array)$cats as $c): ?>
            <span class="yg-chip"><?php echo esc_html($c); ?></span>
          <?php endforeach;
        endif; ?>
        <?php if ($bonus): ?>
          <span class="yg-chip">Welcome bonus: <?php echo esc_html($bonus); ?></span>
        <?php endif; ?>
        <?php if ($minwd): ?>
          <span class="yg-chip">Min Withdrawal: <?php echo esc_html($minwd); ?></span>
        <?php endif; ?>
      </div>

      <div class="yg-kv">
        <div class="kv"><b>Developer</b><span itemprop="author"><?php echo esc_html($dev ?: '—'); ?></span></div>
        <div class="kv"><b>Version</b><span itemprop="softwareVersion"><?php echo esc_html($version ?: '—'); ?></span></div>
        <div class="kv"><b>Updated</b><span><?php echo esc_html($updated ?: 'Today'); ?></span></div>
        <div class="kv"><b>Size</b><span><?php echo esc_html($size_mb ? $size_mb.' MB' : '—'); ?></span></div>
        <div class="kv"><b>Downloads</b><span><?php echo esc_html($downloads ?: '—'); ?></span></div>
        <div class="kv"><b>Rating</b><span><?php echo $rating?esc_html(number_format($rating,1)).'/5':'—'; ?></span></div>
      </div>

      <?php if ($desc_long): ?>
        <section itemprop="description">
          <?php echo wpautop(wp_kses_post($desc_long)); ?>
        </section>
      <?php endif; ?>
    </article>
    <?php
    $body = ob_get_clean();
    return $head.$body;
  }

  /* -------- Tools: Import / Export CSV -------- */
  public function register_tools_page(){
    add_submenu_page(
      'edit.php?post_type='.self::CPT,
      'Import / Export',
      'Import / Export',
      'manage_options',
      'yono_games_tools',
      [$this,'render_tools_page']
    );
  }

  public function render_tools_page(){ ?>
    <div class="wrap">
      <h1>Yono Games — Import / Export</h1>
      <p>CSV headers: <code>title,subtitle,category,badge,logo,bonus_range,min_withdraw,cta_text,cta_url,launch_at,status,description,developer,version,size_mb,updated,downloads,rating,votes,telegram</code></p>
      <ol>
        <li><strong>category</strong>: Rummy, Slots, Arcade, Bingo (multiple via <code>|</code>)</li>
        <li><strong>badge</strong>: New, Coming Soon, Hot (multiple via <code>|</code>)</li>
        <li><strong>launch_at</strong>: YYYY-MM-DD HH:MM (site local time)</li>
        <li><strong>status</strong>: active | upcoming | retired</li>
      </ol>
      <h2>Export</h2>
      <p>
        <a class="button button-primary"
           href="<?php echo esc_url(admin_url('admin-ajax.php?action=yono_games_export&_wpnonce='.wp_create_nonce('yono_games_export'))); ?>">
          Download CSV
        </a>
      </p>
      <hr>
      <h2>Import</h2>
      <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('yono_games_import','_yg_imp'); ?>
        <input type="hidden" name="action" value="yono_games_import">
        <input type="file" name="csv" accept=".csv" required>
        <button type="submit" class="button">Import CSV</button>
      </form>
    </div>
  <?php }

  public function handle_export(){
    check_ajax_referer('yono_games_export');
    if (!current_user_can('manage_options')) wp_die('Not allowed');

    $q = new WP_Query([
      'post_type'      => self::CPT,
      'posts_per_page' => -1,
      'no_found_rows'  => true,
    ]);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=yono-games-export.csv');
    $out = fopen('php://output','w');

    fputcsv($out, [
      'title','subtitle','category','badge','logo','bonus_range','min_withdraw',
      'cta_text','cta_url','launch_at','status','description','developer','version',
      'size_mb','updated','downloads','rating','votes','telegram'
    ]);

    while($q->have_posts()){
      $q->the_post();
      $id   = get_the_ID();
      $cats = wp_get_post_terms($id,self::TAX_CAT,['fields'=>'names']);
      $badg = wp_get_post_terms($id,self::TAX_BADGE,['fields'=>'names']);
      fputcsv($out, [
        get_the_title(),
        get_post_meta($id,'_yg_subtitle',true),
        implode('|',$cats),
        implode('|',$badg),
        get_post_meta($id,'_yg_logo',true),
        get_post_meta($id,'_yg_bonus',true),
        get_post_meta($id,'_yg_min_withdraw',true),
        get_post_meta($id,'_yg_cta_text',true),
        get_post_meta($id,'_yg_cta_url',true),
        yono_iso_to_local_value(get_post_meta($id,'_yg_launch_at',true)),
        get_post_meta($id,'_yg_status',true),
        get_post_meta($id,'_yg_desc',true),
        get_post_meta($id,'_yg_dev',true),
        get_post_meta($id,'_yg_version',true),
        get_post_meta($id,'_yg_size_mb',true),
        get_post_meta($id,'_yg_updated',true),
        get_post_meta($id,'_yg_downloads',true),
        get_post_meta($id,'_yg_rating',true),
        get_post_meta($id,'_yg_votes',true),
        get_post_meta($id,'_yg_telegram',true),
      ]);
    }
    wp_reset_postdata();
    fclose($out);
    wp_die();
  }

  public function handle_import(){
    if (!current_user_can('manage_options')) wp_die('Not allowed');
    if (!isset($_POST['_yg_imp']) || !wp_verify_nonce($_POST['_yg_imp'],'yono_games_import')) wp_die('Bad nonce');
    if (empty($_FILES['csv']['tmp_name'])) wp_die('No file');

    $fh = fopen($_FILES['csv']['tmp_name'],'r');
    $header = fgetcsv($fh);
    $map = array_flip($header);

    while(($row=fgetcsv($fh))!==false){
      $title = isset($row[$map['title']]) ? sanitize_text_field($row[$map['title']]) : '';
      if (!$title) continue;

      $existing = get_page_by_title($title, OBJECT, self::CPT);
      $post_id = $existing ? $existing->ID : wp_insert_post([
        'post_type'   => self::CPT,
        'post_title'  => $title,
        'post_status' => 'publish',
      ]);

      update_post_meta($post_id,'_yg_subtitle',     isset($row[$map['subtitle']])     ? sanitize_text_field($row[$map['subtitle']]) : '');
      update_post_meta($post_id,'_yg_logo',         isset($row[$map['logo']])         ? esc_url_raw($row[$map['logo']]) : '');
      update_post_meta($post_id,'_yg_bonus',        isset($row[$map['bonus_range']])  ? sanitize_text_field($row[$map['bonus_range']]) : '');
      update_post_meta($post_id,'_yg_min_withdraw', isset($row[$map['min_withdraw']]) ? sanitize_text_field($row[$map['min_withdraw']]) : '');
      update_post_meta($post_id,'_yg_cta_text',     isset($row[$map['cta_text']])     ? sanitize_text_field($row[$map['cta_text']]) : 'Get Started');
      update_post_meta($post_id,'_yg_cta_url',      isset($row[$map['cta_url']])      ? esc_url_raw($row[$map['cta_url']]) : '');
      $launch_local = isset($row[$map['launch_at']]) ? sanitize_text_field($row[$map['launch_at']]) : '';
      update_post_meta($post_id,'_yg_launch_at',    yono_local_to_iso_utc($launch_local));
      update_post_meta($post_id,'_yg_status',       isset($row[$map['status']])       ? sanitize_text_field($row[$map['status']]) : 'active');
      update_post_meta($post_id,'_yg_desc',         isset($row[$map['description']])  ? sanitize_textarea_field($row[$map['description']]) : '');
      update_post_meta($post_id,'_yg_dev',        isset($row[$map['developer']]) ? sanitize_text_field($row[$map['developer']]) : '');
      update_post_meta($post_id,'_yg_version',    isset($row[$map['version']])   ? sanitize_text_field($row[$map['version']])   : '');
      update_post_meta($post_id,'_yg_size_mb',    isset($row[$map['size_mb']])   ? sanitize_text_field($row[$map['size_mb']])   : '');
      update_post_meta($post_id,'_yg_updated',    isset($row[$map['updated']])   ? sanitize_text_field($row[$map['updated']])   : '');
      update_post_meta($post_id,'_yg_downloads',  isset($row[$map['downloads']]) ? sanitize_text_field($row[$map['downloads']]) : '');
      update_post_meta($post_id,'_yg_rating',     isset($row[$map['rating']])    ? floatval($row[$map['rating']]) : '');
      update_post_meta($post_id,'_yg_votes',      isset($row[$map['votes']])     ? intval($row[$map['votes']]) : '');
      update_post_meta($post_id,'_yg_telegram',   isset($row[$map['telegram']])  ? esc_url_raw($row[$map['telegram']]) : '');

      $cats = isset($row[$map['category']]) ? array_filter(array_map('trim', explode('|', $row[$map['category']]))) : [];
      $badg = isset($row[$map['badge']])    ? array_filter(array_map('trim', explode('|', $row[$map['badge']])))    : [];
      if ($cats) wp_set_object_terms($post_id, $cats, self::TAX_CAT, false);
      if ($badg) wp_set_object_terms($post_id, $badg, self::TAX_BADGE, false);
    }
    fclose($fh);
    wp_safe_redirect( admin_url('edit.php?post_type='.self::CPT.'&import=1') );
    exit;
  }

}
new Yono_Games();

/* =========================================================
 * CONTEXT GAME DETECTOR
 * ========================================================= */
if (!function_exists('yono_detect_game_from_context')) {
  function yono_detect_game_from_context($maybe_slug = ''){
    if ($maybe_slug){
      $p = get_page_by_path(sanitize_title($maybe_slug), OBJECT, 'yono_game');
      if ($p) return $p;
    }
    if (is_singular('yono_game')) return get_queried_object();
    if (is_singular()){
      $pid = get_the_ID();
      $content = get_post_field('post_content', $pid);
      if (preg_match('/\[yono_game_review\s+[^\]]*slug\s*=\s*"(.*?)"/i', $content, $m)){
        $slug = sanitize_title($m[1]);
        $p = get_page_by_path($slug, OBJECT, 'yono_game');
        if ($p) return $p;
      }
    }
    return null;
  }
}

/* =========================================================
 * LATEST APPS — whole card clickable + badges + gold stars
 * Usage: [yono_latest_apps count="6" cat="Rummy,Slots" order="latest"]
 * ========================================================= */
if (shortcode_exists('yono_latest_apps')) remove_shortcode('yono_latest_apps');
add_shortcode('yono_latest_apps', function($atts){
  $a = shortcode_atts([
    'count'=>6,
    'cat'  =>'',
    'badge'=>'',
    'order'=>'latest'
  ], $atts, 'yono_latest_apps');

  $tax_query=[];
  if (!empty($a['cat'])){
    $tax_query[]=[
      'taxonomy'=>'game_cat',
      'field'   =>'name',
      'terms'   =>array_filter(array_map('trim', explode(',', $a['cat'])) ),
    ];
  }
  if (!empty($a['badge'])){
    $tax_query[]=[
      'taxonomy'=>'game_badge',
      'field'   =>'name',
      'terms'   =>array_filter(array_map('trim', explode(',', $a['badge'])) ),
    ];
  }

  $orderby='date'; $order='DESC';
  if (strtolower($a['order'])==='name'){ $orderby='title'; $order='ASC'; }
  if (strtolower($a['order'])==='random'){ $orderby='rand'; }

  $q = new WP_Query([
    'post_type'      => 'yono_game',
    'posts_per_page' => max(1,intval($a['count'])),
    'no_found_rows'  => true,
    'tax_query'      => $tax_query,
    'orderby'        => $orderby,
    'order'          => $order,
  ]);
  if (!$q->have_posts()) return '';

  $css = '<style>
  .yl-list{display:grid;gap:12px}
  .yl-item{position:relative;display:grid;grid-template-columns:64px 1fr;gap:10px;align-items:center;background:#0e1014;border:1px solid rgba(148,163,184,.15);border-radius:14px;padding:10px}
  .yl-link{position:absolute;inset:0;z-index:1}
  .yl-logo{width:64px;height:64px;border-radius:12px;object-fit:cover;background:#0b1220;border:1px solid rgba(148,163,184,.16);z-index:2}
  .yl-title{margin:0;font-weight:800;color:#e6edf7;line-height:1.1;z-index:2}
  .yl-sub{color:#9aa4b2;font-size:12.5px;margin-top:2px;z-index:2}
  .yl-meta{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;z-index:2}
  .yl-chip{background:#102138;color:#cfe1ff;border:1px solid rgba(57,78,140,.55);border-radius:999px;padding:3px 8px;font-size:11px;font-weight:800}
  .yl-badge{background:#2a1d00;border:1px solid #f59e0b;color:#fbbf24;border-radius:8px;padding:3px 8px;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.2px}
  </style>';

  $out = '<div class="yl-list">';
  while($q->have_posts()){
    $q->the_post();
    $id   = get_the_ID();
    $logo = get_post_meta($id,'_yg_logo',true);
    $sub  = get_post_meta($id,'_yg_subtitle',true);
    $ver  = get_post_meta($id,'_yg_version',true);
    $dev  = get_post_meta($id,'_yg_dev',true);
    $rate = floatval(get_post_meta($id,'_yg_rating',true));

    $badges = wp_get_post_terms($id, 'game_badge', ['fields'=>'names']);
    $badge_html = '';
    if (!is_wp_error($badges) && $badges) {
      foreach ($badges as $b) {
        $badge_html .= '<span class="yl-badge' . esc_attr( yono_badge_effect_class_by_name($b) ) . '">' .
                         esc_html($b) .
                       '</span>';
      }
    }

    $out .= '<article class="yl-item">'.
              ($logo?'<img class="yl-logo" src="'.esc_url($logo).'" alt="'.esc_attr(get_the_title()).'">':'').
              '<div>'.
                '<h4 class="yl-title">'.esc_html(get_the_title()).'</h4>'.
                ($sub?'<div class="yl-sub">'.esc_html($sub).'</div>':'').
                '<div class="yl-meta">'.
                  $badge_html.
                  ($dev?'<span class="yl-chip">'.esc_html($dev).'</span>':'').
                  ($ver?'<span class="yl-chip">v'.esc_html($ver).'</span>':'').
                '</div>'.
                yono_render_stars($rate, 14, 'y-stars') .
                '<span class="y-score">'.($rate?esc_html(number_format($rate,1)).'/5':'—').'</span>'.
              '</div>'.
              '<a class="yl-link" href="'.esc_url(get_permalink($id)).'"></a>'.
            '</article>';
  }
  wp_reset_postdata();
  $out .= '</div>';
  return $css.$out;
});

/* =========================================================
 * RELATED APPS — finds games that share Category / Badge
 * Usage: [yono_related_apps count="6" by="cat,badge" order="relevance"]
 * ========================================================= */
if (shortcode_exists('yono_related_apps')) remove_shortcode('yono_related_apps');
add_shortcode('yono_related_apps', function($atts){
  $a = shortcode_atts([
    'count' => 6,
    'by'    => 'cat,badge',    // cat | badge | cat,badge
    'order' => 'relevance',    // relevance | latest | random
    'id'    => '',
    'slug'  => '',
  ], $atts, 'yono_related_apps');

  // Find the "source" game: by id/slug or from page context
  $source = null;
  if ($a['id']) {
    $source = get_post(intval($a['id']));
  }
  if (!$source && $a['slug']) {
    $source = get_page_by_path(sanitize_title($a['slug']), OBJECT, 'yono_game');
  }
  if (!$source && function_exists('yono_detect_game_from_context')) {
    $source = yono_detect_game_from_context();
  }
  if (!$source || $source->post_type !== 'yono_game') return '';

  $source_id = $source->ID;
  $use_cat   = stripos($a['by'],'cat')   !== false;
  $use_badge = stripos($a['by'],'badge') !== false;

  $tax_query = ['relation' => 'OR'];
  $cat_ids   = $use_cat   ? wp_get_post_terms($source_id, 'game_cat',   ['fields'=>'ids']) : [];
  $badg_ids  = $use_badge ? wp_get_post_terms($source_id, 'game_badge', ['fields'=>'ids']) : [];

  if ($use_cat && $cat_ids) {
    $tax_query[] = ['taxonomy'=>'game_cat','field'=>'term_id','terms'=>$cat_ids];
  }
  if ($use_badge && $badg_ids) {
    $tax_query[] = ['taxonomy'=>'game_badge','field'=>'term_id','terms'=>$badg_ids];
  }

  // If no shared terms, show nothing early.
  if (count($tax_query) === 1) return '';

  // Base query
  $orderby='date'; $order='DESC';
  if ($a['order']==='random') {
    $orderby='rand';
  } elseif ($a['order']==='relevance') {
    $orderby='date'; $order='DESC';
  }

  // Pull a few extra so manual relevance sort has room
  $fetch = max(intval($a['count']) * 3, 12);

  $q = new WP_Query([
    'post_type'      => 'yono_game',
    'post__not_in'   => [$source_id],
    'posts_per_page' => $fetch,
    'tax_query'      => $tax_query,
    'orderby'        => $orderby,
    'order'          => $order,
    'no_found_rows'  => true,
  ]);
  if (!$q->have_posts()) return '';

  // Manual relevance score = shared categories + shared badges
  $scored = [];
  while($q->have_posts()){
    $q->the_post();
    $id = get_the_ID();
    $c  = $use_cat   ? wp_get_post_terms($id,'game_cat',['fields'=>'ids'])   : [];
    $b  = $use_badge ? wp_get_post_terms($id,'game_badge',['fields'=>'ids']) : [];
    $score = 0;
    if ($cat_ids && $c) $score += count(array_intersect($cat_ids, $c));
    if ($badg_ids && $b) $score += count(array_intersect($badg_ids, $b));
    $scored[] = ['id'=>$id, 'score'=>$score];
  }
  wp_reset_postdata();

  if ($a['order']==='relevance') {
    usort($scored, function($x,$y){
      if ($x['score'] === $y['score']) return 0;
      return ($x['score'] > $y['score']) ? -1 : 1;
    });
  } else {
    shuffle($scored);
  }

  $ids = array_slice(array_column($scored,'id'), 0, max(1,intval($a['count'])));
  if (!$ids) return '';

  // Reuse the “Latest Apps” compact card visuals (same classes)
  $css = '<style>
    .yl-list{display:grid;gap:12px}
    .yl-item{position:relative;display:grid;grid-template-columns:64px 1fr;gap:10px;align-items:center;background:#0e1014;border:1px solid rgba(148,163,184,.15);border-radius:14px;padding:10px}
    .yl-link{position:absolute;inset:0;z-index:1}
    .yl-logo{width:64px;height:64px;border-radius:12px;object-fit:cover;background:#0b1220;border:1px solid rgba(148,163,184,.16);z-index:2}
    .yl-title{margin:0;font-weight:800;color:#e6edf7;line-height:1.1;z-index:2}
    .yl-sub{color:#9aa4b2;font-size:12.5px;margin-top:2px;z-index:2}
    .yl-meta{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;z-index:2}
    .yl-chip{background:#102138;color:#cfe1ff;border:1px solid rgba(57,78,140,.55);border-radius:999px;padding:3px 8px;font-size:11px;font-weight:800}
    .yl-badge{background:#2a1d00;border:1px solid #f59e0b;color:#fbbf24;border-radius:8px;padding:3px 8px;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.2px}
  </style>';

  $out = '<div class="yl-list">';
  foreach ($ids as $id){
    $logo = get_post_meta($id,'_yg_logo',true);
    $sub  = get_post_meta($id,'_yg_subtitle',true);
    $ver  = get_post_meta($id,'_yg_version',true);
    $dev  = get_post_meta($id,'_yg_dev',true);
    $rate = floatval(get_post_meta($id,'_yg_rating',true));

    $badges = wp_get_post_terms($id, 'game_badge', ['fields'=>'names']);
    $badge_html = '';
    if (!is_wp_error($badges) && $badges) {
      foreach ($badges as $b) {
        $badge_html .= '<span class="yl-badge' . esc_attr( yono_badge_effect_class_by_name($b) ) . '">' .
                         esc_html($b) .
                       '</span>';
      }
    }

    $out .= '<article class="yl-item">'.
              ($logo?'<img class="yl-logo" src="'.esc_url($logo).'" alt="'.esc_attr(get_the_title($id)).'">':'').
              '<div>'.
                '<h4 class="yl-title">'.esc_html(get_the_title($id)).'</h4>'.
                ($sub?'<div class="yl-sub">'.esc_html($sub).'</div>':'').
                '<div class="yl-meta">'.
                   $badge_html.
                   ($dev?'<span class="yl-chip">'.esc_html($dev).'</span>':'').
                   ($ver?'<span class="yl-chip">v'.esc_html($ver).'</span>':'').
                '</div>'.
                yono_render_stars($rate, 14, 'y-stars') .
                '<span class="y-score">'.($rate?esc_html(number_format($rate,1)).'/5':'—').'</span>'.
              '</div>'.
              '<a class="yl-link" href="'.esc_url(get_permalink($id)).'"></a>'.
            '</article>';
  }
  $out .= '</div>';

  return $css.$out;
});

/* =========================================================
 * PAGINATION HELPER
 * ========================================================= */
if (!function_exists('yono_render_archive_pagination')) {
  function yono_render_archive_pagination($total_pages, $current, $query_key='yg_page') {
    if ($total_pages < 2) return '';
    $base = remove_query_arg($query_key);
    $links = paginate_links([
      'base'      => add_query_arg($query_key, '%#%', $base ? $base : get_permalink()),
      'format'    => '',
      'current'   => max(1, $current),
      'total'     => $total_pages,
      'type'      => 'array',
      'prev_text' => __('«'),
      'next_text' => __('Next »'),
    ]);
    if (!$links) return '';
    $out = '<nav class="yga-pager">';
    foreach($links as $l){
      $out .= '<span class="yga-page">'.$l.'</span>';
    }
    $out .= '</nav>';
    return $out;
  }
}

/* =========================================================
 * LIST ARCHIVE — like screenshot #1
 * Usage: [yono_games_list_archive per_page="10" cat="Rummy,Slots" order="latest"]
 * ========================================================= */
add_shortcode('yono_games_list_archive', function($atts){
  $a = shortcode_atts([
    'per_page'=>10,
    'cat'     =>'',
    'badge'   =>'',
    'order'   =>'latest',
    'qvar'    =>'yg_page'
  ], $atts, 'yono_games_list_archive');

  $paged = isset($_GET[$a['qvar']]) ? max(1, intval($_GET[$a['qvar']])) : 1;

  $tax_query=[];
  if ($a['cat']){
    $tax_query[] = [
      'taxonomy'=>'game_cat',
      'field'   =>'name',
      'terms'   =>array_map('trim', explode(',', $a['cat'])),
    ];
  }
  if ($a['badge']){
    $tax_query[] = [
      'taxonomy'=>'game_badge',
      'field'   =>'name',
      'terms'   =>array_map('trim', explode(',', $a['badge'])),
    ];
  }

  $orderby='date'; $order='DESC';
  if ($a['order']==='name'){ $orderby='title'; $order='ASC'; }
  if ($a['order']==='random'){ $orderby='rand'; }

  $q = new WP_Query([
    'post_type'      =>'yono_game',
    'posts_per_page' =>max(1,intval($a['per_page'])),
    'paged'          =>$paged,
    'tax_query'      =>$tax_query,
    'orderby'        =>$orderby,
    'order'          =>$order,
  ]);

  $css = '<style>
  .yga-list{display:grid;gap:12px}
  .yga-li{position:relative;display:grid;grid-template-columns:64px 1fr;gap:10px;align-items:center;background:#0e1014;border:1px solid rgba(148,163,184,.15);border-radius:14px;padding:10px}
  .yga-link{position:absolute;inset:0;z-index:1}
  .yga-logo{width:64px;height:64px;border-radius:12px;object-fit:cover;background:#0b1220;border:1px solid rgba(148,163,184,.16);z-index:2}
  .yga-ttl{margin:0 0 2px;font-weight:800;color:#e6edf7;line-height:1.1;z-index:2}
  .yga-sub{color:#9aa4b2;font-size:12.5px;z-index:2}
  .yga-meta{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;z-index:2}
  .yga-chip{background:#102138;color:#cfe1ff;border:1px solid rgba(57,78,140,.55);border-radius:999px;padding:3px 8px;font-size:11px;font-weight:800}
  .yga-badge{background:#2a1d00;border:1px solid #f59e0b;color:#fbbf24;border-radius:8px;padding:3px 8px;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.2px}
  .yga-pager{display:flex;gap:6px;margin-top:14px;flex-wrap:wrap}
  .yga-pager a, .yga-pager span{display:inline-block;padding:6px 10px;border:1px solid rgba(148,163,184,.2);border-radius:8px;background:#0e1014;color:#e6edf7;text-decoration:none}
  .yga-pager .current{background:#1e293b;border-color:#3b82f6}
  </style>';

  $out = '<div class="yga-list">';
  while($q->have_posts()){
    $q->the_post();
    $id   = get_the_ID();
    $logo = get_post_meta($id,'_yg_logo',true);
    $sub  = get_post_meta($id,'_yg_subtitle',true);
    $ver  = get_post_meta($id,'_yg_version',true);
    $dev  = get_post_meta($id,'_yg_dev',true);
    $rate = floatval(get_post_meta($id,'_yg_rating',true));

    $badg = wp_get_post_terms($id,'game_badge',['fields'=>'names']);
    $badge_html = '';
    if (!is_wp_error($badg) && $badg) {
      foreach ($badg as $b) {
        $badge_html .= '<span class="yga-badge' . esc_attr( yono_badge_effect_class_by_name($b) ) . '">' .
                         esc_html($b) .
                       '</span>';
      }
    }

    $out .= '<article class="yga-li">'.
      ($logo?'<img class="yga-logo" src="'.esc_url($logo).'" alt="'.esc_attr(get_the_title()).'">':'').
      '<div>'.
        '<h4 class="yga-ttl">'.esc_html(get_the_title()).'</h4>'.
        ($sub?'<div class="yga-sub">'.esc_html($sub).'</div>':'').
        '<div class="yga-meta">'.
          $badge_html.
          ($dev?'<span class="yga-chip">'.esc_html($dev).'</span>':'').
          ($ver?'<span class="yga-chip">v'.esc_html($ver).'</span>':'').
        '</div>'.
        yono_render_stars($rate, 14, 'y-stars') .
        '<span class="y-score">'.($rate?esc_html(number_format($rate,1)).'/5':'—').'</span>'.
      '</div>'.
      '<a class="yga-link" href="'.esc_url(get_permalink($id)).'"></a>'.
    '</article>';
  }
  wp_reset_postdata();
  $out .= '</div>';
  $out .= yono_render_archive_pagination($q->max_num_pages, $paged, $a['qvar']);
  return $css.$out;
});

/* =========================================================
 * GRID ARCHIVE — like screenshot #2
 * Usage: [yono_games_grid_archive per_page="12" columns="5" cat="Rummy"]
 * ========================================================= */
add_shortcode('yono_games_grid_archive', function($atts){
  $a = shortcode_atts([
    'per_page'=>12,
    'columns' =>5,
    'cat'     =>'',
    'badge'   =>'',
    'order'   =>'latest',
    'qvar'    =>'yg_page'
  ], $atts, 'yono_games_grid_archive');

  $paged = isset($_GET[$a['qvar']]) ? max(1,intval($_GET[$a['qvar']])) : 1;

  $tax_query=[];
  if ($a['cat']){
    $tax_query[]=[
      'taxonomy'=>'game_cat',
      'field'   =>'name',
      'terms'   =>array_map('trim', explode(',', $a['cat'])),
    ];
  }
  if ($a['badge']){
    $tax_query[]=[
      'taxonomy'=>'game_badge',
      'field'   =>'name',
      'terms'   =>array_map('trim', explode(',', $a['badge'])),
    ];
  }

  $orderby='date'; $order='DESC';
  if ($a['order']==='name'){ $orderby='title'; $order='ASC'; }
  if ($a['order']==='random'){ $orderby='rand'; }

  $q = new WP_Query([
    'post_type'      =>'yono_game',
    'posts_per_page' =>max(1,intval($a['per_page'])),
    'paged'          =>$paged,
    'tax_query'      =>$tax_query,
    'orderby'        =>$orderby,
    'order'          =>$order,
  ]);

  $cols = max(2, min(6, intval($a['columns'])));
  $css = '<style>
  .yg-grid-arch{display:grid;gap:22px;grid-template-columns:repeat('.$cols.', minmax(0,1fr))}
  @media(max-width:1100px){.yg-grid-arch{grid-template-columns:repeat(4,minmax(0,1fr))}}
  @media(max-width:920px){.yg-grid-arch{grid-template-columns:repeat(3,minmax(0,1fr))}}
  @media(max-width:720px){.yg-grid-arch{grid-template-columns:repeat(2,minmax(0,1fr))}}
  .yg-card-arch{position:relative;display:block;background:#0e1014;border:1px solid rgba(148,163,184,.15);border-radius:14px;padding:10px;color:inherit;text-decoration:none}
  .yg-ribbon{position:absolute;left:8px;top:8px;background:#ef4444;color:#fff;font-weight:900;font-size:10px;padding:3px 6px;border-radius:6px;z-index:2}
  .yg-logo-arch{width:100%;aspect-ratio:1/1;border-radius:12px;object-fit:cover;background:#0b1220;border:1px solid rgba(148,163,184,.16)}
  .yg-ttl-arch{margin:8px 0 2px;font-weight:800;color:#e6edf7;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .yg-sub-arch{color:#9aa4b2;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .yga-pager{display:flex;gap:6px;margin-top:18px;flex-wrap:wrap}
  .yga-pager a, .yga-pager span{display:inline-block;padding:6px 10px;border:1px solid rgba(148,163,184,.2);border-radius:8px;background:#0e1014;color:#e6edf7;text-decoration:none}
  .yga-pager .current{background:#1e293b;border-color:#3b82f6}
  </style>';

  $out = '<div class="yg-grid-arch">';
  while($q->have_posts()){
    $q->the_post();
    $id   = get_the_ID();
    $logo = get_post_meta($id,'_yg_logo',true);
    $sub  = get_post_meta($id,'_yg_subtitle',true);
    $rate = floatval(get_post_meta($id,'_yg_rating',true));

    $badg   = wp_get_post_terms($id,'game_badge',['fields'=>'slugs']);
    $slugs  = is_wp_error($badg) ? [] : array_map('sanitize_title', (array)$badg);
    $effect = '';

    if (in_array('new', $slugs, true)) {
      // Apply glow/pulse class defined in yono_badge_effect_class_by_slug()
      $effect = yono_badge_effect_class_by_slug('new');
    }

    $ribbon = in_array('new', $slugs, true)
      ? '<span class="yg-ribbon' . esc_attr($effect) . '">NEW</span>'
      : '';

    $out .= '<a class="yg-card-arch" href="'.esc_url(get_permalink($id)).'">'.
      $ribbon.
      ($logo?'<img class="yg-logo-arch" src="'.esc_url($logo).'" alt="'.esc_attr(get_the_title()).'">':'').
      '<div class="yg-ttl-arch">'.esc_html(get_the_title()).'</div>'.
      ($sub?'<div class="yg-sub-arch">'.esc_html($sub).'</div>':'').
      yono_render_stars($rate, 14, 'y-stars').
    '</a>';
  }
  wp_reset_postdata();
  $out .= '</div>';
  $out .= yono_render_archive_pagination($q->max_num_pages, $paged, $a['qvar']);
  return $css.$out;
});

/* =========================================================
 * ROOT PERMALINKS FOR yono_game  →  https://site.com/<slug>/
 * with collision safety + redirects
 * ========================================================= */

/** Detect if a slug would collide with existing content at root */
function yono_game_slug_conflicts($slug){
  if (get_page_by_path($slug, OBJECT, ['post','page'])) return true;
  if (term_exists($slug, 'category') || term_exists($slug, 'post_tag')) return true;
  return false;
}

/* ===== Root slugs for Yono Games (no /game/) ===== */

/** Optional: make games win even if a Page/Post has the same slug */
// define('YONO_GAMES_OVERRIDE_PAGES', true); // uncomment to force override

// 1) Output root links for the CPT (so get_permalink() becomes /slug/)
add_filter('post_type_link', function($permalink, $post){
  if ($post->post_type === Yono_Games::CPT) {
    return home_url( user_trailingslashit($post->post_name) );
  }
  return $permalink;
}, 10, 2);

// 2) Add a very general rewrite tag + rule for "/{slug}/"
add_action('init', function () {
  add_rewrite_tag('%yono_game%', '([^&]+)');
  add_rewrite_rule('^([^/]+)/?$', 'index.php?yono_game=$matches[1]', 'top');
}, 9);

// 3) Convert that request to the proper single game IF it doesn't collide
add_filter('request', function ($vars) {
  if (empty($vars['yono_game'])) return $vars;

  $slug = sanitize_title($vars['yono_game']);

  // If a real Post/Page exists with that slug, keep WordPress default…
  if (!defined('YONO_GAMES_OVERRIDE_PAGES') || !YONO_GAMES_OVERRIDE_PAGES) {
    $page_or_post = get_page_by_path($slug, OBJECT, ['post','page']);
    if ($page_or_post) return $vars; // do NOT hijack
  }

  // If a Game exists with that slug, route to it
  $game = get_page_by_path($slug, OBJECT, Yono_Games::CPT);
  if ($game) {
    return [
      'post_type' => Yono_Games::CPT,
      'name'      => $slug,
    ];
  }

  return $vars; // no special handling
}, 9);

// 4) Make sure WordPress knows our custom query var
add_filter('query_vars', function ($vars) {
  $vars[] = 'yono_game';
  return $vars;
});

// Automatically flush rewrite rules on plugin activation/deactivation
register_activation_hook(__FILE__, 'flush_rewrite_rules');
register_deactivation_hook(__FILE__, function () { flush_rewrite_rules(); });
