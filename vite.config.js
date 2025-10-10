import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react' // npm i -D @vitejs/plugin-react

export default defineConfig({
  plugins: [
    react(),
    laravel({
      input: ['resources/js/app.jsx'],
      refresh: true,
    }),
  ],
})
