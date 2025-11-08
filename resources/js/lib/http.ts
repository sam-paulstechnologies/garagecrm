import axios from 'axios';

// If you're using Sanctum cookie auth in the same domain:
const http = axios.create({
  baseURL: '/api/v1',
  withCredentials: true,
  headers: { 'Accept': 'application/json' },
});

// If you're using Bearer tokens instead, add an interceptor:
// http.interceptors.request.use(cfg => {
//   const token = localStorage.getItem('api_token');
//   if (token) cfg.headers.Authorization = `Bearer ${token}`;
//   return cfg;
// });

export default http;
