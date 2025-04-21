// vite.config.js
import { defineConfig } from "file:///C:/Projects/Skote_React_Laravel_v1.0.0/Admin/node_modules/vite/dist/node/index.js";
import laravel from "file:///C:/Projects/Skote_React_Laravel_v1.0.0/Admin/node_modules/laravel-vite-plugin/dist/index.mjs";
import { viteStaticCopy } from "file:///C:/Projects/Skote_React_Laravel_v1.0.0/Admin/node_modules/vite-plugin-static-copy/dist/index.js";
import react from "file:///C:/Projects/Skote_React_Laravel_v1.0.0/Admin/node_modules/@vitejs/plugin-react/dist/index.mjs";
import reactRefresh from "file:///C:/Projects/Skote_React_Laravel_v1.0.0/Admin/node_modules/@vitejs/plugin-react-refresh/index.js";
var vite_config_default = defineConfig({
  build: {
    manifest: true,
    rtl: true,
    outDir: "public/build/",
    cssCodeSplit: true,
    rollupOptions: {
      output: {
        assetFileNames: (css) => {
          if (css.name.split(".").pop() == "css") {
            return `css/[name].min.css`;
          } else if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(css.name.split(".").pop())) {
            return "images/" + css.name;
          } else {
            return "others/" + css.name;
          }
        },
        entryFileNames: `js/[name].js`
      }
    }
  },
  plugins: [
    laravel({
      input: [
        "resources/scss/theme.scss",
        "resources/js/app.js"
      ],
      refresh: true
    }),
    viteStaticCopy({
      targets: [
        {
          src: "resources/fonts",
          dest: ""
        },
        {
          src: "resources/images",
          dest: ""
        }
      ]
    }),
    react(),
    reactRefresh()
  ]
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFxQcm9qZWN0c1xcXFxTa290ZV9SZWFjdF9MYXJhdmVsX3YxLjAuMFxcXFxBZG1pblwiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9maWxlbmFtZSA9IFwiQzpcXFxcUHJvamVjdHNcXFxcU2tvdGVfUmVhY3RfTGFyYXZlbF92MS4wLjBcXFxcQWRtaW5cXFxcdml0ZS5jb25maWcuanNcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfaW1wb3J0X21ldGFfdXJsID0gXCJmaWxlOi8vL0M6L1Byb2plY3RzL1Nrb3RlX1JlYWN0X0xhcmF2ZWxfdjEuMC4wL0FkbWluL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XG5pbXBvcnQgbGFyYXZlbCBmcm9tICdsYXJhdmVsLXZpdGUtcGx1Z2luJztcbmltcG9ydCB7IHZpdGVTdGF0aWNDb3B5IH0gZnJvbSAndml0ZS1wbHVnaW4tc3RhdGljLWNvcHknO1xuaW1wb3J0IHJlYWN0IGZyb20gJ0B2aXRlanMvcGx1Z2luLXJlYWN0JztcbmltcG9ydCByZWFjdFJlZnJlc2ggZnJvbSAnQHZpdGVqcy9wbHVnaW4tcmVhY3QtcmVmcmVzaCc7XG5cbmV4cG9ydCBkZWZhdWx0IGRlZmluZUNvbmZpZyh7XG4gICAgYnVpbGQ6IHtcbiAgICAgICAgbWFuaWZlc3Q6IHRydWUsXG4gICAgICAgIHJ0bDogdHJ1ZSxcbiAgICAgICAgb3V0RGlyOiAncHVibGljL2J1aWxkLycsXG4gICAgICAgIGNzc0NvZGVTcGxpdDogdHJ1ZSxcbiAgICAgICAgcm9sbHVwT3B0aW9uczoge1xuICAgICAgICAgICAgb3V0cHV0OiB7XG4gICAgICAgICAgICAgICAgYXNzZXRGaWxlTmFtZXM6IChjc3MpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGNzcy5uYW1lLnNwbGl0KCcuJykucG9wKCkgPT0gJ2NzcycpIHtcbiAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gJ2Nzcy8nICsgYFtuYW1lXWAgKyAnLm1pbi4nICsgJ2Nzcyc7XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSBpZiAoL3BuZ3xqcGU/Z3xzdmd8Z2lmfHRpZmZ8Ym1wfGljby9pLnRlc3QoY3NzLm5hbWUuc3BsaXQoJy4nKS5wb3AoKSkpIHtcbiAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gJ2ltYWdlcy8nICsgY3NzLm5hbWU7XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgcmV0dXJuICdvdGhlcnMvJyArIGNzcy5uYW1lO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgZW50cnlGaWxlTmFtZXM6ICdqcy8nICsgYFtuYW1lXWAgKyBgLmpzYCxcbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0sXG4gICAgfSxcbiAgICBwbHVnaW5zOiBbXG4gICAgICAgIGxhcmF2ZWwoe1xuICAgICAgICAgICAgaW5wdXQ6IFtcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL3Njc3MvdGhlbWUuc2NzcycsXG4gICAgICAgICAgICAgICAgJ3Jlc291cmNlcy9qcy9hcHAuanMnLFxuICAgICAgICAgICAgXSxcbiAgICAgICAgICAgIHJlZnJlc2g6IHRydWUsXG4gICAgICAgIH0pLFxuICAgICAgICB2aXRlU3RhdGljQ29weSh7XG4gICAgICAgICAgICB0YXJnZXRzOiBbXG4gICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICBzcmM6ICdyZXNvdXJjZXMvZm9udHMnLFxuICAgICAgICAgICAgICAgICAgICBkZXN0OiAnJ1xuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICBzcmM6ICdyZXNvdXJjZXMvaW1hZ2VzJyxcbiAgICAgICAgICAgICAgICAgICAgZGVzdDogJydcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgXSxcbiAgICAgICAgfSksXG4gICAgICAgIHJlYWN0KCksXG4gICAgICAgIHJlYWN0UmVmcmVzaCgpXG4gICAgXSxcbn0pO1xuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUE4VCxTQUFTLG9CQUFvQjtBQUMzVixPQUFPLGFBQWE7QUFDcEIsU0FBUyxzQkFBc0I7QUFDL0IsT0FBTyxXQUFXO0FBQ2xCLE9BQU8sa0JBQWtCO0FBRXpCLElBQU8sc0JBQVEsYUFBYTtBQUFBLEVBQ3hCLE9BQU87QUFBQSxJQUNILFVBQVU7QUFBQSxJQUNWLEtBQUs7QUFBQSxJQUNMLFFBQVE7QUFBQSxJQUNSLGNBQWM7QUFBQSxJQUNkLGVBQWU7QUFBQSxNQUNYLFFBQVE7QUFBQSxRQUNKLGdCQUFnQixDQUFDLFFBQVE7QUFDckIsY0FBSSxJQUFJLEtBQUssTUFBTSxHQUFHLEVBQUUsSUFBSSxLQUFLLE9BQU87QUFDdEMsbUJBQU87QUFBQSxVQUNULFdBQVcsa0NBQWtDLEtBQUssSUFBSSxLQUFLLE1BQU0sR0FBRyxFQUFFLElBQUksQ0FBQyxHQUFHO0FBQzVFLG1CQUFPLFlBQVksSUFBSTtBQUFBLFVBQ3pCLE9BQU87QUFDTCxtQkFBTyxZQUFZLElBQUk7QUFBQSxVQUN6QjtBQUFBLFFBQ0Y7QUFBQSxRQUNBLGdCQUFnQjtBQUFBLE1BQ3RCO0FBQUEsSUFDSjtBQUFBLEVBQ0o7QUFBQSxFQUNBLFNBQVM7QUFBQSxJQUNMLFFBQVE7QUFBQSxNQUNKLE9BQU87QUFBQSxRQUNIO0FBQUEsUUFDQTtBQUFBLE1BQ0o7QUFBQSxNQUNBLFNBQVM7QUFBQSxJQUNiLENBQUM7QUFBQSxJQUNELGVBQWU7QUFBQSxNQUNYLFNBQVM7QUFBQSxRQUNMO0FBQUEsVUFDSSxLQUFLO0FBQUEsVUFDTCxNQUFNO0FBQUEsUUFDVjtBQUFBLFFBQ0E7QUFBQSxVQUNJLEtBQUs7QUFBQSxVQUNMLE1BQU07QUFBQSxRQUNWO0FBQUEsTUFDSjtBQUFBLElBQ0osQ0FBQztBQUFBLElBQ0QsTUFBTTtBQUFBLElBQ04sYUFBYTtBQUFBLEVBQ2pCO0FBQ0osQ0FBQzsiLAogICJuYW1lcyI6IFtdCn0K
