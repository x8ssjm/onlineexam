<?php
// admin/index.php
declare(strict_types=1);
ob_start();

require_once __DIR__ . "/../connection/db.php";
require_once __DIR__ . "/includes/auth.php";

start_secure_session();
require_admin();

$activeView = $_GET["view"] ?? "dashboard";
$valid = ["dashboard","banks","questions","students","exams","live","scores","settings"];
if (!in_array($activeView, $valid, true)) $activeView = "dashboard";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Online Exam Portal • Admin</title>

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

  <style>
    :root { --sidebar-w: 280px; }
    body { background:#f6f7fb; }
    .app { min-height: 100vh; }
    .sidebar { width: var(--sidebar-w); background: #111827; color: #e5e7eb; }
    .sidebar .brand { font-weight: 700; letter-spacing: .2px; }
    .sidebar .nav-link { color:#cbd5e1; border-radius:.6rem; }
    .sidebar .nav-link:hover { background: rgba(255,255,255,.06); color:#fff; }
    .sidebar .nav-link.active { background: rgba(13,110,253,.22); color:#fff; }
    .content { width: 100%; }
    .card { border:0; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.06); }
    .badge-soft { background: rgba(13,110,253,.12); color:#0d6efd; }
    .table td, .table th { vertical-align: middle; }
    .muted { color:#6b7280; }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
    @media (max-width: 991.98px){ .sidebar { display:none; } }
  </style>
</head>

<body>

<?php require_once __DIR__ . "/includes/sidebar.php"; ?>

<!-- Main -->
<main class="container py-4">
  <?php
  // Dynamic include
  $file = __DIR__ . "/pages/{$activeView}.php";
  if (file_exists($file)) {
      require_once $file;
  } else {
      echo "<div class='alert alert-danger'>View not found: " . htmlspecialchars($activeView) . "</div>";
  }
  ?>


</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
