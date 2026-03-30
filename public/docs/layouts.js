/* ── Sidebar drawer (mobile) ── */
function toggleSidebar() {
    const sb  = document.getElementById('docsSidebar');
    const ov  = document.getElementById('sidebarOverlay');
    if (!sb) return;
    const open = sb.classList.toggle('is-open');
    if (ov) {
        if (open) {
            ov.style.display = 'block';
            setTimeout(() => ov.classList.add('is-visible'), 10);
            document.body.style.overflow = 'hidden';
        } else {
            closeSidebar();
        }
    }
}

function closeSidebar() {
    const sb = document.getElementById('docsSidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (sb) sb.classList.remove('is-open');
    if (ov) {
        ov.classList.remove('is-visible');
        setTimeout(() => { ov.style.display = 'none'; }, 260);
    }
    document.body.style.overflow = '';
}

/* Close sidebar when a link inside it is clicked (mobile) */
document.addEventListener('DOMContentLoaded', () => {
    const sb = document.getElementById('docsSidebar');
    if (sb) {
        sb.querySelectorAll('a').forEach(a => {
            a.addEventListener('click', () => {
                if (window.innerWidth <= 768) closeSidebar();
            });
        });
    }
});
