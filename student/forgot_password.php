<?php
// user/forgot_password.php
declare(strict_types=1);
require_once __DIR__ . "/../connection/db.php";
require_once __DIR__ . "/includes/auth.php";





$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // 1. Check if student exists
        $stmt = $conn->prepare("SELECT id, full_name FROM students WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($student = $res->fetch_assoc()) {
            // 2. Fetch EmailJS Settings
            $settings_res = $conn->query("SELECT * FROM settings");
            $email_settings = [];
            if($settings_res) {
                while ($row = $settings_res->fetch_assoc()) {
                    $email_settings[$row['setting_key']] = $row['setting_value'];
                }
            }
            
            $service_id = $email_settings['reset_service_id'] ?? '';
            $template_id = $email_settings['reset_template_id'] ?? '';
            $public_key = $email_settings['reset_public_key'] ?? ''; // This is 'user_id' in API
            
            if ($service_id && $template_id && $public_key) {
                // 3. Reset Password
                $plainPass = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
                $hashedPass = password_hash($plainPass, PASSWORD_DEFAULT);
                
                $upd = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
                $upd->bind_param("si", $hashedPass, $student['id']);
                
                if ($upd->execute()) {
                    // 4. Send Email via EmailJS API
                    $data = [
                        'service_id' => $service_id,
                        'template_id' => $template_id,
                        'user_id' => $public_key,
                        'template_params' => [
                            'to_name' => $student['full_name'],
                            'to_email' => $email,
                            'password' => $plainPass
                        ]
                    ];
                    
                    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Origin: http://' . $_SERVER['HTTP_HOST'] // Attempt to simulate origin
                    ]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    // Disable SSL verification for local WAMP usage
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    
                    if (curl_errno($ch)) {
                        $curlErr = curl_error($ch);
                        $error = "Failed to connect to email service: " . htmlspecialchars($curlErr);
                    } else {
                        if ($httpCode === 200 || $httpCode === 201 || trim($response) === 'OK') {
                             $message = "A new password has been sent to your email address.";
                        } else {
                            // capture the actual error from EmailJS
                            $error = "Email Server Error ($httpCode): " . htmlspecialchars($response);
                        }
                    }
                    curl_close($ch);

                } else {
                    $error = "Database error. Please try again.";
                }
            } else {
                $error = "Email system configuration missing. Please contact admin.";
            }

        } else {
             $error = "Email address not found in our records."; 
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reset Password • Online Exam</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { background: #f6f7fb; font-family: 'Inter', sans-serif; height: 100vh; display: flex; align-items: center; }
    .login-card { border: 0; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; max-width: 450px; width: 100%; margin: auto; }
    .card-header { background: #111827; color: white; padding: 40px 20px; text-align: center; border:0; }
    .btn-primary { background: #2563eb; border: 0; padding: 12px; font-weight: 600; border-radius: 12px; }
    .form-control { padding: 12px; border-radius: 10px; border: 1px solid #e5e7eb; }
    .form-control:focus { box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); border-color: #2563eb; }
  </style>
</head>
<body>
  <div class="login-card bg-white">
    <div class="card-header">
       <div class="mb-2"><i class="bi bi-key fs-1 text-primary"></i></div>
       <h4 class="mb-0 fw-bold">Reset Password</h4>
       <div class="small opacity-75">Enter your email to receive a new password</div>
    </div>
    <div class="p-4 p-md-5">
      <?php if ($message): ?>
        <div class="alert alert-success small py-2 mb-4"><?= $message ?></div>
        <a href="login.php" class="btn btn-outline-primary w-100">Back to Login</a>
      <?php else: ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger small py-2 mb-4"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
            <label class="form-label small fw-bold">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="yourname@example.com" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">Reset Password</button>
            <div class="text-center">
                <a href="login.php" class="text-decoration-none small text-muted">Back to Login</a>
            </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
