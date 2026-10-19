/* Public pages: mobile menu, login / sign-up modal. */
(function () {
  "use strict";

  const modal = document.querySelector(".auth-modal");

  function switchAuthTab(tab) {
    document.querySelectorAll(".auth-tab").forEach((b) => b.classList.toggle("active", b.dataset.tab === tab));
    document.querySelectorAll(".auth-form").forEach((f) => f.classList.toggle("active", f.id === tab));
  }
  function openModal(tab) {
    if (!modal) return;
    modal.classList.add("active");
    switchAuthTab(tab);
    const first = modal.querySelector(`#${tab} input:not([type=hidden]), #${tab} select`);
    if (first && window.matchMedia("(min-width: 641px)").matches) first.focus();
  }
  window.showLoginModal = () => openModal("login");
  window.showSignupModal = () => openModal("signup");

  document.addEventListener("click", (e) => {
    if (e.target.closest(".login-btn")) { e.preventDefault(); return openModal("login"); }
    if (e.target.closest(".signup-btn")) { e.preventDefault(); return openModal("signup"); }
    if (e.target.closest("[data-signup-coach]")) {
      openModal("signup");
      const role = document.getElementById("signupRole");
      if (role) { role.value = "trainer"; role.dispatchEvent(new Event("change")); }
      return;
    }
    const tab = e.target.closest(".auth-tab");
    if (tab) return switchAuthTab(tab.dataset.tab);
    if (e.target.closest(".close-modal") || e.target === modal) return modal.classList.remove("active");

    const menu = e.target.closest("[data-menu]");
    const nav = document.getElementById("site-nav");
    if (menu && nav) {
      const open = !nav.classList.contains("open");
      nav.classList.toggle("open", open);
      menu.setAttribute("aria-expanded", open ? "true" : "false");
      menu.querySelector("i").className = open ? "fas fa-xmark" : "fas fa-bars";
    } else if (nav && e.target.closest("#site-nav a")) {
      nav.classList.remove("open");
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal) modal.classList.remove("active");
  });

  // Links like /#login open the login window (used by pages that need a sign-in)
  if (location.hash === "#login") openModal("login");
  if (location.hash === "#signup") openModal("signup");
})();

/* Home page motion: things appear as they scroll into view, numbers count up, one hero line at a time.
   Only with html.motion (set in the layout unless the visitor asked for reduced motion). */
(function () {
  "use strict";
  var root = document.documentElement;
  if (!root.classList.contains("motion")) return;

  // count up from 0 when seen
  function countUp(el) {
    var to = parseFloat(el.getAttribute("data-count"));
    if (!isFinite(to) || el.dataset.counted) return;
    el.dataset.counted = "1";
    var start = performance.now(), duration = 1400;
    (function step(now) {
      var t = Math.min(1, (now - start) / duration);
      var eased = 1 - Math.pow(1 - t, 3);
      el.textContent = Math.round(to * eased).toLocaleString("en-US");
      if (t < 1) requestAnimationFrame(step);
    })(start);
  }

  var watched = document.querySelectorAll("[data-reveal], [data-count]");
  if (!("IntersectionObserver" in window)) {
    watched.forEach(function (el) { el.classList.add("in"); if (el.hasAttribute("data-count")) countUp(el); });
  } else {
    var seen = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("in");
        if (entry.target.hasAttribute("data-count")) countUp(entry.target);
        entry.target.querySelectorAll("[data-count]").forEach(countUp);
        seen.unobserve(entry.target);
      });
    }, { threshold: 0.18, rootMargin: "0px 0px -40px 0px" });
    watched.forEach(function (el) { seen.observe(el); });
  }

  // the hero line changes every few seconds
  document.querySelectorAll("[data-rotator]").forEach(function (box) {
    var lines = box.querySelectorAll("span");
    if (lines.length < 2) return;
    var i = 0;
    setInterval(function () {
      if (document.hidden) return;
      var current = lines[i];
      i = (i + 1) % lines.length;
      current.classList.remove("on");
      current.classList.add("out");
      lines[i].classList.remove("out");
      lines[i].classList.add("on");
      setTimeout(function () { current.classList.remove("out"); }, 600);
    }, 2800);
  });
})();
