# Ameen Gym – Laravel Gym Management System

## Project Overview
Ameen Gym is a web-based system built with Laravel 9 to manage gym trainees and trainers through role-based access.  
The system allows trainers to create workout and nutrition plans in JSON format and enables trainees to view their schedules and submit plan requests.

## Actors & Roles
- Trainee: View personal dashboard, display workout plan, display nutrition plan, submit requests to trainers.
- Trainer: Receive trainee requests, create plans, manage weekly exercises and meals.
- Admin: Manage users and trainers, monitor system content.

## Algorithms & Technical Concepts Used
- Role-Based Authorization using Laravel Middleware.
- Storing plan details as JSON and rendering them as HTML tables.
- Conditional logic to display “View Plan / Request Plan”.
- QR Code generation for trainee identification (planned for local club use).

## Features Implemented
- Authentication & Login.
- Trainer Dashboard with notification button.
- Trainee Dashboard with personal info.
- Workout Plan Management stored in plan_details.
- Nutrition Plan Management stored in JSON.
- Request system: workout_requests and nutrition_requests.

## Installation & How to Run

### 1. Clone Repository
git clone https://github.com/Ameen041/Gym-Management-System.git

### 2. Install Dependencies
composer install

### 3. Database Setup
- Copy demo environment file:
copy .env.example .env

- Then run migrations:
php artisan migrate

### 4. Start Application
php artisan serve

## Notes
- The included .env credentials are demo/local settings only for academic evaluation.
- Trainers create weekly structures from Saturday to Friday stored as JSON.

## Contributing
This repository represents a graduation project submission for university review.

## License
MIT