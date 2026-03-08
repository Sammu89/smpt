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

  function syncStickyState(shouldStick) {
    body.classList.toggle("smpt-nav-is-sticky", shouldStick);

    if (shouldStick) {
      sentinel.style.height = nav.offsetHeight + "px";
      body.style.setProperty("--smpt-sticky-nav-height", nav.offsetHeight + "px");
    } else {
      sentinel.style.height = "0px";
      body.style.removeProperty("--smpt-sticky-nav-height");
    }
  }

  function updateObserver() {
    if (!("IntersectionObserver" in window)) {
      syncStickyState(sentinel.getBoundingClientRect().top <= getAdminOffset());
      return;
    }

    if (window.smptStickyObserver) {
      window.smptStickyObserver.disconnect();
    }

    window.smptStickyObserver = new IntersectionObserver(
      function (entries) {
        var entry = entries[0];
        syncStickyState(entry.intersectionRatio < 1);
      },
      {
        threshold: [1],
        rootMargin: "-" + getAdminOffset() + "px 0px 0px 0px"
      }
    );

    window.smptStickyObserver.observe(sentinel);
  }

  if ("ResizeObserver" in window) {
    new ResizeObserver(function () {
      if (body.classList.contains("smpt-nav-is-sticky")) {
        sentinel.style.height = nav.offsetHeight + "px";
        body.style.setProperty("--smpt-sticky-nav-height", nav.offsetHeight + "px");
      }
    }).observe(nav);
  }

  if (!("IntersectionObserver" in window)) {
    function fallbackScrollHandler() {
      syncStickyState(sentinel.getBoundingClientRect().top <= getAdminOffset());
    }

    window.addEventListener("scroll", fallbackScrollHandler, { passive: true });
    window.addEventListener("resize", fallbackScrollHandler);
    fallbackScrollHandler();
    return;
  }

  window.addEventListener("resize", updateObserver);
  updateObserver();
});
