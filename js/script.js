// Military-Grade Clipboard Logic (Ultra-Reliable)
window.copyEmailToClipboard = function (btn) {
    const text = btn.getAttribute('data-email');
    if (!text) return;

    const showFeedback = () => {
        // 1. Button Feedback
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.style.background = '#98c379';
        btn.style.color = '#282c33';
        btn.style.borderColor = '#98c379';

        // 2. Premium Toast Notification
        const toast = document.createElement('div');
        toast.className = 'copy-toast';
        toast.innerHTML = `
            <div style="display:flex; align-items:center; gap:12px;">
                <i class="fas fa-copy" style="color:var(--primary-color); font-size:1.2rem;"></i>
                <div style="text-align:left;">
                    <div style="font-size:0.8rem; color:#888; text-transform:uppercase; letter-spacing:1px;">Email Saved to Clipboard</div>
                    <div style="font-weight:600; color:#fff;">${text}</div>
                </div>
            </div>
        `;
        toast.style.cssText = `
            position: fixed; bottom: 40px; left: 50%; transform: translateX(-50%);
            background: rgba(26, 28, 35, 0.95); backdrop-filter: blur(10px); color: #fff; 
            padding: 16px 24px; border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6); border: 1px solid var(--primary-color);
            z-index: 99999; font-size: 1rem;
            animation: slideUpToast 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.style.background = '';
            btn.style.color = '';
            btn.style.borderColor = '';
            toast.style.animation = 'slideDownToast 0.5s ease-in forwards';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    };

    // Try modern API first
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(showFeedback).catch(() => fallbackCopy(text));
    } else {
        fallbackCopy(text);
    }

    function fallbackCopy(txt) {
        const textArea = document.createElement("textarea");
        textArea.value = txt;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showFeedback();
        } catch (err) {
            console.error('Fallback copy failed', err);
            prompt("Copy this email:", txt);
        }
        document.body.removeChild(textArea);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    // Scroll Reveal Animation (Simple Fade-in Up)
    const revealElements = document.querySelectorAll('.project-card, .skill-box, .contact-box, .section-header');

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    revealElements.forEach(el => {
        el.classList.add('reveal-item');
        revealObserver.observe(el);
    });

    // Smooth Scroll for Navigation Links (Only internal hashes)
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (!href || href === '#' || !href.startsWith('#')) return;

            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Get references
    const sections = document.querySelectorAll('section');
    const navLinks = document.querySelectorAll('.nav-link');
    const backToTopButton = document.getElementById("backToTop");

    // Back to Top Button Setup
    if (backToTopButton) {
        // Show/hide on scroll
        window.addEventListener('scroll', function () {
            if (document.documentElement.scrollTop > 200 || document.body.scrollTop > 200) {
                backToTopButton.style.display = "flex";
            } else {
                backToTopButton.style.display = "none";
            }
        });

        // Click to scroll top
        backToTopButton.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Hover effects
        backToTopButton.addEventListener('mouseover', function () {
            this.style.transform = 'scale(1.1)';
            this.style.filter = 'brightness(1.2)';
        });

        backToTopButton.addEventListener('mouseout', function () {
            this.style.transform = 'scale(1)';
            this.style.filter = 'brightness(1)';
        });
    }

    // Navbar active state on scroll
    window.addEventListener('scroll', () => {
        // Navbar Highlight Logic
        // Guard clause: If no sections are found (e.g., on blog page), do not attempt to highlight scroll sections
        if (sections.length === 0) return;

        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollY >= (sectionTop - 200)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (current && link.getAttribute('href').includes(current)) {
                link.classList.add('active');
            }
        });
    });

    // Hamburger Menu Toggle
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = hamburger.querySelector('i');
            if (navMenu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                const icon = hamburger.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });
        });
    }
});
