/** @type {import('tailwindcss').Config} */

export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.jsx", 
    "./resources/**/*.js",
  ],
  // Dans Tailwind v4, la plupart de la configuration se fait dans le CSS avec @theme
  // Le fichier config peut être minimal

  plugins: [
    require('@tailwindcss/line-clamp'),
  ],
}