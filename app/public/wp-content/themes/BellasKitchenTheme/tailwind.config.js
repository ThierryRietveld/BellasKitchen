/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './*.php',
    './template-parts/**/*.php',
    './js/**/*.js'
  ],
  theme: {
    container: {
      center: true,
      padding: {
        DEFAULT: '1.25rem',
        md: '2rem'
      },
      screens: {
        '2xl': '1200px'
      }
    },
    extend: {
      colors: {
        ember: { 50: '#fff8f1', 100: '#ffedd5', 200: '#fed7aa', 300: '#fdba74', 500: '#f97316', 700: '#c2410c', 900: '#7c2d12' },
        fig: { 100: '#f3e8ff', 300: '#d8b4fe', 700: '#7e22ce', 900: '#581c87' },
        sage: { 100: '#e7f5ec', 300: '#9dd6af', 700: '#2f6a48' },
        peach: { 100: '#ffe5d4', 200: '#ffd1ba', 300: '#ffbfa3' },
        butter: { 100: '#fff4bf', 200: '#ffe78a', 300: '#ffd95c' },
        mint: { 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac' },
        berry: { 100: '#fce7f3', 200: '#fbcfe8', 300: '#f9a8d4' },
        skycandy: { 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc' },
        night: {
          page: '#17080d',
          surface: '#241016',
          surfaceElevated: '#31161d',
          surfaceHover: '#421d27',
          border: '#5a2934',
          borderMuted: '#3a1a22',
          borderStrong: '#7a3846',
          text: '#fff1f4',
          textSoft: '#f8dbe2',
          muted: '#e6b7c2',
          subtle: '#c88a99',
          placeholder: '#9f6472',
          ring: '#421d27'
        }
      },
      fontFamily: {
        display: ['Fraunces', 'serif'],
        sans: ['Manrope', 'sans-serif']
      },
      boxShadow: {
        glow: '0 20px 60px rgba(236, 72, 153, 0.14)',
        card: '0 18px 40px rgba(249, 168, 212, 0.18)'
      }
    }
  },
  plugins: []
};
