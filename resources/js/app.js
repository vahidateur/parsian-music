import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import adminShell from './admin-shell';

Alpine.plugin(focus);
window.__alpineFocusReady = true;
Alpine.data('adminShell', adminShell);

window.Alpine = Alpine;

Alpine.start();
