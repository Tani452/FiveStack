SET FOREIGN_KEY_CHECKS = 0;

-- ========================================================
-- ClubHub - Database Schema
-- Database: clubhub
-- Tables: users, clubs, club_members, venues, events,
--         bookings, rsvps, announcements, budget_requests,
--         expenses, tasks
-- ========================================================

CREATE DATABASE IF NOT EXISTS clubhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clubhub;

-- --------------------------------------------------------
-- 1. Users Table
-- Roles: admin (faculty coordinator), club_head, member, guest
-- --------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'club_head', 'member', 'guest') NOT NULL DEFAULT 'guest',
    phone VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Clubs Table
-- Extracurricular college clubs and societies
-- --------------------------------------------------------
DROP TABLE IF EXISTS clubs;
CREATE TABLE clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(50) NOT NULL DEFAULT 'General',
    head_id INT DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clubs_head FOREIGN KEY (head_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Club Members Table
-- Student memberships with approval lifecycle
-- --------------------------------------------------------
DROP TABLE IF EXISTS club_members;
CREATE TABLE club_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_club_member (club_id, user_id),
    CONSTRAINT fk_cm_club FOREIGN KEY (club_id) REFERENCES clubs (id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Venues Table
-- Physical campus spaces bookable for activities
-- --------------------------------------------------------
DROP TABLE IF EXISTS venues;
CREATE TABLE venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    capacity INT NOT NULL DEFAULT 50,
    location VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Events Table
-- Club events requiring faculty coordinator sanction
-- --------------------------------------------------------
DROP TABLE IF EXISTS events;
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    venue_id INT DEFAULT NULL,
    status ENUM('draft', 'pending', 'approved', 'rejected', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    admin_remarks TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_club FOREIGN KEY (club_id) REFERENCES clubs (id) ON DELETE CASCADE,
    CONSTRAINT fk_events_venue FOREIGN KEY (venue_id) REFERENCES venues (id) ON DELETE SET NULL,
    CONSTRAINT fk_events_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Bookings Table
-- Venue timeslot bookings with clash detection support
-- --------------------------------------------------------
DROP TABLE IF EXISTS bookings;
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    event_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_venue FOREIGN KEY (venue_id) REFERENCES venues (id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. RSVPs Table
-- Student event registration and attendance status
-- --------------------------------------------------------
DROP TABLE IF EXISTS rsvps;
CREATE TABLE rsvps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('attending', 'interested', 'cancelled') NOT NULL DEFAULT 'attending',
    rsvp_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_event_user_rsvp (event_id, user_id),
    CONSTRAINT fk_rsvps_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
    CONSTRAINT fk_rsvps_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Announcements Table
-- Campus-wide (club_id IS NULL) or club-specific notices
-- --------------------------------------------------------
DROP TABLE IF EXISTS announcements;
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_announcements_club FOREIGN KEY (club_id) REFERENCES clubs (id) ON DELETE CASCADE,
    CONSTRAINT fk_announcements_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Budget Requests Table
-- Club budget allocations submitted for administrative approval
-- --------------------------------------------------------
DROP TABLE IF EXISTS budget_requests;
CREATE TABLE budget_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_remarks TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_br_club FOREIGN KEY (club_id) REFERENCES clubs (id) ON DELETE CASCADE,
    CONSTRAINT fk_br_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. Expenses Table
-- Actual expenditures logged against budgets
-- --------------------------------------------------------
DROP TABLE IF EXISTS expenses;
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    budget_request_id INT DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    receipt_file VARCHAR(255) DEFAULT NULL,
    expense_date DATE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expenses_club FOREIGN KEY (club_id) REFERENCES clubs (id) ON DELETE CASCADE,
    CONSTRAINT fk_expenses_br FOREIGN KEY (budget_request_id) REFERENCES budget_requests (id) ON DELETE SET NULL,
    CONSTRAINT fk_expenses_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 11. Tasks Table
-- Team tasks and event action items
-- --------------------------------------------------------
DROP TABLE IF EXISTS tasks;
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    event_id INT DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    assigned_to INT DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    status ENUM('todo', 'in_progress', 'completed') NOT NULL DEFAULT 'todo',
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_club FOREIGN KEY (club_id) REFERENCES clubs (id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE SET NULL,
    CONSTRAINT fk_tasks_assignee FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_tasks_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restore foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
