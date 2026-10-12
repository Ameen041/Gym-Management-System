@if($errors->any())
  <div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    Please fix the following errors:
    <ul style="margin-top: 8px; margin-left: 20px;">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
  <div class="form-group">
    <label class="form-label" for="name">Exercise name</label>
    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $exercise->name) }}" required placeholder="e.g. Barbell Bench Press">
  </div>

  <div class="form-group">
    <label class="form-label" for="muscle_group">Target muscle</label>
    <select id="muscle_group" name="muscle_group" class="form-control" required>
      <option value="">-- Select muscle --</option>
      @foreach(\App\Models\Exercise::MUSCLE_GROUPS as $key => $group)
        <option value="{{ $key }}" {{ old('muscle_group', $exercise->muscle_group) === $key ? 'selected' : '' }}>
          {{ $group['label'] }} — {{ $group['label_ar'] }}
        </option>
      @endforeach
    </select>
  </div>

  <div class="form-group">
    <label class="form-label" for="equipment">Equipment</label>
    <input type="text" id="equipment" name="equipment" class="form-control" value="{{ old('equipment', $exercise->equipment) }}" placeholder="Barbell, Dumbbells, Cable, Machine...">
  </div>

  <div class="form-group">
    <label class="form-label" for="difficulty">Level</label>
    <select id="difficulty" name="difficulty" class="form-control" required>
      @foreach(\App\Models\Exercise::DIFFICULTIES as $key => $label)
        <option value="{{ $key }}" {{ old('difficulty', $exercise->difficulty ?? 'beginner') === $key ? 'selected' : '' }}>{{ $label }}</option>
      @endforeach
    </select>
  </div>
</div>

<div class="form-group">
  <label class="form-label" for="description">Short description</label>
  <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $exercise->description) }}</textarea>
</div>

<div class="form-group">
  <label class="form-label" for="instructions">How to perform (one step per line)</label>
  <textarea id="instructions" name="instructions" class="form-control" rows="6" placeholder="Lie flat on the bench...&#10;Lower the bar to mid-chest...&#10;Press the bar back up...">{{ old('instructions', $exercise->instructions) }}</textarea>
</div>

<div class="form-group">
  <label class="form-label" for="tips">Tips &amp; common mistakes (one per line)</label>
  <textarea id="tips" name="tips" class="form-control" rows="4">{{ old('tips', $exercise->tips) }}</textarea>
</div>

<div class="card" style="background: var(--gray-300); box-shadow:none; margin-bottom:16px;">
  <h3 style="margin-bottom:12px;"><i class="fas fa-video"></i> Video explanation</h3>

  <div class="form-group">
    <label class="form-label" for="video_url">Video link (YouTube / Vimeo / direct .mp4)</label>
    <input type="url" id="video_url" name="video_url" class="form-control" value="{{ old('video_url', $exercise->video_url) }}" placeholder="https://www.youtube.com/watch?v=...">
    <small style="color: var(--gray-700);">Recommended: a YouTube link keeps the app fast and doesn't use server storage.</small>
  </div>

  <div class="form-group">
    <label class="form-label" for="video_file">Or upload a video file (MP4/WebM/MOV, max 100MB)</label>
    <input type="file" id="video_file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg,video/quicktime">
    @if($exercise->video_path)
      <div style="margin-top:8px;">
        <video src="{{ asset($exercise->video_path) }}" controls preload="metadata" style="max-width:320px;border-radius:8px;"></video>
        <label style="display:block;margin-top:6px;">
          <input type="checkbox" name="remove_video" value="1"> Remove uploaded video
        </label>
      </div>
    @endif
    <small style="color: var(--gray-700);">If both are provided, the video link is shown first.</small>
  </div>

  <div class="form-group" style="margin-bottom:0;">
    <label class="form-label" for="image_file">Thumbnail image (optional)</label>
    <input type="file" id="image_file" name="image_file" class="form-control" accept="image/*">
    @if($exercise->image_path)
      <div style="margin-top:8px;">
        <img src="{{ asset($exercise->image_path) }}" alt="" style="max-width:160px;border-radius:8px;">
        <label style="display:block;margin-top:6px;">
          <input type="checkbox" name="remove_image" value="1"> Remove image
        </label>
      </div>
    @endif
  </div>
</div>

<div class="form-group">
  <label>
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $exercise->is_active) ? 'checked' : '' }}>
    Visible to trainees &amp; trainers
  </label>
</div>
