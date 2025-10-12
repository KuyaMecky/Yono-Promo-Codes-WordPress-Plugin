# Game and Promo Plugin

A WordPress plugin that lets you:

* Manage **Promo Codes** with start/end schedules
* Show a **floating "Free Codes"** pill that opens a modern modal with tabs (Morning / Afternoon / Evening)
* Manage **Games** (active & upcoming) with clean "store-tile" cards, countdowns, search, category chips, and sorting
* **Import/Export** games via CSV
* Choose a **game logo** from the Media Library (or paste a direct URL)
* Embed everything anywhere with **shortcodes**

---

## Table of contents

* [Highlights](#highlights)
* [Requirements](#requirements)
* [Installation](#installation)
* [Quick start](#quick-start)
* [Shortcodes](#shortcodes)
* [Promo Codes: Admin & Display](#promo-codes-admin--display)
* [Floating Widget](#floating-widget)
* [Games: Admin & Display](#games-admin--display)
* [CSV Import / Export](#csv-import--export)
* [Styling & Customization](#styling--customization)
* [Accessibility](#accessibility)
* [FAQ](#faq)
* [Changelog](#changelog)
* [License](#license)
* [Credits](#credits)

---

## Highlights

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

## Requirements

* WordPress 5.8+ (tested on WP 6.x)
* PHP 7.4+ (PHP 8.x compatible)
* Theme with front-end `wp_footer()` and `wp_head()` hooks

---

## 🚀 Quick Start

1. **Install**

   * Zip the `yono-promos` folder or upload it to `/wp-content/plugins/`.
   * Activate **Yono Promo Codes** in **WP Admin → Plugins**.

2. **Create a Promo Code**

   * Go to **Promo Codes → Add New**.
   * Enter a **Promo Code**, optional label, and optional **Start/End** times.
   * Assign a **Promo Period** (Morning / Afternoon / Evening).
   * Publish.

2. **Add Games**

   * Go to **Games → Add New**.
   * Provide **Title**, **Subtitle**, **Logo** (select from Media or paste URL), **Bonus**, **Minimum Withdrawal**, **CTA**, optional **Launch** date/time.
   * Assign one or more **Categories** and **Badges**.
   * Publish.

3. **Embed on a Page**

   * Insert the shortcodes:

     ```
     [yono_promos]
     ```

That’s it—active or upcoming codes appear with live timers and copy buttons.

---

## Shortcodes

### `[yono_promos]`

Displays cards for your promo codes. Also powers the modal content.

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

## CSV Import / Export

Open **Games → Import / Export** from the admin menu.

### Export

* Click **Download CSV** to export all games.

### Import

* Upload a CSV with the following headers:

```
title,subtitle,category,badge,logo,bonus_range,min_withdraw,cta_text,cta_url,launch_at,status
```

**Notes**

* `category` and `badge` support **multiple values** separated by `|`.
  Example: `Slots|Bingo`
* `launch_at` expects **site local time**: `YYYY-MM-DD HH:MM`
* Existing games are matched by **title** and updated (idempotent).

---

## Styling & Customization

* Front-end CSS is in:

  * `assets/css/promos.css` (promos, modal, floating pill + games layout)
  * `assets/css/games.css` (if kept separate)
* Front-end JS is in:

  * `assets/js/promos.js` (promos, modal, games filters/sorting/countdown)
  * `assets/js/games.js` (if kept separate)
* Colors (accent/orange, blues) can be adjusted in CSS variables or gradient lines.

---

## Accessibility

* Modal supports **ESC to close**, backdrop click to dismiss
* **Focus trapping** while modal is open
* Visible **focus outlines** on interactive controls
* Countdown regions use `aria-live="polite"` for status updates

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

## License

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








