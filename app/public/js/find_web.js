const buttons = document.querySelectorAll('.actions button');

buttons.forEach(btn => {
    btn.addEventListener('click', function () {

        const previous = btn.textContent;

        btn.disabled = true;
        btn.textContent = btn.dataset.loading;

        if (btn.id === 'trouver-contact') {

            const siret = this.dataset.siret;

            if (!website) {
                btn.disabled = false;
                btn.textContent = previous;
                return;
            }

            window.location.href =
                '/find/contact' + siret;

            return;
        }

        setTimeout(() => {
            btn.disabled = false;
            btn.textContent = previous;
        }, 1200);
    });
});