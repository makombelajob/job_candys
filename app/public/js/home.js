document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.site-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            nav.classList.toggle('is-open');
        });
    }

    const form = document.querySelector('.contact-form');
    const message = document.querySelector('.form-message');

    if (form && message) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            message.textContent = 'Merci pour votre message. Nous reviendrons vers vous très bientôt.';
            form.reset();
        });
    }
});
