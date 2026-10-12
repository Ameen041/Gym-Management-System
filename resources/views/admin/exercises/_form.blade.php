@if($errors->any())
  <div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    {{ __('Please fix the following errors:') }}
    <ul style="margin-top: 8px; margin-inline-start: 20px;">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
  <div class="form-group">
    <label class="form-label" for="muscle_group">{{ __('Target muscle') }}</label>
    <select id="muscle_group" name="muscle_group" class="form-control" required>
      <option value="">{{ __('-- Select muscle --') }}</option>
      @foreach(\App\Models\Exercise::MUSCLE_GROUPS as $key => $group)
        <option value="{{ $key }}" {{ old('muscle_group', $exercise->muscle_group) === $key ? 'selected' : '' }}>
          {{ $group['label'] }} — {{ $group['label_ar'] }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="form-group">
    <label class="form-label" for="difficulty">{{ __('Level') }}</label>
    <select id="difficulty" name="difficulty" class="form-control" required>
      @foreach(\App\Models\Exercise::DIFFICULTIES as $key => $label)
        <option value="{{ $key }}" {{ old('difficulty', $exercise->difficulty ?? 'beginner') === $key ? 'selected' : '' }}>{{ __($label) }}</option>
      @endforeach
    </select>
  </div>
</div>

<div class="lang-columns">
  {{-- English --}}
  <div class="card lang-card" dir="ltr" lang="en">
    <h3 style="margin-bottom:12px;">English</h3>

    <div class="form-group">
      <label class="form-label" for="name">{{ __('Exercise name') }}</label>
      <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $exercise->name) }}" required placeholder="Barbell Bench Press">
    </div>

    <div class="form-group">
      <label class="form-label" for="equipment">{{ __('Equipment') }}</label>
      <input type="text" id="equipment" name="equipment" class="form-control" value="{{ old('equipment', $exercise->equipment) }}" placeholder="Barbell, Dumbbells, Cable, Machine...">
    </div>

    <div class="form-group">
      <label class="form-label" for="description">{{ __('Short description') }}</label>
      <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $exercise->description) }}</textarea>
    </div>

    <div class="form-group">
      <label class="form-label" for="instructions">{{ __('How to perform (one step per line)') }}</label>
      <textarea id="instructions" name="instructions" class="form-control" rows="6">{{ old('instructions', $exercise->instructions) }}</textarea>
    </div>

    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label" for="tips">{{ __('Tips & common mistakes (one per line)') }}</label>
      <textarea id="tips" name="tips" class="form-control" rows="4">{{ old('tips', $exercise->tips) }}</textarea>
    </div>
  </div>

  {{-- Arabic --}}
  <div class="card lang-card" dir="rtl" lang="ar">
    <h3 style="margin-bottom:12px;">العربية <small style="font-weight:400;color:var(--gray-700);">({{ __('optional') }})</small></h3>

    <div class="form-group">
      <label class="form-label" for="name_ar">{{ __('Exercise name') }}</label>
      <input type="text" id="name_ar" name="name_ar" class="form-control" value="{{ old('name_ar', $exercise->name_ar) }}" placeholder="ضغط البنش بالبار">
    </div>

    <div class="form-group">
      <label class="form-label" for="equipment_ar">{{ __('Equipment') }}</label>
      <input type="text" id="equipment_ar" name="equipment_ar" class="form-control" value="{{ old('equipment_ar', $exercise->equipment_ar) }}" placeholder="بار، دمبلز، كابل، جهاز...">
    </div>

    <div class="form-group">
      <label class="form-label" for="description_ar">{{ __('Short description') }}</label>
      <textarea id="description_ar" name="description_ar" class="form-control" rows="2">{{ old('description_ar', $exercise->description_ar) }}</textarea>
    </div>

    <div class="form-group">
      <label class="form-label" for="instructions_ar">{{ __('How to perform (one step per line)') }}</label>
      <textarea id="instructions_ar" name="instructions_ar" class="form-control" rows="6">{{ old('instructions_ar', $exercise->instructions_ar) }}</textarea>
    </div>

    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label" for="tips_ar">{{ __('Tips & common mistakes (one per line)') }}</label>
      <textarea id="tips_ar" name="tips_ar" class="form-control" rows="4">{{ old('tips_ar', $exercise->tips_ar) }}</textarea>
    </div>
  </div>
</div>
<p style="color: var(--gray-700); font-size: 0.875rem; margin: -4px 0 16px;">
  <i class="fas fa-info-circle"></i> {{ __('Arabic fields are shown when the app is in Arabic; empty fields fall back to English.') }}
</p>

<div class="card" style="background: var(--gray-300); box-shadow:none; margin-bottom:16px;">
  <h3 style="margin-bottom:12px;"><i class="fas fa-video"></i> {{ __('Video explanation') }}</h3>

  <div class="form-group">
    <label class="form-label" for="video_url">{{ __('Video link (YouTube / Vimeo / direct .mp4)') }}</label>
    <input type="url" id="video_url" name="video_url" class="form-control" dir="ltr" value="{{ old('video_url', $exercise->video_url) }}" placeholder="https://www.youtube.com/watch?v=...">
    <small style="color: var(--gray-700);">{{ __('Recommended: a YouTube link keeps the app fast and doesn\'t use server storage.') }}</small>
  </div>

  <div class="form-group">
    <label class="form-label" for="video_file">{{ __('Or upload a video file (MP4/WebM/MOV, max 100MB)') }}</label>
    <input type="file" id="video_file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg,video/quicktime">
    @if($exercise->video_path)
      <div style="margin-top:8px;">
        <video src="{{ asset($exercise->video_path) }}" controls preload="metadata" style="max-width:320px;border-radius:8px;"></video>
        <label style="display:block;margin-top:6px;">
          <input type="checkbox" name="remove_video" value="1"> {{ __('Remove uploaded video') }}
        </label>
      </div>
    @endif
    <small style="color: var(--gray-700);">{{ __('If both are provided, the video link is shown first.') }}</small>
  </div>

  <div class="form-group" style="margin-bottom:0;">
    <label class="form-label" for="image_file">{{ __('Thumbnail image (optional)') }}</label>
    <input type="file" id="image_file" name="image_file" class="form-control" accept="image/*">
    @if($exercise->image_path)
      <div style="margin-top:8px;">
        <img src="{{ asset($exercise->image_path) }}" alt="" style="max-width:160px;border-radius:8px;">
        <label style="display:block;margin-top:6px;">
          <input type="checkbox" name="remove_image" value="1"> {{ __('Remove image') }}
        </label>
      </div>
    @endif
  </div>
</div>

<div class="form-group">
  <label>
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $exercise->is_active) ? 'checked' : '' }}>
    {{ __('Visible to trainees & trainers') }}
  </label>
</div>

<style>
  .lang-columns{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:16px}
  .lang-card{box-shadow:none;border:1px solid var(--gray-400);margin:0}
</style>
