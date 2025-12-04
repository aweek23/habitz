import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  root: path.resolve(__dirname),
  build: {
    outDir: path.resolve(__dirname, '../public'),
    assetsDir: 'assets',
    emptyOutDir: false,
    rollupOptions: {
      input: path.resolve(__dirname, 'index.html'),
      output: {
        entryFileNames: 'assets/main.js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: ({ name }) =>
          name && name.endsWith('.css') ? 'assets/main.css' : 'assets/[name][extname]'
      }
    }
  },
  server: {
    port: 5173,
    open: true
  }
});
