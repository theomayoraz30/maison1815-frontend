/**
 * main.js — Entry point
 *
 * Imports and initialises all modules.
 * GSAP and ScrollTrigger are loaded as globals via <script> tags in the HTML.
 */

import { initScramble    } from './modules/scramble.js';
import { initNavbar      } from './modules/navbar.js';
import { initLang        } from './modules/lang.js';
import { initSlider      } from './modules/slider.js';
import { initCards       } from './modules/cards.js';
import { initTalents     } from './modules/talents.js';
import { initAccordion   } from './modules/accordion.js';
import { initAbout       } from './modules/about.js';
import { initLightbox    } from './modules/lightbox.js';
import { initVideoPlayer } from './modules/videoPlayer.js';

// Register GSAP plugins once
if (typeof ScrollTrigger !== 'undefined') {
  gsap.registerPlugin(ScrollTrigger);
}

document.addEventListener('DOMContentLoaded', () => {
  initNavbar();
  initLang();
  initScramble();
  initHero();
  initSlider();
  initCards();
  initTalents();
  initAccordion();
  initAbout();
  initLightbox();
  initVideoPlayer();
});

/**
 * initHero — Page-load entrance animation for the hero split layout.
 * Logo slides in from the left, description from the right.
 * CSS sets opacity:0 on both columns; GSAP drives them in.
 */
function initHero() {
  const logo   = document.querySelector('.hero__logo-img-wrap');
  const desc   = document.querySelector('.hero__desc');
  const scroll = document.querySelector('.hero__scroll');

  if (!logo && !desc) return;

  // Stagger: logo from left, description from right
  if (logo) gsap.set(logo, { x: -30 });
  if (desc) gsap.set(desc, { x: 30 });

  const tl = gsap.timeline({ delay: 0.25 });

  if (logo) {
    tl.to(logo, { opacity: 1, x: 0, duration: 1.1, ease: 'power3.out' }, 0.25);
  }
  if (desc) {
    tl.to(desc, { opacity: 1, x: 0, duration: 1.0, ease: 'power3.out' }, 0.42);
  }
  if (scroll) {
    tl.to(scroll, { opacity: 1, duration: 0.5, ease: 'power2.out' }, '-=0.25');
  }
}
