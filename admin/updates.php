<?php
// admin/updates.php
declare(strict_types=1);

require_once __DIR__ . "/../connection/db.php";
$title = "System Update • Admin";
require_once __DIR__ . "/includes/header.php";

// Define the specific update to run
$sql = "ALTER TABLE students ADD COLUMN gender CHAR(1) NULL AFTER email";

$msg = "";
$status = "info";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if ($conn->query($sql)) {
            $status = "success";
            $msg = "Update executed successfully.";
        } else {
            // Check if error is because column doesn't exist (common 1091)
            if ($conn->errno === 1091) {
                $status = "warning";
                $msg = "Column 'is_active' does not exist (already removed).";
            } else {
                $status = "danger";
                $msg = "Error: " . $conn->error;
            }
        }
    } catch (Throwable $e) {
        $status = "danger";
        $msg = "Exception: " . $e->getMessage();
    }
}

require_once __DIR__ . "/includes/sidebar.php";
?>

<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">System Update</h3>
      <div class="muted">Run pending database changes</div>
    </div>
    <a href="index.php?view=dashboard" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Dashboard
    </a>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-md-8">
        <div class="card p-4">
            <h5 class="card-title">Pending Update</h5>
            <p class="text-secondary">The following SQL command will be executed to update your database structure:</p>
            
            <div class="bg-light p-3 rounded mb-3 border">
                <code class="text-dark fw-bold"><?= htmlspecialchars($sql) ?></code>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?= $status ?>">
                    <?php if($status==='success'): ?><i class="bi bi-check-circle-fill me-2"></i><?php endif; ?>
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <?php if ($status !== 'success'): ?>
            <form method="post">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-play-fill me-1"></i> Run Update
                </button>
            </form>
            <?php else: ?>
                <button class="btn btn-success" disabled>
                    <i class="bi bi-check-lg me-1"></i> Completed
                </button>
            <?php endif; ?>
        </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . "/includes/footer.php"; ?>