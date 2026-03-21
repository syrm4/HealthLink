<?php
// HealthLink — Role-based dashboard router
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_login();

$role = $_SESSION['user_role'] ?? '';

switch ($role) {
    case 'community':
        header('Location: ' . BASE_PATH . '/pages/community_portal.php');
        break;
    case 'staff':
        header('Location: ' . BASE_PATH . '/pages/staff_portal.php');
        break;
    case 'admin':
        header('Location: ' . BASE_PATH . '/pages/admin_dashboard.php');
        break;
    case 'leader':
        header('Location: ' . BASE_PATH . '/pages/leader_dashboard.php');
        break;
    default:
        header('Location: ' . BASE_PATH . '/index.php');
        break;
}
exit;
