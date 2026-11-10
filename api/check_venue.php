<?php
/**
 * ClubHub - AJAX Venue Availability & Clash Detection Endpoint
 * Phase 8: Real-time overlap detection as organizers pick date and time on booking forms.
 * Emits JSON with HTTP 200 (Available) or HTTP 409 (Conflict).
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$venue_id = isset($_REQUEST['venue_id']) ? (int)$_REQUEST['venue_id'] : 0;
$event_date = trim($_REQUEST['event_date'] ?? '');
$start_time = trim($_REQUEST['start_time'] ?? '');
$end_time = trim($_REQUEST['end_time'] ?? '');
$current_event_id = isset($_REQUEST['event_id']) ? (int)$_REQUEST['event_id'] : 0;

// 1. Validate Input Parameters
if ($venue_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'available' => false,
        'error'     => 'Please select a valid campus venue.',
    ]);
    exit;
}

if ($event_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $event_date)) {
    http_response_code(400);
    echo json_encode([
        'available' => false,
        'error'     => 'Please provide a valid date in YYYY-MM-DD format.',
    ]);
    exit;
}

if ($start_time === '' || $end_time === '') {
    http_response_code(400);
    echo json_encode([
        'available' => false,
        'error'     => 'Both start time and end time are required.',
    ]);
    exit;
}

if (strtotime($end_time) <= strtotime($start_time)) {
    http_response_code(400);
    echo json_encode([
        'available' => false,
        'error'     => 'End time must be later than start time.',
    ]);
    exit;
}

try {
    // 2. Query Venue Information
    $v_stmt = $pdo->prepare('SELECT id, name, capacity, location FROM venues WHERE id = ? LIMIT 1');
    $v_stmt->execute([$venue_id]);
    $venue = $v_stmt->fetch();

    if (!$venue) {
        http_response_code(404);
        echo json_encode([
            'available' => false,
            'error'     => 'Selected venue was not found.',
        ]);
        exit;
    }

    // 3. Query for Overlapping Events (Clash Detection)
    $sql = "
        SELECT e.id, e.title, e.start_time, e.end_time, e.event_date,
               c.name AS club_name
        FROM events e
        JOIN clubs c ON e.club_id = c.id
        WHERE e.venue_id = ?
          AND e.event_date = ?
          AND e.status NOT IN ('rejected', 'cancelled', 'draft')
          AND (e.start_time < ? AND e.end_time > ?)
    ";
    $params = [$venue_id, $event_date, $end_time, $start_time];

    // Exclude current event if editing
    if ($current_event_id > 0) {
        $sql .= ' AND e.id != ?';
        $params[] = $current_event_id;
    }

    $sql .= ' LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clash = $stmt->fetch();

    if ($clash) {
        $c_start = date('h:i A', strtotime($clash['start_time']));
        $c_end = date('h:i A', strtotime($clash['end_time']));
        $message = "Venue Clash: {$venue['name']} is already reserved on {$event_date} for '{$clash['title']}' ({$clash['club_name']}) from {$c_start} to {$c_end}.";

        http_response_code(409); // Conflict
        echo json_encode([
            'available' => false,
            'clash'     => true,
            'venue'     => [
                'id'       => (int)$venue['id'],
                'name'     => $venue['name'],
                'capacity' => (int)$venue['capacity'],
                'location' => $venue['location'],
            ],
            'message'   => $message,
            'conflict'  => [
                'event_id'   => (int)$clash['id'],
                'title'      => $clash['title'],
                'club_name'  => $clash['club_name'],
                'start_time' => $clash['start_time'],
                'end_time'   => $clash['end_time'],
                'time_span'  => "{$c_start} - {$c_end}",
            ],
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    // 4. No Conflict Found: Venue is Available!
    http_response_code(200);
    echo json_encode([
        'available' => true,
        'clash'     => false,
        'venue'     => [
            'id'       => (int)$venue['id'],
            'name'     => $venue['name'],
            'capacity' => (int)$venue['capacity'],
            'location' => $venue['location'],
        ],
        'message'   => "{$venue['name']} is available for the selected time slot ({$start_time} to {$end_time}) on {$event_date}!",
    ], JSON_UNESCAPED_SLASHES);
    exit;

} catch (PDOException $e) {
    log_error('Venue availability check error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'available' => false,
        'error'     => 'Database error while checking venue availability.',
    ]);
    exit;
}

