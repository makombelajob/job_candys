const buttons = document.querySelectorAll('.actions button');

buttons.forEach(btn => {
    btn.addEventListener('click', function () {
        const previous = btn.textContent;

        btn.disabled = true;
        btn.textContent = btn.dataset.loading;

        setTimeout(() => {
            btn.disabled = false;
            btn.textContent = previous;
        }, 1200);
    });
});