/**
 * accordion.js — GSAP height accordion
 *
 * Click: GSAP height 0 → auto + opacity fade.
 * Click again: collapse. Only one item open at a time.
 * Used on project-video.html for the Team section.
 */

export function initAccordion() {
  const items = document.querySelectorAll('.accordion__item');
  if (!items.length) return;

  items.forEach(item => {
    const trigger = item.querySelector('.accordion__trigger');
    const body    = item.querySelector('.accordion__body');
    if (!trigger || !body) return;

    trigger.addEventListener('click', () => {
      const isOpen = item.classList.contains('is-open');

      // Collapse all open items
      items.forEach(other => {
        if (other !== item && other.classList.contains('is-open')) {
          other.classList.remove('is-open');
          other.querySelector('.accordion__trigger').setAttribute('aria-expanded', 'false');
          gsap.to(other.querySelector('.accordion__body'), {
            height: 0, opacity: 0,
            duration: 0.35, ease: 'power3.inOut',
          });
        }
      });

      if (isOpen) {
        item.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        gsap.to(body, { height: 0, opacity: 0, duration: 0.35, ease: 'power3.inOut' });
      } else {
        item.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        gsap.to(body, { height: 'auto', opacity: 1, duration: 0.45, ease: 'power3.out' });
      }
    });
  });
}
