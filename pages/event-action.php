<?php
/**
 * ClubHub - Event Sanction & Approval Action Handler
 * Phase 7b: Enables Faculty Coordinator (Admin) to approve or reject
 * club event proposals with coordinator comments and venue conflict validation.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Only Admin can sanction or reject club events
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security verification failed. Please try again.');
    redirect('dashboard.php');
}

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$decision = trim($_POST['decision'] ?? '');
$admin_remarks = trim($_POST['admin_remarks'] ?? '');

if ($event_id <= 0 || !in_array($decision, ['approve', 'reject'], true)) {
    set_flash('danger', 'Invalid event approval request.');
    redirect('dashboard.php');
}

try {
    // Fetch event details
    $e_stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $e_stmt->execute([$event_id]);
    $event = $e_stmt->fetch();

    if (!$event) {
        set_flash('danger', 'Event record not found.');
        redirect('dashboard.php');
    }

    if ($decision === 'approve') {
        // If event specifies a venue, check for clash before approving
        if (!empty($event['venue_id'])) {
            $clash_stmt = $pdo->prepare("
                SELECT e.title, e.start_time, e.end_time, v.name AS venue_name
                FROM events e
                JOIN venues v ON e.venue_id = v.id
                WHERE e.venue_id = ?
                  AND e.event_date = ?
                  AND e.status = 'approved'
                  AND (e.start_time < ? AND e.end_time > ?)
                  AND e.id != ?
                LIMIT 1
            ");
            $clash_stmt->execute([
                $event['venue_id'],
                $event['event_date'],
                $event['end_time'],
                $event['start_time'],
                $event_id
            ]);
            $clash = $clash_stmt->fetch();

            if ($clash) {
                set_flash('danger', 'Cannot approve: Venue Clash! ' . $clash['venue_name'] . ' is already approved for "' . $clash['title'] . '" on ' . $event['event_date'] . '.');
                redirect('event-detail.php?id=' . $event_id);
            }
        }

        // Update event status to approved
        $up = $pdo->prepare('UPDATE events SET status = "approved", admin_remarks = ? WHERE id = ?');
        $up->execute([$admin_remarks !== '' ? $admin_remarks : 'Approved by Faculty Coordinator.', $event_id]);

        // Sync bookings
        if (!empty($event['venue_id'])) {
            $pdo->prepare('DELETE FROM bookings WHERE event_id = ?')->execute([$event_id]);
            $b_stmt = $pdo->prepare('INSERT INTO bookings (venue_id, event_id, booking_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, "confirmed")');
            $b_stmt->execute([
                $event['venue_id'],
                $event_id,
                $event['event_date'],
                $event['start_time'],
                $event['end_time']
            ]);
        }

        set_flash('success', 'Event "' . $event['title'] . '" has been approved and published to the campus calendar!');
    } elseif ($decision === 'reject') {
        $up = $pdo->prepare('UPDATE events SET status = "rejected", admin_remarks = ? WHERE id = ?');
        $up->execute([$admin_remarks !== '' ? $admin_remarks : 'Proposal rejected by Faculty Coordinator.', $event_id]);

        // Cancel any bookings
        $pdo->prepare('DELETE FROM bookings WHERE event_id = ?')->execute([$event_id]);

        set_flash('info', 'Event "' . $event['title'] . '" has been rejected with coordinator remarks.');
    }
} catch (PDOException $e) {
    log_error('Error processing event sanction: ' . $e->getMessage());
    set_flash('danger', 'Database error while sanctioning event.');
}

redirect('dashboard.php');

