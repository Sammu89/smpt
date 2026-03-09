# Hero Header Redo — Implementation Plan

## Goal

Rebuild the hero header behavior cleanly after rollback, with linear scaling, explicit background layers, canvas-based aurora and stars, and final visual cleanup.

## Architecture Overview

### Layer Stack (back to front)

```
z-index  Element                          What it does
───────  ───────────────────────────────  ─────────────────────────────────────────
  0      .smpt-aurora-layer               <div> with background-color: #2678DC (Mercury blue) coded on header.css not inline
           └─ <canvas>                    Transparent canvas. JS draws colored aurora blobs on it.

  1      .smpt-hero-background            <div> with a navy linear-gradient that uses
                                          CSS custom properties --bg-angle and --bg-mid.
                                          JS updates those properties slowly over time.

  2      <canvas class="smpt-stars-canvas"> Transparent canvas. JS draws all 58 dots + 7 flare
                                          stars here (radial gradients + flare rays).

  3+     Content                          .smpt-header-hero__logo (z:2),
                                          .smpt-hero-header__image (z:3) — senshi.
```

All three animated layers sit inside `.site-header` as first children, each with `position: absolute; inset: 0; pointer-events: none;`. Content sits on top via its own z-index.

### Single Animation Loop

There is exactly ONE `requestAnimationFrame` loop in the entire file. It calls three sub-updaters with internal throttling:

| Sub-system       | Target FPS | Reason                                          |
|------------------|------------|-------------------------------------------------|
| Navy gradient    | ~5 fps     | Changes over 3–8 minutes. 5 fps is overkill already. |
| Aurora canvas    | ~20 fps    | Blobs are huge and blurry. 20 fps looks identical to 60. |
| Stars canvas     | ~30 fps    | Twinkle/drift needs slightly more fluidity.     |

The loop uses a timestamp diff to decide which sub-system to update each frame.

### Files Involved

| File                                  | Changes needed                                         |
|---------------------------------------|--------------------------------------------------------|
| `css/header.css`                      | Delete pseudo-element backgrounds, delete all aurora CSS keyframes, delete star DOM styles, add new layer classes, fix linear scaling. |
| `javascript/hero-header-animation.js` | Full rewrite: canvas aurora, canvas stars, navy gradient animation, single rAF loop. |
| `template-parts/header/hero.php`      | No structural changes needed (JS creates the layers dynamically). |
| `inc/assets.php`                      | No changes needed (already enqueues the right JS/CSS files). |

---

## Phase 1 — Linear Scaling (No Breakpoint Jumps from 1650 to 650)

### What to change in `css/header.css`

#### 1.1 Header height

**Current state (REMOVE):**
- Default: `min-height: clamp(200px, 42svh, 500px)` on `.inside-header`.
- `@media (max-width: 1100px)`: overrides to `min-height: clamp(220px, 40svh, 430px)`.
- Other breakpoints also touch height.

**Replace with a single rule (no media query):**
```css
.site-header .inside-header {
  min-height: clamp(220px, 28vw, 500px);
}
```
`28vw` at 1650px = 462px. `28vw` at 650px = 182px, clamped up to 220px. Linear. No jumps.

#### 1.2 Senshi image

**Current state (REMOVE):**
- Default: `width: clamp(420px, 58vw, 980px)` on `.smpt-hero-header__image img`.
- `@media (max-width: 1100px)`: changes width/max-width/max-height to different values.

**Replace with single rules (no media query):**
```css
.smpt-hero-header__image img {
  width: clamp(420px, 58vw, 980px);
  max-width: min(72vw, 980px);
  max-height: min(58svh, 607px);
  object-fit: contain;
  object-position: center bottom;
}
```
Remove the 1100px breakpoint override entirely. The clamp already handles the range.

#### 1.3 Logo

**Current state (REMOVE):**
- Default: `width: clamp(240px, 30vw, 500px)` on `.smpt-header-hero__logo img`.
- Various breakpoint overrides change this.

**Replace with single rules (no media query):**
```css
.smpt-header-hero__logo img {
  width: clamp(240px, 30vw, 500px);
  max-width: 40vw;
}
```
Add safe left margin to the logo wrapper to prevent edge clipping:
```css
.smpt-header-hero__logo {
  margin-left: clamp(0.5rem, 2vw, 1.5rem);
}
```
Remove all 1100px breakpoint overrides for logo sizing.

#### 1.4 Particle scale (stars canvas)

Stars are now on canvas, so the old CSS variable `--smpt-particle-scale` and its breakpoint steps (`1 → 0.78 → 0.55 → 0.4`) are deleted. Instead, in JS, read the header width and compute a scale factor:

```javascript
const scale = Math.max(0.4, Math.min(1, (headerWidth - 320) / (1650 - 320)));
```
This linearly maps 320px→0.4, 1650px→1.0. Apply it when computing star sizes in the canvas draw.

#### 1.5 Keep the 650px breakpoint

The `@media (max-width: 650px)` breakpoint stays because it switches the layout from side-by-side (flexbox) to stacked (CSS grid) for mobile. That is a layout mode change, not a scaling jump, so it is acceptable.

#### 1.6 Remove these media queries entirely (hero-related rules only)

- `@media (max-width: 1100px)` — hero height, senshi size, logo size, particle scale.
- `@media (max-width: 480px)` — hero-specific padding/sizing overrides.
- `@media (max-width: 320px)` — hero-specific minimum widths.

Keep any rules in these queries that affect non-hero elements (navigation, etc.).

### Acceptance criteria

- [ ] Resize browser smoothly from 1650 to 650. Height, senshi, logo all scale without any visible snap.
- [ ] At 1400, 1200, 900, 700 — no jumps.
- [ ] At 650 and below — mobile grid layout kicks in (this is the only allowed breakpoint).

---

## Phase 2 — Replace Pseudo-Element Backgrounds with Real DOM Layers

### What to delete in `css/header.css`

#### 2.1 Delete `.site-header::before` (lines ~22–50)

This is the animated navy sky gradient pseudo-element. Delete the entire rule block including:
- The `content: ""`, `position: absolute`, `left: calc(50% - 50vw)`, `width: 100vw` setup.
- The `background: linear-gradient(...)` with navy colors.
- The `animation: smpt-sky-angle 42s ...` property.

#### 2.2 Delete `.site-header::after` (lines ~52–74)

This is the "breathing" gradient layer. Delete the entire rule block.

#### 2.3 Delete `.smpt-hero-header::before` (lines ~352–372)

This is the radial glow pseudo-element under the senshi. Delete the entire rule block.

#### 2.4 Delete these `@keyframes` from `css/header.css`

- `@keyframes smpt-sky-angle` — the rotating gradient angle animation.
- `@keyframes smpt-sky-breathe` — the breathing opacity animation.

### What to add in `css/header.css`

#### 2.5 Aurora layer

```css
.smpt-aurora-layer {
  position: absolute;
  inset: 0;
  z-index: 0;
  overflow: hidden;
  pointer-events: none;
  background-color: #2678DC; /* Mercury blue — always visible as base */
}
.smpt-aurora-layer canvas {
  display: block;
  width: 100%;
  height: 100%;
}
```

#### 2.6 Navy gradient layer

```css
.smpt-hero-background {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  background: linear-gradient(
    var(--bg-angle, 5deg),
    rgb(8, 13, 71) 0%,
    rgb(8, 13, 71) var(60%), The starting value should be 60% and then it is randomized within a range of 35 to 80% by JS on page load.
    rgba(8, 13, 71, 0) 100%
  );
}
```
The JS animation range is `-15deg` to `+15deg`. The starting value should be 5deg and then it is randomized within that range by JS on page load.

#### 2.7 Stars canvas layer

```css
.smpt-stars-canvas {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  display: block;
  width: 100%;
  height: 100%;
}
```

### What to add in `javascript/hero-header-animation.js`

#### 2.8 Create layers on DOMContentLoaded

```javascript
const siteHeader = document.querySelector('.site-header');

// 1. Aurora layer (z:0)
const auroraLayer = document.createElement('div');
auroraLayer.className = 'smpt-aurora-layer';
const auroraCanvas = document.createElement('canvas');
auroraLayer.appendChild(auroraCanvas);

// 2. Navy gradient layer (z:0, stacks above aurora via DOM order)
const navyLayer = document.createElement('div');
navyLayer.className = 'smpt-hero-background';

// 3. Stars canvas (z:1)
const starsCanvas = document.createElement('canvas');
starsCanvas.className = 'smpt-stars-canvas';

// Insert in order: aurora first, then navy, then stars
siteHeader.prepend(starsCanvas);
siteHeader.prepend(navyLayer);
siteHeader.prepend(auroraLayer);
```

### What to delete from `css/header.css`

#### 2.9 Delete all existing star DOM styles

Delete every rule that targets these selectors (they were for the old DOM-based stars):
- `.smpt-header-hero__sky`
- `.smpt-after-background`
- `.smpt-after-background--dot`
- `.smpt-after-background--flare`
- `.smpt-after-background-core`
- `.smpt-after-background-ray`
- `.smpt-after-background-ray--vertical`
- `.smpt-after-background-ray--horizontal`
- `.smpt-after-background--white`
- `.smpt-after-background--gold`
- `.smpt-after-background--yellow`

Also delete these `@keyframes`:
- `@keyframes smpt-sparkle-fade`
- `@keyframes smpt-sparkle-drift`
- `@keyframes smpt-ray-pulse-v`
- `@keyframes smpt-ray-pulse-h`

#### 2.10 Delete all existing aurora DOM styles

Delete every rule that targets these selectors:
- `.smpt-before-background`
- `.smpt-before-background__blob`
- `.smpt-before-background__blob--gold` (and all other color variants)

Also delete these `@keyframes`:
- `@keyframes smpt-before-background-fade`
- `@keyframes smpt-before-background-sway-a`
- `@keyframes smpt-before-background-sway-b`
- All per-blob opacity keyframes (e.g. `smpt-before-background-blob-1-opacity`, etc.)

### Acceptance criteria

- [ ] Page loads with three real DOM layers visible in DevTools: `.smpt-aurora-layer`, `.smpt-hero-background`, `.smpt-stars-canvas`.
- [ ] No `::before` or `::after` pseudo-elements on `.site-header` or `.smpt-hero-header` for background purposes.
- [ ] Mercury blue is visible where navy gradient is transparent.
- [ ] No CSS `@keyframes` remain for sky, breathing, sway, sparkle, or ray animations.

---

## Phase 3 — Animate the Navy Gradient via JS

### How it works

The navy gradient covers the bottom portion of the header, fading to transparent at the top (revealing the Mercury blue aurora layer behind it). Two properties animate very slowly:

- **Angle** (`--bg-angle`): drifts between **-15deg** and **+15deg**.
- **Midpoint** (`--bg-mid`): drifts between **35%** and **80%** (where the solid navy ends and the fade-to-transparent begins).

### Speed behavior

Each animation cycle (one smooth drift from current value to a new random target), the system picks a random duration:
- **Slowest (glacial):** 480 seconds (8 minutes) for a full swing.
- **Fastest:** 4 minutes for a full swing.
- Duration is picked randomly per cycle: `180 + Math.random() * 300` seconds.
- When one cycle completes (target reached), a new random target and new random duration are chosen.
- Angle and midpoint animate independently (separate targets, separate durations).

### Implementation in `hero-header-animation.js`

```javascript
// State for navy gradient animation
const navy = {
  angle:    { current: randomBetween(-15, 15), target: 0, duration: 0, elapsed: 0 },
  midpoint: { current: randomBetween(35, 80),  target: 0, duration: 0, elapsed: 0 },
};

function pickNavyTarget(prop, min, max) {
  prop.target   = randomBetween(min, max);
  prop.duration = 180 + Math.random() * 300; // 240–480 seconds
  prop.elapsed  = 0;
}

// Initialize with first targets
pickNavyTarget(navy.angle, -15, 15);
pickNavyTarget(navy.midpoint, 35, 80);

function updateNavy(dtSeconds) {
  [
    { prop: navy.angle,    min: -15, max: 15 },
    { prop: navy.midpoint, min: 35,  max: 80 },
  ].forEach(({ prop, min, max }) => {
    prop.elapsed += dtSeconds;
    const t = Math.min(prop.elapsed / prop.duration, 1);
    // Smooth easing (ease-in-out)
    const eased = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
    prop.current = prop.start + (prop.target - prop.start) * eased;
    if (t >= 1) {
      prop.start = prop.current;
      pickNavyTarget(prop, min, max);
    }
  });
  navyLayer.style.setProperty('--bg-angle', navy.angle.current + 'deg');
  navyLayer.style.setProperty('--bg-mid',   navy.midpoint.current + '%');
}
```

Note: each prop also needs a `start` value. Set `prop.start = prop.current` when calling `pickNavyTarget`. The code above shows the pattern — store `start` alongside `current`, `target`, `duration`, `elapsed`.

### Update frequency

Call `updateNavy(dt)` inside the main rAF loop, but only if at least **200ms** have passed since the last navy update. The gradient moves so slowly that 5 fps is more than enough.

### Acceptance criteria

- [ ] On page load, the navy gradient is visible with a random starting angle and midpoint.
- [ ] Over 4–8 minutes, the angle and midpoint visibly drift to new values.
- [ ] The motion is smooth (eased), never jerky.
- [ ] Speed varies randomly between cycles.
- [ ] DevTools shows only `--bg-angle` and `--bg-mid` changing on the element — no inline `background` rewrites.

---

## Phase 4 — Aurora via Canvas (Mercury Blue Base + Animated Blobs)

### Container setup

The `.smpt-aurora-layer` div has `background-color: #2678DC` (Mercury blue) coded via header.css The `<canvas>` inside it is transparent. When no blobs are present, the user sees pure Mercury blue.

### Canvas setup

```javascript
const auroraCtx = auroraCanvas.getContext('2d');

function sizeAuroraCanvas() {
  const rect = siteHeader.getBoundingClientRect();
  // Cap DPI at 1 for aurora — blobs are blurry by nature, no need for retina resolution
  auroraCanvas.width  = rect.width;
  auroraCanvas.height = rect.height;
}
window.addEventListener('resize', sizeAuroraCanvas);
sizeAuroraCanvas();
```

### Blob data structure

```javascript
const AURORA_COLORS = [
  { name: 'Mars',      hex: '#D62636', rgb: [214, 38, 54]   },
  { name: 'Jupiter',   hex: '#28AA5F', rgb: [40, 170, 95]   },
  { name: 'Venus',     hex: '#FFB928', rgb: [255, 185, 40]  },
  { name: 'Moon',      hex: '#FFB6C1', rgb: [255, 182, 193] },
  { name: 'ChibiMoon', hex: '#FF6EAA', rgb: [255, 110, 170] },
  { name: 'Uranus',    hex: '#55A0FF', rgb: [85, 160, 255]  },
  { name: 'Neptune',   hex: '#008C96', rgb: [0, 140, 150]   },
  { name: 'Pluto',     hex: '#780F23', rgb: [120, 15, 35]   },
  { name: 'Saturn',    hex: '#5F2882', rgb: [95, 40, 130]   },
];
```

Each active blob is an object:

```javascript
{
  color:       { r, g, b },       // from AURORA_COLORS
  x:           Number,            // current center X (px)
  y:           Number,            // current center Y (px)
  startX:      Number,            // entry point X
  startY:      Number,            // entry point Y
  endX:        Number,            // exit point X
  endY:        Number,            // exit point Y
  radiusX:     Number,            // current horizontal radius (px) — oval, mostly horizontal
  radiusY:     Number,            // current vertical radius (px) — smaller than radiusX
  startRadiusX: Number,           // radius at birth
  endRadiusX:   Number,           // radius at death (for grow/shrink behavior)
  startRadiusY: Number,
  endRadiusY:   Number,
  lifetime:    Number,            // total lifetime in seconds (30–50)
  age:         Number,            // seconds since spawn
  texture:     OffscreenCanvas,   // pre-rendered blob gradient (see below)
  textureDirty: Boolean,          // true when radius changed enough to need re-render
}
```

### Blob lifecycle manager

Maintain an array `activeBlobs = []`. The manager runs inside the main rAF loop at ~20fps.

#### Spawning rules

- **Max concurrent blobs:** 3–4 (pick `maxBlobs = 3 + Math.round(Math.random())` on page load).
- **Spawn interval:** After a blob spawns, wait a random **5–15 seconds** before spawning the next.
- Track `nextSpawnTime` (starts at a random 2–5s after page load).
- When `activeBlobs.length < maxBlobs` AND current time > `nextSpawnTime`:
  1. Create a new blob.
  2. Set `nextSpawnTime = now + randomBetween(5, 15)`.

#### Creating a blob

1. **Pick color:** Random from `AURORA_COLORS`. Avoid picking the same color as any currently active blob if possible (try up to 5 times, then accept a duplicate).

2. **Pick entry side:** Random 0–3 (top, right, bottom, left).

3. **Pick entry position along that side:**
   - If top: `x = random(0, canvasWidth)`, `y = -radiusY` (off-screen above).
   - If right: `x = canvasWidth + radiusX`, `y = random(0, canvasHeight)`.
   - If bottom: `x = random(0, canvasWidth)`, `y = canvasHeight + radiusY`.
   - If left: `x = -radiusX`, `y = random(0, canvasHeight)`.

4. **Pick exit side:** Random, but MUST be different from entry side.

5. **Pick exit position** along the exit side (same logic as entry but for the exit side).

6. **Pick size behavior** (random, equal probability):
   - **Grow:** start radius is 60–80% of max, end radius is 100% of max.
   - **Shrink:** start radius is 100% of max, end radius is 40–60% of max.
   - **Constant:** start and end radius are the same (80–100% of max).
   - Max radius range: `radiusX` between 150–400px, `radiusY` is `radiusX * randomBetween(0.4, 0.7)` (horizontal oval).

7. **Pick lifetime:** `randomBetween(25, 50)` seconds.

8. **Pre-render texture** (see below).

#### Pre-rendered blob texture

To avoid creating a `ctx.createRadialGradient` every frame, render each blob once to an offscreen canvas when it spawns:

```javascript
function renderBlobTexture(blob) {
  const size = Math.ceil(Math.max(blob.radiusX, blob.radiusY) * 2.5);
  const offscreen = new OffscreenCanvas(size, size);
  const ctx = offscreen.getContext('2d');
  const cx = size / 2, cy = size / 2;
  const gradient = ctx.createRadialGradient(cx, cy, 0, cx, cy, size / 2);
  const { r, g, b } = blob.color;
  gradient.addColorStop(0,   `rgba(${r},${g},${b}, 0.6)`);
  gradient.addColorStop(0.5, `rgba(${r},${g},${b}, 0.25)`);
  gradient.addColorStop(1,   `rgba(${r},${g},${b}, 0)`);
  ctx.fillStyle = gradient;
  ctx.fillRect(0, 0, size, size);
  blob.texture = offscreen;
  blob.textureDirty = false;
}
```

Re-render the texture only when `radiusX` has changed by more than 10% since last render (check every ~1 second, not every frame).

#### Updating blobs each frame

For each blob in `activeBlobs`:

1. **Advance age:** `blob.age += dt`.
2. **Compute progress:** `t = blob.age / blob.lifetime` (0 to 1).
3. **Compute position:** linear interpolation from start to end.
   ```javascript
   blob.x = blob.startX + (blob.endX - blob.startX) * t;
   blob.y = blob.startY + (blob.endY - blob.startY) * t;
   ```
4. **Compute radius:** linear interpolation from start radius to end radius.
   ```javascript
   blob.radiusX = blob.startRadiusX + (blob.endRadiusX - blob.startRadiusX) * t;
   blob.radiusY = blob.startRadiusY + (blob.endRadiusY - blob.startRadiusY) * t;
   ```
   If radius changed >10% since last texture render, set `textureDirty = true`.
5. **Compute opacity:** fade in over first 3s, full in the middle, fade out over last 3s.
   ```javascript
   let opacity = 1;
   if (blob.age < 3) opacity = blob.age / 3;
   else if (blob.age > blob.lifetime - 3) opacity = (blob.lifetime - blob.age) / 3;
   ```
6. **If `blob.age >= blob.lifetime`:** remove from `activeBlobs`.

#### Drawing blobs each frame

```javascript
function drawAurora() {
  auroraCtx.clearRect(0, 0, auroraCanvas.width, auroraCanvas.height);
  for (const blob of activeBlobs) {
    if (blob.textureDirty) renderBlobTexture(blob);
    auroraCtx.globalAlpha = opacity; // computed in update step
    const drawW = blob.radiusX * 2.5;
    const drawH = blob.radiusY * 2.5;
    auroraCtx.drawImage(
      blob.texture,
      blob.x - drawW / 2,
      blob.y - drawH / 2,
      drawW,
      drawH
    );
  }
  auroraCtx.globalAlpha = 1;
}
```

### Acceptance criteria

- [ ] When page loads, Mercury blue (#2678DC) is visible.
- [ ] Within 2–5 seconds, first blob appears, fading in over 3 seconds.
- [ ] Blobs are oval (wider than tall), soft-edged, clearly colored.
- [ ] Blobs drift across the canvas from one side to another over 30–50s.
- [ ] Some blobs grow, some shrink, some stay constant size.
- [ ] Max 3–4 blobs visible at once. Sometimes fewer (gaps between spawns).
- [ ] All 9 colors appear over time (randomized).
- [ ] Entry/exit sides vary randomly — no repetitive patterns.
- [ ] CPU usage stays low (check DevTools Performance tab — should see minimal JS time).
- [ ] On a low-end device, aurora remains smooth at 20fps.

---

## Phase 5 — Stars on Canvas (Particle System Refactor)

### Star data structure

On initialization, generate an array of 58 dot objects and 7 flare objects. These are permanent (not spawned/destroyed like aurora blobs). They are stored in arrays:

```javascript
const dots = [];   // 58 items
const flares = []; // 7 items
```

#### Dot object

```javascript
{
  x:          Number,   // position as fraction 0–1 of canvas width
  y:          Number,   // position as fraction 0–1 of canvas height
  size:       Number,   // core radius in px (1.1 to 3.2)
  glowSize:   Number,   // glow radius = size * 7
  tone:       String,   // 'white' | 'gold' | 'yellow'
  opacity:    Number,   // current opacity (0–1), pulsing
  phase:      Number,   // random start phase (0–2π)
  twinkleDur: Number,   // seconds per full twinkle cycle (1.8–3.6)
  driftDur:   Number,   // seconds per full drift cycle (5.4–8.6)
  driftDist:  Number,   // max drift in px (2–7)
  driftPhase: Number,   // random start phase for drift (0–2π)
}
```

#### Flare object

```javascript
{
  x:            Number,   // position as fraction 0–1 of canvas width
  y:            Number,   // position as fraction 0–1 of canvas height
  coreSize:     Number,   // core radius in px (3–5)
  glowSize:     Number,   // glow radius = coreSize * 7
  rayV:         Number,   // vertical ray length in px (48–76)
  rayH:         Number,   // horizontal ray length in px (38–62)
  rayWidth:     Number,   // ray thickness in px (1.5)
  tone:         String,   // 'gold' (65% chance) | 'white' (35% chance)
  opacity:      Number,   // current opacity — core AND rays share this
  phase:        Number,   // random start phase (0–2π) — SAME for core and rays
  twinkleDur:   Number,   // seconds per cycle (2.4–4.2) — SAME for core and rays
  driftDur:     Number,   // seconds per drift cycle (6.2–9.5)
  driftDist:    Number,   // max drift in px (3–8)
  driftPhase:   Number,   // random start phase for drift
}
```

**Critical:** flare core opacity and ray opacity use the **same** `phase` and `twinkleDur`. This keeps them synchronized. There is no separate ray pulse timing.

### Star placement

#### Dot placement

- `x`: random between 0.02 and 0.98.
- `y`: weighted toward upper area using `Math.pow(Math.random(), 1.35)`, mapped to 0.05–0.63.
- Must pass exclusion zone check (see Phase 6). If inside exclusion zone, re-roll (max 20 attempts).

#### Flare placement

- Uses grid-based anti-clustering (see Phase 7).
- Must pass exclusion zone check.

### Tone colors

```javascript
const STAR_TONES = {
  white:  { core: [255, 255, 255], glow: [200, 210, 255] },
  gold:   { core: [255, 240, 180], glow: [255, 210, 100] },
  yellow: { core: [255, 255, 200], glow: [255, 255, 140] },
};
```

### Canvas setup

```javascript
const starsCtx = starsCanvas.getContext('2d');

function sizeStarsCanvas() {
  const rect = siteHeader.getBoundingClientRect();
  const dpr = Math.min(window.devicePixelRatio || 1, 2); // cap at 2x
  starsCanvas.width  = rect.width * dpr;
  starsCanvas.height = rect.height * dpr;
  starsCtx.scale(dpr, dpr);
  // Store logical size for coordinate calculations
  starsCanvas._logicalWidth  = rect.width;
  starsCanvas._logicalHeight = rect.height;
}
```

### Drawing a dot

```javascript
function drawDot(dot, now) {
  const w = starsCanvas._logicalWidth;
  const h = starsCanvas._logicalHeight;

  // Compute twinkle opacity
  const twinkleT = (now / dot.twinkleDur + dot.phase) % (2 * Math.PI);
  const opacity = 0.3 + 0.7 * ((Math.sin(twinkleT) + 1) / 2);
  // 0.3 minimum so stars never fully disappear

  // Compute drift offset
  const driftT = (now / dot.driftDur + dot.driftPhase) % (2 * Math.PI);
  const driftX = Math.sin(driftT) * dot.driftDist;
  const driftY = Math.cos(driftT * 0.7) * dot.driftDist * 0.5;

  const cx = dot.x * w + driftX;
  const cy = dot.y * h + driftY;

  // Draw glow (radial gradient, ~7x core radius)
  const gradient = starsCtx.createRadialGradient(cx, cy, 0, cx, cy, dot.glowSize);
  const { core, glow } = STAR_TONES[dot.tone];
  gradient.addColorStop(0,    `rgba(${core[0]},${core[1]},${core[2]}, ${opacity})`);
  gradient.addColorStop(0.07, `rgba(${core[0]},${core[1]},${core[2]}, ${opacity * 0.7})`);
  gradient.addColorStop(0.14, `rgba(${glow[0]},${glow[1]},${glow[2]}, ${opacity * 0.3})`);
  gradient.addColorStop(0.5,  `rgba(${glow[0]},${glow[1]},${glow[2]}, ${opacity * 0.08})`);
  gradient.addColorStop(1,    `rgba(${glow[0]},${glow[1]},${glow[2]}, 0)`);
  // Stop at 0.07 (1/14 ≈ 7%) = core is ~1/7 of total glow radius

  starsCtx.fillStyle = gradient;
  starsCtx.beginPath();
  starsCtx.arc(cx, cy, dot.glowSize, 0, Math.PI * 2);
  starsCtx.fill();
}
```

The core is tiny (the first ~7% of the gradient) and the glow fades to zero before the edge. No hard cutoffs.

### Drawing a flare

```javascript
function drawFlare(flare, now) {
  const w = starsCanvas._logicalWidth;
  const h = starsCanvas._logicalHeight;

  // SAME timing for core and rays — synchronized
  const twinkleT = (now / flare.twinkleDur + flare.phase) % (2 * Math.PI);
  const opacity = 0.2 + 0.8 * ((Math.sin(twinkleT) + 1) / 2);

  const driftT = (now / flare.driftDur + flare.driftPhase) % (2 * Math.PI);
  const driftX = Math.sin(driftT) * flare.driftDist;
  const driftY = Math.cos(driftT * 0.7) * flare.driftDist * 0.5;

  const cx = flare.x * w + driftX;
  const cy = flare.y * h + driftY;

  // Draw rays FIRST (behind the core glow)
  const { core, glow } = STAR_TONES[flare.tone];
  const rayOpacity = opacity * 0.6; // rays slightly dimmer than core

  // Vertical ray
  const vGrad = starsCtx.createLinearGradient(cx, cy - flare.rayV / 2, cx, cy + flare.rayV / 2);
  vGrad.addColorStop(0,   `rgba(${glow[0]},${glow[1]},${glow[2]}, 0)`);
  vGrad.addColorStop(0.4, `rgba(${core[0]},${core[1]},${core[2]}, ${rayOpacity})`);
  vGrad.addColorStop(0.5, `rgba(${core[0]},${core[1]},${core[2]}, ${rayOpacity})`);
  vGrad.addColorStop(0.6, `rgba(${core[0]},${core[1]},${core[2]}, ${rayOpacity})`);
  vGrad.addColorStop(1,   `rgba(${glow[0]},${glow[1]},${glow[2]}, 0)`);
  starsCtx.fillStyle = vGrad;
  starsCtx.fillRect(cx - flare.rayWidth / 2, cy - flare.rayV / 2, flare.rayWidth, flare.rayV);

  // Horizontal ray
  const hGrad = starsCtx.createLinearGradient(cx - flare.rayH / 2, cy, cx + flare.rayH / 2, cy);
  hGrad.addColorStop(0,   `rgba(${glow[0]},${glow[1]},${glow[2]}, 0)`);
  hGrad.addColorStop(0.4, `rgba(${core[0]},${core[1]},${core[2]}, ${rayOpacity})`);
  hGrad.addColorStop(0.5, `rgba(${core[0]},${core[1]},${core[2]}, ${rayOpacity})`);
  hGrad.addColorStop(0.6, `rgba(${core[0]},${core[1]},${core[2]}, ${rayOpacity})`);
  hGrad.addColorStop(1,   `rgba(${glow[0]},${glow[1]},${glow[2]}, 0)`);
  starsCtx.fillStyle = hGrad;
  starsCtx.fillRect(cx - flare.rayH / 2, cy - flare.rayWidth / 2, flare.rayH, flare.rayWidth);

  // Draw core glow (same as dot but larger)
  const coreGrad = starsCtx.createRadialGradient(cx, cy, 0, cx, cy, flare.glowSize);
  coreGrad.addColorStop(0,    `rgba(${core[0]},${core[1]},${core[2]}, ${opacity})`);
  coreGrad.addColorStop(0.07, `rgba(${core[0]},${core[1]},${core[2]}, ${opacity * 0.7})`);
  coreGrad.addColorStop(0.14, `rgba(${glow[0]},${glow[1]},${glow[2]}, ${opacity * 0.3})`);
  coreGrad.addColorStop(0.5,  `rgba(${glow[0]},${glow[1]},${glow[2]}, ${opacity * 0.08})`);
  coreGrad.addColorStop(1,    `rgba(${glow[0]},${glow[1]},${glow[2]}, 0)`);
  starsCtx.fillStyle = coreGrad;
  starsCtx.beginPath();
  starsCtx.arc(cx, cy, flare.glowSize, 0, Math.PI * 2);
  starsCtx.fill();
}
```

### Drawing all stars (called from main loop at ~30fps)

```javascript
function drawStars(now) {
  starsCtx.clearRect(0, 0, starsCanvas.width, starsCanvas.height);
  for (const dot of dots) drawDot(dot, now);
  for (const flare of flares) drawFlare(flare, now);
}
```

### Acceptance criteria

- [ ] Stars appear as soft points of light with no hard halo edges.
- [ ] Core is tiny (point of light), glow extends ~7x outward and fades to nothing.
- [ ] Flare rays pulse in perfect sync with their core — never out of phase.
- [ ] Three tone colors (white, gold, yellow) are visible.
- [ ] No DOM elements for stars exist — everything is canvas.
- [ ] DevTools Performance shows no layout/paint from star animation — only canvas composite.
- [ ] Smooth on low-end devices.

---

## Phase 6 — Exclusion Zone (No Stars Where Senshi Is)

### How to compute the exclusion zone

```javascript
function getExclusionZone() {
  const img = document.querySelector('.smpt-hero-header__image');
  if (!img) return null;
  const headerRect = siteHeader.getBoundingClientRect();
  const imgRect    = img.getBoundingClientRect();

  // Coordinates relative to the header
  const relLeft   = imgRect.left   - headerRect.left;
  const relTop    = imgRect.top    - headerRect.top;
  const relRight  = imgRect.right  - headerRect.left;
  const relBottom = imgRect.bottom - headerRect.top;

  // Middle horizontal third of the image
  const thirdW = (relRight - relLeft) / 3;
  return {
    left:   relLeft + thirdW,
    right:  relLeft + thirdW * 2,
    top:    relTop,
    bottom: relBottom,
  };
}
```

This is recomputed on resize and on init.

### Exclusion check

```javascript
function isInsideExclusion(xFraction, yFraction, zone) {
  if (!zone) return false;
  const w = starsCanvas._logicalWidth;
  const h = starsCanvas._logicalHeight;
  const px = xFraction * w;
  const py = yFraction * h;
  return px >= zone.left && px <= zone.right && py >= zone.top && py <= zone.bottom;
}
```

Used in both `addDot()` and `addFlare()` during initial placement. If inside, re-roll position (max 20 attempts, then accept — edge case safety).

### Debug overlay

Create a `<div>` for visual debugging:

```javascript
const debugBox = document.createElement('div');
debugBox.className = 'smpt-debug-exclusion-zone';
siteHeader.appendChild(debugBox);

function updateDebugBox(zone) {
  if (!zone) return;
  debugBox.style.left   = zone.left + 'px';
  debugBox.style.top    = zone.top + 'px';
  debugBox.style.width  = (zone.right - zone.left) + 'px';
  debugBox.style.height = (zone.bottom - zone.top) + 'px';
}
```

#### CSS for debug box (add to `header.css`)

```css
.smpt-debug-exclusion-zone {
  position: absolute;
  border: 2px solid red;
  background: rgba(255, 0, 0, 0.1);
  pointer-events: none;
  z-index: 9999;
  display: none;
}
body.smpt-debug .smpt-debug-exclusion-zone {
  display: block;
}
```

To activate: add class `smpt-debug` to `<body>` via DevTools, or run `document.body.classList.add('smpt-debug')` in console.

### Acceptance criteria

- [ ] No stars (dots or flares) appear inside the middle third of the senshi image bounds.
- [ ] Adding `smpt-debug` class to body shows a red rectangle matching the exclusion zone.
- [ ] On resize, the debug box and exclusion zone update correctly.

---

## Phase 7 — Flare Star Distribution (Anti-Clustering)

### Grid-based placement

Divide the header into a grid of cells. Place each of the 7 flares into a different cell.

```javascript
function placeFlares(exclusionZone) {
  // 4 columns × 2 rows = 8 cells (7 flares go into 7 of them)
  const cols = 4, rows = 2;
  const cellW = 1 / cols; // as fraction of canvas width
  const cellH = 1 / rows; // as fraction of canvas height

  // Build list of all cells
  const cells = [];
  for (let r = 0; r < rows; r++) {
    for (let c = 0; c < cols; c++) {
      cells.push({ col: c, row: r });
    }
  }

  // Shuffle cells randomly
  for (let i = cells.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [cells[i], cells[j]] = [cells[j], cells[i]];
  }

  // Take first 7 cells
  const chosen = cells.slice(0, 7);
  const placed = []; // track placed positions for minimum distance check

  for (const cell of chosen) {
    let x, y, attempts = 0;
    do {
      // Random position within this cell, with 10% inner padding
      x = (cell.col + 0.1 + Math.random() * 0.8) * cellW;
      y = (cell.row + 0.1 + Math.random() * 0.8) * cellH;
      attempts++;
    } while (
      attempts < 20 &&
      (isInsideExclusion(x, y, exclusionZone) || tooCloseToExisting(x, y, placed))
    );

    placed.push({ x, y });
    // Create flare object with this x, y...
  }
}
```

### Minimum distance check

```javascript
function tooCloseToExisting(x, y, placed) {
  const minDist = 0.12; // 12% of canvas width as minimum distance (fraction)
  for (const p of placed) {
    const dx = x - p.x;
    const dy = y - p.y;
    if (Math.sqrt(dx * dx + dy * dy) < minDist) return true;
  }
  return false;
}
```

### Acceptance criteria

- [ ] 7 flare stars are spread across the header — no two in the same grid cell.
- [ ] No two flares are within 12% of canvas width of each other.
- [ ] Flares respect the exclusion zone.
- [ ] Despite the grid, placement looks organic (randomized within cells).

---

## Phase 8 — Final Visual Cleanup

### What to change in `css/header.css`

#### 8.1 Remove all shadows from senshi image

Find the `.smpt-hero-header__image` rule that has:
```css
filter: drop-shadow(0 16px 30px rgba(8, 14, 32, 0.42))
        drop-shadow(0 0 18px rgba(235, 218, 196, 0.22));
```
**Delete the entire `filter` property** from this rule. Do not replace it.

#### 8.2 Remove all shadows from logo wrapper

Find the `.smpt-header-hero__logo` rule that has:
```css
filter: drop-shadow(0 14px 28px rgba(4, 10, 26, 0.34))
        drop-shadow(0 0 18px rgba(255, 255, 255, 0.22));
```
**Delete the entire `filter` property** from this rule. Do not replace it.

#### 8.3 Change logo outline to 2px

Find the `--smpt-logo-outline` custom property definition. It currently uses `1px` offsets:
```css
--smpt-logo-outline:
  drop-shadow(1px 0 0 white)
  drop-shadow(-1px 0 0 white)
  drop-shadow(0 1px 0 white)
  drop-shadow(0 -1px 0 white);
```
**Change all `1px` to `2px`:**
```css
--smpt-logo-outline:
  drop-shadow(2px 0 0 white)
  drop-shadow(-2px 0 0 white)
  drop-shadow(0 2px 0 white)
  drop-shadow(0 -2px 0 white);
```

#### 8.4 Remove mobile logo glow animation

Find and delete the `@keyframes smpt-logo-glow-reveal` block that adds dramatic drop-shadow glow back to the logo during mobile reveal animation. The `.smpt-logo-reveal` class can keep its opacity/transform animation but must NOT reference a filter/shadow animation.

Also in `hero-header-animation.js`: remove the interval that adds/removes the `smpt-logo-reveal` and `smpt-logo-fadeback` classes, or keep it but ensure the CSS for those classes has no shadow/glow — only opacity transitions.

### Acceptance criteria

- [ ] Senshi image has zero filter/shadow in computed styles.
- [ ] Logo has zero shadow in computed styles (only the outline filter via `--smpt-logo-outline`).
- [ ] Logo outline is visibly 2px white on all sides.
- [ ] Mobile logo reveal animation (if kept) has no glow/shadow — only opacity.

---

## Main rAF Loop Structure

```javascript
let lastNavyUpdate  = 0;
let lastAuroraFrame = 0;
let lastStarsFrame  = 0;

const NAVY_INTERVAL  = 200;  // ms between navy updates (~5fps)
const AURORA_INTERVAL = 50;  // ms between aurora frames (~20fps)
const STARS_INTERVAL  = 33;  // ms between star frames (~30fps)

function mainLoop(timestamp) {
  const now = timestamp / 1000; // seconds for animation math

  if (timestamp - lastNavyUpdate >= NAVY_INTERVAL) {
    updateNavy((timestamp - lastNavyUpdate) / 1000);
    lastNavyUpdate = timestamp;
  }

  if (timestamp - lastAuroraFrame >= AURORA_INTERVAL) {
    updateAuroraBlobs((timestamp - lastAuroraFrame) / 1000);
    drawAurora();
    lastAuroraFrame = timestamp;
  }

  if (timestamp - lastStarsFrame >= STARS_INTERVAL) {
    drawStars(now);
    lastStarsFrame = timestamp;
  }

  requestAnimationFrame(mainLoop);
}

// Respect reduced motion preference
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  requestAnimationFrame(mainLoop);
}
```

---

## Resize Handling

```javascript
function onResize() {
  sizeAuroraCanvas();
  sizeStarsCanvas();
  exclusionZone = getExclusionZone();
  updateDebugBox(exclusionZone);
  // Dots and flares keep their fractional positions — canvas resize handles scaling
}
window.addEventListener('resize', onResize);
```

---

## Build Order

1. **Phase 1** — Linear scaling in CSS. Test resize smoothness.
2. **Phase 2** — Create DOM layers + delete all old pseudo-element and DOM-star CSS. Page will look broken (no animation yet) but layers should be structurally correct.
3. **Phase 3** — Navy gradient JS animation. Verify slow drift works.
4. **Phase 4** — Aurora canvas. Verify blobs appear, drift, fade.
5. **Phase 5** — Stars canvas. Verify dots and flares render with soft glow.
6. **Phase 6** — Exclusion zone. Verify debug box, verify no stars in zone.
7. **Phase 7** — Flare distribution. Verify even spread.
8. **Phase 8** — Visual cleanup. Remove shadows, fix outline.

Each phase should be testable independently before moving to the next.
