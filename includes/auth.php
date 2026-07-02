<?php
/**
 * Auth helper functions.
 * Requires config.php to already be included (session + $conn).
 */

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_role(): ?string {
    return $_SESSION['role'] ?? null;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if (current_role() !== $role) {
        header('Location: /login.php');
        exit;
    }
}

/** Basic CSRF token helpers */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}