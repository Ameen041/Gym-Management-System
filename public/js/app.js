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

/* An exercise picture that can't load (offline, blocked, video removed) shows the dumbbell icon instead of a broken image. */
(function () {
  "use strict";
  function fallback(img) {
    var icon = document.createElement("i");
    icon.className = "fas fa-dumbbell";
    img.replaceWith(icon);
  }
  document.addEventListener("error", function (e) {
    var img = e.target;
    if (img && img.tagName === "IMG" && img.closest(".pick-icon, .lib-thumb")) fallback(img);
  }, true);
  // pictures that already failed before this script ran
  document.querySelectorAll(".pick-icon img, .lib-thumb img").forEach(function (img) {
    if (img.complete && img.naturalWidth === 0) fallback(img);
  });
})();

/* Subscribe form: a "yes" to a health question asks for details and a check with a doctor. */
(function () {
  "use strict";
  var box = document.querySelector("[data-health]");
  if (!box) return;
  var form = box.closest("form");
  function sync() {
    var yes = !!box.querySelector('input[value="1"]:checked');
    var doctor = form.querySelector("[data-health-doctor]");
    form.querySelector("[data-health-yes]").hidden = !yes;
    doctor.hidden = !yes;
    doctor.querySelector("input").required = yes;
    form.querySelector("[data-health-notes]").required = yes;
    form.querySelector("[data-health-optional]").hidden = yes;
  }
  box.addEventListener("change", sync);
  sync();
})();

/* Share buttons: [data-share] opens the phone's share sheet, where there is one. */
(function () {
  "use strict";
  if (!navigator.share) return;
  document.querySelectorAll("[data-share]").forEach(function (btn) {
    btn.hidden = false;
    btn.addEventListener("click", function () {
      navigator.share({ text: btn.getAttribute("data-share-text") }).catch(function () {});
    });
  });
})();
