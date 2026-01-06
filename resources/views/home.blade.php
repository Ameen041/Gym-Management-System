@extends('layouts.app')

@section('title','Ameen-Gym | Homepage')

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
                <h2>Create the best version of yourself!</h2>
                <p class="subtitle">Personal training and nutrition programs with the best trainers to achieve your goals</p>
                <div class="cta-buttons">
                    <a href="{{ url('/trainers') }}" class="btn btn-primary btn-lg">Browse Trainers</a>
                    <a href="#programs" class="btn btn-outline btn-lg">View Programs</a>
                </div>
                
                <div class="stats">
                    <div class="stat-item">
                        <span class="number">+500</span>
                        <span class="label">Trainees</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">+30</span>
                        <span class="label">Professional Trainers</span>
                    </div>
                    <div class="stat-item">
                        <span class="number">+95%</span>
                        <span class="label">Customer Satisfaction</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">Why Smart Bodybuilding Club?</h2>
            <p class="section-subtitle">We provide you with a unique and comprehensive sports experience</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Appointment Booking</h3>
                    <p>Book your session with the trainer at a time that suits you</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Progress Tracking</h3>
                    <p>Analyze your progress with detailed charts and reports</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3>Personal Training</h3>
                    <p>Training programs designed specifically for your goals</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Nutrition Plans</h3>
                    <p>Dietary schedules calculated with calories</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <h3>Online Sessions</h3>
                    <p>Train from anywhere with virtual sessions</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3>Certified Certificates</h3>
                    <p>Get accredited achievement certificates</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Programs Section -->
    <section class="programs" id="programs">
        <div class="container">
            <h2 class="section-title">Our Training Programs</h2>
            <p class="section-subtitle">Choose the program that suits your goals</p>
            
            <div class="programs-tabs">
                <button class="tab-btn active" data-tab="fitness">General Fitness</button>
                <button class="tab-btn" data-tab="bodybuilding">Bodybuilding</button>
                <button class="tab-btn" data-tab="weight-loss">Weight Loss</button>
                <button class="tab-btn" data-tab="rehabilitation">Rehabilitation</button>
            </div>
            
            <div class="programs-content">
                <div class="tab-content active" id="fitness">
                    <div class="program-card">
                        <div class="program-image">
                            <img src="{{ asset('images/fitness-program.jpg') }}" alt="General Fitness Program">
                        </div>
                        <div class="program-info">
                            <h3>General Fitness Program</h3>
                            <p>Comprehensive program to improve physical fitness and endurance</p>
                            <ul class="program-features">
                                <li><i class="fas fa-check"></i> 3 sessions per week</li>
                                <li><i class="fas fa-check"></i> Comprehensive exercises</li>
                                <li><i class="fas fa-check"></i> Weekly follow-up</li>
                            </ul>
                            <a href="#" class="btn btn-primary">Details</a>
                        </div>
                    </div>
                </div>
                
                <div class="tab-content" id="bodybuilding">
                </div>
            </div>
        </div>
    </section>

    <!-- Trainers Section -->
    <section class="trainers" id="trainers">
        <div class="container">
            <h2 class="section-title">Our Professional Trainers</h2>
            <p class="section-subtitle">Meet our team of certified trainers</p>
            
            <div class="trainers-grid">
                <div class="trainer-card">
                    <div class="trainer-image">
                        <img src="{{ asset('images/trainer1.jpg') }}" alt="Trainer Omar Al-Saour">
                        <div class="social-links">
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                    <div class="trainer-info">
                        <h3>Omar Al-Saour</h3>
                        <span class="specialty">Bodybuilding Expert</span>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span>(48)</span>
                        </div>
                        <a href="{{ url('/trainers/1') }}" class="btn btn-outline">View Profile</a>
                    </div>
                </div>
                
                <div class="trainer-card">
                    <div class="trainer-image">
                        <img src="{{ asset('images/trainer2.jpg') }}" alt="Trainer Adnan Sheikh Al-Ard">
                        <div class="social-links">
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                    <div class="trainer-info">
                        <h3>Adnan Sheikh Al-Ard</h3>
                        <span class="specialty">Physical Fitness Expert</span>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span>(36)</span>
                        </div>
                        <a href="{{ url('/trainers/2') }}" class="btn btn-outline">View Profile</a>
                    </div>
                </div>
                
                <div class="trainer-card">
                    <div class="trainer-image">
                        <img src="{{ asset('images/trainer3.jpg') }}" alt="Trainer Mohammed Al-Gharib">
                        <div class="social-links">
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                    <div class="trainer-info">
                        <h3>Mohammed Al-Gharib</h3>
                        <span class="specialty">Rehabilitation Expert</span>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                            <span>(29)</span>
                        </div>
                        <a href="{{ url('/trainers/3') }}" class="btn btn-outline">View Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Nutrition Section -->
    <section class="nutrition" id="nutrition">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Nutrition Programs</h2>
                <p class="section-subtitle">Diet plans designed according to your goals</p>
            </div>
            
            <div class="nutrition-plans">
                <div class="plan-card">
                    <div class="plan-header">
                        <h3>Basic Plan</h3>
                        <span class="price">299 SAR/month</span>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Weekly diet plan</li>
                        <li><i class="fas fa-check"></i> Weekly follow-up</li>
                        <li><i class="fas fa-check"></i> Adjustments based on progress</li>
                        <li><i class="fas fa-check"></i> Healthy recipes</li>
                    </ul>
                    <a href="#" class="btn btn-primary">Subscribe Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <h2 class="section-title">Customer Reviews</h2>
            <p class="section-subtitle">Inspiring success stories</p>
            
            <div class="testimonials-slider">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-right"></i>
                        </div>
                        <p class="testimonial-text">
                            "Thanks to the system and the dedicated trainer, I was able to lose 15 kilos in just 4 months. The diet program was easy and delicious!"
                        </p>
                        <div class="client-info">
                            <img src="{{ asset('images/client1.jpg') }}" alt="Sarah Mohammed">
                            <div>
                                <h4>Sarah Mohammed</h4>
                                <span>Lost 15 kilos</span>
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
                            "The trainer was very professional and helped me build muscle and gain strength in record time. I recommend this experience to everyone!"
                        </p>
                        <div class="client-info">
                            <img src="{{ asset('images/client2.jpg') }}" alt="Ahmed Khalid">
                            <div>
                                <h4>Ahmed Khalid</h4>
                                <span>Gained 8 kilos of muscle</span>
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
                            "After suffering from back pain, the rehabilitation program helped me regain my movement and daily activity. Thanks to the training team!"
                        </p>
                        <div class="client-info">
                            <img src="{{ asset('images/client3.jpg') }}" alt="Nora Abdullah">
                            <div>
                                <h4>Nora Abdullah</h4>
                                <span>Full back improvement</span>
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
            <h2 class="section-title">Membership Packages</h2>
            <p class="section-subtitle">Choose the package that suits you</p>
            
            <div class="pricing-plans">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Basic Package</h3>
                        <div class="price">
                            <span>499</span>
                            <span>SAR/month</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> 8 monthly sessions</li>
                        <li><i class="fas fa-check"></i> Training program</li>
                        <li><i class="fas fa-check"></i> Weekly follow-up</li>
                        <li><i class="fas fa-check"></i> Gym access</li>
                    </ul>
                    <a href="#" class="btn btn-outline">Choose Package</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-info">
                    <h2 class="section-title">Contact Us</h2>
                    <p class="contact-text">Have a question or want to book a session? Contact us and we'll be happy to help you</p>
                    
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
                            <span>Damascus Countryside, Syrian Arab Republic</span>
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
                        <input type="text" placeholder="Full Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" placeholder="Phone Number">
                    </div>
                    <div class="form-group">
                        <textarea placeholder="Your Message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </section>
    
@endsection

@section('custom_js')
<script src="{{ asset('js/home.js') }}"></script>
@endsection