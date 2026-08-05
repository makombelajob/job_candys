document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('trouver-contact');
    if (btn) {
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
    }

    // Adjust .full-screen min-height to account for sticky header/footer so content visually centers
    function adjustFullScreen() {
        var full = document.querySelector('.full-screen');
        if (!full) return;
        var header = document.querySelector('.site-header');
        var footer = document.querySelector('footer, .site-footer');
        var offset = 0;
        if (header) offset += header.offsetHeight;
        if (footer) offset += footer.offsetHeight;
        // Use available viewport height minus header/footer
        var available = window.innerHeight - offset;
        if (available > 0) {
            full.style.minHeight = available + 'px';
            full.style.display = 'flex';
            full.style.alignItems = 'center';
            full.style.justifyContent = 'center';
        } else {
            full.style.minHeight = '';
        }
    }

    adjustFullScreen();
    window.addEventListener('resize', adjustFullScreen);
});
