/**
 * lightbox.js — Fullscreen image viewer for the onset gallery
 *
 * Click any figure to open. Navigate with:
 * - Arrow buttons
 * - Keyboard ← →
 * - Mouse wheel / trackpad scroll
 * - Click backdrop or press Escape to close
 */

export function initLightbox() {
  const gallery = document.querySelector('.onset-gallery');
  if (!gallery) return;

  const figures = [...gallery.querySelectorAll('figure')];
  const srcs    = figures.map(f => f.querySelector('img').src);
  if (!srcs.length) return;

  // ── Build and inject lightbox DOM
  const lb = document.createElement('div');
  lb.id        = 'lightbox';
  lb.className = 'lightbox';
  lb.setAttribute('role', 'dialog');
  lb.setAttribute('aria-modal', 'true');
  lb.setAttribute('aria-label', 'Visionneuse');
  lb.innerHTML = `
    <button class="lightbox__close" aria-label="Fermer">
      <i class="bx bx-x" aria-hidden="true"></i>
    </button>

    <button class="lightbox__nav lightbox__prev" aria-label="Image précédente">
      <i class="bx bx-chevron-left" aria-hidden="true"></i>
    </button>

    <div class="lightbox__stage">
      <img class="lightbox__img" src="" alt="" draggable="false">
    </div>

    <button class="lightbox__nav lightbox__next" aria-label="Image suivante">
      <i class="bx bx-chevron-right" aria-hidden="true"></i>
    </button>

    <span class="lightbox__counter" aria-live="polite"></span>
  `;
  document.body.appendChild(lb);

  const img     = lb.querySelector('.lightbox__img');
  const counter = lb.querySelector('.lightbox__counter');
  const btnPrev = lb.querySelector('.lightbox__prev');
  const btnNext = lb.querySelector('.lightbox__next');
  const btnClose= lb.querySelector('.lightbox__close');

  let current   = 0;
  let navigating = false; // debounce wheel

  // ── State helpers
  function updateCounter() {
    counter.textContent = `${current + 1} — ${srcs.length}`;
  }

  function open(index) {
    current   = index;
    img.src   = srcs[current];
    updateCounter();

    lb.classList.add('is-open');
    document.body.style.overflow = 'hidden';

    gsap.killTweensOf([lb, img]);
    gsap.set(lb,  { opacity: 0 });
    gsap.set(img, { scale: 0.96, opacity: 0 });
    gsap.to(lb,   { opacity: 1, duration: 0.3,  ease: 'power2.out' });
    gsap.to(img,  { scale: 1,   opacity: 1, duration: 0.4, ease: 'power3.out', delay: 0.05 });
  }

  function close() {
    gsap.to(lb, {
      opacity: 0,
      duration: 0.25,
      ease: 'power2.in',
      onComplete: () => {
        lb.classList.remove('is-open');
        document.body.style.overflow = '';
        img.src = '';
      },
    });
  }

  function navigate(dir) {
    const next = (current + dir + srcs.length) % srcs.length;
    const xOut = dir * -48;
    const xIn  = dir * 48;

    gsap.to(img, {
      opacity: 0,
      x: xOut,
      duration: 0.18,
      ease: 'power2.in',
      onComplete: () => {
        current = next;
        img.src = srcs[current];
        updateCounter();
        gsap.fromTo(img,
          { opacity: 0, x: xIn },
          { opacity: 1, x: 0, duration: 0.22, ease: 'power3.out' }
        );
      },
    });
  }

  // ── Click to open
  figures.forEach((figure, i) => {
    figure.addEventListener('click', () => open(i));
  });

  // ── Controls
  btnClose.addEventListener('click', close);
  btnPrev.addEventListener('click',  () => navigate(-1));
  btnNext.addEventListener('click',  () => navigate(1));

  // Click backdrop (not image or buttons)
  lb.addEventListener('click', e => {
    if (e.target === lb || e.target.classList.contains('lightbox__stage')) close();
  });

  // Keyboard
  document.addEventListener('keydown', e => {
    if (!lb.classList.contains('is-open')) return;
    if (e.key === 'Escape')     { e.preventDefault(); close(); }
    if (e.key === 'ArrowLeft')  { e.preventDefault(); navigate(-1); }
    if (e.key === 'ArrowRight') { e.preventDefault(); navigate(1); }
  });

  // Mouse wheel / trackpad scroll
  lb.addEventListener('wheel', e => {
    e.preventDefault();
    if (navigating) return;
    navigating = true;
    navigate(e.deltaY > 0 ? 1 : -1);
    setTimeout(() => { navigating = false; }, 350);
  }, { passive: false });
}
