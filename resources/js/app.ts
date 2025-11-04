import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { configureEcho } from '@laravel/echo-vue';
import { router } from '@inertiajs/vue3';

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Handler global de erros do Inertia para requisições que falharam
router.on('error', (event) => {
    // Se o erro retornar uma resposta de erro HTTP (4xx, 5xx)
    // o Laravel já vai renderizar a página de erro customizada
    // Este handler é apenas para erros de rede/conexão
    if (event.detail.errors) {
        console.error('Erro na requisição:', event.detail.errors);
    }
});
