<?php
// admin/ajax_exams.php
declare(strict_types=1);
require_once __DIR__ . "/../connection/db.php";
require_once __DIR__ . "/includes/auth.php";

start_secure_session();
require_admin();

if (isset($_GET['action']) && $_GET['action'] === 'get_participants') {
    $eid = (int)$_GET['exam_id'];
    $res = mysqli_query($conn, "SELECT student_id FROM exam_participants WHERE exam_id=$eid");
    $ids = [];
    while($r = mysqli_fetch_row($res)) $ids[] = $r[0];
    header('Content-Type: application/json');
    echo json_encode($ids);
    exit;
}
?>
