<?php
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function requireLogin(): void {
    startSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: /index.php'); exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        header('Location: /index.php?error=unauthorized'); exit;
    }
}

function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']);
}

function currentUser(): array {
    startSession();
    return $_SESSION['user'] ?? [];
}

function formatRequestType(string $type): string {
    return [
        'mailing'         => 'Mailing',
        'presentation'    => 'Presentation',
        'inperson_support'=> 'In-person support',
    ][$type] ?? $type;
}

function formatStatus(string $status): string {
    return ucfirst(str_replace('_', ' ', $status));
}

function priorityColor(int $score): string {
    if ($score >= 8) return '#E24B4A';
    if ($score >= 6) return '#BA7517';
    return '#639922';
}
