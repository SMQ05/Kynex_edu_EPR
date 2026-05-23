{{--
  Shared lightweight JS for public pages: mobile drawer + scroll reveal.
  No dependencies. Pair with partials/theme.blade.php.
  Markup contract:
    - Toggle button:   [data-nav-open]
    - Close button:    [data-nav-close]
    - Backdrop:        .drawer-backdrop  (also closes on click)
    - Reveal elements: .reveal  (gain .in when scrolled into view)
--}}
<script>
(function () {
  // ── Mobile drawer ──────────────────────────────────────────────
  var open  = document.querySelector('[data-nav-open]');
  var body  = document.body;
  function setNav(state){ body.classList.toggle('nav-open', state); if (open) open.setAttribute('aria-expanded', state); }
  if (open) open.addEventListener('click', function(){ setNav(!body.classList.contains('nav-open')); });
  document.querySelectorAll('[data-nav-close], .drawer-backdrop, .drawer-link').forEach(function(el){
    el.addEventListener('click', function(){ setNav(false); });
  });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') setNav(false); });

  // ── Scroll reveal (respects reduced-motion) ────────────────────
  var items = document.querySelectorAll('.reveal');
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduce || !('IntersectionObserver' in window)) {
    items.forEach(function(el){ el.classList.add('in'); });
  } else {
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){
        if (en.isIntersecting){ en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    items.forEach(function(el, i){ el.style.transitionDelay = (Math.min(i % 4, 3) * 70) + 'ms'; io.observe(el); });
  }
})();
</script>
