<?php
// user/ajax_save_answer.php
declare(strict_types=1);
require_once __DIR__ . "/../connection/db.php";
require_once __DIR__ . "/includes/auth.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submission_id = (int)$_POST['submission_id'];
    $question_id = (int)$_POST['question_id'];
    $selected = mysqli_real_escape_string($conn, $_POST['selected']);

    // Check if submission is still ongoing and window is open
    $q_sub = "SELECT s.status, e.end_time 
              FROM exam_submissions s 
              JOIN exams e ON s.exam_id = e.exam_id 
              WHERE s.submission_id = $submission_id LIMIT 1";
    $res = mysqli_query($conn, $q_sub);
    $sub = mysqli_fetch_assoc($res);

    if ($sub && $sub['status'] === 'ongoing' && strtotime($sub['end_time']) > time()) {
        // Fetch original correct answer
        $q_orig = "SELECT correct_answer FROM questions WHERE question_id = $question_id LIMIT 1";
        $res_orig = mysqli_query($conn, $q_orig);
        $orig = mysqli_fetch_assoc($res_orig);
        $is_correct = ($orig['correct_answer'] === $selected) ? 1 : 0;

        // Fetch exam scoring rules
        $q_rules = "SELECT e.question_weight, e.negative_marking 
                    FROM exams e 
                    JOIN exam_submissions s ON e.exam_id = s.exam_id 
                    WHERE s.submission_id = $submission_id LIMIT 1";
        $res_rules = mysqli_query($conn, $q_rules);
        $rules = mysqli_fetch_assoc($res_rules);
        
        $marks = $is_correct ? (float)$rules['question_weight'] : -(float)$rules['negative_marking'];

        $update = "UPDATE student_answers 
                   SET selected_option = '$selected', is_correct = $is_correct, marks = $marks 
                   WHERE submission_id = $submission_id AND question_id = $question_id";
        mysqli_query($conn, $update);
    }
}
?>
