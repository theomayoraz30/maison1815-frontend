/**
 * navbar.js — Navbar interactions
 *
 * - Hover: GSAP scaleX 0→1 underline + color → orange
 * - Mobile: hamburger toggles full-screen overlay (GSAP fade + stagger)
 * - Scroll: add .is-scrolled when page scrolls past 40px; re-paint link colors via GSAP
 *   (CSS alone can't override GSAP inline styles, so we push the correct color through GSAP)
 * - No-hero pages (about, onset…): immediately add .is-scrolled so links start dark
 */

export function initNavbar() {
  const navbar = document.getElementById('navbar');
  const burger = navbar?.querySelector('.navbar__burger');
  const mobile = document.getElementById('navbar-mobile');
  const links  = navbar?.querySelectorAll('.nav-link');

  if (!navbar) return;

  // CSS handles the initial color correctly via .navbar--over-hero (index only).
  // No JS needed to set initial state — avoid flash-of-white on non-hero pages.

  // Returns the correct "resting" color based on scroll state and page type
  const restingColor = () => {
    if (navbar.classList.contains('is-scrolled')) return 'var(--color-fg)';
    if (navbar.classList.contains('navbar--over-hero')) return '#ffffff';
    return 'var(--color-fg)';
  };

  // ── Hover underline + color (desktop links)
  links?.forEach(link => {
    const line = link.querySelector('.nav-link__line');
    if (!line) return;

    gsap.set(line, { scaleX: 0, transformOrigin: 'left center' });

    link.addEventListener('mouseenter', () => {
      gsap.to(line, { scaleX: 1, duration: 0.35, ease: 'power2.out' });
      gsap.to(link, { color: 'var(--color-primary)', duration: 0.2, ease: 'none', overwrite: 'auto' });
    });

    link.addEventListener('mouseleave', () => {
      gsap.to(line, { scaleX: 0, duration: 0.3, ease: 'power2.in' });
      // Read scroll state at leave time — always correct because onScroll updates is-scrolled synchronously
      gsap.to(link, { color: restingColor(), duration: 0.25, ease: 'none', overwrite: 'auto' });
    });
  });

  // ── Mobile burger menu
  if (burger && mobile) {
    const mobileLinks = mobile.querySelectorAll('.nav-link');
    let isOpen = false;

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

    mobileLinks.forEach(l => l.addEventListener('click', closeMenu));

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && isOpen) closeMenu();
    });
  }

  // ── Scroll state — toggle .is-scrolled and re-paint links via GSAP on each transition
  // CSS selectors can't override GSAP inline styles, so we drive the color change through GSAP
  const onScroll = () => {
    const wasScrolled  = navbar.classList.contains('is-scrolled');
    const isNowScrolled = window.scrollY > 40;

    navbar.classList.toggle('is-scrolled', isNowScrolled);

    // Only re-paint when the state actually flips
    if (wasScrolled !== isNowScrolled) {
      links?.forEach(link => {
        // Skip links currently hovered (they're orange — leave them alone)
        if (!link.matches(':hover')) {
          gsap.to(link, { color: restingColor(), duration: 0.25, ease: 'none', overwrite: 'auto' });
        }
      });
    }
  };

  window.addEventListener('scroll', onScroll, { passive: true });
}
