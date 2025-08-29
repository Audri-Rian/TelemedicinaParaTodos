/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
      "./resources/**/*.blade.php", // Arquivos Blade (Laravel)
      "./resources/**/*.js",        // Scripts JS
      "./resources/**/*.vue",       // Se usar Vue no Laravel
    ],
    theme: {
      extend: {
        colors: {
          primary: "#FAF505",  // Amarelo Tailwind
        },
        fontFamily: {
          sans: ["Spline Sans", "ui-sans-serif", "system-ui", "sans-serif"],
          display: ["Knewave", "cursive"],
          lato: ["Lato", "sans-serif"],
          montserrat: ["Montserrat", "sans-serif"],
          outfit: ["Outfit", "sans-serif"],
          raleway: ["Raleway", "sans-serif"],
          spline: ["Spline Sans", "sans-serif"],
          "spline-bold": ["Spline Sans", "sans-serif"],
        },
      },
    },
    plugins: [],
  }
  