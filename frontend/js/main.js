document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================================================
    // 1. Sticky Header & Scroll-to-Top Button
    // ==========================================================================
    const header = document.querySelector('.main-header');
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');

    window.addEventListener('scroll', function() {
        // Sticky header
        if (header) {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }

        // Scroll-to-top button visibility (guard if element missing)
        if (scrollToTopBtn) {
            if (window.scrollY > 300) {
                scrollToTopBtn.classList.add('show');
            } else {
                scrollToTopBtn.classList.remove('show');
            }
        }
    });

    // Scroll back to top
    if (scrollToTopBtn) {
        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // ==========================================================================
    // 2. Mobile Navigation Toggle Drawer
    // ==========================================================================
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    const mobileNav = document.getElementById('mobileNav');
    const mobileOverlay = document.getElementById('mobileOverlay');

    function openMobileMenu() {
        // Reveal drawer to assistive tech and make it interactive
        if (mobileNav) {
            mobileNav.classList.add('open');
            mobileNav.setAttribute('aria-hidden', 'false');
            // remove inert if supported
            try { mobileNav.inert = false; } catch (e) { mobileNav.removeAttribute('inert'); }
        }
        if (mobileOverlay) mobileOverlay.classList.add('open');
        if (mobileMenuToggle) mobileMenuToggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden'; // Disable background scroll

        // Move focus into the drawer (close button) and remember previously focused element
        previousActiveElement = document.activeElement;
        if (mobileMenuClose) mobileMenuClose.focus();
    }

    function closeMobileMenu() {
        if (mobileNav) {
            mobileNav.classList.remove('open');
            mobileNav.setAttribute('aria-hidden', 'true');
            // add inert if supported
            try { mobileNav.inert = true; } catch (e) { mobileNav.setAttribute('inert', ''); }
        }
        if (mobileOverlay) mobileOverlay.classList.remove('open');
        if (mobileMenuToggle) mobileMenuToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = ''; // Re-enable scroll

        // Return focus to the element that opened the drawer
        try {
            if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
                previousActiveElement.focus();
            }
        } catch (err) {
            // ignore
        }
    }

    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', openMobileMenu);
    }

    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', closeMobileMenu);
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', closeMobileMenu);
    }

    // ==========================================================================
    // 3. Tab System (Product Detail Page)
    // ==========================================================================
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');

            // Deactivate all buttons & panes
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));

            // Activate current button & target pane
            this.classList.add('active');
            const targetPane = document.getElementById(targetTab);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });

    // ==========================================================================
    // 4. Contact Form Handler (Natural HTML/PHP Submit is handled by contact.php)
    // ==========================================================================
    // JS simulation removed to enable real MySQL insertions on contact.php

    // ==========================================================================
    // 5. Active Link Highlight Fallback
    // ==========================================================================
    // Handles toggles or extra client interactions
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            this.parentElement.classList.toggle('active');
        });
    });

    // ==========================================================================
    // 6. User Dropdown Menu Toggle (Desktop & Mobile)
    // ==========================================================================
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('open');
            }
        });
    }

    // Theme toggler removed — site uses single light theme

});
