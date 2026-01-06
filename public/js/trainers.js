document.addEventListener("DOMContentLoaded", function () {
    // Small hover feedback (optional, safe)
    document.querySelectorAll(".trainer-card .btn").forEach(btn => {
      btn.addEventListener("mouseenter", () => btn.classList.add("hover"));
      btn.addEventListener("mouseleave", () => btn.classList.remove("hover"));
    });
  });