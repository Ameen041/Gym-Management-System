# Ameen Gym – Gym Management System (Laravel)

## 🏋️ Project Overview

Ameen Gym is an online coaching marketplace for Syria, built with Laravel.
Coaches publish their profile and packages, trainees choose a coach and pay with
**Sham Cash**, and the platform keeps a commission. Coaches build workout and
nutrition plans from a ready exercise library with videos, and follow up with
their clients on WhatsApp.

---

### 🛒 How the marketplace works
1. A coach signs up, completes the profile (photo, bio, specialties, city, WhatsApp, certificates) and adds packages with their own prices.
2. The admin reviews the profile and certificates, then approves it; the coach appears on `/coaches`.
3. A trainee filters coaches (specialty, city, gender, price), picks a package and answers the intake questions.
4. The trainee transfers the amount with Sham Cash and uploads the receipt.
5. The admin checks the transfer and confirms it: the subscription starts (30/60/90 days) and the commission is calculated (default 15%, set in Admin → Settings).
6. The coach sees the new client, builds the plan (exercises picked from the library get a video link) and both sides get a WhatsApp button.
7. The trainee reviews the coach; the admin records payouts to coaches from Admin → Coach payouts.

**Coach payouts:** all payments arrive in the platform's Sham Cash account. A client's money becomes *ready to pay*
once the coach has sent the plan and the hold period has passed (default 7 days after the payment is confirmed,
set in Admin → Settings). The suggested routine is a weekly payout: send each coach the "Ready to pay" amount on
Sham Cash, then record it in Admin → Coach payouts (the coach gets a notification).

**Notifications:** a bell in every dashboard shows what happened: new payment receipts and coaches waiting for review
(admin), new clients, reviews and payouts (coach), confirmed / rejected payments, new plans and renewal reminders (trainee).

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
- Manage the exercise library (add exercises, video links or uploaded videos; paste a YouTube link straight from the list)
- Manage the food library used by the nutrition calculator (`/admin/foods`)

---

### 🎥 Exercise Library
- Weight-training exercises grouped by muscle (chest, back, shoulders, arms, legs, abs...)
- Each exercise has a video explanation (YouTube / Vimeo link or uploaded MP4), step-by-step instructions and tips
- Search and filter by muscle group, available to trainees and trainers at `/exercises`
- Managed by the admin at `/admin/exercises`; about 140 exercises (including cardio) are seeded automatically,
  with English and Arabic steps and tips written for this project
- Videos: the admin pastes a YouTube link per exercise (embedding is allowed by YouTube); until then the page offers a
  YouTube search in Arabic and English. Exercise videos/images from other apps or sites are copyrighted and are not copied.

### 🥗 Nutrition Calculator
- Coaches build each meal from foods (eggs, chicken, rice, bulgur, hummus, pita bread...) and enter the amount in grams
  or in servings ("3 × 1 large egg", "1 large loaf")
- Calories, protein, carbs and fat are calculated per food, per meal, per day and as a weekly average
- Starter library of 141 foods with values per 100 g from the **USDA National Nutrient Database for Standard Reference,
  Release 28** (published as FoodData Central "SR Legacy"), a public-domain reference: `database/seeders/data/foods.php`
- Local products (labneh, akkawi cheese, bakery bread...) are added by the admin from the package nutrition label,
  and a coach can also type a food that is not in the list with its own values

---

### 🌐 Arabic / English
- One-click language switch (العربية / English) on every page: home, dashboards, plans, exercises and the admin panel
- Arabic mode switches the whole layout to right-to-left (`public/css/rtl.css`) with the Tajawal font
- The choice is remembered (session + cookie)
- Interface texts live in `lang/ar.json`; validation messages in `lang/ar/`
- Exercises have Arabic name, equipment, description, steps and tips (empty Arabic fields fall back to English)

---

### 🎨 Design
- One design system for every page (`public/css/app.css`): green brand colour, day and night themes
  (follows the phone setting, with a switch in the header), Cairo + IBM Plex Sans Arabic fonts
- Phone-first: bottom tab bar in the app, sidebar on desktop, bottom sheets for pickers
- Fonts and icons are served from `public/vendor` (no Google / CDN needed, works offline in the app)
- Coaches pick exercises and foods from searchable lists (Arabic spelling variants are understood)
  filtered by muscle / equipment or food category, one day per tab, with "copy to other days"

### 📱 Installable App (PWA)
- The system can be installed on Android, iPhone and desktop like a normal app ("Install App" button)
- Web app manifest, app icons and a service worker (`public/sw.js`) with an offline page
- Requires HTTPS in production (installation is disabled on plain HTTP)

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

## ▶️ Quick Start on Windows (one click)

Double-click **`start-local.bat`** in the project folder. It finds PHP (from your PATH or Laragon),
installs dependencies on the first run, creates a local SQLite database with the demo accounts and
exercises, starts the server and opens the browser at `http://127.0.0.1:8000`.

- No MySQL or Laragon services need to be running.
- Local settings are stored in `.env.local` and `database/database.sqlite` (your `.env` is not touched).
- Keep the window open while using the system; close it to stop the server.
- Requires PHP 8 (Laragon's PHP works) and, only for the first run without a `vendor` folder, Composer.

To get a newer version later, double-click **`update.bat`**: it pulls the latest `master` from GitHub
(using git from your PATH, Laragon or GitHub Desktop) and then starts the system. Your local database
and `.env` files are kept.

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

## 🔑 Demo Accounts (local testing only)

The seeders create these accounts **only when `APP_ENV` is not `production`**:

| Role | Email | Password |
|---|---|---|
| Admin | admin@demo.com | Admin@12345 |
| Coach | trainer@demo.com | Trainer@12345 |
| Coach | coach.lina@demo.com / coach.omar@demo.com | Trainer@12345 |
| Trainee | trainee@demo.com | Trainee@12345 |

On a real server set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env` before the first deploy.
The admin account is created once and never overwritten, so a changed password is kept.
If the server already has `admin@demo.com`, change its password from Admin → Users.

---

## 📊 Plan Data Structure

Workout and Nutrition plans are stored as structured JSON inside:

plan_details  

Example Workout Structure:

Saturday  
- Chest | Bench Press | 4 | 10  
- Chest | Incline Press | 3 | 12  

Example Nutrition Structure (each meal lists its foods; totals are calculated):

Saturday  
- Meal 1 (Breakfast): 3 × boiled egg (150 g) + 1 pita bread (60 g) → 398 kcal | 24.4 g protein | 35.1 g carbs | 16.6 g fat  

This structure allows flexible weekly scheduling and clean UI rendering.

---

## 🔄 Future Improvements

- Online payment gateway integration
- Multi-gym SaaS architecture
- Mobile App API support
- Progress analytics & charts
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