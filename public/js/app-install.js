// Registers the service worker and wires up the "install the app" buttons, the bar at the
// bottom of phones and the how-to window (partials/install-app).
(function () {
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js').catch(function () {});
    });
  }

  var deferredPrompt = null;
  var installed = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  var ua = navigator.userAgent;
  var platform = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
    ? 'ios' : (/android/i.test(ua) ? 'android' : 'desktop');
  var LATER = 'himma-install-later'; // when the phone bar was closed; it comes back after two weeks

  function closedRecently() {
    try {
      var at = Number(localStorage.getItem(LATER));
      return at > 0 && Date.now() - at < 14 * 24 * 3600 * 1000;
    } catch (e) { return false; }
  }

  // The buttons show everywhere until the app is installed: the browser's own prompt can come
  // late or never (iPhone, some browsers), and then the button explains how instead.
  function refresh() {
    document.querySelectorAll('[data-install-app]').forEach(function (btn) { btn.hidden = installed; });
    var bar = document.querySelector('[data-install-bar]');
    if (bar) bar.hidden = installed || platform === 'desktop' || closedRecently();
    document.documentElement.classList.toggle('has-install-bar', !!bar && !bar.hidden);
  }

  function howTo() {
    var dialog = document.querySelector('[data-install-help]');
    if (!dialog) return;
    dialog.querySelectorAll('[data-platform]').forEach(function (list) { list.hidden = list.dataset.platform !== platform; });
    if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', '');
  }

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    installed = true;
    refresh();
  });

  document.addEventListener('DOMContentLoaded', function () {
    refresh();

    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-install-app]')) {
        if (deferredPrompt) {
          deferredPrompt.prompt();
          deferredPrompt.userChoice.finally(function () { deferredPrompt = null; });
        } else {
          howTo();
        }
      } else if (e.target.closest('[data-install-dismiss]')) {
        try { localStorage.setItem(LATER, String(Date.now())); } catch (err) { /* private mode */ }
        refresh();
      }
    });
  });
})();
