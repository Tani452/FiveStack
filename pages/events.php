<?php
/**
 * ClubHub - Events Directory Page
 * Phase 3: Instant client-side search and category filtering.
 */

$page_title = 'Campus Events';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Upcoming College Events</h1>
    <p>Browse schedule, venue information, and registration details for all sanctioned club events and campus workshops.</p>
</section>

<section class="filter-section">
    <h2>Search & Filter Events</h2>
    <form class="filter-form" action="events.php" method="GET">
        <p class="form-group">
            <label class="form-label" for="event_search">Search Event:</label>
            <input class="form-control" type="text" id="event_search" name="event_search" placeholder="Type event name or keyword...">
        </p>
        <p class="form-group">
            <label class="form-label" for="event_category">Category:</label>
            <select class="form-control" id="event_category" name="event_category">
                <option value="">All Categories</option>
                <option value="technical">Technical</option>
                <option value="cultural">Cultural</option>
                <option value="literary">Literary</option>
                <option value="sports">Sports</option>
                <option value="media">Media & Creative</option>
            </select>
        </p>
        <p class="form-actions">
            <button type="submit" class="btn btn-primary">Filter Schedule</button>
            <button type="reset" class="btn btn-secondary">Clear</button>
        </p>
    </form>
</section>

<section class="events-grid card-grid">
    <h2>Scheduled Events</h2>

    <!-- Sample Event 1 -->
    <article class="card event-card" data-category="technical">
        <h3>HackStart 2026: 24-Hour Code Sprint</h3>
        <p><strong>Organized By:</strong> Coding Club</p>
        <p><strong>Category:</strong> Technical</p>
        <p>
            <strong>Date:</strong> <time datetime="2026-11-20">November 20, 2026</time> | 
            <strong>Time:</strong> 09:00 AM - 09:00 PM
        </p>
        <p><strong>Venue:</strong> Computer Lab 3 (Capacity: 60)</p>
        <p><strong>Status:</strong> Approved by Faculty Coordinator</p>
        <p>Annual inter-department hackathon challenging teams to develop innovative open source solutions for campus problems.</p>
        <p><a href="login.php" class="btn-outline">Log in to Register Team</a></p>
    </article>

    <!-- Sample Event 2 -->
    <article class="card event-card" data-category="literary">
        <h3>National Youth Parliamentary Debate</h3>
        <p><strong>Organized By:</strong> Literary & Debating Society</p>
        <p><strong>Category:</strong> Literary</p>
        <p>
            <strong>Date:</strong> <time datetime="2026-11-24">November 24, 2026</time> | 
            <strong>Time:</strong> 10:00 AM - 04:00 PM
        </p>
        <p><strong>Venue:</strong> Seminar Hall 1 (Capacity: 120)</p>
        <p><strong>Status:</strong> Approved by Faculty Coordinator</p>
        <p>Three rounds of British Parliamentary debate on contemporary economic policy, artificial intelligence ethics, and global trade.</p>
        <p><a href="login.php" class="btn-outline">Log in to Register as Speaker/Audience</a></p>
    </article>

    <!-- Sample Event 3 -->
    <article class="card event-card" data-category="cultural">
        <h3>Acoustic Under the Stars: Live Music Fest</h3>
        <p><strong>Organized By:</strong> Cultural & Arts Society</p>
        <p><strong>Category:</strong> Cultural</p>
        <p>
            <strong>Date:</strong> <time datetime="2026-11-28">November 28, 2026</time> | 
            <strong>Time:</strong> 05:30 PM - 08:30 PM
        </p>
        <p><strong>Venue:</strong> Open Air Theatre (Capacity: 800)</p>
        <p><strong>Status:</strong> Approved by Faculty Coordinator</p>
        <p>An open-air musical evening featuring acoustic sets from student guitarists, vocal ensembles, and percussionists.</p>
        <p><a href="login.php" class="btn-outline">Log in for Event Pass</a></p>
    </article>

    <!-- Sample Event 4 -->
    <article class="card event-card" data-category="technical">
        <h3>Autonomous Drone Building Bootcamp</h3>
        <p><strong>Organized By:</strong> Robotics & IoT Society</p>
        <p><strong>Category:</strong> Technical</p>
        <p>
            <strong>Date:</strong> <time datetime="2026-12-02">December 2, 2026</time> | 
            <strong>Time:</strong> 01:00 PM - 05:00 PM
        </p>
        <p><strong>Venue:</strong> Electronics Hardware Lab (Capacity: 45)</p>
        <p><strong>Status:</strong> Approved by Faculty Coordinator</p>
        <p>A hands-on workshop covering flight controllers, ESC calibration, brushless motors, and radio transmitter programming.</p>
        <p><a href="login.php" class="btn-outline">Log in to Reserve Kit</a></p>
    </article>

    <!-- Sample Event 5 -->
    <article class="card event-card" data-category="sports">
        <h3>Inter-Department Football Championship</h3>
        <p><strong>Organized By:</strong> Sports & Athletics Club</p>
        <p><strong>Category:</strong> Sports</p>
        <p>
            <strong>Date:</strong> <time datetime="2026-12-08">December 8, 2026</time> | 
            <strong>Time:</strong> 03:30 PM - 06:30 PM
        </p>
        <p><strong>Venue:</strong> Main Sports Ground (Capacity: 500)</p>
        <p><strong>Status:</strong> Approved by Faculty Coordinator</p>
        <p>Quarter-final knockout rounds between the Computer Science, Mechanical, and Electronics departmental squads.</p>
        <p><a href="login.php" class="btn-outline">Log in for Match Fixtures</a></p>
    </article>

    <!-- Sample Event 6 -->
    <article class="card event-card" data-category="media">
        <h3>Campus Lens: Annual Photography Exhibition</h3>
        <p><strong>Organized By:</strong> Photography & Media Club</p>
        <p><strong>Category:</strong> Media & Creative</p>
        <p>
            <strong>Date:</strong> <time datetime="2026-12-12">December 12, 2026</time> | 
            <strong>Time:</strong> 11:00 AM - 05:00 PM
        </p>
        <p><strong>Venue:</strong> Main Auditorium Foyer (Capacity: 200)</p>
        <p><strong>Status:</strong> Approved by Faculty Coordinator</p>
        <p>Showcase of student fine-art photographs, architectural street photography, and macro nature prints with visitor voting.</p>
        <p><a href="login.php" class="btn-outline">Log in to Submit Photo Entry</a></p>
    </article>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
