import './bootstrap'

import { createInertiaApp } from '@inertiajs/react'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createRoot } from 'react-dom/client'

import Alpine from 'alpinejs'   // ✅ ADD THIS

// Start Alpine (for Blade popups)
window.Alpine = Alpine
Alpine.start()

const appName = import.meta.env.VITE_APP_NAME || 'Garage CRM'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx')
        ),

    setup({ el, App, props }) {
        const root = createRoot(el)
        root.render(<App {...props} />)

        const loader = document.getElementById('app-loader')
        if (loader) {
            loader.classList.add('hidden')
        }
    },

    progress: {
        color: '#2563eb',
        showSpinner: true,
    },
})

/**
 * --------------------------------------------------------------------------
 * Blade-mounted WhatsApp editor (SAFE – does not affect Inertia)
 * --------------------------------------------------------------------------
 */
import TemplateEditor from './components/WhatsAppTemplateEditor.jsx'

function mountWaTemplateEditor() {
    const el = document.getElementById('wa-template-editor')
    if (!el) return

    let initial = {}
    try {
        initial = JSON.parse(el.dataset.initial || '{}')
    } catch {}

    const root = createRoot(el)
    root.render(<TemplateEditor initial={initial} />)
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountWaTemplateEditor)
} else {
    mountWaTemplateEditor()
}

/**
 * --------------------------------------------------------------------------
 * Garage Calendar (Blade mounted – NO Inertia)
 * --------------------------------------------------------------------------
 */
import './calendar'
