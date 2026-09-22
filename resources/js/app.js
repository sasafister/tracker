import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import App from './App.vue';
import { Monochrome } from './theme.js';

createApp(App)
    .use(PrimeVue, {
        theme: {
            preset: Monochrome,
            options: {
                // Tailwind owns the page styling; PrimeVue's own CSS goes into
                // a layer so utility classes keep winning.
                cssLayer: {
                    name: 'primevue',
                    order: 'theme, base, primevue',
                },
                darkModeSelector: false,
            },
        },
    })
    .use(ConfirmationService)
    .mount('#app');
