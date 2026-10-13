/* =========================
   Trainer Dashboard JS
   ========================= */

   document.addEventListener("DOMContentLoaded", () => {
    // ===== Profile toggle =====
    const toggleProfileBtn = document.getElementById("toggleProfileBtn");
    const closeProfileBtn  = document.getElementById("closeProfileBtn");
    const profileBox       = document.getElementById("profileBox");
  
    const toggleEditBtn = document.getElementById("toggleEditBtn");
    const editBox       = document.getElementById("editBox");
  
    if (toggleProfileBtn && profileBox) {
      toggleProfileBtn.addEventListener("click", () => {
        profileBox.classList.toggle("hidden");
        if (editBox) editBox.classList.add("hidden");
        if (!profileBox.classList.contains("hidden")) {
          profileBox.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      });
    }
  
    if (closeProfileBtn && profileBox) {
      closeProfileBtn.addEventListener("click", () => {
        profileBox.classList.add("hidden");
        if (editBox) editBox.classList.add("hidden");
      });
    }
  
    if (toggleEditBtn && editBox) {
      toggleEditBtn.addEventListener("click", () => {
        editBox.classList.toggle("hidden");
      });
    }
  
    // ==========================================================
    // Event Delegation (IMPORTANT)
    // - Works for dynamically added rows
    // ==========================================================
    document.addEventListener("click", (e) => {
      // Remove row buttons
      const removeBtn = e.target.closest(".remove-row");
      if (removeBtn) {
        const row = removeBtn.closest(".workout-row, .nutrition-row");
        if (!row) return;
  
        const container = row.parentElement; // rows container for that day
        const isWorkout = row.classList.contains("workout-row");
        const selector  = isWorkout ? ".workout-row" : ".nutrition-row";
  
        if (container.querySelectorAll(selector).length > 1) {
          row.remove();
        } else {
          const i18n = window.I18N || {};
          alert(isWorkout
            ? (i18n.min_exercise_row || "This day must contain at least one exercise row.")
            : (i18n.min_meal_row || "This day must contain at least one meal row.")
          );
        }
        return;
      }
  
      // Add mini buttons (day cards)
      const addMini = e.target.closest(".add-mini-btn");
      if (addMini) {
        const day = addMini.getAttribute("data-day");
        const type = addMini.getAttribute("data-type"); // workout | nutrition
        if (!day || !type) return;
  
        if (type === "workout") addWorkoutRow(day);
        if (type === "nutrition") addNutritionRow(day);
      }
    });
  });
  
  /* ==========================================================
     Helpers
     ========================================================== */
  function resetRowFields(row) {
    row.querySelectorAll("input, select, textarea").forEach((el) => {
      // keep hidden day[] intact (but forget the linked library exercise)
      if (el.type === "hidden") {
        if (el.name === "exercise_id[]") el.value = "";
        return;
      }
  
      // reset values safely
      if (el.tagName === "SELECT") {
        el.selectedIndex = 0;
        return;
      }
  
      if (el.type === "checkbox" || el.type === "radio") {
        el.checked = false;
        return;
      }
  
      el.value = "";
    });
  }
  
  /* ================== WORKOUT WEEKLY ROWS ================== */
  function addWorkoutRow(day) {
    const container = document.getElementById(`workout-rows-${day}`);
    if (!container) return;
  
    const firstRow = container.querySelector(".workout-row");
    if (!firstRow) return;
  
    const newRow = firstRow.cloneNode(true);
    resetRowFields(newRow);
    container.appendChild(newRow);
  }
  
  /* ================== NUTRITION WEEKLY ROWS ================== */
  function addNutritionRow(day) {
    const container = document.getElementById(`nutrition-rows-${day}`);
    if (!container) return;
  
    const firstRow = container.querySelector(".nutrition-row");
    if (!firstRow) return;
  
    const newRow = firstRow.cloneNode(true);
    resetRowFields(newRow);
    container.appendChild(newRow);
  }
  
  /* ================== TEMPLATES (Weekly) ==================
     Format:
     workout:   day|muscle|exercise|sets|reps
     nutrition: day|meal_number|description|calories|protein|carbs|fat
  ========================================================== */
  
  function clearWeeklyContainers(prefix) {
    const days = ["Saturday","Sunday","Monday","Tuesday","Wednesday","Thursday","Friday"];
    days.forEach((day) => {
      const container = document.getElementById(`${prefix}-${day}`);
      if (!container) return;
  
      const isWorkout = prefix.includes("workout");
      const rowClass  = isWorkout ? ".workout-row" : ".nutrition-row";
  
      const first = container.querySelector(rowClass);
      container.innerHTML = "";
  
      if (first) {
        // IMPORTANT: append the original first row, but reset it
        resetRowFields(first);
        container.appendChild(first);
      }
    });
  }
  
  /* ===== Workout Template Loader ===== */
  function loadWorkoutTemplateWeekly(selectEl) {
    const option = selectEl?.options?.[selectEl.selectedIndex];
    const body = option ? option.getAttribute("data-body") : null;
    if (!body) return;
  
    clearWeeklyContainers("workout-rows");
  
    const lines = body.split("\n").map(l => l.trim()).filter(Boolean);
  
    lines.forEach((line) => {
      const parts = line.split("|").map(p => p.trim());
      if (parts.length < 5) return;
  
      const [day, muscle, exercise, sets, reps] = parts;
      const container = document.getElementById(`workout-rows-${day}`);
      if (!container) return;
  
      // use first row if empty, otherwise add new row
      let row = container.querySelector(".workout-row");
      const muscleEl = row?.querySelector('select[name="muscle[]"]');
      const exEl     = row?.querySelector('input[name="exercise[]"]');
  
      const isFirstEmpty = row && muscleEl && exEl && !muscleEl.value && !exEl.value;
  
      if (!isFirstEmpty) {
        row = row.cloneNode(true);
        resetRowFields(row);
        container.appendChild(row);
      }
  
      row.querySelector('select[name="muscle[]"]').value = muscle || "";
      row.querySelector('input[name="exercise[]"]').value = exercise || "";
      row.querySelector('input[name="sets[]"]').value = sets || "";
      row.querySelector('input[name="reps[]"]').value = reps || "";
      syncExerciseFromLibrary(row.querySelector('input[name="exercise[]"]'), false);
    });
  }
  
  /* ===== Nutrition Template Loader ===== */
  function loadNutritionTemplateWeekly(selectEl) {
    const option = selectEl?.options?.[selectEl.selectedIndex];
    const body = option ? option.getAttribute("data-body") : null;
    if (!body) return;
  
    clearWeeklyContainers("nutrition-rows");
  
    const lines = body.split("\n").map(l => l.trim()).filter(Boolean);
  
    lines.forEach((line) => {
      const parts = line.split("|").map(p => p.trim());
      if (parts.length < 7) return;
  
      const [day, mealNum, desc, calories, protein, carbs, fat] = parts;
      const container = document.getElementById(`nutrition-rows-${day}`);
      if (!container) return;
  
      let row = container.querySelector(".nutrition-row");
      const mealEl = row?.querySelector('input[name="meal_number[]"]');
      const descEl = row?.querySelector('input[name="description[]"]');
  
      const isFirstEmpty = row && mealEl && descEl && !mealEl.value && !descEl.value;
  
      if (!isFirstEmpty) {
        row = row.cloneNode(true);
        resetRowFields(row);
        container.appendChild(row);
      }
  
      row.querySelector('input[name="meal_number[]"]').value = mealNum || "";
      row.querySelector('input[name="description[]"]').value = desc || "";
      row.querySelector('input[name="calories[]"]').value = calories || "";
      row.querySelector('input[name="protein[]"]').value = protein || "";
      row.querySelector('input[name="carbs[]"]').value = carbs || "";
      row.querySelector('input[name="fat[]"]').value = fat || "";
    });
  }
  
  /* ==========================================================
     Exercise library picker (plan builder)
     - The exercise input uses <datalist id="exercise-library">
     - When the typed name matches a library exercise, remember its id
       (so the client gets the video) and fill in the muscle.
     ========================================================== */
  function findLibraryOption(value) {
    const list = document.getElementById("exercise-library");
    if (!list || !value) return null;
    const wanted = value.trim().toLowerCase();
    return Array.from(list.options).find((opt) =>
      opt.value.trim().toLowerCase() === wanted ||
      (opt.dataset.en || "").trim().toLowerCase() === wanted
    ) || null;
  }

  function syncExerciseFromLibrary(input, fillMuscle = true) {
    if (!input) return;
    const row = input.closest(".workout-row");
    if (!row) return;
    const hidden = row.querySelector('input[name="exercise_id[]"]');
    const option = findLibraryOption(input.value);

    if (hidden) hidden.value = option ? option.dataset.id : "";
    input.classList.toggle("from-library", !!option);

    const muscle = row.querySelector('select[name="muscle[]"]');
    if (option && muscle && option.dataset.muscle && (fillMuscle || !muscle.value)) {
      muscle.value = option.dataset.muscle;
    }
  }

  document.addEventListener("input", (e) => {
    if (e.target.matches && e.target.matches('input[name="exercise[]"]')) syncExerciseFromLibrary(e.target);
  });

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('input[name="exercise[]"]').forEach((input) => syncExerciseFromLibrary(input, false));
  });

  /* ==========================================================
     Export functions to global scope
     (so Blade onchange="" still works)
     ========================================================== */
  window.addWorkoutRow = addWorkoutRow;
  window.addNutritionRow = addNutritionRow;
  window.loadWorkoutTemplateWeekly = loadWorkoutTemplateWeekly;
  window.loadNutritionTemplateWeekly = loadNutritionTemplateWeekly;