import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import adminShell from './admin-shell';
import adminState from './admin-state';
import adminDateForm from './admin-date-form';
import bulkSelectionState from './bulk-selection-state';
import invoiceForm from './invoice-form';
import settingsWorkingDays from './settings-working-days';
import sessionCreate from './session-create';

Alpine.plugin(focus);
window.__alpineFocusReady = true;
Alpine.data('adminShell', adminShell);
Alpine.data('adminState', adminState);
Alpine.data('adminDateForm', adminDateForm);
Alpine.data('bulkSelectionState', bulkSelectionState);
Alpine.data('invoiceForm', invoiceForm);
Alpine.data('settingsWorkingDays', settingsWorkingDays);
Alpine.data('sessionCreate', sessionCreate);

window.Alpine = Alpine;

Alpine.start();
