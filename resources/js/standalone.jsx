import React from 'react';
import ReactDOM from 'react-dom/client';

const root = document.getElementById('react-root');

const Minimal = () => (
  <div style={{ padding: '1rem', color: 'blue' }}>
    <h1>✅ Pure React via Vite is working!</h1>
    <p>This does NOT use any Laravel plugin.</p>
  </div>
);

if (root) {
  ReactDOM.createRoot(root).render(<Minimal />);
} else {
  console.error('❌ root not found');
}
