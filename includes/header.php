<?php
require_once __DIR__ . '/../includes/auth.php';
startSession();
$_hlUser = $_SESSION['user'] ?? null;
$_hlRole = $_SESSION['role'] ?? null;

$roleLabels = [
    'community' => 'Community partner',
    'staff'     => 'Staff',
    'admin'     => 'Admin',
    'leader'    => 'Leadership',
];
$roleLabel = $roleLabels[$_hlRole] ?? ucfirst($_hlRole ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthLink</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<nav class="navbar">
    <div class="container">

        <!-- Logo -->
        <a href="/" class="navbar-brand">
            <img src="/assets/images/ihcHEALTHLINK.png" alt="HealthLink">
        </a>

        <!-- Nav links (role-based) -->
        <?php if ($_hlUser): ?>
        <div class="navbar-nav">
            <?php if ($_hlRole === 'community'): ?>
                <a href="/pages/community_portal.php">My requests</a>
                <a href="/pages/community_portal.php?tab=new">New request</a>
            <?php elseif ($_hlRole === 'staff'): ?>
                <a href="/pages/staff_portal.php">My requests</a>
                <a href="/pages/staff_portal.php?tab=new">New request</a>
            <?php elseif ($_hlRole === 'admin'): ?>
                <a href="/pages/admin_dashboard.php">Request queue</a>
                <a href="/pages/admin_dashboard.php?filter=flagged">Flagged</a>
            <?php elseif ($_hlRole === 'leader'): ?>
                <a href="/pages/leader_dashboard.php">Dashboard</a>
                <a href="/pages/leader_dashboard.php?tab=reports">Reports</a>
            <?php endif; ?>
        </div>

        <!-- User info + sign out -->
        <div class="navbar-user">
            <div class="avatar avatar-sm avatar-<?= htmlspecialchars($_hlRole ?? 'community') ?>">
                <?= strtoupper(substr($_hlUser['full_name'], 0, 1)) ?>
            </div>
            <span><?= htmlspecialchars($_hlUser['full_name']) ?></span>
            <span class="user-role"><?= htmlspecialchars($roleLabel) ?></span>
            <a href="/logout.php" class="btn btn-secondary btn-sm">Sign out</a>
        </div>
        <?php endif; ?>

    </div>
</nav>
