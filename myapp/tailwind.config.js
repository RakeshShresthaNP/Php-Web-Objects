/** @type {import('tailwindcss').Config} */
module.exports = {
  // Point this to every file that contains Tailwind classes
  content: [
    "./app/views/**/*.php",      // All PHP views
    "./app/views/layouts/*.php", // Layout files
    "./public/js/**/*.js",       // Admin & User JS files
    "./assets/manage/*.js"           // Dashboard assets
  ],
  theme: {
    extend: {
      colors: {
        // We can define your specific dark-mode colors as variables
        'admin-bg': '#0b0e14',
        'admin-side': '#151a21',
        'admin-card': '#1c222d',
        'admin-accent': '#4d7cfe',
      },
    },
  },
  plugins: [],
}manage