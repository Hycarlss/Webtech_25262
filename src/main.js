import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './assets/style.css'

// Globally intercept fetch requests to add Authorization header with the JWT token
const originalFetch = window.fetch;
window.fetch = async (url, options = {}) => {
  const token = localStorage.getItem('token');
  if (token) {
    options.headers = {
      ...options.headers,
      'Authorization': `Bearer ${token}`
    };
  }
  return originalFetch(url, options);
};

createApp(App)
    .use(router)        // register router
    .mount('#app')