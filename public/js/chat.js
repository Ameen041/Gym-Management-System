/* Chat page: send without reloading, new messages every few seconds, "seen" ticks, a box that grows as you type. */
(function () {
  "use strict";
  var chat = document.querySelector("[data-chat]");
  if (!chat) return;

  var log = chat.querySelector("[data-chat-log]");
  var form = chat.querySelector("[data-chat-form]");
  var pollUrl = chat.getAttribute("data-poll");
  var lastId = +chat.getAttribute("data-last") || 0;
  var seenId = +chat.getAttribute("data-seen") || 0;
  var EVERY = 4000;
  var busy = false, again = false, timer = null;

  function nearBottom() {
    return window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 160;
  }
  function toBottom() {
    window.scrollTo(0, document.documentElement.scrollHeight);
  }

  function append(html) {
    var tpl = document.createElement("template");
    tpl.innerHTML = html.trim();
    // a message can come back twice (sent here and fetched): keep one
    tpl.content.querySelectorAll(".msg").forEach(function (m) {
      if (document.getElementById(m.id)) m.remove();
    });
    var empty = log.querySelector(".chat-empty");
    if (empty) empty.remove();
    log.appendChild(tpl.content);
  }

  function markSeen() {
    log.querySelectorAll(".msg-mine").forEach(function (m) {
      var tick = m.querySelector(".msg-tick");
      if (tick && !tick.classList.contains("seen") && +m.getAttribute("data-id") <= seenId) {
        tick.classList.remove("fa-check");
        tick.classList.add("fa-check-double", "seen");
        tick.title = tick.getAttribute("data-seen-label");
      }
    });
  }

  function schedule() {
    clearTimeout(timer);
    timer = setTimeout(poll, EVERY);
  }

  function poll() {
    if (busy) { again = true; return; }
    if (document.hidden) { schedule(); return; }
    busy = true;
    fetch(pollUrl + "?after=" + lastId, { headers: { "Accept": "application/json" }, credentials: "same-origin" })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data) return;
        if (data.last_id > lastId) {
          var stick = nearBottom();
          append(data.html);
          lastId = data.last_id;
          if (stick) toBottom();
        }
        seenId = data.seen_id;
        markSeen();
      })
      .catch(function () {})
      .then(function () {
        busy = false;
        if (again) { again = false; poll(); } else schedule();
      });
  }

  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) poll();
  });

  if (form) {
    var box = form.querySelector("textarea");
    var btn = form.querySelector("button[type=submit]");
    var err = form.querySelector("[data-chat-error]");
    var grow = function () {
      box.style.height = "auto";
      box.style.height = Math.min(box.scrollHeight + 2, 168) + "px";
    };
    box.addEventListener("input", grow);
    grow();

    // Enter sends on a computer; on a phone Enter is a new line and the button sends
    box.addEventListener("keydown", function (e) {
      if (e.key === "Enter" && !e.shiftKey && !e.isComposing && window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
        e.preventDefault();
        if (form.requestSubmit) form.requestSubmit(); else btn.click();
      }
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!box.value.trim() || btn.disabled) return;
      btn.disabled = true;
      err.hidden = true;
      fetch(form.action, {
        method: "POST",
        headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
        body: new FormData(form)
      })
        .then(function (r) {
          if (r.status === 419) { location.reload(); return null; }   // the page was open too long
          return r.json().then(function (data) { return { ok: r.ok, data: data }; });
        })
        .then(function (res) {
          if (!res) return;
          if (res.ok) {
            box.value = "";
            grow();
            poll();
            setTimeout(toBottom, 60);
            return;
          }
          var errors = res.data.errors ? Object.keys(res.data.errors).map(function (k) { return res.data.errors[k][0]; }) : [];
          err.textContent = res.data.error || errors[0] || res.data.message || "";
          err.hidden = !err.textContent;
        })
        .catch(function () {
          err.textContent = (window.I18N && window.I18N.chat_failed) || "";
          err.hidden = !err.textContent;
        })
        .then(function () {
          btn.disabled = false;
          box.focus();
        });
    });
  }

  if (!location.hash) toBottom();
  schedule();
})();
