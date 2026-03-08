document.addEventListener("DOMContentLoaded", function () {
  var stage = document.querySelector(".smpt-header-hero__stage");

  if (!stage) {
    return;
  }

  var sky = stage.querySelector(".smpt-header-hero__sky");

  if (!sky) {
    sky = document.createElement("div");
    sky.className = "smpt-header-hero__sky";
    sky.setAttribute("aria-hidden", "true");
    stage.insertBefore(sky, stage.firstChild);
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

    particle.className = "smpt-header-hero__particle smpt-header-hero__particle--dot smpt-header-hero__particle--" + tone;
    core.className = "smpt-header-hero__particle-core";

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

    particle.className = "smpt-header-hero__particle smpt-header-hero__particle--flare smpt-header-hero__particle--" + tone;
    core.className = "smpt-header-hero__particle-core";
    rayV.className = "smpt-header-hero__particle-ray smpt-header-hero__particle-ray--vertical";
    rayH.className = "smpt-header-hero__particle-ray smpt-header-hero__particle-ray--horizontal";

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
});
