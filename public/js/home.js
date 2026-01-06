document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    const authButtons = document.querySelector('.auth-buttons');
    
    if (mobileMenuBtn) {
        
        function initMenu() {
            if (window.innerWidth <= 992) {
                navLinks.style.display = 'none';
                authButtons.style.display = 'none';
            } else {
                navLinks.style.display = 'flex';
                authButtons.style.display = 'flex';
            }
        }
        
        initMenu();
        window.addEventListener('resize', initMenu);
        
        mobileMenuBtn.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                const isOpening = navLinks.style.display === 'none';
                
                navLinks.style.display = isOpening ? 'flex' : 'none';
                authButtons.style.display = isOpening ? 'flex' : 'none';
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-times');
                icon.classList.toggle('fa-bars');
                
                document.body.style.overflow = isOpening ? 'hidden' : 'auto';
            }
        });
    }

});
    
    // Auth Modal
   
const loginBtn = document.querySelector('.login-btn');
const signupBtn = document.querySelector('.signup-btn');
const authModal = document.querySelector('.auth-modal');
const closeModal = document.querySelector('.close-modal');
const authTabs = document.querySelectorAll('.auth-tab');
const authForms = document.querySelectorAll('.auth-form');

// Show modal functions
function showLoginModal() {
    authModal.classList.add('active');
    switchAuthTab('login');
}

function showSignupModal() {
    authModal.classList.add('active');
    switchAuthTab('signup');
}

// Event listeners
if (loginBtn) {
    loginBtn.addEventListener('click', function(e) {
        e.preventDefault();
        showLoginModal();
    });
}

if (signupBtn) {
    signupBtn.addEventListener('click', function(e) {
        e.preventDefault();
        showSignupModal();
    });
}

if (closeModal) {
    closeModal.addEventListener('click', function() {
        authModal.classList.remove('active');
    });
}

// Tab switching
authTabs.forEach(tab => {
    tab.addEventListener('click', function() {
        const tabId = this.getAttribute('data-tab');
        switchAuthTab(tabId);
    });
});

function switchAuthTab(tabId) {
    authTabs.forEach(tab => {
        tab.classList.toggle('active', tab.getAttribute('data-tab') === tabId);
    });
    
    authForms.forEach(form => {
        form.classList.toggle('active', form.id === tabId);
    });
}

// Close modal when clicking outside
if (closeModal) {
    closeModal.addEventListener('click', function() {
        authModal.classList.remove('active');
    });
}
    
    // Programs Tabs
    const programTabs = document.querySelectorAll('.tab-btn');
    const programContents = document.querySelectorAll('.tab-content');
    
    programTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Update Active Tab
            programTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show Corresponding Content
            programContents.forEach(content => {
                if (content.id === tabId) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
        });
    });
    
    // Smooth Scrolling for Anchor Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                if (navLinks.classList.contains('show')) {
                    mobileMenuBtn.click();
                }
            }
        });
    });
    
    // Header Scroll Effect
    window.addEventListener('scroll', function() {
        const header = document.querySelector('.header');
        if (window.scrollY > 50) {
            header.style.background = 'rgba(18, 18, 18, 0.95)';
            header.style.padding = '10px 0';
        } else {
            header.style.background = 'rgba(18, 18, 18, 0.9)';
            header.style.padding = '20px 0';
        }
    });
    
    // Testimonials Slider
    const testimonialsSlider = document.querySelector('.testimonials-slider');
    if (testimonialsSlider) {
        let isDown = false;
        let startX;
        let scrollLeft;
        
        testimonialsSlider.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - testimonialsSlider.offsetLeft;
            scrollLeft = testimonialsSlider.scrollLeft;
        });
        
        testimonialsSlider.addEventListener('mouseleave', () => {
            isDown = false;
        });
        
        testimonialsSlider.addEventListener('mouseup', () => {
            isDown = false;
        });
        
        testimonialsSlider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - testimonialsSlider.offsetLeft;
            const walk = (x - startX) * 2;
            testimonialsSlider.scrollLeft = scrollLeft - walk;
        });
    }
    
    // Button Hover Effects
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
            if (this.classList.contains('btn-primary')) {
                this.style.boxShadow = '0 5px 15px rgba(255, 107, 0, 0.4)';
            }
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
