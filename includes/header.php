<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

$_hlUser = is_logged_in() ? current_user() : null;
$_hlRole = $_hlUser['role'] ?? null;

$roleLabels = [
    'community' => 'Community partner',
    'staff'     => 'Staff',
    'admin'     => 'Admin',
    'leader'    => 'Leadership',
];
$roleLabel = $roleLabels[$_hlRole] ?? ucfirst($_hlRole ?? '');

$initials = '';
if ($_hlUser && !empty($_hlUser['name'])) {
    $parts    = explode(' ', trim($_hlUser['name']));
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $initials .= strtoupper(substr(end($parts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthLink</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/style.css">
</head>
<body>
<nav class="navbar">
    <div class="container">
        <a href="<?= BASE_PATH ?>/" class="navbar-brand">
            <img src="<?= BASE_PATH ?>/assets/images/ihcHEALTHLINK.png" alt="HealthLink">
        </a>
        <?php if ($_hlUser): ?>
        <div class="navbar-nav">
            <?php if ($_hlRole === 'community'): ?>
                <a href="<?= BASE_PATH ?>/pages/community_portal.php">My requests</a>
                <a href="<?= BASE_PATH ?>/pages/community_portal.php?tab=new">New request</a>
            <?php elseif ($_hlRole === 'staff'): ?>
                <a href="<?= BASE_PATH ?>/pages/staff_portal.php">Request queue</a>
                <a href="<?= BASE_PATH ?>/pages/staff_portal.php?tab=new">New request</a>
            <?php elseif ($_hlRole === 'admin'): ?>
                <a href="<?= BASE_PATH ?>/pages/admin_dashboard.php">Request queue</a>
                <a href="<?= BASE_PATH ?>/pages/admin_dashboard.php?filter=flagged">Flagged</a>
            <?php elseif ($_hlRole === 'leader'): ?>
                <a href="<?= BASE_PATH ?>/pages/leader_dashboard.php">Dashboard</a>
                <a href="<?= BASE_PATH ?>/pages/leader_dashboard.php?tab=approvals">Approvals</a>
            <?php endif; ?>
        </div>
        <div class="navbar-user">
            <div class="avatar avatar-sm avatar-<?= htmlspecialchars($_hlRole ?? 'community') ?>">
                <?= htmlspecialchars($initials) ?>
            </div>
            <span><?= htmlspecialchars($_hlUser['name'] ?? '') ?></span>
            <span class="user-role"><?= htmlspecialchars($roleLabel) ?></span>
            <a href="<?= BASE_PATH ?>/logout.php" class="btn btn-secondary btn-sm">Sign out</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
