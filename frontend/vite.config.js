import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  root: path.resolve(__dirname),
  build: {
    outDir: path.resolve(__dirname, '../public'),
    emptyOutDir: true
  },
  server: {
    port: 5173,
    open: true
  }
});
