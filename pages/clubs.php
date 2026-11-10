<?php
/**
 * ClubHub - Clubs Directory Page
 * Phase 1: Static semantic HTML5 structure with 6 sample club cards.
 */

$page_title = 'Clubs Directory';
require_once __DIR__ . '/../includes/header.php';
?>

<section>
    <h1>College Clubs Directory</h1>
    <p>Discover student organizations, technical societies, cultural clubs, and sports teams across the campus.</p>
</section>

<section>
    <h2>Filter Clubs</h2>
    <form action="clubs.php" method="GET">
        <p>
            <label for="search">Search Club Name:</label>
            <input type="text" id="search" name="search" placeholder="e.g. Coding, Robotics, Drama">
        </p>
        <p>
            <label for="category">Category:</label>
            <select id="category" name="category">
                <option value="">All Categories</option>
                <option value="technical">Technical</option>
                <option value="cultural">Cultural</option>
                <option value="literary">Literary</option>
                <option value="sports">Sports</option>
                <option value="media">Media & Creative</option>
            </select>
        </p>
        <p>
            <button type="submit">Filter Directory</button>
            <button type="reset">Reset</button>
        </p>
    </form>
</section>

<section>
    <h2>Active Student Clubs</h2>

    <!-- Sample Club Card 1 -->
    <article>
        <h3>Coding Club</h3>
        <p><strong>Category:</strong> Technical</p>
        <p><strong>Club Head:</strong> Alex Turner (Computer Science)</p>
        <p><strong>Faculty Advisor:</strong> Dr. Sarah Jenkins</p>
        <p><strong>Active Members:</strong> 85 Students</p>
        <p>Focuses on competitive programming, open source software contributions, algorithms, and full-stack web development hackathons.</p>
        <p><a href="login.php">Log in to Apply for Membership</a></p>
    </article>

    <!-- Sample Club Card 2 -->
    <article>
        <h3>Robotics & IoT Society</h3>
        <p><strong>Category:</strong> Technical</p>
        <p><strong>Club Head:</strong> Maya Patel (Electronics)</p>
        <p><strong>Faculty Advisor:</strong> Prof. Alan Davis</p>
        <p><strong>Active Members:</strong> 62 Students</p>
        <p>Hands-on engineering society dedicated to designing autonomous mobile robots, microcontrollers, drone technologies, and IoT prototypes.</p>
        <p><a href="login.php">Log in to Apply for Membership</a></p>
    </article>

    <!-- Sample Club Card 3 -->
    <article>
        <h3>Cultural & Arts Society</h3>
        <p><strong>Category:</strong> Cultural</p>
        <p><strong>Club Head:</strong> Rohan Verma (Mechanical)</p>
        <p><strong>Faculty Advisor:</strong> Dr. Emily Watson</p>
        <p><strong>Active Members:</strong> 110 Students</p>
        <p>Brings together campus talents in vocal and instrumental music, street theatre, fine art exhibitions, and classical dance.</p>
        <p><a href="login.php">Log in to Apply for Membership</a></p>
    </article>

    <!-- Sample Club Card 4 -->
    <article>
        <h3>Literary & Debating Society</h3>
        <p><strong>Category:</strong> Literary</p>
        <p><strong>Club Head:</strong> Jessica Taylor (Civil)</p>
        <p><strong>Faculty Advisor:</strong> Prof. Michael Green</p>
        <p><strong>Active Members:</strong> 48 Students</p>
        <p>Conducts parliamentary debate tournaments, elocution competitions, creative writing workshops, and the annual college quiz league.</p>
        <p><a href="login.php">Log in to Apply for Membership</a></p>
    </article>

    <!-- Sample Club Card 5 -->
    <article>
        <h3>Sports & Athletics Club</h3>
        <p><strong>Category:</strong> Sports</p>
        <p><strong>Club Head:</strong> David Kim (Management)</p>
        <p><strong>Faculty Advisor:</strong> Coach Brian Miller</p>
        <p><strong>Active Members:</strong> 130 Students</p>
        <p>Coordinates collegiate leagues in football, basketball, cricket, and athletics, promoting physical fitness and team sportsmanship.</p>
        <p><a href="login.php">Log in to Apply for Membership</a></p>
    </article>

    <!-- Sample Club Card 6 -->
    <article>
        <h3>Photography & Media Club</h3>
        <p><strong>Category:</strong> Media & Creative</p>
        <p><strong>Club Head:</strong> Chloe Bennett (Electrical)</p>
        <p><strong>Faculty Advisor:</strong> Dr. Emily Watson</p>
        <p><strong>Active Members:</strong> 55 Students</p>
        <p>Covers college events through digital photography, video journalism, post-production workshops, and campus documentary projects.</p>
        <p><a href="login.php">Log in to Apply for Membership</a></p>
    </article>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

