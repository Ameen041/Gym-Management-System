@extends('layouts.panel')
@section('title', $exercise->display_name)

@section('content')
<a href="{{ route('exercises.index', ['muscle' => $exercise->muscle_group]) }}" class="back-link"><i class="fas fa-arrow-right-long"></i> {{ __('Exercises') }}</a>

<div class="ex-detail">
  <div class="ex-main">
    @php($frames = $exercise->frame_urls)
    @if($exercise->video_src)
      <div class="ex-video">
        <video src="{{ $exercise->video_src }}" controls playsinline preload="metadata" @if($exercise->thumbnail) poster="{{ $exercise->thumbnail }}" @endif></video>
      </div>
    @elseif($exercise->motion)
      {{-- Himma's own animation (js/exercise-motion.js): the figure does the movement, the working muscles light up --}}
      @php($worked = $exercise->muscles)
      <figure class="ex-demo ex-motion">
        <div class="ex-demo-stage">
          <svg data-motion="{{ $exercise->motion }}" data-primary="{{ implode(' ', $worked['primary']) }}" data-secondary="{{ implode(' ', $worked['secondary']) }}"
               role="img" aria-label="{{ __(':name, animation', ['name' => $exercise->display_name]) }}"></svg>
        </div>
        <div class="ex-demo-bar">
          <button type="button" class="ex-demo-btn" data-motion-play aria-pressed="true" data-play-text="{{ __('Play') }}">
            <i class="fas fa-pause"></i><span>{{ __('Pause') }}</span>
          </button>
          <span class="mm-legend"><span><i class="mm-dot mm-on"></i> {{ __('Main') }}</span><span><i class="mm-dot mm-soft"></i> {{ __('Helping') }}</span></span>
        </div>
      </figure>
    @elseif($frames)
      {{-- the drawings of the start and end position take turns, so the movement shows like a short clip --}}
      <figure class="ex-demo {{ $exercise->is_drawing ? 'is-drawing' : '' }}" data-demo>
        <div class="ex-demo-stage">
          @foreach($frames as $i => $src)
            <img src="{{ $src }}" alt="{{ $exercise->display_name }}{{ count($frames) > 1 ? ' – ' . ($i === 0 ? __('Start') : ($i === count($frames) - 1 ? __('End') : __('Middle'))) : '' }}" class="{{ $i === 0 ? 'on' : '' }}" @if($i) loading="lazy" @endif>
          @endforeach
        </div>
        @if(count($frames) > 1)
          <div class="ex-demo-bar">
            <button type="button" class="ex-demo-btn" data-demo-play aria-pressed="true" data-play-text="{{ __('Play') }}">
              <i class="fas fa-pause"></i><span>{{ __('Pause') }}</span>
            </button>
            <div class="ex-demo-steps">
              @foreach($frames as $i => $src)
                <button type="button" data-demo-step="{{ $i }}" class="{{ $i === 0 ? 'on' : '' }}">{{ $i === 0 ? __('Start') : ($i === count($frames) - 1 ? __('End') : __('Middle')) }}</button>
              @endforeach
            </div>
          </div>
        @endif
        @if($exercise->is_drawing)
          <figcaption class="ex-credit">{{ __('Drawing') }}: <a href="https://github.com/everkinetic/data" target="_blank" rel="noopener">Everkinetic</a> · <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener">CC BY-SA 4.0</a></figcaption>
        @endif
      </figure>
    @else
      <div class="ex-video">
        <div class="ex-novideo">
          <i class="fas fa-person-running"></i>
          <p>{{ __('The video for this exercise is coming soon. Follow the steps below.') }}</p>
        </div>
      </div>
    @endif

    <div class="ex-title">
      <h1>{{ $exercise->display_name }}</h1>
      @if(app()->isLocale('ar') && $exercise->name_ar)<span class="muted small" dir="ltr">{{ $exercise->name }}</span>@endif
      <div class="tags" style="margin-top:8px">
        <span class="badge">{{ $exercise->muscle_label }}</span>
        <span class="badge badge-muted">{{ $exercise->display_equipment ?: __('Bodyweight') }}</span>
        <span class="badge badge-muted">{{ $exercise->difficulty_label }}</span>
      </div>
      @if($exercise->display_description)<p class="muted" style="margin-top:10px">{{ $exercise->display_description }}</p>@endif
    </div>

    <section class="panel">
      <div class="panel-head"><h2><i class="fas fa-child-reaching"></i> {{ __('Muscles worked') }}</h2></div>
      @include('partials.muscle-map', ['muscles' => $exercise->muscles, 'id' => $exercise->id])
    </section>

    @if(count($exercise->steps))
      <section class="panel">
        <div class="panel-head"><h2><i class="fas fa-list-ol"></i> {{ __('How to perform') }}</h2></div>
        <ol class="steps-list">
          @foreach($exercise->steps as $step)<li>{{ $step }}</li>@endforeach
        </ol>
      </section>
    @endif
  </div>

  <aside class="ex-side">
    @if(count($exercise->tips_list))
      <section class="panel">
        <div class="panel-head"><h2><i class="fas fa-lightbulb"></i> {{ __('Tips') }}</h2></div>
        <ul class="tip-list">
          @foreach($exercise->tips_list as $tip)<li><i class="fas fa-check"></i><span>{{ $tip }}</span></li>@endforeach
        </ul>
      </section>
    @endif

    @if($related->isNotEmpty())
      <section class="panel">
        <div class="panel-head"><h2><i class="fas fa-dumbbell"></i> {{ __('More :muscle exercises', ['muscle' => $exercise->muscle_label]) }}</h2></div>
        <div class="stack">
          @foreach($related as $item)
            <a href="{{ route('exercises.show', $item) }}" class="pick">
              <span class="pick-icon {{ $item->is_drawing ? 'is-drawing' : '' }}">@if($item->thumbnail)<img src="{{ $item->thumbnail }}" alt="" loading="lazy">@else<i class="fas fa-dumbbell"></i>@endif</span>
              <span><span class="pick-name">{{ $item->display_name }}</span><span class="pick-meta">{{ $item->display_equipment ?: __('Bodyweight') }}</span></span>
              <i class="fas fa-chevron-left muted flip-ltr"></i>
            </a>
          @endforeach
        </div>
      </section>
    @endif
  </aside>
</div>
@endsection

@push('scripts')
  @if(!$exercise->video_src && $exercise->motion)
    <script src="{{ \App\Support\Asset::url('js/exercise-motion.js') }}" defer></script>
  @endif
@endpush
