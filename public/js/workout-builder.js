/* =========================================================
   Workout plan builder (coach)
   - one tab per day, exercises listed as rows (sets × reps)
   - "Add exercise" opens a picker: search (Arabic / English),
     filter by muscle and equipment, tap to add
   - form fields stay day[] / exercise_id[] / exercise[] / muscle[] / sets[] / reps[]
   ========================================================= */
(function () {
  "use strict";

  const WB = window.WB;
  const form = document.getElementById("workout-form");
  if (!form || !WB) return;
  const t = WB.i18n;

  // ---------- helpers ----------
  const norm = (s) => String(s || "").toLowerCase()
    .replace(/[ً-ْـ]/g, "")   // tashkeel, tatweel
    .replace(/[أإآٱ]/g, "ا").replace(/ة/g, "ه").replace(/ى/g, "ي").replace(/ؤ/g, "و").replace(/ئ/g, "ي")
    .replace(/[-_.,()/]/g, " ").replace(/\s+/g, " ").trim();
  const esc = (s) => String(s == null ? "" : s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const el = (html) => { const box = document.createElement("div"); box.innerHTML = html.trim(); return box.firstElementChild; };

  WB.library.forEach((e) => (e.key = norm([e.n, e.en, e.ar, e.mt, e.et].join(" "))));
  const byId = new Map(WB.library.map((e) => [String(e.id), e]));
  const byName = new Map();
  WB.library.forEach((e) => [e.n, e.en, e.ar].forEach((n) => n && !byName.has(norm(n)) && byName.set(norm(n), e)));
  const muscleKeyByLabel = new Map(Object.entries(WB.muscles).map(([k, m]) => [m.label.toLowerCase(), k]));

  const days = Array.from(form.querySelectorAll(".builder-day"));
  const dayEl = (d) => form.querySelector(`.builder-day[data-day="${d}"]`);
  let activeDay = form.dataset.firstDay || days[0].dataset.day;

  const templateRow = el(document.getElementById("wb-row-template").innerHTML);
  const SR_HTML = templateRow.querySelector(".wb-sr").outerHTML;
  const ACTIONS_HTML = templateRow.querySelector(".wb-actions").outerHTML;

  const countText = (n) => (n === 1 ? t.oneExercise : t.exercises.replace(":count", n));

  // ---------- rows ----------
  function setSR(row, sets, reps) {
    row.querySelector('[name="sets[]"]').value = sets;
    row.querySelector('[name="reps[]"]').value = reps;
  }

  function libraryRow(day, ex, sets, reps) {
    const row = el(`<div class="wb-row" data-row>
      <input type="hidden" name="day[]" value="${esc(day)}" data-day-input>
      <input type="hidden" name="exercise_id[]" value="${ex.id}" data-ex-id>
      <span class="wb-order" data-order></span>
      <span class="pick-icon">${ex.t ? `<img src="${esc(ex.t)}" alt="" loading="lazy">` : '<i class="fas fa-dumbbell"></i>'}</span>
      <div class="wb-info">
        <input type="hidden" name="exercise[]" value="${esc(ex.n)}">
        <input type="hidden" name="muscle[]" value="${esc(ex.ml)}" data-muscle>
        <strong class="wb-name">${esc(ex.n)}</strong>
        <span class="wb-meta">${esc(ex.mt)} · ${esc(ex.et)}${ex.v ? ' · <i class="fas fa-circle-play"></i>' : ""}</span>
      </div>
      ${SR_HTML}${ACTIONS_HTML}
    </div>`);
    const cardio = ex.m === "cardio";
    setSR(row, sets || (cardio ? WB.defaults.cardioSets : WB.defaults.sets), reps || (cardio ? WB.defaults.cardioReps : WB.defaults.reps));
    return row;
  }

  function customRow(day, name, muscleLabel, sets, reps) {
    const row = el(document.getElementById("wb-row-template").innerHTML.split("__DAY__").join(esc(day)));
    row.querySelector('[name="exercise[]"]').value = name || "";
    const select = row.querySelector("select[data-muscle]");
    const match = Array.from(select.options).find((o) => o.value.toLowerCase() === String(muscleLabel || "").toLowerCase());
    select.value = match ? match.value : "";
    setSR(row, sets || WB.defaults.sets, reps || WB.defaults.reps);
    return row;
  }

  function addRow(day, row) {
    dayEl(day).querySelector("[data-list]").appendChild(row);
    refresh();
  }

  // ---------- tabs & summary ----------
  function showDay(day) {
    activeDay = day;
    days.forEach((s) => (s.hidden = s.dataset.day !== day));
    form.querySelectorAll("[data-tab]").forEach((b) => {
      const on = b.dataset.tab === day;
      b.classList.toggle("on", on);
      b.setAttribute("aria-selected", on ? "true" : "false");
    });
    refresh();
  }

  function refresh() {
    let total = 0, used = 0;
    days.forEach((s) => {
      const rows = s.querySelectorAll("[data-row]");
      total += rows.length;
      if (rows.length) used++;
      s.classList.toggle("has-rows", rows.length > 0);
      rows.forEach((r, i) => (r.querySelector("[data-order]").textContent = i + 1));
      const tab = form.querySelector(`[data-tab="${s.dataset.day}"]`);
      tab.querySelector("[data-tab-count]").textContent = rows.length || "–";
      tab.classList.toggle("has", rows.length > 0);
    });
    const current = dayEl(activeDay);
    const n = current.querySelectorAll("[data-row]").length;
    form.querySelector("[data-dock-day]").textContent = `${current.dataset.dayLabel}: ${n ? countText(n) : t.rest}`;
    form.querySelector("[data-dock-week]").textContent = t.week.replace(":count", total).replace(":days", used);
    if (picker.classList.contains("open")) renderResults();
  }

  // ---------- picker ----------
  const picker = document.getElementById("picker");
  const backdrop = picker.nextElementSibling;
  const results = picker.querySelector("[data-results]");
  const searchInput = picker.querySelector("[data-search]");
  let fMuscle = "", fEq = "", fQuery = "";

  function setChip(group, attr, value) {
    picker.querySelectorAll(`[${attr}]`).forEach((c) => c.classList.toggle("on", c.getAttribute(attr) === value && (value !== "" || attr === "data-muscle")));
  }

  function openPicker() {
    picker.querySelector("[data-picker-day]").textContent = dayEl(activeDay).dataset.dayLabel;
    // start on the muscle of the last exercise of the day
    const last = dayEl(activeDay).querySelector("[data-row]:last-child [data-muscle]");
    fMuscle = last ? (muscleKeyByLabel.get(String(last.value).toLowerCase()) || "") : "";
    if (!picker.querySelector(`[data-muscle="${fMuscle}"]`)) fMuscle = "";
    setChip("m", "data-muscle", fMuscle);
    picker.classList.add("open");
    backdrop.classList.add("show");
    picker.setAttribute("aria-hidden", "false");
    renderResults();
    if (window.matchMedia("(min-width: 641px)").matches) searchInput.focus();
  }

  function closePicker() {
    picker.classList.remove("open");
    backdrop.classList.remove("show");
    picker.setAttribute("aria-hidden", "true");
  }

  function renderResults() {
    const inDay = new Set(Array.from(dayEl(activeDay).querySelectorAll("[data-ex-id]")).map((i) => i.value).filter(Boolean));
    const tokens = norm(fQuery).split(" ").filter(Boolean);
    const list = WB.library.filter((e) =>
      (!fMuscle || e.m === fMuscle) && (!fEq || e.e === fEq) && tokens.every((tk) => e.key.includes(tk)));

    let html = list.map((e) => `
      <button type="button" class="pick ${inDay.has(String(e.id)) ? "added" : ""}" data-id="${e.id}">
        <span class="pick-icon">${e.t ? `<img src="${esc(e.t)}" alt="" loading="lazy">` : '<i class="fas fa-dumbbell"></i>'}</span>
        <span><span class="pick-name">${esc(e.n)}</span>
          <span class="pick-meta">${esc(e.mt)} · ${esc(e.et)}${e.v ? ` · <i class="fas fa-circle-play"></i> ${esc(t.video)}` : ""}${inDay.has(String(e.id)) ? ` · <span class="in-day">${esc(t.inDay)}</span>` : ""}</span></span>
        <span class="pick-add"><i class="fas ${inDay.has(String(e.id)) ? "fa-check" : "fa-plus"}"></i></span>
      </button>`).join("");

    if (!list.length) html = `<p class="pick-empty">${esc(t.noResults)}</p>`;
    if (fQuery.trim() && !byName.has(norm(fQuery))) {
      html += `<button type="button" class="pick pick-custom" data-custom>
        <span class="pick-icon"><i class="fas fa-pen"></i></span>
        <span><span class="pick-name">${esc(t.addCustom.replace(":name", fQuery.trim()))}</span></span>
        <span class="pick-add"><i class="fas fa-plus"></i></span></button>`;
    }
    results.innerHTML = html;
    const n = dayEl(activeDay).querySelectorAll("[data-row]").length;
    picker.querySelector("[data-picker-count]").textContent = `${dayEl(activeDay).dataset.dayLabel}: ${n ? countText(n) : t.rest}`;
  }

  picker.addEventListener("click", (e) => {
    const muscle = e.target.closest("[data-muscle]");
    const eq = e.target.closest("[data-eq]");
    const pick = e.target.closest(".pick[data-id]");
    const custom = e.target.closest("[data-custom]");
    if (muscle && muscle.tagName === "BUTTON") {
      fMuscle = muscle.dataset.muscle;
      setChip("m", "data-muscle", fMuscle);
      renderResults();
    } else if (eq) {
      fEq = fEq === eq.dataset.eq ? "" : eq.dataset.eq;
      picker.querySelectorAll("[data-eq]").forEach((c) => c.classList.toggle("on", c.dataset.eq === fEq));
      renderResults();
    } else if (pick) {
      addRow(activeDay, libraryRow(activeDay, byId.get(pick.dataset.id)));
    } else if (custom) {
      const label = fMuscle && WB.muscles[fMuscle] ? WB.muscles[fMuscle].label : "";
      addRow(activeDay, customRow(activeDay, fQuery.trim(), label));
      fQuery = ""; searchInput.value = "";
      renderResults();
    }
  });
  searchInput.addEventListener("input", () => {
    fQuery = searchInput.value;
    // typing searches every muscle
    if (fQuery.trim() && fMuscle) { fMuscle = ""; setChip("m", "data-muscle", ""); }
    renderResults();
  });
  document.querySelectorAll("[data-close-picker]").forEach((b) => b.addEventListener("click", closePicker));
  document.addEventListener("keydown", (e) => { if (e.key === "Escape" && picker.classList.contains("open")) closePicker(); });

  // ---------- form events ----------
  form.addEventListener("click", (e) => {
    const tab = e.target.closest("[data-tab]");
    const move = e.target.closest("[data-move]");
    const remove = e.target.closest("[data-remove]");
    if (tab) return showDay(tab.dataset.tab);
    if (e.target.closest("[data-open-picker]")) return openPicker();
    if (move) {
      const row = move.closest("[data-row]");
      const sibling = move.dataset.move === "-1" ? row.previousElementSibling : row.nextElementSibling;
      if (sibling) move.dataset.move === "-1" ? sibling.before(row) : sibling.after(row);
      return refresh();
    }
    if (remove) { remove.closest("[data-row]").remove(); refresh(); }
  });

  form.addEventListener("change", (e) => {
    if (e.target.matches("[data-copy]")) {
      const target = e.target.value;
      e.target.value = "";
      if (!target) return;
      const source = dayEl(activeDay);
      const label = target === "*" ? t.allDays : dayEl(target).dataset.dayLabel;
      if (!confirm(t.copyConfirm.replace(":target", label).replace(":day", source.dataset.dayLabel))) return;
      const rows = Array.from(source.querySelectorAll("[data-row]"));
      days.filter((d) => d !== source && (target === "*" || d.dataset.day === target)).forEach((d) => {
        const list = d.querySelector("[data-list]");
        list.innerHTML = "";
        rows.forEach((row) => {
          const copy = row.cloneNode(true);
          copy.querySelector("[data-day-input]").value = d.dataset.day;
          const fromSelects = row.querySelectorAll("select");
          copy.querySelectorAll("select").forEach((s, i) => (s.value = fromSelects[i].value));
          list.appendChild(copy);
        });
      });
      refresh();
    }

    if (e.target.matches("[data-template]")) {
      const option = e.target.options[e.target.selectedIndex];
      const body = option ? option.getAttribute("data-body") : "";
      e.target.value = "";
      if (!body) return;
      if (form.querySelector("[data-row]") && !confirm(t.templateConfirm)) return;
      days.forEach((d) => (d.querySelector("[data-list]").innerHTML = ""));
      body.split("\n").map((l) => l.trim()).filter(Boolean).forEach((line) => {
        const [day, muscle, exercise, sets, reps] = line.split("|").map((p) => p.trim());
        if (!dayEl(day) || !exercise) return;
        const ex = byName.get(norm(exercise));
        dayEl(day).querySelector("[data-list]").appendChild(ex ? libraryRow(day, ex, sets, reps) : customRow(day, exercise, muscle, sets, reps));
      });
      const first = days.find((d) => d.querySelector("[data-row]"));
      showDay(first ? first.dataset.day : activeDay);
    }
  });

  showDay(activeDay);
})();
