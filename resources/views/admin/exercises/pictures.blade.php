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
    <li>{{ __('Then copy the second text and send it: it is a full description of the end position, in the same style. If Gemini sends the first picture again, send it in a new chat.') }}</li>
    <li>{{ __('Download both pictures and upload them under the exercise: first the start, then the end.') }}</li>
    <li>{{ __('For a video: send the start picture to Gemini with the video text (step 4), download the video and upload it (step 5). It plays by itself on the exercise page, without sound.') }}</li>
  </ol>
  <p class="muted small">{{ __('A picture with a mistake (an extra arm, a bent bar, a wrong position)? Ask Gemini again in a new chat. Pictures are saved on the server; keep a copy of them on your computer too.') }}</p>
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
            <div class="pic-label">{{ __('Second text: the end position') }}</div>
            <p class="pic-prompt" dir="ltr" id="p2-{{ $exercise->id }}">{{ PicturePrompt::end($exercise) }}</p>
          </div>
          <button type="button" class="btn btn-sm btn-primary" data-copy-text="#p2-{{ $exercise->id }}" data-done="{{ __('Copied') }}"><i class="fas fa-copy"></i> {{ __('Copy') }}</button>
        </div>
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
        <div class="pic-step">
          <span class="pic-num">4</span>
          <div class="pic-text">
            <div class="pic-label">{{ __('Video (optional): send this with the start picture and Gemini animates it') }}
              @if($exercise->has_video)<span class="badge badge-success"><i class="fas fa-video"></i> {{ __('Video added') }}</span>@endif
            </div>
            <p class="pic-prompt" dir="ltr" id="p3-{{ $exercise->id }}">{{ PicturePrompt::video($exercise) }}</p>
            {{-- a square picture loses its top and bottom in the wide video: this adds white space on the sides, in the browser --}}
            <label class="btn btn-sm btn-secondary pic-widen">
              <i class="fas fa-expand"></i> {{ __('Square picture? Make it wide for the video') }}
              <input type="file" accept="image/*" hidden data-widen="{{ $exercise->id }}">
            </label>
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
  .pic-widen{margin-top:6px;cursor:pointer}
  @media (max-width:640px){
    .pic-step{grid-template-columns:26px minmax(0,1fr)}
    .pic-step > .btn{grid-column:2;justify-self:start}
  }
</style>

<script>
  // a picture for the video: the same picture in a 16:9 frame with white space around it, saved to the device
  document.addEventListener('change', function (e) {
    var input = e.target.closest && e.target.closest('input[data-widen]');
    if (!input || !input.files || !input.files[0]) return;
    var url = URL.createObjectURL(input.files[0]), img = new Image();
    img.onload = function () {
      var h = img.naturalHeight / 0.8, w = h * 16 / 9;
      if (w < img.naturalWidth / 0.9) { w = img.naturalWidth / 0.9; h = w * 9 / 16; }
      var c = document.createElement('canvas');
      c.width = Math.round(w); c.height = Math.round(h);
      var ctx = c.getContext('2d');
      // the added space takes the colour of the picture's own background (its top-left corner), so no frame shows
      ctx.drawImage(img, 0, 0, 1, 1, 0, 0, 1, 1);
      var px = ctx.getImageData(0, 0, 1, 1).data;
      ctx.fillStyle = 'rgb(' + px[0] + ',' + px[1] + ',' + px[2] + ')';
      ctx.fillRect(0, 0, c.width, c.height);
      var x = Math.round((c.width - img.naturalWidth) / 2), y = Math.round((c.height - img.naturalHeight) / 2);
      var iw = img.naturalWidth, ih = img.naturalHeight;
      ctx.drawImage(img, x, y);
      // soften the picture's edges into the added space, so its frame doesn't show
      var bg = 'rgba(' + px[0] + ',' + px[1] + ',' + px[2] + ',', f = Math.round(Math.min(iw, ih) * 0.08);
      [[x, y, x + f, y, x, y, f, ih], [x + iw, y, x + iw - f, y, x + iw - f, y, f, ih],
       [x, y, x, y + f, x, y, iw, f], [x, y + ih, x, y + ih - f, x, y + ih - f, iw, f]].forEach(function (g) {
        var grad = ctx.createLinearGradient(g[0], g[1], g[2], g[3]);
        grad.addColorStop(0, bg + '1)'); grad.addColorStop(1, bg + '0)');
        ctx.fillStyle = grad; ctx.fillRect(g[4], g[5], g[6], g[7]);
      });
      URL.revokeObjectURL(url);
      c.toBlob(function (blob) {
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'exercise-' + input.getAttribute('data-widen') + '-wide.png';
        document.body.appendChild(a); a.click(); a.remove();
        input.value = '';
      }, 'image/png');
    };
    img.src = url;
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
