<?php
// Fetch KPIs
$totalStudents = 0;
if(isset($conn)){
    $res = $conn->query("SELECT COUNT(*) FROM students");
    if($res) $totalStudents = $res->fetch_row()[0];
}
?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
  <div>
    <h3 class="mb-0">Dashboard</h3>
    <div class="muted">Overview and quick actions</div>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-secondary" id="btnResetDemo">
      <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Demo Data
    </button>
    <button class="btn btn-primary" id="btnSeed">
      <i class="bi bi-lightning-charge me-1"></i> Load Sample Data
    </button>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="muted small">Question Banks</div>
          <div class="fs-3 fw-bold" id="kpiBanks">0</div>
        </div>
        <span class="badge rounded-pill badge-soft"><i class="bi bi-collection me-1"></i> Banks</span>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="muted small">Questions</div>
          <div class="fs-3 fw-bold" id="kpiQuestions">0</div>
        </div>
        <span class="badge rounded-pill badge-soft"><i class="bi bi-question-circle me-1"></i> MCQ</span>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="muted small">Students</div>
          <div class="fs-3 fw-bold" id="kpiStudents"><?= $totalStudents ?></div>
        </div>
        <span class="badge rounded-pill badge-soft"><i class="bi bi-people me-1"></i> Users</span>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-6 col-xl-3">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="muted small">Ongoing Exams</div>
          <div class="fs-3 fw-bold" id="kpiLive">0</div>
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
      <div class="mt-3" id="dashLiveList"></div>
    </div>
  </div>

  <div class="col-12 col-xl-5">
    <div class="card p-3">
      <div class="fw-semibold">Quick Actions</div>
      <div class="muted small mb-3">Create and manage core items</div>
      <div class="d-grid gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalBank">+ Add Question Bank</button>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalQuestion">+ Add Question</button>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalStudent">+ Add Student</button>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalExam">+ Create Exam</button>
      </div>
    </div>
  </div>
</div>
