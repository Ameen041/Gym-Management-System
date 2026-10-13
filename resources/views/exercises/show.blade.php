<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $exercise->display_name }} | {{ __('Ameen Gym') }}</title>
  @include('partials.head')
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/exercises.css') }}">
</head>
<body>
<div class="page">

  @include('exercises._topbar', [
      'heading'    => $exercise->display_name,
      'subheading' => $exercise->muscle_label . ' · ' . $exercise->difficulty_label,
      'back'       => route('exercises.index', ['muscle' => $exercise->muscle_group]),
  ])

  <div class="detail">
    <div class="side">
      <section class="panel">
        @if($exercise->embed_url)
          <div class="video-wrap">
            <iframe src="{{ $exercise->embed_url }}" title="{{ $exercise->display_name }}"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen loading="lazy"></iframe>
          </div>
        @elseif($exercise->video_src)
          <div class="video-wrap">
            <video src="{{ $exercise->video_src }}" controls playsinline preload="metadata"
                   @if($exercise->thumbnail) poster="{{ $exercise->thumbnail }}" @endif></video>
          </div>
        @else
          <div class="video-missing">
            <i class="fas fa-video"></i>
            <p>{{ __('The video explanation for this exercise hasn\'t been added yet.') }}</p>
            @if(app()->isLocale('ar') && $exercise->youtube_search_url_ar)
              <a href="{{ $exercise->youtube_search_url_ar }}" target="_blank" rel="noopener" class="btn btn-youtube">
                <i class="fab fa-youtube"></i> {{ __('Watch Arabic explanations on YouTube') }}
              </a>
            @endif
            <a href="{{ $exercise->youtube_search_url }}" target="_blank" rel="noopener" class="btn btn-youtube">
              <i class="fab fa-youtube"></i> {{ __('Watch examples on YouTube') }}
            </a>
          </div>
        @endif

        <h2>{{ $exercise->display_name }}</h2>
        @if(app()->isLocale('ar') && $exercise->name_ar)
          <p class="ar" dir="ltr">{{ $exercise->name }}</p>
        @endif
        @if($exercise->display_description)
          <p class="description" style="margin-top:10px">{{ $exercise->display_description }}</p>
        @endif
      </section>

      @if(count($exercise->steps))
        <section class="panel">
          <h3 class="section-title"><i class="fas fa-list-ol"></i> {{ __('How to perform') }}</h3>
          <ol class="steps">
            @foreach($exercise->steps as $step)
              <li>{{ $step }}</li>
            @endforeach
          </ol>
        </section>
      @endif
    </div>

    <aside class="side">
      <section class="panel">
        <h3 class="section-title"><i class="fas fa-info-circle"></i> {{ __('Details') }}</h3>
        <div class="meta">
          <div class="meta-row"><span>{{ __('Target muscle') }}</span><span>{{ $exercise->muscle_label }}</span></div>
          <div class="meta-row"><span>{{ __('Equipment') }}</span><span>{{ $exercise->display_equipment ?: '—' }}</span></div>
          <div class="meta-row"><span>{{ __('Level') }}</span><span>{{ $exercise->difficulty_label }}</span></div>
        </div>
      </section>

      @if(count($exercise->tips_list))
        <section class="panel">
          <h3 class="section-title"><i class="fas fa-lightbulb"></i> {{ __('Tips & common mistakes') }}</h3>
          <ul class="tips">
            @foreach($exercise->tips_list as $tip)
              <li><i class="fas fa-check-circle"></i><span>{{ $tip }}</span></li>
            @endforeach
          </ul>
        </section>
      @endif

      @if($related->isNotEmpty())
        <section class="panel">
          <h3 class="section-title"><i class="fas fa-dumbbell"></i> {{ __('More :muscle exercises', ['muscle' => $exercise->muscle_label]) }}</h3>
          <div class="related">
            @foreach($related as $item)
              <a href="{{ route('exercises.show', $item) }}">
                <span class="mini">
                  @if($item->thumbnail)
                    <img src="{{ $item->thumbnail }}" alt="" loading="lazy">
                  @else
                    <i class="fas fa-dumbbell"></i>
                  @endif
                </span>
                <span>
                  <strong>{{ $item->display_name }}</strong><br>
                  <small style="color:var(--muted)">{{ $item->display_equipment ?: $item->muscle_label }}</small>
                </span>
              </a>
            @endforeach
          </div>
        </section>
      @endif
    </aside>
  </div>

</div>
</body>
</html>
