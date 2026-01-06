document.addEventListener("DOMContentLoaded", function() {
    // Print functionality
    const printBtn = document.getElementById('print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }

    // Animation for plan details
    const planDetails = document.querySelectorAll('.detail-section');
    planDetails.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        section.style.transition = 'all 0.5s ease ' + (index * 0.1) + 's';
        
        setTimeout(() => {
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, 100);
    });

    // Hover effects for all buttons
    const buttons = document.querySelectorAll('.action-btn, .btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            if (this.classList.contains('back-btn')) {
                this.style.boxShadow = '0 4px 8px rgba(255, 255, 255, 0.1)';
            } else if (this.classList.contains('send-btn') || this.classList.contains('request-btn')) {
                this.style.boxShadow = '0 4px 12px rgba(255, 107, 0, 0.3)';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });

    // Highlight table rows on hover
    const tableRows = document.querySelectorAll('.workout-table tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(255, 107, 0, 0.1)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // Focus effect for form inputs
    const formInputs = document.querySelectorAll('.form-group input, .form-group textarea');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentNode.querySelector('label').style.color = '#ff8c00';
        });
        
        input.addEventListener('blur', function() {
            this.parentNode.querySelector('label').style.color = 'var(--accent-orange)';
        });
    });
});