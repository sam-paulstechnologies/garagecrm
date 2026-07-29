import './bootstrap'

import { createInertiaApp } from '@inertiajs/react'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createRoot } from 'react-dom/client'
import Alpine from 'alpinejs'

import TemplateEditor from './Components/WhatsAppTemplateEditor.jsx'
import './calendar'

// Start Alpine for Blade-mounted components and popups.
window.Alpine = Alpine
Alpine.start()

const appName = 'SayaraForce'
const inertiaRoot = document.getElementById('app')

// Blade-rendered application screens share this bundle but do not provide an
// Inertia page payload. Only initialise Inertia when its real root is present.
if (inertiaRoot?.dataset.page) {
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
            color: '#FF6A00',
            showSpinner: true,
        },
    })
}

/**
 * --------------------------------------------------------------------------
 * Blade-mounted WhatsApp editor
 * --------------------------------------------------------------------------
 * This mounts only when the Blade page contains #wa-template-editor.
 * It does not interfere with the Inertia application.
 */
function mountWaTemplateEditor() {
    const element = document.getElementById('wa-template-editor')

    if (!element) {
        return
    }

    let initial = {}

    try {
        initial = JSON.parse(element.dataset.initial || '{}')
    } catch (error) {
        console.warn(
            'Unable to parse WhatsApp template editor initial data.',
            error
        )
    }

    const root = createRoot(element)

    root.render(<TemplateEditor initial={initial} />)
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountWaTemplateEditor)
} else {
    mountWaTemplateEditor()
}
