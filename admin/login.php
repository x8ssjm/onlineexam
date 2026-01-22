<?php
// admin/login.php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../connection/db.php";

start_secure_session();

if (!empty($_SESSION["admin_id"])) {
  header("Location: index.php?view=dashboard");
  exit;
}

$info = "";
$error = "";

if (isset($_GET["timeout"])) $info = "Session expired. Please log in again.";
if (isset($_GET["logged_out"])) $info = "You have been logged out.";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  $password = (string)($_POST["password"] ?? "");

  $stmt = $conn->prepare("SELECT id, email, password FROM admins WHERE email = ? LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($row = $res->fetch_assoc()) {
    if (password_verify($password, $row["password"])) {
      $_SESSION["admin_id"] = (int)$row["id"];
      $_SESSION["admin_username"] = $row["email"];
      $_SESSION["LAST_ACTIVITY"] = time();

      header("Location: index.php?view=dashboard");
      exit;
    } else {
      $error = "Invalid email or password.";
    }
  } else {
    $error = "Invalid email or password.";
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login • Online Exam Portal</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    body { background:#f6f7fb; }
    .card { border:0; border-radius:16px; box-shadow: 0 10px 30px rgba(0,0,0,.06); }
  </style>
</head>
<body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">

      <div class="text-center mb-3">
        <i class="bi bi-shield-lock fs-1 text-primary"></i>
        <div class="fw-bold fs-4">Online Exam Portal</div>
        <div class="text-secondary">Admin Login</div>
      </div>

      <div class="card p-4">
        <?php if ($info): ?>
          <div class="alert alert-info"><?= htmlspecialchars($info) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" required autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
              <input class="form-control" id="password" type="password" name="password" required>
              <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i class="bi bi-eye" id="toggleIcon"></i>
              </button>
            </div>
          </div>

          <button class="btn btn-primary w-100" type="submit">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  const pw = document.getElementById("password");
  const btn = document.getElementById("togglePassword");
  const icon = document.getElementById("toggleIcon");

  btn.addEventListener("click", function () {
    const hidden = pw.type === "password";
    pw.type = hidden ? "text" : "password";
    icon.classList.toggle("bi-eye", !hidden);
    icon.classList.toggle("bi-eye-slash", hidden);
  });
})();
</script>
</body>
</html>
