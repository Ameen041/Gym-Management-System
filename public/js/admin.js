/* Admin panel: open / close the menu on small screens. */
(function () {
  "use strict";
  const side = document.getElementById("admin-side");
  if (!side) return;
  const setOpen = (open) => document.body.classList.toggle("side-open", open);
  document.addEventListener("click", (e) => {
    if (e.target.closest("[data-side-toggle]")) setOpen(!document.body.classList.contains("side-open"));
    else if (e.target.closest("[data-side-close]")) setOpen(false);
  });
  document.addEventListener("keydown", (e) => { if (e.key === "Escape") setOpen(false); });
})();
