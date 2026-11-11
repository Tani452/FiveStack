<?php
/**
 * ClubHub - Approved Events JSON Endpoint
 * Phase 9: Supplies approved events data for the React Event Calendar widget.
 * Emits clean JSON with HTTP status codes and CORS headers.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error'   => 'Method not allowed. Use GET.',
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

$month = trim($_GET['month'] ?? ''); // Optional 'YYYY-MM'
$category = trim($_GET['category'] ?? ''); // Optional club category filter

try {
    $sql = '
        SELECT e.id, e.title, e.description, e.event_date, e.start_time, e.end_time, e.status,
               c.id AS club_id, c.name AS club_name, c.category AS club_category,
               v.id AS venue_id, v.name AS venue_name, v.location AS venue_location, v.capacity AS venue_capacity,
               (SELECT COUNT(*) FROM rsvps r WHERE r.event_id = e.id AND r.status = "attending") AS rsvp_count
        FROM events e
        JOIN clubs c ON e.club_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        WHERE e.status = "approved"
    ';
    $params = [];

    // Optional month filter (e.g., 2026-11)
    if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
        $sql .= ' AND DATE_FORMAT(e.event_date, "%Y-%m") = ?';
        $params[] = $month;
    }

    // Optional category filter
    if ($category !== '' && strtolower($category) !== 'all') {
        $sql .= ' AND LOWER(c.category) = LOWER(?)';
        $params[] = $category;
    }

    $sql .= ' ORDER BY e.event_date ASC, e.start_time ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $events = array_map(function ($ev) {
        $start_ts = strtotime($ev['start_time']);
        $end_ts = strtotime($ev['end_time']);
        $date_ts = strtotime($ev['event_date']);

        return [
            'id'             => (int)$ev['id'],
            'title'          => $ev['title'],
            'description'    => $ev['description'],
            'event_date'     => $ev['event_date'],
            'formatted_date' => $date_ts ? date('F j, Y', $date_ts) : $ev['event_date'],
            'start_time'     => $ev['start_time'],
            'end_time'       => $ev['end_time'],
            'formatted_time' => ($start_ts && $end_ts) ? date('h:i A', $start_ts) . ' - ' . date('h:i A', $end_ts) : '',
            'club_id'        => (int)$ev['club_id'],
            'club_name'      => $ev['club_name'],
            'club_category'  => $ev['club_category'],
            'venue_id'       => $ev['venue_id'] ? (int)$ev['venue_id'] : null,
            'venue_name'     => $ev['venue_name'] ?? 'To Be Announced',
            'venue_location' => $ev['venue_location'] ?? 'Campus',
            'venue_capacity' => $ev['venue_capacity'] ? (int)$ev['venue_capacity'] : null,
            'rsvp_count'     => (int)$ev['rsvp_count'],
            'status'         => $ev['status'],
        ];
    }, $rows);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count'   => count($events),
        'data'    => $events,
    ], JSON_UNESCAPED_SLASHES);
    exit;

} catch (PDOException $e) {
    log_error('api/events.php query failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database error retrieving approved events.',
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

