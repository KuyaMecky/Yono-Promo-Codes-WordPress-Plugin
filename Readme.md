Got it! Here’s your **README** updated to display the logo right at the top (centered), using your image URL.

You can paste this whole thing into your `README.md` (GitHub/GitLab) or keep just the header block if you only want the logo addition.

---

<p align="center">
  <img src="https://allyonorefer.com/wp-content/uploads/2025/10/cropped-Untitled-design-9.png" alt="Yono Promo Codes Logo" width="140" height="140">
</p>

<h1 align="center">Yono Promo Codes — WordPress Plugin</h1>

<p align="center">
  Modern, scheduler-aware promo codes with live countdowns, copy-to-clipboard, and a slick, dark UI—built for teams that ship fast.
</p>

> Manage promo codes as content (custom post type), schedule their availability with start/end times, group them by period (Morning/Afternoon/Evening), then render beautiful cards anywhere via a simple shortcode.

---

## ✨ Highlights

* **Custom Post Type**: `Promo Codes` with clean admin UI
* **Schedule-Aware**: Start & End datetime (site timezone)
* **Auto Status**: Upcoming / Active / Expired (auto-calculated)
* **Live Countdown**: “Starts in…” / “Ends in…” per code
* **Quick Copy**: One-click copy for promo codes
* **Period Grouping**: Taxonomy `promo_period` (morning / afternoon / evening)
* **Shortcode Renderer**: Responsive, modern cards grid (dark glass look)
* **Safe Output**: Escaped, nonce-protected, and lightweight
* **Widget-Friendly**: Works alongside your separate `promo-widget` button

---

## 🧩 What’s Included

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

   * Zip the `yono-promos` folder or upload it to `/wp-content/plugins/`.
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

| Attribute      | Default                               | Notes                                     |       |
| -------------- | ------------------------------------- | ----------------------------------------- | ----- |
| `period`       | *(all)*                               | Comma list: `morning,afternoon,evening`   |       |
| `status`       | `active,upcoming`                     | Pick from `active`, `upcoming`, `expired` |       |
| `show_expired` | `false`                               | Include expired items                     |       |
| `limit`        | `100`                                 | Max promos to fetch                       |       |
| `layout`       | `cards`                               | `cards` or `list`                         |       |
| `columns`      | responsive (1–3)                      | Force `1`, `2`, or `3`                    |       |
| `empty_text`   | `No promo codes available right now.` | Fallback message                          |       |
| `show_copy`    | `true`                                | Show Copy button                          |       |
| `show_timer`   | `true`                                | Show live timer                           |       |
| `order`        | `ASC`                                 | Title sort `ASC                           | DESC` |

**Examples**

* Default (long format, timers ON):

  ```
  [yono_promos]
  ```

* Compact countdown (HH:MM:SS):

  ```
  [yono_promos format="compact"]
  ```

* Only upcoming promos with countdown:

  ```
  [yono_promos status="upcoming" format="long"]
  ```

* Morning tab only, 3 columns, compact timers:

  ```
  [yono_promos period="morning" columns="3" format="compact"]
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
* Verify **Start/End** windows (Upcoming vs Expired).
* Confirm **period** filter (if set) matches the promo’s taxonomy.

**Countdown seems off**

* Ensure the **site timezone** is correct (Settings → General → Timezone).
* Start/End are saved in UTC; the UI uses ISO consistently.

**Copy button not working**

* Some legacy browsers block clipboard APIs on non-HTTPS pages. Fallback is included.

---

## 🧩 Theming Tips

Override styles in your theme after the plugin CSS:

```css
.yono-promo-card .promo-badge { filter: saturate(1.15); }
.yono-promos .promo-code { color: #ffd56b; }
```

Want tabs like your floating widget? Create three sections and filter each shortcode by `period`.

---

## 🗺️ Roadmap

* `[yono_promos_tabs]` helper shortcode (3 tabs with AJAX switching)
* Admin list “Status” column + quick filters
* REST endpoint for widget sync
* Expiry notices in admin & dashboard widget
* Optional cache for large libraries

---

## 📦 Compatibility

* **WordPress**: 5.8+
* **PHP**: 7.4+
* Works with Classic, Gutenberg, and most page builders.

---

## 🔧 Developer Notes

* CPT: `yono_promo`
* Taxonomy: `promo_period` (`morning`, `afternoon`, `evening`)
* Meta:

  * `_yono_code` (string)
  * `_yono_label` (string)
  * `_yono_start` (ISO UTC)
  * `_yono_end` (ISO UTC)

---

## 🧑‍⚖️ License

GPL-2.0+

---

## 🙌 Credits

Built with care by **YonoAgency**.
Design language inspired by the Yono Gaming Store’s dark UI.

---

### (Optional) WordPress.org “assets” tips

If you later publish on wp.org, you can also add icons/banners by placing files in an `/assets/` folder in the SVN repo, e.g.:

* `assets/icon-128x128.png`
* `assets/banner-1544x500.jpg`








