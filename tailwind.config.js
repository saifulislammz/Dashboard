/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./views/**/*.php",
    "./public/**/*.php",
    "./src/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        primary: '#0F766E', // Teal color matching the design
        brandText: '#1F2937',
        secondaryText: '#6B7280',
        bgLight: '#F9FAFB',
        cardBg: '#FFFFFF',
        iconBgGreen: '#E6F4F1',
        iconBgTeal: '#E0F2FE',
        iconBgOrange: '#FEF3C7',
        iconBgBlue: '#DBEAFE',
      }
    },
  },
  plugins: [],
}
