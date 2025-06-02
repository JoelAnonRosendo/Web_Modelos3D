// animation.js
document.addEventListener('DOMContentLoaded', () => {

    // -------------------------------------
    // 1. SCROLL SUAVE CON LENIS
    // -------------------------------------
    const lenis = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        smoothTouch: true,
        touchMultiplier: 1.5,
    });

    function raf(time) {
        lenis.raf(time);
        requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetId === "#" || (targetId.startsWith("#") && targetElement)) {
                e.preventDefault();
                if (targetElement) {
                    lenis.scrollTo(targetElement);
                } else if (targetId === "#" || targetId === "#home") {
                    lenis.scrollTo(0);
                }
            }
        });
    });

    // -----------------------------------------------------
    // 2. ANIMACIONES AL HACER SCROLL CON GSAP & SCROLLTRIGGER
    // -----------------------------------------------------
    gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);

    gsap.utils.toArray('.product-grid h2, .categories-section h2, .about-us h2, .auth-form h2, .admin-form h2, .model-detail-container .model-header h1, .admin-panel-container h2').forEach(title => {
        gsap.from(title, { opacity: 0, y: 30, duration: 0.8, ease: 'power2.out', scrollTrigger: { trigger: title, start: 'top 85%', toggleActions: 'play none none reverse' } });
    });

    gsap.utils.toArray('.product-card').forEach((card, index) => {
        gsap.from(card, { opacity: 0, y: 50, scale: 0.95, duration: 0.6, ease: 'power3.out', scrollTrigger: { trigger: card, start: 'top 90%', toggleActions: 'play none none reverse' }, delay: (index % (ScrollTrigger.isMobile ? 2 : (document.defaultView.innerWidth > 992 ? 4 : 3))) * 0.07 });
    });
    
    const heroContainer = document.querySelector('.hero > .container');
    if (heroContainer && heroContainer.children.length > 0) {
        gsap.from(heroContainer.children, { opacity: 0, y: 30, duration: 0.8, ease: 'power2.out', stagger: 0.2, delay: 0.3, scrollTrigger: { trigger: heroContainer, start: 'top 70%', toggleActions: 'play none none none' } });
    }

    gsap.utils.toArray('.category-item').forEach((item, index) => {
        gsap.from(item, { opacity: 0, y: 20, scale: 0.9, duration: 0.5, ease: 'power1.out', scrollTrigger: { trigger: item.parentNode, start: 'top 85%', toggleActions: 'play none none reverse' }, delay: index * 0.05 });
    });
    
    gsap.utils.toArray('.about-us p, .model-info .description p, .admin-panel-container > p:not(.message)').forEach(p => {
         gsap.from(p, { opacity: 0, y: 20, duration: 1, ease: 'power2.out', scrollTrigger: { trigger: p, start: 'top 80%', toggleActions: 'play none none reverse' } });
    });

    gsap.utils.toArray('.admin-form .form-group, .auth-form .form-group').forEach((group, index) => {
        gsap.from(group, { opacity: 0, x: -30, duration: 0.5, ease: 'power2.out', delay: index * 0.05, scrollTrigger: { trigger: group.closest('form'), start: 'top 80%', end: 'bottom 20%', toggleActions: 'play none none none', once: true } });
    });

    const adminTableRows = document.querySelectorAll('.admin-table tbody tr');
    if (adminTableRows.length > 0) {
        gsap.from(adminTableRows, { opacity: 0, y: 20, duration: 0.4, stagger: 0.05, ease: 'power2.out', scrollTrigger: { trigger: '.admin-table', start: 'top 85%', toggleActions: 'play none none none', once: true } });
    }
    if (document.querySelector('header .logo h1')) {
      gsap.from('header .logo h1', { opacity:0, x:-40, duration: 0.6, delay: 0.2, ease: 'power3.out' });
    }
    if (document.querySelector('header nav ul')) {
      gsap.from('header nav ul', { opacity:0, y:-20, duration: 0.6, delay: 0.3, ease: 'power3.out' });
    }

    gsap.utils.toArray('.btn, .product-card, .category-item').forEach(el => {
        const isProductCard = el.classList.contains('product-card');
        el.addEventListener('mouseenter', () => {
            gsap.to(el, { y: -3, scale: isProductCard ? 1.02 : 1.05, boxShadow: isProductCard ? '0 8px 25px rgba(0,0,0,0.15)' : el.style.boxShadow, duration: 0.2, ease: 'power2.out' });
        });
        el.addEventListener('mouseleave', () => {
            gsap.to(el, { y: 0, scale: 1, boxShadow: isProductCard ? '0 4px 15px rgba(0,0,0,0.1)' : el.style.boxShadow, duration: 0.2, ease: 'power2.out' });
        });
    });
    
    ScrollTrigger.refresh();


}); // Fin de DOMContentLoaded