# CLAUDE.md — Production Agency Website

## Project Overview
Creative, minimalist, immersive website for a photo/video production company.
Premium UX. Visually impactful. Ultra-smooth interactions.

---

## Stack
- **CSS**: Tailwind CSS (compiled, no CDN)
- **JS**: Vanilla JS — modular (ES modules)
- **Animations**: GSAP (ScrollTrigger, all interactions)
- **Layout**: CSS columns for masonry (no extra lib unless justified)
- **No frameworks** (no React, Vue, etc.)

---

## File Structure
```
/
├── index.html
├── about.html
├── onset.html
├── talents.html
├── project-video.html
├── project-photo.html
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── js/modules/
│       ├── scramble.js       ← text scramble effect
│       ├── navbar.js
│       ├── slider.js         ← clients infinite scroll
│       ├── cards.js          ← project cards hover
│       ├── talents.js        ← talents hover positioning
│       └── accordion.js
└── assets/media/
```

---

## Design System

| Token        | Value                          |
|--------------|--------------------------------|
| Background   | `#000` / `#fff`                |
| Primary      | `#FF5500` (orange, variants ok)|
| Font         | `SF Pro Display` (see Assets → Fonts)        |
| Text style   | UPPERCASE for headings, tracked |
| Border radius| Minimal (0–4px max)            |

Define all tokens as CSS variables in `style.css`.

---

## Global Effects

### Text Scramble (Hacker Effect)
- **File**: `assets/js/modules/scramble.js`
- **Trigger**: hover
- **Behavior**: characters randomize → reconstruct in alternate language (FR↔EN)
- **Duration**: ~400ms, smooth, non-blocking
- **Apply to**: navbar links, main headings, key static text
- **Implementation**: GSAP ticker or requestAnimationFrame loop

### Scroll Reveal
- GSAP ScrollTrigger on all sections
- Fade + translateY(20px) → default
- Staggered on lists/grids

---

## Components (shared across all pages)

### Navbar
- Position: fixed top-left
- Links: uppercase, left-aligned
- Hover: color → orange + underline (GSAP scaleX from 0→1)
- Scramble effect on hover (FR↔EN)
- Mobile: hamburger menu

### Footer
- Layout: `flex justify-between items-center`
- Left: company name (uppercase)
- Right: 3 social icons (SVG, hover → orange)

---

## Pages

### `index.html` — Homepage

**Hero**
- Fullscreen video (100vh), autoplay, muted, loop
- Navbar overlaid (z-index)
- Logo top-left, tagline centered

**Projects Grid**
- Responsive: `grid-cols-1 md:grid-cols-2 lg:grid-cols-3`
- Cards:
  - Default: static image (`https://placehold.co/800x600`)
  - Hover: crossfade to `<video>` (autoplay, loop, muted) — GSAP fade + scale(1.03)
  - Below card: CLIENT / PROJECT / RÉGISSEUR (uppercase, small)

**Clients Ticker**
- Infinite horizontal scroll (CSS animation or GSAP)
- Hover → pause
- Hover logo → filter to orange (CSS or GSAP)
- Drag support: mouse + touch (pointer events)

---

### `about.html`

**Intro**
- Single paragraph or short text
- Scramble effect on hover

**Contact / Office**
- 2 info blocks, offset slightly to the right
- e.g. `ml-auto mr-16` or similar

**Team List**
- Single column, vertical list
- Each item: Name — Title — Email
- Hover: image appears fixed on right side of screen
  - Image: `https://placehold.co/400x500`
  - GSAP: `opacity 0→1`, `scale 0.95→1`, `duration: 0.3`

**End of page**
- Full-width image (`https://placehold.co/1600x600`)

---

### `onset.html`

- Masonry gallery using CSS `columns: 3` (desktop), `2` (tablet), `1` (mobile)
- Mixed image sizes for asymmetric layout:
  - Use `placehold.co` with varying dimensions: 600x800, 800x600, 400x600, 900x500, etc.
- No captions needed
- Images: subtle hover scale (GSAP, `scale 1→1.02`)

---

### `talents.html`

- Vertical list of names (full name per line)
- Each name: random orange shade (generate on page load via JS)
  - Range: `hsl(20, 100%, 45%)` → `hsl(35, 100%, 60%)`
- Font: large, uppercase

**Hover behavior**
- Each talent has a predefined asset (image OR video — set in data attributes)
- On hover: asset appears at random position in viewport
  - Constrain to viewport: `x: random(50, vw-300)`, `y: random(50, vh-300)`
  - GSAP: `opacity 0→1`, `scale 0.9→1`, duration 0.25s
  - On mouseout: reverse animation

---

### `project-video.html`

**Custom Video Player** (no native controls)
- HTML `<video>` with `controls` attribute removed
- Custom UI:
  - Play/Pause button (SVG icon, toggles)
  - Progress bar: `<input type="range">` styled custom, updates in real-time
  - Mute/Unmute button
  - All interactions via JS event listeners

**Team Accordion**
- Centered title: `TEAM`
- Click item → GSAP `height: 0 → auto` with `opacity` fade
- Click again → collapse

**Project Info**
- CLIENT / PROJECT / DESCRIPTION — clean typographic layout

---

### `project-photo.html`

**Hero**
- Single full-width image (`https://placehold.co/1600x900`)

**Gallery**
- CSS columns (3 desktop / 2 tablet / 1 mobile)
- Mixed proportions for masonry feel
- Images from `placehold.co`

**Project Info**
- Same layout as `project-video.html`

---

## Coding Standards

- **No inline styles** (use Tailwind classes or CSS variables only)
- **No code duplication** (navbar/footer injected via JS or shared includes)
- **ES modules**: each feature in its own file under `js/modules/`
- **main.js**: imports and initializes all modules
- **Comments**: short, relevant (explain "why", not "what")
- **Mobile-first**: all breakpoints use `min-width`

---

## Assets

### Fonts
- **SF Pro Display** — primary font (replaces DM Sans / Neue Haas Grotesk)
- Files located in `fonts/sf-pro-display/` (OTF: Regular, Medium, Bold + italic variants)
- Declare via `@font-face` in `style.css`, use CSS variable: `--font-display: 'SF Pro Display', sans-serif`

### Placeholders
- All images: `https://placehold.co/{width}x{height}`
- All videos: reference local files (`assets/media/video.mp4`) — use `<source>` tag

---

## What NOT to do
- No jQuery
- No Bootstrap
- No unnecessary libraries
- No heavy animations (nothing above 60fps budget)
- No layout shifts on load
- No `!important` abuse