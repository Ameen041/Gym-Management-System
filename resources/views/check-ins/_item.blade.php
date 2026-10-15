{{-- One check-in. $checkIn, $previous (earlier check-in or null), $coachView (bool) --}}
@php
  use App\Models\CheckIn;
  $kg = fn ($w) => \App\Http\Controllers\CheckInController::kg($w);
  $change = $previous ? round($checkIn->weight - $previous->weight, 1) : null;
@endphp
<article class="ci" id="check-in-{{ $checkIn->id }}">
  <div class="ci-head">
    <b>{{ $kg($checkIn->weight) }} {{ __('kg') }}</b>
    @if($change !== null)
      <span class="muted small" dir="ltr">{{ $change > 0 ? '+' : '' }}{{ $kg($change) }}</span>
    @endif
    <span class="muted small grow">{{ $checkIn->created_at->translatedFormat('l j M') }}</span>
    @if($coachView && !$checkIn->replied_at)
      <span class="badge badge-warn">{{ __('Needs a reply') }}</span>
    @endif
  </div>

  <div class="ci-facts">
    @if($checkIn->waist)<span><i class="fas fa-ruler-horizontal"></i>{{ __('Waist') }} {{ $kg($checkIn->waist) }} {{ __('cm') }}</span>@endif
    @if($checkIn->workouts_done !== null)<span><i class="fas fa-dumbbell"></i>{{ trans_choice('{0} No workouts|{1} 1 workout|[2,*] :count workouts', $checkIn->workouts_done) }}</span>@endif
    @if($checkIn->diet_adherence)<span><i class="fas fa-utensils"></i>{{ __('Diet') }}: {{ __(CheckIn::DIET_SCALE[$checkIn->diet_adherence]) }}</span>@endif
    @if($checkIn->energy)<span><i class="fas fa-bolt"></i>{{ __('Energy') }}: {{ __(CheckIn::FEEL_SCALE[$checkIn->energy]) }}</span>@endif
    @if($checkIn->sleep)<span><i class="fas fa-moon"></i>{{ __('Sleep') }}: {{ __(CheckIn::FEEL_SCALE[$checkIn->sleep]) }}</span>@endif
  </div>

  @if($checkIn->note)<p class="ci-note" dir="auto">{{ $checkIn->note }}</p>@endif

  @if($checkIn->photos)
    <div class="thumbs">
      @foreach($checkIn->photoUrls() as $url)
        <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="{{ __('Progress photo') }}" loading="lazy"></a>
      @endforeach
    </div>
  @endif

  @if($checkIn->coach_reply)
    <div class="ci-reply">
      <small><i class="fas fa-comment-dots"></i> {{ $coachView ? __('Your reply') : __('Your coach') }}
        @if($coachView)<span class="muted">· {{ $checkIn->replied_at->diffForHumans() }}</span>@endif</small>
      <p dir="auto">{{ $checkIn->coach_reply }}</p>
    </div>
  @elseif(!$coachView)
    <p class="muted small" style="margin-top:8px"><i class="fas fa-hourglass-half"></i> {{ __('Waiting for your coach\'s reply') }}</p>
  @endif

  @if($coachView)
    @if($checkIn->coach_reply)
      <details class="ci-edit"><summary><i class="fas fa-pen"></i> {{ __('Edit reply') }}</summary>@include('check-ins._reply-form')</details>
    @else
      @include('check-ins._reply-form')
    @endif
  @endif
</article>
