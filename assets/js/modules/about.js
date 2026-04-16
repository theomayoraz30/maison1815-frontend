/**
 * about.js — About page interactions
 *
 * - Team member hover: floating image follows cursor (GSAP)
 * - Office image: subtle scale reveal on scroll (ScrollTrigger)
 */

export function initAbout() {
  initTeamHover();
  initOfficeReveal();
}

function initTeamHover() {
  const members = document.querySelectorAll('.team__member');
  if (!members.length) return;

  const hoverImg = document.querySelector('.team__hover-img');
  if (!hoverImg) return;

  members.forEach(member => {
    member.addEventListener('mouseenter', () => {
      const src = member.dataset.img;
      if (src && hoverImg.src !== src) hoverImg.src = src;
      // Instant position — no easing on show, image is already pinned via CSS (right: 0, top: 50%)
      gsap.set(hoverImg, { opacity: 1 });
    });

    member.addEventListener('mouseleave', () => {
      gsap.set(hoverImg, { opacity: 0 });
    });
  });
}

function initOfficeReveal() {
  // No scroll-based motion — static image only
}
