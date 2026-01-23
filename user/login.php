<?php
// user/login.php
declare(strict_types=1);
require_once __DIR__ . "/../connection/db.php";
require_once __DIR__ . "/includes/auth.php";

if (is_student_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password FROM students WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($student = $res->fetch_assoc()) {
        if (password_verify($password, $student['password'])) {
            $_SESSION['student_logged_in'] = true;
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['student_name'] = $student['full_name'];
            header("Location: index.php");
            exit;
        }
    }
    $error = "Invalid email or password.";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Login • Online Exam</title>
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
    .input-group-text { background: transparent; border-color: #e5e7eb; border-radius: 0 10px 10px 0; cursor: pointer; }
    .form-control.has-toggle { border-right: 0; }
  </style>
</head>
<body>
  <div class="login-card bg-white">
    <div class="card-header">
       <div class="mb-2"><i class="bi bi-mortarboard fs-1 text-primary"></i></div>
       <h4 class="mb-0 fw-bold">Student Portal</h4>
       <div class="small opacity-75">Sign in to your account</div>
    </div>
    <div class="p-4 p-md-5">
      <?php if ($error): ?>
        <div class="alert alert-danger small py-2 mb-4"><?= $error ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label small fw-bold">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="yourname@example.com" required>
        </div>
        <div class="mb-4">
          <label class="form-label small fw-bold">Password</label>
          <div class="input-group">
            <input type="password" name="password" id="password" class="form-control has-toggle" placeholder="••••••••" required>
            <span class="input-group-text" id="togglePassword">
              <i class="bi bi-eye" id="toggleIcon"></i>
            </span>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-3">Login to Dashboard</button>
      </form>
    </div>
  </div>
  <script>
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const toggleIcon = document.getElementById('toggleIcon');

    togglePassword.addEventListener('click', function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      toggleIcon.classList.toggle('bi-eye');
      toggleIcon.classList.toggle('bi-eye-slash');
    });
  </script>
</body>
</html>
