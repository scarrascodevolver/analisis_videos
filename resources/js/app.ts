import './bootstrap';
import { createApp, h, type Directive } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// v-click-outside directive
const clickOutside: Directive = {
    mounted(el, binding) {
        el._clickOutsideHandler = (e: MouseEvent) => {
            if (!el.contains(e.target as Node)) {
                binding.value(e);
            }
        };
        document.addEventListener('click', el._clickOutsideHandler);
    },
    unmounted(el) {
        document.removeEventListener('click', el._clickOutsideHandler);
        delete el._clickOutsideHandler;
    },
};

createInertiaApp({
    title: (title) => title ? `${title} - RugbyKP` : 'RugbyKP',
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .directive('click-outside', clickOutside)
            .mount(el);
    },
    progress: {
        color: '#00B7B5',
    },
});
