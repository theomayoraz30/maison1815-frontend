/**
 * main.js — Entry point
 *
 * Imports and initialises all modules.
 * GSAP and ScrollTrigger are loaded as globals via <script> tags in the HTML.
 */

import { initScramble } from './modules/scramble.js';
import { initNavbar   } from './modules/navbar.js';
import { initSlider   } from './modules/slider.js';
import { initCards    } from './modules/cards.js';
import { initTalents  } from './modules/talents.js';
import { initAccordion } from './modules/accordion.js';

// Register GSAP plugins once
if (typeof ScrollTrigger !== 'undefined') {
  gsap.registerPlugin(ScrollTrigger);
}

document.addEventListener('DOMContentLoaded', () => {
  initNavbar();
  initScramble();
  initSlider();
  initCards();
  initTalents();
  initAccordion();
});
