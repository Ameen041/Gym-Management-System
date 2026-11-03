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
  // inapp: a link opened inside Instagram, Facebook, TikTok…, whose browsers cannot install apps
  var platform = /Instagram|FBAN|FBAV|FB_IAB|Snapchat|TikTok|musical_ly|Line\//i.test(ua) ? 'inapp'
    : /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1) ? 'ios'
    : /android/i.test(ua) ? 'android' : 'desktop';
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

  function install() {
    deferredPrompt.prompt();
    deferredPrompt.userChoice.finally(function () { deferredPrompt = null; showReady(); });
  }

  // The window offers "Install now" whenever the browser's own prompt is there (even if it
  // only arrives while the window is open: Chrome waits until the visitor has used the site a little)
  function showReady() {
    var ready = document.querySelector('[data-install-ready]');
    if (ready) ready.hidden = !deferredPrompt;
  }

  function howTo() {
    var dialog = document.querySelector('[data-install-help]');
    if (!dialog) return;
    dialog.querySelectorAll('[data-platform]').forEach(function (list) { list.hidden = list.dataset.platform !== platform; });
    showReady();
    if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', '');
  }

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    showReady();
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    installed = true;
    refresh();
  });

  document.addEventListener('DOMContentLoaded', function () {
    refresh();

    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-install-now]')) {
        var dialog = document.querySelector('[data-install-help]');
        if (dialog && dialog.open) dialog.close();
        if (deferredPrompt) install();
      } else if (e.target.closest('[data-install-app]')) {
        if (deferredPrompt) install(); else howTo();
      } else if (e.target.closest('[data-install-dismiss]')) {
        try { localStorage.setItem(LATER, String(Date.now())); } catch (err) { /* private mode */ }
        refresh();
      }
    });
  });
})();
