import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { fileURLToPath, URL } from "node:url";

export default defineConfig({
  plugins: [
    react({
      fastRefresh: false,
    }),
  ],
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", import.meta.url)),
    },
  },
  build: {
    outDir: "dist",
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: `assets/index.js`,
        chunkFileNames: `assets/chunk-[name].js`,
        assetFileNames: ({ name }) => {
          if (name && (name.endsWith('.css') || name.endsWith('.scss'))) {
            return `assets/index.css`
          }
          return `assets/[name][extname]`
        },
      },
    },
  },
  server: {
    port: 5174,
    strictPort: true,
    cors: true,
    hmr: {
      host: "localhost",
    },
  },
})
