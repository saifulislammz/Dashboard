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
        primary: '#10b981', // green matching the design
        'primary-dark': '#059669',
      }
    },
  },
  plugins: [],
}
