<?php
$host = "localhost";
$user = "root";          // change if needed
$pass = "";              // change if needed
$db   = "online_exam_portal";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
