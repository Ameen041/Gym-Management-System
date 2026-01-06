// Simple admin panel functionality
document.addEventListener('DOMContentLoaded', function() {
  // Toggle sidebar on mobile
  const sidebarToggle = document.querySelector('.sidebar-toggle');
  const sidebar = document.querySelector('.sidebar');
  
  if (sidebarToggle) {
      sidebarToggle.addEventListener('click', function() {
          sidebar.classList.toggle('active');
      });
  }
  
  // Auto-dismiss alerts
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
      setTimeout(() => {
          alert.style.opacity = '0';
          alert.style.transition = 'opacity 0.5s';
          setTimeout(() => {
              if (alert.parentNode) {
                  alert.remove();
              }
          }, 500);
      }, 5000);
  });
  
  // Confirm delete actions
  const deleteForms = document.querySelectorAll('form[onsubmit*="confirm"]');
  deleteForms.forEach(form => {
      form.addEventListener('submit', function(e) {
          if (!confirm('Are you sure you want to delete this?')) {
              e.preventDefault();
          }
      });
  });
  
  // Add hover effect to table rows
  const tableRows = document.querySelectorAll('.table tbody tr');
  tableRows.forEach(row => {
      row.addEventListener('mouseenter', function() {
          this.style.backgroundColor = 'var(--gray-300)';
      });
      
      row.addEventListener('mouseleave', function() {
          this.style.backgroundColor = '';
      });
  });
  
  // Template type format helper
  const typeSelect = document.getElementById('type');
  if (typeSelect) {
      typeSelect.addEventListener('change', function() {
          // Format guide will update when form is submitted
          console.log('Template type changed to:', this.value);
      });
  }
  
  // Form validation
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
      form.addEventListener('submit', function(e) {
          const requiredFields = this.querySelectorAll('[required]');
          let isValid = true;
          
          requiredFields.forEach(field => {
              if (!field.value.trim()) {
                  isValid = false;
                  field.classList.add('error');
                  
                  // Create error message if not exists
                  if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('text-danger')) {
                      const errorMsg = document.createElement('div');
                      errorMsg.className = 'text-danger';
                      errorMsg.textContent = 'This field is required';
                      field.parentNode.appendChild(errorMsg);
                  }
              } else {
                  field.classList.remove('error');
                  
                  // Remove error message if exists
                  const errorMsg = field.parentNode.querySelector('.text-danger');
                  if (errorMsg) {
                      errorMsg.remove();
                  }
              }
          });
          
          if (!isValid) {
              e.preventDefault();
          }
      });
  });
  
  // Clear error on input
  const inputs = document.querySelectorAll('input, textarea, select');
  inputs.forEach(input => {
      input.addEventListener('input', function() {
          this.classList.remove('error');
          
          const errorMsg = this.parentNode.querySelector('.text-danger');
          if (errorMsg) {
              errorMsg.remove();
          }
      });
  });
});