import React from 'react';
import ReactDOM from 'react-dom/client';

// ✅ FullCalendar CDN-based includes for use in non-React DOM (e.g., Blade)
import 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css';
import 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js';

// ✅ Test React root (you can remove or replace later)
const root = document.getElementById('react-root');

if (root) {
  ReactDOM.createRoot(root).render(
    <h1 style={{ color: 'green' }}>✅ React via Laravel + Vite is working!</h1>
  );
}
