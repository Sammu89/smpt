# Color System Documentation

Last updated: 2026-03-20

## GeneratePress Global Colors — Official Reference

> Appearance → Customize → Colors → Global Colors

By default, there are 7 colors added. Each color has a specific purpose.

From left to right:

- **Contrast** – strongest text color
- **Contrast 2** – lighter text color
- **Contrast 3** – lightest text/border color
- **Base** – dark background (strongest text still readable)
- **Base 2** – lighter background
- **Base 3** – lightest background (white)
- **Accent** – main branding color

You can delete or rename these default colors. However, if you do you will need to update the color fields that are using the deleted/renamed color, as those fields will no longer have a value.

You can also add as many of your own colors as you like. Keep your global colors simple and minimal – these are colors you're going to be reusing throughout your design and content. The beauty here is that you can always switch up the colors to completely change the style of your website.

**Contrast Colors** — typically your text/border colors.

**Base Colors** — typically your background colors. There should be enough contrast between these colors and your Contrast colors so your text is still readable.

**Accent Colors** — the least used colors on your site. They're meant to grab attention (links, buttons, special headings, etc…).

**Picking colors for elements:** hover on the color option in the Customizer to know what each option is for. Initial/hover/current color options can be picked individually for elements like the primary navigation's background and text.

---

## Overview

All theme colors are controlled through **GeneratePress Global Colors** (see above). These 7 variables are emitted as CSS custom properties on `:root` by GeneratePress automatically. Every color change should start in the Customizer — no CSS edits needed for palette changes.

The child theme CSS uses only these variables (plus a set of intentional semantic colors — see below).

---

## The 7 Global Color Variables

### Current values (set in DB via `generate_settings.global_colors`)

| Variable | Current Hex | Role |
|---|---|---|
| `--contrast` | `#222222` | Primary dark foreground — body text, headings, dark UI elements |
| `--contrast-2` | `#575760` | Secondary foreground — subdued text, meta info, placeholders, disabled button backgrounds |
| `--contrast-3` | `#b2b2be` | Tertiary foreground — borders on focus, empty star icons, category text, compatibility labels |
| `--base` | `#f0f0f0` | Subtle surface — dividers, card borders, tag borders, light separators |
| `--base-2` | `#f7f8f9` | Page background — body background, form inputs, ghost button backgrounds |
| `--base-3` | `#ffffff` | **Default white background** — the site's base white. Used for content area, modal panels, cards, form inputs, and as text color on dark/accent backgrounds. When you think "white background", use this. |
| `--accent` | `#2678dc` | Brand / action color — links, active nav items, CTA buttons, stream buttons, comment submit |

### Conceptual model

```
FOREGROUND (ink)       BACKGROUND (paper)     ACTION
--contrast  (darkest)  --base-3  (lightest)   --accent
--contrast-2           --base-2
--contrast-3 (lightest)--base    (darkest)
```

The scales go in opposite directions: contrast gets lighter, base gets darker.

`--base-3` is the **default white background**. It is `#ffffff` and should be used whenever you need a white surface — cards, modals, content area, form inputs, text on dark backgrounds. Do not hardcode `#fff` or `#ffffff` — always use `var(--base-3)`.

---

## Where Each Variable Is Used — By File

### `css/header.css`
- `--accent` — `.smpt-aurora-layer` background (the aurora canvas behind the header hero)
- `rgb(8, 13, 71)` — `.smpt-hero-background` deep navy gradient — **intentionally hardcoded**, do not replace with a GP variable. This is the branded deep-space navy for the hero background and must remain fixed regardless of theme palette changes.
- `--contrast` — Mobile menu close button, search input text, search button background, nav link text, login form input text, login form submit background, submenu toggle icon
- `--contrast-2` — Mobile menu eyebrow/title labels, login panel links
- `--base-3` — Search button text, login form submit text
- `--accent` — Account link hover color, guest toggle hover color (via `var(--accent)`)
- `--contrast` — Logged-in sub-menu link hover
- `--base` — Logged-in account sub-menu background

### `css/headers.css`
- `--accent` — Animated underline gradient on `.single-post-title`, `h2.estrela`, `h2.lua`
- `--contrast` — `h2.header`, `.single-post-title` text color, page-context `.single-post-title`
- `--contrast-2` — `h3.subheader` text color

### `css/episodios.css`
- `--base-3` — `.smpt-play--stream` text, `.smpt-dl` text, nostalgia button text, player close button text
- `--accent` — `.smpt-play--stream` background, `.smpt-dl` background
- `--contrast-2` — `.smpt-dl-help` text, `.smpt-play--nostalgia` background
- `--contrast` — `.valor` text (episode detail values), `.topo-link` text

### `css/episode-interactions.css`
- `--contrast-3` — Empty star fill, empty episode header star color
- `--contrast-2` — Rating info text, view count text, disabled button background, comment time, comment action buttons, comment author display name, empty state text
- `--accent` — Comment toggle hover border, watched button hover border, watched `is-watched` border, seen checkmark background/border, comment save background/border, comment submit background, load more text color
- `--base-3` — Seen checkmark text (white check on accent), comment save text, comment submit text
- `--contrast-3` — Disabled button text (light text on `--contrast-2` bg)

### `css/infobox.css`
- `--base-3` — Default infobox background
- `--base` — Default infobox border, divider lines, close button border
- `--base-2` — Close button background
- `--contrast` — Body text, header text, close button text
- `--contrast-2` — Footer text
- `--accent` — Infobox links

### `css/botoes_e_links.css`
- `--accent` — `.destaque` link color, `.a.destaque:hover` reverts to `--contrast`
- `--contrast` — `.a.destaque:hover` color
- `--contrast-2` — `.related-post-title a:hover` color

### `css/page-nav.css`
- `--accent` — Page nav link color
- `--contrast` — Page nav link hover color

### `css/noticias.css`
- `--accent` — `.beforetitle` gradient underline
- `--contrast-3` — Blog category "TEMA:" prefix text
- `--base-3` — Blog thumbnail hover overlay text ("LER NOTÍCIA"), pagination text
- `--contrast` — Pagination background
- `--base` — Post tag border-bottom
- `--accent` — `h3.sd-title` (share section) top border

### `css/member-area.css`
- `--accent` — Kicker text (`.smpt-member-kicker`), buttons (`.smpt-member-form button`, `.smpt-member-button`), inline links
- `--base-3` — Active switch tab background, form input background, button text on accent, member card-like surfaces
- `--base-2` — Ghost button background (`.smpt-member-button--ghost`)
- `--contrast` — Ghost button text
- `--contrast-2` — Note text (`.smpt-member-note`), activity timestamp
- `--base` — Activity item divider border

### `css/audio.css`
- `--accent` — Audio player background and outline when `.isPlaying`

### `css/teste.css`
- `--contrast-3` — `.compatibilidade` label text (compatibility checkbox)

---

## How to Change the Palette

**For any of the 7 global colors:**
1. Go to Appearance → Customize → Colors → Global Colors
2. Change the hex value
3. All CSS variables update site-wide automatically — no child theme edits needed

**For the accent color specifically (most common change):**
- Changing `--accent` updates: aurora header, CTA buttons, download buttons, nav active state, links, comment forms, pagination bg, audio player — everything at once.

---

## Semantic Colors — Intentionally NOT Using GP Globals

These are hardcoded because they represent specific meanings unrelated to the theme palette:

| Color | Hex | Usage |
|---|---|---|
| Like green | `#4caf50` / `#2e7d32` | Like button hover, active state, thumb icon |
| Dislike red | `#f44336` / `#c62828` | Dislike button hover, active state, delete hover |
| Star / Favorite gold | `#f5c518` / `#a36b00` | Star fill, favorite button active, IMDb-style rating |
| Quero ver pink | `#e91e63` / `#ad1457` | "Want to watch" button active state |
| Bullet gold | `#ffd700` | `★` star bullets in nav lists |
| Audio player gold | `#DFBB3A` | Default (idle) audio player color |
| Error dark red | `#8d1c34` / `#8b2020` | Error notice text in header login / member area |
| Success dark blue | `#184f93` | Success notice text in member area |
| Video black | `#000` | Video player and TV frame iframe backgrounds |
| Confetti pink | `#ff4d6d` | Like confetti burst animation |
| Gloom slate | `#2f3645` | Dislike gloom drip animation |

These colors must remain hardcoded. Do not replace them with GP globals.

---

## `--smpt-*` Table Theming Variables

Episode tables use a layered variable system. These are set externally (per-table via shortcode attributes or PHP) and resolve into the standard GP variables as fallbacks:

```css
--smpt-table-divider-color-resolved  → fallback: var(--contrast-3) or #eee
--smpt-table-background-color-resolved → fallback: var(--base-3) or #fff
--smpt-table-header-background-color-resolved → fallback: #000 (intentional black)
--smpt-table-header-text-color-resolved → fallback: var(--base-3)
--smpt-table-label-color-resolved → fallback: var(--contrast-2)
--smpt-table-border-color-resolved → fallback: var(--base)
```

These allow per-table overrides without touching the global palette.

---

## Quick Reference for Agents

| I need to style... | Use |
|---|---|
| Body / heading text | `var(--contrast)` |
| Subdued / meta text | `var(--contrast-2)` |
| Borders / empty icons / light labels | `var(--contrast-3)` |
| Subtle backgrounds / dividers | `var(--base)` |
| Page / input backgrounds | `var(--base-2)` |
| Card / modal / white surfaces | `var(--base-3)` |
| Links / buttons / brand color | `var(--accent)` |
| Text on top of `--accent` | `var(--base-3)` |
| Text on top of `--contrast` | `var(--base-3)` |
| Like/dislike/stars/favorites | Hardcoded semantic (see table above) |
