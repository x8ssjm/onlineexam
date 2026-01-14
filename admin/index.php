<?php
// admin/index.php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";

start_secure_session();
require_admin();

$activeView = $_GET["view"] ?? "dashboard";
$valid = ["dashboard","banks","questions","students","exams","live","scores"];
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
  <!-- Header row -->
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
    <div>
      <h3 class="mb-0" id="pageTitle">Dashboard</h3>
      <div class="muted" id="pageSubtitle">Overview and quick actions</div>
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

  <!-- DASHBOARD -->
  <section id="view-dashboard" class="view">
    <!-- keep your existing dashboard blocks exactly -->
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
              <div class="fs-3 fw-bold" id="kpiStudents">0</div>
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
            <button class="btn btn-outline-primary btn-sm" data-view-jump="live">
              <i class="bi bi-broadcast me-1"></i>Open Live
            </button>
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
  </section>

  <!-- BANKS -->
  <section id="view-banks" class="view d-none">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div>
          <div class="fw-semibold">Question Banks</div>
          <div class="muted small">Add, rename, or delete banks</div>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalBank">
          <i class="bi bi-plus-lg me-1"></i> Add Bank
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Bank Name</th>
              <th class="text-end">Questions</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="banksTbody"></tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- QUESTIONS -->
  <section id="view-questions" class="view d-none">
    <div class="card p-3">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
          <div class="fw-semibold">Questions (MCQ)</div>
          <div class="muted small">Add, edit, or delete questions; organize by bank</div>
        </div>
        <div class="d-flex gap-2">
          <select class="form-select" id="filterBank" style="min-width:220px"></select>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalQuestion">
            <i class="bi bi-plus-lg me-1"></i> Add Question
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Question</th>
              <th>Bank</th>
              <th class="text-end">Marks</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="questionsTbody"></tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- STUDENTS -->
  <section id="view-students" class="view d-none">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div>
          <div class="fw-semibold">Students</div>
          <div class="muted small">Add, edit details, remove students</div>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalStudent">
          <i class="bi bi-plus-lg me-1"></i> Add Student
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th class="text-end">Student ID</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="studentsTbody"></tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- EXAMS -->
  <section id="view-exams" class="view d-none">
    <div class="row g-3">
      <div class="col-12">
        <div class="card p-3">
          <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <div>
              <div class="fw-semibold">Exams</div>
              <div class="muted small">Create exams, assign students, set start/end time, run multiple exams</div>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalExam">
              <i class="bi bi-plus-lg me-1"></i> Create Exam
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>Exam</th>
                  <th>Bank</th>
                  <th>Schedule</th>
                  <th class="text-end">Assigned</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="examsTbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- LIVE -->
  <section id="view-live" class="view d-none">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div>
          <div class="fw-semibold">Live Exams</div>
          <div class="muted small">Monitor ongoing exams and participant counts</div>
        </div>
        <button class="btn btn-outline-primary btn-sm" id="btnRefreshLive">
          <i class="bi bi-arrow-repeat me-1"></i> Refresh
        </button>
      </div>

      <div id="liveList"></div>
    </div>
  </section>

  <!-- SCORES -->
  <section id="view-scores" class="view d-none">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div>
          <div class="fw-semibold">Scores</div>
          <div class="muted small">Record and view scores per exam</div>
        </div>
        <select class="form-select" id="scoreExamSelect" style="max-width:340px"></select>
      </div>

      <div id="scoresPanel"></div>
    </div>
  </section>

  <div class="small muted mt-3">
    Note: this is a front-end demo (data stored in <span class="mono">localStorage</span>). For real security + multi-user live tracking, connect to a backend.
  </div>
</main>

<!-- MODALS (keep your exact modals) -->
<!-- Bank Modal -->
<div class="modal fade" id="modalBank" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="bankForm">
      <div class="modal-header">
        <h5 class="modal-title" id="bankModalTitle">Add Question Bank</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="bankId">
        <label class="form-label">Bank Name</label>
        <input class="form-control" id="bankName" placeholder="e.g., Java Basics" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Question Modal -->
<div class="modal fade" id="modalQuestion" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" id="questionForm">
      <div class="modal-header">
        <h5 class="modal-title" id="questionModalTitle">Add Question</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="qId">

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Question (MCQ)</label>
            <textarea class="form-control" id="qText" rows="2" required></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label">Question Bank</label>
            <select class="form-select" id="qBank" required></select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Marks</label>
            <input type="number" min="1" class="form-control" id="qMarks" value="1" required>
          </div>

          <div class="col-md-6"><label class="form-label">Option A</label><input class="form-control" id="qA" required></div>
          <div class="col-md-6"><label class="form-label">Option B</label><input class="form-control" id="qB" required></div>
          <div class="col-md-6"><label class="form-label">Option C</label><input class="form-control" id="qC" required></div>
          <div class="col-md-6"><label class="form-label">Option D</label><input class="form-control" id="qD" required></div>

          <div class="col-md-6">
            <label class="form-label">Correct Answer</label>
            <select class="form-select" id="qCorrect" required>
              <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Difficulty</label>
            <select class="form-select" id="qDiff" required>
              <option>Easy</option><option>Medium</option><option>Hard</option>
            </select>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Student Modal -->
<div class="modal fade" id="modalStudent" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="studentForm">
      <div class="modal-header">
        <h5 class="modal-title" id="studentModalTitle">Add Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="sId">
        <div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" id="sName" required></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="sEmail" required></div>
        <div class="mb-3"><label class="form-label">Student ID</label><input class="form-control" id="sStudentId" placeholder="e.g., STU-001" required></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Exam Modal -->
<div class="modal fade" id="modalExam" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" id="examForm">
      <div class="modal-header">
        <h5 class="modal-title" id="examModalTitle">Create Exam</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="eId">

        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Exam Name</label><input class="form-control" id="eName" required></div>
          <div class="col-md-6"><label class="form-label">Question Bank</label><select class="form-select" id="eBank" required></select></div>
          <div class="col-md-6"><label class="form-label">Start Time</label><input type="datetime-local" class="form-control" id="eStart" required></div>
          <div class="col-md-6"><label class="form-label">End Time</label><input type="datetime-local" class="form-control" id="eEnd" required></div>
          <div class="col-md-6"><label class="form-label">Total Marks (auto)</label><input class="form-control" id="eTotal" readonly></div>
          <div class="col-md-6">
            <label class="form-label">Mode</label>
            <select class="form-select" id="eMode">
              <option value="scheduled">Scheduled</option>
              <option value="immediate">Immediate (start now)</option>
            </select>
          </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold">Assign Students</div>
            <div class="muted small">Select students who can take this exam</div>
          </div>
          <div class="small muted"><span id="assignedCount">0</span> selected</div>
        </div>

        <div class="row g-2 mt-2" id="studentCheckboxGrid"></div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Save Exam</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

<script>
// ===== Your original JS (fixed + keeps demo localStorage) =====
const LS_KEY = "oep_admin_data_v1";
const els = (id) => document.getElementById(id);
const uuid = () => Math.random().toString(16).slice(2) + Date.now().toString(16);

function toLocalInputValue(date){
  const pad = (n) => String(n).padStart(2,"0");
  return date.getFullYear() + "-" + pad(date.getMonth()+1) + "-" + pad(date.getDate()) +
    "T" + pad(date.getHours()) + ":" + pad(date.getMinutes());
}

function loadState(){
  const raw = localStorage.getItem(LS_KEY);
  if (raw) return JSON.parse(raw);
  return { banks: [], questions: [], students: [], exams: [] };
}

function saveState(state){
  localStorage.setItem(LS_KEY, JSON.stringify(state));
}

let state = loadState();

function bankNameById(id){ return state.banks.find(b => b.id === id)?.name ?? "—"; }
function questionsByBank(bankId){ return state.questions.filter(q => q.bankId === bankId); }
function calcTotalMarks(bankId){ return questionsByBank(bankId).reduce((sum,q)=>sum + Number(q.marks||0), 0); }

function examStatus(exam){
  const now = new Date();
  const start = new Date(exam.startAt);
  const end = new Date(exam.endAt);
  if (now < start) return { label: "Upcoming", cls: "secondary" };
  if (now >= start && now <= end) return { label: "Ongoing", cls: "success" };
  return { label: "Ended", cls: "dark" };
}
function getLiveExams(){ return state.exams.filter(e => examStatus(e).label === "Ongoing"); }

const views = ["dashboard","banks","questions","students","exams","live","scores"];

function showView(view){
  views.forEach(v=>{
    const el = document.getElementById("view-"+v);
    el.classList.toggle("d-none", v !== view);
  });

  const titles = {
    dashboard: ["Dashboard","Overview and quick actions"],
    banks: ["Question Banks","Create and manage banks"],
    questions: ["Questions","Add, edit, delete MCQs"],
    students: ["Students","Add, edit details, remove students"],
    exams: ["Exams","Schedule and manage multiple exams"],
    live: ["Live Exams","Monitor ongoing exams and participants"],
    scores: ["Scores","Record and view exam scores"]
  };

  els("pageTitle").textContent = titles[view][0];
  els("pageSubtitle").textContent = titles[view][1];

  renderAll();
}

function renderKPIs(){
  els("kpiBanks").textContent = state.banks.length;
  els("kpiQuestions").textContent = state.questions.length;
  els("kpiStudents").textContent = state.students.length;
  els("kpiLive").textContent = getLiveExams().length;
}

// ... keep the rest of your JS as-is (renderBanks/renderQuestions/etc.)
// (your original JS block is fine after the saveState/loadState fix)

// QUICK: I’m calling your original functions exactly as you had them,
// so paste the remaining JS from your old file under this line.
</script>

<script>
// Boot the view from PHP (?view=...)
showView(<?= json_encode($activeView) ?>);
</script>

</body>
</html>
