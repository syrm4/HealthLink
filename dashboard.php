<?php
// HealthLink — Role-based dashboard router
require_once __DIR__ . '/includes/auth.php';
require_login();

$role = $_SESSION['user_role'] ?? '';

switch ($role) {
    case 'community':
        header('Location: /pages/community_portal.php');
        break;
    case 'staff':
        header('Location: /pages/staff_portal.php');
        break;
    case 'admin':
        header('Location: /pages/admin_dashboard.php');
        break;
    case 'leader':
        header('Location: /pages/leader_dashboard.php');
        break;
    default:
        header('Location: /index.php');
        break;
}
exit;
