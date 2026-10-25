<?php
/**
 * CampusDesk — Authentication & Role-Based Access Control (RBAC)
 * File: includes/auth.php
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    // Academic & Security Session Hardening
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/**
 * Check if a user is currently authenticated
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user details from session
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'user_id'    => $_SESSION['user_id'],
        'username'   => $_SESSION['username'] ?? '',
        'email'      => $_SESSION['email'] ?? '',
        'role'       => $_SESSION['role'] ?? '',
        'student_id' => $_SESSION['student_id'] ?? null,
        'full_name'  => $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'User')
    ];
}

/**
 * Require authentication or redirect to login
 */
function require_login(string $base_path = '../'): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = "Please log in to access this page.";
        header("Location: {$base_path}login.php");
        exit;
    }
}

/**
 * Enforce specific role (e.g., 'admin' or 'student')
 */
function require_role(string $role, string $base_path = '../'): void {
    require_login($base_path);
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die("<div style='font-family: sans-serif; padding: 2rem; max-width: 500px; margin: 40px auto; border: 1px solid #fca5a5; background: #fff1f2; border-radius: 8px;'>
            <h3 style='color: #991b1b; margin-top:0;'>403 Forbidden: Access Denied</h3>
            <p style='color: #7f1d1d;'>You do not have permission to access administrative operations with your current account.</p>
            <p><a href='{$base_path}login.php' style='color: #1e40af;'>Return to Login</a></p>
        </div>");
    }
}

/**
 * Log in a user and regenerate session ID to defend against session fixation
 */
function login_user(array $user_data): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user_data['user_id'];
    $_SESSION['username']   = $user_data['username'];
    $_SESSION['email']      = $user_data['email'];
    $_SESSION['role']       = $user_data['role'];
    $_SESSION['student_id'] = $user_data['student_id'] ?? null;
    $_SESSION['full_name']  = $user_data['full_name'] ?? $user_data['username'];
}

/**
 * Securely destroy session on logout
 */
function logout_user(string $redirect_url = 'login.php'): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    header("Location: {$redirect_url}");
    exit;
}
