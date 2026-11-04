import { defineConfig } from 'vite';

export default defineConfig({
  publicDir: 'vercel-public',
  build: {
    outDir: 'dist',
    emptyOutDir: true,
  },
});
