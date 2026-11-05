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

/* Photo viewer: a[data-zoom] links to a photo open it over the page instead of in a new window.
 * In the installed app a new window has no way back, so "back" closed the whole app. Here the back
 * button (or ✕, Esc, a tap outside the photo) closes the photo and stays on the page.
 * Links with data-zoom in the same parent are one group: arrows and swipes go through them. */
(function () {
  "use strict";
  var rtl = document.documentElement.dir === "rtl";
  var ar = document.documentElement.lang === "ar";
  var box, img, count, prevBtn, nextBtn, group = [], index = 0, open = false;

  function build() {
    box = document.createElement("div");
    box.className = "zoom";
    box.hidden = true;
    box.setAttribute("role", "dialog");
    box.setAttribute("aria-modal", "true");
    box.innerHTML =
      '<button type="button" class="zoom-btn zoom-close" aria-label="' + (ar ? "إغلاق" : "Close") + '"><i class="fas fa-xmark"></i></button>' +
      '<button type="button" class="zoom-btn zoom-prev" aria-label="' + (ar ? "السابقة" : "Previous") + '"><i class="fas fa-chevron-' + (rtl ? "right" : "left") + '"></i></button>' +
      '<img alt="">' +
      '<button type="button" class="zoom-btn zoom-next" aria-label="' + (ar ? "التالية" : "Next") + '"><i class="fas fa-chevron-' + (rtl ? "left" : "right") + '"></i></button>' +
      '<div class="zoom-count" dir="ltr"></div>';
    document.body.appendChild(box);
    img = box.querySelector("img");
    count = box.querySelector(".zoom-count");
    prevBtn = box.querySelector(".zoom-prev");
    nextBtn = box.querySelector(".zoom-next");

    box.addEventListener("click", function (e) {
      if (e.target.closest(".zoom-prev")) return show(index - 1);
      if (e.target.closest(".zoom-next")) return show(index + 1);
      if (e.target === img) return;
      close();   // ✕ or the dark area around the photo
    });

    var startX = null, startY = null;
    box.addEventListener("touchstart", function (e) {
      if (e.touches.length !== 1) { startX = null; return; }
      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
    }, { passive: true });
    box.addEventListener("touchend", function (e) {
      if (startX === null || group.length < 2) return;
      var dx = e.changedTouches[0].clientX - startX, dy = e.changedTouches[0].clientY - startY;
      startX = null;
      if (Math.abs(dx) < 50 || Math.abs(dx) < Math.abs(dy)) return;
      // the next photo comes from the side you read towards
      show(index + ((dx < 0) !== rtl ? 1 : -1));
    });
  }

  function show(i) {
    index = (i + group.length) % group.length;
    var link = group[index];
    var thumb = link.querySelector("img");
    img.src = link.href;
    img.alt = thumb ? thumb.alt : "";
    var many = group.length > 1;
    prevBtn.hidden = nextBtn.hidden = count.hidden = !many;
    count.textContent = (index + 1) + " / " + group.length;
  }

  function openFrom(link) {
    if (!box) build();
    var parent = link.parentElement;
    group = Array.prototype.filter.call(parent.querySelectorAll("a[data-zoom]"), function (a) { return a.parentElement === parent; });
    if (group.indexOf(link) < 0) group = [link];
    show(group.indexOf(link));
    box.hidden = false;
    document.documentElement.classList.add("zoom-open");
    if (!open) {
      open = true;
      try { history.pushState({ zoom: true }, ""); } catch (e) {}
    }
    box.querySelector(".zoom-close").focus({ preventScroll: true });
  }

  function hide() {
    open = false;
    if (!box) return;
    box.hidden = true;
    img.removeAttribute("src");
    document.documentElement.classList.remove("zoom-open");
  }

  // closing goes back in history, so the entry added on open is used up and "back" works as before
  function close() {
    if (history.state && history.state.zoom) history.back(); else hide();
  }

  window.addEventListener("popstate", function () { if (open) hide(); });

  // a reload while a photo was open leaves its history entry behind: nothing to show for it
  if (history.state && history.state.zoom) {
    try { history.replaceState(null, ""); } catch (e) {}
  }

  document.addEventListener("click", function (e) {
    var link = e.target.closest && e.target.closest("a[data-zoom]");
    if (!link || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
    e.preventDefault();
    openFrom(link);
  });

  document.addEventListener("keydown", function (e) {
    if (!open) return;
    if (e.key === "Escape") close();
    else if (e.key === "ArrowRight" && group.length > 1) show(index + (rtl ? -1 : 1));
    else if (e.key === "ArrowLeft" && group.length > 1) show(index + (rtl ? 1 : -1));
  });
})();

/* Exercise page: the drawings of the start and end position take turns, like a short looping clip.
 * [data-demo] holds the pictures; Pause stops it, and Start / End show one position.
 * With "reduce motion" turned on in the phone, it starts paused. */
(function () {
  "use strict";
  document.querySelectorAll("[data-demo]").forEach(function (demo) {
    var pics = demo.querySelectorAll(".ex-demo-stage img");
    var steps = demo.querySelectorAll("[data-demo-step]");
    var play = demo.querySelector("[data-demo-play]");
    if (pics.length < 2 || !play) return;
    var index = 0, timer = null;
    var label = play.querySelector("span"), icon = play.querySelector("i");
    var pauseText = label.textContent, playText = play.getAttribute("data-play-text") || "▶";

    function show(i) {
      index = (i + pics.length) % pics.length;
      pics.forEach(function (p, n) { p.classList.toggle("on", n === index); });
      steps.forEach(function (b, n) { b.classList.toggle("on", n === index); });
    }
    function run(on) {
      clearInterval(timer);
      timer = on ? setInterval(function () { show(index + 1); }, 1300) : null;
      play.setAttribute("aria-pressed", on ? "true" : "false");
      icon.className = on ? "fas fa-pause" : "fas fa-play";
      label.textContent = on ? pauseText : playText;
    }

    play.addEventListener("click", function () { run(!timer); });
    steps.forEach(function (b) {
      b.addEventListener("click", function () { run(false); show(Number(b.getAttribute("data-demo-step"))); });
    });
    // the next picture is loaded before its turn
    pics.forEach(function (p) { p.loading = "eager"; });
    run(!window.matchMedia("(prefers-reduced-motion: reduce)").matches);
  });
})();

/* Big uploads (exercise videos): form[data-upload] is sent in the background with a progress bar,
 * so the admin sees it moving on a slow connection. The server answers JSON: {redirect} when it is
 * saved, or the errors to show in [data-upload-error]. */
(function () {
  "use strict";
  if (!window.FormData || !window.XMLHttpRequest) return;
  var ar = document.documentElement.lang === "ar";

  document.addEventListener("submit", function (e) {
    var form = e.target;
    if (!form.matches || !form.matches("form[data-upload]") || e.defaultPrevented) return;
    e.preventDefault();

    var bar = form.querySelector("[data-upload-progress]");
    var fill = bar && bar.querySelector("span");
    var box = form.querySelector("[data-upload-error]");
    var buttons = form.querySelectorAll('button[type="submit"], button:not([type])');
    function busy(on) { buttons.forEach(function (b) { b.disabled = on; }); }
    function error(text) {
      busy(false);
      if (bar) bar.hidden = true;
      if (box) { box.hidden = false; box.textContent = text; } else { alert(text); }
    }

    var xhr = new XMLHttpRequest();
    xhr.open("POST", form.action);
    xhr.setRequestHeader("Accept", "application/json");
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.upload.onprogress = function (ev) {
      if (!bar || !ev.lengthComputable) return;
      var pct = Math.round(ev.loaded / ev.total * 100);
      bar.hidden = false;
      fill.style.width = pct + "%";
      bar.setAttribute("data-label", pct < 100 ? pct + "%" : (ar ? "جارٍ الحفظ…" : "Saving…"));
    };
    xhr.onload = function () {
      var data = {};
      try { data = JSON.parse(xhr.responseText); } catch (err) {}
      if (xhr.status >= 200 && xhr.status < 300 && data.redirect) {
        window.location.href = data.redirect;
      } else if (xhr.status === 422 && data.errors) {
        error(Object.keys(data.errors).map(function (k) { return data.errors[k][0]; }).join("\n"));
      } else if (xhr.status === 413) {
        error(ar ? "الملف أكبر مما يقبله السيرفر. صوّر فيديو أقصر أو بجودة أقل (720p)." : "The file is bigger than the server accepts. Film a shorter video, or at a lower quality (720p).");
      } else {
        error((ar ? "لم يُحفظ. حاول مرة أخرى." : "It was not saved. Please try again.") + " (" + xhr.status + ")");
      }
    };
    xhr.onerror = function () { error(ar ? "انقطع الاتصال. حاول مرة أخرى." : "The connection dropped. Please try again."); };

    if (box) box.hidden = true;
    if (bar) { bar.hidden = false; fill.style.width = "0"; bar.setAttribute("data-label", "0%"); }
    busy(true);
    xhr.send(new FormData(form));
  });
})();
