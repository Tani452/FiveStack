<?php
/**
 * ClubHub - AJAX Search Endpoint
 * Phase 8: Returns JSON results for live event and club searches with debounce.
 * Emits proper HTTP status codes and escaped JSON payloads.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$type = isset($_GET['type']) && $_GET['type'] === 'clubs' ? 'clubs' : 'events';
$query = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

$current_user_id = is_logged_in() ? (int)$_SESSION['user']['id'] : 0;
$is_admin = has_role('admin');
$is_club_head = has_role('club_head');

try {
    // -------------------------------------------------------------
    // SEARCH CLUBS
    // -------------------------------------------------------------
    if ($type === 'clubs') {
        $sql = '
            SELECT c.id, c.name, c.category, c.description, c.status,
                   u.name AS head_name,
                   (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id AND cm.status = "approved") AS member_count
            FROM clubs c
            LEFT JOIN users u ON c.head_id = u.id
            WHERE 1=1
        ';
        $params = [];

        if ($query !== '') {
            $sql .= ' AND (c.name LIKE ? OR c.description LIKE ?)';
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }

        if ($category !== '') {
            $sql .= ' AND LOWER(c.category) = LOWER(?)';
            $params[] = $category;
        }

        $sql .= ' ORDER BY c.name ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $clubs = $stmt->fetchAll();

        // Format club data
        $results = array_map(function ($c) {
            return [
                'id'           => (int)$c['id'],
                'name'         => $c['name'],
                'category'     => $c['category'],
                'description'  => $c['description'],
                'status'       => $c['status'],
                'head_name'    => $c['head_name'] ?? 'Not Assigned',
                'member_count' => (int)$c['member_count'],
            ];
        }, $clubs);

        echo json_encode([
            'success' => true,
            'type'    => 'clubs',
            'count'   => count($results),
            'data'    => $results,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    // -------------------------------------------------------------
    // SEARCH EVENTS (Default)
    // -------------------------------------------------------------
    $sql = '
        SELECT e.id, e.title, e.description, e.event_date, e.start_time, e.end_time, e.status,
               c.id AS club_id, c.name AS club_name, c.category AS club_category,
               v.id AS venue_id, v.name AS venue_name, v.location AS venue_location, v.capacity AS venue_capacity,
               (SELECT COUNT(*) FROM rsvps r WHERE r.event_id = e.id AND r.status = "attending") AS rsvp_count,
               (SELECT COUNT(*) FROM rsvps r WHERE r.event_id = e.id AND r.user_id = ? AND r.status = "attending") AS user_rsvped
        FROM events e
        JOIN clubs c ON e.club_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        WHERE 1=1
    ';
    $params = [$current_user_id];

    // Guests and regular members only see approved events
    if (!$is_admin && !$is_club_head) {
        $sql .= ' AND e.status = "approved"';
    }

    if ($query !== '') {
        $sql .= ' AND (e.title LIKE ? OR e.description LIKE ? OR c.name LIKE ? OR v.name LIKE ?)';
        $params[] = '%' . $query . '%';
        $params[] = '%' . $query . '%';
        $params[] = '%' . $query . '%';
        $params[] = '%' . $query . '%';
    }

    if ($category !== '') {
        $sql .= ' AND LOWER(c.category) = LOWER(?)';
        $params[] = $category;
    }

    $sql .= ' ORDER BY e.event_date ASC, e.start_time ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    // Format events
    $results = array_map(function ($ev) {
        return [
            'id'             => (int)$ev['id'],
            'title'          => $ev['title'],
            'description'    => $ev['description'],
            'club_id'        => (int)$ev['club_id'],
            'club_name'      => $ev['club_name'],
            'club_category'  => $ev['club_category'],
            'event_date'     => $ev['event_date'],
            'formatted_date' => date('F j, Y', strtotime($ev['event_date'])),
            'start_time'     => $ev['start_time'],
            'end_time'       => $ev['end_time'],
            'formatted_time' => date('h:i A', strtotime($ev['start_time'])) . ' - ' . date('h:i A', strtotime($ev['end_time'])),
            'venue_id'       => $ev['venue_id'] ? (int)$ev['venue_id'] : null,
            'venue_name'     => $ev['venue_name'] ?? 'TBA',
            'venue_location' => $ev['venue_location'] ?? '',
            'venue_capacity' => $ev['venue_capacity'] ? (int)$ev['venue_capacity'] : null,
            'status'         => $ev['status'],
            'rsvp_count'     => (int)$ev['rsvp_count'],
            'user_rsvped'    => (int)$ev['user_rsvped'] > 0,
        ];
    }, $events);

    echo json_encode([
        'success' => true,
        'type'    => 'events',
        'count'   => count($results),
        'data'    => $results,
    ], JSON_UNESCAPED_SLASHES);
    exit;

} catch (PDOException $e) {
    log_error('AJAX search endpoint error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database error during search execution.',
    ]);
    exit;
}

