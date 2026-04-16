/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.html',
    './assets/js/**/*.js',
  ],
  theme: {
    // Override defaults — only the design system palette
    colors: {
      black:       '#000000',
      white:       '#ffffff',
      transparent: 'transparent',
      current:     'currentColor',
      orange: {
        DEFAULT: '#FF5500',
        light:   '#FF7733',
        dark:    '#CC4400',
        muted:   'rgba(255, 85, 0, 0.15)',
      },
      white: {
        DEFAULT: '#ffffff',
        10:  'rgba(255,255,255,0.10)',
        20:  'rgba(255,255,255,0.20)',
        40:  'rgba(255,255,255,0.40)',
        60:  'rgba(255,255,255,0.60)',
      },
      black: {
        DEFAULT: '#000000',
        80: 'rgba(0,0,0,0.80)',
        60: 'rgba(0,0,0,0.60)',
      },
    },
    fontFamily: {
      display: ['SF Pro Display', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
    },
    extend: {
      letterSpacing: {
        nav:     '0.15em',
        heading: '0.08em',
        ultra:   '0.25em',
      },
      transitionTimingFunction: {
        'expo-out': 'cubic-bezier(0.16, 1, 0.3, 1)',
        'expo-in':  'cubic-bezier(0.7, 0, 0.84, 0)',
      },
      borderRadius: {
        DEFAULT: '0px',
        sm: '2px',
        md: '4px',
      },
    },
  },
  plugins: [],
};
