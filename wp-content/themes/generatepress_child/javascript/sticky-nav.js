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
  var mobileMenu = document.getElementById("smpt-mobile-menu");
  var mobileMenuPanel = mobileMenu ? mobileMenu.querySelector(".smpt-mobile-menu__panel") : null;
  var mobileMenuRoot = mobileMenu ? mobileMenu.querySelector("[data-smpt-mobile-menu-root]") : null;
  var mobileMenuCloseButton = mobileMenu ? mobileMenu.querySelector(".smpt-mobile-menu__close") : null;
  var sentinel = document.createElement("div");
  var layoutSyncTimeout = null;
  var stickyStateFrame = null;
  var autoCollapseClass = "smpt-nav-auto-collapsed";
  var lastFocusedElement = null;
  var lastHandledMobileTouchAt = 0;

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
    var isSticky = body.classList.contains("smpt-nav-is-sticky");

    if (isSticky === shouldStick) {
      updateStickySpacer();
      return;
    }

    body.classList.toggle("smpt-nav-is-sticky", shouldStick);
    updateStickySpacer();
    requestLayoutSync();
  }

  function evaluateStickyState() {
    syncStickyState(sentinel.getBoundingClientRect().top <= getAdminOffset());
  }

  function requestStickyStateSync() {
    if (stickyStateFrame) {
      return;
    }

    stickyStateFrame = window.requestAnimationFrame(function () {
      stickyStateFrame = null;
      evaluateStickyState();
    });
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

  function removeIdsFromTree(element) {
    if (!element) {
      return;
    }

    if (element.hasAttribute("id")) {
      element.removeAttribute("id");
    }

    Array.prototype.forEach.call(element.children, removeIdsFromTree);
  }

  function getDirectChildByTag(element, tagName) {
    if (!element) {
      return null;
    }

    tagName = tagName.toUpperCase();

    for (var i = 0; i < element.children.length; i += 1) {
      if (element.children[i].tagName === tagName) {
        return element.children[i];
      }
    }

    return null;
  }

  function getDirectChildByClass(element, className) {
    if (!element) {
      return null;
    }

    for (var i = 0; i < element.children.length; i += 1) {
      if (element.children[i].classList.contains(className)) {
        return element.children[i];
      }
    }

    return null;
  }

  function getMenuItemLabelElement(menuItem) {
    return getDirectChildByClass(menuItem, "smpt-mobile-parent-disclosure") || getDirectChildByTag(menuItem, "A");
  }

  function getMenuItemStateKey(menuItem) {
    if (!menuItem) {
      return "";
    }

    var className = Array.prototype.find.call(menuItem.classList, function (name) {
      return /^menu-item-\d+$/.test(name);
    });
    var link = getMenuItemLabelElement(menuItem);
    var href = link ? link.getAttribute("href") || "" : "";
    var text = link ? link.textContent.trim() : "";

    return className || [href, text].join("|");
  }

  function getOpenMobileSubmenuKeys() {
    if (!mobileMenuRoot) {
      return [];
    }

    return Array.prototype.map.call(
      mobileMenuRoot.querySelectorAll(".menu-item-has-children.is-open"),
      function (menuItem) {
        return getMenuItemStateKey(menuItem);
      }
    ).filter(Boolean);
  }

  function finishMobileSubmenuAnimation(submenu, expanded) {
    if (!submenu) {
      return;
    }

    if (submenu.smptAnimationFrame) {
      window.cancelAnimationFrame(submenu.smptAnimationFrame);
      submenu.smptAnimationFrame = null;
    }

    if (submenu.smptAnimationTimeout) {
      window.clearTimeout(submenu.smptAnimationTimeout);
      submenu.smptAnimationTimeout = null;
    }

    if (submenu.smptAnimationCleanup) {
      submenu.removeEventListener("transitionend", submenu.smptAnimationCleanup);
      submenu.smptAnimationCleanup = null;
    }

    submenu.style.transition = "";
    submenu.style.maxHeight = expanded ? "none" : "";
    submenu.style.opacity = expanded ? "1" : "";
    submenu.style.transform = expanded ? "translateY(0)" : "";
    submenu.style.overflow = expanded ? "visible" : "";

    if (!expanded) {
      submenu.hidden = true;
    }
  }

  function animateMobileSubmenu(submenu, expanded, immediate) {
    if (!submenu) {
      return;
    }

    finishMobileSubmenuAnimation(submenu, !expanded);

    if (immediate || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      submenu.hidden = !expanded;
      submenu.setAttribute("aria-hidden", expanded ? "false" : "true");
      submenu.style.maxHeight = expanded ? "none" : "";
      submenu.style.opacity = expanded ? "1" : "";
      submenu.style.transform = expanded ? "translateY(0)" : "";
      submenu.style.overflow = expanded ? "visible" : "";
      return;
    }

    var cleanup = function () {
      finishMobileSubmenuAnimation(submenu, expanded);
    };

    submenu.hidden = false;
    submenu.style.overflow = "hidden";
    submenu.style.transition = "none";

    if (expanded) {
      submenu.style.maxHeight = "0px";
      submenu.style.opacity = "0";
      submenu.style.transform = "translateY(-4px)";

      submenu.smptAnimationFrame = window.requestAnimationFrame(function () {
        submenu.smptAnimationFrame = null;
        submenu.style.transition = "max-height 220ms cubic-bezier(0.22, 1, 0.36, 1), opacity 180ms ease, transform 220ms ease";
        submenu.style.maxHeight = submenu.scrollHeight + "px";
        submenu.style.opacity = "1";
        submenu.style.transform = "translateY(0)";
      });
    } else {
      submenu.style.maxHeight = submenu.scrollHeight + "px";
      submenu.style.opacity = "1";
      submenu.style.transform = "translateY(0)";
      submenu.offsetHeight;
      submenu.style.transition = "max-height 200ms cubic-bezier(0.4, 0, 0.2, 1), opacity 160ms ease, transform 200ms ease";
      submenu.style.maxHeight = "0px";
      submenu.style.opacity = "0";
      submenu.style.transform = "translateY(-4px)";
    }

    submenu.smptAnimationCleanup = function (event) {
      if (event.target !== submenu || event.propertyName !== "max-height") {
        return;
      }

      cleanup();
    };

    submenu.addEventListener("transitionend", submenu.smptAnimationCleanup);
    submenu.smptAnimationTimeout = window.setTimeout(cleanup, 280);
  }

  function setMobileSubmenuState(menuItem, expanded, options) {
    if (!menuItem) {
      return;
    }

    var immediate = !!(options && options.immediate);
    var submenu = getDirectChildByTag(menuItem, "UL");
    var link = getDirectChildByTag(menuItem, "A");
    var disclosure = getDirectChildByClass(menuItem, "smpt-mobile-parent-disclosure");
    var toggle = getDirectChildByClass(menuItem, "smpt-mobile-submenu-toggle");
    var label = toggle ? toggle.querySelector(".smpt-mobile-submenu-toggle__label") : null;

    menuItem.classList.toggle("is-open", expanded);

    if (submenu) {
      submenu.setAttribute("aria-hidden", expanded ? "false" : "true");
      animateMobileSubmenu(submenu, expanded, immediate);
    }

    if (toggle) {
      toggle.setAttribute("aria-expanded", expanded ? "true" : "false");
      toggle.setAttribute("aria-label", expanded ? "Fechar submenu" : "Abrir submenu");
    }

    if (disclosure) {
      disclosure.setAttribute("aria-expanded", expanded ? "true" : "false");
      disclosure.setAttribute("aria-label", expanded ? "Fechar submenu" : "Abrir submenu");
    }

    if (label) {
      label.textContent = expanded ? "-" : "+";
    }
  }

  function cloneMenuForOffcanvas() {
    if (!mainMenu || !mobileMenuRoot) {
      return;
    }

    var openStateKeys = getOpenMobileSubmenuKeys();
    var clonedMenu = mainMenu.cloneNode(true);

    removeIdsFromTree(clonedMenu);
    clonedMenu.classList.add("smpt-mobile-side-nav");
    clonedMenu.removeAttribute("aria-hidden");

    Array.prototype.forEach.call(clonedMenu.querySelectorAll(".menu-item-has-children"), function (menuItem) {
      var link = getDirectChildByTag(menuItem, "A");
      var submenu = getDirectChildByTag(menuItem, "UL");
      var inlineToggle = link ? link.querySelector(".dropdown-menu-toggle") : null;
      var button = null;
      var linkHref = link ? (link.getAttribute("href") || "").trim().toLowerCase() : "";
      var useDisclosureButton = !!submenu && (linkHref === "#" || linkHref === "" || linkHref === "javascript:void(0)");
      var shouldOpen = openStateKeys.indexOf(getMenuItemStateKey(menuItem)) !== -1 ||
        menuItem.classList.contains("smpt-force-open") ||
        menuItem.classList.contains("current-menu-item") ||
        menuItem.classList.contains("current-menu-ancestor") ||
        menuItem.classList.contains("current_page_item") ||
        menuItem.classList.contains("current_page_ancestor");

      if (inlineToggle) {
        inlineToggle.remove();
      }

      if (link && useDisclosureButton) {
        button = document.createElement("button");
        button.type = "button";
        button.className = "smpt-mobile-parent-link smpt-mobile-parent-disclosure";
        button.innerHTML = link.innerHTML;
        menuItem.replaceChild(button, link);
      } else if (link) {
        button = document.createElement("button");
        button.type = "button";
        button.className = "smpt-mobile-submenu-toggle";
        button.innerHTML = '<span class="screen-reader-text">Alternar submenu</span><span class="smpt-mobile-submenu-toggle__label" aria-hidden="true">+</span>';
      }

      if (submenu) {
        submenu.classList.add("smpt-mobile-submenu");
      }

      if (button && button.classList.contains("smpt-mobile-submenu-toggle")) {
        menuItem.insertBefore(button, submenu || null);
      }

      setMobileSubmenuState(menuItem, shouldOpen, { immediate: true });
    });

    mobileMenuRoot.innerHTML = "";
    mobileMenuRoot.appendChild(clonedMenu);
  }

  function openOffcanvasMenu() {
    if (!mobileMenu || !nav.classList.contains(autoCollapseClass)) {
      return;
    }

    cloneMenuForOffcanvas();
    lastFocusedElement = document.activeElement;
    mobileMenu.hidden = false;
    mobileMenu.setAttribute("aria-hidden", "false");

    if (!body.classList.contains("smpt-mobile-menu-open")) {
      body.classList.add("smpt-mobile-menu-open");
    }

    if (!htmlEl.classList.contains("smpt-mobile-menu-open")) {
      htmlEl.classList.add("smpt-mobile-menu-open");
    }

    if (!htmlEl.classList.contains("mobile-menu-open")) {
      htmlEl.classList.add("mobile-menu-open");
    }

    nav.classList.remove("toggled");

    if (menuToggle) {
      menuToggle.setAttribute("aria-expanded", "true");
    }

    if (mainMenu) {
      mainMenu.setAttribute("aria-hidden", "true");
    }

    window.requestAnimationFrame(function () {
      mobileMenu.classList.add("is-open");
    });

    if (mobileMenuCloseButton) {
      window.setTimeout(function () {
        mobileMenuCloseButton.focus();
      }, 60);
    }
  }

  function closeOffcanvasMenu(options) {
    if (!mobileMenu) {
      return;
    }

    var shouldRestoreFocus = !options || options.restoreFocus !== false;

    mobileMenu.classList.remove("is-open");
    mobileMenu.setAttribute("aria-hidden", "true");

    if (body.classList.contains("smpt-mobile-menu-open")) {
      body.classList.remove("smpt-mobile-menu-open");
    }

    if (htmlEl.classList.contains("smpt-mobile-menu-open")) {
      htmlEl.classList.remove("smpt-mobile-menu-open");
    }

    if (htmlEl.classList.contains("mobile-menu-open")) {
      htmlEl.classList.remove("mobile-menu-open");
    }

    if (menuToggle) {
      menuToggle.setAttribute("aria-expanded", "false");
    }

    window.setTimeout(function () {
      if (!mobileMenu.classList.contains("is-open")) {
        mobileMenu.hidden = true;
      }
    }, 320);

    if (shouldRestoreFocus && lastFocusedElement && typeof lastFocusedElement.focus === "function") {
      lastFocusedElement.focus();
    }

    lastFocusedElement = null;
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
    closeOffcanvasMenu({ restoreFocus: false });

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

  function handleMobileMenuToggleInteraction(event) {
    var closeTarget = event.target.closest("[data-smpt-mobile-close]");
    var submenuToggle = event.target.closest(".smpt-mobile-submenu-toggle");
    var disclosureButton = event.target.closest(".smpt-mobile-parent-disclosure");
    var menuLink = event.target.closest("a");
    var parentMenuItem = disclosureButton ? disclosureButton.closest(".menu-item-has-children") : (menuLink ? menuLink.closest(".menu-item-has-children") : null);

    if (closeTarget) {
      event.preventDefault();
      closeOffcanvasMenu();
      return true;
    }

    if (disclosureButton && parentMenuItem) {
      event.preventDefault();
      setMobileSubmenuState(parentMenuItem, !parentMenuItem.classList.contains("is-open"));
      return true;
    }

    if (submenuToggle) {
      event.preventDefault();
      setMobileSubmenuState(
        submenuToggle.parentElement,
        submenuToggle.getAttribute("aria-expanded") !== "true"
      );
      return true;
    }

    return false;
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

      if (!htmlEl.classList.contains("smpt-mobile-menu-open")) {
        cloneMenuForOffcanvas();
      }
    }).observe(mainMenu, {
      childList: true,
      subtree: true,
      characterData: true,
      attributes: true
    });
  }

  if (menuToggle) {
    menuToggle.addEventListener("click", function (event) {
      if (nav.classList.contains(autoCollapseClass)) {
        event.preventDefault();
        event.stopPropagation();

        if (htmlEl.classList.contains("smpt-mobile-menu-open")) {
          closeOffcanvasMenu();
        } else {
          openOffcanvasMenu();
        }
      }

      requestLayoutSync();
      window.setTimeout(updateStickySpacer, 0);
    }, true);
  }

  if (mobileMenu) {
    mobileMenu.addEventListener("touchend", function (event) {
      if (handleMobileMenuToggleInteraction(event)) {
        lastHandledMobileTouchAt = Date.now();
      }
    }, { passive: false });

    mobileMenu.addEventListener("click", function (event) {
      var menuLink = event.target.closest("a");

      if (Date.now() - lastHandledMobileTouchAt < 750) {
        event.preventDefault();
        event.stopPropagation();
        return;
      }

      if (handleMobileMenuToggleInteraction(event)) {
        return;
      }

      if (menuLink && mobileMenuPanel && mobileMenuPanel.contains(menuLink)) {
        closeOffcanvasMenu({ restoreFocus: false });
      }
    });
  }

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && htmlEl.classList.contains("smpt-mobile-menu-open")) {
      closeOffcanvasMenu();
    }
  });

  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(requestLayoutSync);
  }

  window.addEventListener("load", requestLayoutSync);
  window.addEventListener("load", requestStickyStateSync);
  window.addEventListener("scroll", requestStickyStateSync, { passive: true });
  window.addEventListener("resize", function () {
    if (!nav.classList.contains(autoCollapseClass)) {
      closeOffcanvasMenu({ restoreFocus: false });
    }

    requestStickyStateSync();
    requestLayoutSync();
  });

  cloneMenuForOffcanvas();
  evaluateStickyState();
  requestLayoutSync();
});
