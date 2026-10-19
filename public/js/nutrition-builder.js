/* =========================================================
   Nutrition plan builder (coach)
   - one tab per day; a day has meals, a meal has foods
   - "Add food" opens a picker (search Arabic / English + categories)
   - calories / protein / carbs / fat per food, meal, day and week
     (the server recalculates library foods again when saving)
   - daily targets first: each day shows a bar per value that fills up to the target range
   - form fields: targets[calories|protein|carbs|fat],
     meals[key][day|number|note|items][key][food_id|name|qty|unit|calories|protein|carbs|fat]
   ========================================================= */
(function () {
  "use strict";

  const NB = window.NB;
  const form = document.getElementById("nutrition-form");
  if (!form || !NB) return;
  const t = NB.i18n;
  const MACROS = ["calories", "protein", "carbs", "fat"];

  // ---------- helpers ----------
  const norm = (s) => String(s || "").toLowerCase()
    .replace(/[ً-ْـ]/g, "")
    .replace(/[أإآٱ]/g, "ا").replace(/ة/g, "ه").replace(/ى/g, "ي").replace(/ؤ/g, "و").replace(/ئ/g, "ي")
    .replace(/[-_.,()/%]/g, " ").replace(/\s+/g, " ").trim();
  const esc = (s) => String(s == null ? "" : s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const num = (v) => { const n = parseFloat(v); return isFinite(n) ? n : 0; };
  const fmt = (v, digits) => String(Math.round(v * Math.pow(10, digits)) / Math.pow(10, digits));
  const el = (html) => { const box = document.createElement("div"); box.innerHTML = html.trim(); return box.firstElementChild; };

  NB.foods.forEach((f) => (f.key = norm([f.n, f.en, f.ar].join(" "))));
  const byId = new Map(NB.foods.map((f) => [String(f.id), f]));

  let counter = Date.now() % 1000000;
  const uid = (p) => p + counter++;
  const days = Array.from(form.querySelectorAll(".nb-day"));
  const dayEl = (d) => form.querySelector(`.nb-day[data-day="${d}"]`);
  let activeDay = form.dataset.firstDay || days[0].dataset.day;

  // ---------- one food line ----------
  function syncItem(item) {
    const idEl = item.querySelector("[data-food-id]");
    const qtyEl = item.querySelector("[data-qty]");
    const unitEl = item.querySelector("[data-unit]");
    const servingOpt = unitEl.querySelector("[data-serving-option]");
    const macroEls = {};
    item.querySelectorAll("[data-macro]").forEach((m) => (macroEls[m.dataset.macro] = m));
    const food = byId.get(String(idEl.value)) || null;
    if (!food) idEl.value = "";
    item.classList.toggle("is-library", !!food);

    if (food && food.sg) {
      servingOpt.hidden = false; servingOpt.disabled = false;
      servingOpt.textContent = `${food.sn || t.serving} (${fmt(food.sg, 1)} ${t.g})`;
    } else {
      servingOpt.hidden = true; servingOpt.disabled = true;
      unitEl.value = "g";
    }

    if (food) {
      item.querySelector("[data-food-name]").value = food.n;
      item.querySelector("[data-food-label]").textContent = food.n;
      item.querySelector("[data-food-meta]").textContent = `${fmt(food.cal, 0)} ${t.kcal} ${t.per100}`;
      const grams = num(qtyEl.value) * (unitEl.value === "serving" && food.sg ? food.sg : 1);
      const k = grams / 100;
      macroEls.calories.value = fmt(food.cal * k, 0);
      macroEls.protein.value = fmt(food.p * k, 1);
      macroEls.carbs.value = fmt(food.c * k, 1);
      macroEls.fat.value = fmt(food.f * k, 1);
      item.querySelector("[data-item-sum]").innerHTML =
        `<b>${macroEls.calories.value}</b> ${esc(t.kcal)} <span>${esc(t.p)} ${macroEls.protein.value} · ${esc(t.c)} ${macroEls.carbs.value} · ${esc(t.f)} ${macroEls.fat.value}</span>`;
    }
  }

  // ---------- daily targets ----------
  const targetsBox = form.querySelector("[data-targets]");
  const weight = num(targetsBox.dataset.weight);
  const unitOf = (m) => (m === "calories" ? t.kcal : t.g);

  function targets() {
    const out = {};
    MACROS.forEach((m) => (out[m] = num(targetsBox.querySelector(`[data-target="${m}"]`).value)));
    return out;
  }

  /** under / ok / over the target range, or "" without a target */
  function state(value, target, macro) {
    if (target <= 0) return "";
    const r = NB.range[macro];
    return value < target * (1 - r) ? "under" : value > target * (1 + r) ? "over" : "ok";
  }

  function refreshTargets(tg) {
    const fromMacros = tg.protein * 4 + tg.carbs * 4 + tg.fat * 9;
    MACROS.forEach((m) => {
      const hint = targetsBox.querySelector(`[data-target-hint="${m}"]`);
      const parts = [];
      if (weight && tg[m]) parts.push(t.perKg.replace(":n", fmt(tg[m] / weight, 1)));
      if (m !== "calories" && tg[m] && tg.calories) parts.push(t.ofKcal.replace(":n", fmt((tg[m] * (m === "fat" ? 9 : 4) / tg.calories) * 100, 0)));
      hint.textContent = parts.join(" · ");
    });
    // protein, carbs and fat should give about the calories asked for
    const check = targetsBox.querySelector("[data-target-check]");
    const off = tg.calories > 0 && (tg.protein || tg.carbs || tg.fat) && Math.abs(fromMacros - tg.calories) > tg.calories * NB.range.calories;
    check.hidden = !off;
    if (off) {
      check.querySelector("[data-target-check-text]").textContent = t.macrosGive
        .replace(":kcal", fmt(fromMacros, 0)).replace(":target", fmt(tg.calories, 0));
    }
  }

  function paintRange(box, day, tg) {
    let all = true;
    MACROS.forEach((m) => {
      const row = box.querySelector(`[data-range="${m}"]`);
      const st = state(day[m], tg[m], m);
      const max = 1.3; // the bar shows up to 130% of the target
      const r = NB.range[m];
      row.classList.remove("is-under", "is-ok", "is-over");
      if (st) row.classList.add("is-" + st);
      row.style.setProperty("--fill", (tg[m] > 0 ? Math.min(day[m] / tg[m], max) / max * 100 : 0) + "%");
      row.style.setProperty("--zone-a", ((1 - r) / max) * 100 + "%");
      row.style.setProperty("--zone-b", ((1 + r) / max) * 100 + "%");
      row.querySelector(`[data-v="${m}"]`).textContent = fmt(day[m], m === "calories" ? 0 : 1);
      row.querySelector("[data-of]").textContent = tg[m] > 0 ? ` / ${fmt(tg[m], 0)} ${unitOf(m)}` : ` ${unitOf(m)}`;
      const gap = Math.abs(tg[m] - day[m]);
      row.querySelector("[data-left]").textContent =
        st === "under" ? t.left.replace(":n", `${fmt(gap, 0)} ${unitOf(m)}`)
        : st === "over" ? t.over.replace(":n", `${fmt(gap, 0)} ${unitOf(m)}`)
        : st === "ok" ? "✓" : "";
      if (st !== "ok") all = false;
    });
    box.classList.toggle("no-targets", !(tg.calories > 0));
    return all && tg.calories > 0;
  }

  // ---------- totals ----------
  function sumItems(items) {
    const s = { calories: 0, protein: 0, carbs: 0, fat: 0 };
    items.forEach((item) => {
      if (!item.querySelector("[data-food-name]").value.trim()) return;
      item.querySelectorAll("[data-macro]").forEach((m) => (s[m.dataset.macro] += num(m.value)));
    });
    return s;
  }

  function refresh() {
    const tg = targets();
    refreshTargets(tg);
    const week = [];
    const offDays = [];
    days.forEach((dayBox) => {
      const meals = dayBox.querySelectorAll(".nb-meal");
      dayBox.classList.toggle("has-rows", meals.length > 0);
      meals.forEach((meal, i) => {
        const s = sumItems(meal.querySelectorAll("[data-item]"));
        meal.querySelector("[data-meal-kcal]").textContent = `${fmt(s.calories, 0)} ${t.kcal}`;
        meal.querySelector("[data-meal-total]").textContent = `${t.p} ${fmt(s.protein, 1)} · ${t.c} ${fmt(s.carbs, 1)} · ${t.f} ${fmt(s.fat, 1)}`;
        meal.querySelector("[data-meal-number]").placeholder = i + 1;
      });
      const day = sumItems(dayBox.querySelectorAll("[data-item]"));
      if (meals.length) week.push(day);

      // with targets the bars show even on an empty day: they are what's left to fill
      const total = dayBox.querySelector("[data-day-total]");
      total.hidden = meals.length === 0 && !(tg.calories > 0);
      const onTarget = paintRange(total, day, tg);
      if (meals.length && tg.calories > 0 && !onTarget) offDays.push(dayBox.dataset.dayLabel);

      const tab = form.querySelector(`[data-tab="${dayBox.dataset.day}"]`);
      tab.querySelector("[data-tab-count]").textContent = meals.length ? fmt(day.calories, 0) : "–";
      tab.classList.toggle("has", meals.length > 0);
      tab.classList.toggle("on-target", meals.length > 0 && onTarget);
      tab.classList.toggle("off-target", meals.length > 0 && tg.calories > 0 && !onTarget);
      const row = form.querySelector(`[data-summary-day="${dayBox.dataset.day}"]`);
      if (row) {
        MACROS.forEach((m) => {
          const cell = row.querySelector(`[data-v="${m}"]`);
          cell.textContent = fmt(day[m], m === "calories" ? 0 : 1);
          cell.className = meals.length ? "is-" + (state(day[m], tg[m], m) || "none") : "";
        });
      }
    });
    form.dataset.offDays = offDays.join(document.documentElement.lang === "ar" ? "، " : ", ");
    const targetRow = form.querySelector("[data-summary-target]");
    MACROS.forEach((m) => (targetRow.querySelector(`[data-v="${m}"]`).textContent = tg[m] > 0 ? fmt(tg[m], 0) : "–"));

    const avgRow = form.querySelector("[data-summary-avg]");
    MACROS.forEach((m) => {
      const avg = week.length ? week.reduce((a, d) => a + d[m], 0) / week.length : 0;
      avgRow.querySelector(`[data-v="${m}"]`).textContent = fmt(avg, m === "calories" ? 0 : 1);
    });

    const current = dayEl(activeDay);
    const mealsNow = current.querySelectorAll(".nb-meal").length;
    const dayNow = sumItems(current.querySelectorAll("[data-item]"));
    const ofTarget = tg.calories > 0 ? ` / ${fmt(tg.calories, 0)}` : "";
    form.querySelector("[data-dock-day]").textContent = `${current.dataset.dayLabel}: ${mealsNow ? fmt(dayNow.calories, 0) + ofTarget + " " + t.kcal : t.noMeals}`;
    form.querySelector("[data-dock-sub]").textContent = mealsNow
      ? `${t.meals.replace(":count", mealsNow)} · ${t.p} ${fmt(dayNow.protein, 0)} · ${t.c} ${fmt(dayNow.carbs, 0)} · ${t.f} ${fmt(dayNow.fat, 0)}`
      : "";
    if (picker.classList.contains("open")) renderResults();
  }

  // ---------- add / remove ----------
  function fromTemplate(id, replacements) {
    let html = document.getElementById(id).innerHTML;
    Object.entries(replacements).forEach(([k, v]) => (html = html.split(k).join(v)));
    return el(html);
  }

  function addMeal(dayBox) {
    const meal = fromTemplate("nb-meal-template", { __M__: uid("m"), __DAY__: dayBox.dataset.day });
    dayBox.querySelector("[data-meals]").appendChild(meal);
    return meal;
  }

  function addItem(meal, values) {
    const item = fromTemplate("nb-item-template", { __M__: meal.dataset.meal, __I__: uid("i") });
    Object.entries(values || {}).forEach(([k, v]) => {
      const input = item.querySelector(`[name$="[${k}]"]`);
      if (input) input.value = v;
    });
    meal.querySelector("[data-items]").appendChild(item);
    syncItem(item);
    return item;
  }

  /** Copy a meal into another day: new keys for every field name. */
  function cloneMealInto(meal, dayBox) {
    const key = uid("m");
    const copy = meal.cloneNode(true);
    copy.dataset.meal = key;
    copy.querySelector("[data-day-input]").value = dayBox.dataset.day;
    copy.querySelectorAll("[name]").forEach((input) => (input.name = input.name.replace(/^meals\[[^\]]+\]/, `meals[${key}]`)));
    copy.querySelectorAll("[data-item]").forEach((item) => {
      const itemKey = uid("i");
      item.querySelectorAll("[name]").forEach((input) => (input.name = input.name.replace(/\[items\]\[[^\]]+\]/, `[items][${itemKey}]`)));
    });
    // cloneNode keeps typed values but not the chosen <select> option
    const fromSelects = meal.querySelectorAll("select");
    copy.querySelectorAll("select").forEach((s, i) => (s.value = fromSelects[i].value));
    dayBox.querySelector("[data-meals]").appendChild(copy);
    copy.querySelectorAll("[data-item]").forEach(syncItem);
  }

  const hasContent = (scope) => Array.from(scope.querySelectorAll("[data-food-name], .nb-note")).some((i) => i.value.trim() !== "");

  // ---------- tabs ----------
  function showDay(day) {
    activeDay = day;
    days.forEach((d) => (d.hidden = d.dataset.day !== day));
    form.querySelectorAll("[data-tab]").forEach((b) => {
      const on = b.dataset.tab === day;
      b.classList.toggle("on", on);
      b.setAttribute("aria-selected", on ? "true" : "false");
    });
    refresh();
  }

  // ---------- picker ----------
  const picker = document.getElementById("picker");
  const backdrop = picker.nextElementSibling;
  const results = picker.querySelector("[data-results]");
  const searchInput = picker.querySelector("[data-search]");
  let targetMeal = null, fCat = "", fQuery = "";

  function openPicker(meal) {
    targetMeal = meal;
    const index = Array.from(meal.parentElement.children).indexOf(meal) + 1;
    picker.querySelector("[data-picker-target]").textContent = `${t.meal} ${meal.querySelector("[data-meal-number]").value || index}`;
    picker.classList.add("open"); backdrop.classList.add("show"); picker.setAttribute("aria-hidden", "false");
    renderResults();
    if (window.matchMedia("(min-width: 641px)").matches) searchInput.focus();
  }

  function closePicker() {
    picker.classList.remove("open"); backdrop.classList.remove("show"); picker.setAttribute("aria-hidden", "true");
    if (targetMeal && !hasContent(targetMeal)) targetMeal.remove(); // a new meal left empty
    targetMeal = null;
    refresh();
  }

  function renderResults() {
    if (!targetMeal) return;
    const inMeal = new Set(Array.from(targetMeal.querySelectorAll("[data-food-id]")).map((i) => i.value).filter(Boolean));
    const tokens = norm(fQuery).split(" ").filter(Boolean);
    const list = NB.foods.filter((f) => (!fCat || f.cat === fCat) && tokens.every((tk) => f.key.includes(tk)));
    let html = list.map((f) => {
      const added = inMeal.has(String(f.id));
      const serving = f.sg ? ` · ${esc(f.sn)} = ${fmt(f.sg, 1)} ${esc(t.g)}` : "";
      return `<button type="button" class="pick ${added ? "added" : ""}" data-id="${f.id}">
        <span class="pick-icon pick-kcal"><b>${fmt(f.cal, 0)}</b><small>${esc(t.kcal)}</small></span>
        <span><span class="pick-name">${esc(f.n)}</span>
          <span class="pick-meta">${esc(t.per100)}: ${esc(t.p)} ${fmt(f.p, 1)} · ${esc(t.c)} ${fmt(f.c, 1)} · ${esc(t.f)} ${fmt(f.f, 1)}${serving}${added ? ` · <span class="in-day">${esc(t.inMeal)}</span>` : ""}</span></span>
        <span class="pick-add"><i class="fas ${added ? "fa-check" : "fa-plus"}"></i></span>
      </button>`;
    }).join("");
    if (!list.length) html = `<p class="pick-empty">${esc(t.noResults)}</p>`;
    if (fQuery.trim()) {
      html += `<button type="button" class="pick pick-custom" data-custom>
        <span class="pick-icon"><i class="fas fa-pen"></i></span>
        <span><span class="pick-name">${esc(t.addCustom.replace(":name", fQuery.trim()))}</span></span>
        <span class="pick-add"><i class="fas fa-plus"></i></span></button>`;
    }
    results.innerHTML = html;
    const s = sumItems(targetMeal.querySelectorAll("[data-item]"));
    picker.querySelector("[data-picker-count]").textContent = `${t.meal}: ${fmt(s.calories, 0)} ${t.kcal}`;
  }

  picker.addEventListener("click", (e) => {
    const cat = e.target.closest("[data-cat]");
    const pick = e.target.closest(".pick[data-id]");
    const custom = e.target.closest("[data-custom]");
    if (cat) {
      fCat = cat.dataset.cat;
      picker.querySelectorAll("[data-cat]").forEach((c) => c.classList.toggle("on", c.dataset.cat === fCat));
      renderResults();
    } else if (pick && targetMeal) {
      const food = byId.get(pick.dataset.id);
      addItem(targetMeal, { food_id: food.id, name: food.n, qty: food.sg ? 1 : 100, unit: food.sg ? "serving" : "g" });
      refresh();
    } else if (custom && targetMeal) {
      const item = addItem(targetMeal, { name: fQuery.trim() });
      fQuery = ""; searchInput.value = "";
      closePicker();
      item.querySelector('[data-macro="calories"]').focus();
    }
  });
  searchInput.addEventListener("input", () => { fQuery = searchInput.value; renderResults(); });
  document.querySelectorAll("[data-close-picker]").forEach((b) => b.addEventListener("click", closePicker));
  document.addEventListener("keydown", (e) => { if (e.key === "Escape" && picker.classList.contains("open")) closePicker(); });

  // ---------- form events ----------
  form.addEventListener("input", (e) => {
    const item = e.target.closest("[data-item]");
    if (item && e.target.matches("[data-qty]")) syncItem(item);
    refresh();
  });

  form.addEventListener("change", (e) => {
    const item = e.target.closest("[data-item]");
    if (item && e.target.matches("[data-unit]")) {
      // switching unit keeps the same amount of food
      const food = byId.get(String(item.querySelector("[data-food-id]").value));
      const qtyEl = item.querySelector("[data-qty]");
      if (food && food.sg && num(qtyEl.value) > 0) {
        qtyEl.value = e.target.value === "serving" ? fmt(num(qtyEl.value) / food.sg, 1) : fmt(num(qtyEl.value) * food.sg, 0);
      }
      syncItem(item);
      refresh();
    }

    if (e.target.matches("[data-copy]")) {
      const target = e.target.value;
      e.target.value = "";
      if (!target) return;
      const source = dayEl(activeDay);
      const label = target === "*" ? t.allDays : dayEl(target).dataset.dayLabel;
      if (!confirm(t.copyConfirm.replace(":target", label).replace(":day", source.dataset.dayLabel))) return;
      const meals = Array.from(source.querySelectorAll(".nb-meal"));
      days.filter((d) => d !== source && (target === "*" || d.dataset.day === target)).forEach((d) => {
        d.querySelector("[data-meals]").innerHTML = "";
        meals.forEach((m) => cloneMealInto(m, d));
      });
      refresh();
    }

    if (e.target.matches("[data-template]")) {
      const option = e.target.options[e.target.selectedIndex];
      const body = option ? option.getAttribute("data-body") : "";
      e.target.value = "";
      if (!body) return;
      if (hasContent(form) && !confirm(t.templateConfirm)) return;
      days.forEach((d) => (d.querySelector("[data-meals]").innerHTML = ""));
      // template format: day|meal_number|description|calories|protein|carbs|fat
      body.split("\n").map((l) => l.trim()).filter(Boolean).forEach((line) => {
        const [day, mealNo, desc, calories, protein, carbs, fat] = line.split("|").map((p) => p.trim());
        if (!dayEl(day) || !desc) return;
        const meal = addMeal(dayEl(day));
        meal.querySelector("[data-meal-number]").value = mealNo || "";
        addItem(meal, { name: desc, calories, protein, carbs, fat });
      });
      const first = days.find((d) => d.querySelector(".nb-meal"));
      showDay(first ? first.dataset.day : activeDay);
    }
  });

  // carbs take whatever calories protein and fat leave
  targetsBox.querySelector("[data-fill-carbs]").addEventListener("click", () => {
    const tg = targets();
    const carbs = Math.max(0, (tg.calories - tg.protein * 4 - tg.fat * 9) / 4);
    targetsBox.querySelector('[data-target="carbs"]').value = fmt(carbs, 0);
    refresh();
  });

  // sending a plan with days off target asks first
  form.addEventListener("submit", (e) => {
    refresh();
    if (form.dataset.offDays && !confirm(t.offTarget.replace(":days", form.dataset.offDays))) e.preventDefault();
  });

  form.addEventListener("click", (e) => {
    const tab = e.target.closest("[data-tab]");
    if (tab) return showDay(tab.dataset.tab);
    const btn = e.target.closest("button");
    if (!btn) return;
    const meal = btn.closest(".nb-meal");

    if (btn.matches("[data-add-meal]")) {
      openPicker(addMeal(dayEl(activeDay)));
    } else if (btn.matches("[data-add-food]")) {
      openPicker(meal);
    } else if (btn.matches("[data-remove-item]")) {
      btn.closest("[data-item]").remove();
    } else if (btn.matches("[data-remove-meal]")) {
      if (!hasContent(meal) || confirm(t.removeMeal)) meal.remove();
    } else {
      return;
    }
    refresh();
  });

  // ---------- start ----------
  form.querySelectorAll("[data-item]").forEach(syncItem);
  showDay(activeDay);
  // a new plan starts at the targets (not on phones: the keyboard would cover the page)
  const firstEmpty = targetsBox.querySelector("[data-target]:invalid");
  if (firstEmpty && !form.querySelector(".nb-meal") && window.matchMedia("(hover: hover)").matches) firstEmpty.focus();
})();
