<?php
// HealthLink — Auth helpers
// W3Schools best practices: session guard, role checks, password_verify()

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Require one of the given roles. Redirects to login if not logged in,
 * returns 403 if role does not match.
 */
function require_role(string ...$roles): void {
    require_login();
    if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
        http_response_code(403);
        die('<h2 style="font-family:sans-serif;padding:20px;">Access denied. Required role: ' . htmlspecialchars(implode(' or ', $roles)) . '</h2>');
    }
}

/** Return all current-user session data as an array. */
function current_user(): array {
    return [
        'id'    => $_SESSION['user_id']    ?? null,
        'name'  => $_SESSION['user_name']  ?? null,
        'role'  => $_SESSION['user_role']  ?? null,
        'username' => $_SESSION['username'] ?? null,
        'lang'  => $_SESSION['user_lang']  ?? 'en',
        'org'   => $_SESSION['user_org']   ?? null,
        'email' => $_SESSION['user_email'] ?? null,
    ];
}

/** Populate session from a users table row. */
function login_user(array $user): void {
    session_regenerate_id(true); // prevent session fixation
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
