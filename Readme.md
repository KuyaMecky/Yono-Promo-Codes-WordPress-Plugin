<<<<<<< HEAD
# Yono Promo Codes & Games
=======
# Game and Promo Plugin
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e

A WordPress plugin that lets you:

* Manage **Promo Codes** with start/end schedules
<<<<<<< HEAD
* Show a **floating “Free Codes”** pill that opens a modern modal with tabs (Morning / Afternoon / Evening)
* Manage **Games** (active & upcoming) with clean “store-tile” cards, countdowns, search, category chips, and sorting
=======
* Show a **floating "Free Codes"** pill that opens a modern modal with tabs (Morning / Afternoon / Evening)
* Manage **Games** (active & upcoming) with clean "store-tile" cards, countdowns, search, category chips, and sorting
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
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

* **Custom Post Types**

  * `yono_promo` with taxonomy `promo_period` (Morning, Afternoon, Evening)
  * `yono_game` with taxonomies `game_cat` (Rummy/Slots/Arcade/Bingo) and `game_badge` (New/Hot/Coming Soon)

* **Scheduling & Countdowns**

  * Promos: “Starts in… / Ends in… / Expired”
  * Games: “Launches in…” with automatic switch to Live

* **Beautiful UI**

  * Floating gradient pill → modal with tabs
  * Store-tile game cards with badges, logo, details, CTA

* **Content Ops**

  * Media Library picker for game logos (keeps URL field)
  * CSV Import/Export (safe, idempotent by title)

* **Developer-friendly**

  * Clean HTML, scoped CSS, lightweight JS
  * Defaults sensible; extensible via CSS

---

## Requirements

* WordPress 5.8+ (tested on WP 6.x)
* PHP 7.4+ (PHP 8.x compatible)
* Theme with front-end `wp_footer()` and `wp_head()` hooks

---

## Installation

1. Copy the plugin folder into `/wp-content/plugins/`.
2. Activate **Yono Promo Codes & Games** from **Plugins → Installed Plugins**.
3. Optional: Flush permalinks (visit **Settings → Permalinks**, click **Save**).

---

## Quick start

<<<<<<< HEAD
1. **Add Promo Codes**

   * Go to **Promo Codes → Add New**.
   * Enter a **Promo Code**, optional label, and optional **Start/End** times.
   * Assign a **Promo Period** (Morning / Afternoon / Evening).
   * Publish.

2. **Add Games**
=======
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
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e

   * Go to **Games → Add New**.
   * Provide **Title**, **Subtitle**, **Logo** (select from Media or paste URL), **Bonus**, **Minimum Withdrawal**, **CTA**, optional **Launch** date/time.
   * Assign one or more **Categories** and **Badges**.
   * Publish.

3. **Embed on a Page**

   * Insert the shortcodes:

     ```markdown
     [yono_promos period="morning,afternoon,evening"]
     [yono_games columns="3" per_page="60" sort="name"]
     ```

4. **Floating “Free Codes” Button**

   * Appears automatically on the site whenever there is at least one **active or upcoming** promo.

---

## Shortcodes

### `[yono_promos]`

Displays cards for your promo codes. Also powers the modal content.

**Attributes**

| Attribute      | Type   | Default                               | Notes                                                     |
| -------------- | ------ | ------------------------------------- | --------------------------------------------------------- |
| `period`       | string | `""`                                  | Comma-list of `morning, afternoon, evening`. Empty = all. |
| `status`       | string | `active,upcoming`                     | Include `expired` if desired.                             |
| `show_expired` | bool   | `false`                               | Show expired cards.                                       |
| `limit`        | int    | `100`                                 | Max posts to fetch.                                       |
| `layout`       | string | `cards`                               | Reserved for future layouts.                              |
| `columns`      | int    | `1`                                   | 1–3 responsive columns.                                   |
| `empty_text`   | string | `No promo codes available right now.` | Message when empty.                                       |
| `show_copy`    | bool   | `true`                                | Show “Copy” button.                                       |
| `show_timer`   | bool   | `true`                                | Show countdown/status.                                    |
| `order`        | string | `ASC`                                 | Title ordering.                                           |
| `format`       | string | `long`                                | `long` or `compact` countdown format.                     |

**Examples**

<<<<<<< HEAD
```markdown
[yono_promos period="morning"]
[yono_promos period="morning,evening" show_timer="false"]
[yono_promos show_expired="true" format="compact"]
```

---

### `[yono_games]`

Outputs the responsive games grid with toolbar.

**Attributes**

| Attribute      | Type   | Default | Notes                                  |
| -------------- | ------ | ------- | -------------------------------------- |
| `cat`          | string | `""`    | Filter by category names (comma-list). |
| `badge`        | string | `""`    | Filter by badge names (comma-list).    |
| `per_page`     | int    | `60`    | Max posts.                             |
| `columns`      | int    | `3`     | 1–4 columns.                           |
| `sort`         | string | `name`  | `name`, `launch`, `latest`.            |
| `show_count`   | bool   | `true`  | Show “Showing N games”.                |
| `show_search`  | bool   | `true`  | Show search input.                     |
| `show_filters` | bool   | `true`  | Show category chips.                   |

**Examples**

```markdown
[yono_games columns="4" sort="launch"]
[yono_games cat="Slots,Bingo" badge="Hot" sort="latest" per_page="24"]
[yono_games_list_archive per_page="10" cat="Rummy,Slots"]
[yono_games_grid_archive per_page="12" columns="5" cat="Rummy"]
[yono_latest_apps count="6" cat="Rummy,Slots"]



```

```
How to use

On a single game page:
[yono_related_apps] → auto-detects the current game and shows up to 6 related items.

Options:

count="4" – number of items

by="cat" / by="badge" / by="cat,badge"

order="relevance" (default) | latest | random

id="123" or slug="jaiho-win" – if you want to force the “source” game from a different page

```
=======
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
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e

---

## Promo Codes: Admin & Display

* **Fields**: Promo Code, Label/Description, Start (local), End (local)
* **Status logic**:

  * Before start → **Upcoming**
  * Between start and end (or no end) → **Active**
  * After end → **Expired**
* **Countdown**: “Starts in …” / “Ends in …” with `long` or `compact` style
* **Period tabs**: Morning / Afternoon / Evening (used in modal)

---

## Floating Widget

A vertical, gradient **Free Codes** pill fixed at the bottom-right opens a modal:

* **Tabs** for Morning / Afternoon / Evening
* **Copy** buttons and **countdowns** for each promo
* Appears automatically when there’s at least one **active or upcoming** promo

No configuration required—styles and scripts load with the plugin.

---

## Games: Admin & Display

* **Fields**

  * Logo (Media Library picker **or** paste external URL)
  * Subtitle, Welcome Bonus (range), Minimum Withdrawal
  * CTA Text + URL
  * Launch (local time) & status (`active`, `upcoming`, `retired`)
* **Taxonomies**

  * `game_cat`: Rummy, Slots, Arcade, Bingo (customizable)
  * `game_badge`: New, Hot, Coming Soon (customizable)
* **Front-end features**

  * **Search** by name/category/badge
  * **Category chips** (All, Slots, Rummy, etc.)
  * **Sort** by name, launch date, latest added
  * **Countdown** until launch with automatic switch to “Live”
  * Clean, compact **store-tile** cards with logo, spec box, CTA

---

<<<<<<< HEAD
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

## FAQ

**Q: How do I show only Afternoon promos?**
Use:

```markdown
[yono_promos period="afternoon"]
```

**Q: The floating button isn’t visible.**
There must be at least one **active or upcoming** promo. Create a promo or adjust Start/End.

**Q: Can I use external image URLs for logos?**
Yes. The Logo field accepts any valid URL; you can also select from the Media Library.

**Q: Can I change badge names or categories?**
Yes. Add/edit terms under **Games → Categories** or **Games → Badges**.

---

## Changelog

**2.1.0**

* Floating “Free Codes” widget + modal tabs
* Games grid with store-tile cards, search, filters, sorting, countdown
* CSV Import/Export (idempotent by title)
* Media picker for game logos with URL fallback
* Accessibility improvements, refined styles

---

=======
>>>>>>> 7de58f5b10964db4f5c4305dcd4e834e62c6092e
## License

GPL-2.0+
This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

---

## Credits

* **Author:** Kuya Mecky Pogi
* **GitHub:** [https://github.com/KuyaMecky](https://github.com/KuyaMecky)

If you’d like this README bundled into the plugin folder or want screenshot placeholders added, I can prep a `/assets/` section with example images and update links.
