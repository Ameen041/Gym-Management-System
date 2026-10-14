@extends('layouts.panel')
@section('title', $exercise->display_name)

@section('content')
<a href="{{ route('exercises.index', ['muscle' => $exercise->muscle_group]) }}" class="back-link"><i class="fas fa-arrow-right-long"></i> {{ __('Exercises') }}</a>

<div class="ex-detail">
  <div class="ex-main">
    <div class="ex-video">
      @if($exercise->embed_url)
        <iframe src="{{ $exercise->embed_url }}" title="{{ $exercise->display_name }}"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen loading="lazy"></iframe>
      @elseif($exercise->video_src)
        <video src="{{ $exercise->video_src }}" controls playsinline preload="metadata" @if($exercise->thumbnail) poster="{{ $exercise->thumbnail }}" @endif></video>
      @else
        <div class="ex-novideo">
          <i class="fas fa-video"></i>
          <p>{{ __('No video yet') }}</p>
          <div class="form-actions" style="justify-content:center">
            @if(app()->isLocale('ar') && $exercise->youtube_search_url_ar)
              <a href="{{ $exercise->youtube_search_url_ar }}" target="_blank" rel="noopener" class="btn btn-sm btn-youtube"><i class="fab fa-youtube"></i> {{ __('Arabic videos') }}</a>
            @endif
            <a href="{{ $exercise->youtube_search_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-youtube"><i class="fab fa-youtube"></i> {{ __('YouTube') }}</a>
          </div>
        </div>
      @endif
    </div>

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
              <span class="pick-icon">@if($item->thumbnail)<img src="{{ $item->thumbnail }}" alt="" loading="lazy">@else<i class="fas fa-dumbbell"></i>@endif</span>
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
