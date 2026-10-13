@extends('layouts.app')

@section('title', __('Ameen Gym') . ' | ' . __('Online coaching'))

@section('custom_css')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <style>
      .how-step .feature-icon{font-size:1.4rem;font-weight:800}
      .coach-cta{max-width:760px;margin:0 auto}
      .coach-cta .plan-features li{margin-bottom:10px}
      .trainer-info .price{display:block;color:var(--primary-color);font-weight:700;margin:6px 0}
      .rating-line{color:#f5b301;margin:4px 0}
      .rating-line small{color:var(--light-gray)}
    </style>
@endsection

@section('content')
    @php use App\Support\Money; @endphp
    <!-- Overlay -->
    <div class="overlay"></div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>{{ __('Your personal coach, online, at a price that suits you') }}</h2>
                <p class="subtitle">{{ __('Choose a certified coach, get a workout and nutrition plan made for you with exercise videos, and follow up on WhatsApp.') }}</p>
                <div class="cta-buttons">
                    <a href="{{ route('coaches.index') }}" class="btn btn-primary btn-lg">{{ __('Find your coach') }}</a>
                    <a href="#for-coaches" class="btn btn-outline btn-lg">{{ __('Are you a coach?') }}</a>
                </div>

                <div class="stats">
                    <div class="stat-item">
                        <span class="number">{{ $coachesCount }}</span>
                        <span class="label">{{ __('Coaches') }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">{{ $traineesCount }}</span>
                        <span class="label">{{ __('Trainees') }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">{{ $exercisesCount }}</span>
                        <span class="label">{{ __('Exercise videos') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="features" id="how">
        <div class="container">
            <h2 class="section-title">{{ __('How it works') }}</h2>
            <p class="section-subtitle">{{ __('Four simple steps') }}</p>
            <div class="features-grid">
                @foreach([
                    [__('Choose your coach'), __('Compare coaches by specialty, price and reviews.')],
                    [__('Tell us about you'), __('Answer a few questions about your goal, level and schedule.')],
                    [__('Pay with Sham Cash'), __('Transfer the amount and upload the receipt. We confirm it quickly.')],
                    [__('Get your plan'), __('Your coach sends your plan within :days days and follows up on WhatsApp.', ['days' => \App\Models\Subscription::PLAN_DUE_DAYS])],
                ] as $i => [$title, $text])
                    <div class="feature-card how-step">
                        <div class="feature-icon">{{ $i + 1 }}</div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Why us -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">{{ __('Why train with us?') }}</h2>
            <p class="section-subtitle">{{ __('Everything you need to train in your gym with a real plan') }}</p>
            <div class="features-grid">
                @foreach([
                    ['fa-user-check', __('Reviewed coaches'), __('We check every coach and their certificates before they appear.')],
                    ['fa-dumbbell', __('A plan made for you'), __('A weekly workout plan built around your goal and your days.')],
                    ['fa-utensils', __('Nutrition plan'), __('Meals with calories and macros, if your package includes it.')],
                    ['fa-play-circle', __('Exercise videos'), __('Every exercise in your plan links to a video that shows how to do it.')],
                    ['fab fa-whatsapp', __('WhatsApp follow-up'), __('Talk to your coach directly whenever you need.')],
                    ['fa-tags', __('Cheaper than private coaching'), __('Online coaching costs less than a private coach at the gym.')],
                ] as [$icon, $title, $text])
                    <div class="feature-card">
                        <div class="feature-icon"><i class="{{ str_starts_with($icon, 'fab') ? $icon : 'fas ' . $icon }}"></i></div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Coaches -->
    <section class="trainers" id="trainers">
        <div class="container">
            <h2 class="section-title">{{ __('Our coaches') }}</h2>
            <p class="section-subtitle">{{ __('Some of the coaches available now') }}</p>

            <div class="trainers-grid">
                @forelse($featuredTrainers as $trainer)
                    <div class="trainer-card">
                        <div class="trainer-image">
                            <img src="{{ $trainer->photo_url }}" alt="{{ $trainer->name }}">
                        </div>
                        <div class="trainer-info">
                            <h3>{{ $trainer->name }}</h3>
                            <span class="specialty">{{ implode(' · ', array_slice($trainer->coachProfile->specialty_labels, 0, 2)) ?: __('Online Trainer') }}</span>
                            <div class="rating-line">
                                @for($i = 1; $i <= 5; $i++)<i class="{{ $trainer->coachProfile->rating_avg >= $i - 0.25 ? 'fas' : 'far' }} fa-star"></i>@endfor
                                <small>({{ $trainer->coachProfile->rating_count }})</small>
                            </div>
                            <span class="price">{{ __('From') }} {{ Money::format($trainer->min_price) }} / {{ __('month') }}</span>
                            <a href="{{ route('coaches.show', $trainer) }}" class="btn btn-outline">{{ __('View Profile') }}</a>
                        </div>
                    </div>
                @empty
                    <p class="section-subtitle">{{ __('Trainers will appear here soon.') }}</p>
                @endforelse
            </div>
            @if($featuredTrainers->isNotEmpty())
                <div style="text-align:center;margin-top:30px">
                    <a href="{{ route('coaches.index') }}" class="btn btn-primary btn-lg">{{ __('See all coaches') }}</a>
                </div>
            @endif
        </div>
    </section>

    @if($latestReviews->isNotEmpty())
    <!-- Real reviews -->
    <section class="testimonials">
        <div class="container">
            <h2 class="section-title">{{ __('What trainees say') }}</h2>
            <p class="section-subtitle">{{ __('Real reviews from subscribers') }}</p>
            <div class="testimonials-slider">
                @foreach($latestReviews as $review)
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <div class="quote-icon"><i class="fas fa-quote-right"></i></div>
                            <p class="testimonial-text">"{{ $review->comment }}"</p>
                            <div class="client-info">
                                <div>
                                    <h4>{{ \Illuminate\Support\Str::of($review->trainee->name ?? '')->explode(' ')->first() }}</h4>
                                    <span>{{ str_repeat('★', $review->rating) }} · {{ __('Coach') }}: {{ $review->coach->name ?? '' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- For coaches -->
    <section class="nutrition" id="for-coaches">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">{{ __('Are you a coach?') }}</h2>
                <p class="section-subtitle">{{ __('Get clients online and build their programs in minutes') }}</p>
            </div>
            <div class="nutrition-plans">
                <div class="plan-card coach-cta">
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> {{ __('A ready exercise library with videos: add exercises to your programs with one click.') }}</li>
                        <li><i class="fas fa-check"></i> {{ __('Set your own price for your packages.') }}</li>
                        <li><i class="fas fa-check"></i> {{ __('We collect the payment and send you your earnings on Sham Cash.') }}</li>
                        <li><i class="fas fa-check"></i> {{ __('Your clients contact you on WhatsApp.') }}</li>
                    </ul>
                    @guest
                        <a href="#" class="btn btn-primary" onclick="showSignupModal(); document.getElementById('signupRole').value='trainer'; document.getElementById('signupRole').dispatchEvent(new Event('change')); return false;">{{ __('Join as a coach') }}</a>
                    @else
                        @if(auth()->user()->role === 'trainer')
                            <a href="{{ route('trainer.dashboard') }}" class="btn btn-primary">{{ __('Go to my dashboard') }}</a>
                        @endif
                    @endguest
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-info">
                    <h2 class="section-title">{{ __('Contact Us') }}</h2>
                    <p class="contact-text">{{ __('Have a question or a suggestion? Contact us and we\'ll be happy to help you') }}</p>
                    
                    <div class="contact-methods">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>0945004651</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>aminjalal@gmail.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>{{ __('Damascus Countryside, Syrian Arab Republic') }}</span>
                        </div>
                    </div>
                    
                </div>
                
                <form class="contact-form" method="POST" action="{{ route('contact.store') }}">
                    @csrf
                    @if(session('contact_success'))
                        <div class="auth-alert auth-alert-ok"><i class="fas fa-check-circle"></i> {{ session('contact_success') }}</div>
                    @endif
                    @if($errors->contact->any())
                        <div class="auth-alert"><i class="fas fa-exclamation-circle"></i> {{ $errors->contact->first() }}</div>
                    @endif
                    <div class="form-group">
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Full Name') }}" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Email') }}" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="{{ __('Phone Number') }}">
                    </div>
                    <div class="form-group">
                        <textarea name="message" placeholder="{{ __('Your Message') }}" rows="5" required>{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Send Message') }}</button>
                </form>
            </div>
        </div>
    </section>
    
@endsection

@section('custom_js')
<script src="{{ asset('js/home.js') }}"></script>
@endsection