import Alpine from 'alpinejs';
import { registerStoreAvatar } from './avatar.js';
import { registerStoreForm } from './form.js';
import { registerStoreHeader } from './nav.js';
import { registerStoreProduct } from './product.js';
import { registerThemeStore } from './theme.js';
import { registerTooltip } from './tooltip.js';
import './app.css';

window.Alpine = Alpine;
registerThemeStore(Alpine);
registerStoreHeader(Alpine);
registerStoreAvatar(Alpine);
registerStoreForm(Alpine);
registerStoreProduct(Alpine);
registerTooltip(Alpine);
Alpine.start();
