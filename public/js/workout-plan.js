   document.addEventListener("DOMContentLoaded", function () {
   
    const printBtn = document.getElementById("print-btn");
    if (printBtn) {
      printBtn.addEventListener("click", function () {
        window.print();
      });
    }
  
    const planDetails = document.querySelectorAll(".detail-section");
    planDetails.forEach((section, index) => {
      section.style.opacity = "0";
      section.style.transform = "translateY(20px)";
      section.style.transition = "all 0.5s ease " + index * 0.1 + "s";
  
      setTimeout(() => {
        section.style.opacity = "1";
        section.style.transform = "translateY(0)";
      }, 100);
    });
  
    const buttons = document.querySelectorAll(".action-btn, .btn");
    buttons.forEach((button) => {
      button.addEventListener("mouseenter", function () {
        this.style.transform = "translateY(-2px)";
        this.style.boxShadow = "0 4px 12px rgba(67, 97, 238, 0.25)";
      });
  
      button.addEventListener("mouseleave", function () {
        this.style.transform = "translateY(0)";
        this.style.boxShadow = "none";
      });
    });
  
    const tableRows = document.querySelectorAll(".workout-table tr");
    tableRows.forEach((row) => {
      row.addEventListener("mouseenter", function () {
     
        this.style.backgroundColor = "rgba(67, 97, 238, 0.08)";
      });
  
      row.addEventListener("mouseleave", function () {
        this.style.backgroundColor = "";
      });
    });
  
  
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach((alert) => {
      setTimeout(() => {
        alert.style.transition = "opacity 0.5s ease";
        alert.style.opacity = "0";
        setTimeout(() => {
          alert.remove();
        }, 500);
      }, 5000);
    });
  
    const formInputs = document.querySelectorAll(
      ".form-group input, .form-group textarea, .form-group select"
    );
  
    formInputs.forEach((input) => {
      input.addEventListener("focus", function () {
        const label = this.parentNode.querySelector("label");
        if (label) label.style.color = "#4361ee"; 
      });
  
      input.addEventListener("blur", function () {
        const label = this.parentNode.querySelector("label");
        if (label) label.style.color = "var(--accent-orange)"; 
      });
    });
  });