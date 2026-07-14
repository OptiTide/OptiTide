// Bootstrap JS bundle for the public storefront (navbar, dropdown, carousel,
// accordion). Isolated from the Tailwind app.js used elsewhere.
import 'bootstrap';

// Navbar gains a shadow once you scroll off the top.
const nav = document.querySelector('.navbar.sticky-top');
if (nav) {
    const onScroll = () => nav.classList.toggle('shadow-sm', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

// Count-up numerals when the stats band scrolls into view (reduced-motion safe).
if (!matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
            if (!e.isIntersecting) return;
            const el = e.target;
            const to = parseFloat(el.dataset.to);
            const dec = el.dataset.dec ? 1 : 0;
            const suf = el.dataset.suf || '';
            const pre = el.dataset.pre || '';
            const t0 = performance.now();
            const dur = 1400;
            const tick = (t) => {
                const p = Math.min((t - t0) / dur, 1);
                el.textContent = pre + (to * (1 - Math.pow(1 - p, 3))).toFixed(dec) + suf;
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
            io.unobserve(el);
        });
    }, { threshold: 0.4 });
    document.querySelectorAll('.stat-num[data-to]').forEach((el) => io.observe(el));
}
