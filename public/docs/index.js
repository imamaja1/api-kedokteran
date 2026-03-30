/* Active sidebar highlight on scroll */
const sbLinks = document.querySelectorAll('.docs-sidebar a');
const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            sbLinks.forEach(l => l.classList.remove('active'));
            const a = document.querySelector(`.docs-sidebar a[href="#${e.target.id}"]`);
            if (a) a.classList.add('active');
        }
    });
}, { rootMargin: '-15% 0px -72% 0px' });
document.querySelectorAll('[id^="ep-"],[id^="section-"]').forEach(el => io.observe(el));
