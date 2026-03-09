document.addEventListener("DOMContentLoaded", function () {
  var header = document.querySelector(".site-header");
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

  if (!header) {
    return;
  }

  /* ── Aurora borealis blobs ── */
  var aurora = document.createElement("div");
  aurora.className = "smpt-before-background";
  aurora.setAttribute("aria-hidden", "true");

  var blobColors = ["gold", "rose", "red", "magenta", "burgundy", "violet", "navy", "skyblue", "aquamarine", "green"];
  for (var b = 0; b < blobColors.length; b += 1) {
    var blob = document.createElement("div");
    blob.className = "smpt-before-background__blob smpt-before-background__blob--" + blobColors[b];
    aurora.appendChild(blob);
  }
  header.insertBefore(aurora, header.firstChild);

  /* ── Twinkling star particles ── */
  var sky = header.querySelector(".smpt-header-hero__sky");

  if (!sky) {
    sky = document.createElement("div");
    sky.className = "smpt-header-hero__sky";
    sky.setAttribute("aria-hidden", "true");
    header.insertBefore(sky, header.firstChild);
  } else {
    sky.innerHTML = "";
  }

  var tones = ["white", "gold", "yellow"];
  var dotCount = 58;
  var flareCount = 7;

  function randomBetween(min, max) {
    return min + Math.random() * (max - min);
  }

  function weightedTop() {
    var t = Math.pow(Math.random(), 1.35);
    return 5 + t * 58;
  }

  function addDot() {
    var particle = document.createElement("span");
    var core = document.createElement("span");
    var tone = tones[Math.floor(Math.random() * tones.length)];
    var size = randomBetween(2.2, 6.4);

    particle.className = "smpt-after-background smpt-after-background--dot smpt-after-background--" + tone;
    core.className = "smpt-after-background-core";

    particle.style.left = randomBetween(2, 98).toFixed(2) + "%";
    particle.style.top = weightedTop().toFixed(2) + "%";
    particle.style.setProperty("--smpt-size", size.toFixed(2) + "px");
    particle.style.setProperty("--smpt-delay", (-randomBetween(0, 6)).toFixed(2) + "s");
    particle.style.setProperty("--smpt-twinkle-duration", randomBetween(1.8, 3.6).toFixed(2) + "s");
    particle.style.setProperty("--smpt-drift-duration", randomBetween(5.4, 8.6).toFixed(2) + "s");
    particle.style.setProperty("--smpt-drift-distance", randomBetween(2, 7).toFixed(2) + "px");

    particle.appendChild(core);
    sky.appendChild(particle);
  }

  function addFlare() {
    var particle = document.createElement("span");
    var core = document.createElement("span");
    var rayV = document.createElement("span");
    var rayH = document.createElement("span");
    var tone = Math.random() > 0.65 ? "gold" : "white";
    var size = randomBetween(6, 10);
    var rayVSize = randomBetween(48, 76);
    var rayHSize = randomBetween(38, 62);

    particle.className = "smpt-after-background smpt-after-background--flare smpt-after-background--" + tone;
    core.className = "smpt-after-background-core";
    rayV.className = "smpt-after-background-ray smpt-after-background-ray--vertical";
    rayH.className = "smpt-after-background-ray smpt-after-background-ray--horizontal";

    particle.style.left = randomBetween(10, 90).toFixed(2) + "%";
    particle.style.top = randomBetween(16, 68).toFixed(2) + "%";
    particle.style.setProperty("--smpt-size", size.toFixed(2) + "px");
    particle.style.setProperty("--smpt-ray-v", rayVSize.toFixed(2) + "px");
    particle.style.setProperty("--smpt-ray-h", rayHSize.toFixed(2) + "px");
    particle.style.setProperty("--smpt-delay", (-randomBetween(0, 8)).toFixed(2) + "s");
    particle.style.setProperty("--smpt-twinkle-duration", randomBetween(2.4, 4.2).toFixed(2) + "s");
    particle.style.setProperty("--smpt-drift-duration", randomBetween(6.2, 9.5).toFixed(2) + "s");
    particle.style.setProperty("--smpt-ray-pulse-duration", randomBetween(2.0, 3.8).toFixed(2) + "s");
    particle.style.setProperty("--smpt-drift-distance", randomBetween(3, 8).toFixed(2) + "px");

    particle.appendChild(rayV);
    particle.appendChild(rayH);
    particle.appendChild(core);
    sky.appendChild(particle);
  }

  for (var i = 0; i < dotCount; i += 1) {
    addDot();
  }

  for (var j = 0; j < flareCount; j += 1) {
    addFlare();
  }

  /* ── Logo reveal: every 2 min, bring logo in front of senshi for 10 s ── */
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
