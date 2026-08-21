import { cardCheckout } from './card-tokenization';

document.addEventListener('alpine:init', () => {
    Alpine.data('cardCheckout', cardCheckout);
});
