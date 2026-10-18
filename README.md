# همّة · Himma – online coaching marketplace (Laravel)

[![Tests](https://github.com/Ameen041/Gym-Management-System/actions/workflows/tests.yml/badge.svg)](https://github.com/Ameen041/Gym-Management-System/actions/workflows/tests.yml)

## 🏋️ Project Overview

Himma (همّة) is an online coaching marketplace for Syria, built with Laravel.
Coaches publish their profile and packages, trainees choose a coach and pay with
**Sham Cash**, and the platform keeps a commission. Coaches build workout and
nutrition plans from a ready exercise library with videos, and follow up with
their clients in the built-in chat (phone numbers stay private).

---

### 🛒 How the marketplace works
1. A coach signs up, completes the profile (photo, bio, specialties, city, phone for the admin, certificates) and adds packages with their own prices.
2. The admin reviews the profile and certificates, then approves it; the coach appears on `/coaches`.
3. A trainee filters coaches (specialty, city, gender, price), picks a package and answers the intake questions.
4. The trainee transfers the amount with Sham Cash and uploads the receipt.
5. The admin checks the transfer and confirms it: the subscription starts (30/60/90 days) and the commission is calculated (default 15%, set in Admin → Settings).
6. The coach sees the new client, builds the plan (exercises picked from the library get a video link) and both sides talk in the chat.
7. The trainee reviews the coach; the admin records payouts to coaches from Admin → Coach payouts.

**Coach payouts:** all payments arrive in the platform's Sham Cash account. A client's money becomes *ready to pay*
once the coach has sent the plan and the hold period has passed (default 7 days after the payment is confirmed,
set in Admin → Settings). The suggested routine is a weekly payout: send each coach the "Ready to pay" amount on
Sham Cash, then record it in Admin → Coach payouts (the coach gets a notification).

**Notifications:** a bell in every dashboard shows what happened: new payment receipts and coaches waiting for review
(admin), new clients, reviews and payouts (coach), confirmed / rejected payments, new plans and renewal reminders (trainee).
Each user can also turn on **phone notifications**: every notification and every chat message then reaches the phone.

---

## 🚀 Main Features

### 🔐 Accounts & safety
- Roles: admin, coach and trainee, each with its own area; accounts can be suspended
- Login is slowed down after wrong passwords, security headers on every page
- Payment receipts and body / progress photos are private (trainee, their coach and the admin only)

---

### 👨‍🏫 Coach
- Dashboard: active clients, plans due, check-ins to answer, renewals, earnings
- Public profile and packages with their own prices; private Sham Cash account for payouts
- Weekly workout and nutrition plans (Saturday → Friday) from the exercise and food libraries or admin templates
- Nutrition plans start from daily targets (calories, protein, carbs, fat); each day shows a bar per value
  that turns green once it is within range (±5% calories, ±10% macros), and the client sees the daily target
- Weekly check-ins with a weight chart; reply to each one, remind late clients in the chat
- Chat with each client: unread badges, "seen" ticks, ready messages for renewal and check-in reminders
- Renewals: send the last plan again in one tap

---

### 🧑‍💪 Trainee
- Find a coach (specialty, city, gender, price), answer the intake questions, pay with Sham Cash
- Workout and nutrition plans with exercise videos, today's day first, printable
- Weekly check-in: weight, waist, training days, diet, energy, sleep, notes and private photos
- One-tap renewal; the plan carries over until the coach sends the new one
- Chat with the coach, open while the subscription runs and a week after; report a problem in one tap

---

### 🛠 Admin
- Approve coaches, confirm or reject Sham Cash receipts, record weekly payouts to coaches
- Users, exercises (video links or uploads), foods, workout & nutrition templates, contact messages
- Settings: commission, payout hold period, the platform's Sham Cash account
- System page: launch checklist and database backups

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
- Arabic mode switches the whole layout to right-to-left (`public/css/rtl.css`)
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
- Phone notifications (Web Push) on Android and computers, and on iPhone once the app is added to the Home Screen (iOS 16.4+).
  No outside service: the site makes its own key on first use (kept in the settings table, so it moves with backups)

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

- PHP 8.2+ (extensions: pdo_sqlite or pdo_mysql / pdo_pgsql, mbstring, gd, fileinfo)
- Composer
- A database: SQLite (local), MySQL or PostgreSQL (server)
- Apache or Nginx with the document root on `public/` (the Dockerfile sets this up)

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

Copy the settings template and fill it in (see "Going Live" below):

cp .env.production.example .env  

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

## ✅ Automated Tests

Every pull request runs `.github/workflows/tests.yml` on GitHub: it installs the packages, compiles
every page template, checks the JavaScript and runs the feature tests in `tests/` (on an in-memory
SQLite database). A red cross on the pull request means something broke; open "Details" to see what.
The tests also check that every text has its Arabic translation in `lang/ar.json`.

Run them on your own computer with `vendor/bin/phpunit` (after `composer install`).

---

## 🔑 Demo Accounts (local testing only)

The seeders create these accounts **only when `APP_ENV` is not `production`**:

| Role | Email | Password |
|---|---|---|
| Admin | admin@demo.com | Admin@12345 |
| Coach | trainer@demo.com | Trainer@12345 |
| Coach | coach.lina@demo.com / coach.omar@demo.com | Trainer@12345 |
| Trainee | trainee@demo.com | Trainee@12345 |
| Trainee with 5 weeks of check-ins | rami@demo.com | Trainee@12345 |

On a real server set `ADMIN_EMAIL` and `ADMIN_PASSWORD` (10+ characters) before the first deploy:
in production there is no default admin password, so no admin is created without them.
The admin account is created once and never overwritten, so a changed password is kept.

---

## 🚀 Going Live (checklist)

1. **Settings:** copy `.env.production.example` into the host's environment settings and fill it in
   (`APP_KEY`, `APP_URL` with https, database, `ADMIN_EMAIL` / `ADMIN_PASSWORD`, mail).
2. **Persistent storage:** uploaded files (coach photos, payment receipts, body photos) and backups
   live in `storage/app`. On the server this folder must be on a persistent disk
   (Render: add a Disk mounted at `/var/www/html/storage/app`; a VPS keeps it by default),
   otherwise every redeploy deletes them.
3. **Start:** `start.sh` (used by the Dockerfile) runs migrations and seeders, links `public/storage`
   and starts the scheduler for the nightly database backup.
4. **Check:** log in as admin and open **System**. Every line should be green.
   Download a backup every week and keep it off the server.

Private files: payment receipts and body photos are stored outside `public/` and only shown,
through the app, to the trainee, their coach (photos only) and the admin.

Backups: `php artisan app:backup` writes a gzipped backup of all data to `storage/app/backups`
(the last 14 are kept; one is made every night). `php artisan app:restore <file>` replaces all data
with a backup after saving the current data first. The format is the same for SQLite, MySQL and
PostgreSQL, so a backup can also move data between them.

Weekly check-ins: a trainee sends one a week (weight, waist, training days, diet, energy, sleep, notes,
private photos); the coach sees a weight chart and replies. `php artisan app:check-in-reminders`
runs every morning from the scheduler: it reminds trainees on the due day and tells the coach after
3 late days.

Renewals: a trainee renews in one tap (same coach and package, answers carried over) and pays as
before; once confirmed, the new period starts when the current one ends. Until the coach sends the
new plan the trainee keeps the last one, and the coach can send it again as it is or start from it.
`php artisan app:subscription-reminders` runs every morning: renewal reminders (trainee and coach),
unfinished payments, plans due tomorrow or late (coach, and the admin when late) and receipts waiting
for review for more than a day. Each reminder is sent once.

Coach payouts: each coach adds their Sham Cash account (name, number, optional QR) at the bottom of
My Profile; only the admin sees it. Admin → Coach payouts shows it next to the amount ready to pay,
with a copy button. Changing an account needs the coach's password, the admins are told, and the
account is flagged for a week so it can be confirmed before money is sent. Each payout keeps the
account it was sent to.

Chat instead of WhatsApp: coach and client talk in the app and never see each other's number (only
the admin does, for support). Messages with a phone or account number (also in Arabic digits or written
in words), a link, an email, a username, or the name of another app or payment service are not sent;
each try is kept, and the admin is told when someone tries 3 times in 30 days (Admin → Chats).
Profiles, packages, check-ins and reviews are checked the same way. Chats are private: the admin can
read one only after the coach or the client reports it. The filter stops the easy ways around the
platform, not every possible one, so the terms should also forbid it.

Password resets: if email is not set up, Admin → Users → **Reset link** makes a one-time link
(valid 60 minutes) to send to the user on WhatsApp.

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

- Online card payments once they work reliably in Syria
- Photos in the chat (they could carry a number, so they need a check first)
- Coach templates of their own, saved from a client's plan

---

## 🎯 Target Usage

- Independent personal trainers who coach clients online
- Trainees in Syria (and Syrians abroad) who want a coach, a plan and weekly follow-up

---

## 📜 License

MIT License

---

## 👨‍💻 Author

Developed by Eng. Ameen  
Designed to evolve into a commercial gym management SaaS solution.