<?php
/**
 * Plugin Name: Yono Promo Codes
 * Description: Manage promo codes with schedule & countdown. Shortcode: [yono_promos].
 * Version: 1.0.0
 * Author: Kuya Mecky Pogi
 * Author URI: https://github.com/KuyaMecky
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) exit;

/* ---------------------------------------------------------
 * UTIL HELPERS (shared)
 * --------------------------------------------------------- */
if (!function_exists('yono_iso_to_local_value')) {
  function yono_iso_to_local_value($iso){
    if (!$iso) return '';
    $ts = strtotime($iso);
    if (!$ts) return '';
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
  }

  public function register_cpt_and_tax(){
    register_post_type(self::CPT, [
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
    }
  }

  public function register_meta_boxes(){
    add_meta_box('yono_promos_details','Promo Details',[$this,'render_meta_box'],self::CPT,'normal','default');
  }

  public function admin_assets($hook){
    global $post_type;
    if (($hook === 'post-new.php' || $hook === 'post.php') && $post_type === self::CPT) {
      // Use WP core date/time styles
      wp_enqueue_style('yono-promos-admin', plugin_dir_url(__FILE__) . 'assets/css/promos.css', [], '1.0.0');
      wp_enqueue_script('yono-promos-admin', plugin_dir_url(__FILE__) . 'assets/js/promos.js', ['jquery'], '1.0.0', true);
    }
  }

  public function render_meta_box($post) {
    wp_nonce_field(self::NONCE, self::NONCE);
    $code  = get_post_meta($post->ID,'_yono_code',true);
    $label = get_post_meta($post->ID,'_yono_label',true);
    $start = get_post_meta($post->ID,'_yono_start',true);
    $end   = get_post_meta($post->ID,'_yono_end',true);
    ?>
    <div class="yono-meta-logo" style="margin:-4px 0 12px;">
      <img src="https://allyonorefer.com/wp-content/uploads/2025/10/cropped-Untitled-design-9.png"
        alt="Yono Promo Codes" style="width:28px;height:28px;border-radius:4px;margin-right:8px;vertical-align:middle;">
      <strong style="vertical-align:middle;">Yono Promo Details</strong>
    </div>

    <div class="yono-meta-wrap">
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
      'period'=>'','status'=>'active,upcoming','show_expired'=>'false',
      'limit'=>'100','layout'=>'cards','columns'=>'1','empty_text'=>'No promo codes available right now.',
      'show_copy'=>'true','show_timer'=>'true','order'=>'ASC','format'=>'long',
    ], $atts,'yono_promos');

    $periods = array_filter(array_map('trim', explode(',', strtolower($atts['period']))));
    $show_expired = filter_var($atts['show_expired'], FILTER_VALIDATE_BOOLEAN);
    $want_status = array_filter(array_map('trim', explode(',', strtolower($atts['status']))));
    if (!$want_status) $want_status=['active','upcoming'];
    $limit = max(1, intval($atts['limit']));
    $order = strtoupper($atts['order'])==='DESC' ? 'DESC' : 'ASC';

    $tax_query = [];
    if (!empty($periods)){
      $tax_query[] = ['taxonomy'=>self::TAX,'field'=>'slug','terms'=>$periods];
    }

    $q = new WP_Query([
      'post_type'=>self::CPT,'posts_per_page'=>$limit,'tax_query'=>$tax_query,
      'orderby'=>'title','order'=>$order,'no_found_rows'=>true,
    ]);
    $now_ts = time();
    $items=[];
    while($q->have_posts()){ $q->the_post();
      $id=get_the_ID(); $code=get_post_meta($id,'_yono_code',true);
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
      <?php endforeach; ?>
    </div>
    <?php
  }
}

new Yono_Promos();
