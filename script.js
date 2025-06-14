/**
 * ASOM Studio Landing Page - JavaScript
 * Vanilla JavaScript for interactions and animations
 */

// ===== GLOBAL VARIABLES =====
let lastScrollY = window.scrollY;
const header = document.querySelector('.header');
const navToggle = document.getElementById('nav-toggle');
const navMenu = document.getElementById('nav-menu');
const contactForm = document.getElementById('contact-form');
const formMessage = document.getElementById('form-message');

// ===== DOM CONTENT LOADED =====
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// ===== INITIALIZE APPLICATION =====
function initializeApp() {
    setupSmoothScrolling();
    setupMobileNavigation();
    setupScrollEffects();
    setupFormHandling();
    setupAnimations();
    setupTypingAnimation();
    setupScrollToTopButton();
    
    // Show page content with fade in animation
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.transition = 'opacity 0.5s ease';
        document.body.style.opacity = '1';
    }, 100);
}

// ===== SMOOTH SCROLLING =====
function setupSmoothScrolling() {
    // Handle navigation link clicks
    const navLinks = document.querySelectorAll('a[href^="#"]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                const offsetTop = targetSection.offsetTop - 80; // Account for fixed header
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                if (navMenu.classList.contains('active')) {
                    closeMobileMenu();
                }
            }
        });
    });
}

// ===== MOBILE NAVIGATION =====
function setupMobileNavigation() {
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', toggleMobileMenu);
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                closeMobileMenu();
            }
        });
        
        // Close menu on window resize if larger than mobile
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
    }
}

function toggleMobileMenu() {
    navToggle.classList.toggle('active');
    navMenu.classList.toggle('active');
    document.body.classList.toggle('menu-open');
}

function closeMobileMenu() {
    navToggle.classList.remove('active');
    navMenu.classList.remove('active');
    document.body.classList.remove('menu-open');
}

// ===== SCROLL EFFECTS =====
function setupScrollEffects() {
    window.addEventListener('scroll', debounce(handleScroll, 10));
}

function handleScroll() {
    const currentScrollY = window.scrollY;
    
    // Header scroll effect
    if (header) {
        if (currentScrollY > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    }
    
    // Parallax effect for hero section
    const hero = document.querySelector('.hero');
    if (hero && currentScrollY < window.innerHeight) {
        const parallaxSpeed = 0.5;
        hero.style.transform = `translateY(${currentScrollY * parallaxSpeed}px)`;
    }
    
    // Animate elements on scroll
    animateOnScroll();
    
    lastScrollY = currentScrollY;
}

// ===== SCROLL ANIMATIONS =====
function setupAnimations() {
    // Add animation classes to elements
    const animatedElements = document.querySelectorAll('.service-card, .contact-item, .stat');
    animatedElements.forEach(el => {
        el.classList.add('animate-on-scroll');
    });
}

function animateOnScroll() {
    const elements = document.querySelectorAll('.animate-on-scroll');
    const windowHeight = window.innerHeight;
    
    elements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const elementVisible = 150;
        
        if (elementTop < windowHeight - elementVisible) {
            element.classList.add('fade-in-up');
        }
    });
}

// ===== TYPING ANIMATION =====
function setupTypingAnimation() {
    const typingElement = document.querySelector('.typing-text');
    if (!typingElement) return;
    
    const originalText = typingElement.textContent;
    const words = ['AI-Powered', 'Innovative', 'Creative', 'Smart'];
    let wordIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    
    function typeAnimation() {
        const currentWord = words[wordIndex];
        
        if (isDeleting) {
            typingElement.textContent = currentWord.substring(0, charIndex - 1);
            charIndex--;
        } else {
            typingElement.textContent = currentWord.substring(0, charIndex + 1);
            charIndex++;
        }
        
        let typeSpeed = isDeleting ? 100 : 150;
        
        if (!isDeleting && charIndex === currentWord.length) {
            typeSpeed = 2000; // Pause at end
            isDeleting = true;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            wordIndex = (wordIndex + 1) % words.length;
            typeSpeed = 500; // Pause before typing new word
        }
        
        setTimeout(typeAnimation, typeSpeed);
    }
    
    // Start animation after a delay
    setTimeout(typeAnimation, 1000);
}

// ===== FORM HANDLING =====
function setupFormHandling() {
    if (contactForm) {
        contactForm.addEventListener('submit', handleFormSubmit);
        
        // Real-time validation
        const inputs = contactForm.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    if (!validateForm()) {
        return;
    }
    
    const submitButton = contactForm.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    
    // Show loading state
    submitButton.textContent = 'Sending...';
    submitButton.disabled = true;
    hideFormMessage();
    
    try {
        const formData = new FormData(contactForm);
        
        const response = await fetch('contact.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showFormMessage('success', result.message || 'Message sent successfully! We\'ll get back to you soon.');
            contactForm.reset();
        } else {
            showFormMessage('error', result.message || 'Something went wrong. Please try again.');
        }
    } catch (error) {
        console.error('Form submission error:', error);
        showFormMessage('error', 'Network error. Please check your connection and try again.');
    } finally {
        // Reset button state
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    }
}

function validateForm() {
    const requiredFields = contactForm.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField({ target: field })) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(e) {
    const field = e.target;
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Remove existing error styling
    field.classList.remove('error');
    removeFieldError(field);
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        errorMessage = 'This field is required';
        isValid = false;
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            errorMessage = 'Please enter a valid email address';
            isValid = false;
        }
    }
    
    // Name validation (no numbers)
    if (field.name === 'name' && value) {
        const nameRegex = /^[a-zA-Z\s'-]+$/;
        if (!nameRegex.test(value)) {
            errorMessage = 'Please enter a valid name';
            isValid = false;
        }
    }
    
    if (!isValid) {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    let errorElement = field.parentNode.querySelector('.field-error');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
}

function removeFieldError(field) {
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

function clearFieldError(e) {
    const field = e.target;
    if (field.classList.contains('error') && field.value.trim()) {
        field.classList.remove('error');
        removeFieldError(field);
    }
}

function showFormMessage(type, message) {
    if (formMessage) {
        formMessage.className = `form-message ${type}`;
        formMessage.textContent = message;
        formMessage.style.display = 'block';
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(() => {
                hideFormMessage();
            }, 5000);
        }
    }
}

function hideFormMessage() {
    if (formMessage) {
        formMessage.style.display = 'none';
    }
}

// ===== SCROLL TO TOP BUTTON =====
function setupScrollToTopButton() {
    // Create scroll to top button
    const scrollButton = document.createElement('button');
    scrollButton.innerHTML = '↑';
    scrollButton.className = 'scroll-to-top';
    scrollButton.setAttribute('aria-label', 'Scroll to top');
    scrollButton.style.cssText = `
        position: fixed;
        bottom: 120px;
        right: 2rem;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: var(--color-accent-primary);
        color: white;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 999;
        box-shadow: var(--shadow-card);
    `;
    
    document.body.appendChild(scrollButton);
    
    // Show/hide based on scroll position
    window.addEventListener('scroll', debounce(() => {
        if (window.scrollY > 500) {
            scrollButton.style.opacity = '1';
            scrollButton.style.visibility = 'visible';
        } else {
            scrollButton.style.opacity = '0';
            scrollButton.style.visibility = 'hidden';
        }
    }, 100));
    
    // Scroll to top on click
    scrollButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// ===== UTILITY FUNCTIONS =====
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        
        if (callNow) func.apply(context, args);
    };
}

function isElementInViewport(el) {
    const rect = el.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// ===== PERFORMANCE OPTIMIZATIONS =====
// Preload critical resources
function preloadResources() {
    const criticalImages = [
        // Add any critical images here
    ];
    
    criticalImages.forEach(src => {
        const img = new Image();
        img.src = src;
    });
}

// ===== ERROR HANDLING =====
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
    // You could send error reports to your analytics service here
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    // Handle unhandled promise rejections
});

// ===== ACCESSIBILITY ENHANCEMENTS =====
document.addEventListener('keydown', function(e) {
    // Escape key closes mobile menu
    if (e.key === 'Escape' && navMenu.classList.contains('active')) {
        closeMobileMenu();
    }
});

// Focus management for mobile menu
navToggle.addEventListener('click', function() {
    setTimeout(() => {
        if (navMenu.classList.contains('active')) {
            const firstLink = navMenu.querySelector('a');
            if (firstLink) firstLink.focus();
        }
    }, 100);
});

// ===== ADDITIONAL CSS FOR DYNAMIC ELEMENTS =====
const additionalStyles = `
    .field-error {
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    .form-group input.error,
    .form-group select.error,
    .form-group textarea.error {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
    
    .header.scrolled {
        background-color: rgba(255, 255, 255, 0.98);
        box-shadow: var(--shadow-subtle);
    }
    
    .scroll-to-top:hover {
        background-color: var(--color-accent-secondary);
        transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
        .scroll-to-top {
            bottom: 100px !important;
            right: 1rem !important;
            width: 45px !important;
            height: 45px !important;
            font-size: 1.25rem !important;
        }
    }
`;

// Inject additional styles
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);

// ===== INITIALIZE PRELOADING =====
preloadResources();

// ===== CONSOLE SIGNATURE =====
console.log('%c🚀 ASOM Studio - Landing Page Loaded Successfully', 'color: #FF6B35; font-size: 16px; font-weight: bold;');
console.log('%cBuilt with ❤️ using Vanilla JavaScript', 'color: #4A4A4A; font-size: 12px;'); 