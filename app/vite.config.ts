import path from 'node:path'
import babel from '@rolldown/plugin-babel'
import tailwindcss from '@tailwindcss/vite'
import react, { reactCompilerPreset } from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), babel({ presets: [reactCompilerPreset()] }), tailwindcss()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    proxy: {
      '/api': {
        target: 'https://127.0.0.1:8000',
        // symfony serve uses a self-signed mkcert certificate — don't reject it.
        secure: false,
        // Rewrite the Host header to match the target so TLS SNI / Symfony
        // routing both see 127.0.0.1:8000 instead of the front origin.
        changeOrigin: true,
      },
    },
  },
})
