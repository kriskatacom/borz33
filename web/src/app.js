import Alpine from 'alpinejs';
import { registerStoreHeader } from './nav.js';
import { registerThemeStore } from './theme.js';
import { registerTooltip } from './tooltip.js';
import './app.css';

window.Alpine = Alpine;
registerThemeStore(Alpine);
registerStoreHeader(Alpine);
registerTooltip(Alpine);
Alpine.start();
