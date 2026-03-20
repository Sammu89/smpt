# Button Color Audit

Last updated: 2026-03-20

Complete reference of every button and button-like element in the child theme and mu-plugin, grouped by visual category.

---

## Group 1 — Primary Buttons (accent fill)

These are the main call-to-action buttons. Solid accent background, light text.

| Class | File | Background | Text | Border | Hover |
|---|---|---|---|---|---|
| `.smpt-play--stream` | `episodios.css` | `var(--accent)` | `var(--base-3)` | none | `opacity: 0.85` |
| `.smpt-dl` | `episodios.css` | `var(--accent)` | `var(--base-3)` | none | `opacity: 0.85` |
| `.smpt-ep-comment-save` | `episode-interactions.css` | `var(--accent)` | `var(--base-3)` | `var(--accent)` | — |
| `.smpt-ep-comment-submit` | `episode-interactions.css` | `var(--accent)` | `var(--base-3)` | none | `opacity: 0.85` |
| `.smpt-member-form button` / `.smpt-member-button` | `member-area.css` | `var(--accent)` | `var(--base-3)` | none | — |

---

## Group 2 — Secondary / Ghost Buttons (transparent fill)

Transparent or neutral background, bordered. Used for secondary actions alongside primary buttons.

| Class | File | Background | Text | Border | Hover border |
|---|---|---|---|---|---|
| `.smpt-ep-like-btn` | `episode-interactions.css` | none | inherits | `var(--smpt-table-divider-color-resolved)` | `#4caf50` (green) |
| `.smpt-ep-dislike-btn` | `episode-interactions.css` | none | inherits | `var(--smpt-table-divider-color-resolved)` | `#f44336` (red) |
| `.smpt-ep-comment-toggle` | `episode-interactions.css` | none | inherits | `var(--smpt-table-divider-color-resolved)` | `var(--accent)` |
| `.smpt-ep-watched-btn` | `episode-interactions.css` | none | inherits | `var(--smpt-table-divider-color-resolved)` | `var(--accent)` |
| `.smpt-ep-want-btn` | `episode-interactions.css` | none | inherits | `var(--smpt-table-divider-color-resolved)` | `#e91e63` (pink) |
| `.smpt-ep-favorite-btn` | `episode-interactions.css` | none | inherits | `var(--smpt-table-divider-color-resolved)` | `#f5c518` (gold) |
| `.smpt-ep-comment-cancel` | `episode-interactions.css` | none | inherits | `var(--smpt-table-divider-color-resolved)` | — |
| `.smpt-ep-load-more` | `episode-interactions.css` | none | `var(--accent)` | `var(--smpt-table-divider-color-resolved)` | `rgba(accent, 0.05)` bg |
| `.smpt-member-button--ghost` | `member-area.css` | `var(--base-2)` | `var(--contrast)` | none | — |

---

## Group 3 — Disabled Buttons

Applied on top of any button via `.smpt-ep-btn--disabled`. Uses `!important` to override all other states.

| Class | File | Background | Text | Notes |
|---|---|---|---|---|
| `.smpt-ep-btn--disabled` | `episode-interactions.css` | `var(--contrast-2)` `!important` | `var(--contrast-3)` `!important` | Shown to logged-out users on all interaction buttons |
| `.smpt-dl.smpt-ep-btn--disabled` | `episode-interactions.css` | `var(--contrast-2)` `!important` | `var(--contrast-3)` `!important` | Specifically overrides the download button variant |

---

## Group 4 — Interaction-State Buttons (semantic colors, not theme palette)

These buttons change color to signal a specific meaning. Colors are intentionally hardcoded and must not be replaced with GP globals.

| Class | State | Background | Border | Text |
|---|---|---|---|---|
| `.smpt-ep-like-btn.is-active` | Liked | `rgba(76,175,80, 0.12)` | `#4caf50` | `#2e7d32` |
| `.smpt-ep-dislike-btn.is-active` | Disliked | `rgba(244,67,54, 0.12)` | `#f44336` | `#c62828` |
| `.smpt-ep-watched-btn.is-watched` | Watched | `rgba(38,120,220, 0.12)` | `var(--accent)` | `var(--accent)` |
| `.smpt-ep-want-btn.is-active` | Want to watch | `rgba(233,30,99, 0.1)` | `#e91e63` | `#ad1457` |
| `.smpt-ep-favorite-btn.is-active` | Favorited | `rgba(245,197,24, 0.12)` | `#f5c518` | `#a36b00` |

---

## Group 5 — Utility / UI Buttons (header & navigation)

Buttons used in the mobile menu, sticky nav, and login panel.

| Class | File | Background | Text | Notes |
|---|---|---|---|---|
| `.smpt-mobile-menu__search-form button` | `header.css` | `var(--contrast)` | `var(--base-3)` | Search submit in mobile menu |
| `.smpt-nav-login-panel__form button` | `header.css` | `var(--contrast)` | `var(--base-3)` | Login submit in nav dropdown |
| `.smpt-mobile-menu__close` | `header.css` | `rgba(15,31,79, 0.08)` | `var(--contrast)` | Close "×" button in mobile panel |
| `.smpt-mobile-submenu-toggle` | `header.css` | transparent | `var(--contrast)` | Expand/collapse chevron in mobile nav |
| `.smpt-mobile-submenu-toggle__label` | `header.css` | `rgba(15,31,79, 0.08)` | inherits | The pill badge around the chevron |
| `.smpt-player-close` | `episodios.css` | `rgba(0,0,0, 0.6)` | `var(--base-3)` | Close button overlaid on video player |

---

## Group 6 — GP-inherited Buttons (no color set in child theme)

These inherit colors directly from GeneratePress's button settings (Customizer → Colors → Buttons). No color is set in the child theme CSS. To change these, use the GP Customizer.

| Class | File | Notes |
|---|---|---|
| `.botao`, `.botao-download` | `botoes_e_links.css` | Generic reusable button class |
| `.botao-voltar` | `botoes_e_links.css` | Back navigation button |
| `.episodio-opcoes .button`, `.episodio-opcoes button` | `botoes_e_links.css` | Buttons inside episode option columns |
| `.smpt-toggle` | `episodios.css` | Accordion toggle (season/group switches) — inherits GP button |

---

## Group 7 — Admin Dashboard Buttons (mu-plugin, WP admin only)

These are in the WordPress admin dashboard and intentionally use WP admin colors, not the front-end GP global variables.

| Class | File | Background | Text | Notes |
|---|---|---|---|---|
| `.smpt-toggle-btn` | `analytics-dashboard.css` | `#2271b1` | `#fff` | WP admin blue — toggle analytics view |
| `.smpt-toggle-btn:hover` | `analytics-dashboard.css` | `#135e96` | — | WP admin blue hover |
| `.smpt-period-btn` | `analytics-dashboard.css` | `#f0f0f1` | `#2c3338` | Period selector (inactive) — WP admin neutral |
| `.smpt-period-btn:hover` | `analytics-dashboard.css` | `#e0e0e1` | — | — |
| `.smpt-period-btn.smpt-period-active` | `analytics-dashboard.css` | `#FF69B4` | `#fff` | Active period — hot pink (branded) |

> Admin buttons use WP admin colors on purpose. Do not apply GP global variables here.

---

## Comment / Form Action Buttons (episode comments)

| Class | File | Background | Text | Border |
|---|---|---|---|---|
| `.smpt-ep-comment-action` (edit/delete) | `episode-interactions.css` | none | `var(--contrast-2)` | none |
| `.smpt-ep-comment-action:hover` | `episode-interactions.css` | — | `var(--accent)` | — |
| `.smpt-ep-comment-delete:hover` | `episode-interactions.css` | — | `#f44336` (red) | — |

---

## Quick Summary

| Pattern | When to use |
|---|---|
| Accent bg + base-3 text | Primary CTA — stream, download, submit, save |
| Transparent + divider border | Secondary/ghost — like, dislike, watched, comment toggle |
| Contrast bg + base-3 text | Utility submit — search, login form |
| Contrast-2 bg + contrast-3 text | Disabled state (logged-out) — all interaction buttons |
| Semantic colors (green/red/pink/gold) | Active states that signal a specific meaning |
| GP Customizer button colors | Generic `.button` and `.botao-*` classes |
| WP admin colors | Analytics dashboard only |
