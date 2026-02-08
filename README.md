# Ameen Gym – Gym Management System (Laravel)

## 🏋️ Project Overview

Ameen Gym is a complete Gym Management System built with Laravel.
It provides a structured environment for managing trainees, trainers, workout plans, nutrition plans, and administrative control through a clean role-based architecture.

The system is designed for real gym environments and can be extended into a SaaS-ready product.

---

## 🚀 Main Features

### 🔐 Authentication System
- Secure login & registration
- Role-based access control (Admin / Trainer / Trainee)
- Middleware protection
- Active / Inactive user control

---

### 👨‍🏫 Trainer Features
- Trainer dashboard
- View trainee requests (Workout & Nutrition)
- Create weekly workout plans (Saturday → Friday)
- Create weekly nutrition plans
- Store plan data in structured JSON format
- Approve / Reject trainee requests
- Use ready-made templates

---

### 🧑‍💪 Trainee Features
- Personal dashboard
- View assigned workout plan
- View assigned nutrition plan
- Submit workout requests
- Submit nutrition requests
- Upload optional body progress photos

---

### 🛠 Admin Features (Pro Version)
- Manage users (activate / deactivate)
- Manage trainers & trainees
- Create & manage reusable workout & nutrition templates
- Monitor workout & nutrition requests
- Subscription monitoring
- Payment dashboard (extendable)

---

## 🧠 Technical Architecture

- Laravel MVC Architecture
- Role-Based Authorization using Middleware
- JSON-based weekly plan storage
- Dynamic rendering of plans as HTML tables
- Clean Blade templating structure
- Modular controllers (Admin / Trainer / Trainee)

---

## 📦 System Requirements

- PHP 8+
- Composer
- MySQL
- Laravel 9+
- Apache or Nginx

---

## ⚙ Installation Guide

### 1️⃣ Clone Repository

git clone https://github.com/Ameen041/Gym-Management-System.git  
cd Gym-Management-System  

---

### 2️⃣ Install Dependencies

composer install  

---

### 3️⃣ Setup Environment

Copy environment file:

cp .env.example .env  

Generate application key:

php artisan key:generate  

---

### 4️⃣ Configure Database

Edit .env file and update:

DB_DATABASE=your_database  
DB_USERNAME=your_username  
DB_PASSWORD=your_password  

Then run:

php artisan migrate  

---

### 5️⃣ Run Application

php artisan serve  

Open in browser:

http://127.0.0.1:8000  

---

## 🔑 Demo Accounts (Ready to Test)

Use the following demo accounts to test the system roles after seeding the database:

### ✅ Admin
- Email: admin@demo.com  
- Password: 12345678  

### ✅ Trainer
- Email: trainer@demo.com  
- Password: 12345678  

### ✅ Trainee
- Email: trainee@demo.com  
- Password: 12345678  

> If you don’t have these accounts yet, run the demo seeder (if included in the project):
> 
>
> php artisan db:seed
> 
> 
> Or (if you created a specific seeder like DemoUsersSeeder):
> 
>
> php artisan db:seed --class=DemoUsersSeeder
> 
---

## 📊 Plan Data Structure

Workout and Nutrition plans are stored as structured JSON inside:

plan_details  

Example Workout Structure:

Saturday  
- Chest | Bench Press | 4 | 10  
- Chest | Incline Press | 3 | 12  

Example Nutrition Structure:

Saturday  
- Meal 1 | 450 kcal | 20g Protein | 65g Carbs | 12g Fat  

This structure allows flexible weekly scheduling and clean UI rendering.

---

## 🔄 Future Improvements

- Online payment gateway integration
- Multi-gym SaaS architecture
- Mobile App API support
- Progress analytics & charts
- Notification system
- QR-based gym entry tracking

---

## 🎯 Target Usage

- Small to Medium Gyms
- Personal Trainers
- Fitness Studios
- Subscription-based Fitness Centers

---

## 📜 License

MIT License

---

## 👨‍💻 Author

Developed by Eng. Ameen  
Designed to evolve into a commercial gym management SaaS solution.