<?php
/**
 * ClubHub - About Page
 * Phase 1: Static semantic HTML5 structure describing the system.
 */

$page_title = 'About ClubHub';
require_once __DIR__ . '/../includes/header.php';
?>

<section>
    <h1>About ClubHub</h1>
    <p>ClubHub is a centralized operations platform developed as a collegiate web development lab project to modernize how extracurricular student clubs, campus venues, and campus events are coordinated.</p>
</section>

<section>
    <h2>Platform Objectives</h2>
    <p>Managing college clubs often involves fragmented spreadsheets, paper approval trails, double-booked auditoriums, and disconnected email threads. ClubHub solves these operational challenges by providing:</p>
    <ul>
        <li>A single unified portal for students to explore, join, and follow college clubs.</li>
        <li>Automated venue reservation with real-time clash detection to avoid double bookings.</li>
        <li>Structured event proposal and administrative approval workflows for faculty coordinators.</li>
        <li>Transparent budget allocations, expense reporting, and digital receipt tracking.</li>
        <li>Role-based dashboards tailored for faculty coordinators, club leaders, members, and campus guests.</li>
    </ul>
</section>

<section>
    <h2>User Roles & Access Levels</h2>
    <article>
        <h3>Faculty Coordinator (Admin)</h3>
        <p>Oversees all college societies, approves or rejects event proposals, reviews budget requests, and manages venue allocations.</p>
    </article>
    <article>
        <h3>Club Head (Student Leader)</h3>
        <p>Manages club roster, reviews membership applications, drafts event proposals, reserves venues, and logs club expenditure.</p>
    </article>
    <article>
        <h3>Club Member (Enrolled Student)</h3>
        <p>Joins approved clubs, registers for campus events, views club announcements, and participates in activities.</p>
    </article>
    <article>
        <h3>Guest (Campus Visitor / Prospective Student)</h3>
        <p>Browses public club profiles, checks upcoming open events, and views campus-wide announcements.</p>
    </article>
</section>

<section>
    <h2>Technical Specifications</h2>
    <p>This project is implemented strictly using foundational web engineering standards without heavy third-party PHP frameworks:</p>
    <ul>
        <li><strong>Frontend:</strong> Semantic HTML5, responsive CSS3, and vanilla JavaScript.</li>
        <li><strong>Backend:</strong> PHP 8.x utilizing PHP Data Objects (PDO) with prepared statements.</li>
        <li><strong>Database:</strong> MySQL relational database running on local XAMPP stack.</li>
        <li><strong>Security:</strong> CSRF token verification, password hashing via <code>password_hash()</code>, and XSS sanitization via <code>htmlspecialchars()</code>.</li>
    </ul>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

