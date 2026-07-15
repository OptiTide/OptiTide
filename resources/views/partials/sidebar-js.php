<script>
// Off-canvas sidebar drawer (mobile + high-zoom widths). Dismissible via the
// backdrop, the close button, a nav-link tap, or Escape.
function otToggleSidebar(force) {
    var sb = document.getElementById('sidebar');
    var bd = document.getElementById('sidebarBackdrop');
    var tg = document.getElementById('sidebarToggle');
    if (!sb) return;
    var open = (typeof force === 'boolean') ? force : !sb.classList.contains('open');
    sb.classList.toggle('open', open);
    if (bd) bd.classList.toggle('show', open);
    if (tg) tg.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
}
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#sidebar .nav-link').forEach(function (a) {
        a.addEventListener('click', function () { if (window.innerWidth <= 991.98) otToggleSidebar(false); });
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') otToggleSidebar(false); });
    window.addEventListener('resize', function () { if (window.innerWidth > 991.98) otToggleSidebar(false); });
});
</script>
