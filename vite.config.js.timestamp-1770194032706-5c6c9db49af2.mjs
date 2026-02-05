// vite.config.js
import { defineConfig } from "file:///E:/my-bee-company/node_modules/vite/dist/node/index.js";
import laravel from "file:///E:/my-bee-company/node_modules/laravel-vite-plugin/dist/index.js";
import react from "file:///E:/my-bee-company/node_modules/@vitejs/plugin-react/dist/index.mjs";
import path from "path";
var __vite_injected_original_dirname = "E:\\my-bee-company";
var vite_config_default = defineConfig({
  server: {
    host: "127.0.0.1"
    // Add this to force IPv4 only
  },
  plugins: [
    react(),
    laravel({
      input: ["resources/components/App.jsx"],
      buildDirectory: "tenancy/assets/build",
      refresh: true
    })
  ],
  build: {
    outDir: path.resolve(__vite_injected_original_dirname, "public/tenancy/assets/build"),
    // Output to a folder named after the tenant
    target: "esnext"
    //browsers can handle the latest ES features
  },
  resolve: {
    alias: {
      "@": path.resolve(__vite_injected_original_dirname, "public")
      // Alias '@' to the 'resources' directory
    }
  },
  ssr: {
    external: ["@webassemblyjs/helper-api-error"]
  },
  assetsInclude: ["**/*.node"],
  optimizeDeps: {
    include: ["@swc/wasm"]
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJFOlxcXFxteS1iZWUtY29tcGFueVwiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9maWxlbmFtZSA9IFwiRTpcXFxcbXktYmVlLWNvbXBhbnlcXFxcdml0ZS5jb25maWcuanNcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfaW1wb3J0X21ldGFfdXJsID0gXCJmaWxlOi8vL0U6L215LWJlZS1jb21wYW55L3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XG5pbXBvcnQgbGFyYXZlbCBmcm9tICdsYXJhdmVsLXZpdGUtcGx1Z2luJztcbmltcG9ydCByZWFjdCBmcm9tICdAdml0ZWpzL3BsdWdpbi1yZWFjdCc7XG5pbXBvcnQgcGF0aCBmcm9tICdwYXRoJztcblxuZXhwb3J0IGRlZmF1bHQgZGVmaW5lQ29uZmlnKHtcbiAgICBzZXJ2ZXI6IHtcbiAgICAgICAgaG9zdDogJzEyNy4wLjAuMScsICAvLyBBZGQgdGhpcyB0byBmb3JjZSBJUHY0IG9ubHlcbiAgICB9LFxuICAgIHBsdWdpbnM6IFtcbiAgICAgICAgcmVhY3QoKSxcbiAgICAgICAgbGFyYXZlbCh7XG4gICAgICAgICAgICBpbnB1dDogW1wicmVzb3VyY2VzL2NvbXBvbmVudHMvQXBwLmpzeFwiXSAsXG4gICAgICAgICAgICBidWlsZERpcmVjdG9yeTondGVuYW5jeS9hc3NldHMvYnVpbGQnLFxuICAgICAgICAgICAgcmVmcmVzaDogdHJ1ZSxcbiAgICAgICAgfSksXG4gICAgXSxcbiAgICBidWlsZDoge1xuICAgICAgICBvdXREaXI6IHBhdGgucmVzb2x2ZShfX2Rpcm5hbWUsICdwdWJsaWMvdGVuYW5jeS9hc3NldHMvYnVpbGQnKSwgLy8gT3V0cHV0IHRvIGEgZm9sZGVyIG5hbWVkIGFmdGVyIHRoZSB0ZW5hbnRcbiAgICAgICAgdGFyZ2V0OiAnZXNuZXh0JyAvL2Jyb3dzZXJzIGNhbiBoYW5kbGUgdGhlIGxhdGVzdCBFUyBmZWF0dXJlc1xuICAgICAgfSxcbiAgICByZXNvbHZlOiB7XG4gICAgICAgIGFsaWFzOiB7XG4gICAgICAgICAgICAnQCc6IHBhdGgucmVzb2x2ZShfX2Rpcm5hbWUsICdwdWJsaWMnKSwgLy8gQWxpYXMgJ0AnIHRvIHRoZSAncmVzb3VyY2VzJyBkaXJlY3RvcnlcbiAgICAgICAgfSxcbiAgICB9LFxuICAgIHNzcjoge1xuICAgICAgICBleHRlcm5hbDogW1wiQHdlYmFzc2VtYmx5anMvaGVscGVyLWFwaS1lcnJvclwiXVxuICAgICAgfSxcbiAgICBhc3NldHNJbmNsdWRlOiBbJyoqLyoubm9kZSddLFxuICAgIG9wdGltaXplRGVwczoge1xuICAgICAgICBpbmNsdWRlOiBbJ0Bzd2Mvd2FzbSddXG4gICAgICB9XG59KTsiXSwKICAibWFwcGluZ3MiOiAiO0FBQXlPLFNBQVMsb0JBQW9CO0FBQ3RRLE9BQU8sYUFBYTtBQUNwQixPQUFPLFdBQVc7QUFDbEIsT0FBTyxVQUFVO0FBSGpCLElBQU0sbUNBQW1DO0FBS3pDLElBQU8sc0JBQVEsYUFBYTtBQUFBLEVBQ3hCLFFBQVE7QUFBQSxJQUNKLE1BQU07QUFBQTtBQUFBLEVBQ1Y7QUFBQSxFQUNBLFNBQVM7QUFBQSxJQUNMLE1BQU07QUFBQSxJQUNOLFFBQVE7QUFBQSxNQUNKLE9BQU8sQ0FBQyw4QkFBOEI7QUFBQSxNQUN0QyxnQkFBZTtBQUFBLE1BQ2YsU0FBUztBQUFBLElBQ2IsQ0FBQztBQUFBLEVBQ0w7QUFBQSxFQUNBLE9BQU87QUFBQSxJQUNILFFBQVEsS0FBSyxRQUFRLGtDQUFXLDZCQUE2QjtBQUFBO0FBQUEsSUFDN0QsUUFBUTtBQUFBO0FBQUEsRUFDVjtBQUFBLEVBQ0YsU0FBUztBQUFBLElBQ0wsT0FBTztBQUFBLE1BQ0gsS0FBSyxLQUFLLFFBQVEsa0NBQVcsUUFBUTtBQUFBO0FBQUEsSUFDekM7QUFBQSxFQUNKO0FBQUEsRUFDQSxLQUFLO0FBQUEsSUFDRCxVQUFVLENBQUMsaUNBQWlDO0FBQUEsRUFDOUM7QUFBQSxFQUNGLGVBQWUsQ0FBQyxXQUFXO0FBQUEsRUFDM0IsY0FBYztBQUFBLElBQ1YsU0FBUyxDQUFDLFdBQVc7QUFBQSxFQUN2QjtBQUNOLENBQUM7IiwKICAibmFtZXMiOiBbXQp9Cg==
