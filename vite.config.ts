import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'src/web/assets/dist',
    rollupOptions: {
      input: {
        calendar: 'src/web/assets/src/calendar.js',
        'event-date-field': 'src/web/assets/src/event-date-field.js',
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name].[ext]',
      },
    },
  },
});
