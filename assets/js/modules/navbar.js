/**
 * navbar.js — Navbar interactions
 *
 * - Hover: GSAP scaleX 0→1 underline + color → orange
 * - Mobile: hamburger toggles full-screen overlay (GSAP fade + stagger)
 * - Scroll: add .is-scrolled class when page scrolls past 40px
 */

export function initNavbar() {
  const navbar  = document.getElementById('navbar');
  const burger  = navbar?.querySelector('.navbar__burger');
  const mobile  = document.getElementById('navbar-mobile');
  const links   = navbar?.querySelectorAll('.nav-link');

  if (!navbar) return;

  // ── Hover underline + color (desktop links)
  links?.forEach(link => {
    const line = link.querySelector('.nav-link__line');
    if (!line) return;

    // Ensure GSAP starts from the correct initial state
    gsap.set(line, { scaleX: 0, transformOrigin: 'left center' });

    link.addEventListener('mouseenter', () => {
      gsap.to(line, { scaleX: 1, duration: 0.35, ease: 'power2.out' });
      gsap.to(link, { color: 'var(--color-primary)', duration: 0.2, ease: 'none' });
    });

    link.addEventListener('mouseleave', () => {
      gsap.to(line, { scaleX: 0, duration: 0.3, ease: 'power2.in' });
      // Restore white over hero video, dark once scrolled onto white background
      const baseColor = navbar.classList.contains('is-scrolled')
        ? 'var(--color-fg)'
        : '#ffffff';
      gsap.to(link, { color: baseColor, duration: 0.25, ease: 'none' });
    });
  });

  // ── Mobile burger menu
  if (burger && mobile) {
    const mobileLinks = mobile.querySelectorAll('.nav-link');
    let isOpen = false;

    // Prep mobile links for stagger entrance
    gsap.set(mobileLinks, { y: 20, opacity: 0 });

    function openMenu() {
      isOpen = true;
      burger.classList.add('is-open');
      burger.setAttribute('aria-expanded', 'true');
      mobile.style.pointerEvents = 'all';

      gsap.to(mobile, {
        opacity: 1, duration: 0.4, ease: 'power2.out',
        onStart: () => { mobile.style.display = 'flex'; },
      });

      gsap.to(mobileLinks, {
        y: 0, opacity: 1,
        duration: 0.5, stagger: 0.07, ease: 'power3.out',
        delay: 0.1,
      });
    }

    function closeMenu() {
      isOpen = false;
      burger.classList.remove('is-open');
      burger.setAttribute('aria-expanded', 'false');

      gsap.to(mobile, {
        opacity: 0, duration: 0.3, ease: 'power2.in',
        onComplete: () => {
          mobile.style.pointerEvents = 'none';
          mobile.style.display = 'none';
        },
      });

      gsap.to(mobileLinks, {
        y: 12, opacity: 0, duration: 0.25, stagger: 0.04,
      });
    }

    burger.addEventListener('click', () => {
      isOpen ? closeMenu() : openMenu();
    });

    // Close on link click
    mobileLinks.forEach(l => l.addEventListener('click', closeMenu));

    // Close on Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && isOpen) closeMenu();
    });
  }

  // ── Scroll state (darkens navbar background)
  const onScroll = () => {
    navbar.classList.toggle('is-scrolled', window.scrollY > 40);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
}
