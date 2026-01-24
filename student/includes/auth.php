<?php
// user/includes/auth.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_student_logged_in(): bool {
    return isset($_SESSION['student_logged_in']) && $_SESSION['student_logged_in'] === true;
}

function require_student_login(): void {
    if (!is_student_logged_in()) {
        header("Location: login.php");
        exit;
    }
}
?>
