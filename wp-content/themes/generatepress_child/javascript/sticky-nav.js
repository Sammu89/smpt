document.addEventListener("DOMContentLoaded", function () {
  var nav = document.getElementById("site-navigation");

  if (!nav) {
    return;
  }

  var body = document.body;
  var sentinel = document.createElement("div");
  sentinel.className = "smpt-sticky-nav-sentinel";
  nav.parentNode.insertBefore(sentinel, nav);

  function getAdminOffset() {
    if (!body.classList.contains("admin-bar")) {
      return 0;
    }

    return window.matchMedia("(max-width: 782px)").matches ? 46 : 32;
  }

  function syncStickyState() {
    var shouldStick = sentinel.getBoundingClientRect().top <= getAdminOffset();
    body.classList.toggle("smpt-nav-is-sticky", shouldStick);

    if (shouldStick) {
      sentinel.style.height = nav.offsetHeight + "px";
      body.style.setProperty("--smpt-sticky-nav-height", nav.offsetHeight + "px");
    } else {
      sentinel.style.height = "0px";
      body.style.removeProperty("--smpt-sticky-nav-height");
    }
  }

  if ("ResizeObserver" in window) {
    new ResizeObserver(syncStickyState).observe(nav);
  }

  window.addEventListener("scroll", syncStickyState, { passive: true });
  window.addEventListener("resize", syncStickyState);
  syncStickyState();
});
