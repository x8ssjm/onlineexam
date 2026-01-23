<?php
// admin/ajax_groups.php
declare(strict_types=1);
require_once __DIR__ . "/../connection/db.php";
require_once __DIR__ . "/includes/auth.php";

start_secure_session();
require_admin();

if (isset($_GET['action']) && $_GET['action'] === 'get_group_students') {
    $current_group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
    
    // Fetch all students and their current group_id
    $q = "SELECT s.id, s.full_name, s.student_id, s.group_id, g.group_name 
          FROM students s 
          LEFT JOIN `groups` g ON s.group_id = g.group_id 
          ORDER BY s.full_name ASC";
    $res = mysqli_query($conn, $q);
    $students = [];
    while($r = mysqli_fetch_assoc($res)) {
        $students[] = [
            'id' => $r['id'],
            'name' => $r['full_name'],
            'sid' => $r['student_id'],
            'group_id' => $r['group_id'],
            'group_name' => $r['group_name']
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($students);
    exit;
}
?>
