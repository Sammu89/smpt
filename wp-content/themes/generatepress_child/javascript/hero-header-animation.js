/* Deterministic daily RNG keyed from the site date emitted by WordPress. */
(function () {
  function hashString(input) {
    var hash = 2166136261;

    for (var i = 0; i < input.length; i++) {
      hash ^= input.charCodeAt(i);
      hash = Math.imul(hash, 16777619);
    }

    return hash >>> 0;
  }

  function createMulberry32(seed) {
    return function () {
      var t = seed += 0x6D2B79F5;
      t = Math.imul(t ^ (t >>> 15), t | 1);
      t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }

  window.smptCreateDailyRandom = function (namespace) {
    var daySeed = String(window.smptHeaderSeed || "smpt-default-day");
    return createMulberry32(hashString(daySeed + ":" + String(namespace || "default")));
  };
})();

/* ── Shared animation coordination ──
   Single source of truth for visibility, reduced-motion, and CPU capability.
   All animation subsystems check these before doing work. */
(function () {
  var state = window.smptAnim = {
    visible: true,
    lowCPU: false,
    reducedMotion: window.matchMedia("(prefers-reduced-motion: reduce)").matches
  };

  function setHeaderVisibility(isVisible) {
    if (state.visible === isVisible) {
      return;
    }
    state.visible = isVisible;
    window.dispatchEvent(new CustomEvent("smpt:header-visibility", {
      detail: { visible: isVisible }
    }));
  }

  /* Proactive low-CPU heuristic */
  var cores = navigator.hardwareConcurrency || 4;
  var mem   = navigator.deviceMemory || 4; /* Chrome/Edge only; others get 4 */
  state.lowCPU = cores <= 2 || mem <= 2;

  document.addEventListener("DOMContentLoaded", function () {
    var header = document.querySelector(".site-header");
    if (!header) return;

    /* Pause ALL animations when the header scrolls out of view */
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) {
        setHeaderVisibility(entries[0].isIntersecting);
      }, { threshold: 0 }).observe(header);
    }
  });
})();

/* ── Logo reveal: every 2 min, bring logo in front of senshi for 10 s ── */
document.addEventListener("DOMContentLoaded", function () {
  var header = document.querySelector(".site-header");
  if (!header) return;

  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  var logo = document.querySelector(".smpt-header-hero__logo");

  if (logo) {
    var INTERVAL = 120000;
    var mql = window.matchMedia("(max-width: 650px)");

    function onFadebackEnd() {
      logo.classList.remove("smpt-logo-fadeback");
      logo.removeEventListener("animationend", onFadebackEnd);
    }

    function onRevealEnd() {
      logo.removeEventListener("animationend", onRevealEnd);
      logo.classList.remove("smpt-logo-reveal");
      logo.classList.add("smpt-logo-fadeback");
      logo.addEventListener("animationend", onFadebackEnd);
    }

    function revealLogo() {
      if (reducedMotion.matches || !mql.matches || logo.classList.contains("smpt-logo-reveal") || logo.classList.contains("smpt-logo-fadeback")) {
        return;
      }
      logo.addEventListener("animationend", onRevealEnd);
      logo.classList.add("smpt-logo-reveal");
    }

    setInterval(revealLogo, INTERVAL);
  }
});


/* ===== Appended Phase 5 + 6 Block ===== */

/*
 * Phase 5 + 6 extension block
 * Canvas stars + exclusion zone (kept visually close to previous CSS star look).
 */
document.addEventListener("DOMContentLoaded", function () {
  var header = document.querySelector(".site-header");
  if (!header) {
    return;
  }

  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  var starsCanvas = header.querySelector(".smpt-stars-canvas");
  var starsRandom = window.smptCreateDailyRandom("stars");

  if (!starsCanvas) {
    starsCanvas = document.createElement("canvas");
    starsCanvas.className = "smpt-stars-canvas";
    starsCanvas.setAttribute("aria-hidden", "true");
    header.prepend(starsCanvas);
  }

  /* Remove legacy DOM sky if present (Phase 1 no longer creates it) */
  var legacySky = header.querySelector(".smpt-header-hero__sky");
  if (legacySky) legacySky.remove();

  var starsCtx = starsCanvas.getContext("2d");
  var starsScale = 1;
  var dots = [];
  var flares = [];
  var exclusionZone = null;

  var STAR_TONES = {
    white: {
      core: [232, 243, 255],
      glow: [176, 212, 255],
      halo: [111, 173, 255]
    },
    gold: {
      core: [247, 221, 156],
      glow: [255, 232, 183],
      halo: [246, 201, 92]
    },
    yellow: {
      core: [255, 238, 134],
      glow: [255, 246, 190],
      halo: [255, 224, 86]
    }
  };

  var DOT_COUNT = 58;
  var FLARE_COUNT = 7;
  var PI2 = Math.PI * 2;

  var debugBox = header.querySelector(".smpt-debug-exclusion-zone");
  if (!debugBox) {
    debugBox = document.createElement("div");
    debugBox.className = "smpt-debug-exclusion-zone";
    header.appendChild(debugBox);
  }

  function randomBetween(min, max) {
    return min + starsRandom() * (max - min);
  }

  function getStarsScale(headerWidth) {
    return Math.max(0.4, Math.min(1, (headerWidth - 320) / (1650 - 320)));
  }

  function updateStarsScale() {
    var width = header.getBoundingClientRect().width;
    starsScale = getStarsScale(width);
    starsCanvas.style.setProperty("--smpt-stars-scale", starsScale.toFixed(4));
  }

  function sizeStarsCanvas() {
    var rect = header.getBoundingClientRect();
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var width = Math.max(1, Math.round(rect.width));
    var height = Math.max(1, Math.round(rect.height));

    starsCanvas.width = Math.round(width * dpr);
    starsCanvas.height = Math.round(height * dpr);
    starsCtx.setTransform(dpr, 0, 0, dpr, 0, 0);

    starsCanvas._logicalWidth = width;
    starsCanvas._logicalHeight = height;
  }

  function getExclusionZone() {
    var imageWrap = document.querySelector(".smpt-hero-header__image");
    if (!imageWrap) {
      return null;
    }

    var headerRect = header.getBoundingClientRect();
    var imgRect = imageWrap.getBoundingClientRect();

    var relLeft = imgRect.left - headerRect.left;
    var relTop = imgRect.top - headerRect.top;
    var relRight = imgRect.right - headerRect.left;
    var relBottom = imgRect.bottom - headerRect.top;

    var thirdH = (relBottom - relTop) / 3;
    var yOffset = -50;

    return {
      left: relLeft,
      right: relRight,
      top: relTop + thirdH + yOffset,
      bottom: relTop + thirdH * 2 + yOffset
    };
  }

  function updateDebugBox(zone) {
    if (!zone) {
      debugBox.style.width = "0px";
      debugBox.style.height = "0px";
      return;
    }

    debugBox.style.left = zone.left + "px";
    debugBox.style.top = zone.top + "px";
    debugBox.style.width = Math.max(0, zone.right - zone.left) + "px";
    debugBox.style.height = Math.max(0, zone.bottom - zone.top) + "px";
  }

  function isInsideExclusion(xFraction, yFraction, zone) {
    if (!zone) {
      return false;
    }

    var w = starsCanvas._logicalWidth || 1;
    var h = starsCanvas._logicalHeight || 1;
    var px = xFraction * w;
    var py = yFraction * h;

    return px >= zone.left && px <= zone.right && py >= zone.top && py <= zone.bottom;
  }

  function tooCloseToExisting(x, y, placed) {
    var minDist = 0.12;
    for (var i = 0; i < placed.length; i++) {
      var dx = x - placed[i].x;
      var dy = y - placed[i].y;
      if (Math.sqrt(dx * dx + dy * dy) < minDist) {
        return true;
      }
    }
    return false;
  }

  function randomTone() {
    var tones = ["white", "gold", "yellow"];
    return tones[Math.floor(starsRandom() * tones.length)];
  }

  function weightedTopY() {
    var t = Math.pow(starsRandom(), 1.35);
    return 0.05 + t * 0.58;
  }

  function addDot(zone) {
    var x = randomBetween(0.02, 0.98);
    var y = weightedTopY();

    for (var attempt = 0; attempt < 20; attempt++) {
      var nextX = randomBetween(0.02, 0.98);
      var nextY = weightedTopY();
      if (!isInsideExclusion(nextX, nextY, zone)) {
        x = nextX;
        y = nextY;
        break;
      }
    }

    var size = randomBetween(1.1, 3.2);

    dots.push({
      x: x,
      y: y,
      size: size,
      glowSize: size * 7,
      tone: randomTone(),
      phase: randomBetween(0, PI2),
      twinkleDur: randomBetween(1.8, 3.6),
      driftDur: randomBetween(5.4, 8.6),
      driftDist: randomBetween(2, 7),
      driftPhase: randomBetween(0, PI2)
    });
  }

  function placeFlares(zone) {
    var cols = 4;
    var rows = 2;
    var yMin = 0.08;
    var yMax = 0.66;
    var ySpan = yMax - yMin;

    var cells = [];
    for (var r = 0; r < rows; r++) {
      for (var c = 0; c < cols; c++) {
        cells.push({ col: c, row: r });
      }
    }

    for (var i = cells.length - 1; i > 0; i--) {
      var j = Math.floor(starsRandom() * (i + 1));
      var tmp = cells[i];
      cells[i] = cells[j];
      cells[j] = tmp;
    }

    var chosen = cells.slice(0, FLARE_COUNT);
    var placed = [];

    for (var k = 0; k < chosen.length; k++) {
      var cell = chosen[k];
      var x = 0.5;
      var y = 0.3;

      for (var attempt = 0; attempt < 20; attempt++) {
        var xCell = (cell.col + 0.1 + starsRandom() * 0.8) / cols;
        var yCell = yMin + ((cell.row + 0.1 + starsRandom() * 0.8) / rows) * ySpan;

        if (isInsideExclusion(xCell, yCell, zone) || tooCloseToExisting(xCell, yCell, placed)) {
          continue;
        }

        x = xCell;
        y = yCell;
        break;
      }

      placed.push({ x: x, y: y });

      var coreSize = randomBetween(3, 5);

      flares.push({
        x: x,
        y: y,
        coreSize: coreSize,
        glowSize: coreSize * 7,
        rayV: randomBetween(48, 76),
        rayH: randomBetween(38, 62),
        rayWidth: 1.5,
        tone: starsRandom() < 0.65 ? "gold" : "white",
        phase: randomBetween(0, PI2),
        twinkleDur: randomBetween(2.4, 4.2),
        driftDur: randomBetween(6.2, 9.5),
        driftDist: randomBetween(3, 8),
        driftPhase: randomBetween(0, PI2)
      });
    }
  }

  function initStars() {
    dots = [];
    flares = [];

    for (var i = 0; i < DOT_COUNT; i++) {
      addDot(exclusionZone);
    }

    placeFlares(exclusionZone);
  }

  function drawDot(dot, nowSeconds) {
    var w = starsCanvas._logicalWidth;
    var h = starsCanvas._logicalHeight;
    if (!w || !h) {
      return;
    }

    var twinkleT = (nowSeconds / dot.twinkleDur) * PI2 + dot.phase;
    var opacity = 0.3 + 0.7 * ((Math.sin(twinkleT) + 1) / 2);

    var driftT = (nowSeconds / dot.driftDur) * PI2 + dot.driftPhase;
    var driftDist = dot.driftDist * starsScale;
    var driftX = Math.sin(driftT) * driftDist;
    var driftY = Math.cos(driftT * 0.7) * driftDist * 0.5;

    var cx = dot.x * w + driftX;
    var cy = dot.y * h + driftY;

    var tone = STAR_TONES[dot.tone];
    var core = tone.core;
    var glow = tone.glow;
    var halo = tone.halo;

    var glowSize = dot.glowSize * starsScale;
    var gradient = starsCtx.createRadialGradient(cx, cy, 0, cx, cy, glowSize);

    gradient.addColorStop(0, "rgba(" + core[0] + "," + core[1] + "," + core[2] + "," + opacity + ")");
    gradient.addColorStop(0.07, "rgba(" + core[0] + "," + core[1] + "," + core[2] + "," + (opacity * 0.72) + ")");
    gradient.addColorStop(0.16, "rgba(" + glow[0] + "," + glow[1] + "," + glow[2] + "," + (opacity * 0.35) + ")");
    gradient.addColorStop(0.52, "rgba(" + halo[0] + "," + halo[1] + "," + halo[2] + "," + (opacity * 0.1) + ")");
    gradient.addColorStop(1, "rgba(" + halo[0] + "," + halo[1] + "," + halo[2] + ",0)");

    starsCtx.fillStyle = gradient;
    starsCtx.beginPath();
    starsCtx.arc(cx, cy, glowSize, 0, PI2);
    starsCtx.fill();
  }

  function drawFlare(flare, nowSeconds) {
    var w = starsCanvas._logicalWidth;
    var h = starsCanvas._logicalHeight;
    if (!w || !h) {
      return;
    }

    var twinkleT = (nowSeconds / flare.twinkleDur) * PI2 + flare.phase;
    var opacity = 0.22 + 0.78 * ((Math.sin(twinkleT) + 1) / 2);

    var driftT = (nowSeconds / flare.driftDur) * PI2 + flare.driftPhase;
    var driftDist = flare.driftDist * starsScale;
    var driftX = Math.sin(driftT) * driftDist;
    var driftY = Math.cos(driftT * 0.7) * driftDist * 0.5;

    var cx = flare.x * w + driftX;
    var cy = flare.y * h + driftY;

    var tone = STAR_TONES[flare.tone];
    var core = tone.core;
    var glow = tone.glow;
    var halo = tone.halo;

    var rayV = flare.rayV * starsScale;
    var rayH = flare.rayH * starsScale;
    var rayWidth = Math.max(1, flare.rayWidth * starsScale);
    var rayOpacity = opacity * 0.62;

    var vGrad = starsCtx.createLinearGradient(cx, cy - rayV / 2, cx, cy + rayV / 2);
    vGrad.addColorStop(0, "rgba(" + halo[0] + "," + halo[1] + "," + halo[2] + ",0)");
    vGrad.addColorStop(0.42, "rgba(" + core[0] + "," + core[1] + "," + core[2] + "," + rayOpacity + ")");
    vGrad.addColorStop(0.58, "rgba(" + core[0] + "," + core[1] + "," + core[2] + "," + rayOpacity + ")");
    vGrad.addColorStop(1, "rgba(" + halo[0] + "," + halo[1] + "," + halo[2] + ",0)");
    starsCtx.fillStyle = vGrad;
    starsCtx.fillRect(cx - rayWidth / 2, cy - rayV / 2, rayWidth, rayV);

    var hGrad = starsCtx.createLinearGradient(cx - rayH / 2, cy, cx + rayH / 2, cy);
    hGrad.addColorStop(0, "rgba(" + halo[0] + "," + halo[1] + "," + halo[2] + ",0)");
    hGrad.addColorStop(0.42, "rgba(" + core[0] + "," + core[1] + "," + core[2] + "," + rayOpacity + ")");
    hGrad.addColorStop(0.58, "rgba(" + core[0] + "," + core[1] + "," + core[2] + "," + rayOpacity + ")");
    hGrad.addColorStop(1, "rgba(" + halo[0] + "," + halo[1] + "," + halo[2] + ",0)");
    starsCtx.fillStyle = hGrad;
    starsCtx.fillRect(cx - rayH / 2, cy - rayWidth / 2, rayH, rayWidth);

    var glowSize = flare.glowSize * starsScale;
    var coreGrad = starsCtx.createRadialGradient(cx, cy, 0, cx, cy, glowSize);
    coreGrad.addColorStop(0, "rgba(" + core[0] + "," + core[1] + "," + core[2] + "," + opacity + ")");
    coreGrad.addColorStop(0.07, "rgba(" + core[0] + "," + core[1] + "," + core[2] + "," + (opacity * 0.74) + ")");
    coreGrad.addColorStop(0.16, "rgba(" + glow[0] + "," + glow[1] + "," + glow[2] + "," + (opacity * 0.32) + ")");
    coreGrad.addColorStop(0.52, "rgba(" + halo[0] + "," + halo[1] + "," + halo[2] + "," + (opacity * 0.1) + ")");
    coreGrad.addColorStop(1, "rgba(" + halo[0] + "," + halo[1] + "," + halo[2] + ",0)");

    starsCtx.fillStyle = coreGrad;
    starsCtx.beginPath();
    starsCtx.arc(cx, cy, glowSize, 0, PI2);
    starsCtx.fill();
  }

  function drawStars(nowSeconds) {
    var w = starsCanvas._logicalWidth;
    var h = starsCanvas._logicalHeight;
    if (!w || !h) {
      return;
    }

    starsCtx.clearRect(0, 0, w, h);

    for (var i = 0; i < dots.length; i++) {
      drawDot(dots[i], nowSeconds);
    }

    for (var j = 0; j < flares.length; j++) {
      drawFlare(flares[j], nowSeconds);
    }
  }

  function onResize() {
    sizeStarsCanvas();
    updateStarsScale();
    exclusionZone = getExclusionZone();
    updateDebugBox(exclusionZone);
    initStars();
    drawStars(performance.now() / 1000);
  }

  onResize();
  window.addEventListener("resize", onResize);

  /* Reduced-motion OR low-CPU: draw stars once (static), no animation loop */
  if (reducedMotion.matches || window.smptAnim.lowCPU) {
    drawStars(performance.now() / 1000);
    return;
  }

  var lastStarsFrame = 0;
  var STARS_INTERVAL = 33;
  var starsRafId = 0;

  function stopStarsLoop() {
    if (starsRafId) {
      cancelAnimationFrame(starsRafId);
      starsRafId = 0;
    }
  }

  function startStarsLoop() {
    if (!window.smptAnim.visible || starsRafId) {
      return;
    }
    lastStarsFrame = 0;
    starsRafId = requestAnimationFrame(starsLoop);
  }

  function starsLoop(timestamp) {
    if (!lastStarsFrame || timestamp - lastStarsFrame >= STARS_INTERVAL) {
      drawStars(timestamp / 1000);
      lastStarsFrame = timestamp;
    }
    starsRafId = requestAnimationFrame(starsLoop);
  }

  window.addEventListener("smpt:header-visibility", function (event) {
    if (event.detail.visible) {
      startStarsLoop();
    } else {
      stopStarsLoop();
    }
  });

  startStarsLoop();
});

/* ── Phase 3: Navy gradient layer + slow animation ── */
document.addEventListener("DOMContentLoaded", function () {
  var header = document.querySelector(".site-header");
  if (!header) return;

  /* Create aurora base layer (Mercury blue via CSS) */
  var auroraLayer = header.querySelector(".smpt-aurora-layer");
  if (!auroraLayer) {
    auroraLayer = document.createElement("div");
    auroraLayer.className = "smpt-aurora-layer";
    auroraLayer.setAttribute("aria-hidden", "true");
    header.prepend(auroraLayer);
  }

  /* Create navy gradient layer */
  var navyLayer = header.querySelector(".smpt-hero-background");
  if (!navyLayer) {
    navyLayer = document.createElement("div");
    navyLayer.className = "smpt-hero-background";
    navyLayer.setAttribute("aria-hidden", "true");
    header.prepend(navyLayer);
  }

  function randomBetween(min, max) {
    return min + Math.random() * (max - min);
  }

  /* State for angle and midpoint — independent targets and durations */
  var navy = {
    angle:    { current: randomBetween(-15, 15), start: 0, target: 0, duration: 0, elapsed: 0 },
    midpoint: { current: randomBetween(25, 60),  start: 0, target: 0, duration: 0, elapsed: 0 }
  };

  function pickTarget(prop, min, max) {
    prop.start    = prop.current;
    prop.target   = randomBetween(min, max);
    prop.duration = 180 + Math.random() * 300; /* 3–8 minutes per cycle */
    prop.elapsed  = 0;
  }

  pickTarget(navy.angle,    -15, 15);
  pickTarget(navy.midpoint,  25, 60);

  /* Apply starting values immediately */
  navyLayer.style.setProperty("--navy-angle", navy.angle.current.toFixed(2) + "deg");
  navyLayer.style.setProperty("--navy-mid",   navy.midpoint.current.toFixed(1) + "%");

  var lastNavyUpdate = 0;
  var NAVY_INTERVAL  = 200; /* ~5 fps is plenty for this slow drift */
  var navyRafId = 0;

  function updateNavy(dtSeconds) {
    var defs = [
      { prop: navy.angle,    min: -15, max: 15 },
      { prop: navy.midpoint, min:  25, max: 60 }
    ];
    for (var i = 0; i < defs.length; i++) {
      var p = defs[i].prop;
      p.elapsed += dtSeconds;
      var t = Math.min(p.elapsed / p.duration, 1);
      /* Ease-in-out quadratic */
      var e = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
      p.current = p.start + (p.target - p.start) * e;
      if (t >= 1) pickTarget(p, defs[i].min, defs[i].max);
    }
    navyLayer.style.setProperty("--navy-angle", navy.angle.current.toFixed(2) + "deg");
    navyLayer.style.setProperty("--navy-mid",   navy.midpoint.current.toFixed(1) + "%");
  }

  function stopNavyLoop() {
    if (navyRafId) {
      cancelAnimationFrame(navyRafId);
      navyRafId = 0;
    }
  }

  function startNavyLoop() {
    if (!window.smptAnim.visible || navyRafId) {
      return;
    }
    lastNavyUpdate = 0;
    navyRafId = requestAnimationFrame(navyLoop);
  }

  function navyLoop(timestamp) {
    if (timestamp - lastNavyUpdate >= NAVY_INTERVAL) {
      updateNavy((timestamp - lastNavyUpdate) / 1000);
      lastNavyUpdate = timestamp;
    }
    navyRafId = requestAnimationFrame(navyLoop);
  }

  if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    window.addEventListener("smpt:header-visibility", function (event) {
      if (event.detail.visible) {
        startNavyLoop();
      } else {
        stopNavyLoop();
      }
    });
    startNavyLoop();
  }
});

/* ── Phase 4: Aurora Borealis (low-res aurora.txt core) ── */
document.addEventListener("DOMContentLoaded", function () {
  var header = document.querySelector(".site-header");
  if (!header) return;
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

  var auroraLayer = header.querySelector(".smpt-aurora-layer");
  if (!auroraLayer) return;

  /* Low-CPU devices: skip aurora entirely (biggest perf cost) */
  if (window.smptAnim.lowCPU) return;

  var canvas = auroraLayer.querySelector(".smpt-aurora-canvas");
  if (!canvas) {
    canvas = document.createElement("canvas");
    canvas.className = "smpt-aurora-canvas";
    canvas.style.cssText =
      "display:block;width:100%;height:100%;position:absolute;inset:0;" +
      "opacity:0;transition:opacity 0.8s ease;image-rendering:auto;";
    auroraLayer.appendChild(canvas);
  }

  var ctx = canvas.getContext("2d", { willReadFrequently: true });

  /* Render at ~28 % resolution — CSS stretches to full size.
     Bilinear upscale acts as a free gaussian blur = natural aurora softness.
     A 1200×500 header becomes ~336×140 = 47 k pixels instead of 600 k. */
  var RENDER_SCALE = 0.35;

  function rand(min, max) {
    return min + Math.random() * (max - min);
  }

  function mix(a, b, t) {
    return {
      r: Math.round(a.r + (b.r - a.r) * t),
      g: Math.round(a.g + (b.g - a.g) * t),
      b: Math.round(a.b + (b.b - a.b) * t)
    };
  }

  function clamp01(v) {
    return v < 0 ? 0 : (v > 1 ? 1 : v);
  }

  function smoothstep01(v) {
    v = clamp01(v);
    return v * v * (3 - 2 * v);
  }

  function rgba(c, a) {
    return "rgba(" + c.r + "," + c.g + "," + c.b + "," + a + ")";
  }

  /* ── Simplex 3-D noise (aurora.txt family) ── */
  function SimplexNoise3D() {
    this.grad3 = [
      [1, 1, 0], [-1, 1, 0], [1, -1, 0], [-1, -1, 0],
      [1, 0, 1], [-1, 0, 1], [1, 0, -1], [-1, 0, -1],
      [0, 1, 1], [0, -1, 1], [0, 1, -1], [0, -1, -1]
    ];
    this.p = [];
    for (var i = 0; i < 256; i++) this.p[i] = Math.floor(Math.random() * 256);
    this.perm = [];
    for (var j = 0; j < 512; j++) this.perm[j] = this.p[j & 255];
  }

  SimplexNoise3D.prototype.dot3 = function (g, x, y, z) {
    return g[0] * x + g[1] * y + g[2] * z;
  };

  SimplexNoise3D.prototype.noise3d = function (xin, yin, zin) {
    var n0, n1, n2, n3;
    var F3 = 1 / 3;
    var s = (xin + yin + zin) * F3;
    var i = Math.floor(xin + s);
    var j = Math.floor(yin + s);
    var k = Math.floor(zin + s);

    var G3 = 1 / 6;
    var t = (i + j + k) * G3;
    var X0 = i - t, Y0 = j - t, Z0 = k - t;
    var x0 = xin - X0, y0 = yin - Y0, z0 = zin - Z0;

    var i1, j1, k1, i2, j2, k2;
    if (x0 >= y0) {
      if (y0 >= z0)      { i1=1;j1=0;k1=0;i2=1;j2=1;k2=0; }
      else if (x0 >= z0) { i1=1;j1=0;k1=0;i2=1;j2=0;k2=1; }
      else               { i1=0;j1=0;k1=1;i2=1;j2=0;k2=1; }
    } else {
      if (y0 < z0)       { i1=0;j1=0;k1=1;i2=0;j2=1;k2=1; }
      else if (x0 < z0)  { i1=0;j1=1;k1=0;i2=0;j2=1;k2=1; }
      else               { i1=0;j1=1;k1=0;i2=1;j2=1;k2=0; }
    }

    var x1 = x0 - i1 + G3, y1 = y0 - j1 + G3, z1 = z0 - k1 + G3;
    var x2 = x0 - i2 + 2*G3, y2 = y0 - j2 + 2*G3, z2 = z0 - k2 + 2*G3;
    var x3 = x0 - 1 + 3*G3, y3 = y0 - 1 + 3*G3, z3 = z0 - 1 + 3*G3;

    var ii = i & 255, jj = j & 255, kk = k & 255;
    var pm = this.perm, g = this.grad3;
    var gi0 = pm[ii    + pm[jj    + pm[kk   ]]] % 12;
    var gi1 = pm[ii+i1 + pm[jj+j1 + pm[kk+k1]]] % 12;
    var gi2 = pm[ii+i2 + pm[jj+j2 + pm[kk+k2]]] % 12;
    var gi3 = pm[ii+1  + pm[jj+1  + pm[kk+1 ]]] % 12;

    var t0 = 0.6 - x0*x0 - y0*y0 - z0*z0;
    n0 = t0 < 0 ? 0 : (t0 *= t0, t0 * t0 * this.dot3(g[gi0], x0, y0, z0));
    var t1 = 0.6 - x1*x1 - y1*y1 - z1*z1;
    n1 = t1 < 0 ? 0 : (t1 *= t1, t1 * t1 * this.dot3(g[gi1], x1, y1, z1));
    var t2 = 0.6 - x2*x2 - y2*y2 - z2*z2;
    n2 = t2 < 0 ? 0 : (t2 *= t2, t2 * t2 * this.dot3(g[gi2], x2, y2, z2));
    var t3 = 0.6 - x3*x3 - y3*y3 - z3*z3;
    n3 = t3 < 0 ? 0 : (t3 *= t3, t3 * t3 * this.dot3(g[gi3], x3, y3, z3));

    return 32 * (n0 + n1 + n2 + n3);
  };

  var simplex = new SimplexNoise3D();
  var auroraDebug = window.smptAuroraDebug = window.smptAuroraDebug || { forceColorCount: 0 };

  /* ── Palette ── */
  var SENSHI_PALETTE = [
    { r: 214, g: 38,  b: 54  },
    { r: 40,  g: 170, b: 95  },
    { r: 255, g: 185, b: 40  },
    { r: 255, g: 182, b: 193 },
    { r: 255, g: 110, b: 170 },
    { r: 85,  g: 160, b: 255 },
    { r: 0,   g: 140, b: 150 },
    { r: 120, g: 15,  b: 35  },
    { r: 95,  g: 40,  b: 130 }
  ];

  /* Derive the 5 gradient stops from a base color (aurora.txt structure) */
  function deriveStops(base) {
    return {
      c0: mix(base, { r: 255, g: 255, b: 255 }, 0.28),
      c1: mix(base, { r: 255, g: 255, b: 255 }, 0.12),
      c2: mix(base, { r: 255, g: 255, b: 255 }, 0.35),
      c3: mix(base, { r: 0, g: 0, b: 0 }, 0.10),
      c4: mix(base, { r: 0, g: 0, b: 0 }, 0.20)
    };
  }

  /* Pick 1 / 2 / 3 colour families.
     70 % single · 20 % dual · 10 % triple */
  function pickColors() {
    var forcedCount = auroraDebug.forceColorCount | 0;
    var roll = Math.random();
    var count = forcedCount >= 1 && forcedCount <= 3 ? forcedCount : (roll < 0.70 ? 1 : (roll < 0.90 ? 2 : 3));
    var families = [];
    var used = [];
    for (var i = 0; i < count; i++) {
      var idx;
      do { idx = Math.floor(Math.random() * SENSHI_PALETTE.length); } while (used.indexOf(idx) !== -1);
      used.push(idx);
      families.push(deriveStops(SENSHI_PALETTE[idx]));
    }
    return { families: families };
  }

  function createColorCone(familyIndex, xMin, xMax) {
    return {
      familyIndex: familyIndex,
      apexX: rand(xMin, xMax),
      apexY: rand(-0.22, -0.06),
      baseWidth: rand(0.015, 0.04),
      endWidth: rand(0.18, 0.34),
      height: rand(1.05, 1.35),
      lean: rand(-0.10, 0.10),
      sway: rand(-0.08, 0.08),
      driftX: rand(-0.025, 0.025),
      widthCurve: rand(0.85, 1.20),
      phase: rand(0, Math.PI * 2),
      waveAmp: rand(0.015, 0.05),
      waveFreq: rand(3.0, 6.0),
      waveSpeed: rand(0.45, 0.85),
      warpAmp: rand(0.010, 0.035),
      rayFloor: rand(0.15, 0.30)
    };
  }

  function buildColorCones(familyCount) {
    var cones = [];
    if (familyCount < 2) {
      return cones;
    }
    if (familyCount === 2) {
      cones.push(createColorCone(1, 0.36, 0.64));
      return cones;
    }

    if (Math.random() < 0.5) {
      cones.push(createColorCone(1, 0.18, 0.38));
      cones.push(createColorCone(2, 0.62, 0.82));
    } else {
      cones.push(createColorCone(1, 0.62, 0.82));
      cones.push(createColorCone(2, 0.18, 0.38));
    }
    return cones;
  }

  function coneMaskAt(cone, xn, yn, factor) {
    var depth = (yn - cone.apexY) / cone.height;
    if (depth <= 0 || depth >= 1.1) {
      return 0;
    }
    var depthClamped = clamp01(depth);
    var centerX = cone.apexX + cone.centerShift + depthClamped * cone.lean + Math.sin(depthClamped * cone.waveFreq + cone.wavePhase) * cone.waveAmp * depthClamped;
    centerX += (factor - 0.5) * 2 * cone.warpAmp * (0.25 + depthClamped * 0.75);
    var halfWidth = cone.baseWidth + (cone.endWidth - cone.baseWidth) * Math.pow(depthClamped, cone.widthCurve);
    halfWidth *= 0.92 + factor * 0.22;
    var dx = Math.abs(xn - centerX);
    if (dx >= halfWidth) {
      return 0;
    }
    var inner = halfWidth * 0.55;
    var sideMask = dx <= inner ? 1 : 1 - smoothstep01((dx - inner) / Math.max(halfWidth - inner, 0.0001));
    var topFade = smoothstep01(depthClamped / 0.18);
    var rayMask = smoothstep01((factor - cone.rayFloor) / Math.max(1 - cone.rayFloor, 0.0001));
    return sideMask * topFade * rayMask;
  }

  /* ── State ── */
  var auroraPhase = "active";
  var phaseStart  = 0;
  var aurora      = null;
  var lastFrame   = 0;
  var FRAME_INTERVAL = 55;              /* ~18 fps */

  function initAurora() {
    var cc = pickColors();
    var sX = rand(6, 14);  /* horizontal freq — 6 = ~3 wide rays, 14 = ~7 thinner rays */
    aurora = {
      start:    performance.now(),
      duration: rand(25000, 50000),
      families: cc.families,
      cones:    buildColorCones(cc.families.length),
      scaleX:   sX,
      scaleY:   sX / rand(14, 22),       /* ratio 14:1 – 22:1 → vertical elongation = rays */
      seed:     rand(0, Math.PI * 2)
    };
  }

  function sizeCanvas() {
    var rect = header.getBoundingClientRect();
    canvas.width  = Math.max(1, Math.round(rect.width  * RENDER_SCALE));
    canvas.height = Math.max(1, Math.round(rect.height * RENDER_SCALE));
  }

  /* Fade envelope: 12 % fade-in · sustain · 18 % fade-out */
  function envelope(p) {
    if (p < 0.12) return p / 0.12;
    if (p > 0.82) return (1 - p) / 0.18;
    return 1;
  }

  /* ── Per-frame render (aurora.txt core at reduced resolution) ── */
  function renderFrame(now) {
    var w = canvas.width;
    var h = canvas.height;

    /* Aurora finished → stop loop, schedule next via setTimeout */
    if (!aurora) return;
    var progress = Math.min((now - phaseStart) / aurora.duration, 1);
    if (progress >= 1) {
      scheduleNextAurora();
      return;
    }

    var env = Math.max(0, envelope(progress));
    canvas.style.opacity = String(env);
    if (env < 0.01) return;

    var time     = now / 4000;
    var families = aurora.families;
    var multi    = families.length > 1;
    var primary  = families[0];

    /* ── 1) Diagonal colour gradient (aurora.txt structure) ── */
    ctx.clearRect(0, 0, w, h);

    var gradient = ctx.createLinearGradient(0, 0, h / 0.4, h * 0.9);
    gradient.addColorStop(0, rgba(primary.c0, 1.0));
    gradient.addColorStop(
      (Math.sin(time + aurora.seed) + 1) * 0.5 * 0.2,
      rgba(primary.c1, 0.3)
    );
    gradient.addColorStop(
      (Math.cos(time + aurora.seed) + 1) * 0.5 * 0.2 + 0.444,
      rgba(primary.c2, 0.6)
    );
    gradient.addColorStop(0.7, rgba(primary.c3, 0.3));
    gradient.addColorStop(1,   rgba(primary.c4, 0.5));
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, w, h);

    /* ── 2) Black darkening pass (aurora.txt core) ── */
    ctx.save();
    ctx.globalCompositeOperation = "source-over";
    var darkGrad = ctx.createLinearGradient(0, 0, 0, h * 0.5);
    darkGrad.addColorStop(0, "rgba(0,0,0,0.01)");
    darkGrad.addColorStop(1, "rgba(0,0,0,1)");
    ctx.fillStyle = darkGrad;
    ctx.fillRect(0, 0, w, h);
    ctx.restore();

    /* ── 3) Per-pixel simplex noise modulation ── */
    var image = ctx.createImageData(w, h);
    var src   = ctx.getImageData(0, 0, w, h).data;
    var out   = image.data;
    var sX    = aurora.scaleX;
    var sY    = aurora.scaleY;
    var seed  = aurora.seed;
    var cones  = aurora.cones;
    var coneStates = null;
    var familyRows = null;
    if (multi && cones.length) {
      familyRows = new Array(families.length);
      for (var fi = 1; fi < families.length; fi++) {
        familyRows[fi] = new Array(h);
        for (var fy = 0; fy < h; fy++) {
          familyRows[fi][fy] = mix(families[fi].c0, families[fi].c4, fy / h);
        }
      }

      coneStates = new Array(cones.length);
      for (var ci = 0; ci < cones.length; ci++) {
        var cone = cones[ci];
        coneStates[ci] = {
          familyIndex: cone.familyIndex,
          apexX: cone.apexX,
          apexY: cone.apexY,
          baseWidth: cone.baseWidth,
          endWidth: cone.endWidth,
          height: cone.height,
          lean: cone.lean,
          widthCurve: cone.widthCurve,
          centerShift: Math.sin(time * 0.20 + cone.phase) * cone.driftX + Math.cos(time * 0.14 + cone.phase) * cone.sway,
          waveAmp: cone.waveAmp,
          waveFreq: cone.waveFreq,
          wavePhase: cone.phase + time * cone.waveSpeed,
          warpAmp: cone.warpAmp,
          rayFloor: cone.rayFloor
        };
      }
    }

    for (var idx = 0, j = 0, len = out.length; idx < len; idx += 4, j++) {
      var py = (j / w) | 0;
      var px = j - py * w;
      var xn = px / w;

      var n = simplex.noise3d(
        xn * sX,
        (py / h) * sY,
        time + seed
      );
      var factor = n * 0.5 + 0.5;

      /* Top-anchored cone masks: colour widens as it travels downward. */
      if (coneStates) {
        var brightness = (src[idx] * 0.299 + src[idx + 1] * 0.587 + src[idx + 2] * 0.114) / 255;
        var r = src[idx];
        var g = src[idx + 1];
        var b = src[idx + 2];

        for (var cs = 0; cs < coneStates.length; cs++) {
          var coneMask = coneMaskAt(coneStates[cs], xn, py / h, factor);
          if (coneMask > 0) {
            var tint = familyRows[coneStates[cs].familyIndex][py];
            var invMask = 1 - coneMask;
            r = r * invMask + brightness * tint.r * coneMask;
            g = g * invMask + brightness * tint.g * coneMask;
            b = b * invMask + brightness * tint.b * coneMask;
          }
        }

        out[idx]     = Math.floor(factor * r);
        out[idx + 1] = Math.floor(factor * g);
        out[idx + 2] = Math.floor(factor * b);
        out[idx + 3] = 255;
        continue;
      }

      out[idx]     = Math.floor(factor * src[idx]);
      out[idx + 1] = Math.floor(factor * src[idx + 1]);
      out[idx + 2] = Math.floor(factor * src[idx + 2]);
      out[idx + 3] = 255;
    }

    ctx.putImageData(image, 0, 0);
  }

  /* ── Lifecycle hooks ── */
  function onResize() {
    sizeCanvas();
  }

  onResize();
  window.addEventListener("resize", onResize);

  /* Aurora loop: stops rAF entirely during gap periods (battery saving).
     Uses setTimeout to schedule the next aurora start. */
  var auroraRafId = 0;

  function stopAuroraLoop() {
    if (auroraRafId) {
      cancelAnimationFrame(auroraRafId);
      auroraRafId = 0;
    }
  }

  function startAuroraLoop() {
    if (!window.smptAnim.visible || auroraRafId) {
      return;
    }
    if (!auroraRafId) {
      lastFrame = 0;
      auroraRafId = requestAnimationFrame(auroraLoop);
    }
  }

  auroraDebug.restart = function () {
    phaseStart = performance.now();
    initAurora();
    canvas.style.opacity = "1";
    startAuroraLoop();
  };

  function scheduleNextAurora() {
    stopAuroraLoop();
    canvas.style.opacity = "0";
    aurora = null;
    setTimeout(function () {
      if (window.smptAnim.visible) {
        auroraPhase = "active";
        phaseStart  = performance.now();
        initAurora();
        startAuroraLoop();
      } else {
        /* Header not visible — wait and retry */
        scheduleNextAurora();
      }
    }, Math.round(rand(15000, 30000)));
  }

  function auroraLoop(timestamp) {
    if (!lastFrame || timestamp - lastFrame >= FRAME_INTERVAL) {
      renderFrame(performance.now());
      lastFrame = timestamp;
    }
    auroraRafId = requestAnimationFrame(auroraLoop);
  }

  window.addEventListener("smpt:header-visibility", function (event) {
    if (event.detail.visible) {
      if (aurora) {
        startAuroraLoop();
      }
    } else {
      stopAuroraLoop();
    }
  });

  /* Kick off: short initial gap then first aurora */
  setTimeout(function () {
    auroraPhase = "active";
    phaseStart  = performance.now();
    initAurora();
    startAuroraLoop();
  }, Math.round(rand(2000, 5000)));
});
