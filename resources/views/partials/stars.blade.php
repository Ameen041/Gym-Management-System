{{-- Star rating: $rating (0-5), $count (null = show the stars only) --}}
@php($rounded = round(($rating ?? 0) * 2) / 2)
<span class="stars" title="{{ number_format($rating ?? 0, 1) }}">
  @for($i = 1; $i <= 5; $i++)
    @if($rounded >= $i)
      <i class="fas fa-star"></i>
    @elseif($rounded >= $i - 0.5)
      <i class="fas fa-star-half-alt"></i>
    @else
      <i class="far fa-star"></i>
    @endif
  @endfor
  @if(($count ?? null) === null)
  @elseif($count > 0)
    <small class="muted">{{ number_format($rating, 1) }} ({{ $count }})</small>
  @else
    <small class="muted">{{ __('New') }}</small>
  @endif
</span>
