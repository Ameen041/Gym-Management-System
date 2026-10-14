{{-- Applies the saved day/night theme and accent before the page paints (no flash). --}}
<script>
  (function () {
    var d = document.documentElement;
    try {
      var q = new URLSearchParams(location.search).get('accent');
      if (q === 'green' || q === 'purple') localStorage.setItem('accent', q);
      var a = localStorage.getItem('accent');
      if (a === 'green' || a === 'purple') d.setAttribute('data-accent', a);
      var t = localStorage.getItem('theme');
      if (t === 'dark' || t === 'light') d.setAttribute('data-theme', t);
    } catch (e) {}
  })();
</script>
