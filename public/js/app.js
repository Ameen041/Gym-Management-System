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
