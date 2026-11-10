<?php
/**
 * ClubHub - Contact Page
 * Phase 1: Static semantic HTML5 structure with contact details and inquiry form.
 */

$page_title = 'Contact Us';
require_once __DIR__ . '/../includes/header.php';
?>

<section>
    <h1>Contact Student Affairs & ClubHub Team</h1>
    <p>Have questions regarding club registrations, venue bookings, or faculty coordinator approvals? Get in touch with the office of student activities.</p>
</section>

<section>
    <h2>Office Information</h2>
    <address>
        <p><strong>Department of Student Affairs & Extracurricular Activities</strong></p>
        <p>Room 204, Student Activity Centre (SAC), Central Campus</p>
        <p>College of Engineering & Technology</p>
        <p><strong>Email:</strong> <a href="mailto:clubs@college.edu">clubs@college.edu</a></p>
        <p><strong>Phone:</strong> +1 (555) 234-5678</p>
        <p><strong>Office Hours:</strong> Monday - Friday, 09:00 AM - 04:30 PM</p>
    </address>
</section>

<section>
    <h2>Send an Inquiry</h2>
    <form action="contact.php" method="POST">
        <!-- CSRF Protection Token -->
        <?php echo csrf_field(); ?>

        <p>
            <label for="contact_name">Your Full Name:</label>
            <input type="text" id="contact_name" name="contact_name" placeholder="e.g. Alex Morgan" required autocomplete="name">
        </p>

        <p>
            <label for="contact_email">College Email Address:</label>
            <input type="email" id="contact_email" name="contact_email" placeholder="e.g. alex.m@college.edu" required autocomplete="email">
        </p>

        <p>
            <label for="contact_phone">Phone Number (Optional):</label>
            <input type="tel" id="contact_phone" name="contact_phone" placeholder="e.g. 9876543210" pattern="[0-9]{10}" autocomplete="tel">
        </p>

        <p>
            <label for="inquiry_type">Inquiry Category:</label>
            <select id="inquiry_type" name="inquiry_type" required>
                <option value="">-- Choose Category --</option>
                <option value="new_club">New Club Charter Application</option>
                <option value="venue_support">Venue Reservation & Equipment Issue</option>
                <option value="event_approval">Event Sanction & Guidelines</option>
                <option value="budget_query">Budget Request / Requisition Query</option>
                <option value="technical">Technical Support / Bug Report</option>
            </select>
        </p>

        <p>
            <label for="preferred_date">Preferred Meeting / Follow-up Date:</label>
            <input type="date" id="preferred_date" name="preferred_date">
        </p>

        <p>
            <label for="message">Message Details:</label>
            <textarea id="message" name="message" rows="5" cols="40" placeholder="Please describe your question or issue in detail..." required></textarea>
        </p>

        <p>
            <button type="submit">Submit Inquiry</button>
            <button type="reset">Clear Form</button>
        </p>
    </form>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

