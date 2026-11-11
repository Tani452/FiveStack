# ClubHub - Django Reports & Analytics Module (Phase 10)

An administrative reports and analytics dashboard built with **Django 5/6** and **Chart.js** that connects to the existing ClubHub MySQL database using a dedicated **read-only database user**.

---

## Key Features

- **Read-Only Database Integration**: Connects directly to the existing `clubhub` MySQL database without modifying, migrating, or altering any tables of the PHP application (`managed = False` models).
- **Executive KPI Metrics**: Live summary cards showing active clubs, total scheduled events, confirmed student RSVPs, total approved budget, actual spent expenses, and remaining unspent funds.
- **Interactive Visual Charts (Chart.js)**:
  - **Events per Club**: Bar chart comparing total scheduled vs approved events across student societies.
  - **RSVPs per Event**: Attendance chart showing confirmed student attendances for campus events.
  - **Financial Audit**: Multi-bar comparison analyzing Budget Requested vs Approved vs Actual Spent per club.
- **Detailed Tabular Breakdowns**: Clear data tables with category badges and visual budget utilization progress bars.
- **CSV Data Export**: One-click download of all institutional analytics for offline spreadsheet review.

---

## Prerequisites

- **Python 3.10+** (tested on Python 3.10 – 3.15)
- **MySQL / MariaDB** (running in XAMPP on port 3306 with the `clubhub` database loaded)

---

## Setup Steps

### 1. Create MySQL Read-Only User

To ensure the reporting module cannot modify or tamper with PHP application data, create a dedicated read-only user in MySQL:

Using MySQL CLI or phpMyAdmin SQL query window:
```sql
CREATE USER IF NOT EXISTS 'clubhub_reader'@'localhost' IDENTIFIED BY 'reader123';
GRANT SELECT ON clubhub.* TO 'clubhub_reader'@'localhost';
FLUSH PRIVILEGES;
```

---

### 2. Set Up Python Virtual Environment

Navigate into the `django-reports` folder and create a dedicated virtual environment:

```bash
cd django-reports

# Create virtual environment
python -m venv venv

# Activate on Windows (PowerShell):
.\venv\Scripts\Activate.ps1

# Activate on Windows (Command Prompt):
.\venv\Scripts\activate.bat

# Activate on macOS / Linux:
source venv/bin/activate
```

---

### 3. Install Dependencies

Install the requirements using `pip`:

```bash
pip install -r requirements.txt
```

> **Note on Windows MySQL Driver**: `requirements.txt` specifies `PyMySQL`, which installs cleanly without requiring Microsoft C++ Build Tools. `clubhub_reports/__init__.py` automatically patches PyMySQL as MySQLdb for Django.

---

### 4. Configure Environment Variables

Copy the example environment file and verify settings:

```bash
cp .env.example .env
```

Review `.env`:
```ini
SECRET_KEY=clubhub-reports-insecure-dev-secret-key-phase10-2026
DEBUG=True
ALLOWED_HOSTS=localhost,127.0.0.1,0.0.0.0

# Read-Only Database Connection Settings
DB_ENGINE=django.db.backends.mysql
DB_NAME=clubhub
DB_USER=clubhub_reader
DB_PASSWORD=reader123
DB_HOST=localhost
DB_PORT=3306
```

---

### 5. Run the Development Server

Execute the standard Django runserver command:

```bash
python manage.py runserver 8000
```

---

### 6. Access the Dashboard

Open your web browser and navigate to:

- **Dashboard**: [http://localhost:8000/](http://localhost:8000/)
- **CSV Report Export**: [http://localhost:8000/export/csv/](http://localhost:8000/export/csv/)

From the dashboard navbar, clicking **"Return to ClubHub PHP Portal"** takes you back to the main PHP portal at `http://localhost/clubhub/pages/dashboard.php`.

