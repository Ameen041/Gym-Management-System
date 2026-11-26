// Without internet (sw.js keeps the pages): a bar says the page is the copy kept on the phone, forms wait for the
// internet instead of failing, and, for a signed-in coach or trainee, their main pages, plans and exercise pictures
// are kept on the phone while there is internet (the list comes from /offline-pack).
(function () {
  "use strict";
  var html = document.documentElement;
  var PACK_AT = "himma-offline-pack-at"; // when the pack was last sent to sw.js
  var PACK_EVERY = 30 * 60 * 1000;

  function t(key) { return (window.I18N && window.I18N[key]) || ""; }
  function isCopy() { return html.hasAttribute("data-offline-copy"); }
  function offline() { return navigator.onLine === false; }

  function keptAt() {
    var at = new Date(html.getAttribute("data-offline-copy") || "");
    if (isNaN(at.getTime())) return "";
    try {
      return at.toLocaleString(html.lang === "ar" ? "ar-SY-u-nu-latn" : "en-GB", { day: "numeric", month: "short", hour: "numeric", minute: "2-digit" });
    } catch (e) { return at.toLocaleString(); }
  }

  /* ---- the bar at the top of the page ---- */
  // A kept copy shows when the phone has no internet, or when the site doesn't answer (the phone may still think it
  // is online then): "the internet is back" is said only when the phone tells us so while the page is open.
  var bar = null;
  var cameBack = false;
  var backTimer = null;
  function showBar(e) {
    var copy = isCopy();
    if (e && e.type === "online") cameBack = true;
    if (e && e.type === "offline") cameBack = false;
    if (!copy && !offline() && !cameBack) {
      if (bar) bar.hidden = true;
      return;
    }
    if (!bar) {
      bar = document.createElement("div");
      bar.className = "alert alert-warning offline-bar";
      bar.setAttribute("role", "status");
      bar.innerHTML = '<i class="fas fa-wifi"></i><div><span data-offline-text></span> '
        + '<button type="button" class="offline-reload" data-offline-reload hidden></button></div>';
      var spot = document.querySelector("main.content") || document.body;
      spot.insertBefore(bar, spot.firstChild);
    }
    var back = cameBack && !offline();
    var text = back ? t("offline_back") : (copy ? t("offline_copy").replace(":time", keptAt()) : t("offline_now"));
    bar.querySelector("[data-offline-text]").textContent = text;
    var reload = bar.querySelector("[data-offline-reload]");
    reload.textContent = t("offline_reload");
    // a kept copy can be refreshed whenever the phone may have internet
    reload.hidden = offline() || !copy;
    bar.classList.toggle("is-back", back);
    bar.hidden = false;
    // a live page has nothing to refresh: "the internet is back" goes after a moment
    clearTimeout(backTimer);
    if (back && !copy) backTimer = setTimeout(function () { cameBack = false; showBar(); }, 4000);
  }
  document.addEventListener("click", function (e) {
    if (e.target.closest("[data-offline-reload]")) location.reload();
  });
  window.addEventListener("online", showBar);
  window.addEventListener("offline", showBar);
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", showBar); else showBar();

  /* ---- forms: nothing is sent without internet; what was typed stays on the page ---- */
  var toastTimer = null;
  function toast(text) {
    var box = document.querySelector("[data-offline-toast]");
    if (!box) {
      box = document.createElement("div");
      box.className = "offline-toast";
      box.setAttribute("data-offline-toast", "");
      box.setAttribute("role", "alert");
      document.body.appendChild(box);
    }
    box.innerHTML = '<i class="fas fa-wifi"></i><span></span>';
    box.querySelector("span").textContent = text;
    box.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { box.hidden = true; }, 5000);
  }
  document.addEventListener("submit", function (e) {
    var form = e.target;
    var action = form.getAttribute("action") || "";
    // signing out or in: the next person's pages are kept afresh
    if (/\/(logout|login|register)$/.test(action)) {
      try { localStorage.removeItem(PACK_AT); } catch (err) {}
    }
    if ((form.getAttribute("method") || "get").toLowerCase() === "get" || !offline()) return;
    e.preventDefault();
    e.stopPropagation();
    toast(t("offline_not_sent"));
  }, true);

  /* ---- keep the person's pages for later ---- */
  var meta = document.querySelector('meta[name="offline-pack"]');
  if (!meta || isCopy() || !("serviceWorker" in navigator) || !window.fetch) return;
  if (navigator.connection && navigator.connection.saveData) return;
  try {
    if (Date.now() - Number(localStorage.getItem(PACK_AT) || 0) < PACK_EVERY) return;
  } catch (err) { /* private window: kept once per visit */ }

  window.addEventListener("load", function () {
    setTimeout(function () {
      if (offline()) return;
      fetch(meta.getAttribute("content"), { credentials: "same-origin", headers: { Accept: "application/json" } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (pack) {
          if (!pack || !pack.version) return null;
          return navigator.serviceWorker.ready.then(function (reg) {
            if (!reg.active) return;
            reg.active.postMessage({ type: "offline-pack", pack: pack });
            try { localStorage.setItem(PACK_AT, String(Date.now())); } catch (err) {}
          });
        })
        .catch(function () {});
    }, 2000);
  });
})();
