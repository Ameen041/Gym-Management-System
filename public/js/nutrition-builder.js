/* =========================================================
   Nutrition plan builder: meals made of foods from the library.
   Calories / protein / carbs / fat are calculated per food,
   per meal, per day and for the week (the server recalculates
   library foods again when the plan is saved).
   ========================================================= */
(function () {
  "use strict";

  const NB = window.NB || { foods: [], i18n: {} };
  const t = NB.i18n;
  const MACROS = ["calories", "protein", "carbs", "fat"];
  const form = document.getElementById("nutrition-form");
  if (!form) return;

  // ---- food lookup (by id, and by any of its names) ----
  const byId = new Map();
  const byName = new Map();
  const norm = (s) => String(s || "").trim().toLowerCase().replace(/\s+/g, " ");
  NB.foods.forEach((f) => {
    byId.set(String(f.id), f);
    [f.n, f.en, f.ar].forEach((name) => { if (name && !byName.has(norm(name))) byName.set(norm(name), f); });
  });

  let counter = Date.now() % 1000000;
  const uid = (prefix) => prefix + (counter++);
  const num = (v) => { const n = parseFloat(v); return isFinite(n) ? n : 0; };
  const fmt = (v, digits) => {
    const n = Math.round(v * Math.pow(10, digits)) / Math.pow(10, digits);
    return String(n);
  };
  const totalText = (label, s) =>
    `<b>${label}:</b> ${fmt(s.calories, 0)} ${t.kcal} · ${t.protein} ${fmt(s.protein, 1)} ${t.g} · ${t.carbs} ${fmt(s.carbs, 1)} ${t.g} · ${t.fat} ${fmt(s.fat, 1)} ${t.g}`;

  // ---- one food line ----
  function syncItem(item, picked) {
    const nameEl = item.querySelector("[data-food-name]");
    const idEl = item.querySelector("[data-food-id]");
    const qtyEl = item.querySelector("[data-qty]");
    const unitEl = item.querySelector("[data-unit]");
    const servingOpt = unitEl.querySelector("[data-serving-option]");
    const macroEls = {};
    item.querySelectorAll("[data-macro]").forEach((el) => (macroEls[el.dataset.macro] = el));

    let food = null;
    if (picked) {
      food = byName.get(norm(nameEl.value)) || null;
    } else {
      food = byId.get(String(idEl.value)) || null;
      if (food) nameEl.value = food.n;            // current name of the library food
    }
    const wasLibrary = item.classList.contains("is-library");
    idEl.value = food ? food.id : "";
    item.classList.toggle("is-library", !!food);

    // unit: grams, or the food's serving ("1 large egg = 50 g")
    if (food && food.sg) {
      servingOpt.hidden = false;
      servingOpt.disabled = false;
      servingOpt.textContent = `${food.sn || t.serving} (${fmt(food.sg, 1)} ${t.g})`;
    } else {
      servingOpt.hidden = true;
      servingOpt.disabled = true;
      unitEl.value = "g";
    }

    // just picked: suggest an amount
    if (food && picked && !wasLibrary) {
      if (food.sg) { unitEl.value = "serving"; qtyEl.value = qtyEl.value || 1; }
      else { unitEl.value = "g"; qtyEl.value = qtyEl.value || 100; }
    }

    Object.values(macroEls).forEach((el) => (el.readOnly = !!food));
    if (food) {
      const grams = num(qtyEl.value) * (unitEl.value === "serving" && food.sg ? food.sg : 1);
      const factor = grams / 100;
      macroEls.calories.value = fmt(food.cal * factor, 0);
      macroEls.protein.value = fmt(food.p * factor, 1);
      macroEls.carbs.value = fmt(food.c * factor, 1);
      macroEls.fat.value = fmt(food.f * factor, 1);
      item.title = `${food.n}: ${fmt(food.cal, 0)} ${t.kcal} / 100 ${t.g}`;
    } else {
      item.removeAttribute("title");
      if (wasLibrary && picked) MACROS.forEach((m) => (macroEls[m].value = "")); // typed a new, unknown food
    }
  }

  // ---- totals ----
  function sumOf(elements) {
    const s = { calories: 0, protein: 0, carbs: 0, fat: 0 };
    elements.forEach((item) => {
      if (!item.querySelector("[data-food-name]").value.trim()) return;
      item.querySelectorAll("[data-macro]").forEach((el) => (s[el.dataset.macro] += num(el.value)));
    });
    return s;
  }

  function recalc() {
    const week = [];
    form.querySelectorAll(".nb-day").forEach((dayEl) => {
      const meals = dayEl.querySelectorAll(".nb-meal");
      dayEl.classList.toggle("is-empty", meals.length === 0);
      meals.forEach((meal, i) => {
        const s = sumOf(meal.querySelectorAll("[data-item]"));
        meal.querySelector("[data-meal-total]").innerHTML = totalText(t.mealTotal, s);
        const numberEl = meal.querySelector("[data-meal-number]");
        numberEl.placeholder = i + 1;
      });
      const day = sumOf(dayEl.querySelectorAll("[data-item]"));
      dayEl.querySelector("[data-day-total]").innerHTML = meals.length ? totalText(t.dayTotal, day) : "";
      const row = form.querySelector(`[data-summary-day="${dayEl.dataset.day}"]`);
      if (row) MACROS.forEach((m) => (row.querySelector(`[data-v="${m}"]`).textContent = fmt(day[m], m === "calories" ? 0 : 1)));
      if (meals.length) week.push(day);
    });
    const avgRow = form.querySelector("[data-summary-avg]");
    if (avgRow) {
      MACROS.forEach((m) => {
        const avg = week.length ? week.reduce((a, d) => a + d[m], 0) / week.length : 0;
        avgRow.querySelector(`[data-v="${m}"]`).textContent = fmt(avg, m === "calories" ? 0 : 1);
      });
    }
  }

  // ---- adding / removing ----
  function fromTemplate(id, replacements) {
    let html = document.getElementById(id).innerHTML;
    Object.entries(replacements).forEach(([k, v]) => (html = html.split(k).join(v)));
    const box = document.createElement("div");
    box.innerHTML = html.trim();
    return box.firstElementChild;
  }

  function addItem(meal) {
    const item = fromTemplate("nb-item-template", { __M__: meal.dataset.meal, __I__: uid("i") });
    meal.querySelector("[data-items]").appendChild(item);
    syncItem(item, false);
    return item;
  }

  function addMeal(dayEl) {
    const meal = fromTemplate("nb-meal-template", { __M__: uid("m"), __DAY__: dayEl.dataset.day });
    dayEl.querySelector("[data-meals]").appendChild(meal);
    meal.querySelectorAll("[data-item]").forEach((item) => syncItem(item, false));
    return meal;
  }

  function clearItem(item) {
    item.querySelectorAll("input").forEach((el) => (el.value = ""));
    item.querySelector("[data-unit]").value = "g";
    syncItem(item, true);
  }

  /** Copy a meal into another day: new keys for every field name. */
  function cloneMealInto(meal, dayEl) {
    const key = uid("m");
    const copy = meal.cloneNode(true);
    copy.dataset.meal = key;
    copy.querySelector("[data-day-input]").value = dayEl.dataset.day;
    copy.querySelectorAll("[name]").forEach((el) => {
      el.name = el.name.replace(/^meals\[[^\]]+\]/, `meals[${key}]`);
    });
    copy.querySelectorAll("[data-item]").forEach((item) => {
      const itemKey = uid("i");
      item.querySelectorAll("[name]").forEach((el) => {
        el.name = el.name.replace(/\[items\]\[[^\]]+\]/, `[items][${itemKey}]`);
      });
    });
    // cloneNode keeps typed input values but not the chosen <select> option
    const sourceSelects = meal.querySelectorAll("select");
    copy.querySelectorAll("select").forEach((select, i) => (select.value = sourceSelects[i].value));
    dayEl.querySelector("[data-meals]").appendChild(copy);
    copy.querySelectorAll("[data-item]").forEach((item) => syncItem(item, false));
  }

  function hasContent(scope) {
    return Array.from(scope.querySelectorAll("[data-food-name], .nb-note")).some((el) => el.value.trim() !== "");
  }

  // ---- template loader: "day|meal_number|description|calories|protein|carbs|fat" ----
  function loadTemplate(select) {
    const option = select.options[select.selectedIndex];
    const body = option ? option.getAttribute("data-body") : "";
    if (!body) return;
    if (hasContent(form.querySelector(".nb-days")) && !confirm(t.replaceAll)) { select.value = ""; return; }

    form.querySelectorAll("[data-meals]").forEach((box) => (box.innerHTML = ""));
    body.split("\n").map((l) => l.trim()).filter(Boolean).forEach((line) => {
      const [day, mealNo, desc, calories, protein, carbs, fat] = line.split("|").map((p) => p.trim());
      const dayEl = form.querySelector(`.nb-day[data-day="${day}"]`);
      if (!dayEl || !desc) return;
      const meal = addMeal(dayEl);
      meal.querySelector("[data-meal-number]").value = mealNo || "";
      const item = meal.querySelector("[data-item]");
      item.querySelector("[data-food-name]").value = desc;
      syncItem(item, true);
      const values = { calories, protein, carbs, fat };
      MACROS.forEach((m) => (item.querySelector(`[data-macro="${m}"]`).value = values[m] || ""));
    });
    recalc();
  }

  // ---- events ----
  form.addEventListener("input", (e) => {
    const item = e.target.closest("[data-item]");
    if (item && (e.target.matches("[data-food-name]") || e.target.matches("[data-qty]"))) syncItem(item, e.target.matches("[data-food-name]"));
    recalc();
  });

  form.addEventListener("change", (e) => {
    const item = e.target.closest("[data-item]");
    if (item && e.target.matches("[data-unit]")) {
      // switching unit keeps the same quantity of food when possible
      const food = byId.get(String(item.querySelector("[data-food-id]").value));
      const qtyEl = item.querySelector("[data-qty]");
      if (food && food.sg && num(qtyEl.value) > 0) {
        qtyEl.value = e.target.value === "serving" ? fmt(num(qtyEl.value) / food.sg, 1) : fmt(num(qtyEl.value) * food.sg, 0);
      }
      syncItem(item, false);
      recalc();
    }
    if (e.target.matches("[data-template]")) loadTemplate(e.target);
  });

  form.addEventListener("click", (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;
    const dayEl = btn.closest(".nb-day");
    const meal = btn.closest(".nb-meal");

    if (btn.matches("[data-add-meal]")) {
      const newMeal = addMeal(dayEl);
      newMeal.querySelector("[data-food-name]").focus();
    } else if (btn.matches("[data-add-item]")) {
      addItem(meal).querySelector("[data-food-name]").focus();
    } else if (btn.matches("[data-remove-item]")) {
      const item = btn.closest("[data-item]");
      if (meal.querySelectorAll("[data-item]").length > 1) item.remove();
      else clearItem(item);
    } else if (btn.matches("[data-remove-meal]")) {
      if (!hasContent(meal) || confirm(t.removeMeal)) meal.remove();
    } else if (btn.matches("[data-copy-day]")) {
      if (!confirm(t.copyDay.replace(":day", dayEl.dataset.dayLabel))) return;
      const meals = Array.from(dayEl.querySelectorAll(".nb-meal"));
      form.querySelectorAll(".nb-day").forEach((other) => {
        if (other === dayEl) return;
        other.querySelector("[data-meals]").innerHTML = "";
        meals.forEach((m) => cloneMealInto(m, other));
      });
    } else {
      return;
    }
    recalc();
  });

  // ---- start ----
  form.querySelectorAll("[data-item]").forEach((item) => syncItem(item, false));
  recalc();
})();
