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

/* Photo uploads from phones.
 * - input[type=file][data-shrink]: camera photos are 5–15 MB each. They are made smaller in the browser
 *   (longest side 1600px, or the number in data-shrink) before they are sent, so they pass the size limit
 *   and reach the server fast on a slow connection. If the browser can't read a photo it is sent as it is.
 * - form[data-busy]: once sent, the button shows the data-busy text and can't be pressed twice. */
(function () {
  "use strict";

  var canShrink = false;
  try { canShrink = !!(window.File && window.URL && new window.DataTransfer() && HTMLCanvasElement.prototype.toBlob); } catch (e) {}
  var KEEP = /^image\/(jpeg|png|webp)$/i;   // types the server takes as they are

  function shrink(file, maxSide) {
    if (!/^image\//i.test(file.type) && !/\.(hei[cf])$/i.test(file.name)) return Promise.resolve(file);
    return new Promise(function (resolve) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
      img.onload = function () {
        var w = img.naturalWidth, h = img.naturalHeight;
        var scale = Math.min(1, maxSide / Math.max(w, h));
        if (!w || !h || (scale === 1 && file.size <= 800 * 1024 && KEEP.test(file.type))) {
          URL.revokeObjectURL(url);
          return resolve(file);
        }
        var canvas = document.createElement("canvas");
        canvas.width = Math.max(1, Math.round(w * scale));
        canvas.height = Math.max(1, Math.round(h * scale));
        var ctx = canvas.getContext("2d");
        ctx.fillStyle = "#fff";   // transparent parts of a PNG would turn black in a JPEG
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(url);
        canvas.toBlob(function (blob) {
          if (!blob || (blob.size >= file.size && KEEP.test(file.type))) return resolve(file);
          var name = (file.name || "photo").replace(/\.[^.]*$/, "") + ".jpg";
          resolve(new File([blob], name, { type: "image/jpeg", lastModified: Date.now() }));
        }, "image/jpeg", 0.85);
      };
      img.src = url;
    });
  }

  // forms wait for photos that are still being made smaller
  var pending = new WeakMap();

  if (canShrink) {
    document.addEventListener("change", function (e) {
      var input = e.target;
      if (!input.matches || !input.matches('input[type="file"][data-shrink]') || !input.files || !input.files.length) return;
      var maxSide = parseInt(input.getAttribute("data-shrink"), 10) || 1600;
      var files = Array.prototype.slice.call(input.files);
      var job = Promise.all(files.map(function (f) { return shrink(f, maxSide); })).then(function (out) {
        if (input._shrinkJob !== job) return;   // another pick replaced these photos
        if (out.some(function (f, i) { return f !== files[i]; })) {
          var list = new DataTransfer();
          out.forEach(function (f) { list.items.add(f); });
          input.files = list.files;
        }
      }).catch(function () {});
      input._shrinkJob = job;
      if (input.form) {
        var jobs = pending.get(input.form) || [];
        jobs.push(job);
        pending.set(input.form, jobs);
        job.then(function () {
          var left = (pending.get(input.form) || []).filter(function (j) { return j !== job; });
          pending.set(input.form, left);
        });
      }
    });
  }

  function busy(form, on) {
    var text = form.getAttribute("data-busy");
    form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (btn) {
      if (on) {
        if (btn._idle === undefined) btn._idle = btn.innerHTML;
        btn.disabled = true;
        if (text) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + text.replace(/[&<>"]/g, function (c) {
          return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c];
        });
      } else if (btn._idle !== undefined) {
        btn.disabled = false;
        btn.innerHTML = btn._idle;
      }
    });
  }

  document.addEventListener("submit", function (e) {
    var form = e.target;
    if (e.defaultPrevented) return;
    var jobs = pending.get(form) || [];
    if (jobs.length) {
      // still making the photos smaller: send as soon as they are ready
      e.preventDefault();
      busy(form, true);
      Promise.all(jobs).then(function () {
        pending.set(form, []);
        busy(form, false);
        if (form.requestSubmit) form.requestSubmit(); else form.submit();
      });
      return;
    }
    if (form.hasAttribute("data-busy")) {
      // after the browser took the form, so the button still counts as pressed
      setTimeout(function () { busy(form, true); }, 0);
    }
  });

  // back button: the page comes back from the cache with the button still turned off
  window.addEventListener("pageshow", function (e) {
    if (e.persisted) document.querySelectorAll("form[data-busy]").forEach(function (f) { busy(f, false); });
  });
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

// Admin tables on phones become cards (app.css): each cell gets its column's name to show beside the value
(function () {
  document.querySelectorAll('.table-wrapper .table').forEach(function (table) {
    var names = Array.prototype.map.call(table.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
    table.querySelectorAll('tbody tr').forEach(function (row) {
      Array.prototype.forEach.call(row.children, function (cell, i) {
        if (!cell.hasAttribute('data-label')) cell.setAttribute('data-label', names[i] || '');
        // nothing to show (e.g. no actions for this row): the phone card skips the line
        if (!cell.textContent.trim() && !cell.querySelector('img, a, button, input, select, form')) cell.classList.add('cell-empty');
      });
    });
  });
})();

// The side menu (language, logout…) slides in from the ☰ button on small screens
(function () {
  if (!document.querySelector('.side')) return;
  var setOpen = function (open) { document.body.classList.toggle('side-open', open); };
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-side-toggle]')) setOpen(!document.body.classList.contains('side-open'));
    else if (e.target.closest('[data-side-close]')) setOpen(false);
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
})();
