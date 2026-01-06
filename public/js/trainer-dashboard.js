document.addEventListener("DOMContentLoaded", function () {
    const toggleProfileBtn = document.getElementById("toggleProfileBtn");
    const closeProfileBtn  = document.getElementById("closeProfileBtn");
    const profileBox       = document.getElementById("profileBox");
  
    const toggleEditBtn = document.getElementById("toggleEditBtn");
    const editBox      = document.getElementById("editBox");
  
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
  });
  
  /* ================== WORKOUT ROWS ================== */
  function addRow() {
    const container = document.getElementById("workout-rows");
    if (!container) return;
  
    const firstRow = container.querySelector(".workout-row");
    if (!firstRow) return;
  
    const newRow = firstRow.cloneNode(true);
    newRow.querySelectorAll("input, select").forEach(el => (el.value = ""));
    container.appendChild(newRow);
  }
  
  function removeRow(button) {
    const container = document.getElementById("workout-rows");
    if (!container) return;
  
    const row = button.closest(".workout-row");
    if (!row) return;
  
    if (container.querySelectorAll(".workout-row").length > 1) {
      row.remove();
    } else {
      alert("The table must contain at least one row.");
    }
  }
  
  /* ================== NUTRITION ROWS ================== */
  function addNutritionRow() {
    const container = document.getElementById("nutrition-rows");
    if (!container) return;
  
    const first = container.querySelector(".nutrition-row");
    if (!first) return;
  
    const row = first.cloneNode(true);
    row.querySelectorAll("input, select").forEach(el => (el.value = ""));
    container.appendChild(row);
  }
  
  function removeNutritionRow(button) {
    const container = document.getElementById("nutrition-rows");
    if (!container) return;
  
    const row = button.closest(".nutrition-row");
    if (!row) return;
  
    if (container.querySelectorAll(".nutrition-row").length > 1) {
      row.remove();
    } else {
      alert("The table must contain at least one row.");
    }
  }
  
  /* ================== TEMPLATES (TEXT FORMAT) ================== */
  function loadWorkoutTemplateFromSelect(selectEl) {
    if (!selectEl) return;
    const option = selectEl.options[selectEl.selectedIndex];
    const body = option ? option.getAttribute("data-body") : null;
    if (!body) return;
  
    const lines = body
      .split("\n")
      .map(l => l.trim())
      .filter(l => l.length > 0);
  
    const container = document.getElementById("workout-rows");
    if (!container) return;
  
    container.innerHTML = "";
  
    lines.forEach(line => {
      const parts = line.split("|").map(p => p.trim());
      if (parts.length < 5) return;
  
      const [day, muscle, exercise, sets, reps] = parts;
  
      const row = document.createElement("div");
      row.className = " workout-row";
      row.innerHTML = `
        <select name="day[]" required>
          <option value="">Day</option>
          ${["Saturday","Sunday","Monday","Tuesday","Wednesday","Thursday","Friday"]
            .map(d => `<option value="${d}" title="${d}">${d}</option>`).join("")}
        </select>
  
        <select name="muscle[]" required>
          <option value="">Muscle</option>
          ${["Chest","Back","Shoulders","Legs","Abs","Biceps","Triceps"]
            .map(m => `<option value="${m}" title="${m}">${m}</option>`).join("")}
        </select>
  
        <input type="text" name="exercise[]" placeholder="Exercise (Full name)" required>
        <input type="number" name="sets[]" placeholder="Sets" required>
        <input type="text" name="reps[]" placeholder="Reps (e.g. 10-12)" required>
  
        <button type="button" class="remove-row" onclick="removeRow(this)" title="Remove Row">
          <i class="fas fa-trash-alt"></i>
        </button>
      `;
  
      const selects = row.querySelectorAll("select");
      const inputs  = row.querySelectorAll("input");
  
      selects[0].value = day || "";
      selects[1].value = muscle || "";
      inputs[0].value  = exercise || "";
      inputs[1].value  = sets || "";
      inputs[2].value  = reps || "";
  
      container.appendChild(row);
    });
  }
  
  function loadNutritionTemplateFromSelect(selectEl) {
    if (!selectEl) return;
    const option = selectEl.options[selectEl.selectedIndex];
    const body = option ? option.getAttribute("data-body") : null;
    if (!body) return;
  
    const lines = body
      .split("\n")
      .map(l => l.trim())
      .filter(l => l.length > 0);
  
    const container = document.getElementById("nutrition-rows");
    if (!container) return;
  
    container.innerHTML = "";
  
    lines.forEach(line => {
      const parts = line.split("|").map(p => p.trim());
      if (parts.length < 7) return;
  
      const [day, mealNum, desc, calories, protein, carbs, fat] = parts;
  
      const row = document.createElement("div");
      row.className = " nutrition-row";
      row.innerHTML = `
        <select name="day[]" required>
          <option value="">Day</option>
          ${["Saturday","Sunday","Monday","Tuesday","Wednesday","Thursday","Friday"]
            .map(d => `<option value="${d}" title="${d}">${d}</option>`).join("")}
        </select>
  
        <input type="number" name="meal_number[]" placeholder="Meal #" min="1" required>
        <input type="text" name="description[]" placeholder="Meal Description (Full text)" required>
        <input type="number" name="calories[]" placeholder="Calories" required>
        <input type="number" name="protein[]" placeholder="Protein (g)" step="0.1" required>
        <input type="number" name="carbs[]" placeholder="Carbs (g)" step="0.1" required>
        <input type="number" name="fat[]" placeholder="Fat (g)" step="0.1" required>
  
        <button type="button" class="remove-row" onclick="removeNutritionRow(this)" title="Remove Row">
          <i class="fas fa-trash-alt"></i>
        </button>
      `;
  
      const select = row.querySelector("select");
      const inputs = row.querySelectorAll("input");
  
      select.value   = day || "";
      inputs[0].value = mealNum || "";
      inputs[1].value = desc || "";
      inputs[2].value = calories || "";
      inputs[3].value = protein || "";
      inputs[4].value = carbs || "";
      inputs[5].value = fat || "";
  
      container.appendChild(row);
    });
  }