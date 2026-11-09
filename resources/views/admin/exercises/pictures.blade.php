@extends('layouts.admin')
@section('title', __('Exercise pictures'))
@section('page-title', __('Exercise pictures'))

@section('content')
@php use App\Models\Exercise; use App\Support\PicturePrompt; @endphp

<div class="page-header">
  <h1>{{ __('Exercise pictures') }}</h1>
  <p>{{ __('Make two pictures for each exercise with Gemini (start and end), in the same style as the bench press, then upload them here.') }}</p>
</div>

<div class="card pic-help">
  <h3><i class="fas fa-circle-info"></i> {{ __('How to make them') }}</h3>
  <ol>
    <li>{{ __('Open Gemini, start a new chat and choose "Create image".') }}</li>
    <li>{{ __('Copy the first text of the exercise, paste it in Gemini and send it.') }}</li>
    <li>{{ __('For the end picture: tap + in Gemini, attach the start picture, paste the second text and send it. Gemini keeps the picture and changes only the pose.') }}</li>
    <li>{{ __('Download both pictures and upload them under the exercise: first the start, then the end.') }}</li>
    <li>{{ __('For a video: send the start picture to Gemini with the video text (step 4), download the video and upload it (step 5). It plays by itself on the exercise page, without sound.') }}</li>
  </ol>
  <p class="muted small">{{ __('A picture with a mistake (an extra arm, a bent bar, a wrong position)? Ask Gemini again in a new chat. Pictures are saved on the server; keep a copy of them on your computer too.') }}</p>
</div>

<div class="card">
  <form method="GET" action="{{ route('admin.exercises.pictures') }}" class="pic-filters">
    <div class="pic-tabs">
      @foreach(['todo' => __('Not done yet (:count)', ['count' => $todo]), 'done' => __('Done (:count)', ['count' => $done]), 'all' => __('All')] as $key => $label)
        <a href="{{ route('admin.exercises.pictures', array_filter(['show' => $key, 'muscle' => $muscle, 'q' => $q])) }}" class="btn btn-sm {{ $show === $key ? 'btn-primary' : 'btn-secondary' }}">{{ $label }}</a>
      @endforeach
    </div>
    <input type="hidden" name="show" value="{{ $show }}">
    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="{{ __('Search an exercise…') }}" style="max-width:220px">
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
            @if($exercise->has_own_pictures)
              <span class="badge badge-success"><i class="fas fa-check"></i> {{ __('Done') }}</span>
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
            <div class="pic-label">{{ __('First text: the start position') }}</div>
            <p class="pic-prompt" dir="ltr" id="p1-{{ $exercise->id }}">{{ PicturePrompt::start($exercise) }}</p>
          </div>
          <button type="button" class="btn btn-sm btn-primary" data-copy-text="#p1-{{ $exercise->id }}" data-done="{{ __('Copied') }}"><i class="fas fa-copy"></i> {{ __('Copy') }}</button>
        </div>
        <div class="pic-step">
          <span class="pic-num">2</span>
          <div class="pic-text">
            <div class="pic-label">{{ __('Second text: attach the start picture with it, and Gemini changes only the pose') }}</div>
            <p class="pic-prompt" dir="ltr" id="pe-{{ $exercise->id }}">{{ PicturePrompt::edit($exercise) }}</p>
            {{-- drawn from scratch instead, when editing gives the first picture back --}}
            <details class="pic-alt">
              <summary>{{ __('Gemini sent the same picture back? Use this text in a new chat instead') }}</summary>
              <p class="pic-prompt" dir="ltr" id="p2-{{ $exercise->id }}">{{ PicturePrompt::end($exercise) }}</p>
              <button type="button" class="btn btn-sm btn-secondary" data-copy-text="#p2-{{ $exercise->id }}" data-done="{{ __('Copied') }}"><i class="fas fa-copy"></i> {{ __('Copy') }}</button>
            </details>
          </div>
          <button type="button" class="btn btn-sm btn-primary" data-copy-text="#pe-{{ $exercise->id }}" data-done="{{ __('Copied') }}"><i class="fas fa-copy"></i> {{ __('Copy') }}</button>
        </div>
        @if($exercise->has_own_pictures)
          {{-- each picture can be changed on its own (it is sent as soon as it is chosen), or the two swapped --}}
          <div class="pic-step">
            <span class="pic-num">3</span>
            <div class="pic-text">
              <div class="pic-label">{{ __('The pictures') }}</div>
              <div class="pic-pair">
                @foreach(['start' => __('Start'), 'end' => __('End')] as $field => $label)
                  @php($src = $exercise->frame_urls[$loop->index] ?? null)
                  <div class="pic-one">
                    <form method="POST" action="{{ route('admin.exercises.pictures.store', $exercise->id) }}" enctype="multipart/form-data" data-upload>
                      @csrf
                      <div class="pic-one-label">{{ $label }}</div>
                      @if($src)<img src="{{ $src }}" alt="{{ $label }}" loading="lazy">@else<span class="pic-none"><i class="fas fa-image"></i></span>@endif
                      <div class="pic-one-actions">
                        <label class="btn btn-sm btn-secondary">
                          <i class="fas fa-arrows-rotate"></i> {{ __('Change picture') }}
                          <input type="file" name="{{ $field }}" accept="image/*" hidden data-shrink="1200" data-autosubmit>
                        </label>
                        @if($src)
                          {{-- facing the other way than its partner: mirror it on the server --}}
                          <button type="submit" class="btn btn-sm btn-secondary" form="flip-{{ $exercise->id }}-{{ $loop->index }}" title="{{ __('Turn the picture around (mirror)') }}">
                            <i class="fas fa-arrows-left-right"></i> {{ __('Turn around') }}
                          </button>
                        @endif
                      </div>
                      <div class="upload-progress" data-upload-progress hidden><span></span></div>
                      <div class="upload-error" data-upload-error hidden></div>
                    </form>
                    @if($src)
                      <form id="flip-{{ $exercise->id }}-{{ $loop->index }}" method="POST" action="{{ route('admin.exercises.pictures.flip', [$exercise->id, $loop->index]) }}" hidden>@csrf</form>
                    @endif
                  </div>
                @endforeach
              </div>
              @if(count($exercise->frame_urls) === 2)
                <form method="POST" action="{{ route('admin.exercises.pictures.swap', $exercise->id) }}" style="margin-top:8px">
                  @csrf
                  <button class="btn btn-sm btn-secondary"><i class="fas fa-right-left"></i> {{ __('Swap start and end') }}</button>
                </form>
              @endif
            </div>
          </div>
        @else
        <form class="pic-step" method="POST" action="{{ route('admin.exercises.pictures.store', $exercise->id) }}" enctype="multipart/form-data" data-upload>
          @csrf
          <span class="pic-num">3</span>
          <div class="pic-text">
            <div class="pic-label">{{ __('Upload the two pictures') }}</div>
            <div class="pic-files">
              <label>{{ __('Start') }} <input type="file" name="start" accept="image/*" class="form-control" data-shrink="1200"></label>
              <label>{{ __('End') }} <input type="file" name="end" accept="image/*" class="form-control" data-shrink="1200"></label>
            </div>
            <div class="upload-progress" data-upload-progress hidden><span></span></div>
            <div class="upload-error" data-upload-error hidden></div>
          </div>
          <button class="btn btn-sm btn-success"><i class="fas fa-upload"></i> {{ __('Save') }}</button>
        </form>
        @endif
        <div class="pic-step">
          <span class="pic-num">4</span>
          <div class="pic-text">
            <div class="pic-label">{{ __('Video (optional): send this with the start picture and Gemini animates it') }}
              @if($exercise->has_video)<span class="badge badge-success"><i class="fas fa-video"></i> {{ __('Video added') }}</span>@endif
            </div>
            <p class="pic-prompt" dir="ltr" id="p3-{{ $exercise->id }}">{{ PicturePrompt::video($exercise) }}</p>
          </div>
          <button type="button" class="btn btn-sm btn-primary" data-copy-text="#p3-{{ $exercise->id }}" data-done="{{ __('Copied') }}"><i class="fas fa-copy"></i> {{ __('Copy') }}</button>
        </div>
        <form class="pic-step" method="POST" action="{{ route('admin.exercises.video', $exercise->id) }}" enctype="multipart/form-data" data-upload>
          @csrf
          <span class="pic-num">5</span>
          <div class="pic-text">
            <div class="pic-label">{{ $exercise->has_video ? __('Replace the video') : __('Upload the video') }}</div>
            {{-- a frame of the video becomes the cover when the exercise has no pictures yet (app.js: data-poster) --}}
            <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime" required class="form-control" data-poster>
            <input type="file" name="poster" accept="image/*" hidden>
            <div class="upload-progress" data-upload-progress hidden><span></span></div>
            <div class="upload-error" data-upload-error hidden></div>
          </div>
          <button class="btn btn-sm btn-success"><i class="fas fa-upload"></i> {{ __('Save') }}</button>
        </form>
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
      <h3>{{ $show === 'todo' ? __('All exercises have their pictures.') : __('No exercises found') }}</h3>
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
  .pic-alt{margin-top:6px}
  .pic-alt > summary{cursor:pointer;font-size:.8rem;color:var(--gray-700)}
  .pic-alt > .pic-prompt{margin:6px 0}
  .pic-pair{display:flex;gap:12px;flex-wrap:wrap}
  .pic-one{flex:1 1 160px;max-width:240px}
  .pic-one img,.pic-one .pic-none{width:100%;aspect-ratio:1;object-fit:contain;border-radius:10px;border:1px solid var(--gray-400);background:#fff}
  .pic-one-label{font-weight:700;font-size:.85rem}
  .pic-one > form{display:grid;gap:6px;justify-items:start}
  .pic-one-actions{display:flex;gap:6px;flex-wrap:wrap}
  @media (max-width:640px){
    .pic-step{grid-template-columns:26px minmax(0,1fr)}
    .pic-step > .btn{grid-column:2;justify-self:start}
  }
</style>

<script>
  // "Change": the picture is sent as soon as it is chosen
  document.addEventListener('change', function (e) {
    var input = e.target.closest && e.target.closest('input[data-autosubmit]');
    if (input && input.files && input.files.length && input.form) {
      if (input.form.requestSubmit) input.form.requestSubmit(); else input.form.submit();
    }
  });

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
