<?php
// HealthLink — Auth helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        require_once __DIR__ . '/../config/db.php';
        header('Location: ' . BASE_PATH . '/index.php');
        exit;
    }
}

function require_role(string ...$roles): void {
    require_login();
    if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
        http_response_code(403);
        die('<h2 style="font-family:sans-serif;padding:20px;">Access denied. Required role: ' . htmlspecialchars(implode(' or ', $roles)) . '</h2>');
    }
}

function current_user(): array {
    return [
        'id'       => $_SESSION['user_id']    ?? null,
        'name'     => $_SESSION['user_name']  ?? null,
        'role'     => $_SESSION['user_role']  ?? null,
        'username' => $_SESSION['username']   ?? null,
        'lang'     => $_SESSION['user_lang']  ?? 'en',
        'org'      => $_SESSION['user_org']   ?? null,
        'email'    => $_SESSION['user_email'] ?? null,
    ];
}

function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['user_lang']  = $user['preferred_lang'] ?? 'en';
    $_SESSION['user_org']   = $user['organization']   ?? null;
    $_SESSION['user_email'] = $user['email']           ?? null;
}

function logout_user(): void {
    session_unset();
    session_destroy();
}
