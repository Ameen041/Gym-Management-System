{{-- Coach card for the directory and the home page: $coach (with coachProfile and min_price) --}}
@php($profile = $coach->coachProfile)
<a href="{{ route('coaches.show', $coach) }}" class="coach-card">
  <div class="coach-photo">
    <img src="{{ $coach->photo_url }}" alt="{{ $coach->name }}" loading="lazy">
    @if($profile->is_featured)
      <span class="badge"><i class="fas fa-crown"></i> {{ __('Featured') }}</span>
    @endif
  </div>
  <div class="coach-body">
    <h3>{{ $coach->name }}</h3>
    @include('partials.stars', ['rating' => $profile->rating_avg, 'count' => $profile->rating_count])
    <div class="coach-meta">
      @if($profile->city_label)<span><i class="fas fa-location-dot"></i> {{ $profile->city_label }}</span>@endif
      @if($profile->experience_years)<span><i class="fas fa-medal"></i> {{ __(':years years experience', ['years' => $profile->experience_years]) }}</span>@endif
    </div>
    <div class="tags">
      @foreach(array_slice($profile->specialty_labels, 0, 3) as $label)
        <span class="badge badge-muted">{{ $label }}</span>
      @endforeach
    </div>
    <div class="coach-foot">
      <span class="price">{{ \App\Support\Money::format($coach->min_price) }} <small>/ {{ __('month') }}</small></span>
      <span class="btn btn-sm btn-primary">{{ __('View') }}</span>
    </div>
  </div>
</a>
