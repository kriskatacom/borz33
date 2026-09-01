import { createApp } from 'vue';
import AuthApp from './components/AuthApp.vue';
export function mountStoreAuth() { const root = document.querySelector('#store-auth-app'); if (!root) return; let config = {}; try { config = JSON.parse(root.dataset.config || '{}'); } catch { config = {}; } createApp(AuthApp, { config }).mount(root); }
