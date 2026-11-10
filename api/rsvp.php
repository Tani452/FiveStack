<?php
/**
 * ClubHub - AJAX RSVP Endpoint
 * Phase 8: Toggles event attendance registration without page reload.
 * Strictly requires authentication (HTTP 401), CSRF verification,
 * and outputs live attendee counts.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Strictly enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

// 2. Require Authentication (HTTP 401)
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success'  => false,
        'error'    => 'Authentication required. Please log in to RSVP for events.',
        'redirect' => '../pages/login.php',
    ]);
    exit;
}

// 3. Verify CSRF Token (Header X-CSRF-Token or POST parameter)
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'Security verification failed (Invalid CSRF token). Refresh and try again.',
    ]);
    exit;
}

// Extract event_id from POST or JSON input
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : (int)($json_data['event_id'] ?? 0);

if ($event_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid event identifier.']);
    exit;
}

$user = current_user();
$user_id = (int)$user['id'];

try {
    // 4. Verify Event Exists and is Sanctioned/Approved
    $e_stmt = $pdo->prepare('SELECT id, title, status FROM events WHERE id = ? LIMIT 1');
    $e_stmt->execute([$event_id]);
    $event = $e_stmt->fetch();

    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Event not found.']);
        exit;
    }

    if ($event['status'] !== 'approved') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'RSVPs are only open for sanctioned/approved campus events.']);
        exit;
    }

    // 5. Check Existing RSVP Record
    $chk_stmt = $pdo->prepare('SELECT id, status FROM rsvps WHERE event_id = ? AND user_id = ? LIMIT 1');
    $chk_stmt->execute([$event_id, $user_id]);
    $existing = $chk_stmt->fetch();

    $rsvped = false;
    $message = '';

    if ($existing) {
        if ($existing['status'] === 'attending') {
            // Cancel RSVP
            $up_stmt = $pdo->prepare('UPDATE rsvps SET status = "cancelled" WHERE id = ?');
            $up_stmt->execute([(int)$existing['id']]);
            $rsvped = false;
            $message = 'Your RSVP has been cancelled.';
        } else {
            // Re-confirm RSVP
            $up_stmt = $pdo->prepare('UPDATE rsvps SET status = "attending", rsvp_date = CURRENT_TIMESTAMP WHERE id = ?');
            $up_stmt->execute([(int)$existing['id']]);
            $rsvped = true;
            $message = 'RSVP confirmed! See you at ' . $event['title'] . '.';
        }
    } else {
        // Insert new RSVP
        $ins_stmt = $pdo->prepare('INSERT INTO rsvps (event_id, user_id, status) VALUES (?, ?, "attending")');
        $ins_stmt->execute([$event_id, $user_id]);
        $rsvped = true;
        $message = 'RSVP confirmed! See you at ' . $event['title'] . '.';
    }

    // 6. Fetch Updated Attendee Count
    $cnt_stmt = $pdo->prepare('SELECT COUNT(*) FROM rsvps WHERE event_id = ? AND status = "attending"');
    $cnt_stmt->execute([$event_id]);
    $count = (int)$cnt_stmt->fetchColumn();

    http_response_code(200);
    echo json_encode([
        'success'  => true,
        'event_id' => $event_id,
        'rsvped'   => $rsvped,
        'count'    => $count,
        'message'  => $message,
    ], JSON_UNESCAPED_SLASHES);
    exit;

} catch (PDOException $e) {
    log_error('RSVP AJAX error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error updating RSVP.']);
    exit;
}

