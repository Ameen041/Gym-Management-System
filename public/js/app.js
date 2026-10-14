/* Shared behaviour for the app pages: day / night switch. */
(function () {
  "use strict";
  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-theme-toggle]");
    if (!btn) return;
    var root = document.documentElement;
    var current = root.getAttribute("data-theme");
    var isDark = current ? current === "dark" : window.matchMedia("(prefers-color-scheme: dark)").matches;
    var next = isDark ? "light" : "dark";
    root.setAttribute("data-theme", next);
    try { localStorage.setItem("theme", next); } catch (err) {}
  });
})();

/* Tabs: [data-tabs] holds [data-tab] buttons, panels are [data-panel] on the page. Print buttons. */
(function () {
  "use strict";
  document.addEventListener("click", function (e) {
    var tab = e.target.closest("[data-tabs] [data-tab]");
    if (tab) {
      var tabs = tab.closest("[data-tabs]");
      tabs.querySelectorAll("[data-tab]").forEach(function (b) {
        var on = b === tab;
        b.classList.toggle("on", on);
        b.setAttribute("aria-selected", on ? "true" : "false");
      });
      document.querySelectorAll("[data-panel]").forEach(function (p) {
        p.hidden = p.getAttribute("data-panel") !== tab.getAttribute("data-tab");
      });
      return;
    }
    if (e.target.closest("[data-print]")) window.print();
  });
})();

/* Copy buttons: [data-copy] copies the value of the input with id = its data-copy value. */
(function () {
  "use strict";
  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-copy]");
    if (!btn) return;
    var field = document.getElementById(btn.getAttribute("data-copy"));
    if (!field) return;
    var done = function () {
      var label = btn.querySelector("span");
      if (!label) return;
      var old = label.textContent;
      label.textContent = btn.getAttribute("data-copied") || old;
      setTimeout(function () { label.textContent = old; }, 1600);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(field.value).then(done, function () { field.select(); });
    } else {
      field.select();
      try { if (document.execCommand("copy")) done(); } catch (err) {}
    }
  });
})();
