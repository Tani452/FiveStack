# ClubHub - College Clubs Operations Hub

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479a1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![React](https://img.shields.io/badge/React-18.3-61dafb?logo=react&logoColor=black)](https://react.dev/)
[![Vite](https://img.shields.io/badge/Vite-5.4-646cff?logo=vite&logoColor=white)](https://vitejs.dev/)
[![Django](https://img.shields.io/badge/Django-6.1-092e20?logo=django&logoColor=white)](https://www.djangoproject.com/)
[![License](https://img.shields.io/badge/License-Academic-blue.svg)]()

**ClubHub** is a college web development lab project designed as a central operations hub for university student clubs, campus venues, and activity coordination. It features a multi-tiered role system, dynamic event scheduling with venue collision prevention, financial budget approvals, receipt uploads, asynchronous live feeds, an embedded React event calendar, and a dedicated Django executive analytics module.

---

## 1. Fresh Machine Setup Guide (Step-by-Step)

Follow these steps to set up and run ClubHub on a fresh computer:

### Step 1: Install XAMPP
1. Download and install **XAMPP for Windows** (with PHP 8.1 or higher and MySQL/MariaDB) from [Apache Friends](https://www.apachefriends.org/).
2. Launch the **XAMPP Control Panel**.
3. Start both the **Apache** and **MySQL** services. Confirm the service status indicators turn green.

### Step 2: Clone or Place the Project in XAMPP Web Root
1. Place the `clubhub` project folder inside your XAMPP `htdocs` directory:
   ```text
   C:\xampp\htdocs\clubhub\
   ```
2. Open a command prompt or terminal in `C:\xampp\htdocs\clubhub`.

### Step 3: Import the MySQL Database
1. Open your terminal or phpMyAdmin at `http://localhost/phpmyadmin/`.
2. Using the MySQL command line, import the schema followed by the sample data:
   ```bash
   # Navigate to XAMPP MySQL binary directory (or run directly if mysql is in PATH)
   cd C:\xampp\mysql\bin
   
   # Import Schema and Seed Data into database 'clubhub'
   .\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS clubhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   .\mysql.exe -u root clubhub < C:\xampp\htdocs\clubhub\sql\schema.sql
   .\mysql.exe -u root clubhub < C:\xampp\htdocs\clubhub\sql\sample_data.sql
   ```
3. Create the dedicated read-only MySQL user required by the Django reporting module:
   ```bash
   .\mysql.exe -u root -e "CREATE USER IF NOT EXISTS 'clubhub_reader'@'localhost' IDENTIFIED BY 'reader123'; GRANT SELECT ON clubhub.* TO 'clubhub_reader'@'localhost'; FLUSH PRIVILEGES;"
   ```

### Step 4: Verify Database Configuration
1. Open [`config/db.php`](config/db.php) and verify the credentials match your local MySQL server:
   ```php
   $db_host = 'localhost';
   $db_port = '3306';
   $db_name = 'clubhub';
   $db_user = 'root';        // Default XAMPP MySQL user
   $db_pass = '';            // Default XAMPP MySQL password is empty
   ```

### Step 5: Launch the Application
1. Open your web browser and navigate to:
   ```text
   http://localhost/clubhub/
   ```
2. You can immediately browse clubs and events, or click **Login** and test with any of the demo accounts listed below.

---

## 2. Demo User Accounts

The database comes pre-seeded with verified test accounts for each role. All demo accounts use the password: `password123`.

| Role | Name | Email | Password | Dashboard & Permissions |
|:---|:---|:---|:---|:---|
| **Admin** | Dr. Sarah Jenkins | `admin@college.edu` | `password123` | **Faculty Coordinator**: Create/delete clubs and venues, sanction event proposals, review and approve/reject budget requests, publish campus bulletins, access Django reports. |
| **Club Head (Tech)** | Alex Turner | `techhead@college.edu` | `password123` | **Head of Coding Club**: Manage student membership applications, schedule club events, submit budget requests, log expenses with receipt uploads. |
| **Club Head (Cultural)** | Maya Patel | `culthead@college.edu` | `password123` | **Head of Cultural & Arts Society**: Manage society membership roster, propose auditorium events, track society budget. |
| **Member** | Rohan Verma | `rohan@college.edu` | `password123` | **Student Member**: Browse clubs, apply to join clubs, RSVP to campus events without page reload, view personalized dashboard notices. |
| **Guest** | Unregistered | *None* | *None* | Public visitor: Browse public club directory and event calendar, register a new student account. |

*(On the login page, handy one-click shortcut buttons automatically fill in demo credentials).*

---

## 3. Comprehensive Feature List

### Core PHP & MySQL Operations Portal
- **Role-Based Access Control (RBAC)**: Centralized authorization gates (`require_role()`, `require_login()`) enforce role hierarchy on all protected controllers, forms, and actions.
- **Dynamic Club & Venue Management**: Full CRUD capabilities for campus facilities and student societies with server-side boundary validation.
- **Event Scheduling & Clash Detection**: Automatic server-side overlap prevention ensures two clubs cannot book the same venue during overlapping timeslots.
- **Administrative Sanctions**: Club heads submit event proposals in `pending` status; the Faculty Coordinator approves or rejects with coordinator remarks.
- **Budget Requests & Expense Logging**: Club heads submit financial allocations; once approved by the admin, club heads log expenses with receipt file uploads (JPG/PNG/PDF up to 2 MB) and the system tracks remaining funds.
- **Membership Approval Workflow**: Students apply to clubs with status `pending`; the respective Club Head or Admin reviews, approves, rejects, or removes members.
- **Announcements Board**: Faculty administrators publish campus-wide bulletins; club heads post notices to their club members.
- **Friendly Custom Error Page**: Visitors never see raw PHP stack traces or database errors; all errors are logged internally to `logs/error.log`.

### Vanilla JavaScript & AJAX Features
- **Live Search with 300 ms Debounce**: Asynchronous filtering for events and clubs without page reloads using native `fetch()`.
- **Instant RSVP / Cancel Toggle**: Students can register or cancel event attendance with a single click; updates attendee count badges dynamically.
- **Real-Time Venue Availability Checker**: On the event booking form, choosing a venue, date, and timeslot instantly alerts the user if a conflict exists before form submission.
- **Polling Notification Badge**: Refreshes notification counts in the navigation bar every 30 seconds for pending approvals.
- **Client-Side Interactivity**: Accessible modal dialogs, tab switching, and mobile hamburger navigation.

### React Interactive Component (`react-widget/`)
- Built using **React 18** and **Vite**, compiled directly to `assets/react/calendar-widget.js` and `calendar-widget.css`.
- Embedded into the PHP events page at `<div id="calendar-root"></div>`.
- Features an interactive month grid with category filtering and instant date inspection listing all scheduled events for that day.
- To rebuild:
  ```bash
  cd react-widget
  npm install
  npm run build
  ```

### Django Reporting & Analytics Module (`django-reports/`)
- Built with **Django 5/6** and **Chart.js**, isolated in `django-reports/`.
- Connects to the same MySQL database via a read-only database user (`clubhub_reader`) with unmanaged models (`managed = False`).
- Generates executive KPI cards, 3 interactive charts (Events per Club, RSVPs per Event, Budget Breakdown), and downloadable CSV reports.
- To run:
  ```powershell
  cd django-reports
  .\venv\Scripts\activate
  python manage.py runserver 8000
  ```
- Accessible at: [http://localhost:8000/](http://localhost:8000/) or via the Admin Dashboard shortcut.

---

## 4. Security Highlights

ClubHub includes defense-in-depth protections documented in [`SECURITY_NOTES.md`](SECURITY_NOTES.md):
- **Prepared Statements**: 100% of queries use PDO prepared statements to eliminate SQL Injection.
- **Output Encoding**: User data is escaped via `htmlspecialchars()` using `ENT_QUOTES` and `UTF-8`.
- **CSRF Tokens**: All POST forms and AJAX endpoints validate cryptographically secure tokens.
- **Upload Hardening**: File uploads check MIME magic bytes (`finfo_file`), enforce a 2 MB limit, rename files with random hashes, and `.htaccess` disables script execution.
- **Folder Protection**: Master and subdirectory `.htaccess` files block web access to `config/`, `includes/`, `logs/`, and `sql/`, and disable directory browsing (`Options -Indexes`).
- **Session Security**: Session fixation is prevented via `session_regenerate_id(true)` upon login.

---

## 5. Testing & Verification

A structured test matrix covering all functional, architectural, and security test cases is documented in [`TESTING.md`](TESTING.md).

Deployed project link: http://clubhub.gamer.free
