-- ========================================================
-- ClubHub - Realistic Sample Seed Data
-- 1 Admin, 2 Club Heads, 6 Members, 4 Clubs, 3 Venues, 8 Events
-- Default password for all user accounts is: password123
-- Hash: $2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG
-- ========================================================

USE clubhub;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Users (1 Admin, 2 Club Heads, 6 Members)
-- --------------------------------------------------------
TRUNCATE TABLE users;
INSERT INTO users (id, name, email, password, role, phone) VALUES
-- Admin (Faculty Coordinator)
(1, 'Dr. Sarah Jenkins', 'admin@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'admin', '9876543210'),
-- 2 Club Heads
(2, 'Alex Turner', 'techhead@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'club_head', '9876543211'),
(3, 'Maya Patel', 'culthead@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'club_head', '9876543212'),
-- 6 Members
(4, 'Rohan Verma', 'rohan@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'member', '9876543213'),
(5, 'Jessica Taylor', 'jessica@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'member', '9876543214'),
(6, 'David Kim', 'david@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'member', '9876543215'),
(7, 'Chloe Bennett', 'chloe@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'member', '9876543216'),
(8, 'Marcus Vance', 'marcus@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'member', '9876543217'),
(9, 'Ananya Sen', 'ananya@college.edu', '$2y$10$bJloud5VX1p5Fm9xoqDzheM0FlBgcIdyhMf4SSOcYhO5pV2C6lXMG', 'member', '9876543218');

-- --------------------------------------------------------
-- 2. 4 Clubs
-- --------------------------------------------------------
TRUNCATE TABLE clubs;
INSERT INTO clubs (id, name, description, category, head_id, status) VALUES
(1, 'Coding Club', 'Fostering competitive programming, open source software, and full-stack web development projects.', 'Technical', 2, 'active'),
(2, 'Cultural & Arts Society', 'Promoting music, drama, fine arts, dance, and creative celebrations across campus.', 'Cultural', 3, 'active'),
(3, 'Robotics & IoT Society', 'Building autonomous rovers, drones, microcontrollers, and machine learning prototypes.', 'Technical', NULL, 'active'),
(4, 'Literary & Debating Society', 'Organizing parliamentary debates, creative writing workshops, and annual college quizzes.', 'Literary', NULL, 'active');

-- --------------------------------------------------------
-- 3. Club Memberships
-- --------------------------------------------------------
TRUNCATE TABLE club_members;
INSERT INTO club_members (club_id, user_id, status) VALUES
(1, 2, 'approved'), -- Alex in Coding Club
(2, 3, 'approved'), -- Maya in Cultural Society
(1, 4, 'approved'), -- Rohan in Coding Club
(3, 4, 'approved'), -- Rohan in Robotics Society
(4, 5, 'approved'), -- Jessica in Literary Society
(2, 5, 'pending'),  -- Jessica pending in Cultural
(1, 6, 'approved'), -- David in Coding Club
(4, 6, 'approved'), -- David in Literary Society
(2, 7, 'approved'), -- Chloe in Cultural
(3, 7, 'pending'),  -- Chloe pending in Robotics
(3, 8, 'approved'), -- Marcus in Robotics
(1, 9, 'approved'), -- Ananya in Coding Club
(2, 9, 'approved'); -- Ananya in Cultural

-- --------------------------------------------------------
-- 4. 3 Venues
-- --------------------------------------------------------
TRUNCATE TABLE venues;
INSERT INTO venues (id, name, capacity, location, description) VALUES
(1, 'Main Auditorium', 600, 'Central Block, Ground Floor', 'Large acoustic stage with motorized projector, podium, and theatre sound.'),
(2, 'Seminar Hall 1', 120, 'Science Wing, 2nd Floor', 'Air-conditioned tiered lecture and presentation hall with dual projectors.'),
(3, 'Computer Lab 3', 60, 'Tech Block, 1st Floor', 'High-performance computing workstations with gigabit LAN and developer toolchains.');

-- --------------------------------------------------------
-- 5. 8 Events
-- --------------------------------------------------------
TRUNCATE TABLE events;
INSERT INTO events (id, club_id, title, description, event_date, start_time, end_time, venue_id, status, admin_remarks, created_by) VALUES
-- Event 1: Coding Club, Lab 3, Approved
(1, 1, 'HackStart 2026: 24-Hour Code Sprint', 'Annual inter-department hackathon challenging student teams to build campus utilities.', CURDATE() + INTERVAL 5 DAY, '09:00:00', '18:00:00', 3, 'approved', 'Approved. IT staff assigned for network support.', 2),
-- Event 2: Literary Society, Seminar Hall 1, Approved
(2, 4, 'National Youth Parliamentary Debate', 'Three rounds of British Parliamentary debate on global economics and tech ethics.', CURDATE() + INTERVAL 8 DAY, '10:00:00', '16:00:00', 2, 'approved', 'Approved. Audio recording equipment reserved.', 5),
-- Event 3: Cultural Society, Main Auditorium, Approved
(3, 2, 'Acoustic Under the Stars: Live Music Fest', 'An evening of live solo, duet, and band acoustic performances from student artists.', CURDATE() + INTERVAL 12 DAY, '17:30:00', '20:30:00', 1, 'approved', 'Approved. Strict 9 PM sound curfew applies.', 3),
-- Event 4: Robotics Society, Lab 3, Approved
(4, 3, 'Autonomous Drone Building Bootcamp', 'Hands-on workshop covering flight controllers, ESC calibration, and transmitter coding.', CURDATE() + INTERVAL 15 DAY, '13:00:00', '17:00:00', 3, 'approved', 'Approved. Safety eye-protection required for all attendees.', 8),
-- Event 5: Coding Club, Lab 3, Pending
(5, 1, 'Web Dev Beginner Bootcamp', 'Hands-on crash course covering semantic HTML5, CSS3 Grid, and vanilla JavaScript.', CURDATE() + INTERVAL 18 DAY, '14:00:00', '17:00:00', 3, 'pending', NULL, 2),
-- Event 6: Cultural Society, Main Auditorium, Approved
(6, 2, 'Annual Drama & Street Play Auditions', 'Open theatrical auditions for the annual inter-collegiate spring cultural festival.', CURDATE() + INTERVAL 21 DAY, '15:00:00', '19:00:00', 1, 'approved', 'Approved. Green room keys available at security desk.', 3),
-- Event 7: Coding Club, Lab 3, Approved
(7, 1, 'Competitive Coding League: Round 1', 'Timed algorithmic problem solving contest with live campus scoreboard.', CURDATE() + INTERVAL 25 DAY, '16:00:00', '19:00:00', 3, 'approved', 'Approved. Server bandwidth configured.', 2),
-- Event 8: Literary Society, Seminar Hall 1, Pending
(8, 4, 'Inter-Department Trivia & Quiz Challenge', 'General awareness, science history, and pop culture quiz tournament.', CURDATE() + INTERVAL 28 DAY, '11:00:00', '14:00:00', 2, 'pending', NULL, 5);

-- --------------------------------------------------------
-- 6. Bookings (Venue timeslot reservations for approved events)
-- --------------------------------------------------------
TRUNCATE TABLE bookings;
INSERT INTO bookings (id, venue_id, event_id, booking_date, start_time, end_time, status) VALUES
(1, 3, 1, CURDATE() + INTERVAL 5 DAY, '09:00:00', '18:00:00', 'confirmed'),
(2, 2, 2, CURDATE() + INTERVAL 8 DAY, '10:00:00', '16:00:00', 'confirmed'),
(3, 1, 3, CURDATE() + INTERVAL 12 DAY, '17:30:00', '20:30:00', 'confirmed'),
(4, 3, 4, CURDATE() + INTERVAL 15 DAY, '13:00:00', '17:00:00', 'confirmed'),
(5, 1, 6, CURDATE() + INTERVAL 21 DAY, '15:00:00', '19:00:00', 'confirmed'),
(6, 3, 7, CURDATE() + INTERVAL 25 DAY, '16:00:00', '19:00:00', 'confirmed');

-- --------------------------------------------------------
-- 7. RSVPs (Student event attendance tracking)
-- --------------------------------------------------------
TRUNCATE TABLE rsvps;
INSERT INTO rsvps (event_id, user_id, status) VALUES
(1, 4, 'attending'),
(1, 6, 'attending'),
(1, 9, 'attending'),
(2, 5, 'attending'),
(2, 6, 'interested'),
(3, 3, 'attending'),
(3, 7, 'attending'),
(3, 9, 'attending'),
(4, 4, 'attending'),
(4, 8, 'attending');

-- --------------------------------------------------------
-- 8. Announcements
-- --------------------------------------------------------
TRUNCATE TABLE announcements;
INSERT INTO announcements (id, club_id, title, content, created_by) VALUES
(1, NULL, 'Welcome to ClubHub Campus Operations', 'All college student clubs are now coordinated centrally on ClubHub. Browse societies, attend events, and apply for membership.', 1),
(2, 1, 'HackStart 2026 Team Registrations Open', 'Registrations are open for the 24-hour sprint in Lab 3. Form teams of 2 to 4 students across any department.', 2),
(3, 2, 'Spring Cultural Fest Audition Slots', 'Audition sign-up sheets for classical dance, vocalists, and actors are posted on the society board.', 3),
(4, 4, 'Debate Team Tryouts Announced', 'Join the literary society in Seminar Hall 1 for parliamentary motion debate preparations.', 5);

-- --------------------------------------------------------
-- 9. Budget Requests
-- --------------------------------------------------------
TRUNCATE TABLE budget_requests;
INSERT INTO budget_requests (id, club_id, title, description, amount, status, admin_remarks, created_by) VALUES
(1, 1, 'Hackathon Refreshments & Cloud Hosting', 'Participant snacks, test VPS servers, and badges for 24-hour code sprint.', 450.00, 'approved', 'Approved within semester student activities limit.', 2),
(2, 2, 'Acoustic Night Sound Monitor Rental', 'Stage monitor and microphone stand rentals for live music fest.', 300.00, 'approved', 'Approved with vendor quotation on file.', 3),
(3, 3, 'Drone Workshop ESC & Sensor Kits', 'Flight controllers, ESC boards, and lithium polymer battery test packs.', 520.00, 'pending', NULL, 8),
(4, 4, 'Debate Tournament Trophies & Certificates', 'Plaques and participant certificates for parliamentary speakers.', 180.00, 'pending', NULL, 5);

-- --------------------------------------------------------
-- 10. Expenses
-- --------------------------------------------------------
TRUNCATE TABLE expenses;
INSERT INTO expenses (id, club_id, budget_request_id, title, amount, receipt_file, expense_date, created_by) VALUES
(1, 1, 1, 'Cloud Hosting Test Servers', 120.00, NULL, CURDATE() - INTERVAL 2 DAY, 2),
(2, 1, 1, 'Event Name Badges & Lanyards', 45.00, NULL, CURDATE() - INTERVAL 1 DAY, 2),
(3, 2, 2, 'Audio Monitor Rental Deposit', 150.00, NULL, CURDATE() - INTERVAL 3 DAY, 3);

-- --------------------------------------------------------
-- 11. Tasks
-- --------------------------------------------------------
TRUNCATE TABLE tasks;
INSERT INTO tasks (id, club_id, event_id, title, description, assigned_to, due_date, status, priority, created_by) VALUES
(1, 1, 1, 'Configure Gigabit Switches in Lab 3', 'Verify DHCP pool and switch ports for hackathon participants.', 4, CURDATE() + INTERVAL 3 DAY, 'in_progress', 'high', 2),
(2, 1, 1, 'Print Hackathon Schedule Posters', 'Print A3 event schedule posters and hang in Tech Wing foyer.', 6, CURDATE() + INTERVAL 4 DAY, 'todo', 'medium', 2),
(3, 2, 3, 'Test Acoustic Microphones & Stage DI', 'Soundcheck acoustic inputs with venue technician in Auditorium.', 7, CURDATE() + INTERVAL 11 DAY, 'todo', 'high', 3),
(4, 3, 4, 'Inventory Drone Soldering Stations', 'Inspect soldering irons, fume extractors, and test wire reels.', 8, CURDATE() + INTERVAL 13 DAY, 'todo', 'medium', 8);

SET FOREIGN_KEY_CHECKS = 1;

