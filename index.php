<?php
/**
 * ClubHub - Home / Landing Page
 * Phase 2: Styled with design system, banner hero, cards, and buttons.
 */

$page_title = 'Welcome';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-section">
    <h1 class="hero-title">Welcome to ClubHub</h1>
    <p class="hero-lead">ClubHub is the central operations hub for college student clubs, event management, venue booking, and activity coordination.</p>
    <p class="hero-desc">Explore student clubs, discover upcoming campus activities, and connect with peer student coordinators.</p>
</section>

<section class="highlights-grid card-grid">
    <h2>Quick Campus Highlights</h2>
    <article class="card highlight-card">
        <h3>Active Student Clubs</h3>
        <p>Explore over 20+ student-led technical, cultural, literary, and sports societies.</p>
        <p><a href="pages/clubs.php" class="card-link">Browse Club Directory &rarr;</a></p>
    </article>
    <article class="card highlight-card">
        <h3>Upcoming Events</h3>
        <p>Stay up to date with hackathons, debates, cultural evenings, and workshops across campus.</p>
        <p><a href="pages/events.php" class="card-link">View Campus Events &rarr;</a></p>
    </article>
    <article class="card highlight-card">
        <h3>Venue & Budget Management</h3>
        <p>Faculty-coordinated space booking with clash detection and transparent budget tracking.</p>
        <p><a href="pages/about.php" class="card-link">Learn More About ClubHub &rarr;</a></p>
    </article>
</section>

<section class="announcements-section">
    <h2>Campus Announcements</h2>
    <article class="card announcement-card">
        <h3>Fall Semester Club Registration Open</h3>
        <p><time datetime="2026-11-01">November 1, 2026</time> | By: Faculty Coordinator</p>
        <p>All students are invited to register for active college clubs for the current semester. Join a society to participate in events and projects.</p>
    </article>
    <article class="card announcement-card">
        <h3>Auditorium Booking Guidelines Updated</h3>
        <p><time datetime="2026-11-05">November 5, 2026</time> | By: Campus Administration</p>
        <p>Club heads must submit venue booking requests at least 5 business days in advance to prevent schedule clashes.</p>
    </article>
</section>

<section class="cta-section">
    <h2>Get Involved Today</h2>
    <p>Are you a student looking to join a club, or a club coordinator managing operations?</p>
    <p>
        <a href="pages/register.php" class="btn btn-primary">Create an Account</a>
        <a href="pages/login.php" class="btn btn-secondary">Sign In to Dashboard</a>
    </p>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
