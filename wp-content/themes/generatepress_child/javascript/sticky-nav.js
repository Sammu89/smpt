document.addEventListener("DOMContentLoaded", function () {
  var nav = document.getElementById("site-navigation");

  if (!nav) {
    return;
  }

  var body = document.body;
  var htmlEl = document.documentElement;
  var header = document.querySelector(".site-header");
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
  var priorityPlusClass = "smpt-nav-priority-plus";
  var maisItem = null;
  var maisSubMenu = null;
  var maisWidth = 0;
  var hiddenItems = [];
  var isSyncing = false;

  sentinel.className = "smpt-sticky-nav-sentinel";

  function placeStickySentinel() {
    var navAboveHeader = body.classList.contains("nav-above-header");
    var parent = nav.parentNode;

    if (!parent) {
      return;
    }

    if (navAboveHeader && header && header.parentNode === parent) {
      if (header.nextSibling !== sentinel) {
        parent.insertBefore(sentinel, header.nextSibling);
      }

      return;
    }

    if (nav.previousSibling !== sentinel) {
      parent.insertBefore(sentinel, nav);
    }
  }

  placeStickySentinel();

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
    placeStickySentinel();
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
    var disclosureLabel = disclosure ? disclosure.querySelector(".smpt-mobile-parent-disclosure__label") : null;

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

    if (disclosureLabel) {
      disclosureLabel.textContent = expanded ? "-" : "+";
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
      var shouldOpen = openStateKeys.indexOf(getMenuItemStateKey(menuItem)) !== -1 ||
        menuItem.classList.contains("smpt-force-open") ||
        menuItem.classList.contains("current-menu-item") ||
        menuItem.classList.contains("current-menu-ancestor") ||
        menuItem.classList.contains("current_page_item") ||
        menuItem.classList.contains("current_page_ancestor");

      if (inlineToggle) {
        inlineToggle.remove();
      }

      if (link && submenu) {
        button = document.createElement("button");
        button.type = "button";
        button.className = "smpt-mobile-parent-link smpt-mobile-parent-disclosure";
        button.innerHTML =
          '<span class="smpt-mobile-parent-disclosure__content">' + link.innerHTML + '</span>' +
          '<span class="screen-reader-text">Alternar submenu</span>' +
          '<span class="smpt-mobile-parent-disclosure__label" aria-hidden="true">+</span>';
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

  function buildMaisItem() {
    if (maisItem) {
      return;
    }

    var maisCloseDelay = 300;
    var openMaisChildItem = null;

    maisItem = document.createElement("li");
    maisItem.className = "menu-item menu-item-has-children smpt-mais-item";

    var link = document.createElement("a");
    link.href = "#";
    link.addEventListener("click", function (e) { e.preventDefault(); });

    var textNode = document.createTextNode("Mais ");
    link.appendChild(textNode);

    var toggle = document.createElement("span");
    toggle.setAttribute("role", "presentation");
    toggle.className = "dropdown-menu-toggle";
    toggle.innerHTML = '<span class="gp-icon icon-arrow"><svg viewBox="0 0 330 512" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"><path d="M305.913 197.085c0 2.266-1.133 4.815-2.833 6.514L171.087 335.593c-1.7 1.7-4.249 2.832-6.515 2.832s-4.815-1.133-6.515-2.832L26.064 203.599c-1.7-1.7-2.832-4.248-2.832-6.514s1.132-4.816 2.832-6.515l14.162-14.163c1.7-1.699 3.966-2.832 6.515-2.832 2.266 0 4.815 1.133 6.515 2.832l111.316 111.317 111.316-111.317c1.7-1.699 4.249-2.832 6.515-2.832s4.815 1.133 6.515 2.832l14.162 14.163c1.7 1.7 2.833 4.249 2.833 6.515z" /></svg></span>';
    link.appendChild(toggle);

    maisSubMenu = document.createElement("ul");
    maisSubMenu.className = "sub-menu";

    maisItem.appendChild(link);
    maisItem.appendChild(maisSubMenu);

    var maisCloseTimer = null;

    function getMaisDirectChildItem(target) {
      var current = target && target.nodeType === 1 ? target : (target ? target.parentElement : null);

      while (current && current.parentElement !== maisSubMenu) {
        current = current.parentElement;
      }

      return current && current.parentElement === maisSubMenu ? current : null;
    }

    function itemHasNestedMaisSubmenu(menuItem) {
      return !!getDirectChildByTag(menuItem, "UL");
    }

    function positionMaisChildSubmenu(menuItem) {
      if (!menuItem || !itemHasNestedMaisSubmenu(menuItem)) {
        return;
      }

      var submenu = getDirectChildByTag(menuItem, "UL");

      if (!submenu) {
        return;
      }

      menuItem.classList.remove("smpt-mais-child-open-left");
      submenu.style.maxWidth = "";
      submenu.style.left = "calc(100% - 0.25rem)";
      submenu.style.right = "";
      submenu.style.marginLeft = "";
      submenu.style.marginRight = "";

      var viewportPadding = 16;
      var maxViewportWidth = Math.max(220, window.innerWidth - (viewportPadding * 2));
      submenu.style.maxWidth = maxViewportWidth + "px";

      var rect = submenu.getBoundingClientRect();

      if (rect.right > window.innerWidth - viewportPadding) {
        menuItem.classList.add("smpt-mais-child-open-left");
        submenu.style.left = (-1 * (rect.width + 6)) + "px";
        submenu.style.right = "auto";
        submenu.style.marginLeft = "0";
        submenu.style.marginRight = "0";
        rect = submenu.getBoundingClientRect();
      }

      if (rect.left < viewportPadding) {
        menuItem.classList.remove("smpt-mais-child-open-left");
        submenu.style.left = (viewportPadding - menuItem.getBoundingClientRect().left) + "px";
        submenu.style.right = "auto";
        submenu.style.marginLeft = "0";
        submenu.style.marginRight = "0";
      }
    }

    function scheduleMaisChildSubmenuPosition(menuItem) {
      var submenu = menuItem ? getDirectChildByTag(menuItem, "UL") : null;

      if (!submenu) {
        return;
      }

      if (submenu.smptPlacementFrame) {
        window.cancelAnimationFrame(submenu.smptPlacementFrame);
        submenu.smptPlacementFrame = null;
      }

      if (submenu.smptPlacementTimeout) {
        window.clearTimeout(submenu.smptPlacementTimeout);
        submenu.smptPlacementTimeout = null;
      }

      submenu.smptPlacementFrame = window.requestAnimationFrame(function () {
        submenu.smptPlacementFrame = null;
        positionMaisChildSubmenu(menuItem);
      });

      submenu.smptPlacementTimeout = window.setTimeout(function () {
        submenu.smptPlacementTimeout = null;
        positionMaisChildSubmenu(menuItem);
      }, 180);
    }

    function setMaisChildExpandedState(menuItem, expanded) {
      if (!menuItem || !itemHasNestedMaisSubmenu(menuItem)) {
        return;
      }

      var submenu = getDirectChildByTag(menuItem, "UL");
      menuItem.classList.toggle("smpt-mais-child-open", expanded);

      var menuLink = getDirectChildByTag(menuItem, "A");

      if (menuLink) {
        menuLink.setAttribute("aria-expanded", expanded ? "true" : "false");
      }

      if (submenu) {
        submenu.style.left = "";
        submenu.style.right = "";
        submenu.style.maxWidth = "";
        submenu.style.marginLeft = "";
        submenu.style.marginRight = "";
      }

      if (expanded) {
        scheduleMaisChildSubmenuPosition(menuItem);
      } else {
        menuItem.classList.remove("smpt-mais-child-open-left");
      }
    }

    function setOpenMaisChild(menuItem) {
      if (!menuItem || !itemHasNestedMaisSubmenu(menuItem)) {
        if (openMaisChildItem) {
          setMaisChildExpandedState(openMaisChildItem, false);
          openMaisChildItem = null;
        }

        return;
      }

      if (openMaisChildItem === menuItem) {
        return;
      }

      if (openMaisChildItem) {
        setMaisChildExpandedState(openMaisChildItem, false);
      }

      openMaisChildItem = menuItem;
      setMaisChildExpandedState(openMaisChildItem, true);
    }

    maisItem.addEventListener("mouseenter", function () {
      if (maisCloseTimer) {
        clearTimeout(maisCloseTimer);
        maisCloseTimer = null;
      }

      maisItem.classList.add("sfHover");
    });

    maisItem.addEventListener("mouseleave", function () {
      maisCloseTimer = setTimeout(function () {
        maisCloseTimer = null;
        setOpenMaisChild(null);
        maisItem.classList.remove("sfHover");
      }, maisCloseDelay);
    });

    maisSubMenu.addEventListener("focusin", function (event) {
      var directItem = getMaisDirectChildItem(event.target);

      if (!directItem || !itemHasNestedMaisSubmenu(directItem)) {
        return;
      }

      setOpenMaisChild(directItem);
      maisItem.classList.add("sfHover");
    });

    maisSubMenu.addEventListener("mouseover", function (event) {
      var directItem = getMaisDirectChildItem(event.target);

      if (!directItem || !itemHasNestedMaisSubmenu(directItem)) {
        return;
      }

      setOpenMaisChild(directItem);
      maisItem.classList.add("sfHover");
    });

    maisSubMenu.addEventListener("click", function (event) {
      var menuLink = event.target.closest("a");
      var directItem = getMaisDirectChildItem(event.target);

      if (!menuLink || !directItem || !itemHasNestedMaisSubmenu(directItem)) {
        return;
      }

      var href = (menuLink.getAttribute("href") || "").trim().toLowerCase();

      if (href !== "#" && href !== "" && href !== "javascript:void(0)") {
        return;
      }

      event.preventDefault();
      setOpenMaisChild(directItem);
      maisItem.classList.add("sfHover");
    });

    window.addEventListener("resize", function () {
      if (openMaisChildItem && maisItem.classList.contains("sfHover")) {
        positionMaisChildSubmenu(openMaisChildItem);
      }
    });
  }

  function measureMaisWidth() {
    if (!mainMenu || !maisItem) {
      return 80;
    }

    mainMenu.appendChild(maisItem);
    var w = maisItem.getBoundingClientRect().width;
    mainMenu.removeChild(maisItem);

    return w || 80;
  }

  function resetPriorityPlus() {
    hiddenItems.forEach(function (item) {
      item.style.display = "";
    });

    hiddenItems = [];

    if (maisItem && maisItem.parentNode) {
      maisItem.parentNode.removeChild(maisItem);
    }

    if (maisSubMenu) {
      maisSubMenu.innerHTML = "";
    }

    nav.classList.remove(priorityPlusClass);
  }

  function resetExpandedState() {
    resetPriorityPlus();
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

    if (isSyncing) {
      return;
    }

    isSyncing = true;

    var wasCollapsed = nav.classList.contains(autoCollapseClass);
    var wasToggled = nav.classList.contains("toggled");
    var previousFlexWrap = mainMenu.style.flexWrap;

    // Reset everything to natural state for measurement.
    resetPriorityPlus();

    if (wasCollapsed) {
      nav.classList.remove(autoCollapseClass);
    }

    if (wasToggled) {
      nav.classList.remove("toggled");
    }

    mainMenu.style.flexWrap = "nowrap";

    var insideWidth = insideNavigation.getBoundingClientRect().width;
    var availableWidth = insideWidth - getReservedWidth() - getCollapseBuffer();
    var menuWidth = mainMenu.scrollWidth;

    // No overflow — everything fits.
    if (menuWidth <= Math.max(0, availableWidth)) {
      mainMenu.style.flexWrap = previousFlexWrap;
      resetExpandedState();
      updateStickySpacer();
      requestAnimationFrame(function () { isSyncing = false; });
      return;
    }

    // Overflow detected — try priority plus (move up to 3 items to "Mais").
    buildMaisItem();

    if (!maisWidth) {
      maisWidth = measureMaisWidth();
    }

    var items = Array.prototype.filter.call(mainMenu.children, function (child) {
      return child !== maisItem && child.classList.contains("menu-item");
    });

    var itemWidths = items.map(function (item) {
      return { element: item, width: item.getBoundingClientRect().width };
    });

    var totalWidth = itemWidths.reduce(function (sum, info) {
      return sum + info.width;
    }, 0);

    var targetWidth = availableWidth - maisWidth;
    var toHide = [];
    var remainingWidth = totalWidth;

    for (var i = itemWidths.length - 1; i >= 0; i--) {
      if (remainingWidth <= targetWidth) {
        break;
      }

      toHide.unshift(itemWidths[i]);
      remainingWidth -= itemWidths[i].width;
    }

    if (toHide.length >= 1 && toHide.length <= 3 && remainingWidth <= targetWidth) {
      // Priority plus: hide overflow items, show them in the "Mais" dropdown.
      maisSubMenu.innerHTML = "";

      toHide.forEach(function (info) {
        info.element.style.display = "none";
        hiddenItems.push(info.element);

        var clone = info.element.cloneNode(true);
        clone.style.display = "";
        removeIdsFromTree(clone);
        maisSubMenu.appendChild(clone);
      });

      mainMenu.appendChild(maisItem);
      nav.classList.add(priorityPlusClass);

      mainMenu.style.flexWrap = previousFlexWrap;
      mainMenu.setAttribute("aria-hidden", "false");
      menuToggle.setAttribute("aria-expanded", "false");
      updateStickySpacer();
      requestAnimationFrame(function () { isSyncing = false; });
      return;
    }

    // Too many items overflow (>3) or still doesn't fit — full hamburger.
    mainMenu.style.flexWrap = previousFlexWrap;

    if (wasToggled) {
      nav.classList.add("toggled");
    }

    nav.classList.add(autoCollapseClass);

    if (!nav.classList.contains("toggled")) {
      menuToggle.setAttribute("aria-expanded", "false");
      mainMenu.setAttribute("aria-hidden", "true");
    }

    updateStickySpacer();
    requestAnimationFrame(function () { isSyncing = false; });
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
      if (isSyncing) {
        return;
      }

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
    new MutationObserver(function (mutations) {
      if (isSyncing) {
        return;
      }

      var relevant = mutations.some(function (m) {
        // Ignore state changes inside the synthetic "Mais" menu.
        if (
          m.type === "attributes" &&
          maisItem &&
          m.target &&
          typeof m.target.nodeType === "number" &&
          m.target.nodeType === 1 &&
          maisItem.contains(m.target)
        ) {
          return false;
        }

        if (m.type === "attributes") {
          var attr = m.attributeName || "";
          return attr !== "aria-hidden" && attr !== "aria-expanded" && attr !== "aria-label";
        }

        // Ignore childList changes caused by adding/removing the Mais item.
        if (m.type === "childList") {
          var isOnlyMais = Array.prototype.every.call(m.addedNodes, function (n) { return n === maisItem; })
            && Array.prototype.every.call(m.removedNodes, function (n) { return n === maisItem; });

          if (isOnlyMais) {
            return false;
          }
        }

        return true;
      });

      if (!relevant) {
        return;
      }

      requestLayoutSync();

      if (!htmlEl.classList.contains("smpt-mobile-menu-open")) {
        cloneMenuForOffcanvas();
      }
    }).observe(mainMenu, {
      childList: true,
      subtree: true,
      characterData: true,
      attributes: true,
      attributeFilter: ["class", "aria-hidden", "aria-expanded", "aria-label"]
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
