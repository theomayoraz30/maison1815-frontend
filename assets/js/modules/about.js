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

  // Inject inline portrait per member for mobile (CSS shows/hides by breakpoint)
  members.forEach(member => {
    const src = member.dataset.img;
    if (!src) return;
    const img = document.createElement('img');
    img.src = src;
    img.alt = '';
    img.className = 'team__member-img';
    img.draggable = false;
    member.appendChild(img);
  });

  // Desktop hover: floating fixed image
  const hoverImg = document.querySelector('.team__hover-img');
  if (!hoverImg) return;

  members.forEach(member => {
    member.addEventListener('mouseenter', () => {
      const src = member.dataset.img;
      if (src && hoverImg.src !== src) hoverImg.src = src;
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
