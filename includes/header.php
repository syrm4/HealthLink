<?php
require_once __DIR__ . '/../includes/auth.php';
startSession();
$_hlUser = $_SESSION['user'] ?? null;
$_hlRole = $_SESSION['role'] ?? null;
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
    <div class="nav-brand">HealthLink</div>
    <?php if ($_hlUser): ?>
    <div class="nav-user">
        <span class="nav-name"><?= htmlspecialchars($_hlUser['full_name']) ?></span>
        <span class="nav-role role-<?= htmlspecialchars($_hlRole ?? '') ?>"><?= ucfirst(htmlspecialchars($_hlRole ?? '')) ?></span>
        <a href="/logout.php" class="nav-logout">Sign out</a>
    </div>
    <?php endif; ?>
</nav>
