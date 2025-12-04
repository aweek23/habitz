const plugin = require('tailwindcss/plugin');

module.exports = plugin(function ({ addComponents }) {
  addComponents({
    '.ui-card': {
      '@apply rounded-xl border border-base-200 bg-base-100 shadow-md p-6 space-y-4': {},
    },
    '.ui-btn-primary': {
      '@apply inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-content transition hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary': {},
    },
    '.ui-badge': {
      '@apply inline-flex items-center gap-2 rounded-full bg-base-200 px-3 py-1 text-xs font-medium uppercase tracking-wide text-base-content': {},
    }
  });
});
