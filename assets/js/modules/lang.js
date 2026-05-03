/**
 * lang.js — Language switcher
 * Supported: fr, de, en. Persists to localStorage.
 * Note: DE requires data-de attributes on scramble elements for full content translation.
 */

const SUPPORTED = ['fr', 'de', 'en'];
const STORAGE_KEY = 'maison1815-lang';

export function initLang() {
  const saved = localStorage.getItem(STORAGE_KEY);
  const initial = SUPPORTED.includes(saved) ? saved : document.documentElement.lang || 'fr';
  applyLang(initial, false);

  document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      applyLang(btn.dataset.lang, true);
    });
  });
}

function applyLang(lang, persist) {
  if (!SUPPORTED.includes(lang)) return;

  document.documentElement.lang = lang;
  if (persist) localStorage.setItem(STORAGE_KEY, lang);

  // Update active state on all lang buttons (desktop + mobile)
  document.querySelectorAll('.lang-btn').forEach(btn => {
    const isActive = btn.dataset.lang === lang;
    btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });

  // Update scramble elements that have a matching data-{lang} attribute
  document.querySelectorAll('[data-scramble]').forEach(el => {
    const text = el.dataset[lang];
    if (!text) return;
    // Update only the text node (first child), preserve child elements like .nav-link__line
    const textNode = [...el.childNodes].find(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim());
    if (textNode) textNode.textContent = '\n            ' + text + '\n            ';
    else el.prepend(document.createTextNode(text));
  });
}
