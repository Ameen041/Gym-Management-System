{{-- Applies the saved day / night theme before the page paints (no flash). --}}
<script>
  (function () {
    try {
      var t = localStorage.getItem('theme');
      if (t === 'dark' || t === 'light') document.documentElement.setAttribute('data-theme', t);
    } catch (e) {}
  })();
</script>
