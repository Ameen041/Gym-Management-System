/* Public pages: mobile menu, login / sign-up modal. */
(function () {
  "use strict";

  const modal = document.querySelector(".auth-modal");

  function switchAuthTab(tab) {
    document.querySelectorAll(".auth-tab").forEach((b) => b.classList.toggle("active", b.dataset.tab === tab));
    document.querySelectorAll(".auth-form").forEach((f) => f.classList.toggle("active", f.id === tab));
  }
  function openModal(tab) {
    if (!modal) return;
    modal.classList.add("active");
    switchAuthTab(tab);
    const first = modal.querySelector(`#${tab} input:not([type=hidden]), #${tab} select`);
    if (first && window.matchMedia("(min-width: 641px)").matches) first.focus();
  }
  window.showLoginModal = () => openModal("login");
  window.showSignupModal = () => openModal("signup");

  document.addEventListener("click", (e) => {
    if (e.target.closest(".login-btn")) { e.preventDefault(); return openModal("login"); }
    if (e.target.closest(".signup-btn")) { e.preventDefault(); return openModal("signup"); }
    if (e.target.closest("[data-signup-coach]")) {
      openModal("signup");
      const role = document.getElementById("signupRole");
      if (role) { role.value = "trainer"; role.dispatchEvent(new Event("change")); }
      return;
    }
    const tab = e.target.closest(".auth-tab");
    if (tab) return switchAuthTab(tab.dataset.tab);
    if (e.target.closest(".close-modal") || e.target === modal) return modal.classList.remove("active");

    const menu = e.target.closest("[data-menu]");
    const nav = document.getElementById("site-nav");
    if (menu && nav) {
      const open = !nav.classList.contains("open");
      nav.classList.toggle("open", open);
      menu.setAttribute("aria-expanded", open ? "true" : "false");
      menu.querySelector("i").className = open ? "fas fa-xmark" : "fas fa-bars";
    } else if (nav && e.target.closest("#site-nav a")) {
      nav.classList.remove("open");
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal) modal.classList.remove("active");
  });

  // Links like /#login open the login window (used by pages that need a sign-in)
  if (location.hash === "#login") openModal("login");
  if (location.hash === "#signup") openModal("signup");
})();
