import Alpine from 'alpinejs';
import { registerOrderForm } from './order-form';

window.Alpine = Alpine;

registerOrderForm(Alpine);

Alpine.start();
