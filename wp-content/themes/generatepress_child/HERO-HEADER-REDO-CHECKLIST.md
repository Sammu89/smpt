# Hero Header Redo Checklist

## Goal
Rebuild the hero header behavior cleanly after rollback, with linear scaling, explicit background layers, optimized star rendering, and final visual cleanup.

## 1) Linear Resize (No Cuts) from 1650 to 650
- [ ] Header height scales linearly from `1650px` viewport width down to `650px`.
- [ ] No abrupt jumps between breakpoints in this range.
- [ ] Keep one final mobile behavior switch at `<= 650px` only if needed for layout stacking.
- [ ] Lower image (`Senshi`) also scales linearly from `1650` to `650` with no jumps.
- [ ] Logo scale/position stays continuous in the same range.
- [ ] Logo needs a safe margin left so it doesnt get clipped on smaller viewports.

### Acceptance
- [ ] At widths like `1400`, `1200`, `900`, `700`, transitions are smooth.
- [ ] No visible "snap" around old thresholds (`1100`, `850`, etc.).

## 2) Replace Pseudo-Element Background with Real Layers
- [ ] Remove header pseudo-element background approach (`::before`, `::after`) for the hero background stack.
- [ ] Create explicit full-size header layers:
  - [ ] `hero-background-before`: aurora effect layer.
  - [ ] `hero-background`: main navy background layer.
  - [ ] `hero-background-after`: particles/stars layer.
- [ ] Ensure these layers are real DOM elements sized to the header bounds.

### Acceptance
- [ ] No dependency on pseudo-elements for core hero background rendering.
- [ ] Layer order is predictable and easy to reason about.

## 3) Animate the Background Layer

- [ ] Background base is linear-gradient(5deg, rgb(8, 13, 71) 0%, rgb(8, 13, 71) 60%, rgba(8, 13, 71, 0) 100%)
- [ ] Animate the navy background layer via JS (not CSS-only keyframe spaghetti).
- [ ] We animate degres and the % of mid point. Degress can go from (-15 to + 15), the midpoint from 35 - 80%
- [ ] Speed is independent of the aurora, can vary, but always feel subtle. Sometmes glacial, sometimes faster, its random

### Acceptance
- [ ] Background clearly animates but remains smooth and low-cost.

## 4) Aurora Behavior Spec (Low CPU)
- [ ] Aurora base color is **Mercury blue**. When there is no bob, the background has this color
- [ ] Blobs are ovaloid and mostly horizontal.
- [ ] Blob sizes are random within tuned min/max so visible blobs occupy a good part of canvas.
- [ ] Blob size evolution is random per blob:
  - [ ] some start small and grow while crossing the canvas,
  - [ ] some start big and shrink while crossing,
  - [ ] some keep near-constant size.
- [ ] Each blob can enter from any canvas side and leave from a different random side.
- [ ] Entry/exit points must stay random to avoid repetitive loops.
- [ ] All 9 blobs should appear randomply
- [ ] Most of blobs superpose each other, but there are random free spaces without blobs between transitions
- [ ] Blob lifetime is random between **30s and 50s** before they leave completely the canvas.
- [ ] Keep concurrent animated blobs low (small count active at once) to minimize CPU/GPU cost.

### Palette (Locked)
```text
Mercury (base)  #2678DC
Mars            #D62636
Jupiter         #28AA5F
Venus           #FFB928
Moon            #FFB6C1
ChibiMoon       #FF6EAA
Uranus          #55A0FF
Neptune         #008C96
Pluto           #780F23
Saturn          #5F2882
```

### Acceptance
- [ ] Motion feels random (direction, size, timing) without chaos.
- [ ] At any time, only a few blobs are active.
- [ ] Aurora remains smooth on low-end devices.

## 5) Particle System Refactor (Lower GPU/CPU)
- [ ] Refactor stars to avoid expensive blur-heavy glow stacks.
- [ ] Animate mainly with `opacity` + lightweight transform.
- [ ] When using opacity , Replace "hard edge" look by shaping each star core+glow via gradient sizing:
  - [ ] Glow radius should be ~7x the star core.
  - [ ] Glow alpha must fade to near-zero before the visual edge (soft falloff).
- [ ] Keep flare stars synchronized:
  - [ ] Core opacity pulse and flare rays pulse in sync.
  - [ ] Same timing function/phase for each flare star set.
  - [ ] Keep the center of the stars small (point of light, not a full visible circle)

### Acceptance
- [ ] Particle animation is visibly lighter/smoother on low-end devices.
- [ ] No harsh halo cutoffs.

## 6) Exclusion Zone from Center Image
- [ ] Compute exclusion zone dynamically from the senshi image bounds (`hero image`).
- [ ] Use the middle horizontal third of that image as a "no-star" region.
- [ ] No stars should spawn inside this exclusion zone.
- [ ] Add debug mode:
  - [ ] Show a red debug rectangle for exclusion zone visualization via css

### Acceptance
- [ ] Debug box matches intended zone.
- [ ] Stars consistently avoid this zone during spawn and refresh.

## 7) Flare Star Distribution Rules
- [ ] Flare stars must be evenly distributed (anti-cluster logic).
- [ ] Flares must also respect exclusion zone.
- [ ] Add minimum distance between flare stars.

### Acceptance
- [ ] Flares are visually balanced across the header.
- [ ] No flare pile-ups.

## 8) Final Visual Cleanup
- [ ] Remove all shadows from Senshi image.
- [ ] Remove all shadows from logo.
- [ ] Keep only a `2px` white outline on logo.

### Acceptance
- [ ] Senshi has no glow/shadow.
- [ ] Logo has only the 2px white outline, no extra shadow.

---

## Suggested Build Order
1. Linear scaling first (header + images).
2. Layer architecture (Background Before / Background / Background After).
3. Background animation.
4. Aurora behavior implementation (Mercury base + random oval blobs).
5. Particle refactor + flare sync.
6. Exclusion zone + debug box.
7. Flare spacing logic.
8. Final visual cleanup.
