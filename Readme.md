# Yono Promo Codes — WordPress Plugin

Modern, scheduler-aware promo codes with live countdowns, copy-to-clipboard, and a slick, dark UI—built for teams that ship fast.

> Manage promo codes as content (custom post type), schedule their availability with start/end times, group them by period (Morning/Afternoon/Evening), then render beautiful cards anywhere via a simple shortcode.

---

## Highlights

* **Custom Post Type**: `Promo Codes` with clean admin UI
* **Schedule-Aware**: Start & End datetime (site timezone)
* **Auto Status**: Upcoming / Active / Expired (auto-calculated)
* **Live Countdown**: “Starts in…” / “Ends in…” per code
* **Quick Copy**: One-click copy for promo codes
* **Period Grouping**: Taxonomy `promo_period` (morning / afternoon / evening)
* **Shortcode Renderer**: Responsive, modern cards grid (WN dark glass look)
* **Safe Output**: Escaped, nonce-protected, and lightweight
* **Widget-Friendly**: Works alongside your separate `promo-widget` button

---

## What’s Included

```
yono-promos/
├── yono-promos.php             # Main plugin
└── assets/
    ├── css/promos.css          # Card/grid styles
    └── js/promos.js            # Countdown + copy logic
```

---

## 🚀 Quick Start

1. **Install**

   * Zip the `yono-promos` folder or upload the folder to `/wp-content/plugins/`.
   * Activate **Yono Promo Codes** in **WP Admin → Plugins**.

2. **Create a Promo Code**

   * **Promo Codes → Add New**
   * Title: *Morning ₹50 Bonus*
   * **Promo Code**: `MORNING50`
   * **Label/Description**: *Morning Bonus ₹50*
   * **Start / End**: choose local date & time (uses site timezone)
   * **Promo Period**: select *Morning* (right sidebar)

3. **Display in a Page**

   * Add the shortcode:

     ```
     [yono_promos]
     ```

That’s it—active or upcoming codes appear with live timers and copy buttons.

---

## 🧵 Shortcode Reference

**Primary Shortcode**

```
[yono_promos]
```

**Attributes**

| Attribute      | Type   | Default                               | Description                                             |       |
| -------------- | ------ | ------------------------------------- | ------------------------------------------------------- | ----- |
| `period`       | string | *(all)*                               | Comma list of slugs: `morning,afternoon,evening`        |       |
| `status`       | string | `active,upcoming`                     | Which statuses to show: `active`, `upcoming`, `expired` |       |
| `show_expired` | bool   | `false`                               | Include expired items                                   |       |
| `limit`        | int    | `100`                                 | Max promos to fetch                                     |       |
| `layout`       | string | `cards`                               | `cards` or `list` (cards recommended)                   |       |
| `columns`      | int    | responsive (1–3)                      | Force columns: `1`, `2`, or `3`                         |       |
| `empty_text`   | string | `No promo codes available right now.` | Fallback message                                        |       |
| `show_copy`    | bool   | `true`                                | Show Copy button                                        |       |
| `show_timer`   | bool   | `true`                                | Show live timer                                         |       |
| `order`        | string | `ASC`                                 | Sort by title `ASC                                      | DESC` |

**Examples**

* Morning only, 3 columns:

  ```
  [yono_promos period="morning" columns="3"]
  ```
* Include expired too:

  ```
  [yono_promos show_expired="true"]
  ```
* Show only upcoming:

  ```
  [yono_promos status="upcoming"]
  ```
* All periods, two columns, descending by title:

  ```
  [yono_promos period="morning,afternoon,evening" columns="2" order="DESC"]
  ```

---

## 🖥️ Admin Fields

* **Promo Code** (required) — e.g., `SUNSET200`
* **Label/Description** — e.g., *Evening Delight ₹200*
* **Start** — when the promo becomes visible/active (optional)
* **End** — when the promo expires (optional)
* **Promo Period** — taxonomy for grouping (*Morning/Afternoon/Evening*)

**Timezones:**
Start/End are entered in **site timezone** and stored as **UTC**. Front-end timers use the visitor’s clock for smooth, live updates.

---

## 🎨 Front-End UX

* **Dark, glassmorphism-style** cards with accent badges
* **Period badge** (Morning / Afternoon / Evening)
* **Copy to clipboard** with feedback state
* **Live countdown** (“Starts in…” / “Ends in…”)
* **Responsive grid** (1–3 columns)

> The shortcode renderer is independent from your floating `promo-widget`. Use both together without conflicts.

---

## 🔒 Security & Performance

* **Nonce-protected** meta saving
* **Escaped** output everywhere it matters
* **Lean queries** with `no_found_rows` and CPT scoping
* **No external dependencies** beyond vanilla JS/CSS

---

## 🧪 Troubleshooting

**Codes don’t show**

* Check `status` filter: default hides `expired`.
* Verify **Start/End** windows. If Start is in the future → “Upcoming”. If End is past → “Expired”.
* Confirm **period** filter (if set) matches the promo’s taxonomy.

**Countdown seems off**

* Ensure the **site timezone** is correct (Settings → General → Timezone).
* Start/End store in UTC. Shortcode and JS use ISO timestamps consistently.

**Copy button not working**

* Some legacy browsers block clipboard APIs on non-HTTPS pages. The plugin includes a fallback using `execCommand('copy')`.

---

## 🧩 Theming Tips

* Override or extend styles in your theme after the plugin’s CSS:

  ```css
  /* Example: sharpen the badge and tweak colors */
  .yono-promo-card .promo-badge {
    filter: saturate(1.15);
  }
  .yono-promos .promo-code {
    color: #ffd56b;
  }
  ```
* Want tabs like your floating widget? Create three sections with headings and filter each shortcode by `period`.

---

## 🗺️ Roadmap

* `[yono_promos_tabs]` helper shortcode (3 tabs with AJAX switching)
* Admin list “Status” column + quick filters
* REST endpoint for headless or widget sync
* Expiry notices in admin & dashboard widget
* Optional cache layer for very large promo libraries

---

## 📦 Compatibility

* **WordPress**: 5.8+ (tested on latest)
* **PHP**: 7.4+
* **Theme/Builder**: Works with Classic, Gutenberg, and most page builders (Elementor, Divi, etc.)

---

## 🔧 Developer Notes

* CPT: `yono_promo`
* Taxonomy: `promo_period` (slugs: `morning`, `afternoon`, `evening`)
* Meta keys:

  * `_yono_code` (string)
  * `_yono_label` (string)
  * `_yono_start` (ISO UTC)
  * `_yono_end` (ISO UTC)

> If you plan to extend with hooks/filters, a good place is before query build and before HTML render in the shortcode method.

---

## 📚 Usage Snippets

**Gutenberg**: add a Shortcode block → paste `[yono_promos]`.

**PHP** (theme file):

```php
echo do_shortcode('[yono_promos period="morning,afternoon" columns="3"]');
```

---

## 🧑‍⚖️ License

GPL-2.0+ — use, modify, share with attribution.

---

## 🙌 Credits

Built with care by **YonoAgency** for the Yono ecosystem.
Design language inspired by the Yono Gaming Store’s dark UI and interactive components.

---

## 💬 Support

* Need tabs, REST sync to your floating promo widget, or custom layout?
* Want this bundled as a **single .zip** with composer/npm scripts?

Ping your maintainer or drop the specs—you’ll get a ready-to-install build.
