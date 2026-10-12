<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ __('Exercise Library | Ameen Gym') }}</title>
  @include('partials.head')
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/exercises.css') }}">
</head>
<body>
<div class="page">

  @include('exercises._topbar', [
      'heading'    => __('Exercise Library'),
      'subheading' => __('Weight training exercises with video explanations'),
  ])

  <form class="filters" method="GET" action="{{ route('exercises.index') }}">
    @if($muscle)
      <input type="hidden" name="muscle" value="{{ $muscle }}">
    @endif
    <label class="search">
      <i class="fas fa-search"></i>
      <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search exercises or equipment...') }}">
    </label>

    <nav class="chips">
      <a href="{{ route('exercises.index', array_filter(['q' => $search])) }}" class="chip {{ !$muscle ? 'active' : '' }}">
        {{ __('All') }} <small>{{ $counts->sum() }}</small>
      </a>
      @foreach(\App\Models\Exercise::MUSCLE_GROUPS as $key => $group)
        @if(($counts[$key] ?? 0) > 0)
          <a href="{{ route('exercises.index', array_filter(['muscle' => $key, 'q' => $search])) }}"
             class="chip {{ $muscle === $key ? 'active' : '' }}">
            {{ \App\Models\Exercise::muscleLabel($key) }} <small>{{ $counts[$key] }}</small>
          </a>
        @endif
      @endforeach
    </nav>
  </form>

  @if($exercises->isEmpty())
    <div class="empty">
      <i class="fas fa-dumbbell"></i>
      <h3>{{ __('No exercises found') }}</h3>
      <p>{{ __('Try a different search or muscle group.') }}</p>
    </div>
  @else
    <p class="result-count">{{ __(':count exercises', ['count' => $exercises->count()]) }}</p>
    <div class="grid">
      @foreach($exercises as $exercise)
        <a href="{{ route('exercises.show', $exercise) }}" class="ex-card">
          <div class="ex-thumb">
            @if($exercise->thumbnail)
              <img src="{{ $exercise->thumbnail }}" alt="{{ $exercise->display_name }}" loading="lazy">
            @else
              <i class="fas fa-dumbbell"></i>
            @endif
            @if($exercise->has_video)
              <span class="play"><i class="fas fa-play"></i></span>
            @else
              <span class="no-video"><i class="fas fa-video-slash"></i> {{ __('Video coming soon') }}</span>
            @endif
          </div>
          <div class="ex-body">
            <h3>{{ $exercise->display_name }}</h3>
            @if($exercise->display_description)
              <p>{{ $exercise->display_description }}</p>
            @endif
            <div class="tags">
              <span class="tag">{{ $exercise->muscle_label }}</span>
              @if($exercise->display_equipment)
                <span class="tag">{{ $exercise->display_equipment }}</span>
              @endif
              <span class="tag level-{{ $exercise->difficulty }}">{{ $exercise->difficulty_label }}</span>
            </div>
          </div>
        </a>
      @endforeach
    </div>
  @endif

</div>
</body>
</html>
