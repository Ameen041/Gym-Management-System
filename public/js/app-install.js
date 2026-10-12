// Registers the service worker and wires up any [data-install-app] buttons.
(function () {
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js').catch(function () {});
    });
  }

  var deferredPrompt = null;
  var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);

  function buttons() {
    return document.querySelectorAll('[data-install-app]');
  }

  function show(visible) {
    buttons().forEach(function (btn) { btn.hidden = !visible; });
  }

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    show(true);
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    show(false);
  });

  document.addEventListener('DOMContentLoaded', function () {
    // iOS Safari has no install prompt: show the button with manual instructions.
    if (isIos && !isStandalone) show(true);

    buttons().forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (deferredPrompt) {
          deferredPrompt.prompt();
          deferredPrompt.userChoice.finally(function () {
            deferredPrompt = null;
            show(false);
          });
        } else if (isIos) {
          alert('To install Ameen Gym: tap the Share button, then "Add to Home Screen".\n\nلتثبيت التطبيق: اضغط زر المشاركة ثم "إضافة إلى الشاشة الرئيسية".');
        }
      });
    });
  });
})();
