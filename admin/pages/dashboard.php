<?php
// admin/pages/dashboard.php

// Fetch KPIs
$totalStudents = 0;
$totalBanks = 0;
$totalQuestions = 0;

if(isset($conn)){
    // Students
    $resStudents = $conn->query("SELECT COUNT(*) FROM students");
    if($resStudents) $totalStudents = $resStudents->fetch_row()[0];
    
    // Question Banks
    $resBanks = $conn->query("SELECT COUNT(*) FROM question_banks");
    if($resBanks) $totalBanks = $resBanks->fetch_row()[0];
    
    // Questions
    $resQuestions = $conn->query("SELECT COUNT(*) FROM questions");
    if($resQuestions) $totalQuestions = $resQuestions->fetch_row()[0];

    // Ongoing Exams
    $now = date('Y-m-d H:i:s');
    $resLive = $conn->query("SELECT COUNT(*) FROM exams WHERE '$now' BETWEEN start_time AND end_time");
    $totalOngoing = ($resLive) ? $resLive->fetch_row()[0] : 0;
}
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
  <div>
    <h3 class="mb-0">Dashboard</h3>
    <div class="muted">Overview and quick actions</div>
  </div>
  <div class="d-flex gap-2">
    <!-- Legacy demo buttons removed -->
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="muted small">Question Banks</div>
          <div class="fs-3 fw-bold"><?= $totalBanks ?></div>
        </div>
        <span class="badge rounded-pill badge-soft"><i class="bi bi-collection me-1"></i> Banks</span>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="muted small">Questions</div>
          <div class="fs-3 fw-bold"><?= $totalQuestions ?></div>
        </div>
        <span class="badge rounded-pill badge-soft"><i class="bi bi-question-circle me-1"></i> MCQ</span>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="muted small">Students</div>
          <div class="fs-3 fw-bold"><?= $totalStudents ?></div>
        </div>
        <span class="badge rounded-pill badge-soft"><i class="bi bi-people me-1"></i> Users</span>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="muted small">Ongoing Exams</div>
          <div class="fs-3 fw-bold" id="kpiLive"><?= $totalOngoing ?></div>
        </div>
        <span class="badge rounded-pill badge-soft"><i class="bi bi-broadcast me-1"></i> Live</span>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-7">
    <div class="card p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <div class="fw-semibold">Live Exams</div>
          <div class="muted small">Exams currently running + participants</div>
        </div>
        <a href="index.php?view=live" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-broadcast me-1"></i>Open Live
        </a>
      </div>
      <div class="mt-3" id="dashLiveList">
        <?php
        $q_dash_live = "SELECT e.title, e.end_time,
                        (SELECT COUNT(*) FROM exam_submissions WHERE exam_id = e.exam_id AND status = 'ongoing') as active,
                        (SELECT COUNT(*) FROM exam_submissions WHERE exam_id = e.exam_id AND status = 'submitted') as finished
                        FROM exams e
                        WHERE '$now' BETWEEN e.start_time AND e.end_time
                        LIMIT 5";
        $res_dash_live = $conn->query($q_dash_live);
        if($res_dash_live && $res_dash_live->num_rows > 0): 
            while($dl = $res_dash_live->fetch_assoc()):
        ?>
            <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                <div>
                    <div class="small fw-bold"><?= htmlspecialchars($dl['title']) ?></div>
                    <div class="text-muted" style="font-size: 11px;">Ends: <?= date('g:i A', strtotime($dl['end_time'])) ?></div>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2"><?= $dl['active'] ?> Active</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 ms-1"><?= $dl['finished'] ?> Done</span>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div class="p-3 text-center text-muted small">No exams are currently live.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-5">
    <div class="card p-3 h-100">
      <div class="fw-semibold">Quick Actions</div>
      <div class="muted small mb-3">Create and manage core items</div>
      <div class="d-grid gap-2">
        <a href="index.php?view=questions&tab=banks" class="btn btn-primary text-start">
            <i class="bi bi-collection me-2"></i> Manage Question Banks
        </a>
        <a href="index.php?view=questions&tab=questions" class="btn btn-outline-primary text-start">
            <i class="bi bi-question-circle me-2"></i> Manage Questions
        </a>
        <a href="index.php?view=students" class="btn btn-outline-primary text-start">
            <i class="bi bi-people me-2"></i> Manage Students
        </a>
        <a href="index.php?view=scores" class="btn btn-outline-primary text-start">
            <i class="bi bi-trophy me-2"></i> View Scores
        </a>
      </div>
    </div>
  </div>
</div>
