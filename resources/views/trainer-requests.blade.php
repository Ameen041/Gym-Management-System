<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Requests | Trainer Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/trainer-requests.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>

    <div id="photoModal" class="photo-modal" onclick="closePhotoModal(event)">
        <button class="close-btn" type="button" onclick="hidePhotoModal()">Close ✕</button>
        <img id="photoModalImg" src="" alt="Body Photo">
    </div>

    <div class="trainer-requests-container">
        @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
        @endif

        <header class="requests-header">
            <h1><i class="fas fa-inbox"></i> Trainee Requests</h1>
        </header>

        <main class="requests-content">
            <!-- Workout Requests Section -->
            <section class="requests-section">
                <h2 class="section-title"><i class="fas fa-dumbbell"></i> Workout Plan Requests</h2>

                @if ($workoutRequests->isEmpty())
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No workout requests available</p>
                    </div>
                @else
                    <div class="requests-grid">
                        @foreach ($workoutRequests as $request)
                        <div class="request-card">
                            <div class="user-info">
                                <div class="avatar">
                                    @php
                                        $photo = $request->user->profile_photo
                                            ? asset('storage/' . $request->user->profile_photo)
                                            : null;
                                    @endphp

                                    @if($photo)
                                        <img src="{{ $photo }}" alt="User Photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                    @else
                                        <i class="fas fa-user"></i>
                                    @endif
                                </div>
                                <div>
                                    <h3>{{ $request->user->name }}</h3>
                                    <p>{{ $request->user->email }}</p>
                                </div>
                            </div>

<div class="request-details">
                                <div class="detail-item">
                                    <i class="fas fa-bullseye"></i>
                                    <span>Goal: {{ $request->goal }}</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-sticky-note"></i>
                                    <span>Notes: {{ $request->notes ?? 'None' }}</span>
                                </div>

                             
                                @php
                                    $photos = is_array($request->body_photos)
                                        ? $request->body_photos
                                        : ($request->body_photos ? json_decode($request->body_photos, true) : []);
                                    if (!is_array($photos)) $photos = [];
                                @endphp

                                @if(!empty($photos))
                                    <div class="detail-item">
                                        <i class="fas fa-images"></i>
                                        <span>
                                            Photos:
                                            @foreach($photos as $p)
                                                @php $url = asset('storage/' . $p); @endphp
                                                <img
                                                    src="{{ $url }}"
                                                    alt="photo"
                                                    onclick="showPhotoModal('{{ $url }}')"
                                                    style="width:38px;height:38px;object-fit:cover;border-radius:6px;vertical-align:middle;margin-right:6px;cursor:pointer;"
                                                >
                                            @endforeach
                                        </span>
                                    </div>
                                @endif

                                <div class="detail-item">
                                    <i class="fas fa-user-clock"></i>
                                    <span>Age: {{ $request->user->age ?? 'N/A' }}</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-weight"></i>
                                    <span>Weight: {{ $request->user->weight ?? 'N/A' }} kg</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-ruler-vertical"></i>
                                    <span>Height: {{ $request->user->height ?? 'N/A' }} cm</span>
                                </div>
                            </div>

                            <div class="request-actions">
                            <a href="{{ route('trainer.dashboard', [
    'user_id' => $request->user_id,
    'type' => 'workout',
    'request_id' => $request->id
]) }}" class="btn btn-accept">
    <i class="fas fa-plus"></i> Create Plan
</a>

    <form action="{{ route('trainer.rejectWorkout', $request->id) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-reject"
                onclick="return confirm('Are you sure you want to reject this request?')">
            <i class="fas fa-times"></i> Reject
        </button>
    </form>
</div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </section>


<!-- Nutrition Requests Section -->
            <section class="requests-section">
                <h2 class="section-title"><i class="fas fa-utensils"></i> Nutrition Plan Requests</h2>

                @if ($nutritionRequests->isEmpty())
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No nutrition requests available</p>
                    </div>
                @else
                    <div class="requests-grid">
                        @foreach ($nutritionRequests as $request)
                        <div class="request-card">
                            <div class="user-info">
                                <div class="avatar">
                                    @php
                                        $photo = $request->user->profile_photo
                                            ? asset('storage/' . $request->user->profile_photo)
                                            : null;
                                    @endphp

                                    @if($photo)
                                        <img src="{{ $photo }}" alt="User Photo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                    @else
                                        <i class="fas fa-user"></i>
                                    @endif
                                </div>
                                <div>
                                    <h3>{{ $request->user->name }}</h3>
                                    <p>{{ $request->user->email }}</p>
                                </div>
                            </div>

                            <div class="request-details">
                                <div class="detail-item">
                                    <i class="fas fa-bullseye"></i>
                                    <span>Goal: {{ $request->goal }}</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-sticky-note"></i>
                                    <span>Notes: {{ $request->notes ?? 'None' }}</span>
                                </div>

                              
                                @php
                                    $photos = is_array($request->body_photos)
                                        ? $request->body_photos
                                        : ($request->body_photos ? json_decode($request->body_photos, true) : []);
                                    if (!is_array($photos)) $photos = [];
                                @endphp

                                @if(!empty($photos))
                                    <div class="detail-item">
                                        <i class="fas fa-images"></i>
                                        <span>
                                            Photos:
                                            @foreach($photos as $p)
                                                @php $url = asset('storage/' . $p); @endphp
                                                <img
                                                    src="{{ $url }}"
                                                    alt="photo"
                                                    onclick="showPhotoModal('{{ $url }}')"
                                                    style="width:38px;height:38px;object-fit:cover;border-radius:6px;vertical-align:middle;margin-right:6px;cursor:pointer;"
                                                >
                                            @endforeach
                                        </span>
                                    </div>
                                @endif

<div class="detail-item">
                                    <i class="fas fa-user-clock"></i>
                                    <span>Age: {{ $request->user->age ?? 'N/A' }}</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-weight"></i>
                                    <span>Weight: {{ $request->user->weight ?? 'N/A' }} kg</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-ruler-vertical"></i>
                                    <span>Height: {{ $request->user->height ?? 'N/A' }} cm</span>
                                </div>
                            </div>

                            <div class="request-actions">
                            <a href="{{ route('trainer.dashboard', [
    'user_id' => $request->user_id,
    'type' => 'nutrition',
    'request_id' => $request->id
]) }}" class="btn btn-accept">
    <i class="fas fa-plus"></i> Create Plan
</a>

    <form action="{{ route('trainer.rejectNutrition', $request->id) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-reject"
                onclick="return confirm('Are you sure you want to reject this request?')">
            <i class="fas fa-times"></i> Reject
        </button>
    </form>
</div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>

        <footer class="requests-footer">
            <a href="{{ route('trainer.dashboard') }}" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </footer>
    </div>

    <script>
        function showPhotoModal(url) {
            const modal = document.getElementById('photoModal');
            const img   = document.getElementById('photoModalImg');
            img.src = url;
            modal.classList.add('show');
        }

        function hidePhotoModal() {
            const modal = document.getElementById('photoModal');
            const img   = document.getElementById('photoModalImg');
            img.src = '';
            modal.classList.remove('show');
        }

        function closePhotoModal(e) {
          
            if (e.target && e.target.id === 'photoModal') {
                hidePhotoModal();
            }
        }

      
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') hidePhotoModal();
        });
    </script>

    <script src="{{ asset('js/trainer-requests.js') }}"></script>
</body>
</html>