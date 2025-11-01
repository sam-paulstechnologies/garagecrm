import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

/**
 * Keep your existing Inertia app exactly as-is
 */
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.jsx`,
      import.meta.glob('./Pages/**/*.jsx'),
    ),
  setup({ el, App, props }) {
    const root = createRoot(el);
    root.render(<App {...props} />);
  },
  progress: {
    color: '#4B5563',
  },
});

/**
 * ---- WhatsApp Template Editor (Blade-mounted) ------------------------------
 * We ALSO mount a standalone React editor if a div with id="wa-template-editor"
 * exists on the page (e.g., resources/views/admin/whatsapp/templates/react.blade.php).
 * This does NOT interfere with Inertia and can coexist happily.
 */
import TemplateEditor from './components/WhatsAppTemplateEditor.jsx';

function mountWaTemplateEditor() {
  const el = document.getElementById('wa-template-editor');
  if (!el) return;

  // Get initial data from data-initial attribute (JSON)
  let initial = {};
  try {
    initial = JSON.parse(el.dataset.initial || '{}');
  } catch {
    initial = {};
  }

  const root = createRoot(el);
  root.render(<TemplateEditor initial={initial} />);
}

// Mount on DOM ready (handles Blade pages)
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountWaTemplateEditor);
} else {
  mountWaTemplateEditor();
}
