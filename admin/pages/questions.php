<?php
// admin/pages/questions.php

// --- PHP HANDLERS ---

// Handle Bank Actions (Add/Edit)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_bank'])) {
    $bank_id = isset($_POST['bank_id']) ? (int)$_POST['bank_id'] : 0;
    $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    if ($bank_id > 0) {
        $query = "UPDATE question_banks SET bank_name='$bank_name', description='$description' WHERE bank_id=$bank_id";
    } else {
        $query = "INSERT INTO question_banks (bank_name, description) VALUES ('$bank_name', '$description')";
    }

    if (mysqli_query($conn, $query)) {
        header("Location: index.php?view=questions&tab=banks&success=" . ($bank_id > 0 ? "Bank updated" : "Bank added"));
        exit;
    } else {
        $error = mysqli_error($conn);
    }
}

// Handle Delete Bank
if (isset($_GET['delete_bank'])) {
    $bank_id = (int)$_GET['delete_bank'];
    if (mysqli_query($conn, "DELETE FROM question_banks WHERE bank_id=$bank_id")) {
        header("Location: index.php?view=questions&tab=banks&success=Bank deleted");
        exit;
    }
}

// Handle Question Actions (Add/Edit)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_question'])) {
    $qid = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
    $bank_id = (int)$_POST['bank_id'];
    $q_text = mysqli_real_escape_string($conn, $_POST['question_text']);
    $opt_a = mysqli_real_escape_string($conn, $_POST['option_a']);
    $opt_b = mysqli_real_escape_string($conn, $_POST['option_b']);
    $opt_c = mysqli_real_escape_string($conn, $_POST['option_c']);
    $opt_d = mysqli_real_escape_string($conn, $_POST['option_d']);
    $correct = mysqli_real_escape_string($conn, $_POST['correct_answer']);

    if ($qid > 0) {
        $query = "UPDATE questions SET bank_id=$bank_id, question_text='$q_text', option_a='$opt_a', option_b='$opt_b', option_c='$opt_c', option_d='$opt_d', correct_answer='$correct' WHERE question_id=$qid";
    } else {
        $query = "INSERT INTO questions (bank_id, question_text, option_a, option_b, option_c, option_d, correct_answer) 
                  VALUES ($bank_id, '$q_text', '$opt_a', '$opt_b', '$opt_c', '$opt_d', '$correct')";
    }

    if (mysqli_query($conn, $query)) {
        header("Location: index.php?view=questions&success=" . ($qid > 0 ? "Question updated" : "Question added"));
        exit;
    } else {
        $error = mysqli_error($conn);
    }
}

// Handle Delete Question
if (isset($_GET['delete_q'])) {
    $qid = (int)$_GET['delete_q'];
    if (mysqli_query($conn, "DELETE FROM questions WHERE question_id=$qid")) {
        header("Location: index.php?view=questions&success=Question deleted");
        exit;
    }
}

// --- DATA FETCHING ---

$activeTab = $_GET['tab'] ?? 'questions';
$filter_bank = isset($_GET['bank_id']) ? (int)$_GET['bank_id'] : 0;

// Fetch Banks for dropdowns and list
$query_banks = "SELECT qb.*, (SELECT COUNT(*) FROM questions WHERE bank_id = qb.bank_id) as question_count FROM question_banks qb ORDER BY bank_name";
$banks_res = mysqli_query($conn, $query_banks);

$banks = [];
if ($banks_res) {
    while ($b = mysqli_fetch_assoc($banks_res)) $banks[] = $b;
} else {
    $db_error = mysqli_error($conn);
}

// Fetch Questions
$q_query = "SELECT q.*, qb.bank_name FROM questions q JOIN question_banks qb ON q.bank_id = qb.bank_id";
if ($filter_bank > 0) $q_query .= " WHERE q.bank_id = $filter_bank";
$q_query .= " ORDER BY q.question_id DESC";
$q_res = mysqli_query($conn, $q_query);

if (!$q_res && !isset($db_error)) {
    $db_error = mysqli_error($conn);
}
?>

<?php if (isset($_GET['success'])): ?>
<script>
    Swal.fire({ icon: 'success', title: 'Success', text: '<?= htmlspecialchars($_GET['success']) ?>', timer: 2000, showConfirmButton: false });
</script>
<?php endif; ?>

<?php if (isset($error)): ?>
<script>
    Swal.fire({ icon: 'error', title: 'Error', text: '<?= htmlspecialchars($error) ?>' });
</script>
<?php endif; ?>

<div class="card p-3">
  <?php if (isset($db_error)): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <h5 class="alert-heading mb-1">Database Error!</h5>
                <p class="mb-0">The system encountered an error while fetching questions: <code><?= htmlspecialchars($db_error) ?></code></p>
            </div>
        </div>
        <hr>
        <p class="mb-0 small">
            <strong>Action Required:</strong> This usually means your database tables are missing or not updated. 
            Please open <strong>phpMyAdmin</strong> and execute the SQL code in <code>database/db.sql</code> to create the required <code>question_banks</code> and <code>questions</code> tables.
        </p>
    </div>
  <?php endif; ?>

  <!-- Tabs -->
  <ul class="nav nav-pills mb-3 gap-2" id="pills-tab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link <?= $activeTab === 'questions' ? 'active' : '' ?>" id="pills-questions-tab" data-bs-toggle="pill" data-bs-target="#pills-questions" type="button" role="tab">Questions</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?= $activeTab === 'banks' ? 'active' : '' ?>" id="pills-banks-tab" data-bs-toggle="pill" data-bs-target="#pills-banks" type="button" role="tab">Question Banks</button>
    </li>
  </ul>

  <div class="tab-content" id="pills-tabContent">
    
    <!-- QUESTIONS TAB -->
    <div class="tab-pane fade <?= $activeTab === 'questions' ? 'show active' : '' ?>" id="pills-questions" role="tabpanel">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
            <div class="fw-semibold">Questions (MCQ)</div>
            <div class="muted small">Manage questions for your exams</div>
            </div>
            <div class="d-flex gap-2">
            <select class="form-select" id="filterBank" style="min-width:220px" onchange="filterByBank(this.value)">
                <option value="">All Banks</option>
                <?php foreach ($banks as $b): ?>
                    <option value="<?= $b['bank_id'] ?>" <?= $filter_bank == $b['bank_id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['bank_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" onclick="openAddQuestionModal()">
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
                <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($q_res) > 0): while($q = mysqli_fetch_assoc($q_res)): ?>
                <tr>
                    <td>
                        <div class="fw-semibold text-truncate" style="max-width:350px"><?= htmlspecialchars($q['question_text']) ?></div>
                        <div class="small muted">Ans: <?= $q['correct_answer'] ?></div>
                    </td>
                    <td><?= htmlspecialchars($q['bank_name']) ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick='openEditQuestionModal(<?= htmlspecialchars(json_encode($q), ENT_QUOTES) ?>)'><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteQuestion(<?= $q['question_id'] ?>)"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="3" class="text-center muted py-4">No questions found.</td></tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>

    <!-- BANKS TAB -->
    <div class="tab-pane fade <?= $activeTab === 'banks' ? 'show active' : '' ?>" id="pills-banks" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <div>
            <div class="fw-semibold">Question Banks</div>
            <div class="muted small">Categories for organizing questions</div>
            </div>
            <button class="btn btn-primary" onclick="openAddBankModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Bank
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
            <thead>
                <tr>
                <th>Bank Name</th>
                <th>Description</th>
                <th class="text-center">Questions</th>
                <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($banks) > 0): foreach ($banks as $b): ?>
                <tr>
                    <td><div class="fw-bold"><?= htmlspecialchars($b['bank_name']) ?></div></td>
                    <td><div class="small muted"><?= htmlspecialchars($b['description'] ?: '—') ?></div></td>
                    <td class="text-center"><span class="badge bg-light text-dark border"><?= $b['question_count'] ?></span></td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="index.php?view=questions&bank_id=<?= $b['bank_id'] ?>" class="btn btn-sm btn-outline-primary" title="View Questions">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" onclick='openEditBankModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)' title="Edit Bank">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteBank(<?= $b['bank_id'] ?>, '<?= htmlspecialchars($b['bank_name'], ENT_QUOTES) ?>')" title="Delete Bank">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="4" class="text-center muted py-4">No banks found.</td></tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>

  </div>
</div>

<!-- BANK MODAL (Add/Edit) -->
<div class="modal fade" id="modalBank" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST">
      <div class="modal-header">
        <h5 class="modal-title" id="bankModalTitle">Add Question Bank</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="bank_id" id="bank_id">
        <div class="mb-3">
            <label class="form-label fw-bold">Bank Name</label>
            <input class="form-control" name="bank_name" id="bank_name" placeholder="e.g., Mathematics" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Description</label>
            <textarea class="form-control" name="description" id="bank_desc" rows="2" placeholder="Brief description..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit" name="save_bank" id="btnSaveBank">Save Bank</button>
      </div>
    </form>
  </div>
</div>

<!-- QUESTION MODAL (Add/Edit) -->
<div class="modal fade" id="modalQuestion" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="POST">
      <div class="modal-header">
        <h5 class="modal-title" id="questionModalTitle">Add Question</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="question_id" id="q_id">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-bold">Question Bank <span class="text-danger">*</span></label>
                <select name="bank_id" id="q_bank" class="form-select" required>
                    <option value="">-- Select Bank --</option>
                    <?php foreach ($banks as $b): ?>
                        <option value="<?= $b['bank_id'] ?>"><?= htmlspecialchars($b['bank_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold">Question Text <span class="text-danger">*</span></label>
                <textarea name="question_text" id="q_text" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-6">
                <div class="input-group"><span class="input-group-text bg-primary text-white">A</span><input type="text" name="option_a" id="q_a" class="form-control" required></div>
            </div>
            <div class="col-md-6">
                <div class="input-group"><span class="input-group-text bg-primary text-white">B</span><input type="text" name="option_b" id="q_b" class="form-control" required></div>
            </div>
            <div class="col-md-6">
                <div class="input-group"><span class="input-group-text bg-primary text-white">C</span><input type="text" name="option_c" id="q_c" class="form-control" required></div>
            </div>
            <div class="col-md-6">
                <div class="input-group"><span class="input-group-text bg-primary text-white">D</span><input type="text" name="option_d" id="q_d" class="form-control" required></div>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold">Correct Answer</label>
                <div class="d-flex gap-3 p-2 bg-light border rounded">
                    <?php foreach(['A','B','C','D'] as $o): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="correct_answer" id="ans_<?= $o ?>" value="<?= $o ?>" required>
                        <label class="form-check-label" for="ans_<?= $o ?>">Option <?= $o ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit" name="save_question" id="btnSaveQuestion">Save Question</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bankModal = new bootstrap.Modal(document.getElementById('modalBank'));
    const qModal = new bootstrap.Modal(document.getElementById('modalQuestion'));

    window.filterByBank = function(id) {
        const url = new URL(window.location);
        if(id) url.searchParams.set('bank_id', id); else url.searchParams.delete('bank_id');
        window.location.href = url.toString();
    }

    window.openAddBankModal = function() {
        document.getElementById('bankModalTitle').textContent = "Add Question Bank";
        document.getElementById('bank_id').value = "";
        document.getElementById('bank_name').value = "";
        document.getElementById('bank_desc').value = "";
        bankModal.show();
    }

    window.openEditBankModal = function(bank) {
        document.getElementById('bankModalTitle').textContent = "Edit Question Bank";
        document.getElementById('bank_id').value = bank.bank_id;
        document.getElementById('bank_name').value = bank.bank_name;
        document.getElementById('bank_desc').value = bank.description;
        bankModal.show();
    }

    window.confirmDeleteBank = function(id, name) {
        Swal.fire({
            title: 'Delete Bank?',
            text: `Delete "${name}" and all its questions?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete'
        }).then((r) => { if(r.isConfirmed) window.location.href = `index.php?view=questions&delete_bank=${id}`; });
    }

    window.openAddQuestionModal = function() {
        document.getElementById('questionModalTitle').textContent = "Add Question";
        document.getElementById('q_id').value = "";
        document.getElementById('q_bank').value = "<?= $filter_bank ?: '' ?>";
        document.getElementById('q_text').value = "";
        document.getElementById('q_a').value = ""; document.getElementById('q_b').value = "";
        document.getElementById('q_c').value = ""; document.getElementById('q_d').value = "";
        document.querySelectorAll('input[name="correct_answer"]').forEach(i => i.checked = false);
        qModal.show();
    }

    window.openEditQuestionModal = function(q) {
        document.getElementById('questionModalTitle').textContent = "Edit Question";
        document.getElementById('q_id').value = q.question_id;
        document.getElementById('q_bank').value = q.bank_id;
        document.getElementById('q_text').value = q.question_text;
        document.getElementById('q_a').value = q.option_a;
        document.getElementById('q_b').value = q.option_b;
        document.getElementById('q_c').value = q.option_c;
        document.getElementById('q_d').value = q.option_d;
        const radio = document.getElementById('ans_' + q.correct_answer);
        if(radio) radio.checked = true;
        qModal.show();
    }

    window.confirmDeleteQuestion = function(id) {
        Swal.fire({
            title: 'Delete Question?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete'
        }).then((r) => { if(r.isConfirmed) window.location.href = `index.php?view=questions&delete_q=${id}`; });
    }

    // --- Form Validation ---
    const btnSaveBank = document.getElementById('btnSaveBank');
    const btnSaveQuestion = document.getElementById('btnSaveQuestion');

    function validateBankForm() {
        const name = document.getElementById('bank_name').value.trim();
        if(btnSaveBank) btnSaveBank.disabled = !name;
    }

    function validateQuestionForm() {
        const bank = document.getElementById('q_bank').value;
        const text = document.getElementById('q_text').value.trim();
        const a = document.getElementById('q_a').value.trim();
        const b = document.getElementById('q_b').value.trim();
        const c = document.getElementById('q_c').value.trim();
        const d = document.getElementById('q_d').value.trim();
        const correct = document.querySelector('input[name="correct_answer"]:checked');
        
        const isValid = bank && text && a && b && c && d && correct;
        if(btnSaveQuestion) btnSaveQuestion.disabled = !isValid;
    }

    // Attach listeners
    document.getElementById('modalBank').addEventListener('input', validateBankForm);
    document.getElementById('modalQuestion').addEventListener('input', validateQuestionForm);
    document.querySelectorAll('input[name="correct_answer"]').forEach(radio => {
        radio.addEventListener('change', validateQuestionForm);
    });

    // Run initial validation when modals open
    document.getElementById('modalBank').addEventListener('shown.bs.modal', validateBankForm);
    document.getElementById('modalQuestion').addEventListener('shown.bs.modal', validateQuestionForm);
});
</script>

