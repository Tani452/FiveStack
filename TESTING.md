# ClubHub - Master Testing & Quality Assurance Matrix (Phase 11)

This document contains test cases validating functional requirements, client-side interactivity, API endpoints, the React calendar widget, the Django reports module, and security hardening across ClubHub.

---

## 1. Authentication & Role-Based Access Control (RBAC)

| ID | Feature / Component | Steps to Execute | Expected Result | Actual Result | Status |
|:---|:---|:---|:---|:---|:---:|
| **TC01** | Student Registration | 1. Navigate to `pages/register.php`.<br>2. Fill in valid name, unique email, password (&gt;=8 chars), and phone.<br>3. Submit form. | Account created with default role `'member'`; user redirected to `login.php` with success message. | User registered with role `'member'` in MySQL database; success flash message shown. | **PASS** |
| **TC02** | Registration Validation | 1. Attempt to register with mismatched passwords or existing email.<br>2. Submit form. | Registration halted; server-side error messages displayed beside offending fields. | Errors displayed correctly even with JavaScript disabled; database unaffected. | **PASS** |
| **TC03** | Password Hashing | 1. Inspect `users` table in database after registration. | Password stored as a strong bcrypt hash (`$2y$10$...`); no plaintext passwords. | Passwords hashed using native `password_hash(..., PASSWORD_DEFAULT)`. | **PASS** |
| **TC04** | User Login | 1. Go to `pages/login.php`.<br>2. Enter `admin@college.edu` and `password123`.<br>3. Submit form. | Authenticates successfully; session ID regenerated (`session_regenerate_id(true)`); redirected to Admin dashboard. | Logged in as Admin; session regenerated; header shows user role badge. | **PASS** |
| **TC05** | Role-Based Dashboards | 1. Log in sequentially as Admin, Club Head (`techhead@college.edu`), and Member (`rohan@college.edu`). | Each user role sees a customized dashboard tailored to their specific operational duties. | Admin sees approvals & Django launcher; Club Head sees roster & proposals; Member sees enrolled clubs & events. | **PASS** |
| **TC06** | Route Guarding (`require_role`) | 1. Log in as a Member (`rohan@college.edu`).<br>2. Manually type `pages/club-form.php` in browser address bar. | Access denied; user redirected to `dashboard.php` with an alert message. | Protected page rejected non-permitted role; access denied flash alert displayed. | **PASS** |
| **TC07** | Secure Logout | 1. Click **Logout** in the navigation bar. | Session cleared and destroyed; redirected to `login.php`. | Session variables unset, session destroyed, cookie invalidated. | **PASS** |

---

## 2. Dynamic Clubs, Events & Venues (CRUD)

| ID | Feature / Component | Steps to Execute | Expected Result | Actual Result | Status |
|:---|:---|:---|:---|:---|:---:|
| **TC08** | Club Creation & Editing | 1. Log in as Admin.<br>2. Visit `pages/club-form.php`.<br>3. Enter unique club name, description, category, and assign Club Head.<br>4. Save. | Club created in `clubs` table; appears on `pages/clubs.php`. | Club stored via prepared statement; visible in directory with category tag. | **PASS** |
| **TC09** | Venue Management | 1. Log in as Admin.<br>2. Visit `pages/venue-form.php`.<br>3. Enter name, capacity (e.g., 150), and campus location.<br>4. Save. | Venue added to `venues` table; available in event booking dropdowns. | Venue saved with capacity bounds validation; appears on `pages/venues.php`. | **PASS** |
| **TC10** | Event Scheduling Proposal | 1. Log in as Club Head (`techhead@college.edu`).<br>2. Go to `pages/event-form.php`.<br>3. Fill title, venue, future date, start/end times, and description.<br>4. Submit. | Event created with status `'pending'`; appears on Club Head and Admin dashboards. | Event saved with status `'pending'`; faculty coordinator alerted. | **PASS** |
| **TC11** | Server-Side Venue Clash Prevention | 1. As Club Head, attempt to book a venue already booked by another club on the same date and overlapping time.<br>2. Submit form. | Server-side validation catches the clash; rejects submission with detailed conflict details. | Conflict blocked before saving; flash error displayed showing conflicting event and timeslot. | **PASS** |

---

## 3. Core Club Workflows

| ID | Feature / Component | Steps to Execute | Expected Result | Actual Result | Status |
|:---|:---|:---|:---|:---|:---:|
| **TC12** | Membership Join Application | 1. Log in as Member (`rohan@college.edu`).<br>2. Go to `pages/clubs.php` and click **+ Apply to Join** on a club. | Record inserted into `club_members` with status `'pending'`; button updates to "Application Pending". | Join request created; Club Head sees application on their dashboard. | **PASS** |
| **TC13** | Membership Approval | 1. Log in as Club Head for that club.<br>2. On dashboard, review pending member application and click **Approve**. | Status updated to `'approved'`; applicant added to official club roster. | Member approved; member count incremented in public club directory. | **PASS** |
| **TC14** | Event Sanction Workflow | 1. Log in as Admin.<br>2. On dashboard, review pending event proposal.<br>3. Enter coordinator remarks and click **Approve Event**. | Event status updated to `'approved'`; venue booking confirmed in `bookings`; published to campus calendar. | Status updated to `'approved'`; bookings synced; event visible to public visitors. | **PASS** |
| **TC15** | Event Rejection Workflow | 1. Log in as Admin.<br>2. Review another pending event proposal, add comments, and click **Reject**. | Event status changed to `'rejected'`; any tentative bookings cleared; remarks saved. | Event marked `'rejected'`; feedback displayed on event details view. | **PASS** |
| **TC16** | Budget Allocation Request | 1. Log in as Club Head.<br>2. Visit `pages/budget-form.php`.<br>3. Submit request for $500.00 with justification. | Budget request saved with status `'pending'`; Admin dashboard alerted. | Budget proposal created; visible on `pages/budget.php`. | **PASS** |
| **TC17** | Budget Approval | 1. Log in as Admin.<br>2. On `pages/budget.php`, click **Approve** on the request. | Status updated to `'approved'`; budget remaining counter updated. | Request approved; funds unlocked for expense logging. | **PASS** |
| **TC18** | Expense Logging & Receipt Upload | 1. Log in as Club Head.<br>2. Visit `pages/expense-form.php`.<br>3. Link to approved budget, enter amount ($120), and upload a valid PDF or JPG receipt (&lt;2 MB).<br>4. Submit. | File inspected for MIME type, renamed with random hash into `uploads/receipts/`, and expense saved. | Receipt saved safely; remaining budget dynamically decremented. | **PASS** |
| **TC19** | Malicious File Upload Rejection | 1. On `pages/expense-form.php`, attempt to upload an executable script (`test.php`) or file &gt;2 MB. | Upload rejected with error; file not stored on server. | Extension and MIME check caught invalid file; rejected with flash alert. | **PASS** |

---

## 4. Asynchronous AJAX Engine (`api/` & `ajax.js`)

| ID | Feature / Component | Steps to Execute | Expected Result | Actual Result | Status |
|:---|:---|:---|:---|:---|:---:|
| **TC20** | Debounced Live Event Search | 1. On `pages/events.php`, type "Hack" into the search field. | After 300 ms debounce, events filter dynamically without a page reload; loading spinner shown during fetch. | Results updated in DOM; non-matching events hidden; spinner cleared. | **PASS** |
| **TC21** | Live Club Directory Search | 1. On `pages/clubs.php`, type "Code" into search input. | Club list updates dynamically to show matching societies. | Debounced request to `api/search_events.php?type=clubs` rendered results instantly. | **PASS** |
| **TC22** | Asynchronous One-Click RSVP | 1. Log in as Member.<br>2. On `pages/events.php`, click **RSVP / Attend** on an approved event. | Button updates to **Attending ✓ (Cancel)**; attendee count increments; toast notification appears without reload. | POST request to `api/rsvp.php` processed; database updated; live count updated in place. | **PASS** |
| **TC23** | RSVP Cancellation | 1. Click **Attending ✓ (Cancel)** on the previously RSVPed event. | Button reverts to **RSVP / Attend**; attendee count decrements; toast alert shown. | Status updated to `'cancelled'`; counter updated seamlessly. | **PASS** |
| **TC24** | Unauthenticated RSVP Protection | 1. Log out.<br>2. Directly send POST request to `api/rsvp.php`. | Endpoint responds with `HTTP 401 Unauthorized`. | Returns `401 Unauthorized` with JSON error payload. | **PASS** |
| **TC25** | Real-Time Venue Clash Checker | 1. On `pages/event-form.php`, select **Main Auditorium**, date `2026-11-22`, time `17:30` to `20:30`. | Inline warning box appears immediately below venue selector: `⚠️ Booking Conflict: Venue Clash...` | `api/check_venue.php` returned `HTTP 409 Conflict`; conflict highlighted in red before submission. | **PASS** |
| **TC26** | Real-Time Venue Available Check | 1. Change the date to an open slot (e.g. `2027-10-10`, `10:00` - `12:00`). | Inline box turns green: `✓ Available: Main Auditorium is available...`. | `api/check_venue.php` returned `HTTP 200 OK`; input styled green. | **PASS** |
| **TC27** | Navbar Notification Badge Polling | 1. Log in as Admin.<br>2. Observe navbar **Dashboard** link. | Red badge shows count of pending items; polls `api/notifications.php` every 30 seconds. | Badge displays count; updates automatically in background. | **PASS** |

---

## 5. React Event Calendar Component (`react-widget/`)

| ID | Feature / Component | Steps to Execute | Expected Result | Actual Result | Status |
|:---|:---|:---|:---|:---|:---:|
| **TC28** | Approved Events API Feed | 1. Request `http://localhost/clubhub/api/events.php`. | Returns clean JSON payload containing all approved events, dates, and attendee counts. | Valid JSON with `HTTP 200 OK` and CORS headers returned. | **PASS** |
| **TC29** | React Host Embedding | 1. Open `pages/events.php`. | Calendar widget mounts into `<div id="calendar-root"></div>` without interfering with PHP page. | Widget rendered above search form; PHP cards render normally below. | **PASS** |
| **TC30** | Calendar Month Navigation | 1. Click `< Prev` and `Next >` buttons on the calendar header. | Calendar month transitions smoothly; updates day numbers and weekday alignments. | Current month updates; recalculates previous/next padding days. | **PASS** |
| **TC31** | Date Selection & Day Inspector | 1. Click on a date with an event badge (e.g. Nov 15, Nov 22). | Right-hand panel immediately lists that day's events with time pills, club badge, venue, and "View Event" button. | Selected day events displayed dynamically; "View Event" links directly to detail view. | **PASS** |
| **TC32** | Category Filter in Calendar | 1. Click category pill (e.g., "Technical"). | Calendar grid and day inspector filter to show only Technical club events. | Non-technical events filtered out; badge counts updated. | **PASS** |

---

## 6. Django Reports & Analytics Module (`django-reports/`)

| ID | Feature / Component | Steps to Execute | Expected Result | Actual Result | Status |
|:---|:---|:---|:---|:---|:---:|
| **TC33** | Read-Only DB Connection | 1. Start Django server: `python manage.py runserver 8000`.<br>2. Navigate to `http://localhost:8000/`. | Connects to MySQL using `clubhub_reader`; displays KPI cards and charts without modifying tables. | Models loaded with `managed = False`; read-only user access confirmed. | **PASS** |
| **TC34** | Events per Club Chart | 1. View "Events Organized per Club" chart on dashboard. | Bar chart visualizes total events vs approved events per student society using Chart.js. | Chart rendered responsively with interactive tooltips and legend. | **PASS** |
| **TC35** | RSVPs per Event Chart | 1. View "Confirmed RSVPs per Event" chart. | Bar chart displays student attendance numbers per event. | Chart rendered with confirmed attendance counts. | **PASS** |
| **TC36** | Budget Comparison Chart | 1. View "Club Budget Comparison" chart. | Multi-dataset grouped bar chart compares Requested vs Approved vs Spent for each club. | Financial comparison rendered accurately with dollar-formatted tooltips. | **PASS** |
| **TC37** | CSV Data Export | 1. Click **📥 Export CSV** button on dashboard. | Browser downloads `clubhub_reports_export.csv` containing consolidated analytical tables. | Download initiated; valid CSV file returned with `HTTP 200` and `Content-Type: text/csv`. | **PASS** |
| **TC38** | Return to PHP Portal | 1. Click **&larr; Return to ClubHub PHP Portal** in Django header. | Navigates back to `http://localhost/clubhub/pages/dashboard.php`. | Navigation returns to main PHP portal seamlessly. | **PASS** |

---

## 7. Security Hardening & Server Protection

| ID | Feature / Component | Steps to Execute | Expected Result | Actual Result | Status |
|:---|:---|:---|:---|:---|:---:|
| **TC39** | Directory Indexing Protection | 1. In browser, visit `http://localhost/clubhub/assets/` or `http://localhost/clubhub/config/`. | Directory listing forbidden (`HTTP 403 Forbidden`). | `.htaccess` disabled indexes (`Options -Indexes`); access blocked. | **PASS** |
| **TC40** | Direct Config/Includes Access | 1. Attempt to request `http://localhost/clubhub/config/db.php` or `http://localhost/clubhub/includes/auth.php`. | Server returns `HTTP 403 Forbidden`. | Rewrite rule and subdirectory `.htaccess` blocked direct web access. | **PASS** |
| **TC41** | Log File Access Block | 1. Attempt to request `http://localhost/clubhub/logs/error.log`. | Server returns `HTTP 403 Forbidden`. | Direct web access to internal error log rejected. | **PASS** |
| **TC42** | Script Execution in Uploads | 1. Place a `.php` file in `uploads/` and attempt to execute it via browser. | Apache denies execution (`HTTP 403 Forbidden`). | `<FilesMatch>` in `uploads/.htaccess` blocks PHP execution. | **PASS** |
| **TC43** | CSRF Token Tampering | 1. Submit any POST form with an altered or missing CSRF token. | Form rejected with security verification alert; no database modifications. | Constant-time `verify_csrf_token()` failed; submission blocked. | **PASS** |
| **TC44** | XSS Payload Escaping | 1. Enter `<script>alert('xss')</script>` as an event title or announcement. | Payload stored safely and rendered as escaped text `&lt;script&gt;...`; no script executes. | Output passed through `htmlspecialchars()`; no JavaScript executed. | **PASS** |
| **TC45** | Friendly Custom Error Page | 1. Trigger an internal PHP error or PDO exception. | Visitor shown clean, friendly `pages/error.php`; technical stack trace logged to `logs/error.log`. | Display of errors disabled (`display_errors = 0`); friendly error page shown. | **PASS** |

---

## 8. Summary of Test Results

- **Total Test Cases**: 45
- **Passed**: 45
- **Failed**: 0
- **Quality Score**: 100%

