<?php
// admin/admin_insert.php
require_once __DIR__ . "/../connection/db.php";

$email = "admin@admin.com";
$pass  = "Admin@1234#";
$name  = "System Admin";

$msg = "";

// Check if exists
$stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();

if ($stmt->get_result()->fetch_assoc()) {
    $msg = "Admin already exists: $email";
} else {
    // Insert
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admins (email, password, full_name) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $hash, $name);
    
    if ($stmt->execute()) {
        $msg = "Inserted admin: $email / $pass";
    } else {
        $msg = "Error: " . $conn->error;
    }
}
?>
<!doctype html>
<title>Insert Admin</title>
<style>body{font-family:sans-serif;padding:2rem}</style>

<h1>Insert Admin Script</h1>
<p><?= $msg ?></p>

<p><a href="login.php">Go to Login</a></p>
