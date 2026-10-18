/* Phone notifications (Web Push).
   A [data-push-card] asks once; a device that said yes stays subscribed for whoever is logged in,
   and logging out turns it off on that device. */
(function () {
  "use strict";
  var meta = function (name) { var m = document.querySelector('meta[name="' + name + '"]'); return m ? m.content : ""; };
  var key = meta("push-key");
  if (!key || !("serviceWorker" in navigator) || !("PushManager" in window) || !("Notification" in window)) return;

  var LATER_DAYS = 7;
  var cards = document.querySelectorAll("[data-push-card]");

  function bytes(b64) {
    var raw = atob((b64 + "===".slice((b64.length + 3) % 4)).replace(/-/g, "+").replace(/_/g, "/"));
    var out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
  }
  function b64(buffer) {
    var s = String.fromCharCode.apply(null, new Uint8Array(buffer));
    return btoa(s).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
  }
  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) {} return null; }
  function show(on) { cards.forEach(function (c) { c.hidden = !on; }); }

  function save(sub) {
    var stamp = meta("push-user") + "|" + sub.endpoint + "|" + new Date().toDateString();
    if (store("push-saved") === stamp) return Promise.resolve();
    return fetch(meta("push-save"), {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": meta("csrf-token") },
      credentials: "same-origin",
      body: JSON.stringify(sub.toJSON())
    }).then(function (r) { if (r.ok) store("push-saved", stamp); });
  }

  function subscribe(reg) {
    return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: bytes(key) }).then(save);
  }

  navigator.serviceWorker.ready.then(function (reg) {
    return reg.pushManager.getSubscription().then(function (sub) {
      if (sub) {
        // made with another key (the server was moved): subscribe again
        var used = sub.options && sub.options.applicationServerKey;
        if (used && b64(used) !== key) return sub.unsubscribe().then(function () { return subscribe(reg); });
        return save(sub);
      }
      if (Notification.permission === "granted") return subscribe(reg);
      var later = +store("push-later") || 0;
      if (Notification.permission === "default" && Date.now() - later > LATER_DAYS * 864e5) show(true);
    });
  }).catch(function () {});

  document.addEventListener("click", function (e) {
    if (e.target.closest("[data-push-on]")) {
      show(false);
      Promise.resolve(Notification.requestPermission()).then(function (permission) {
        if (permission === "granted") return navigator.serviceWorker.ready.then(subscribe);
      }).catch(function () {});
    } else if (e.target.closest("[data-push-later]")) {
      store("push-later", String(Date.now()));
      show(false);
    }
  });

  // Logging out: this device stops getting the account's notifications
  document.addEventListener("submit", function (e) {
    var form = e.target;
    if (!/\/logout$/.test(form.getAttribute("action") || "") || form.getAttribute("data-push-off")) return;
    e.preventDefault();
    form.setAttribute("data-push-off", "1");
    var done = false;
    var go = function () { if (!done) { done = true; form.submit(); } };
    setTimeout(go, 1500);
    navigator.serviceWorker.getRegistration()
      .then(function (reg) { return reg && reg.pushManager.getSubscription(); })
      .then(function (sub) { store("push-saved", ""); return sub && sub.unsubscribe(); })
      .then(go, go);
  }, true);
})();
