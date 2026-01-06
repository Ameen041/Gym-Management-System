document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-toggle]").forEach(btn => {
      btn.addEventListener("click", () => {
        const selector = btn.getAttribute("data-toggle");
        const el = document.querySelector(selector);
        if (!el) return;
        el.classList.toggle("hidden");
        if (!el.classList.contains("hidden")) {
          el.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      });
    });
  });