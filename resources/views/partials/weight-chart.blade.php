{{--
  Weight over time (drawn by public/js/weight-chart.js to the width of its box).
  $points: list of ['date' => Carbon, 'weight' => float, 'label' => string], oldest first.
  The check-in list next to it shows the same numbers without the chart.
--}}
@php
  $kg = fn ($w) => \App\Http\Controllers\CheckInController::kg($w);
  $data = array_map(fn ($p) => [
      't' => $p['date']->timestamp, 'w' => $p['weight'],
      'd' => $p['date']->translatedFormat('j M Y'), 'a' => $p['date']->translatedFormat('j M'), 'l' => $p['label'],
  ], $points);
  $first = reset($points); $last = end($points);
@endphp
<figure class="wchart" data-chart='@json($data)' data-unit="{{ __('kg') }}" tabindex="0" role="img"
        aria-label="{{ __('Weight from :from kg to :to kg between :start and :end', ['from' => $kg($first['weight']), 'to' => $kg($last['weight']), 'start' => $first['date']->translatedFormat('j M'), 'end' => $last['date']->translatedFormat('j M')]) }}">
  <div class="wchart-tip" hidden><b></b><span></span></div>
</figure>
@once
  @push('scripts')
    <script src="{{ \App\Support\Asset::url('js/weight-chart.js') }}" defer></script>
  @endpush
@endonce
