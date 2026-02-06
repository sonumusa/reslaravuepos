import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import router from './router';
import App from './App.vue';
import { initDatabase } from '@/services/offline-db';
import { initializeSyncService } from '@/services/initSync';

// Initialize offline database
initDatabase().then(success => {
    if (success) {
        console.log('Offline database ready');
    } else {
        console.warn('Offline database failed to initialize');
    }
});

const app = createApp(App);
const pinia = createPinia();

pinia.use(piniaPluginPersistedstate);

app.use(pinia);
app.use(router);

// Initialize sync after stores are available
router.isReady().then(() => {
    initializeSyncService();
});

app.mount('#app');
