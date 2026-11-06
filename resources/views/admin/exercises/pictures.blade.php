@extends('layouts.admin')
@section('title', __('Exercise videos'))
@section('page-title', __('Exercise videos'))

@section('content')
@php use App\Models\Exercise; use App\Support\PicturePrompt; @endphp

<div class="page-header">
  <h1>{{ __('Exercise videos') }}</h1>
  <p>{{ __('For each exercise: make one picture with Gemini, ask it to animate the picture, and upload the video here. The cover is taken from the video.') }}</p>
</div>

<div class="card pic-help">
  <h3><i class="fas fa-circle-info"></i> {{ __('How to make them') }}</h3>
  <ol>
    <li>{{ __('Open Gemini, start a new chat and choose "Create image".') }}</li>
    <li>{{ __('Copy the first text (the picture) and send it.') }}</li>
    <li>{{ __('Copy the second text (the video) and send it in the same chat, with the picture: Gemini animates it.') }}</li>
    <li>{{ __('Download the video and upload it under the exercise. Its cover is taken from the video by itself.') }}</li>
  </ol>
  <p class="muted small">{{ __('A video with a mistake (an extra arm, a bent bar, a wrong move)? Ask Gemini again in a new chat. Videos are saved on the server; keep a copy of them on your computer too.') }}</p>
</div>

<div class="card">
  <form method="GET" action="{{ route('admin.exercises.pictures') }}" class="pic-filters">
    <div class="pic-tabs">
      @foreach(['todo' => __('Not done yet (:count)', ['count' => $todo]), 'done' => __('Done (:count)', ['count' => $done]), 'all' => __('All')] as $key => $label)
        <a href="{{ route('admin.exercises.pictures', array_filter(['show' => $key, 'muscle' => $muscle])) }}" class="btn btn-sm {{ $show === $key ? 'btn-primary' : 'btn-secondary' }}">{{ $label }}</a>
      @endforeach
    </div>
    <input type="hidden" name="show" value="{{ $show }}">
    <select name="muscle" class="form-control" onchange="this.form.submit()" style="max-width:220px">
      <option value="">{{ __('All muscles') }}</option>
      @foreach(Exercise::MUSCLE_GROUPS as $key => $group)
        <option value="{{ $key }}" @selected($muscle === $key)>{{ Exercise::muscleLabel($key) }}</option>
      @endforeach
    </select>
  </form>

  @forelse($exercises as $exercise)
    @php($m = $exercise->muscles)
    <section class="pic-card" id="ex-{{ $exercise->id }}">
      <div class="pic-head">
        <div class="pic-now {{ $exercise->is_drawing ? 'is-drawing' : '' }}">
          @forelse($exercise->frame_urls as $src)
            <img src="{{ $src }}" alt="" loading="lazy">
          @empty
            <span class="pic-none"><i class="fas fa-image"></i></span>
          @endforelse
        </div>
        <div class="pic-title">
          <strong>{{ $exercise->display_name }}</strong>
          @if(app()->isLocale('ar') && $exercise->name_ar)<span class="muted small" dir="ltr">{{ $exercise->name }}</span>@endif
          <span class="small">
            @if($exercise->has_video)
              <span class="badge badge-success"><i class="fas fa-video"></i> {{ __('Video added') }}</span>
            @elseif($exercise->has_own_pictures)
              <span class="badge badge-success"><i class="fas fa-image"></i> {{ __('Pictures added') }}</span>
            @else
              <span class="badge badge-warning">{{ __('Not done yet') }}</span>
            @endif
            <span class="muted">{{ __('Main') }}: {{ collect($m['primary'])->map(fn ($k) => Exercise::muscleName($k))->implode('، ') }}</span>
          </span>
        </div>
      </div>

      <div class="pic-steps">
        <div class="pic-step">
          <span class="pic-num">1</span>
          <div class="pic-text">
            <div class="pic-label">{{ __('First text: the picture') }}</div>
            <p class="pic-prompt" dir="ltr" id="p1-{{ $exercise->id }}">{{ PicturePrompt::start($exercise) }}</p>
          </div>
          <button type="button" class="btn btn-sm btn-primary" data-copy-text="#p1-{{ $exercise->id }}" data-done="{{ __('Copied') }}"><i class="fas fa-copy"></i> {{ __('Copy') }}</button>
        </div>
        <div class="pic-step">
          <span class="pic-num">2</span>
          <div class="pic-text">
            <div class="pic-label">{{ __('Second text: the video (in the same chat, with the picture)') }}</div>
            <p class="pic-prompt" dir="ltr" id="p3-{{ $exercise->id }}">{{ PicturePrompt::video($exercise) }}</p>
          </div>
          <button type="button" class="btn btn-sm btn-primary" data-copy-text="#p3-{{ $exercise->id }}" data-done="{{ __('Copied') }}"><i class="fas fa-copy"></i> {{ __('Copy') }}</button>
        </div>
        <form class="pic-step" method="POST" action="{{ route('admin.exercises.video', $exercise->id) }}" enctype="multipart/form-data" data-upload>
          @csrf
          <span class="pic-num">3</span>
          <div class="pic-text">
            <div class="pic-label">{{ $exercise->has_video ? __('Replace the video') : __('Upload the video') }}</div>
            {{-- the browser takes a frame of the video as the cover (app.js: data-poster) --}}
            <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime" required class="form-control" data-poster>
            <input type="file" name="poster" accept="image/*" hidden>
            <div class="form-hint small muted" data-poster-note hidden><i class="fas fa-image"></i> {{ __('Cover taken from the video.') }}</div>
            <div class="upload-progress" data-upload-progress hidden><span></span></div>
            <div class="upload-error" data-upload-error hidden></div>
          </div>
          <button class="btn btn-sm btn-success"><i class="fas fa-upload"></i> {{ __('Save') }}</button>
        </form>

        <details class="pic-more">
          <summary>{{ __('Pictures instead of a video (optional)') }}</summary>
          <div class="pic-steps">
            <div class="pic-step">
              <span class="pic-num">+</span>
              <div class="pic-text">
                <div class="pic-label">{{ __('Text for the end picture') }}</div>
                <p class="pic-prompt" dir="ltr" id="p2-{{ $exercise->id }}">{{ PicturePrompt::end($exercise) }}</p>
              </div>
              <button type="button" class="btn btn-sm btn-secondary" data-copy-text="#p2-{{ $exercise->id }}" data-done="{{ __('Copied') }}"><i class="fas fa-copy"></i> {{ __('Copy') }}</button>
            </div>
            <form class="pic-step" method="POST" action="{{ route('admin.exercises.pictures.store', $exercise->id) }}" enctype="multipart/form-data" data-upload>
              @csrf
              <span class="pic-num">+</span>
              <div class="pic-text">
                <div class="pic-label">{{ __('Upload the two pictures') }}</div>
                <div class="pic-files">
                  <label>{{ __('Start') }} <input type="file" name="start" accept="image/*" class="form-control" data-shrink="1200"></label>
                  <label>{{ __('End') }} <input type="file" name="end" accept="image/*" class="form-control" data-shrink="1200"></label>
                </div>
                <div class="upload-progress" data-upload-progress hidden><span></span></div>
                <div class="upload-error" data-upload-error hidden></div>
              </div>
              <button class="btn btn-sm btn-secondary"><i class="fas fa-upload"></i> {{ __('Save') }}</button>
            </form>
          </div>
        </details>
        @if($exercise->has_own_pictures && !str_starts_with((string) ($exercise->frames[0] ?? ''), 'images/'))
          <form method="POST" action="{{ route('admin.exercises.pictures.destroy', $exercise->id) }}" onsubmit="return confirm(@js(__('Remove these pictures and go back to the drawing?')))" class="pic-remove">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-secondary"><i class="fas fa-rotate-left"></i> {{ __('Remove the pictures') }}</button>
          </form>
        @endif
      </div>
    </section>
  @empty
    <div class="empty-state">
      <i class="fas fa-circle-check"></i>
      <h3>{{ $show === 'todo' ? __('All exercises have their video or pictures.') : __('No exercises found') }}</h3>
    </div>
  @endforelse

  <div style="margin-top:16px">{{ $exercises->links() }}</div>
</div>

<style>
  .pic-help ol{margin:8px 0 8px;padding-inline-start:20px;display:grid;gap:4px}
  .pic-filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-bottom:16px}
  .pic-tabs{display:flex;gap:6px;flex-wrap:wrap}
  .pic-card{border:1px solid var(--gray-400);border-radius:14px;padding:14px;margin-bottom:14px;display:grid;gap:12px}
  .pic-head{display:flex;gap:12px;align-items:center}
  .pic-now{display:flex;gap:4px;flex:none}
  .pic-now img,.pic-none{width:64px;height:64px;border-radius:10px;object-fit:contain;background:var(--drawing-bg, #fff);border:1px solid var(--gray-400)}
  .pic-none{display:grid;place-items:center;color:var(--gray-700)}
  .pic-title{display:grid;gap:3px;min-width:0}
  .pic-steps{display:grid;gap:10px}
  .pic-step{display:grid;grid-template-columns:28px minmax(0,1fr) auto;gap:10px;align-items:start}
  .pic-num{width:26px;height:26px;border-radius:50%;display:grid;place-items:center;background:var(--primary, #119c4b);color:#fff;font-weight:800;font-size:.85rem}
  .pic-label{font-weight:700;font-size:.9rem;margin-bottom:4px}
  .pic-prompt{font-size:.78rem;line-height:1.5;color:var(--gray-700);background:var(--gray-300);border-radius:10px;padding:8px 10px;max-height:96px;overflow:auto;text-align:left;margin:0}
  .pic-files{display:flex;gap:8px;flex-wrap:wrap}
  .pic-files label{display:grid;gap:4px;font-size:.85rem;font-weight:600;flex:1 1 200px}
  .pic-remove{justify-self:start}
  .pic-more{border-top:1px dashed var(--gray-400);padding-top:8px}
  .pic-more > summary{cursor:pointer;font-weight:700;font-size:.85rem;color:var(--gray-700)}
  .pic-more > .pic-steps{margin-top:10px}
  @media (max-width:640px){
    .pic-step{grid-template-columns:26px minmax(0,1fr)}
    .pic-step > .btn{grid-column:2;justify-self:start}
  }
</style>

<script>
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-copy-text]');
    if (!btn) return;
    var text = document.querySelector(btn.getAttribute('data-copy-text')).textContent.trim();
    var done = function () {
      var old = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-check"></i> ' + btn.getAttribute('data-done');
      setTimeout(function () { btn.innerHTML = old; }, 1800);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done);
    } else {
      // older phones: select the text and copy it
      var area = document.createElement('textarea');
      area.value = text; area.style.position = 'fixed'; area.style.opacity = '0';
      document.body.appendChild(area); area.select();
      try { document.execCommand('copy'); done(); } catch (err) {}
      document.body.removeChild(area);
    }
  });
</script>
@endsection
