<?php
/**
 * Plugin Name: Yono Promo Codes
 * Description: Manage promo codes with schedule & countdown. Shortcode: [yono_promos].
 * Version: 1.0.0
 * Author: Kuya Mecky Pogi
 * Author URL:https://github.com/KuyaMecky
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) exit;

class Yono_Promos {
 
  const CPT = 'yono_promo';
  const TAX = 'promo_period'; // morning / afternoon / evening
  const NONCE = 'yono_promos_meta_nonce';

  public function __construct() {
    add_action('init', [$this, 'register_cpt_and_tax']);
    add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
    add_action('save_post', [$this, 'save_meta']);
    add_shortcode('yono_promos', [$this, 'shortcode']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
  }

  public function register_cpt_and_tax() {
    // CPT
register_post_type(self::CPT, [
  'label' => 'Yono Promo Codes',
  'labels' => [
    'name' => 'Promo Codes',
    'singular_name' => 'Promo Code',
    'add_new_item' => 'Add New Promo Code',
    'edit_item' => 'Edit Promo Code',
  ],
  'public' => false,
  'show_ui' => true,
  'show_in_menu' => true,
  // ↓ Use your logo here
  'menu_icon' => 'https://allyonorefer.com/wp-content/uploads/2025/10/cropped-Untitled-design-9.png',
  'supports' => ['title'],
]);


    // Taxonomy: promo_period (morning/afternoon/evening)
    register_taxonomy(self::TAX, self::CPT, [
      'label' => 'Promo Period',
      'public' => false,
      'show_ui' => true,
      'hierarchical' => false,
      'show_admin_column' => true,
    ]);

    // Ensure default terms exist
    $defaults = ['morning', 'afternoon', 'evening'];
    foreach ($defaults as $term) {
      if (!term_exists($term, self::TAX)) {
        wp_insert_term(ucfirst($term), self::TAX, ['slug' => $term]);
      }
    }
  }

  public function register_meta_boxes() {
    add_meta_box('yono_promos_details', 'Promo Details', [$this, 'render_meta_box'], self::CPT, 'normal', 'default');
  }

  public function admin_assets($hook) {
    global $post_type;
    if (($hook === 'post-new.php' || $hook === 'post.php') && $post_type === self::CPT) {
      // Use WP core date/time styles
      wp_enqueue_style('yono-promos-admin', plugin_dir_url(__FILE__) . 'assets/css/promos.css', [], '1.0.0');
      wp_enqueue_script('yono-promos-admin', plugin_dir_url(__FILE__) . 'assets/js/promos.js', ['jquery'], '1.0.0', true);
    }
  }

  public function render_meta_box($post) {
    wp_nonce_field(self::NONCE, self::NONCE);
    $code   = get_post_meta($post->ID, '_yono_code', true);
    $label  = get_post_meta($post->ID, '_yono_label', true);
    $start  = get_post_meta($post->ID, '_yono_start', true); // ISO8601 string
    $end    = get_post_meta($post->ID, '_yono_end', true);   // ISO8601 string
    ?>
    <div class="yono-meta-logo" style="margin:-4px 0 12px;">
      <img src="https://allyonorefer.com/wp-content/uploads/2025/10/cropped-Untitled-design-9.png"
          alt="Yono Promo Codes" style="width:28px;height:28px;vertical-align:middle;border-radius:4px;margin-right:8px;">
      <strong style="vertical-align:middle;">Yono Promo Details</strong>
    </div>
    <div class="yono-meta-wrap">
      <p>
        <label><strong>Promo Code</strong></label><br>
        <input type="text" name="yono_code" value="<?php echo esc_attr($code); ?>" class="widefat" placeholder="e.g., MORNING50" required>
      </p>
      <p>
        <label><strong>Label / Description</strong> (optional)</label><br>
        <input type="text" name="yono_label" value="<?php echo esc_attr($label); ?>" class="widefat" placeholder="e.g., Morning Bonus ₹50">
      </p>
      <div class="yono-grid">
        <p>
          <label><strong>Start (local time)</strong></label><br>
          <input type="datetime-local" name="yono_start" value="<?php echo esc_attr($this->to_local_datetime_value($start)); ?>">
        </p>
        <p>
          <label><strong>End (local time)</strong></label><br>
          <input type="datetime-local" name="yono_end" value="<?php echo esc_attr($this->to_local_datetime_value($end)); ?>">
        </p>
      </div>
      <p class="description">If you leave <em>Start</em> empty, it’s available immediately. If you leave <em>End</em> empty, it never expires.</p>
      <hr>
      <p><strong>Assign Period (tab):</strong> Use the “Promo Period” box on the right (Morning / Afternoon / Evening) to group and style like your widget tabs.</p>
    </div>
    <?php
  }

  private function to_local_datetime_value($iso) {
    if (!$iso) return '';
    // convert stored UTC/ISO to site local datetime-local value (YYYY-MM-DDTHH:MM)
    $ts = strtotime($iso);
    if (!$ts) return '';
    $local = get_date_from_gmt(gmdate('Y-m-d H:i:s', $ts), 'Y-m-d H:i:s');
    return date('Y-m-d\TH:i', strtotime($local));
  }

  public function save_meta($post_id) {
    if (!isset($_POST[self::NONCE]) || !wp_verify_nonce($_POST[self::NONCE], self::NONCE)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $code  = isset($_POST['yono_code']) ? sanitize_text_field($_POST['yono_code']) : '';
    $label = isset($_POST['yono_label']) ? sanitize_text_field($_POST['yono_label']) : '';
    $start = isset($_POST['yono_start']) ? sanitize_text_field($_POST['yono_start']) : '';
    $end   = isset($_POST['yono_end']) ? sanitize_text_field($_POST['yono_end']) : '';

    // store as ISO UTC strings for consistency
    $start_iso = $this->maybe_local_to_utc_iso($start);
    $end_iso   = $this->maybe_local_to_utc_iso($end);

    update_post_meta($post_id, '_yono_code', $code);
    update_post_meta($post_id, '_yono_label', $label);
    update_post_meta($post_id, '_yono_start', $start_iso);
    update_post_meta($post_id, '_yono_end', $end_iso);
  }

  private function maybe_local_to_utc_iso($local_dt) {
    if (!$local_dt) return '';
    // $local_dt from datetime-local input (no timezone). Interpret in site TZ.
    $site_tz = wp_timezone(); // WP 5.3+
    try {
      $dt = new DateTime($local_dt, $site_tz);
      $dt->setTimezone(new DateTimeZone('UTC'));
      return $dt->format('c'); // ISO8601
    } catch (Exception $e) {
      return '';
    }
  }

  public function enqueue_assets() {
    // Only enqueue when shortcode is present on page
    if (!is_singular() && !is_archive()) return;
    // lightweight approach: always ok to enqueue; or add a detection filter.
    wp_enqueue_style('yono-promos', plugin_dir_url(__FILE__) . 'assets/css/promos.css', [], '1.0.0');
    wp_enqueue_script('yono-promos', plugin_dir_url(__FILE__) . 'assets/js/promos.js', [], '1.0.0', true);
  }

  public function shortcode($atts) {
    $atts = shortcode_atts([
      'period'       => '',
      'status'       => 'active,upcoming',
      'show_expired' => 'false',
      'limit'        => '100',
      'layout'       => 'cards',
      'columns'      => '1',
      'empty_text'   => 'No promo codes available right now.',
      'show_copy'    => 'true',
      'show_timer'   => 'true',
      'order'        => 'ASC',
      // NEW: countdown format (long = “1d 2h 3m 4s”, compact = “01:02:03”)
      'format'       => 'long',
    ], $atts, 'yono_promos');


    $periods = array_filter(array_map('trim', explode(',', strtolower($atts['period']))));
    $show_expired = filter_var($atts['show_expired'], FILTER_VALIDATE_BOOLEAN);
    $want_status = array_filter(array_map('trim', explode(',', strtolower($atts['status']))));
    if (empty($want_status)) $want_status = ['active','upcoming'];
    $limit = max(1, intval($atts['limit']));
    $order = strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC';

    // Build taxonomy query if period filter set
    $tax_query = [];
    if (!empty($periods)) {
      $tax_query[] = [
        'taxonomy' => self::TAX,
        'field'    => 'slug',
        'terms'    => $periods
      ];
    }

    $q = new WP_Query([
      'post_type'      => self::CPT,
      'posts_per_page' => $limit,
      'tax_query'      => $tax_query,
      'orderby'        => 'title',
      'order'          => $order,
      'no_found_rows'  => true,
    ]);

    $now_ts = time(); // server time; we will also calculate with WP local time for display
    $site_tz = wp_timezone();
    $items = [];

    while ($q->have_posts()) {
      $q->the_post();
      $id = get_the_ID();
      $code  = get_post_meta($id, '_yono_code', true);
      if (!$code) continue;

      $label = get_post_meta($id, '_yono_label', true);
      $start_iso = get_post_meta($id, '_yono_start', true);
      $end_iso   = get_post_meta($id, '_yono_end', true);

      $start_ts = $start_iso ? strtotime($start_iso) : 0; // UTC
      $end_ts   = $end_iso   ? strtotime($end_iso)   : 0;

      $status = 'active';
      if ($start_ts && $now_ts < $start_ts) $status = 'upcoming';
      if ($end_ts && $now_ts > $end_ts)     $status = 'expired';

      if ($status === 'expired' && !$show_expired) continue;
      if (!in_array($status, $want_status, true) && !($status==='expired' && $show_expired)) continue;

      $period_terms = wp_get_post_terms($id, self::TAX, ['fields' => 'slugs']);
      $period = $period_terms ? $period_terms[0] : '';

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

    // Output
    if (empty($items)) {
      return '<div class="yono-promos-empty">'.esc_html($atts['empty_text']).'</div>';
    }

    // Data to hydrate timers on the front-end
    $json = wp_json_encode([
      'now'        => gmdate('c'),
      'showCopy'   => filter_var($atts['show_copy'], FILTER_VALIDATE_BOOLEAN),
      'showTimer'  => filter_var($atts['show_timer'], FILTER_VALIDATE_BOOLEAN),
      'format'     => in_array(strtolower($atts['format']), ['long','compact'], true) ? strtolower($atts['format']) : 'long',
    ]);

    ob_start(); ?>
    <div class="yono-promos" data-settings='<?php echo esc_attr($json); ?>' data-layout="<?php echo esc_attr($atts['layout']); ?>" data-columns="<?php echo esc_attr($atts['columns']); ?>">
      <?php foreach ($items as $it): 
        $period_badge = $it['period'] ? ucfirst($it['period']) : '';
        $status = $it['status'];
        $start_attr = $it['start'] ? esc_attr($it['start']) : '';
        $end_attr   = $it['end']   ? esc_attr($it['end'])   : '';
      ?>
      <article class="yono-promo-card status-<?php echo esc_attr($status); ?>" 
               data-start="<?php echo $start_attr; ?>"
               data-end="<?php echo $end_attr; ?>">
        <?php if ($period_badge): ?>
          <span class="promo-badge"><?php echo esc_html($period_badge); ?></span>
        <?php endif; ?>
        <h3 class="promo-title"><?php echo esc_html($it['title']); ?></h3>
        <div class="promo-code-wrap">
          <code class="promo-code"><?php echo esc_html($it['code']); ?></code>
          <button class="promo-copy" type="button" aria-label="Copy promo code" title="Copy promo code">Copy</button>
        </div>
        <?php if ($it['label']): ?>
          <div class="promo-desc"><?php echo esc_html($it['label']); ?></div>
        <?php endif; ?>

        <div class="promo-timer" aria-live="polite" aria-atomic="true">
          <!-- Filled by JS: “Starts in …” or “Ends in …” or “Expired” -->
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
  }
}

new Yono_Promos();
