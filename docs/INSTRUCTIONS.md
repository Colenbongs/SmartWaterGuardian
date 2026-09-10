# 📖 INSTRUCTIONS.md — Setup, Installation & Execution Guide

**Smart Water Guardian** · Code Crew Innovators · University of Johannesburg
**Release:** `Sprint-5-6-MVP` · **Last Updated:** 10 September 2026

---

## 📋 Table of Contents

1. [Before You Begin](#-before-you-begin)
2. [System Requirements](#-system-requirements)
3. [Installation — Choose Your Path](#-installation--choose-your-path)
4. [Path A — XAMPP (Recommended)](#-path-a--xampp-recommended)
5. [Path B — PHP Built-in Server](#-path-b--php-built-in-server)
6. [Path C — Manual Apache/MySQL](#-path-c--manual-apachemysql)
7. [Environment Configuration](#-environment-configuration)
8. [Database Setup](#-database-setup)
9. [Firebase Setup](#-firebase-setup)
10. [Email (SMTP) Setup](#-email-smtp-setup)
11. [ESP32 Hardware Setup](#-esp32-hardware-setup)
12. [First Login & Admin Creation](#-first-login--admin-creation)
13. [Running the Automated Tests](#-running-the-automated-tests)
14. [Verifying the Installation](#-verifying-the-installation)
15. [Common Tasks](#-common-tasks)
16. [Troubleshooting](#-troubleshooting)
17. [Stopping & Cleanup](#-stopping--cleanup)
18. [Quick Reference Card](#-quick-reference-card)

---

## 🚦 Before You Begin

### What you're setting up

Smart Water Guardian is a **PHP + MySQL + Firebase** web application with an **optional ESP32 hardware component**. You can run the full web application locally without any hardware — the dashboard works with Firebase data whether it comes from a real ESP32 or manually injected test data.

### Time estimate

| Path | Setup Time | Difficulty |
|------|-----------|-----------|
| A — XAMPP | 15–25 min | 🟢 Easy |
| B — PHP built-in | 10–20 min | 🟡 Medium |
| C — Manual Apache | 25–40 min | 🔴 Advanced |
| ESP32 hardware (optional) | +30 min | 🟡 Medium |

### What you'll need

| Item | Required? | Notes |
|------|:---------:|-------|
| PHP 8.0+ | ✅ | With `mysqli`, `curl`, `openssl`, `mbstring`, `json` |
| MySQL 5.7+ | ✅ | Or MariaDB 10.3+ |
| Composer 2.x | ✅ | PHP dependency manager |
| Git | ✅ | To clone the repo |
| Web server | ✅ | XAMPP, or PHP's built-in server |
| Firebase account | ✅ | Free tier is sufficient |
| Gmail account | ⬜ | Only if testing email notifications |
| Arduino IDE + ESP32 | ⬜ | Only if testing hardware |

---

## 🖥 System Requirements

### Minimum

| Component | Requirement |
|-----------|-------------|
| OS | Windows 10, macOS 11, Ubuntu 20.04 |
| RAM | 4 GB |
| Disk | 2 GB free |
| Browser | Chrome 100+, Firefox 100+, Edge 100+, Safari 15+ |

### Verify your environment

Open a terminal and run:

```bash
php -v
mysql --version
composer --version
git --version
```

**Expected output (versions may be higher):**

```
PHP 8.0.30 (cli)
mysql  Ver 15.1 Distrib 10.4.28-MariaDB
Composer version 2.5.8
git version 2.40.0
```

### Verify required PHP extensions

```bash
php -m | grep -E 'mysqli|curl|openssl|mbstring|json'
```

**Expected output:**

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

Then restart your web server (or PHP process).

---

## 🛠 Installation — Choose Your Path

| Path | Best For | Skip to |
|------|----------|---------|
| **A — XAMPP** | Windows/macOS users who want everything bundled | [Path A](#-path-a--xampp-recommended) |
| **B — PHP built-in server** | Developers comfortable with the CLI | [Path B](#-path-b--php-built-in-server) |
| **C — Manual Apache/MySQL** | Production-like setups, Linux servers | [Path C](#-path-c--manual-apachemysql) |

**All paths share the same [Environment Configuration](#-environment-configuration), [Database Setup](#-database-setup), and [Firebase Setup](#-firebase-setup) steps.** Only the server hosting differs.

---

## 🅰 Path A — XAMPP (Recommended)

The easiest path for Windows and macOS users. XAMPP bundles Apache, MySQL, PHP, and phpMyAdmin in one installer.

### A.1 Install XAMPP

1. Download from [apachefriends.org](https://www.apachefriends.org/)
2. Run the installer with default options
3. Install to:
   - **Windows:** `C:\xampp`
   - **macOS:** `/Applications/XAMPP`
   - **Linux:** `/opt/lampp`

### A.2 Start Apache and MySQL

1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache** → wait for green
3. Click **Start** next to **MySQL** → wait for green

**Both must show "Running"** before continuing.

> ⚠️ **Port 80 conflict?** If Apache won't start (common on Windows if IIS or Skype is running), change Apache's port:
> 1. XAMPP → **Config** → **Apache (httpd.conf)**
> 2. Change `Listen 80` → `Listen 8080`
> 3. Change `ServerName localhost:80` → `ServerName localhost:8080`
> 4. Restart Apache
> 5. Your URL becomes `http://localhost:8080/SmartWaterGuardian`
> 6. Remember to update `APP_URL` in `.env` to match

### A.3 Clone the Repository into `htdocs`

**Windows (PowerShell):**

```powershell
cd C:\xampp\htdocs
git clone https://github.com/your-org/SmartWaterGuardian.git
cd SmartWaterGuardian
```

**macOS:**

```bash
cd /Applications/XAMPP/htdocs
git clone https://github.com/your-org/SmartWaterGuardian.git
cd SmartWaterGuardian
```

**Linux:**

```bash
cd /opt/lampp/htdocs
git clone https://github.com/your-org/SmartWaterGuardian.git
cd SmartWaterGuardian
```

### A.4 Install PHP Dependencies

From the project root:

```bash
composer install
```

**Expected output:**

```
Loading composer repositories with package information
Installing dependencies from lock file (including require-dev)
  - Installing phpmailer/phpmailer (v6.9.1)
  - Installing phpunit/phpunit (v9.6.13)
Generating optimized autoload files
```

> 💡 **`composer` not found?**
> - **Windows:** Download and run [Composer-Setup.exe](https://getcomposer.org/download/)
> - **macOS:** `brew install composer`
> - **Linux:** `sudo apt install composer`

### A.5 Continue with shared setup

➡️ Skip ahead to [**Environment Configuration**](#-environment-configuration)

---

## 🅱 Path B — PHP Built-in Server

No XAMPP. Just PHP + MySQL from the command line.

### B.1 Install PHP and MySQL

**Ubuntu / Debian:**

```bash
sudo apt update
sudo apt install -y php php-cli php-mysqli php-curl php-mbstring \
                    php-xml php-json php-zip mysql-server unzip git curl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**macOS (Homebrew):**

```bash
brew install php mysql composer git
brew services start mysql
```

**Windows:** Download PHP from [windows.php.net](https://windows.php.net/download/) and add `php.exe`'s folder to your PATH.

### B.2 Verify installations

```bash
php -v
mysql --version
composer --version
```

### B.3 Clone and install dependencies

```bash
# Choose a location (any will do)
mkdir -p ~/projects && cd ~/projects

git clone https://github.com/your-org/SmartWaterGuardian.git
cd SmartWaterGuardian
composer install
```

### B.4 Continue with shared setup

➡️ Skip ahead to [**Environment Configuration**](#-environment-configuration)

---

## 🅲 Path C — Manual Apache/MySQL

For Linux servers or advanced local setups.

### C.1 Install Apache, PHP, MySQL

**Ubuntu / Debian:**

```bash
sudo apt update
sudo apt install -y apache2 mysql-server php libapache2-mod-php \
                    php-mysqli php-curl php-mbstring php-xml php-json \
                    unzip git curl

# Enable Apache modules
sudo a2enmod rewrite headers
sudo systemctl restart apache2
sudo systemctl enable apache2 mysql
```

**Fedora / RHEL:**

```bash
sudo dnf install -y httpd mariadb-server php php-mysqli php-curl \
                    php-mbstring php-xml php-json git curl
sudo systemctl enable --now httpd mariadb
```

### C.2 Configure a Virtual Host

Create `/etc/apache2/sites-available/smartwater.conf`:

```apache
<VirtualHost *:80>
    ServerName smartwater.local
    DocumentRoot /var/www/SmartWaterGuardian

    <Directory /var/www/SmartWaterGuardian>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/smartwater-error.log
    CustomLog ${APACHE_LOG_DIR}/smartwater-access.log combined
</VirtualHost>
```

Enable and reload:

```bash
sudo a2ensite smartwater
sudo systemctl reload apache2
```

Add to `/etc/hosts`:

```
127.0.0.1   smartwater.local
```

### C.3 Clone into DocumentRoot

```bash
sudo git clone https://github.com/your-org/SmartWaterGuardian.git /var/www/SmartWaterGuardian
sudo chown -R www-data:www-data /var/www/SmartWaterGuardian
```

### C.4 Install PHP Dependencies

```bash
cd /var/www/SmartWaterGuardian
sudo -u www-data composer install
```

### C.5 Continue with shared setup

➡️ Proceed to [**Environment Configuration**](#-environment-configuration)

---

## ⚙ Environment Configuration

**All three paths converge here.**

### 1. Create the `.env` file

From the project root:

```bash
cp .env.example .env
```

**Windows alternative:**

```powershell
copy .env.example .env
```

### 2. Edit `.env`

Open `.env` in your editor and fill in the values. **Start with this minimal set** — you can leave Firebase/SMTP blank for now if you just want the app to boot:

```env
# ============================================================
# APPLICATION
# ============================================================
APP_ENV=development
APP_NAME="Smart Water Guardian"
APP_URL=http://localhost/SmartWaterGuardian
APP_TIMEZONE=Africa/Johannesburg

# ============================================================
# DATABASE (default XAMPP credentials)
# ============================================================
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=smart_water_guardian
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4

# ============================================================
# FIREBASE (Web SDK — fill from Firebase Console)
# ============================================================
FIREBASE_API_KEY=
FIREBASE_AUTH_DOMAIN=
FIREBASE_DATABASE_URL=
FIREBASE_PROJECT_ID=
FIREBASE_STORAGE_BUCKET=
FIREBASE_MESSAGING_SENDER_ID=
FIREBASE_APP_ID=

# ============================================================
# FIREBASE ADMIN SDK
# ============================================================
FIREBASE_SERVICE_ACCOUNT_PATH=config/firebase-service-account.json

# ============================================================
# SMTP EMAIL (Gmail example)
# ============================================================
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=
SMTP_FROM_NAME="Smart Water Guardian"

# ============================================================
# SECURITY
# ============================================================
SESSION_TIMEOUT=1800
MAX_LOGIN_ATTEMPTS=3
WARNING_ATTEMPT_THRESHOLD=2
BLOCK_DURATION=300

# ============================================================
# BILLING (South African tariffs 2026)
# ============================================================
VAT_RATE=0.15
TIER1_RATE=18.50
TIER2_RATE=25.00
TIER3_RATE=35.00
TIER4_RATE=45.00

# ============================================================
# ADMIN WHITELIST (emails that auto-become system admins)
# ============================================================
ADMIN_EMAILS=admin@smartwater.com
```

### 3. Verify `.env` is ignored by Git

```bash
git check-ignore .env
```

**Expected output:**

```
.env
```

If nothing is printed, `.env` is **not** ignored — fix `.gitignore` immediately:

```bash
echo ".env" >> .gitignore
echo ".env.*" >> .gitignore
echo "!.env.example" >> .gitignore
```

> 🔒 **Never commit `.env`.** It contains database passwords and API keys.

### 4. Set file permissions (Linux / macOS)

```bash
chmod -R 755 .
chmod -R 775 config/ reports/ logs/ 2>/dev/null
chmod 600 .env
```

**Windows:** No action needed.

---

## 🗄 Database Setup

### 1. Create the database

**Command line (all platforms):**

```bash
mysql -u root -p
```

Then inside the MySQL prompt:

```sql
CREATE DATABASE smart_water_guardian
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
EXIT;
```

**Or via phpMyAdmin (XAMPP users):**

1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click **New** on the left sidebar
3. Database name: `smart_water_guardian`
4. Collation: `utf8mb4_unicode_ci`
5. Click **Create**

### 2. Import the schema

**Command line:**

```bash
mysql -u root -p smart_water_guardian < database/schema.sql
```

**phpMyAdmin:**

1. Select `smart_water_guardian` from the left sidebar
2. Click **Import**
3. Choose file: `database/schema.sql`
4. Click **Go**

### 3. (Optional) Load demo data

```bash
mysql -u root -p smart_water_guardian < database/seed.sql
```

### 4. Verify tables were created

```bash
mysql -u root -p -e "USE smart_water_guardian; SHOW TABLES;"
```

**Expected output (10 tables):**

```
+--------------------------------+
| Tables_in_smart_water_guardian |
+--------------------------------+
| alert_thresholds               |
| alerts                         |
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

If tables are missing → re-run the import and check for SQL errors.

### 5. Verify PHP can connect

Create a quick test file `test-db.php` at the project root:

```php
<?php
require_once 'config/database.php';
$result = $conn->query("SHOW TABLES");
echo "✅ Connected to MySQL. Tables: " . $result->num_rows . "\n";
```

Run it:

```bash
php test-db.php
```

**Expected output:**

```
✅ Connected to MySQL. Tables: 10
```

**Delete the test file when done:**

```bash
rm test-db.php
```

---

## 🔥 Firebase Setup

Smart Water Guardian uses Firebase for:
- **Authentication** (user login)
- **Realtime Database** (live water readings, alerts, messages)

### 1. Create a Firebase project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click **Add project**
3. Name it (e.g., `smartwaterguardian`)
4. Disable Google Analytics (optional) → **Create project**

### 2. Enable Email/Password authentication

1. **Authentication** → **Sign-in method**
2. Enable **Email/Password**
3. Optionally enable **Google** (for social login)

### 3. Create a Realtime Database

1. **Realtime Database** → **Create Database**
2. Region: closest to your users (e.g., `europe-west1` for South Africa)
3. Start in **Locked mode** (we'll apply proper rules next)

### 4. Apply security rules

In the **Rules** tab:

```json
{
  "rules": {
    "users": {
      "$uid": {
        ".read": "$uid === auth.uid || root.child('users').child(auth.uid).child('role').val() === 'system_admin'",
        ".write": "$uid === auth.uid || root.child('users').child(auth.uid).child('role').val() === 'system_admin'"
      }
    },
    "meters": {
      ".read": "auth != null",
      "$meterId": {
        ".write": "auth != null"
      }
    },
    "alerts": {
      "$uid": {
        ".read": "$uid === auth.uid",
        ".write": "$uid === auth.uid"
      }
    },
    "properties": {
      "$uid": {
        ".read": "$uid === auth.uid",
        ".write": "$uid === auth.uid"
      }
    },
    "messages": {
      "$uid": {
        ".read": "$uid === auth.uid",
        ".write": "$uid === auth.uid"
      }
    },
    "reviews": {
      ".read": true,
      ".write": "auth != null"
    }
  }
}
```

Click **Publish**.

> ⚠️ **Never use `".read": true, ".write": true` in production.** It exposes your entire database to the internet. The rules above require authentication for all sensitive paths.

### 5. Get the Web SDK config

1. **Project Settings** (gear icon) → **General**
2. Scroll to **Your apps** → click the **Web** icon (`</>`)
3. Register the app (nickname: "Smart Water Guardian Web")
4. Copy the values from the generated `firebaseConfig` object:

```js
const firebaseConfig = {
  apiKey: "AIzaSy...",
  authDomain: "your-project.firebaseapp.com",
  databaseURL: "https://your-project-default-rtdb.firebaseio.com",
  projectId: "your-project-id",
  storageBucket: "your-project.appspot.com",
  messagingSenderId: "123456789",
  appId: "1:123456789:web:abc123"
};
```

5. Paste each value into `.env`:

```env
FIREBASE_API_KEY=AIzaSy...
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_DATABASE_URL=https://your-project-default-rtdb.firebaseio.com
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=123456789
FIREBASE_APP_ID=1:123456789:web:abc123
```

### 6. Download the Admin SDK service account key

Required for server-side user deletion (`api/delete-user.php`).

1. **Project Settings** → **Service Accounts**
2. Click **Generate new private key**
3. Confirm → a `.json` file downloads
4. Save it as `config/firebase-service-account.json` in the project

Verify it's in `.gitignore`:

```bash
git check-ignore config/firebase-service-account.json
```

**Expected output:**

```
config/firebase-service-account.json
```

> 🔒 **Never commit this file.** It grants full admin access to your Firebase project.

---

## 📧 Email (SMTP) Setup

Smart Water Guardian sends transactional emails for:
- Registration confirmation
- Account approval / rejection
- Bill reminders
- Admin messages

### Gmail (Recommended for development)

1. **Enable 2-Factor Authentication** on your Google account:
   [myaccount.google.com/security](https://myaccount.google.com/security)

2. **Generate an App Password:**
   - Go to [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
   - App: **Mail** · Device: **Other** → name it "Smart Water Guardian"
   - Copy the 16-character password (remove spaces)

3. **Add to `.env`:**

   ```env
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_SECURE=tls
   SMTP_USERNAME=your-email@gmail.com
   SMTP_PASSWORD=abcd efgh ijkl mnop      # ← 16-char app password
   SMTP_FROM_EMAIL=your-email@gmail.com
   SMTP_FROM_NAME="Smart Water Guardian"
   ```

### Alternative SMTP providers

| Provider | Host | Port | Secure |
|----------|------|------|--------|
| Gmail | `smtp.gmail.com` | 587 | tls |
| Outlook | `smtp-mail.outlook.com` | 587 | tls |
| Yahoo | `smtp.mail.yahoo.com` | 587 | tls |
| SendGrid | `smtp.sendgrid.net` | 587 | tls |
| Mailgun | `smtp.mailgun.org` | 587 | tls |

### Test that emails work

Register a new account through the app → check the recipient inbox → if no email arrives, see [Troubleshooting → Email not sending](#email-not-sending).

---

## 🔌 ESP32 Hardware Setup

**This section is optional.** You can run the entire web application without hardware by manually injecting test data into Firebase (see [Simulating data](#simulating-esp32-data) below).

For full hardware documentation, see [`docs/README-ESP32.md`](docs/README-ESP32.md).

### Quick hardware setup

**Components:**
- ESP32 Dev Module
- YF-S201 flow sensor
- Jumper wires + USB data cable

**Wiring:**

| YF-S201 | ESP32 |
|---------|-------|
| Red (VCC) | 3.3V |
| Black (GND) | GND |
| Yellow (Signal) | GPIO34 |

**Software:**
1. Install [Arduino IDE 2.x](https://www.arduino.cc/en/software)
2. Add ESP32 board package (see [README-ESP32.md](docs/README-ESP32.md))
3. Install library: **Firebase ESP32 Client** by mobizt
4. Create `secrets.h` with your credentials (see below)
5. Flash the firmware

### Creating `secrets.h`

**Do not hardcode credentials in the firmware.** Create `secrets.h` alongside the `.ino` file:

```cpp
// secrets.h — DO NOT COMMIT
#ifndef SECRETS_H
#define SECRETS_H

#define WIFI_SSID       "YourWiFiName"
#define WIFI_PASSWORD   "YourWiFiPassword"

#define FIREBASE_HOST   "https://your-project-default-rtdb.firebaseio.com"
#define FIREBASE_AUTH   "YourDatabaseSecret"   // NOT the API key

#define METER_ID        "MTR-2026-0001"

#endif
```

Then in the firmware:

```cpp
#include "secrets.h"
```

Add to `.gitignore`:

```
secrets.h
```

### Getting the Database Secret

The ESP32 writes to Firebase using the **Database Secret** (not the web API key).

1. Firebase Console → **Project Settings** → **Service Accounts**
2. Tab: **Database Secrets**
3. Click **Show** → copy → paste into `secrets.h`

### Simulating ESP32 data

If you don't have hardware, inject test data manually.

1. Open [Firebase Console](https://console.firebase.google.com/)
2. Navigate to **Realtime Database**
3. Create this path manually:

```
meters/
└── MTR-2026-0001/
    └── lastReading/
        ├── flow: 12.5
        ├── volume: 350.2
        ├── pressure: 4.6
        ├── status: "online"
        └── timestamp: "2026-09-10T10:30:00Z"
```

4. Register a consumer with meter number `MTR-2026-0001`
5. The dashboard will now show this data

You can manually update `flow`/`volume` in the Firebase Console to see the dashboard update in real time.

---

## 🔐 First Login & Admin Creation

### Option A — Auto-admin via `.env` (easiest)

1. In `.env`, set:
   ```env
   ADMIN_EMAILS=admin@smartwater.com
   ```
2. Register at `/pages/register.php` with exactly `admin@smartwater.com`
3. The account is auto-approved as **System Admin**
4. Log in → you land on the admin panel

### Option B — Manual promotion in MySQL

1. Register normally through `/pages/register.php`
2. Find your Firebase UID:
   - Firebase Console → **Authentication** → **Users** → copy UID
3. Run in MySQL:

```sql
UPDATE users
SET is_approved = 1, role = 'system_admin'
WHERE email = 'your-email@example.com';
```

4. Log out and log back in → you now have admin access

### Option C — Direct SQL insert (advanced)

If Firebase registration is broken, insert directly:

```sql
INSERT INTO users (
    firebase_uid, email, first_name, last_name,
    role, is_active, is_approved
) VALUES (
    'REPLACE_WITH_FIREBASE_UID',
    'admin@smartwater.com',
    'System', 'Admin',
    'system_admin', 1, 1
);
```

You still need to create the matching Firebase Auth user with the same UID — otherwise login will fail.

---

## 🧪 Running the Automated Tests

The test suite is required for the Sprint 5-6 submission. Run it after setup to verify your environment.

### Run all tests (custom runner)

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

The 2 failing tests are documented as **BUG-014** (LOW severity, cosmetic).

### Run with PHPUnit

```bash
vendor/bin/phpunit
```

### Run a specific test file

```bash
vendor/bin/phpunit tests/BillingTest.php
vendor/bin/phpunit tests/LoginTest.php
vendor/bin/phpunit tests/AdminTest.php
vendor/bin/phpunit tests/UserRegistrationTest.php
```

### Generate an HTML report

```bash
php tests/generate-test-results.php
```

Then open `test-results.html` in your browser.

### Generate code coverage (requires Xdebug)

Check if Xdebug is installed:

```bash
php -m | grep xdebug
```

If yes:

```bash
vendor/bin/phpunit --coverage-html coverage/
```

Then open `coverage/index.html`.

If Xdebug is **not** installed, do not fabricate coverage — report that coverage is unavailable in this environment.

---

## ✅ Verifying the Installation

Run this checklist after setup. Every step should succeed.

### 1. PHP ↔ MySQL

```bash
php -r "require 'config/database.php'; echo 'DB OK: ' . \$conn->query('SELECT COUNT(*) FROM users')->fetch_row()[0] . ' users';"
```

Expected: `DB OK: N users`

### 2. Landing page

Open: [http://localhost/SmartWaterGuardian/](http://localhost/SmartWaterGuardian/)

Expected: animated water splash → hero → testimonials → features

### 3. Registration flow

Open: [http://localhost/SmartWaterGuardian/pages/register.php](http://localhost/SmartWaterGuardian/pages/register.php)

Expected: form submits → "pending approval" overlay appears → user created in Firebase

### 4. Login flow

Open: [http://localhost/SmartWaterGuardian/pages/login.php](http://localhost/SmartWaterGuardian/pages/login.php)

Expected: role selection → credentials → login → redirect to dashboard (or admin)

### 5. Dashboard

Open: [http://localhost/SmartWaterGuardian/pages/dashboard.php](http://localhost/SmartWaterGuardian/pages/dashboard.php)

Expected: live flow rate, volume, pressure (real or simulated from Firebase)

### 6. Admin panel (admin users only)

Open: [http://localhost/SmartWaterGuardian/pages/admin.php](http://localhost/SmartWaterGuardian/pages/admin.php)

Expected: stats, user tabs, pending approvals, charts

### 7. Firebase write

Firebase Console → **Realtime Database** → look for:

```
users/<your-uid>/
meters/MTR-2026-0001/lastReading/
```

Expected: your registered user and any meter data

### 8. Test suite

```bash
php tests/run-tests.php
```

Expected: 53+/55 passing

If **all 8 checks pass**, your installation is complete.

---

## 🔨 Common Tasks

### Reset the database

```bash
mysql -u root -p -e "DROP DATABASE smart_water_guardian;"
mysql -u root -p -e "CREATE DATABASE smart_water_guardian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p smart_water_guardian < database/schema.sql
```

### Backup the database

```bash
mysqldump -u root -p smart_water_guardian > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore from backup

```bash
mysql -u root -p smart_water_guardian < backup_20260910_143000.sql
```

### Clear Composer cache

```bash
composer clear-cache
```

### Reinstall Composer dependencies

```bash
rm -rf vendor/
composer install
```

### Check for security issues in Composer packages

```bash
composer audit
```

### Update a single Composer package

```bash
composer update phpmailer/phpmailer
```

### View the last 20 log entries (if logging enabled)

```bash
tail -n 20 logs/error.log
```

### Pull the latest changes from a specific tag

```bash
git fetch --tags
git checkout Sprint-5-6-MVP
```

---

## 🛠 Troubleshooting

### Database connection failed

**Symptoms:** `Database connection failed` on any page.

**Fixes:**
1. Confirm MySQL is running:
   - XAMPP → green **Running** next to MySQL
   - Linux: `sudo systemctl status mysql`
2. Verify `.env` credentials match your MySQL setup
3. Test manually:
   ```bash
   mysql -u root -p -e "SELECT 1;"
   ```
4. Check the `users` table exists:
   ```bash
   mysql -u root -p smart_water_guardian -e "SHOW TABLES;"
   ```

### Port 80 already in use

**Symptoms:** Apache won't start; "Address already in use".

**Fixes:**
1. Identify the conflicting process:
   - Windows: `netstat -ano | findstr :80`
   - macOS/Linux: `sudo lsof -i :80`
2. Either stop the conflicting process or change Apache's port:
   - XAMPP → **Config** → **Apache (httpd.conf)**
   - `Listen 80` → `Listen 8080`
   - Restart Apache
   - Update `.env`: `APP_URL=http://localhost:8080/SmartWaterGuardian`

### Composer install fails

**Symptoms:** TLS errors, authentication errors, or "package not found".

**Fixes:**
```bash
composer clear-cache
composer diagnose
composer install --no-scripts -vvv
```

If SSL errors persist on Windows, [enable OpenSSL in `php.ini`](https://getcomposer.org/doc/articles/troubleshooting.md#ssl-certificate-problems).

### Firebase authentication fails

**Symptoms:** Login button does nothing, or console shows Firebase errors.

**Fixes:**
1. Open browser DevTools (**F12**) → **Console**
2. Look for `auth/` error codes:
   - `auth/api-key-not-valid` → wrong `FIREBASE_API_KEY` in `.env`
   - `auth/operation-not-allowed` → enable Email/Password in Firebase Console
   - `auth/unauthorized-domain` → add `localhost` to Firebase Console → **Authentication** → **Settings** → **Authorized domains**
3. Verify `.env` values exactly match Firebase Console

### Firebase not updating on dashboard

**Symptoms:** Dashboard shows "Waiting for data..." even after ESP32 or manual injection.

**Fixes:**
1. Open DevTools → **Console** → check for Firebase database errors
2. Verify `FIREBASE_DATABASE_URL` ends with `.firebaseio.com` (no trailing slash)
3. Check Firebase Realtime Database rules allow read
4. In Firebase Console → **Realtime Database** → confirm data exists at:
   ```
   meters/MTR-2026-0001/lastReading
   ```
5. Confirm the meter ID matches your registered property's `meterId`

### Email not sending

**Symptoms:** No emails received; no error in UI.

**Fixes:**
1. Verify you're using a **Gmail App Password**, not your Gmail account password
2. Confirm 2FA is enabled on your Google account
3. Check PHP error log:
   ```bash
   tail -n 50 /path/to/php-error.log
   ```
4. Manually test SMTP with a small script:
   ```php
   <?php
   require 'vendor/autoload.php';
   $m = new PHPMailer\PHPMailer\PHPMailer(true);
   $m->isSMTP();
   $m->Host = 'smtp.gmail.com';
   $m->SMTPAuth = true;
   $m->Username = 'your-email@gmail.com';
   $m->Password = 'your-app-password';
   $m->SMTPSecure = 'tls';
   $m->Port = 587;
   $m->setFrom('your-email@gmail.com', 'Test');
   $m->addAddress('your-email@gmail.com');
   $m->Subject = 'Test';
   $m->Body = 'It works!';
   $m->send();
   echo "Sent!\n";
   ```
5. Some hosts block outbound port 587 — try port **465** with `SMTP_SECURE=ssl`

### File permission errors (Linux/macOS)

**Symptoms:** "Failed to open stream: Permission denied".

**Fixes:**
```bash
# Determine your web user
# XAMPP: daemon  ·  Ubuntu: www-data  ·  macOS Homebrew: _www
sudo chown -R $USER:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 config/ reports/ logs/ 2>/dev/null
sudo chmod 600 .env
```

### "Class 'PHPMailer\PHPMailer\PHPMailer' not found"

**Symptoms:** Fatal error on email-sending code.

**Fixes:**
1. Reinstall Composer dependencies:
   ```bash
   composer install
   ```
2. Verify the file exists:
   ```bash
   ls vendor/phpmailer/phpmailer/src/PHPMailer.php
   ```
3. Check that `require 'vendor/autoload.php';` is at the top of any file using PHPMailer

### Tests fail with "database not found"

**Symptoms:** `php tests/run-tests.php` errors on bootstrap.

**Fixes:**
1. Tests use the same DB as the app. Ensure the DB exists:
   ```bash
   mysql -u root -p -e "SHOW DATABASES LIKE 'smart_water_guardian';"
   ```
2. If missing, run the schema import:
   ```bash
   mysql -u root -p -e "CREATE DATABASE smart_water_guardian;"
   mysql -u root -p smart_water_guardian < database/schema.sql
   ```
3. Tests do not require Firebase to be configured

### ESP32 not connecting to WiFi

**Symptoms:** Serial Monitor shows `Connecting to WiFi...` then restart.

**Fixes:**
1. Verify SSID and password are correct (case-sensitive)
2. Move closer to the router
3. ESP32 only supports 2.4 GHz WiFi — not 5 GHz
4. Check `WiFi.begin()` output for the actual failure reason

### ESP32 not writing to Firebase

**Symptoms:** Serial Monitor shows `Send failed: ...`

**Fixes:**
1. Verify `FIREBASE_HOST` has no trailing slash
2. Verify `FIREBASE_AUTH` is the **Database Secret**, not the API key:
   - Console → **Project Settings** → **Service Accounts** → **Database Secrets**
3. Confirm Realtime Database rules allow authenticated writes
4. Test the Firebase rules with the Firebase Console rules simulator

### Reset everything (nuclear option)

If nothing else works:

```bash
# 1. Reset database
mysql -u root -p -e "DROP DATABASE smart_water_guardian;"
mysql -u root -p -e "CREATE DATABASE smart_water_guardian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p smart_water_guardian < database/schema.sql

# 2. Reset Composer
rm -rf vendor/ composer.lock
composer install

# 3. Reset .env from template
cp .env.example .env
# (edit .env with valid values)

# 4. Restart server
# XAMPP: restart Apache + MySQL in Control Panel
# PHP built-in: Ctrl+C, then: php -S localhost:8000
```

---

## 🛑 Stopping & Cleanup

### Stop XAMPP

XAMPP Control Panel → **Stop** Apache → **Stop** MySQL

### Stop PHP built-in server

In the terminal running the server: **Ctrl + C**

### Stop Apache (systemd)

```bash
sudo systemctl stop apache2
sudo systemctl stop mysql
```

### Before shutting down MySQL — backup

```bash
mysqldump -u root -p smart_water_guardian > backup_$(date +%Y%m%d).sql
```

### Fully uninstall (if desired)

```bash
# Remove the project
rm -rf /path/to/SmartWaterGuardian

# Drop the database
mysql -u root -p -e "DROP DATABASE smart_water_guardian;"

# Uninstall XAMPP via your OS's uninstaller (if desired)
```

---

## 🎴 Quick Reference Card

| Task | Command |
|------|---------|
| Clone repo | `git clone <url> && cd SmartWaterGuardian` |
| Install deps | `composer install` |
| Copy env | `cp .env.example .env` |
| Create DB | `mysql -u root -p -e "CREATE DATABASE smart_water_guardian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"` |
| Import schema | `mysql -u root -p smart_water_guardian < database/schema.sql` |
| Load demo data | `mysql -u root -p smart_water_guardian < database/seed.sql` |
| Start PHP server | `php -S localhost:8000` |
| Open app | `http://localhost/SmartWaterGuardian/` (XAMPP)<br>`http://localhost:8000` (PHP) |
| Run tests | `php tests/run-tests.php` |
| PHPUnit | `vendor/bin/phpunit` |
| Test HTML report | `php tests/generate-test-results.php` |
| Coverage (Xdebug) | `vendor/bin/phpunit --coverage-html coverage/` |
| Backup DB | `mysqldump -u root -p smart_water_guardian > backup.sql` |
| Restore DB | `mysql -u root -p smart_water_guardian < backup.sql` |
| Check secrets not tracked | `git ls-files \| grep -E '\.env$\|firebase-service-account'` |
| View recent commits | `git log --oneline -10` |
| Checkout release tag | `git checkout Sprint-5-6-MVP` |
| Check Apache port | `netstat -ano \| findstr :80` (Win)<br>`sudo lsof -i :80` (Unix) |

---

## 📞 Support

- **Project README:** [`README.md`](README.md)
- **ESP32 Guide:** [`docs/README-ESP32.md`](docs/README-ESP32.md)
- **Bug Register:** [`docs/BUG-REGISTER.md`](docs/BUG-REGISTER.md)
- **Email:** support@smartwater.co.za

---

**Built with 💧 for South Africa**

*Smart Water Guardian · Code Crew Innovators · University of Johannesburg · Sprint 5-6 MVP · 2026*
