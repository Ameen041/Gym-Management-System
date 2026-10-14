@extends('layouts.panel')
@section('title', __('Exercises'))

@section('content')
@php use App\Models\Exercise; @endphp
<div class="lib" data-library data-muscle="{{ $muscle }}">
  <div class="lib-tools">
    <div class="search-box">
      <i class="fas fa-magnifying-glass"></i>
      <input type="search" value="{{ $search }}" data-lib-search placeholder="{{ __('Search: bench, squat, pull-up…') }}" aria-label="{{ __('Search') }}">
    </div>
    <div class="chips" data-lib-muscles>
      <button type="button" class="chip {{ $muscle ? '' : 'on' }}" data-m="">{{ __('All') }} <span class="count">{{ $counts->sum() }}</span></button>
      @foreach(Exercise::MUSCLE_GROUPS as $key => $group)
        @if(($counts[$key] ?? 0) > 0)
          <button type="button" class="chip {{ $muscle === $key ? 'on' : '' }}" data-m="{{ $key }}">{{ Exercise::muscleLabel($key) }} <span class="count">{{ $counts[$key] }}</span></button>
        @endif
      @endforeach
    </div>
    <div class="chips chips-sub" data-lib-eq>
      @foreach(Exercise::EQUIPMENT_TYPES as $key => $label)
        <button type="button" class="chip" data-e="{{ $key }}">{{ __($label) }}</button>
      @endforeach
    </div>
  </div>

  <p class="muted small lib-count" data-lib-count></p>

  <div class="lib-grid">
    @foreach($exercises as $exercise)
      <a href="{{ route('exercises.show', $exercise) }}" class="lib-card" data-item data-m="{{ $exercise->muscle_group }}" data-e="{{ $exercise->equipment_type }}"
         data-text="{{ $exercise->name }} {{ $exercise->name_ar }} {{ $exercise->equipment }} {{ $exercise->equipment_ar }}">
        <span class="lib-thumb">
          @if($exercise->thumbnail)<img src="{{ $exercise->thumbnail }}" alt="" loading="lazy">@else<i class="fas fa-dumbbell"></i>@endif
          @if($exercise->has_video)<span class="lib-play"><i class="fas fa-play"></i></span>@endif
        </span>
        <span class="lib-body">
          <strong>{{ $exercise->display_name }}</strong>
          <span class="muted small">{{ $exercise->muscle_label }} · {{ $exercise->display_equipment ?: __('Bodyweight') }}</span>
        </span>
      </a>
    @endforeach
  </div>
  <div class="panel empty" data-lib-empty hidden><i class="fas fa-dumbbell"></i><h3>{{ __('No exercises found') }}</h3></div>
</div>
@endsection

@push('scripts')
<script>
  (function () {
    var root = document.querySelector("[data-library]");
    var norm = function (s) {
      return String(s || "").toLowerCase().replace(/[ً-ْـ]/g, "").replace(/[أإآٱ]/g, "ا")
        .replace(/ة/g, "ه").replace(/ى/g, "ي").replace(/[-_.,()/]/g, " ").replace(/\s+/g, " ").trim();
    };
    var items = Array.prototype.slice.call(root.querySelectorAll("[data-item]"));
    items.forEach(function (i) { i._key = norm(i.getAttribute("data-text") + " " + i.textContent); });
    var input = root.querySelector("[data-lib-search]");
    var state = { m: root.getAttribute("data-muscle") || "", e: "" };
    var label = @json(__(':count exercises'));

    function apply() {
      var tokens = norm(input.value).split(" ").filter(Boolean), shown = 0;
      items.forEach(function (i) {
        var ok = (!state.m || i.getAttribute("data-m") === state.m) && (!state.e || i.getAttribute("data-e") === state.e)
          && tokens.every(function (t) { return i._key.indexOf(t) !== -1; });
        i.hidden = !ok; if (ok) shown++;
      });
      root.querySelector("[data-lib-count]").textContent = label.replace(":count", shown);
      root.querySelector("[data-lib-empty]").hidden = shown > 0;
    }
    root.addEventListener("click", function (e) {
      var m = e.target.closest("[data-lib-muscles] [data-m]"), q = e.target.closest("[data-lib-eq] [data-e]");
      if (m) { state.m = m.getAttribute("data-m"); root.querySelectorAll("[data-lib-muscles] .chip").forEach(function (c) { c.classList.toggle("on", c === m); }); apply(); }
      if (q) { state.e = state.e === q.getAttribute("data-e") ? "" : q.getAttribute("data-e"); root.querySelectorAll("[data-lib-eq] .chip").forEach(function (c) { c.classList.toggle("on", c.getAttribute("data-e") === state.e); }); apply(); }
    });
    input.addEventListener("input", apply);
    apply();
  })();
</script>
@endpush
