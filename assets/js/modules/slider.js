/**
 * slider.js — Clients infinite ticker
 *
 * Infinite horizontal scroll driven by GSAP ticker (frame-rate independent).
 * Hover → pause.  Mouse + touch drag → scrub offset directly.
 */

export function initSlider() {
  const ticker = document.querySelector('.ticker');
  const track  = document.querySelector('.ticker__track');
  if (!ticker || !track) return;

  const SPEED = 55; // pixels per second

  let offset    = 0;
  let paused    = false;
  let dragging  = false;
  let dragLastX = 0;

  // Measure the width of one list copy for seamless wrap.
  // Uses rAF to ensure layout is complete before measuring.
  const list = track.querySelector('.ticker__list');
  if (!list) return;

  // Wait one paint so getBoundingClientRect is accurate
  requestAnimationFrame(() => {
    const listWidth = list.getBoundingClientRect().width;
    if (listWidth === 0) return;

    // ── Continuous tick — runs every GSAP frame
    const tick = (_time, deltaTime) => {
      if (!paused && !dragging) {
        offset -= SPEED * (deltaTime / 1000);
        // Seamless wrap: when first copy scrolls out, jump back
        if (offset <= -listWidth) offset += listWidth;
        if (offset > 0) offset = 0;
      }
      gsap.set(track, { x: offset });
    };

    gsap.ticker.add(tick);

    // ── Hover: pause the scroll
    ticker.addEventListener('mouseenter', () => { paused = true;  });
    ticker.addEventListener('mouseleave', () => { paused = false; });

    // ── Mouse drag
    const onMouseDown = e => {
      dragging  = true;
      paused    = true;
      dragLastX = e.clientX;
      ticker.style.cursor = 'pointer';
      e.preventDefault(); // prevent text selection while dragging
    };

    const onMouseMove = e => {
      if (!dragging) return;
      const delta = e.clientX - dragLastX;
      dragLastX   = e.clientX;
      offset += delta;
      // Keep offset in [-listWidth, 0] range
      while (offset > 0)           offset -= listWidth;
      while (offset <= -listWidth) offset += listWidth;
    };

    const onMouseUp = () => {
      if (!dragging) return;
      dragging  = false;
      paused    = false;
      ticker.style.cursor = 'pointer';
    };

    ticker.addEventListener('mousedown', onMouseDown);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup',   onMouseUp);

    // ── Touch drag
    ticker.addEventListener('touchstart', e => {
      dragging  = true;
      paused    = true;
      dragLastX = e.touches[0].clientX;
    }, { passive: true });

    ticker.addEventListener('touchmove', e => {
      if (!dragging) return;
      const delta = e.touches[0].clientX - dragLastX;
      dragLastX   = e.touches[0].clientX;
      offset += delta;
      while (offset > 0)           offset -= listWidth;
      while (offset <= -listWidth) offset += listWidth;
    }, { passive: true });

    ticker.addEventListener('touchend', () => {
      dragging = false;
      paused   = false;
    });
  });
}
