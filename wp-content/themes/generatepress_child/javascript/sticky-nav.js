document.addEventListener("DOMContentLoaded", function () {
  var nav = document.getElementById("site-navigation");

  if (!nav) {
    return;
  }

  var body = document.body;
  var htmlEl = document.documentElement;
  var insideNavigation = nav.querySelector(".inside-navigation");
  var mainNav = nav.querySelector(".main-nav");
  var mainMenu = mainNav ? mainNav.querySelector("ul") : null;
  var menuToggle = nav.querySelector(".menu-toggle");
  var sentinel = document.createElement("div");
  var layoutSyncTimeout = null;
  var autoCollapseClass = "smpt-nav-auto-collapsed";

  sentinel.className = "smpt-sticky-nav-sentinel";
  nav.parentNode.insertBefore(sentinel, nav);

  function getAdminOffset() {
    if (!body.classList.contains("admin-bar")) {
      return 0;
    }

    return window.matchMedia("(max-width: 782px)").matches ? 46 : 32;
  }

  function updateStickySpacer() {
    if (body.classList.contains("smpt-nav-is-sticky")) {
      sentinel.style.height = nav.offsetHeight + "px";
      body.style.setProperty("--smpt-sticky-nav-height", nav.offsetHeight + "px");
      return;
    }

    sentinel.style.height = "0px";
    body.style.removeProperty("--smpt-sticky-nav-height");
  }

  function syncStickyState(shouldStick) {
    body.classList.toggle("smpt-nav-is-sticky", shouldStick);
    updateStickySpacer();
    requestLayoutSync();
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

  function getRenderedWidth(element) {
    if (!element) {
      return 0;
    }

    var styles = window.getComputedStyle(element);

    if (styles.display === "none" || styles.visibility === "hidden") {
      return 0;
    }

    return element.getBoundingClientRect().width;
  }

  function getCollapseBuffer() {
    var buffer = parseFloat(window.getComputedStyle(nav).getPropertyValue("--smpt-nav-collapse-buffer"));
    return isNaN(buffer) ? 40 : buffer;
  }

  function getReservedWidth() {
    var reservedWidth = 0;

    if (!insideNavigation) {
      return reservedWidth;
    }

    Array.prototype.forEach.call(insideNavigation.children, function (child) {
      if (child === mainNav || child === menuToggle) {
        return;
      }

      reservedWidth += getRenderedWidth(child);
    });

    return reservedWidth;
  }

  function resetExpandedState() {
    nav.classList.remove(autoCollapseClass);
    nav.classList.remove("toggled");
    htmlEl.classList.remove("mobile-menu-open");

    if (menuToggle) {
      menuToggle.setAttribute("aria-expanded", "false");
    }

    if (mainMenu) {
      mainMenu.setAttribute("aria-hidden", "false");
    }
  }

  function syncAutoCollapseState() {
    if (!insideNavigation || !mainNav || !mainMenu || !menuToggle) {
      return;
    }

    var wasCollapsed = nav.classList.contains(autoCollapseClass);
    var wasToggled = nav.classList.contains("toggled");
    var previousFlexWrap = mainMenu.style.flexWrap;
    var insideWidth = 0;
    var availableWidth = 0;
    var menuWidth = 0;
    var shouldCollapse = false;

    if (wasCollapsed) {
      nav.classList.remove(autoCollapseClass);
    }

    if (wasToggled) {
      nav.classList.remove("toggled");
    }

    mainMenu.style.flexWrap = "nowrap";

    insideWidth = insideNavigation.getBoundingClientRect().width;
    availableWidth = insideWidth - getReservedWidth() - getCollapseBuffer();
    menuWidth = mainMenu.scrollWidth;
    shouldCollapse = menuWidth > Math.max(0, availableWidth);

    mainMenu.style.flexWrap = previousFlexWrap;

    if (wasToggled) {
      nav.classList.add("toggled");
    }

    if (wasCollapsed) {
      nav.classList.add(autoCollapseClass);
    }

    if (shouldCollapse) {
      nav.classList.add(autoCollapseClass);

      if (!nav.classList.contains("toggled")) {
        menuToggle.setAttribute("aria-expanded", "false");
        mainMenu.setAttribute("aria-hidden", "true");
        htmlEl.classList.remove("mobile-menu-open");
      }
    } else {
      resetExpandedState();
    }

    updateStickySpacer();
  }

  function requestLayoutSync() {
    window.clearTimeout(layoutSyncTimeout);
    layoutSyncTimeout = window.setTimeout(function () {
      layoutSyncTimeout = null;
      syncAutoCollapseState();
    }, 80);
  }

  if ("ResizeObserver" in window) {
    var layoutObserver = new ResizeObserver(function () {
      updateStickySpacer();
      requestLayoutSync();
    });

    layoutObserver.observe(nav);

    if (insideNavigation) {
      layoutObserver.observe(insideNavigation);
    }

    if (mainMenu) {
      layoutObserver.observe(mainMenu);
    }
  }

  if ("MutationObserver" in window && mainMenu) {
    new MutationObserver(function () {
      requestLayoutSync();
    }).observe(mainMenu, {
      childList: true,
      subtree: true,
      characterData: true,
      attributes: true
    });
  }

  if (menuToggle) {
    menuToggle.addEventListener("click", function () {
      requestLayoutSync();
      window.setTimeout(updateStickySpacer, 0);
    });
  }

  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(requestLayoutSync);
  }

  window.addEventListener("load", requestLayoutSync);
  window.addEventListener("resize", function () {
    updateObserver();
    requestLayoutSync();
  });

  if (!("IntersectionObserver" in window)) {
    function fallbackScrollHandler() {
      syncStickyState(sentinel.getBoundingClientRect().top <= getAdminOffset());
    }

    window.addEventListener("scroll", fallbackScrollHandler, { passive: true });
    fallbackScrollHandler();
    requestLayoutSync();
    return;
  }

  updateObserver();
  requestLayoutSync();
});
