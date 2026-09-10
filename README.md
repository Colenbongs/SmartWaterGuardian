# 💧 Smart Water Guardian — Complete Setup & Run Guide

**Real-time Water Monitoring & Management System for South Africa**

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Firebase](https://img.shields.io/badge/Firebase-9.22-FFCA28?logo=firebase&logoColor=black)](https://firebase.google.com)
[![ESP32](https://img.shields.io/badge/ESP32-Firmware%20v2.0-E7352C?logo=espressif&logoColor=white)](https://www.espressif.com/)
[![PHPUnit](https://img.shields.io/badge/Tests-53%2F55%20Passing-brightgreen)](test-results.html)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Sprint](https://img.shields.io/badge/Sprint-5--6%20MVP-purple)](#)

> **University of Johannesburg** · Software Projects 2026 · Code Crew Innovators

---

## 📖 Table of Contents

1. [Overview](#-overview)
2. [The Problem We Solve](#-the-problem-we-solve)
3. [Our Solution](#-our-solution)
4. [Key Features](#-key-features)
5. [System Architecture](#-system-architecture)
6. [Technology Stack](#-technology-stack)
7. [Project Structure](#-project-structure)
8. [**🚀 How to Run the Project**](#-how-to-run-the-project) ← **START HERE**
9. [Configuration Reference](#-configuration-reference)
10. [Database Setup](#-database-setup)
11. [Firebase Setup](#-firebase-setup)
12. [ESP32 Hardware Setup](#-esp32-hardware-setup)
13. [User Roles & Permissions](#-user-roles--permissions)
14. [API Reference](#-api-reference)
15. [Billing System](#-billing-system)
16. [Email Notifications](#-email-notifications)
17. [Testing](#-testing)
18. [Security & POPIA Compliance](#-security--popia-compliance)
19. [Bug Register](#-bug-register)
20. [Troubleshooting](#-troubleshooting)
21. [Roadmap](#-roadmap)
22. [Team](#-team)
23. [Contributing](#-contributing)
24. [License](#-license)

---

## 🎯 Overview

**Smart Water Guardian** is a full-stack IoT water management platform built for South African municipalities and consumers. It combines an ESP32-based hardware meter, a Firebase-backed real-time data pipeline, a PHP/MySQL backend, and a responsive web dashboard into a single integrated system.

The platform enables real-time water consumption monitoring, automated leak detection, transparent tiered billing, and proactive consumer engagement — all in one cohesive product.

### Project Status

| Aspect | Status |
|--------|--------|
| **Sprint** | 5-6 MVP |
| **Release Tag** | `Sprint-5-6-MVP` |
| **Test Pass Rate** | 96.4% (53 / 55) |
| **Core Journey** | ✅ Complete |
| **Deployment** | Local (XAMPP / PHP built-in server) |

---

## 🌍 The Problem We Solve

South Africa faces a critical water crisis:

- **46%** of municipal water is lost to leaks and inefficiencies
- Aging infrastructure makes undetected leaks commonplace
- Consumers lack real-time visibility into their water consumption
- Manual billing systems are slow, inaccurate, and opaque
- Municipalities struggle to engage consumers in conservation

Traditional water meters provide **one reading per month**, offering no insight into consumption patterns, leaks, or opportunities for conservation.

---

## 💡 Our Solution

Smart Water Guardian replaces the monthly-reading model with **continuous, real-time monitoring**:

- **IoT Smart Meters** — ESP32 + YF-S201 flow sensors transmit readings every 5 seconds
- **Cloud Analytics** — Firebase Realtime Database stores and streams data instantly
- **Web Dashboard** — Consumers see flow rate, volume, and pressure live
- **Instant Alerts** — Leak detection and threshold breaches trigger notifications
- **Tiered Billing** — Accurate, transparent invoices computed from real consumption
- **Admin Controls** — Municipalities approve users, manage devices, and monitor the fleet

---

## ✨ Key Features

### Core MVP Features

| # | Feature | Description | Status |
|---|---------|-------------|--------|
| 1 | **User Registration & Approval** | Multi-role signup with admin approval workflow | ✅ |
| 2 | **Authentication & Access Control** | Firebase Auth + PHP sessions + role enforcement | ✅ |
| 3 | **Real-time Dashboard** | Live flow rate, volume, and pressure from ESP32 | ✅ |
| 4 | **Usage History & Analytics** | Interactive charts with 7–90 day trends | ✅ |
| 5 | **Alert & Threshold Management** | Custom thresholds with automated notifications | ✅ |
| 6 | **Smart Billing & Payments** | Tiered pricing, VAT, invoice generation | ✅ |
| 7 | **Admin Panel** | User approval, device management, system monitoring | ✅ |
| 8 | **Property Management** | Multi-property support per user | ✅ |
| 9 | **Email Notifications** | Registration, approval, alerts via SMTP | ✅ |
| 10 | **PDF Report Generation** | Downloadable usage reports | ✅ |
| 11 | **Review & Rating System** | Community feedback with admin moderation | ✅ |
| 12 | **Profile & 2FA Setup** | Avatar upload, preferences, two-factor auth | ✅ |
| 13 | **Dark / Light Mode** | Persistent theme per user | ✅ |
| 14 | **ESP32 Hardware Integration** | Live meter data via Firebase | ✅ |

### Complete End-to-End User Journey

```
REGISTER → APPROVE → MONITOR → ALERT → BILL
```

---

## 🏗 System Architecture

```
┌──────────────────────────────────────────────┐
│              CLIENT LAYER                    │
│   Consumer / Municipal Admin / System Admin  │
│              (Web Browser)                   │
└────────────────────┬─────────────────────────┘
                     │ HTTPS / WebSocket
┌────────────────────▼─────────────────────────┐
│            APPLICATION LAYER                 │
│         PHP Backend (Apache)                 │
│  auth │ users │ devices │ alerts │ billing   │
└──────┬───────────────────────────┬───────────┘
       │ MySQL                     │ Firebase SDK
┌──────▼──────────┐        ┌───────▼──────────┐
│  DATA LAYER     │        │  REALTIME LAYER  │
│     MySQL       │        │    Firebase      │
└─────────────────┘        └───────┬──────────┘
                                   │ WiFi
                          ┌────────▼─────────┐
                          │  HARDWARE LAYER  │
                          │  ESP32 + YF-S201 │
                          └──────────────────┘
```

### Data Flow

1. Water flows through the YF-S201 sensor → 450 pulses per liter
2. ESP32 counts pulses via hardware interrupt (GPIO34)
3. Firmware computes flow rate + cumulative volume every 500ms
4. Firebase write every 5 seconds → `meters/{METER_ID}/lastReading`
5. Web dashboard subscribes to Firebase → updates live
6. Backend syncs to MySQL for persistence, billing, reporting
7. Alert engine compares readings to user thresholds → notifications

---

## 🛠 Technology Stack

| Layer | Technologies |
|-------|-------------|
| **Frontend** | HTML5, CSS3, JavaScript (ES6+), Chart.js 4.4, jsPDF 2.5, Font Awesome 6.4, Google Fonts |
| **Backend** | PHP 8.0+, Apache 2.4+, PHPMailer 6.8, Composer 2.x |
| **Database** | MySQL 5.7+/MariaDB 10.3+, Firebase Realtime Database |
| **Auth** | Firebase Authentication, PHP Sessions |
| **Hardware** | ESP32 Dev Module, YF-S201 Flow Sensor, MPX5010DP (optional) |
| **Testing** | PHPUnit 9.5 |
| **Tools** | Git, Arduino IDE 2.x, VS Code |

---

## 📁 Project Structure

```
SmartWaterGuardian/
├── api/                          # Backend API endpoints
│   ├── auth.php
│   ├── alerts.php
│   ├── approve-user.php
│   ├── delete-user.php
│   ├── devices.php
│   ├── generate-pdf-report.php
│   ├── log-activity.php
│   ├── send-notification.php
│   ├── sync.php
│   ├── usage.php
│   └── users.php
├── config/                       # Configuration
│   ├── database.php
│   └── firebase-service-account.json  # (gitignored)
├── pages/                        # Frontend pages
│   ├── dashboard.php
│   ├── history.php
│   ├── alerts.php
│   ├── thresholds.php
│   ├── billing.php
│   ├── properties.php
│   ├── reviews.php
│   ├── profile.php
│   ├── admin.php
│   ├── login.php
│   ├── register.php
│   ├── setup-2fa.php
│   └── verify-email.php
├── tests/                        # Automated test suite
│   ├── AdminTest.php
│   ├── BillingTest.php
│   ├── LoginTest.php
│   ├── UserRegistrationTest.php
│   ├── run-tests.php
│   ├── generate-test-results.php
│   └── bootstrap.php
├── database/                     # SQL artifacts
│   ├── schema.sql
│   └── seed.sql
├── assets/                       # Static assets
├── vendor/                       # Composer dependencies (gitignored)
├── .env.example                  # Environment template
├── .gitignore
├── composer.json
├── phpunit.xml
├── index.php                     # Landing page
├── test-results.html
└── README.md
```

---

# 🚀 How to Run the Project

This is the **complete, step-by-step guide** to get Smart Water Guardian running on your machine.

> **Estimated setup time:** 15–30 minutes (excluding ESP32 hardware)

---

## 📋 Prerequisites Checklist

Before you begin, make sure you have:

| Requirement | Version | Download |
|-------------|---------|----------|
| ✅ **PHP** | 8.0+ | [php.net](https://www.php.net/downloads) |
| ✅ **MySQL** | 5.7+ | [mysql.com](https://dev.mysql.com/downloads/) or via XAMPP |
| ✅ **Composer** | 2.x | [getcomposer.org](https://getcomposer.org/download/) |
| ✅ **Git** | 2.x | [git-scm.com](https://git-scm.com/downloads) |
| ✅ **Web Server** | Apache 2.4+ | [XAMPP](https://www.apachefriends.org/) (recommended) |
| ✅ **Firebase Account** | Free tier | [firebase.google.com](https://firebase.google.com) |
| ✅ **Gmail Account** | Any | For SMTP notifications |

### Verify Your Environment

```bash
php -v                    # Should show 8.0 or higher
mysql --version           # Should show 5.7 or higher
composer --version        # Should show 2.x
git --version             # Any modern version
```

Check PHP extensions (all required):

```bash
php -m | grep -E 'mysqli|curl|openssl|mbstring|json'
```

Expected output:
```
curl
json
mbstring
mysqli
openssl
```

If any are missing, enable them in `php.ini`:
```ini
extension=mysqli
extension=curl
extension=openssl
extension=mbstring
extension=json
```

---

## 🅰️ Option A — Run with XAMPP (Windows / macOS / Linux) — **RECOMMENDED**

This is the easiest and most reliable method.

### Step 1 — Install XAMPP

1. Download XAMPP from [apachefriends.org](https://www.apachefriends.org/)
2. Run the installer with default options
3. Choose a location:
   - Windows: `C:\xampp`
   - macOS: `/Applications/XAMPP`
   - Linux: `/opt/lampp`

### Step 2 — Start Apache and MySQL

1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**

Both should show green **Running** status.

> ⚠️ **Port conflict?** If Apache fails to start, something else is using port 80 (e.g., IIS, Skype). Change Apache's port to **8080**:
> - XAMPP → Config → Apache (httpd.conf)
> - Change `Listen 80` → `Listen 8080`
> - Change `ServerName localhost:80` → `ServerName localhost:8080`
> - Restart Apache
> - Your URL becomes `http://localhost:8080/SmartWaterGuardian`

### Step 3 — Clone the Project into `htdocs`

**Windows (PowerShell):**
```powershell
cd C:\xampp\htdocs
git clone https://github.com/your-org/SmartWaterGuardian.git
cd SmartWaterGuardian
```

**macOS / Linux:**
```bash
cd /Applications/XAMPP/htdocs    # macOS
# OR
cd /opt/lampp/htdocs             # Linux

git clone https://github.com/your-org/SmartWaterGuardian.git
cd SmartWaterGuardian
```

### Step 4 — Install PHP Dependencies

Open a terminal in the project folder:

```bash
composer install
```

**Expected output:**
```
Loading composer repositories with package information
Installing dependencies from lock file
  - Installing phpmailer/phpmailer (v6.9.1)
  - Installing phpunit/phpunit (v9.6.0)
Generating optimized autoload files
```

> 💡 **Composer not found?** Install it globally:
> - Windows: [getcomposer.org/download](https://getcomposer.org/download/) → run `Composer-Setup.exe`
> - macOS: `brew install composer`
> - Linux: `sudo apt install composer`

### Step 5 — Create the Database

**Option A — Command line:**

```bash
mysql -u root -p
```

Then inside MySQL:
```sql
CREATE DATABASE smart_water_guardian
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
EXIT;
```

**Option B — phpMyAdmin (easier):**

1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click **New** on the left sidebar
3. Database name: `smart_water_guardian`
4. Collation: `utf8mb4_unicode_ci`
5. Click **Create**

### Step 6 — Import the Schema

**Command line:**
```bash
mysql -u root -p smart_water_guardian < database/schema.sql
```

**phpMyAdmin:**
1. Select `smart_water_guardian` from left sidebar
2. Click **Import** tab
3. Choose file: `database/schema.sql`
4. Click **Go**

**Verify tables were created:**
```bash
mysql -u root -p -e "USE smart_water_guardian; SHOW TABLES;"
```

Expected output (10 tables):
```
+--------------------------------+
| Tables_in_smart_water_guardian |
+--------------------------------+
| alerts                         |
| alert_thresholds               |
| audit_logs                     |
| billing                        |
| devices                        |
| messages                       |
| properties                     |
| reviews                        |
| users                          |
| water_readings                 |
+--------------------------------+
```

### Step 7 — Configure the Environment

```bash
# Copy the template
cp .env.example .env

# Windows: use copy instead
copy .env.example .env
```

Open `.env` in a text editor and fill in **every required value**.

**Minimal working `.env` for local dev:**

```env
# APPLICATION
APP_ENV=development
APP_NAME="Smart Water Guardian"
APP_URL=http://localhost/SmartWaterGuardian
APP_TIMEZONE=Africa/Johannesburg

# DATABASE (default XAMPP credentials)
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=smart_water_guardian
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4

# FIREBASE (paste from Firebase Console → Project Settings → Web App)
FIREBASE_API_KEY=AIzaSy...
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_DATABASE_URL=https://your-project-default-rtdb.firebaseio.com
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=123456789
FIREBASE_APP_ID=1:123456789:web:abc123

# FIREBASE ADMIN SDK
FIREBASE_SERVICE_ACCOUNT_PATH=config/firebase-service-account.json

# SMTP (Gmail example)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-16-char-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME="Smart Water Guardian"

# SECURITY
SESSION_TIMEOUT=1800
MAX_LOGIN_ATTEMPTS=3
WARNING_ATTEMPT_THRESHOLD=2
BLOCK_DURATION=300

# BILLING
VAT_RATE=0.15
TIER1_RATE=18.50
TIER2_RATE=25.00
TIER3_RATE=35.00
TIER4_RATE=45.00

# ADMIN WHITELIST (comma-separated emails that auto-become admins)
ADMIN_EMAILS=admin@smartwater.com
```

> ⚠️ **Important:** Never commit `.env` to Git. It's already in `.gitignore`.

### Step 8 — Set Up Firebase (see detailed [Firebase Setup](#-firebase-setup) section)

Short version:
1. Create a Firebase project
2. Enable Email/Password authentication
3. Create a Realtime Database
4. Copy the Web SDK config into `.env`
5. Download the Admin SDK service account key → save to `config/firebase-service-account.json`

### Step 9 — Configure SMTP (Gmail)

1. Enable **2-Factor Authentication** on your Google account
2. Generate an **App Password**: [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
3. Copy the 16-character password (remove spaces) into `SMTP_PASSWORD` in `.env`

### Step 10 — Open the Application

Open your browser:

```
http://localhost/SmartWaterGuardian
```

You should see the **Smart Water Guardian landing page** with the animated water droplet splash screen.

### Step 11 — Register Your First Account

1. Click **Get Started** or **Register**
2. Fill in the registration form
3. Choose **Consumer** or **Admin** role
4. Submit

**For your first admin account:**

If you set `ADMIN_EMAILS=admin@smartwater.com` in `.env`, register with that exact email and the account will be auto-approved as **System Admin**.

Otherwise, register normally and manually approve yourself in the database:

```sql
UPDATE users
SET is_approved = 1, role = 'system_admin'
WHERE email = 'your-email@example.com';
```

### Step 12 — Log In and Explore

1. Go to [http://localhost/SmartWaterGuardian/pages/login.php](http://localhost/SmartWaterGuardian/pages/login.php)
2. Enter your credentials
3. Select the correct **role** (Consumer / Admin)
4. You'll land on the dashboard

**You're in!** 🎉

---

## 🅱️ Option B — Run with PHP Built-in Server (No XAMPP)

Simpler, but no phpMyAdmin GUI.

### Step 1 — Install PHP and MySQL

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install php php-mysqli php-curl php-mbstring php-xml php-json mysql-server
```

**macOS (Homebrew):**
```bash
brew install php mysql
brew services start mysql
```

**Windows:** Download PHP from [windows.php.net](https://windows.php.net/download/) and add to PATH.

### Step 2 — Clone and Install

```bash
git clone https://github.com/your-org/SmartWaterGuardian.git
cd SmartWaterGuardian
composer install
```

### Step 3 — Set Up Database

```bash
mysql -u root -p -e "CREATE DATABASE smart_water_guardian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p smart_water_guardian < database/schema.sql
```

### Step 4 — Configure `.env`

Same as Option A, Step 7. But change the URL:

```env
APP_URL=http://localhost:8000
```

### Step 5 — Start the Server

```bash
php -S localhost:8000
```

### Step 6 — Open the Application

Open [http://localhost:8000](http://localhost:8000)

---

## 🅲 Option C — Run with Docker (Advanced)

Coming soon in Sprint 7. For now, use Option A or B.

---

## ✅ Post-Installation Verification

Run through this checklist to confirm everything works:

### 1. Database Connectivity

Visit: [http://localhost/SmartWaterGuardian/test.php](http://localhost/SmartWaterGuardian/test.php)

Or create a `test.php` in the root:

```php
<?php
require_once 'config/database.php';
echo "✅ Database connected successfully!";
echo "<br>📊 Tables found: " . $conn->query("SHOW TABLES")->num_rows;
```

### 2. Landing Page

[http://localhost/SmartWaterGuardian/](http://localhost/SmartWaterGuardian/) → animated splash + hero section

### 3. Registration Flow

[http://localhost/SmartWaterGuardian/pages/register.php](http://localhost/SmartWaterGuardian/pages/register.php) → create test user

### 4. Login Flow

[http://localhost/SmartWaterGuardian/pages/login.php](http://localhost/SmartWaterGuardian/pages/login.php) → log in

### 5. Dashboard

[http://localhost/SmartWaterGuardian/pages/dashboard.php](http://localhost/SmartWaterGuardian/pages/dashboard.php) → should show live metrics

### 6. Admin Panel (if admin)

[http://localhost/SmartWaterGuardian/pages/admin.php](http://localhost/SmartWaterGuardian/pages/admin.php)

### 7. Firebase Data

Visit [Firebase Console](https://console.firebase.google.com/) → Realtime Database → check `users/` node has your test user

### 8. Automated Tests

```bash
php tests/run-tests.php
```

Expected: **53 / 55 pass** (2 known LOW-severity failures — see [Bug Register](#-bug-register))

---

## 🧪 Running Tests

### Run All Tests (Recommended)

```bash
php tests/run-tests.php
```

**Expected output:**

```
========================================
Smart Water Guardian - Test Results
========================================

Total Tests:  55
Passed:       53
Failed:       2
Pass Rate:    96.4%

========================================
```

### Run with PHPUnit

```bash
vendor/bin/phpunit
```

### Run a Specific Test Suite

```bash
vendor/bin/phpunit tests/BillingTest.php
vendor/bin/phpunit tests/LoginTest.php
vendor/bin/phpunit tests/AdminTest.php
vendor/bin/phpunit tests/UserRegistrationTest.php
```

### Generate HTML Report

```bash
php tests/generate-test-results.php
```

Then open `test-results.html` in your browser.

---

## 🛑 Stopping the Application

### XAMPP

XAMPP Control Panel → **Stop** Apache → **Stop** MySQL

### PHP Built-in Server

`Ctrl + C` in the terminal running the server

### Database Backup (before stopping MySQL)

```bash
mysqldump -u root -p smart_water_guardian > backup_$(date +%Y%m%d).sql
```

---

## 📊 Quick Reference: Common Commands

| Task | Command |
|------|---------|
| Start XAMPP | Launch XAMPP Control Panel → Start Apache + MySQL |
| Start PHP server | `php -S localhost:8000` |
| Install dependencies | `composer install` |
| Update dependencies | `composer update` |
| Import database | `mysql -u root -p smart_water_guardian < database/schema.sql` |
| Backup database | `mysqldump -u root -p smart_water_guardian > backup.sql` |
| Run all tests | `php tests/run-tests.php` |
| Run PHPUnit | `vendor/bin/phpunit` |
| Generate test report | `php tests/generate-test-results.php` |
| Clear Composer cache | `composer clear-cache` |
| Check PHP version | `php -v` |
| Check MySQL version | `mysql --version` |

---

## ⚙️ Configuration Reference

### Environment Variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `APP_ENV` | ✅ | `development` | `development` or `production` |
| `APP_NAME` | ✅ | `Smart Water Guardian` | Display name |
| `APP_URL` | ✅ | — | Base URL |
| `APP_TIMEZONE` | ⬜ | `Africa/Johannesburg` | PHP timezone |
| `DB_HOST` | ✅ | `localhost` | MySQL host |
| `DB_PORT` | ✅ | `3306` | MySQL port |
| `DB_DATABASE` | ✅ | `smart_water_guardian` | Database name |
| `DB_USERNAME` | ✅ | `root` | MySQL username |
| `DB_PASSWORD` | ✅ | *(empty)* | MySQL password |
| `DB_CHARSET` | ⬜ | `utf8mb4` | Connection charset |
| `FIREBASE_API_KEY` | ✅ | — | Firebase web SDK key |
| `FIREBASE_DATABASE_URL` | ✅ | — | Realtime DB URL |
| `FIREBASE_SERVICE_ACCOUNT_PATH` | ✅ | `config/firebase-service-account.json` | Admin SDK key path |
| `SMTP_HOST` | ✅ | `smtp.gmail.com` | SMTP server |
| `SMTP_PORT` | ✅ | `587` | SMTP port |
| `SMTP_USERNAME` | ✅ | — | SMTP login |
| `SMTP_PASSWORD` | ✅ | — | SMTP app password |
| `SESSION_TIMEOUT` | ⬜ | `1800` | Session timeout (seconds) |
| `MAX_LOGIN_ATTEMPTS` | ⬜ | `3` | Attempts before lockout |
| `BLOCK_DURATION` | ⬜ | `300` | Lockout duration (seconds) |
| `VAT_RATE` | ⬜ | `0.15` | South African VAT |
| `TIER1_RATE`..`TIER4_RATE` | ⬜ | `18.50`..`45.00` | Water tariffs (R/kL) |

---

## 🗄 Database Setup

### Schema Overview

| Table | Purpose |
|-------|---------|
| `users` | User accounts, roles, approval status |
| `devices` | Registered ESP32 meters |
| `properties` | Physical locations owned by users |
| `water_readings` | Historical flow/volume/pressure data |
| `alerts` | Notification records |
| `alert_thresholds` | User-configured alert limits |
| `billing` | Monthly invoices |
| `messages` | User ↔ admin communications |
| `reviews` | Community feedback |
| `audit_logs` | Security and activity trail |

### Reset Database

```bash
# Drop and recreate
mysql -u root -p -e "DROP DATABASE smart_water_guardian;"
mysql -u root -p -e "CREATE DATABASE smart_water_guardian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p smart_water_guardian < database/schema.sql
```

---

## 🔥 Firebase Setup

### 1. Create the Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. **Add project** → name it (e.g. `smartwaterguardian`)
3. Disable Google Analytics (optional) → **Create project**

### 2. Enable Authentication

1. **Authentication → Sign-in method**
2. Enable **Email/Password**
3. Optionally enable **Google**

### 3. Create Realtime Database

1. **Realtime Database → Create Database**
2. Region: closest to your users
3. Start in **Locked mode**

### 4. Apply Security Rules

In the **Rules** tab:

```json
{
  "rules": {
    ".read": false,
    ".write": false,

    "users": {
      "$uid": {
        ".read": "auth != null && (auth.uid == $uid || root.child('users').child(auth.uid).child('role').val() == 'admin')",
        ".write": "auth != null && (auth.uid == $uid || root.child('users').child(auth.uid).child('role').val() == 'admin')",
        ".validate": "newData.hasChildren(['email', 'name'])",
        "email": {
          ".validate": "newData.isString() && newData.val().matches(/^[^@]+@[^@]+\\.[^@]+$/)"
        },
        "role": {
          ".validate": "newData.val() == 'user' || newData.val() == 'admin'"
        }
      }
    },

    "alerts": {
      ".read": "auth != null",
      ".write": "auth != null",
      "$alertId": {
        ".validate": "newData.hasChildren(['userId', 'message', 'timestamp'])",
        "userId": {
          ".validate": "newData.isString() && (newData.val() == auth.uid || root.child('users').child(auth.uid).child('role').val() == 'admin')"
        }
      }
    },

    "properties": {
      ".read": "auth != null",
      ".write": "auth != null && root.child('users').child(auth.uid).child('role').val() == 'admin'",
      "$propertyId": {
        ".validate": "newData.hasChildren(['address', 'ownerId'])"
      }
    },

    "meters": {
      ".read": "auth != null",
      ".write": "auth != null",
      "$meterId": {
        ".validate": "newData.hasChildren(['propertyId', 'reading', 'timestamp'])",
        "reading": {
          ".validate": "newData.isNumber() && newData.val() >= 0"
        }
      }
    },

    "thresholds": {
      ".read": "auth != null",
      ".write": "auth != null && root.child('users').child(auth.uid).child('role').val() == 'admin'",
      "$thresholdId": {
        ".validate": "newData.hasChildren(['meterId', 'maxValue'])",
        "maxValue": {
          ".validate": "newData.isNumber() && newData.val() > 0"
        }
      }
    },

    "messages": {
      ".read": "auth != null",
      ".write": "auth != null",
      "$messageId": {
        ".validate": "newData.hasChildren(['senderId', 'receiverId', 'content', 'timestamp'])",
        "senderId": {
          ".validate": "newData.val() == auth.uid"
        }
      }
    },

    "reviews": {
      ".read": "auth != null",
      ".write": "auth != null",
      "$reviewId": {
        ".validate": "newData.hasChildren(['userId', 'rating', 'comment'])",
        "userId": {
          ".validate": "newData.val() == auth.uid"
        },
        "rating": {
          ".validate": "newData.isNumber() && newData.val() >= 1 && newData.val() <= 5"
        }
      }
    },

    "bills": {
      ".read": "auth != null && (data.child('userId').val() == auth.uid || root.child('users').child(auth.uid).child('role').val() == 'admin')",
      ".write": "auth != null && root.child('users').child(auth.uid).child('role').val() == 'admin'",
      "$billId": {
        ".validate": "newData.hasChildren(['userId', 'amount', 'dueDate', 'status'])",
        "amount": {
          ".validate": "newData.isNumber() && newData.val() > 0"
        },
        "status": {
          ".validate": "newData.val() == 'pending' || newData.val() == 'paid' || newData.val() == 'overdue'"
        }
      }
    },

    "admin_settings": {
      ".read": "auth != null && root.child('users').child(auth.uid).child('role').val() == 'admin'",
      ".write": "auth != null && root.child('users').child(auth.uid).child('role').val() == 'admin'"
    }
  }
}
```

> ⚠️ **Never use `".read": true, ".write": true` in production** — it exposes your database to the entire internet.

### 5. Get Web SDK Config

1. **Project Settings** (gear icon) → **General**
2. Scroll to **Your apps** → click the **Web** icon (`</>`)
3. Register app → copy the `firebaseConfig` values
4. Paste into `.env`

### 6. Get Admin SDK Key

1. **Project Settings → Service Accounts**
2. Click **Generate new private key**
3. Save the JSON file to `config/firebase-service-account.json`

> 🔒 This file is already in `.gitignore`. Never commit it.

---

## 🔌 ESP32 Hardware Setup

For full details, see [`docs/README-ESP32.md`](docs/README-ESP32.md).

### Quick Start

1. **Install Arduino IDE** and the **ESP32 board package**
2. **Install libraries:** `Firebase ESP32 Client` by mobizt
3. **Wire the sensor:**
   - YF-S201 Red → ESP32 3.3V
   - YF-S201 Black → ESP32 GND
   - YF-S201 Yellow → ESP32 GPIO34
4. **Create `secrets.h`** with your WiFi + Firebase credentials (do **not** commit)
5. **Upload the firmware**
6. **Open Serial Monitor** at 115200 baud → verify data is being sent

---

## 👥 User Roles & Permissions

| Role | Consumer | Municipal Admin | System Admin |
|------|:--------:|:---------------:|:------------:|
| View own dashboard | ✅ | ✅ | ✅ |
| View own usage history | ✅ | ✅ | ✅ |
| Configure thresholds | ✅ | ✅ | ✅ |
| Manage own properties | ✅ | ✅ | ✅ |
| View own bills | ✅ | ✅ | ✅ |
| Approve new users | ❌ | ✅ | ✅ |
| View all users | ❌ | ✅ | ✅ |
| Manage devices | ❌ | ✅ | ✅ |
| Delete users | ❌ | ❌ | ✅ |
| System configuration | ❌ | ❌ | ✅ |

---

## 📡 API Reference

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/auth.php` | Set session / login attempt / logout |
| `POST` | `/api/users.php` | Create or update user |
| `GET`  | `/api/users.php?firebase_uid={uid}` | Get user details |
| `PUT`  | `/api/users.php` | Update user |

### Devices

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`    | `/api/devices.php` | List devices |
| `POST`   | `/api/devices.php` | Register device |
| `PUT`    | `/api/devices.php` | Update device |
| `DELETE` | `/api/devices.php?meter_id={id}` | Delete device |

### Alerts

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`  | `/api/alerts.php` | Get all alerts |
| `POST` | `/api/alerts.php` | Create alert |
| `PUT`  | `/api/alerts.php` | Mark alert as read |

### Billing

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`  | `/api/sync.php?action=get_bills&firebase_uid={uid}` | Get bills |
| `POST` | `/api/sync.php` | Create bill |
| `PUT`  | `/api/sync.php` | Pay bill |

### Notifications

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/send-notification.php` | Send transactional email |

### PDF Reports

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`  | `/api/generate-pdf-report.php?month=YYYY-MM` | Download PDF report |

Full endpoint documentation: [`docs/API.md`](docs/API.md)

---

## 💰 Billing System

### South African Water Tariff Structure (2026)

| Tier | Volume (kL) | Rate (R/kL) |
|------|-------------|-------------|
| Tier 1 | 0 – 6 | R 18.50 |
| Tier 2 | 6 – 20 | R 25.00 |
| Tier 3 | 20 – 40 | R 35.00 |
| Tier 4 | 40+ | R 45.00 |

**VAT:** 15%

### Calculation Example — 30 kL Usage

```
Tier 1:  6 kL × R 18.50 = R 111.00
Tier 2: 14 kL × R 25.00 = R 350.00
Tier 3: 10 kL × R 35.00 = R 350.00
─────────────────────────────────────
Subtotal:                 R 811.00
VAT (15%):                R 121.65
─────────────────────────────────────
Total:                    R 932.65
```

### Invoice Format

```
INV-YYYY-MM-XXXX
Example: INV-2026-01-1234
```

---

## 📧 Email Notifications

| Type | Trigger | Recipient |
|------|---------|-----------|
| `pending_approval` | User registers | New user |
| `account_approved` | Admin approves | User |
| `account_rejected` | Admin rejects | User |
| `account_deleted` | Admin deletes | User |
| `bill_reminder` | Bill due | User |
| `admin_message` | Admin sends | User |

### Configure Gmail SMTP

1. Enable **2-Factor Authentication** on your Gmail account
2. Generate an **App Password** at [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
3. Add to `.env`:
   ```env
   SMTP_USERNAME=your-email@gmail.com
   SMTP_PASSWORD=your-16-char-app-password
   ```

---

## 🧪 Testing

### Test Suites

| Suite | Tests | Coverage |
|-------|:-----:|----------|
| `AdminTest` | 11 | Admin role validation, approval, deletion |
| `BillingTest` | 11 | Tier calculations, VAT, edge cases |
| `LoginTest` | 12 | Auth, role enforcement, lockout |
| `UserRegistrationTest` | 8 | Validation, duplicates, meter numbers |
| **Edge Cases** | 24 | Boundary conditions, type safety |

### Run Tests

```bash
# All tests with custom runner
php tests/run-tests.php

# PHPUnit
vendor/bin/phpunit

# Single suite
vendor/bin/phpunit tests/BillingTest.php

# HTML report
php tests/generate-test-results.php
```

### Expected Result

```
Total:     55
Passed:    53  ✅
Failed:     2  ⚠️ (LOW severity — see Bug Register)
Pass Rate: 96.4%
```

---

## 🔒 Security & POPIA Compliance

### Security Controls

| Control | Implementation |
|---------|----------------|
| Password hashing | Firebase Authentication (bcrypt) |
| Session management | PHP sessions, 30-min timeout |
| Login lockout | 3 attempts → 5-min block |
| SQL injection prevention | Prepared statements (mysqli) |
| XSS protection | `htmlspecialchars()` on output |
| CSRF protection | Session token validation |
| Role-based access | Server-side enforcement |
| Secret management | `.env` + `.gitignore` |

### POPIA Compliance

Smart Water Guardian processes personal information in accordance with the **Protection of Personal Information Act (POPIA, Act 4 of 2013)**.

**Data we collect:**
- Name, email, phone, address (for account management)
- Water usage data (for billing and monitoring)
- Device telemetry (for system operation)

**Data subject rights:**
- ✅ Right to access your data
- ✅ Right to correction
- ✅ Right to deletion
- ✅ Right to object to processing

**Data retention:**
- Active accounts: retained while account is active
- Deleted accounts: purged within 30 days
- Audit logs: retained for 12 months

**Data sharing:**
- We do **not** sell personal data
- Data is shared only with your municipality (if applicable) and Firebase (as a processor)

**Contact for POPIA inquiries:** privacy@smartwater.co.za

> ⚠️ **Release Gate Reminder:** No exposed secrets, plain-text passwords, or unrestricted admin functions are permitted in the submission. See [`.env.example`](.env.example) for the correct pattern.

---

## 🐛 Bug Register

| ID | Description | Severity | Status |
|----|-------------|----------|--------|
| BUG-014 | `calculateBill(0)` returns `float(0)` instead of `int(0)` | 🟢 Low | Open (cosmetic) |
| BUG-015 | ESP32 `getTimestamp()` uses compile-time values | 🟡 Medium | Planned Sprint 7 |
| BUG-016 | ESP32 `getDateString()` returns hardcoded date | 🟡 Medium | Planned Sprint 7 |
| BUG-017 | Firebase rules allow public read in dev config | 🔴 High | Documented; prod rules provided |

**No Critical or High defect blocks the primary user journey.**

Full bug register: [`docs/BUG-REGISTER.md`](docs/BUG-REGISTER.md)

---

## 🛠 Troubleshooting

### Database Connection Failed

**Symptom:** "Database connection failed" error

**Fix:**
1. Confirm MySQL is running (XAMPP → green light)
2. Verify `.env` credentials match your MySQL setup
3. Test connection:
   ```bash
   mysql -u root -p -e "SELECT 1;"
   ```

### Firebase Not Connecting

**Symptom:** Dashboard shows no live data

**Fix:**
1. Verify `FIREBASE_DATABASE_URL` in `.env` ends with `.firebaseio.com`
2. Check Firebase rules allow authenticated read/write
3. Open browser DevTools → Console → look for Firebase errors

### Email Not Sending

**Symptom:** Users don't receive emails

**Fix:**
1. Use an **App Password**, not your Gmail password
2. Verify `SMTP_USERNAME` and `SMTP_PASSWORD` in `.env`
3. Check PHP error log: `tail -f /var/log/apache2/error.log`

### Port 80 Already in Use

**Symptom:** Apache won't start

**Fix:** Change Apache port to 8080:
1. XAMPP → Config → Apache (httpd.conf)
2. Change `Listen 80` → `Listen 8080`
3. Restart Apache
4. Update `.env`: `APP_URL=http://localhost:8080/SmartWaterGuardian`

### Composer Install Fails

**Symptom:** Composer errors

**Fix:**
```bash
composer clear-cache
composer install --no-scripts
```

### Permission Denied (Linux/macOS)

**Symptom:** "Failed to open stream: Permission denied"

**Fix:**
```bash
sudo chown -R $USER:www-data .
chmod -R 755 .
chmod -R 775 config/ reports/ logs/
```

### ESP32 Not Sending Data

**Symptom:** Firebase shows no meter data

**Fix:**
1. Check Serial Monitor at 115200 baud
2. Verify WiFi credentials in `secrets.h`
3. Confirm Firebase URL + secret
4. Ensure GPIO34 is wired correctly

---

## 🗺 Roadmap

### Sprint 7 (Planned)

- [ ] NTP time sync on ESP32
- [ ] Move all secrets to environment variables (release gate)
- [ ] Fix BUG-014 (type strictness)
- [ ] Machine learning leak prediction
- [ ] SMS notifications
- [ ] Docker Compose setup
- [ ] Full API documentation

### Sprint 8 (Vision)

- [ ] Mobile app (React Native)
- [ ] Water quality monitoring
- [ ] Community leaderboards
- [ ] Third-party API
- [ ] Multi-tenant municipal deployment

---

## 👥 Team

**Code Crew Innovators** — University of Johannesburg

| Student Number | Name | Role |
|----------------|------|------|
| 221152725 | Mongiwethu Eddy Ncube | Project Lead / Backend |
| 220115085 | Sandile Sibeko | Frontend / UI-UX |
| 220068905 | Keamogetse Selebano | Hardware / IoT |
| 220122253 | Ndzulamo Michelle Yingwani | Testing / QA |
| 220080694 | Hlonipho Nersely Bila | Database / DevOps |
| 220061777 | Zizile Ezona Mbangi | Documentation |
| 219027546 | Bongane Sithole | Integration / Support |

---

## 🤝 Contributing

### Workflow

1. Fork the repository
2. Create a feature branch
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. Commit with conventional messages
   ```bash
   git commit -m "feat: add amazing feature"
   ```
4. Push and open a Pull Request

### Commit Convention

| Prefix | Purpose |
|--------|---------|
| `feat:` | New feature |
| `fix:` | Bug fix |
| `test:` | Add/update tests |
| `docs:` | Documentation |
| `refactor:` | Code refactor |
| `chore:` | Maintenance |

### Before Submitting

- [ ] All tests pass (`php tests/run-tests.php`)
- [ ] No secrets committed
- [ ] README updated if needed
- [ ] Commit messages are meaningful

---

## 📄 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- **University of Johannesburg** — Project framework
- **Firebase** — Realtime infrastructure
- **PHPMailer** — Email delivery
- **Chart.js** — Data visualization
- **Font Awesome** — Icons
- All contributors, testers, and reviewers

---

## 📞 Support

- **Email:** support@smartwater.co.za
- **Website:** [www.smartwater.co.za](https://www.smartwater.co.za)
- **Documentation:** [docs.smartwater.co.za](https://docs.smartwater.co.za)

---

**Built with 💧 for South Africa**

*Last Updated: January 2026* · *Sprint 5-6 MVP Release*
