<?php
/**
 * CampusDesk — CSRF Protection Helper
 * File: includes/csrf.php
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or retrieve the current session CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden HTML input field with the current CSRF token
 */
function csrf_field(): string {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate submitted CSRF token against session token using timing-safe comparison
 */
function validate_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify CSRF on POST requests or terminate with safe 403 error
 */
function verify_csrf_or_die(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submitted_token = $_POST['csrf_token'] ?? null;
        if (!validate_csrf_token($submitted_token)) {
            http_response_code(403);
            die("Security Error: Invalid or expired CSRF token. Please refresh the page and try again.");
        }
    }
}
