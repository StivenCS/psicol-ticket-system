import { boot } from 'quasar/wrappers';
import axios, { type AxiosInstance } from 'axios';
import { LoadingBar } from 'quasar';

declare module 'vue' {
  interface ComponentCustomProperties {
    $axios: AxiosInstance;
    $api: AxiosInstance;
  }
}

const api = axios.create({
  baseURL: process.env.API_URL ?? 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// Track concurrent requests so the bar stays until all finish
let activeRequests = 0;

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;

  // Skip loading bar for blob downloads (export) — already have button spinner
  if (config.responseType !== 'blob') {
    if (activeRequests === 0) LoadingBar.start();
    activeRequests++;
  }

  return config;
});

api.interceptors.response.use(
  (response) => {
    if (response.config.responseType !== 'blob') {
      activeRequests = Math.max(0, activeRequests - 1);
      if (activeRequests === 0) LoadingBar.stop();
    }
    return response;
  },
  (error: unknown) => {
    activeRequests = Math.max(0, activeRequests - 1);
    if (activeRequests === 0) LoadingBar.stop();

    if (axios.isAxiosError(error) && error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/#/login';
    }
    throw error;
  },
);

export default boot(({ app }) => {
  app.config.globalProperties.$axios = axios;
  app.config.globalProperties.$api = api;
});

export { api };
