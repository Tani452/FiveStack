<?php
/**
 * ClubHub - AJAX Notifications Endpoint
 * Phase 8: Returns live notification counts and summary items for the navbar badge.
 * Auto-refreshes every 30 seconds via client-side polling.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Unauthenticated visitors receive count 0
if (!is_logged_in()) {
    http_response_code(200);
    echo json_encode([
        'authenticated' => false,
        'count'         => 0,
        'items'         => [],
    ]);
    exit;
}

$user = current_user();
$user_id = (int)$user['id'];
$role = $user['role'];

$total_count = 0;
$items = [];

try {
    // -------------------------------------------------------------
    // ADMIN NOTIFICATIONS
    // -------------------------------------------------------------
    if ($role === 'admin') {
        $p_events = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'pending'")->fetchColumn();
        if ($p_events > 0) {
            $total_count += $p_events;
            $items[] = [
                'type'  => 'event',
                'title' => $p_events . ' event proposal' . ($p_events === 1 ? '' : 's') . ' awaiting sanction',
                'count' => $p_events,
                'url'   => 'dashboard.php',
            ];
        }

        $p_budgets = (int)$pdo->query("SELECT COUNT(*) FROM budget_requests WHERE status = 'pending'")->fetchColumn();
        if ($p_budgets > 0) {
            $total_count += $p_budgets;
            $items[] = [
                'type'  => 'budget',
                'title' => $p_budgets . ' budget request' . ($p_budgets === 1 ? '' : 's') . ' awaiting review',
                'count' => $p_budgets,
                'url'   => 'budget.php',
            ];
        }
    }

    // -------------------------------------------------------------
    // CLUB HEAD NOTIFICATIONS
    // -------------------------------------------------------------
    elseif ($role === 'club_head') {
        $p_apps_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM club_members cm 
            JOIN clubs c ON cm.club_id = c.id 
            WHERE c.head_id = ? AND cm.status = 'pending'
        ");
        $p_apps_stmt->execute([$user_id]);
        $p_apps = (int)$p_apps_stmt->fetchColumn();

        if ($p_apps > 0) {
            $total_count += $p_apps;
            $items[] = [
                'type'  => 'membership',
                'title' => $p_apps . ' student join request' . ($p_apps === 1 ? '' : 's') . ' awaiting review',
                'count' => $p_apps,
                'url'   => 'dashboard.php',
            ];
        }
    }

    // -------------------------------------------------------------
    // MEMBER NOTIFICATIONS
    // -------------------------------------------------------------
    elseif ($role === 'member') {
        // Query unread / recent announcements from the past 7 days
        $ann_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM announcements a 
            WHERE (a.club_id IS NULL OR a.club_id IN (
                SELECT club_id FROM club_members WHERE user_id = ? AND status = 'approved'
            )) AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $ann_stmt->execute([$user_id]);
        $ann_count = (int)$ann_stmt->fetchColumn();

        if ($ann_count > 0) {
            $total_count += $ann_count;
            $items[] = [
                'type'  => 'announcement',
                'title' => $ann_count . ' active announcement' . ($ann_count === 1 ? '' : 's'),
                'count' => $ann_count,
                'url'   => 'dashboard.php',
            ];
        }
    }

    http_response_code(200);
    echo json_encode([
        'authenticated' => true,
        'user_id'       => $user_id,
        'role'          => $role,
        'count'         => $total_count,
        'items'         => $items,
        'timestamp'     => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_SLASHES);
    exit;

} catch (PDOException $e) {
    log_error('Notifications endpoint error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'authenticated' => true,
        'count'         => 0,
        'error'         => 'Failed to load notifications.',
    ]);
    exit;
}

