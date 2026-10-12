@extends('layouts.app')

@section('title', __('Gym-System | Homepage'))

@section('custom_css')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endsection

@section('content')
    <!-- Overlay -->
    <div class="overlay"></div>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>{{ __('Create the best version of yourself!') }}</h2>
                <p class="subtitle">{{ __('Personal training and nutrition programs with the best trainers to achieve your goals') }}</p>
                <div class="cta-buttons">
                    <a href="{{ url('/trainers') }}" class="btn btn-primary btn-lg">{{ __('Browse Trainers') }}</a>
                    <a href="#programs" class="btn btn-outline btn-lg">{{ __('View Programs') }}</a>
                </div>
                
                <div class="stats">
                    <div class="stat-item">
                    <span class="number">+{{ $traineesCount }}</span>
                    <span class="label">{{ __('Trainees') }}</span>
                    </div>
                    <div class="stat-item">
                    <span class="number">+{{ $trainersCount }}</span>
                    <span class="label">{{ __('Professional Trainers') }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">+95%</span>
                        <span class="label">{{ __('Customer Satisfaction') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">{{ __('Why Our Gym System?') }}</h2>
            <p class="section-subtitle">{{ __('We provide you with a unique and comprehensive sports experience') }}</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>{{ __('Appointment Booking') }}</h3>
                    <p>{{ __('Book your session with the trainer at a time that suits you') }}</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>{{ __('Progress Tracking') }}</h3>
                    <p>{{ __('Analyze your progress with detailed charts and reports') }}</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3>{{ __('Personal Training') }}</h3>
                    <p>{{ __('Training programs designed specifically for your goals') }}</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>{{ __('Nutrition Plans') }}</h3>
                    <p>{{ __('Dietary schedules calculated with calories') }}</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <h3>{{ __('Online Sessions') }}</h3>
                    <p>{{ __('Train from anywhere with virtual sessions') }}</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3>{{ __('Certified Certificates') }}</h3>
                    <p>{{ __('Get accredited achievement certificates') }}</p>
                </div>
            </div>
        </div>
    </section>

   <!-- Programs Section -->
<section class="programs" id="programs">
  <div class="container">
    <h2 class="section-title">{{ __('Latest Programs') }}</h2>
    <p class="section-subtitle">{{ __('Recently added training and nutrition programs') }}</p>

    <div class="programs-content">
      <div class="programs-grid">

        {{-- WORKOUT CARD --}}
        <div class="program-card">
          <div class="program-info">
            <h3>{{ __('Workout Program') }}</h3>

            @if($latestWorkout)
              <h4 class="program-subtitle">{{ $latestWorkout->title }}</h4>
              <p class="program-desc">
                {{ $latestWorkout->description ?: __('Weekly workout plan template ready to use.') }}
              </p>

              @php
                $lines = preg_split("/\r\n|\n|\r/", trim($latestWorkout->plan_details ?? ''));
                $lines = array_values(array_filter(array_map('trim',$lines)));
                $preview = array_slice($lines, 0, 6);

                $pretty = [];
                foreach($preview as $line){
                  $p = array_map('trim', explode('|', $line));
                  if(count($p) < 5) continue;
                  [$day,$muscle,$exercise,$sets,$reps] = $p;
                  $pretty[] = __($day)." • {$exercise} — {$sets} × {$reps}";
                }
              @endphp

              @if(count($pretty))
                <div class="program-features">
                  <p class="muted small">{{ __('Quick preview:') }}</p>
                  <ul class="preview-list">
                    @foreach($pretty as $item)
                      <li>{{ $item }}</li>
                    @endforeach
                  </ul>

                  @if(count($lines) > 6)
                    <p class="muted tiny">{{ __('+:count more items…', ['count' => count($lines) - 6]) }}</p>
                  @endif
                </div>
              @endif

            @else
              <p class="muted">{{ __('No workout templates yet.') }}</p>
            @endif
          </div>
        </div>

        {{-- NUTRITION CARD --}}
        <div class="program-card">
          <div class="program-info">
            <h3>{{ __('Nutrition Program') }}</h3>

            @if($latestNutrition)
              <h4 class="program-subtitle">{{ $latestNutrition->title }}</h4>
              <p class="program-desc">
                {{ $latestNutrition->description ?: __('Weekly nutrition plan template with calories & macros.') }}
              </p>

              @php
                $lines = preg_split("/\r\n|\n|\r/", trim($latestNutrition->plan_details ?? ''));
                $lines = array_values(array_filter(array_map('trim',$lines)));
                $preview = array_slice($lines, 0, 6);

                $pretty = [];
                foreach($preview as $line){
                  $p = array_map('trim', explode('|', $line));
                  if(count($p) < 7) continue;
                  [$day,$meal,$desc,$cal,$pro,$carb,$fat] = $p;
                  $pretty[] = __($day)." • ".__('Meal :number', ['number' => $meal]).": {$desc} — {$cal} ".__('kcal')." (P{$pro}/C{$carb}/F{$fat})";
                }
              @endphp

              @if(count($pretty))
                <div class="program-features">
                  <p class="muted small">{{ __('Quick preview:') }}</p>
                  <ul class="preview-list">
                    @foreach($pretty as $item)
                      <li>{{ $item }}</li>
                    @endforeach
                  </ul>

                  @if(count($lines) > 6)
                    <p class="muted tiny">{{ __('+:count more items…', ['count' => count($lines) - 6]) }}</p>
                  @endif
                </div>
              @endif

            @else
              <p class="muted">{{ __('No nutrition templates yet.') }}</p>
            @endif
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

{{-- Styles (put in home.css if you prefer) --}}
<style>
  .programs-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:18px;
  }

  .program-subtitle{
    margin-top:10px;
    font-size:16px;
  }

  .program-desc{
    margin-top:6px;
    opacity:.9;
    line-height:1.6;
  }

  .preview-list{
    margin:8px 0 0;
    padding-inline-start:18px;
    line-height:1.75;
  }

  .tiny{ font-size:12px; margin-top:10px; }

  @media (max-width: 900px){
    .programs-grid{ grid-template-columns:1fr; }
  }
</style>

    <!-- Trainers Section -->
    <section class="trainers" id="trainers">
        <div class="container">
            <h2 class="section-title">{{ __('Our Professional Trainers') }}</h2>
            <p class="section-subtitle">{{ __('Meet our team of certified trainers') }}</p>
            
            <div class="trainers-grid">
                <div class="trainer-card">
                    <div class="trainer-image">
                        <img src="{{ asset('images/trainer1.jpg') }}" alt="{{ __('Trainer Omar Al-Saour') }}">
                        <div class="social-links">
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                    <div class="trainer-info">
                        <h3>{{ __('Omar Al-Saour') }}</h3>
                        <span class="specialty">{{ __('Bodybuilding Expert') }}</span>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span>(48)</span>
                        </div>
                        <a href="{{ url('/trainers/1') }}" class="btn btn-outline">{{ __('View Profile') }}</a>
                    </div>
                </div>
                
                <div class="trainer-card">
                    <div class="trainer-image">
                        <img src="{{ asset('images/trainer2.jpg') }}" alt="{{ __('Trainer Adnan Sheikh Al-Ard') }}">
                        <div class="social-links">
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                    <div class="trainer-info">
                        <h3>{{ __('Adnan Sheikh Al-Ard') }}</h3>
                        <span class="specialty">{{ __('Physical Fitness Expert') }}</span>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span>(36)</span>
                        </div>
                        <a href="{{ url('/trainers/2') }}" class="btn btn-outline">{{ __('View Profile') }}</a>
                    </div>
                </div>
                
                <div class="trainer-card">
                    <div class="trainer-image">
                        <img src="{{ asset('images/trainer3.jpg') }}" alt="{{ __('Trainer Mohammed Al-Gharib') }}">
                        <div class="social-links">
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                    <div class="trainer-info">
                        <h3>{{ __('Mohammed Al-Gharib') }}</h3>
                        <span class="specialty">{{ __('Rehabilitation Expert') }}</span>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                            <span>(29)</span>
                        </div>
                        <a href="{{ url('/trainers/3') }}" class="btn btn-outline">{{ __('View Profile') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Nutrition Section -->
    <section class="nutrition" id="nutrition">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">{{ __('Nutrition Programs') }}</h2>
                <p class="section-subtitle">{{ __('Diet plans designed according to your goals') }}</p>
            </div>
            
            <div class="nutrition-plans">
                <div class="plan-card">
                    <div class="plan-header">
                        <h3>{{ __('Basic Plan') }}</h3>
                        <span class="price">{{ __('299 SAR/month') }}</span>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> {{ __('Weekly diet plan') }}</li>
                        <li><i class="fas fa-check"></i> {{ __('Weekly follow-up') }}</li>
                        <li><i class="fas fa-check"></i> {{ __('Adjustments based on progress') }}</li>
                        <li><i class="fas fa-check"></i> {{ __('Healthy recipes') }}</li>
                    </ul>
                    <a href="#" class="btn btn-primary">{{ __('Subscribe Now') }}</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <h2 class="section-title">{{ __('Customer Reviews') }}</h2>
            <p class="section-subtitle">{{ __('Inspiring success stories') }}</p>
            
            <div class="testimonials-slider">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-right"></i>
                        </div>
                        <p class="testimonial-text">
                            {{ __('"Thanks to the system and the dedicated trainer, I was able to lose 15 kilos in just 4 months. The diet program was easy and delicious!"') }}
                        </p>
                        <div class="client-info">
                            <img src="{{ asset('images/client1.jpg') }}" alt="{{ __('Sarah Mohammed') }}">
                            <div>
                                <h4>{{ __('Sarah Mohammed') }}</h4>
                                <span>{{ __('Lost 15 kilos') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-right"></i>
                        </div>
                        <p class="testimonial-text">
                            {{ __('"The trainer was very professional and helped me build muscle and gain strength in record time. I recommend this experience to everyone!"') }}
                        </p>
                        <div class="client-info">
                            <img src="{{ asset('images/client2.jpg') }}" alt="{{ __('Ahmed Khalid') }}">
                            <div>
                                <h4>{{ __('Ahmed Khalid') }}</h4>
                                <span>{{ __('Gained 8 kilos of muscle') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-right"></i>
                        </div>
                        <p class="testimonial-text">
                            {{ __('"After suffering from back pain, the rehabilitation program helped me regain my movement and daily activity. Thanks to the training team!"') }}
                        </p>
                        <div class="client-info">
                            <img src="{{ asset('images/client3.jpg') }}" alt="{{ __('Nora Abdullah') }}">
                            <div>
                                <h4>{{ __('Nora Abdullah') }}</h4>
                                <span>{{ __('Full back improvement') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing" id="pricing">
        <div class="container">
            <h2 class="section-title">{{ __('Membership Packages') }}</h2>
            <p class="section-subtitle">{{ __('Choose the package that suits you') }}</p>
            
            <div class="pricing-plans">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>{{ __('Basic Package') }}</h3>
                        <div class="price">
                            <span>499</span>
                            <span>{{ __('SAR/month') }}</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> {{ __('8 monthly sessions') }}</li>
                        <li><i class="fas fa-check"></i> {{ __('Training program') }}</li>
                        <li><i class="fas fa-check"></i> {{ __('Weekly follow-up') }}</li>
                        <li><i class="fas fa-check"></i> {{ __('Gym access') }}</li>
                    </ul>
                    <a href="#" class="btn btn-outline">{{ __('Choose Package') }}</a>
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
                    <p class="contact-text">{{ __('Have a question or want to book a session? Contact us and we\'ll be happy to help you') }}</p>
                    
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
                    
                    <div class="social-media">
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-snapchat"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <form class="contact-form">
                    <div class="form-group">
                        <input type="text" placeholder="{{ __('Full Name') }}" required>
                    </div>
                    <div class="form-group">
                        <input type="email" placeholder="{{ __('Email') }}" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" placeholder="{{ __('Phone Number') }}">
                    </div>
                    <div class="form-group">
                        <textarea placeholder="{{ __('Your Message') }}" rows="5" required></textarea>
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