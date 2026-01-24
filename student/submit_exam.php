<?php
// user/submit_exam.php
declare(strict_types=1);
require_once __DIR__ . "/../connection/db.php";
require_once __DIR__ . "/includes/auth.php";

$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($submission_id) {
    // 1. Calculate final score
    $q_score = "SELECT SUM(marks) FROM student_answers WHERE submission_id = $submission_id";
    $res_score = mysqli_query($conn, $q_score);
    $total_marks = (float)mysqli_fetch_row($res_score)[0];

    // Get total possible marks
    $q_total = "SELECT COUNT(*) * e.question_weight 
                FROM student_answers sa 
                JOIN exam_submissions s ON sa.submission_id = s.submission_id 
                JOIN exams e ON s.exam_id = e.exam_id 
                WHERE sa.submission_id = $submission_id";
    $res_total = mysqli_query($conn, $q_total);
    $max_marks = (float)mysqli_fetch_row($res_total)[0];

    $percentage = ($max_marks > 0) ? round(($total_marks / $max_marks) * 100, 1) : 0;

    // 2. Close submission
    $update = "UPDATE exam_submissions 
               SET status = 'submitted', score = $percentage, end_time = CURRENT_TIMESTAMP 
               WHERE submission_id = $submission_id";
    mysqli_query($conn, $update);
}

header("Location: index.php?view=dashboard&submitted=1");
exit;
?>
