<?php
/**
 * CampusDesk — Helper Utility Functions
 * File: includes/functions.php
 */

declare(strict_types=1);

/**
 * Sanitize output string to prevent Cross-Site Scripting (XSS)
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash alert message (stored in session)
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION["flash_{$type}"] = $message;
}

/**
 * Retrieve and clear flash alert message
 */
function get_flash(string $type): ?string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION["flash_{$type}"])) {
        $msg = $_SESSION["flash_{$type}"];
        unset($_SESSION["flash_{$type}"]);
        return $msg;
    }
    return null;
}

/**
 * Calculate attendance percentage with division-by-zero safeguard
 * Rule: Present counts as 1.0, Late counts as 0.5, Absent counts as 0.0
 */
function calculate_attendance_percentage(int $present, int $late, int $total): float {
    if ($total <= 0) {
        return 0.0;
    }
    $effective_presence = $present + ($late * 0.5);
    $percentage = ($effective_presence / $total) * 100;
    return round(min(100.0, max(0.0, $percentage)), 1);
}

/**
 * Grade evaluation logic (Academic pass threshold 40%)
 */
function determine_grade(float $percentage): array {
    if ($percentage >= 90) {
        return ['grade' => 'O', 'status' => 'Pass', 'label' => 'Outstanding'];
    } elseif ($percentage >= 80) {
        return ['grade' => 'A+', 'status' => 'Pass', 'label' => 'Excellent'];
    } elseif ($percentage >= 70) {
        return ['grade' => 'A', 'status' => 'Pass', 'label' => 'Very Good'];
    } elseif ($percentage >= 60) {
        return ['grade' => 'B+', 'status' => 'Pass', 'label' => 'Good'];
    } elseif ($percentage >= 50) {
        return ['grade' => 'B', 'status' => 'Pass', 'label' => 'Above Average'];
    } elseif ($percentage >= 40) {
        return ['grade' => 'C', 'status' => 'Pass', 'label' => 'Pass'];
    } else {
        return ['grade' => 'F', 'status' => 'Fail', 'label' => 'Needs Improvement'];
    }
}

/**
 * Format currency amount into Indian Rupee symbol format
 */
function format_currency(float|int|string $amount): string {
    $num = (float)$amount;
    return '₹' . number_format($num, 2);
}

/**
 * Format dates consistently (e.g. 15 Oct 2024)
 */
function format_date(?string $date_str): string {
    if (empty($date_str)) {
        return '—';
    }
    $time = strtotime($date_str);
    return $time ? date('d M Y', $time) : $date_str;
}
