/**
 * scramble.js — Hacker text effect
 *
 * On mouseenter: characters randomize → reconstruct to alternate language (EN→FR)
 * On mouseleave: scramble back to original (FR→EN)
 *
 * Usage: add [data-scramble data-en="TEXT" data-fr="TEXTE"] to any element.
 * Requires GSAP as a global (loaded via <script> before this module).
 */

const GLYPHS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789#@$&!?%';
const DURATION_MS = 380;

/**
 * Animate characters from current state → targetText using GSAP ticker.
 * @param {HTMLElement} el
 * @param {string} targetText
 */
function runScramble(el, targetText) {
  const totalFrames = Math.round(DURATION_MS / (1000 / 60)); // ~23 frames @ 60fps
  let frame = 0;

  // Cancel any in-progress animation on this element
  if (el._scrambleTicker) {
    gsap.ticker.remove(el._scrambleTicker);
    el._scrambleTicker = null;
  }

  el._scrambleTicker = () => {
    frame++;
    const progress = frame / totalFrames;

    const chars = Array.from({ length: targetText.length }, (_, i) => {
      const char = targetText[i];

      // Preserve spaces as non-breaking to avoid collapse
      if (char === ' ') return '\u00A0';

      // Staggered reveal: left characters resolve earlier than right
      // revealAt maps i → a threshold in [0.35, 0.90]
      const revealAt = 0.35 + (i / Math.max(targetText.length - 1, 1)) * 0.55;

      if (progress >= revealAt) return char;
      return GLYPHS[Math.floor(Math.random() * GLYPHS.length)];
    });

    el.textContent = chars.join('');

    if (frame >= totalFrames) {
      el.textContent = targetText;
      gsap.ticker.remove(el._scrambleTicker);
      el._scrambleTicker = null;
    }
  };

  gsap.ticker.add(el._scrambleTicker);
}

/**
 * Attach scramble behaviour to all [data-scramble] elements.
 * Called once on DOMContentLoaded.
 */
export function initScramble() {
  document.querySelectorAll('[data-scramble]').forEach(el => {
    const textEN = el.dataset.en;
    const textFR = el.dataset.fr;

    if (!textEN || !textFR) return;

    // Initialise to EN
    el.textContent = textEN;

    // Reserve enough space for the longer variant so layout doesn't shift
    el.style.display     = 'inline-block';
    el.style.minWidth    = `${measureTextWidth(el, textEN, textFR)}px`;

    el.addEventListener('mouseenter', () => runScramble(el, textFR));
    el.addEventListener('mouseleave', () => runScramble(el, textEN));
  });
}

/**
 * Measure the rendered pixel width of the longer of two strings
 * without mutating the element visually.
 * @param {HTMLElement} el
 * @param {...string} texts
 * @returns {number}
 */
function measureTextWidth(el, ...texts) {
  const canvas  = document.createElement('canvas');
  const ctx     = canvas.getContext('2d');
  const style   = getComputedStyle(el);
  ctx.font      = `${style.fontWeight} ${style.fontSize} ${style.fontFamily}`;
  return Math.max(...texts.map(t => ctx.measureText(t).width));
}
