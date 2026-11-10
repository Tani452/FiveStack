<?php
/**
 * ClubHub - Home / Landing Page
 * Phase 1: Static semantic HTML5 structure.
 */

$page_title = 'Welcome';
require_once __DIR__ . '/includes/header.php';
?>

<section>
    <h1>Welcome to ClubHub</h1>
    <p>ClubHub is the central operations hub for college student clubs, event management, venue booking, and activity coordination.</p>
    <p>Explore student clubs, discover upcoming campus activities, and connect with peer student coordinators.</p>
</section>

<section>
    <h2>Quick Campus Highlights</h2>
    <article>
        <h3>Active Student Clubs</h3>
        <p>Explore over 20+ student-led technical, cultural, literary, and sports societies.</p>
        <p><a href="pages/clubs.php">Browse Club Directory &rarr;</a></p>
    </article>
    <article>
        <h3>Upcoming Events</h3>
        <p>Stay up to date with hackathons, debates, cultural evenings, and workshops across campus.</p>
        <p><a href="pages/events.php">View Campus Events &rarr;</a></p>
    </article>
    <article>
        <h3>Venue & Budget Management</h3>
        <p>Faculty-coordinated space booking with clash detection and transparent budget tracking.</p>
        <p><a href="pages/about.php">Learn More About ClubHub &rarr;</a></p>
    </article>
</section>

<section>
    <h2>Campus Announcements</h2>
    <article>
        <h3>Fall Semester Club Registration Open</h3>
        <p><time datetime="2026-11-01">November 1, 2026</time> | By: Faculty Coordinator</p>
        <p>All students are invited to register for active college clubs for the current semester. Join a society to participate in events and projects.</p>
    </article>
    <article>
        <h3>Auditorium Booking Guidelines Updated</h3>
        <p><time datetime="2026-11-05">November 5, 2026</time> | By: Campus Administration</p>
        <p>Club heads must submit venue booking requests at least 5 business days in advance to prevent schedule clashes.</p>
    </article>
</section>

<section>
    <h2>Get Involved Today</h2>
    <p>Are you a student looking to join a club, or a club coordinator managing operations?</p>
    <p>
        <a href="pages/register.php">Create an Account</a> | 
        <a href="pages/login.php">Sign In to Dashboard</a>
    </p>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

