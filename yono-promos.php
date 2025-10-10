<?php
/**
 * Plugin Name: Yono Promo Codes & Games
 * Description: Promo codes with scheduling + floating widget & modal, and a Games grid with upcoming countdown, search, filter, sort, CSV import/export. Includes Media Library picker for game logos (with URL fallback).
 * Version: 2.1.2
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
    if (($hook==='post-new.php'||$hook==='post.php') && $post_type===self::CPT){
      wp_enqueue_style('yono-promos-admin', plugin_dir_url(__FILE__).'assets/css/promos.css',[], '2.1.2');
      wp_enqueue_script('yono-promos-admin', plugin_dir_url(__FILE__).'assets/js/promos.js',['jquery'],'2.1.2', true);
    }
  }

  public function enqueue_assets(){
    wp_enqueue_style('yono-promos', plugin_dir_url(__FILE__).'assets/css/promos.css',[], '2.1.2');
    wp_enqueue_script('yono-promos', plugin_dir_url(__FILE__).'assets/js/promos.js',[], '2.1.2', true);
  }

  public function render_meta_box($post){
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
    <?php return ob_get_clean();
  }

  private function has_any_active_or_upcoming_promos(){
    $now_ts = time();
    $q = new WP_Query(['post_type'=>self::CPT,'posts_per_page'=>1,'no_found_rows'=>true,'orderby'=>'date','order'=>'DESC']);
    $has=false;
    while($q->have_posts()){ $q->the_post();
      $st=get_post_meta(get_the_ID(),'_yono_start',true);
      $et=get_post_meta(get_the_ID(),'_yono_end',true);
      $sts=$st?strtotime($st):0; $ets=$et?strtotime($et):0;
      $status='active'; if ($sts && $now_ts<$sts) $status='upcoming'; if ($ets && $now_ts>$ets) $status='expired';
      if ($status!=='expired'){ $has=true; break; }
    } wp_reset_postdata();
    return $has;
  }

  /* ---------- Floating widget: server-render each tab ---------- */
  public function render_floating_widget(){
    if (is_admin()) return;

    // show only if there is at least one active or upcoming promo
    $q = new WP_Query([
      'post_type' => self::CPT,
      'posts_per_page' => 1,
      'no_found_rows' => true,
      'orderby' => 'date', 'order' => 'DESC'
    ]);
    $show = false; $now=time();
    while($q->have_posts()){ $q->the_post();
      $st = get_post_meta(get_the_ID(), '_yono_start', true);
      $et = get_post_meta(get_the_ID(), '_yono_end', true);
      $sts=$st?strtotime($st):0; $ets=$et?strtotime($et):0;
      $status='active'; if($sts && $now<$sts) $status='upcoming'; if($ets && $now>$ets) $status='expired';
      if($status!=='expired'){ $show=true; break; }
    }
    wp_reset_postdata();
    if(!$show) return;

    $settings_json = esc_attr( wp_json_encode([
      'now'       => gmdate('c'),
      'showCopy'  => true,
      'showTimer' => true,
      'format'    => 'long'
    ]) );

    ?>
    <style>
      .promo-popup[hidden]{display:none!important}
      .promo-popup.show{display:flex}
    </style>

    <!-- Floating pill -->
    <div class="promo-widget" aria-hidden="false">
      <button type="button" class="promo-trigger" aria-controls="promoPopup" aria-expanded="false">
        <span>Free Codes</span>
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M3 12l7 7L21 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </div>

    <!-- Popup -->
    <div id="promoPopup" class="promo-popup" role="dialog" aria-modal="true" aria-labelledby="promoTitle" hidden>
      <div class="promo-content" role="document">
        <button class="promo-close" type="button" aria-label="Close">&times;</button>

        <div class="promo-header">
          <h3 id="promoTitle">Yono Games</h3>
          <p>Free Promo Codes</p>
        </div>

        <div class="tab-navigation" role="tablist" aria-label="Promo periods">
          <button class="tab-button active" role="tab" aria-selected="true" data-period="morning">Morning</button>
          <button class="tab-button" role="tab" aria-selected="false" data-period="afternoon">Afternoon</button>
          <button class="tab-button" role="tab" aria-selected="false" data-period="evening">Evening</button>
        </div>

        <!-- Morning -->
        <div class="tab-content active" data-period="morning">
          <div class="yono-promos" data-settings="<?php echo $settings_json; ?>">
            <?php echo do_shortcode('[yono_promos period="morning" limit="200" order="ASC" show_timer="true" show_copy="true" format="long"]'); ?>
          </div>
        </div>
        <!-- Afternoon -->
        <div class="tab-content" data-period="afternoon">
          <div class="yono-promos" data-settings="<?php echo $settings_json; ?>">
            <?php echo do_shortcode('[yono_promos period="afternoon" limit="200" order="ASC" show_timer="true" show_copy="true" format="long"]'); ?>
          </div>
        </div>
        <!-- Evening -->
        <div class="tab-content" data-period="evening">
          <div class="yono-promos" data-settings="<?php echo $settings_json; ?>">
            <?php echo do_shortcode('[yono_promos period="evening" limit="200" order="ASC" show_timer="true" show_copy="true" format="long"]'); ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <div id="toast-notification" aria-live="polite" aria-atomic="true">Code copied</div>

    <!-- Open/close + tabs (robust, no re-bucketing) -->
    <script>
    (function(){
      if (window.__yonoPromoInit) return;
      window.__yonoPromoInit = true;

      document.addEventListener('DOMContentLoaded', function(){
        var popup   = document.getElementById('promoPopup');
        var trigger = document.querySelector('.promo-trigger');
        if (!popup || !trigger) return;

        var closeBtn = popup.querySelector('.promo-close');
        var tabs = Array.prototype.slice.call(popup.querySelectorAll('.tab-button'));
        var panes = Array.prototype.slice.call(popup.querySelectorAll('.tab-content'));

        function activate(period){
          tabs.forEach(function(t){
            var on = t.getAttribute('data-period')===period;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true':'false');
          });
          panes.forEach(function(p){
            var on = p.getAttribute('data-period')===period;
            p.classList.toggle('active', on);
          });
        }

        function openPopup(){
          popup.hidden = false;
          popup.classList.add('show');
          document.documentElement.style.overflow = 'hidden';
          trigger.setAttribute('aria-expanded','true');
          activate('morning');
        }
        function closePopup(){
          popup.classList.remove('show');
          popup.hidden = true;
          document.documentElement.style.overflow = '';
          trigger.setAttribute('aria-expanded','false');
        }

        trigger.addEventListener('click', openPopup);
        if (closeBtn) closeBtn.addEventListener('click', closePopup);
        popup.addEventListener('click', function(e){ if (e.target === popup) closePopup(); });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !popup.hidden) closePopup(); });

        // toast feedback for copy
        var toast = document.getElementById('toast-notification');
        popup.addEventListener('click', function(e){
          var btn = e.target.closest && e.target.closest('.promo-copy');
          if (!btn || !toast) return;
          toast.classList.add('show');
          setTimeout(function(){ toast.classList.remove('show'); }, 1200);
        });
      });
    })();
    </script>
    <?php
  }
}
new Yono_Promos();

/* =========================================================
 * GAMES + CSV + MEDIA PICKER
 * ========================================================= */
class Yono_Games {
  const CPT  = 'yono_game';
  const TAX_CAT   = 'game_cat';    // Rummy/Slots/Arcade/Bingo
  const TAX_BADGE = 'game_badge';  // New/Coming Soon/Hot/etc.
  const NONCE = 'yono_games_meta_nonce';

  public function __construct(){
    add_action('init',                   [$this,'register_cpt_tax']);
    add_action('add_meta_boxes',         [$this,'register_meta_boxes']);
    add_action('save_post',              [$this,'save_meta']);
    add_action('admin_menu',             [$this,'register_tools_page']);
    add_shortcode('yono_games',          [$this,'shortcode']);
    add_action('wp_enqueue_scripts',     [$this,'enqueue_assets']);
    add_action('admin_enqueue_scripts',  [$this,'admin_assets']);
    add_action('wp_ajax_yono_games_export',   [$this,'handle_export']);
    add_action('admin_post_yono_games_import',[$this,'handle_import']);
  }

  public function register_cpt_tax(){
    register_post_type(self::CPT, [
      'label'=>'Yono Games',
      'labels'=>['name'=>'Games','singular_name'=>'Game','add_new_item'=>'Add New Game','edit_item'=>'Edit Game'],
      'public'=>false,'show_ui'=>true,'show_in_menu'=>true,'menu_icon'=>'dashicons-games','supports'=>['title']
    ]);
    register_taxonomy(self::TAX_CAT, self::CPT, [
      'label'=>'Game Category','public'=>false,'show_ui'=>true,'hierarchical'=>true,'show_admin_column'=>true
    ]);
    register_taxonomy(self::TAX_BADGE, self::CPT, [
      'label'=>'Game Badge','public'=>false,'show_ui'=>true,'hierarchical'=>false,'show_admin_column'=>true
    ]);
    foreach (['Rummy','Slots','Arcade','Bingo'] as $cat){ if (!term_exists($cat,self::TAX_CAT)) wp_insert_term($cat,self::TAX_CAT); }
    foreach (['New','Coming Soon','Hot'] as $b){ if (!term_exists($b,self::TAX_BADGE)) wp_insert_term($b,self::TAX_BADGE,['slug'=>sanitize_title($b)]); }
  }

  public function enqueue_assets(){
    wp_enqueue_style('yono-games', plugin_dir_url(__FILE__).'assets/css/games.css',[], '2.1.2');
    wp_enqueue_script('yono-games', plugin_dir_url(__FILE__).'assets/js/games.js',[], '2.1.2', true);
  }

  public function admin_assets($hook){
    if (strpos($hook,'yono_games_tools') !== false){
      wp_enqueue_style('yono-games', plugin_dir_url(__FILE__).'assets/css/games.css',[], '2.1.2');
      wp_enqueue_script('yono-games-admin', plugin_dir_url(__FILE__).'assets/js/games-admin.js',[], '2.1.2', true);
    }
    global $post_type;
    if (($hook==='post-new.php' || $hook==='post.php') && $post_type===self::CPT){
      wp_enqueue_media();
      wp_enqueue_script('yono-media-picker', plugin_dir_url(__FILE__).'assets/js/yono-media-picker.js',['jquery'],'2.1.2', true);
    }
  }

  public function register_meta_boxes(){
    add_meta_box('yono_game_details','Game Details',[$this,'render_meta_box'], self::CPT,'normal','default');
  }

  public function render_meta_box($post){
    wp_nonce_field(self::NONCE, self::NONCE);
    $logo = get_post_meta($post->ID,'_yg_logo',true);
    $subtitle = get_post_meta($post->ID,'_yg_subtitle',true);
    $bonus = get_post_meta($post->ID,'_yg_bonus',true);
    $minwd = get_post_meta($post->ID,'_yg_min_withdraw',true);
    $cta_text = get_post_meta($post->ID,'_yg_cta_text',true) ?: 'Get Started';
    $cta_url  = get_post_meta($post->ID,'_yg_cta_url',true);
    $launch   = get_post_meta($post->ID,'_yg_launch_at',true);
    $status   = get_post_meta($post->ID,'_yg_status',true) ?: 'active';
    ?>
    <style>.yono-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}</style>

    <p>
      <label><strong>Logo</strong></label><br>
      <div class="yg-media-row" style="display:flex;gap:10px;align-items:flex-start;">
        <img src="<?php echo $logo ? esc_url($logo) : ''; ?>"
             class="yg-media-preview"
             style="width:56px;height:56px;border-radius:8px;object-fit:cover;background:#0e1726;border:1px solid rgba(148,163,184,.2);<?php echo $logo ? '' : 'display:none;'; ?>"
             alt="">
        <div style="flex:1 1 auto;">
          <input type="url" id="yg_logo" name="yg_logo" class="widefat" placeholder="https://…" value="<?php echo esc_attr($logo); ?>">
          <div style="margin-top:8px;display:flex;gap:8px;">
            <button class="button yg-media-select" type="button" data-target="#yg_logo" data-preview=".yg-media-preview">Select / Upload</button>
            <button class="button yg-media-remove" type="button" data-target="#yg_logo" data-preview=".yg-media-preview" <?php echo $logo ? '' : 'style="display:none"'; ?>>Remove</button>
          </div>
          <small class="description">Paste any image URL or click “Select / Upload” to choose from Media Library.</small>
        </div>
      </div>
    </p>

    <div class="yono-grid-2">
      <p><label><strong>Subtitle</strong></label><br>
        <input type="text" name="yg_subtitle" class="widefat" placeholder="Slots Game" value="<?php echo esc_attr($subtitle); ?>"></p>
      <p><label><strong>Welcome bonus (range)</strong></label><br>
        <input type="text" name="yg_bonus" class="widefat" placeholder="₹75–₹150" value="<?php echo esc_attr($bonus); ?>"></p>
      <p><label><strong>Minimum Withdrawal</strong></label><br>
        <input type="text" name="yg_min_withdraw" class="widefat" placeholder="₹100" value="<?php echo esc_attr($minwd); ?>"></p>
      <p><label><strong>CTA Text</strong></label><br>
        <input type="text" name="yg_cta_text" class="widefat" value="<?php echo esc_attr($cta_text); ?>"></p>
      <p><label><strong>CTA URL</strong></label><br>
        <input type="url" name="yg_cta_url" class="widefat" placeholder="https://example.com/download" value="<?php echo esc_attr($cta_url); ?>"></p>
      <p><label><strong>Launch (local time)</strong></label><br>
        <input type="datetime-local" name="yg_launch_at" value="<?php echo esc_attr(yono_iso_to_local_value($launch)); ?>">
        <em class="description">If set in the future, card shows “Launches in …” countdown.</em>
      </p>
      <p><label><strong>Status</strong></label><br>
        <select name="yg_status" class="widefat">
          <?php foreach (['active'=>'Active','upcoming'=>'Upcoming','retired'=>'Retired'] as $k=>$v): ?>
            <option value="<?php echo esc_attr($k); ?>" <?php selected($status,$k); ?>><?php echo esc_html($v); ?></option>
          <?php endforeach; ?>
        </select>
      </p>
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
      '_yg_launch_at'    => isset($_POST['yg_launch_at']) ? yono_local_to_iso_utc(sanitize_text_field($_POST['yg_launch_at'])) : '',
      '_yg_status'       => isset($_POST['yg_status']) ? sanitize_text_field($_POST['yg_status']) : 'active',
    ];
    foreach($fields as $k=>$v){ update_post_meta($post_id,$k,$v); }
  }

  public function shortcode($atts){
    $atts = shortcode_atts([
      'cat'=>'','badge'=>'','per_page'=>'60','columns'=>'3','sort'=>'name',
      'show_count'=>'true','show_search'=>'true','show_filters'=>'true',
    ], $atts, 'yono_games');

    $tax_query=[];
    if (!empty($atts['cat'])){
      $cats = array_map('trim', explode(',', $atts['cat']));
      $tax_query[] = ['taxonomy'=>self::TAX_CAT,'field'=>'name','terms'=>$cats];
    }
    if (!empty($atts['badge'])){
      $b = array_map('trim', explode(',', $atts['badge']));
      $tax_query[] = ['taxonomy'=>self::TAX_BADGE,'field'=>'name','terms'=>$b];
    }

    $orderby='title'; $order='ASC'; $meta_key='';
    switch (strtolower($atts['sort'])){
      case 'latest': $orderby='date'; $order='DESC'; break;
      case 'launch': $orderby='meta_value'; $order='ASC'; $meta_key='_yg_launch_at'; break;
      default: $orderby='title'; $order='ASC';
    }

    $q = new WP_Query([
      'post_type'=>self::CPT,'posts_per_page'=>intval($atts['per_page']),
      'tax_query'=>$tax_query,'orderby'=>$orderby,'order'=>$order,
      'no_found_rows'=>true,'meta_key'=>$meta_key
    ]);

    $items=[];
    while($q->have_posts()){ $q->the_post();
      $id=get_the_ID();
      $logo = get_post_meta($id,'_yg_logo',true);
      $subtitle = get_post_meta($id,'_yg_subtitle',true);
      $bonus = get_post_meta($id,'_yg_bonus',true);
      $minwd = get_post_meta($id,'_yg_min_withdraw',true);
      $cta_text = get_post_meta($id,'_yg_cta_text',true) ?: 'Get Started';
      $cta_url  = get_post_meta($id,'_yg_cta_url',true);
      $launch   = get_post_meta($id,'_yg_launch_at',true);
      $status   = get_post_meta($id,'_yg_status',true) ?: 'active';
      if ($launch && time() < strtotime($launch)) $status='upcoming';

      $cat  = wp_get_post_terms($id, self::TAX_CAT,   ['fields'=>'names']);
      $badg = wp_get_post_terms($id, self::TAX_BADGE, ['fields'=>'slugs']);

      $items[] = [
        'id'=>$id,'title'=>get_the_title(),'logo'=>$logo,'subtitle'=>$subtitle,
        'bonus'=>$bonus,'minwd'=>$minwd,'cta_text'=>$cta_text,'cta_url'=>$cta_url,
        'launch'=>$launch,'status'=>$status,'cat'=>$cat,'badge'=>$badg
      ];
    } wp_reset_postdata();

    $count = count($items);
    ob_start(); ?>
    <section class="yono-games" data-columns="<?php echo intval($atts['columns']); ?>">
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
            <?php foreach (get_terms(['taxonomy'=>self::TAX_CAT,'hide_empty'=>false]) as $t): ?>
              <button class="yg-chip" data-cat="<?php echo esc_attr($t->name); ?>"><?php echo esc_html($t->name); ?></button>
            <?php endforeach; ?>
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
          $is_up  = $g['status']==='upcoming'; ?>
          <article class="yg-card <?php echo $is_up ? 'is-upcoming':''; ?>"
                   data-name="<?php echo esc_attr(mb_strtolower($g['title'])); ?>"
                   data-cats="<?php echo esc_attr($cats); ?>"
                   data-badges="<?php echo esc_attr($badges); ?>"
                   data-launch="<?php echo esc_attr($g['launch']); ?>">
            <div class="yg-card-head">
              <?php if (!empty($g['badge'])) foreach($g['badge'] as $b): ?>
                <span class="yg-badge"><?php echo esc_html(ucwords(str_replace('-',' ',$b))); ?></span>
              <?php endforeach; if (!empty($g['cat'])) foreach($g['cat'] as $c): ?>
                <span class="yg-pill"><?php echo esc_html($c); ?></span>
              <?php endforeach; ?>
            </div>

            <div class="yg-card-body">
              <?php if ($g['logo']): ?><img src="<?php echo esc_url($g['logo']); ?>" class="yg-logo" alt=""><?php endif; ?>
              <div>
                <h3 class="yg-title"><?php echo esc_html($g['title']); ?></h3>
                <?php if ($g['subtitle']): ?><div class="yg-sub"><?php echo esc_html($g['subtitle']); ?></div><?php endif; ?>
                <div class="yg-spec">
                  <div>🎁 <strong>Welcome bonus:</strong> <?php echo esc_html($g['bonus']); ?></div>
                  <div>💳 <strong>Minimum Withdrawal:</strong> <?php echo esc_html($g['minwd']); ?></div>
                </div>
                <?php if ($is_up): ?>
                  <div class="yg-coming">
                    <span class="yg-coming-label">Launches in</span>
                    <span class="yg-countdown" aria-live="polite">—</span>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="yg-card-foot">
              <?php if ($g['cta_url']): ?>
                <a class="yg-cta" href="<?php echo esc_url($g['cta_url']); ?>" target="_blank" rel="nofollow noopener"><?php echo esc_html($g['cta_text']); ?></a>
              <?php else: ?>
                <button class="yg-cta" disabled><?php echo esc_html($g['cta_text']); ?></button>
              <?php endif; ?>
              <button class="yg-save" type="button" title="Save card">📥</button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
    return ob_get_clean();
  }

  /* -------- Tools: Import / Export CSV -------- */
  public function register_tools_page(){
    add_submenu_page('edit.php?post_type='.self::CPT,'Import / Export','Import / Export','manage_options','yono_games_tools',[$this,'render_tools_page']);
  }

  public function render_tools_page(){ ?>
    <div class="wrap">
      <h1>Yono Games — Import / Export</h1>
      <p>CSV headers: <code>title,subtitle,category,badge,logo,bonus_range,min_withdraw,cta_text,cta_url,launch_at,status</code></p>
      <ol>
        <li><strong>category</strong>: Rummy, Slots, Arcade, Bingo (multiple via <code>|</code>)</li>
        <li><strong>badge</strong>: New, Coming Soon, Hot (multiple via <code>|</code>)</li>
        <li><strong>launch_at</strong>: YYYY-MM-DD HH:MM (site local time)</li>
        <li><strong>status</strong>: active | upcoming | retired (auto-upcoming if launch is in the future)</li>
      </ol>

      <h2>Export</h2>
      <p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin-ajax.php?action=yono_games_export&_wpnonce='.wp_create_nonce('yono_games_export'))); ?>">Download CSV</a></p>
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

    $q = new WP_Query(['post_type'=>self::CPT,'posts_per_page'=>-1,'no_found_rows'=>true]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=yono-games-export.csv');
    $out = fopen('php://output','w');
    fputcsv($out, ['title','subtitle','category','badge','logo','bonus_range','min_withdraw','cta_text','cta_url','launch_at','status']);
    while($q->have_posts()){ $q->the_post();
      $id=get_the_ID();
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
        get_post_meta($id,'_yg_status',true)
      ]);
    }
    wp_reset_postdata();
    fclose($out); wp_die();
  }

  public function handle_import(){
    if (!current_user_can('manage_options')) wp_die('Not allowed');
    if (!isset($_POST['_yg_imp']) || !wp_verify_nonce($_POST['_yg_imp'],'yono_games_import')) wp_die('Bad nonce');
    if (empty($_FILES['csv']['tmp_name'])) wp_die('No file');

    $fh = fopen($_FILES['csv']['tmp_name'],'r');
    $header = fgetcsv($fh); $map = array_flip($header);
    while(($row=fgetcsv($fh))!==false){
      $title = sanitize_text_field($row[$map['title']] ?? '');
      if (!$title) continue;
      $existing = get_page_by_title($title, OBJECT, self::CPT);
      $post_id = $existing ? $existing->ID : wp_insert_post(['post_type'=>self::CPT,'post_title'=>$title,'post_status'=>'publish']);

      update_post_meta($post_id,'_yg_subtitle',     sanitize_text_field($row[$map['subtitle']] ?? ''));
      update_post_meta($post_id,'_yg_logo',         esc_url_raw($row[$map['logo']] ?? ''));
      update_post_meta($post_id,'_yg_bonus',        sanitize_text_field($row[$map['bonus_range']] ?? ''));
      update_post_meta($post_id,'_yg_min_withdraw', sanitize_text_field($row[$map['min_withdraw']] ?? ''));
      update_post_meta($post_id,'_yg_cta_text',     sanitize_text_field($row[$map['cta_text']] ?? 'Get Started'));
      update_post_meta($post_id,'_yg_cta_url',      esc_url_raw($row[$map['cta_url']] ?? ''));
      $launch_local = sanitize_text_field($row[$map['launch_at']] ?? '');
      update_post_meta($post_id,'_yg_launch_at',    yono_local_to_iso_utc($launch_local));
      update_post_meta($post_id,'_yg_status',       sanitize_text_field($row[$map['status']] ?? 'active'));

      $cats = array_filter(array_map('trim', explode('|', $row[$map['category']] ?? '')));
      $badg = array_filter(array_map('trim', explode('|', $row[$map['badge']] ?? '')));
      if ($cats) wp_set_object_terms($post_id, $cats, self::TAX_CAT, false);
      if ($badg) wp_set_object_terms($post_id, $badg, self::TAX_BADGE, false);
    }
    fclose($fh);
    wp_safe_redirect( admin_url('edit.php?post_type='.self::CPT.'&import=1') );
    exit;
  }
}
new Yono_Games();
