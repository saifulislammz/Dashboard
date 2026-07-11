const colors = require('tailwindcss/colors');

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./views/**/*.php",
    "./public/**/*.php",
    "./src/**/*.php"
  ],
  theme: {
    fontFamily: {
      sans: ['var(--font-family-primary)', 'ui-sans-serif', 'system-ui', 'sans-serif'],
    },
    fontSize: {
      xs: 'var(--font-size-xs)',
      sm: 'var(--font-size-sm)',
      base: 'var(--font-size-base)',
      lg: 'var(--font-size-lg)',
      xl: 'var(--font-size-xl)',
      '2xl': 'var(--font-size-2xl)',
      '3xl': 'var(--font-size-3xl)',
      '4xl': 'var(--font-size-4xl)',
      '5xl': 'var(--font-size-5xl)',
      '6xl': 'var(--font-size-6xl)',
    },
    fontWeight: {
      normal: 'var(--font-weight-normal)',
      medium: 'var(--font-weight-medium)',
      semibold: 'var(--font-weight-semibold)',
      bold: 'var(--font-weight-bold)',
    },
    colors: {
      transparent: 'transparent',
      current: 'currentColor',
      black: colors.black,
      white: colors.white,
      gray: colors.gray,
      green: colors.emerald,
      red: colors.red,
      yellow: colors.yellow,
      primary: 'var(--color-primary-green)', // Controlled from universal.css
      brandText: '#1F2937',
      secondaryText: '#6B7280',
      bgLight: '#F9FAFB',
      cardBg: '#FFFFFF',
      iconBgGreen: '#E6F4F1',
      iconBgYellow: '#FEF3C7',
      iconBgRed: '#FEE2E2',
    },
    extend: {},
  },
  plugins: [],
}
