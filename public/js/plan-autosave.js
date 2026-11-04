/* =========================================================
   Plan builders: the form is saved as a draft while the coach works
   - a moment after each change (typing, adding, moving, removing rows)
   - right away when the page is left or hidden (sendBeacon), and kept on the device too:
     a beacon is limited to 64 KB, a full week of meals can be more. On the next visit
     what the server did not get is sent, then shown.
   The trainee sees nothing until "Send"; the draft comes back when the coach returns.
   ========================================================= */
(function () {
  "use strict";

  const form = document.querySelector("form[data-autosave]");
  if (!form) return;
  const url = form.dataset.autosave;
  const status = form.querySelector("[data-autosave-status]");
  const WAIT = 1000; // ms after the last change
  const RETRY = 5000;
  // server time (ms), so a copy kept on the device compares with the server's dates whatever the phone's clock says
  const skew = Date.now() - Number(form.dataset.now || Date.now());
  const serverNow = () => Date.now() - skew;
  const KEY = "himma-draft:" + url;
  const kept = {
    read() { try { return JSON.parse(localStorage.getItem(KEY)); } catch (e) { return null; } },
    write(data) { try { localStorage.setItem(KEY, JSON.stringify({ at: serverNow(), data: data })); } catch (e) { /* full or private mode */ } },
    clear() { try { localStorage.removeItem(KEY); } catch (e) { /* private mode */ } },
  };

  // the fields as they would be sent, without the CSRF token
  const snapshot = () => {
    const data = new FormData(form);
    data.delete("_token");
    return new URLSearchParams(data).toString();
  };

  let saved = null; // what the server has
  let timer = 0;
  let busy = false;
  let again = false;
  let sending = false; // the plan itself is being sent

  function show(state) {
    if (!status || !status.dataset[state]) return;
    status.textContent = status.dataset[state];
    status.classList.toggle("is-saved", state === "saved");
    status.classList.toggle("is-failed", state === "failed");
  }

  function schedule(delay) {
    if (saved === null || sending) return; // not ready yet, or leaving with the real send
    clearTimeout(timer);
    timer = setTimeout(save, delay === undefined ? WAIT : delay);
  }

  function save() {
    const now = snapshot();
    if (now === saved) return;
    if (busy) { again = true; return; }
    busy = true;
    show("saving");
    fetch(url, {
      method: "POST",
      body: new FormData(form),
      credentials: "same-origin",
      headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
    })
      .then((res) => {
        if (!res.ok) throw new Error(res.status);
        saved = now;
        kept.clear();
        show("saved");
      })
      .catch(() => {
        show("failed");
        schedule(RETRY);
      })
      .finally(() => {
        busy = false;
        if (again) { again = false; schedule(0); }
      });
  }

  // leaving the page (another link, back, closing the app): send what is not saved yet
  function flush() {
    if (saved === null || sending) return;
    const now = snapshot();
    if (now === saved) return;
    clearTimeout(timer);
    kept.write(now);
    if (navigator.sendBeacon) navigator.sendBeacon(url, new FormData(form));
  }

  form.addEventListener("input", () => schedule());
  form.addEventListener("change", () => schedule());
  // rows added, moved or removed by the builder
  new MutationObserver(() => schedule()).observe(form, { childList: true, subtree: true });

  // sending the plan, or going back to the sent plan: nothing more to keep
  const done = (e) => {
    if (e.defaultPrevented) return; // the coach answered "no" to a question first
    sending = true;
    clearTimeout(timer);
    kept.clear();
  };
  form.addEventListener("submit", done);
  const discard = document.getElementById("discard-draft");
  if (discard) discard.addEventListener("submit", done);
  window.addEventListener("pagehide", flush);
  document.addEventListener("visibilitychange", () => { if (document.visibilityState === "hidden") flush(); });

  // start from the form as the page shows it: this script runs after the builder has drawn its rows,
  // so a change made in the first instant already counts
  saved = snapshot();

  // the last changes stayed on this device (the page was left before they reached the server): send them, then show them
  const last = kept.read();
  if (last && last.data !== saved && last.at > Number(form.dataset.savedAt || 0)) {
    const body = new URLSearchParams(last.data);
    body.append("_token", form.querySelector('[name="_token"]').value);
    show("saving");
    fetch(url, { method: "POST", body: body, credentials: "same-origin", headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" } })
      .then((res) => {
        if (!res.ok) throw new Error(res.status);
        kept.clear();
        location.reload();
      })
      .catch(() => show("failed"));
  } else if (last) {
    kept.clear();
  }
})();
