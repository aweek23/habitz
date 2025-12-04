module.exports = {
  content: [
    './index.php',
    './frontend/index.html',
    './frontend/src/**/*.{js,jsx,ts,tsx,vue,php}',
    './node_modules/flowbite/**/*.js',
    './node_modules/preline/dist/*.js'
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        body: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif']
      }
    }
  },
  plugins: [
    require('daisyui'),
    require('flowbite/plugin'),
    require('preline/plugin'),
    require('./frontend/plugins/hyperui'),
    require('@tailwindcss/forms')
  ],
  daisyui: {
    themes: ['light', 'dark', 'cupcake']
  }
};
