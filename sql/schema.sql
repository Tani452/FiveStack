-- ========================================================
-- ClubHub: College Clubs Operations Hub
-- Database Schema and Initial Seed Data
-- ========================================================

CREATE DATABASE IF NOT EXISTS clubhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clubhub;

-- Disable foreign key checks during creation
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Users Table
-- Stores admin, club heads, members, and guests
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. Clubs Table
-- Stores college clubs and references their assigned club head
-- --------------------------------------------------------
DROP TABLE IF EXISTS clubs;
CREATE TABLE clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(50) NOT NULL DEFAULT 'General',
    head_id INT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clubs_head FOREIGN KEY (head_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. Club Memberships Table
-- Manages students joining clubs with approval status
-- --------------------------------------------------------
DROP TABLE IF EXISTS club_members;
CREATE TABLE club_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_club_user (club_id, user_id),
    CONSTRAINT fk_cm_club FOREIGN KEY (club_id) REFERENCES clubs (id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Venues Table
-- Campus venues available for club activities and events
-- --------------------------------------------------------
DROP TABLE IF EXISTS venues;
CREATE TABLE venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    capacity INT NOT NULL DEFAULT 50,
    location VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 5. Events Table
-- Club events requiring faculty coordinator (admin) approval
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
    status ENUM('draft', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_remarks TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_club FOREIGN KEY (club_id) REFERENCES clubs (id) ON DELETE CASCADE,
    CONSTRAINT fk_events_venue FOREIGN KEY (venue_id) REFERENCES venues (id) ON DELETE SET NULL,
    CONSTRAINT fk_events_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 6. Venue Bookings Table
-- Tracks reserved slots and prevents scheduling clashes
-- --------------------------------------------------------
DROP TABLE IF EXISTS venue_bookings;
CREATE TABLE venue_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    event_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vb_venue FOREIGN KEY (venue_id) REFERENCES venues (id) ON DELETE CASCADE,
    CONSTRAINT fk_vb_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 7. Announcements Table
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
    CONSTRAINT fk_ann_club FOREIGN KEY (club_id) REFERENCES clubs (id) ON DELETE CASCADE,
    CONSTRAINT fk_ann_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 8. Budget Requests Table
-- Clubs propose budgets for administrative approval
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
    CONSTRAINT fk_br_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 9. Expenses Table
-- Tracks actual spending and uploaded receipts
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
    CONSTRAINT fk_exp_club FOREIGN KEY (club_id) REFERENCES clubs (id) ON DELETE CASCADE,
    CONSTRAINT fk_exp_br FOREIGN KEY (budget_request_id) REFERENCES budget_requests (id) ON DELETE SET NULL,
    CONSTRAINT fk_exp_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enable foreign key checks back
SET FOREIGN_KEY_CHECKS = 1;

-- ========================================================
-- Seed Data
-- Default password for all seed accounts is: password123
-- Hash generated via password_hash('password123', PASSWORD_DEFAULT)
-- ========================================================

-- Users
INSERT INTO users (id, name, email, password, role, phone) VALUES
(1, 'Dr. Sarah Jenkins', 'admin@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'admin', '9876543210'),
(2, 'Alex Turner', 'techhead@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'club_head', '9876543211'),
(3, 'Maya Patel', 'culthead@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'club_head', '9876543212'),
(4, 'Rohan Verma', 'member@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'member', '9876543213'),
(5, 'Jessica Taylor', 'guest@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'guest', '9876543214');

-- Clubs
INSERT INTO clubs (id, name, description, category, head_id, status) VALUES
(1, 'Coding Club', 'Fostering competitive programming, open source software, and full-stack web development projects.', 'Technical', 2, 'active'),
(2, 'Cultural & Arts Society', 'Promoting music, drama, fine arts, dance, and creative celebrations across campus.', 'Cultural', 3, 'active'),
(3, 'Robotics & AI Society', 'Building autonomous rovers, drones, and machine learning prototypes.', 'Technical', NULL, 'active'),
(4, 'Literary & Debating Club', 'Organizing parliamentary debates, creative writing workshops, and annual college quizzes.', 'Literary', NULL, 'active');

-- Club Memberships
INSERT INTO club_members (club_id, user_id, status) VALUES
(1, 2, 'approved'),
(2, 3, 'approved'),
(1, 4, 'approved'),
(2, 4, 'pending');

-- Venues
INSERT INTO venues (id, name, capacity, location, description) VALUES
(1, 'Main Auditorium', 600, 'Central Block, Ground Floor', 'Large acoustic stage with motorized projector, podium, and theatre sound.'),
(2, 'Seminar Hall 1', 120, 'Science Wing, 2nd Floor', 'Air-conditioned tiered lecture and presentation hall with dual projectors.'),
(3, 'Computer Lab 3', 60, 'Tech Block, 1st Floor', 'High-performance computing workstations with gigabit LAN.'),
(4, 'Open Air Theatre', 800, 'North Lawn Quadrangle', 'Open outdoor amphitheatre for festival nights and drama performances.');

-- Events
INSERT INTO events (id, club_id, title, description, event_date, start_time, end_time, venue_id, status, admin_remarks, created_by) VALUES
(1, 1, 'HackStart 2026 Hackathon', 'A 24-hour sprint to build innovative student campus utilities and web tools.', CURDATE() + INTERVAL 5 DAY, '09:00:00', '18:00:00', 3, 'approved', 'Approved. Please coordinate keycard access with IT support.', 2),
(2, 2, 'Acoustic Unplugged Evening', 'An evening of live solo and band acoustic music performances under the stars.', CURDATE() + INTERVAL 8 DAY, '17:30:00', '20:30:00', 4, 'approved', 'Approved. Sound limit strictly 9:00 PM.', 3),
(3, 1, 'Web Dev Bootcamp', 'Beginner friendly hands-on workshop covering PHP, MySQL, and modern JavaScript.', CURDATE() + INTERVAL 12 DAY, '14:00:00', '17:00:00', 2, 'pending', NULL, 2);

-- Confirmed Venue Bookings for Approved Events
INSERT INTO venue_bookings (venue_id, event_id, booking_date, start_time, end_time, status) VALUES
(3, 1, CURDATE() + INTERVAL 5 DAY, '09:00:00', '18:00:00', 'confirmed'),
(4, 2, CURDATE() + INTERVAL 8 DAY, '17:30:00', '20:30:00', 'confirmed');

-- Announcements
INSERT INTO announcements (club_id, title, content, created_by) VALUES
(NULL, 'Welcome to ClubHub 2026', 'All college student clubs are now managed centrally through ClubHub. Explore clubs, check upcoming events, and apply for memberships.', 1),
(1, 'HackStart 2026 Registrations Open', 'Registrations for the annual 24-hour hackathon are now open for all departments. Check the events page for rules and slot timings.', 2),
(2, 'Auditions for Spring Cultural Fest', 'Vocalists, instrumentalists, and stage actors are invited for spring fest auditions next Wednesday.', 3);

-- Budget Requests
INSERT INTO budget_requests (id, club_id, title, description, amount, status, admin_remarks, created_by) VALUES
(1, 1, 'Hackathon Refreshments & Cloud Credits', 'Snacks, coffee supplies, and test cloud hosting servers for 24-hour participants.', 450.00, 'approved', 'Approved within semester allocation limit.', 2),
(2, 2, 'Sound Equipment Rental', 'Microphone stand rentals and stage monitors for the outdoor acoustic evening.', 300.00, 'pending', NULL, 3);

-- Expenses
INSERT INTO expenses (club_id, budget_request_id, title, amount, receipt_file, expense_date, created_by) VALUES
(1, 1, 'Cloud Hosting Vouchers', 150.00, NULL, CURDATE(), 2);
