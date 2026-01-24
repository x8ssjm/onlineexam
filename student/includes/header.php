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

    :root {
        --header-height: 60px;
        --sidebar-width: 280px;
    }
    body { background:#f9fafb; font-family: 'Inter', sans-serif; } /* Lighter gray background */
    
    /* Header - Solid Blue */
    .navbar { 
        background-color: #1e40af; /* Deep Royal Blue (Tailwind Blue-800 approx) */
        height: var(--header-height);
        z-index: 1030;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    .navbar-brand { font-weight: 700; color: #fff !important; font-size: 1.25rem; letter-spacing: 0.5px; }

    /* Content Wrapper */
    .app-wrapper {
        margin-top: var(--header-height);
        margin-left: var(--sidebar-width);
        padding: 2rem;
        min-height: calc(100vh - var(--header-height));
        display: flex;
        flex-direction: column;
    }
    
    .card { border:0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
    .btn-primary { border-radius: 8px; font-weight: 500; }
    
    @media (max-width: 991.98px) {
        .sidebar { transform: translateX(-100%); transition: transform 0.3s ease-in-out; }
        .sidebar.show { transform: translateX(0); }
        .app-wrapper { margin-left: 0; }
    }
  </style>
</head>
<body>

<!-- Header -->
<nav class="navbar fixed-top border-bottom border-white border-opacity-10">
  <div class="container-fluid px-4">
    <div class="d-flex align-items-center w-100 position-relative">
        <!-- Mobile Toggle -->
        <button class="btn text-white d-lg-none me-3" type="button" onclick="document.querySelector('.sidebar').classList.toggle('show')">
            <i class="bi bi-list fs-4"></i>
        </button>
        
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2 me-auto" href="index.php">
          <i class="bi bi-mortarboard-fill text-white fs-4"></i>
          <span>Online Exam Portal</span>
        </a>
    </div>
  </div>
</nav>

<!-- Layout Container -->
<div class="d-flex">
    <?php require_once __DIR__ . "/sidebar.php"; ?>
    <main class="app-wrapper w-100">
