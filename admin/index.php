<?php
// admin/index.php
declare(strict_types=1);

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

<!-- MODALS -->
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

// --- Render Functions (Safeguarded) ---

function renderKPIs(){
  if(els("kpiBanks")) els("kpiBanks").textContent = state.banks.length;
  if(els("kpiQuestions")) els("kpiQuestions").textContent = state.questions.length;
  if(els("kpiStudents")) els("kpiStudents").textContent = state.students.length;
  if(els("kpiLive")) els("kpiLive").textContent = getLiveExams().length;
}

function renderBanks(){
  const tbody = els("banksTbody");
  if (!tbody) return;
  tbody.innerHTML = state.banks.map(b => `
    <tr>
      <td>${b.name}</td>
      <td class="text-end">${questionsByBank(b.id).length}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-secondary" onclick="editBank('${b.id}')"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-sm btn-outline-danger" onclick="delBank('${b.id}')"><i class="bi bi-trash"></i></button>
      </td>
    </tr>
  `).join("") || '<tr><td colspan="3" class="text-center muted">No banks found</td></tr>';
}

function renderQuestions(){
  const tbody = els("questionsTbody");
  if (!tbody) return;

  const filter = els("filterBank").value;
  let list = state.questions;
  if(filter) list = list.filter(q => q.bankId === filter);

  tbody.innerHTML = list.map(q => `
    <tr>
      <td>
        <div class="fw-semibold text-truncate" style="max-width:300px">${q.text}</div>
        <div class="small muted">Ans: ${q.correctOption} | Diff: ${q.difficulty}</div>
      </td>
      <td>${bankNameById(q.bankId)}</td>
      <td class="text-end">${q.marks}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-secondary" onclick="editQuestion('${q.id}')"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-sm btn-outline-danger" onclick="delQuestion('${q.id}')"><i class="bi bi-trash"></i></button>
      </td>
    </tr>
  `).join("") || '<tr><td colspan="4" class="text-center muted">No questions found</td></tr>';
}

function renderStudents(){
  // Handled server-side in pages/students.php
}

function renderExams(){
  const tbody = els("examsTbody");
  if(!tbody) return;

  tbody.innerHTML = state.exams.map(e => {
    const st = examStatus(e);
    return `
    <tr>
      <td>
        <div class="fw-semibold">${e.name}</div>
        <div class="small muted">${e.mode === 'immediate' ? 'Immediate' : 'Scheduled'}</div>
      </td>
      <td>${bankNameById(e.bankId)}</td>
      <td class="small">
        <div>Start: ${new Date(e.startAt).toLocaleString()}</div>
        <div>End: ${new Date(e.endAt).toLocaleString()}</div>
      </td>
      <td class="text-end">${e.assigned?.length||0}</td>
      <td><span class="badge bg-${st.cls}">${st.label}</span></td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-danger" onclick="delExam('${e.id}')"><i class="bi bi-trash"></i></button>
      </td>
    </tr>`;
  }).join("") || '<tr><td colspan="6" class="text-center muted">No exams found</td></tr>';
}

function renderLive(){
  const container = els("liveList");
  if(!container && !els("dashLiveList")) return;

  const live = getLiveExams();
  const html = live.map(e => `
    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
      <div>
        <div class="fw-bold">${e.name}</div>
        <div class="small muted">Ends: ${new Date(e.endAt).toLocaleTimeString()}</div>
      </div>
      <div class="text-end">
        <div class="fs-5 fw-bold text-primary">0</div>
        <div class="small muted">Active</div>
      </div>
    </div>
  `).join("") || '<div class="text-center muted py-3">No active exams right now.</div>';

  if(container) container.innerHTML = html;
  if(els("dashLiveList")) els("dashLiveList").innerHTML = html;
}

function renderScores(){
  const panel = els("scoresPanel");
  if(!panel) return;
  // Placeholder logic
  const sel = els("scoreExamSelect");
  const eid = sel.value;
  if(!eid) { panel.innerHTML = "<div class='text-center muted py-5'>Select an exam to view scores</div>"; return; }
  
  panel.innerHTML = "<div class='alert alert-info'>Score tracking requires backend participation tracking.</div>";
}

function populateSelects(){
  // Bank Select for Filter (Questions Page)
  const filter = els("filterBank");
  if(filter) {
    const curr = filter.value;
    filter.innerHTML = '<option value="">All Banks</option>' + state.banks.map(b => `<option value="${b.id}">${b.name}</option>`).join("");
    filter.value = curr;
    filter.onchange = renderQuestions;
  }
  
  // Bank Select for Modal (Question)
  const qBank = els("qBank");
  if(qBank) qBank.innerHTML = state.banks.map(b => `<option value="${b.id}">${b.name}</option>`).join("");
  
  // Bank Select for Modal (Exam)
  const eBank = els("eBank");
  if(eBank) {
    eBank.innerHTML = state.banks.map(b => `<option value="${b.id}">${b.name}</option>`).join("");
    eBank.onchange = function(){ els("eTotal").value = calcTotalMarks(this.value); };
  }

  // Student Checkboxes for Modal (Exam)
  const grid = els("studentCheckboxGrid");
  if(grid) {
    grid.innerHTML = state.students.map(s => `
      <div class="col-6">
        <div class="form-check">
          <input class="form-check-input student-check" type="checkbox" value="${s.id}" id="sc_${s.id}">
          <label class="form-check-label small" for="sc_${s.id}">${s.name}</label>
        </div>
      </div>
    `).join("");

    // listener for count
    grid.querySelectorAll(".student-check").forEach(ch => {
      ch.addEventListener("change", () => {
        els("assignedCount").textContent = grid.querySelectorAll(".student-check:checked").length;
      });
    });
  }
  
  // Exam Select for Scores
  const sSel = els("scoreExamSelect");
  if(sSel){
     sSel.innerHTML = '<option value="">Select Exam...</option>' + state.exams.map(e=>`<option value="${e.id}">${e.name}</option>`).join("");
     sSel.onchange = renderScores;
  }
}

function renderAll(){
  populateSelects();
  renderKPIs();
  renderBanks();
  renderQuestions();
  renderStudents();
  renderExams();
  renderLive();
  renderScores();
}

// --- CRUD & Actions ---

// ... (Rest of CRUD logics: saveBank, delBank, saveQuestion etc. - copied from before but verifying safety not strictly needed as they are triggered by Modals which exist globally)
// wait, modals exist globally, so saveBank is safe TO CALL.
// BUT saveBank calls renderBanks() at the end. renderBanks() is safe now. so we are good.

// Seed Data
if(els("btnSeed")){
    els("btnSeed").onclick = () => {
        if(!confirm("Load sample data? Overwrites current.")) return;
        state = {
            banks: [{id:"b1",name:"General Knowledge"},{id:"b2",name:"Math 101"}],
            questions: [
                {id:"q1",bankId:"b1",text:"Capital of France?",marks:1,options:{A:"Berlin",B:"Madrid",C:"Paris",D:"Rome"},difficulty:"Easy",correctOption:"C"},
                {id:"q2",bankId:"b1",text:"2+2=?",marks:1,options:{A:"3",B:"4",C:"5",D:"22"},difficulty:"Easy",correctOption:"B"}
            ],
            students: [],
            exams: []
        };
        saveState(state);
        renderAll();
    };
}
if(els("btnResetDemo")){
    els("btnResetDemo").onclick = () => {
        if(confirm("Clear all data?")) { localStorage.removeItem(LS_KEY); location.reload(); }
    };
}
if(els("btnRefreshLive")) els("btnRefreshLive").onclick = renderLive;


// --- Modal Handlers ---

// Bank
const mb = els("modalBank");
if(mb){
    mb.addEventListener("show.bs.modal", () => {
        els("bankForm").reset(); els("bankId").value=""; els("bankModalTitle").textContent="Add Question Bank";
    });
    els("bankForm").onsubmit = (e) => {
        e.preventDefault();
        const id = els("bankId").value || uuid();
        const name = els("bankName").value;
        if(!els("bankId").value) state.banks.push({id,name});
        else { const b = state.banks.find(x=>x.id===id); if(b) b.name=name; }
        saveState(state);
        bootstrap.Modal.getInstance(mb).hide();
        renderAll();
    };
}
window.editBank = (id) => {
    const b = state.banks.find(x=>x.id===id);
    if(!b) return;
    els("bankId").value=b.id; els("bankName").value=b.name;
    els("bankModalTitle").textContent="Edit Question Bank";
    new bootstrap.Modal(mb).show();
};
window.delBank = (id) => {
    if(!confirm("Delete this bank and all its questions?")) return;
    state.banks = state.banks.filter(x=>x.id!==id);
    state.questions = state.questions.filter(x=>x.bankId!==id);
    saveState(state); renderAll();
};

// Question
const mq = els("modalQuestion");
if(mq){
    mq.addEventListener("show.bs.modal", () => {
        els("questionForm").reset(); els("qId").value=""; els("questionModalTitle").textContent="Add Question";
        populateSelects(); // ensure bank list is fresh
    });
    els("questionForm").onsubmit = (e) => {
        e.preventDefault();
        const id = els("qId").value || uuid();
        const q = {
            id,
            bankId: els("qBank").value,
            text: els("qText").value,
            marks: els("qMarks").value,
            difficulty: els("qDiff").value,
            correctOption: els("qCorrect").value,
            options: { A:els("qA").value, B:els("qB").value, C:els("qC").value, D:els("qD").value }
        };
        if(!els("qId").value) state.questions.push(q);
        else { const idx = state.questions.findIndex(x=>x.id===id); if(idx>-1) state.questions[idx]=q; }
        saveState(state);
        bootstrap.Modal.getInstance(mq).hide();
        renderAll();
    };
}
window.editQuestion = (id) => {
    const q = state.questions.find(x=>x.id===id);
    if(!q) return;
    els("qId").value=q.id; els("qBank").value=q.bankId; els("qText").value=q.text;
    els("qMarks").value=q.marks; els("qDiff").value=q.difficulty; els("qCorrect").value=q.correctOption;
    els("qA").value=q.options.A; els("qB").value=q.options.B; els("qC").value=q.options.C; els("qD").value=q.options.D;
    els("questionModalTitle").textContent="Edit Question";
    new bootstrap.Modal(mq).show();
};
window.delQuestion = (id) => {
    if(confirm("Delete question?")) { state.questions = state.questions.filter(x=>x.id!==id); saveState(state); renderAll(); }
};

// Student
const ms = els("modalStudent");
if(ms){
    ms.addEventListener("show.bs.modal", () => {
        els("studentForm").reset(); els("sId").value=""; els("studentModalTitle").textContent="Add Student";
    });
    els("studentForm").onsubmit = (e) => {
        e.preventDefault();
        const id = els("sId").value || uuid();
        const s = { id, name: els("sName").value, email: els("sEmail").value, studentId: els("sStudentId").value };
        if(!els("sId").value) state.students.push(s);
        else { const idx = state.students.findIndex(x=>x.id===id); if(idx>-1) state.students[idx]=s; }
        saveState(state);
        bootstrap.Modal.getInstance(ms).hide();
        renderAll();
    };
}
window.editStudent = (id) => {
    const s = state.students.find(x=>x.id===id);
    if(!s) return;
    els("sId").value=s.id; els("sName").value=s.name; els("sEmail").value=s.email; els("sStudentId").value=s.studentId;
    els("studentModalTitle").textContent="Edit Student";
    new bootstrap.Modal(ms).show();
};
window.delStudent = (id) => {
    if(confirm("Delete student?")) { state.students = state.students.filter(x=>x.id!==id); saveState(state); renderAll(); }
};

// Exam
const me = els("modalExam");
if(me){
    me.addEventListener("show.bs.modal", () => {
        els("examForm").reset(); els("eId").value=""; els("examModalTitle").textContent="Create Exam";
        populateSelects(); els("assignedCount").textContent="0";
    });
    els("examForm").onsubmit = (e) => {
        e.preventDefault();
        const id = els("eId").value || uuid();
        const assigned = Array.from(document.querySelectorAll(".student-check:checked")).map(c=>c.value);
        const ex = {
            id,
            name: els("eName").value,
            bankId: els("eBank").value,
            startAt: els("eStart").value,
            endAt: els("eEnd").value,
            mode: els("eMode").value,
            assigned
        };
        state.exams.push(ex);
        saveState(state);
        bootstrap.Modal.getInstance(me).hide();
        renderAll();
    }; // (Edit exam logic omitted for brevity in original, can add if needed)
}
window.delExam = (id) => {
    if(confirm("Delete exam?")) { state.exams = state.exams.filter(x=>x.id!==id); saveState(state); renderAll(); }
};


// Initial Render
renderAll();
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
