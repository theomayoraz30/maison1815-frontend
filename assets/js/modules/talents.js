/**
 * talents.js — Talent list hover interactions
 *
 * - On page load: assign a random orange hue to each talent name
 * - On hover: asset appears at random viewport position and STAYS visible
 *   until hovering a different talent (cross-fade) or mouse leaves the list
 */

export function initTalents() {
  const items   = document.querySelectorAll('.talent__item');
  const hoverEl = document.querySelector('.talent__hover-asset');

  if (!items.length) return;

  // Assign a unique random orange shade to each name
  items.forEach(item => {
    const hue       = 20 + Math.random() * 15;      // 20–35° orange band
    const lightness = 45 + Math.random() * 15;      // 45–60% vivid
    const nameEl    = item.querySelector('.talent__name');
    if (nameEl) nameEl.style.color = `hsl(${hue}, 100%, ${lightness}%)`;
  });

  if (!hoverEl) return;

  const ASSET_W = 300;
  const ASSET_H = 400;
  const MARGIN  = 60;

  let currentItem = null;

  function randomPosition() {
    const x = MARGIN + Math.random() * Math.max(0, window.innerWidth  - ASSET_W - MARGIN * 2);
    const y = MARGIN + Math.random() * Math.max(0, window.innerHeight - ASSET_H - MARGIN * 2);
    return { x, y };
  }

  function updateMedia(src, type) {
    hoverEl.innerHTML = type === 'video'
      ? `<video src="${src}" autoplay muted loop playsinline></video>`
      : `<img src="${src}" alt="" draggable="false">`;
  }

  function showAsset(item) {
    // Same talent — do nothing, image stays as-is
    if (item === currentItem) return;
    currentItem = item;

    const src  = item.dataset.asset;
    const type = item.dataset.type || 'image';
    if (!src) return;

    const { x, y } = randomPosition();
    const isVisible = parseFloat(gsap.getProperty(hoverEl, 'opacity')) > 0.01;

    if (isVisible) {
      // Cross-fade: quick fade out → swap content → fade in at new position
      gsap.to(hoverEl, {
        opacity: 0,
        duration: 0.15,
        ease: 'power2.in',
        onComplete: () => {
          updateMedia(src, type);
          gsap.set(hoverEl, { x, y });
          gsap.to(hoverEl, { opacity: 1, duration: 0.2, ease: 'power3.out' });
        },
      });
    } else {
      // First appearance: fade + scale in
      updateMedia(src, type);
      gsap.set(hoverEl,  { x, y, scale: 0.92, opacity: 0 });
      gsap.to(hoverEl,   { opacity: 1, scale: 1, duration: 0.25, ease: 'power3.out' });
    }
  }

  function hideAsset() {
    currentItem = null;
    gsap.to(hoverEl, {
      opacity: 0,
      scale: 0.92,
      duration: 0.2,
      ease: 'power2.in',
      onComplete: () => { hoverEl.innerHTML = ''; },
    });
  }

  // Show / persist on item hover
  items.forEach(item => {
    item.addEventListener('mouseenter', () => showAsset(item));
  });

  // Hide only when the cursor exits the entire list
  const list = document.querySelector('.talent__list');
  if (list) list.addEventListener('mouseleave', hideAsset);
}
