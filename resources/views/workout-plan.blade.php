<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Plan | Ameen Gym</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/workout-plan.css') }}">
</head>
<body>
    <div class="dashboard-container">
        <div class="plan-card">
            <div class="plan-header">
                <h1><i class="fas fa-dumbbell"></i> Workout Plan</h1>
                <div class="plan-actions">
                    <a href="{{ url('/trainee-dashboard') }}" class="action-btn back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button id="print-btn" class="action-btn">
                        <i class="fas fa-print"></i> Print Plan
                    </button>
                </div>
            </div>
   
            <div class="plan-content">
                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif

                @isset($plan)
                    <div class="plan-details">
                        <div class="detail-section">
                            <h2><i class="fas fa-heading"></i> Plan Title</h2>
                            <p class="plan-title">{{ $plan->title }}</p>
                        </div>

                        @php
                            $details = json_decode($plan->plan_details, true);
                        @endphp

                        @if(is_array($details))
                            <div class="table-container">
                                <table class="workout-table">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-calendar-day"></i> Day</th>
                                            <th><i class="fas fa-dumbbell"></i> Muscle</th>
                                            <th><i class="fas fa-running"></i> Exercise</th>
                                            <th><i class="fas fa-layer-group"></i> Sets</th>
                                            <th><i class="fas fa-redo"></i> Reps</th>
                                        </tr>
                                    </thead>
                                    <tbody>
@foreach($details as $day => $exercises)

    <tr class="day-row">
        <td colspan="5">
            <i class="fas fa-calendar-day"></i>
            <b>{{ $day }}</b>
            <span class="day-count">({{ count($exercises) }} exercises)</span>
        </td>
    </tr>


    @foreach($exercises as $exercise)
        <tr>
            <td class="day-empty"></td>
            <td>{{ $exercise['muscle'] ?? '' }}</td>
            <td>{{ $exercise['exercise'] ?? '' }}</td>
            <td>{{ $exercise['sets'] ?? '' }}</td>
            <td>{{ $exercise['reps'] ?? '' }}</td>
        </tr>
    @endforeach

@endforeach
</tbody>
                                </table>
                            </div>
                        @else
                            <div class="no-data">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>No valid workout plan data available.</p>
                            </div>
                        @endif
                    </div>
                    @elseif(isset($trainerId))
                    <form action="{{ route('trainee.requestWorkout') }}" method="POST" class="plan-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="trainer_id" value="{{ $trainerId }}">

                        <div class="form-group">
                            <label for="goal"><i class="fas fa-bullseye"></i> Your Goal:</label>
                            <input type="text" name="goal" id="goal" required>
                        </div>

                        <div class="form-group">
                            <label for="notes"><i class="fas fa-sticky-note"></i> Additional Notes:</label>
                            <textarea name="notes" id="notes" rows="5"></textarea>
                        </div>

                        <div class="form-group">
    <label><i class="fas fa-camera"></i> Body Photos (optional):</label>
    <input type="file" name="body_photos[]" accept="image/*" multiple>
</div>

                        <button type="submit" class="btn send-btn">
                            <i class="fas fa-paper-plane"></i> Send Request
                        </button>
                    </form>
                @else
                    <div class="no-plan">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>No workout plan available</h3>
                        <p>You can request a workout plan from your trainer</p>
                        <a href="{{ route('trainers', ['type' => 'workout']) }}" class="btn request-btn">
                            <i class="fas fa-user-tie"></i> Choose a Trainer & Request Plan
                        </a>
                    </div>
                @endisset
            </div>
        </div>
    </div>

    <script src="{{ asset('js/workout-plan.js') }}"></script>
</body>
</html>