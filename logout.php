<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
logout_user();
header('Location: ' . BASE_PATH . '/index.php');
exit;
