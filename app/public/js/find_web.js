document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('trouver-contact');
    if (!btn) return;

    btn.addEventListener('click', function () {
        console.log('Trouver contact clicked');
        btn.disabled = true;
        var previous = btn.textContent;
        btn.textContent = 'Recherche...';

        // Simulate an async action — replace with real logic as needed
        setTimeout(function () {
            btn.disabled = false;
            btn.textContent = previous;
        }, 1200);
    });
});
