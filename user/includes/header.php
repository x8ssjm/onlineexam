<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Portal • Online Exam</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7fb; font-family: 'Inter', sans-serif; }
    .navbar { background: #111827; }
    .navbar-brand { font-weight: 700; color: #fff !important; }
    .card { border:0; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.06); }
    .btn-primary { border-radius: 10px; padding: 10px 20px; font-weight: 600; }
    .muted { color:#6b7280; }
    .bg-gradient-primary { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); color: white; }
    .exam-card { transition: transform 0.2s; cursor: pointer; }
    .exam-card:hover { transform: translateY(-5px); }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom sticky-top py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <i class="bi bi-mortarboard-fill text-primary fs-3"></i>
      <span>Online Exam Portal</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="bi bi-list text-white fs-4"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto align-items-center gap-2 mt-3 mt-lg-0">
        <li class="nav-item">
          <a class="nav-link text-white small px-3" href="index.php?view=dashboard">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white small px-3" href="index.php?view=exams">My Exams</a>
        </li>
        <li class="nav-item dropdown ms-lg-3">
          <a class="nav-link dropdown-toggle btn btn-outline-light btn-sm text-white px-3" href="#" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['student_name'] ?? 'Student') ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
            <li><a class="dropdown-item py-2" href="logout.php"><i class="bi bi-box-arrow-left me-2 text-danger"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-5">
