<?php
// Handle Bulk Insert
$msg = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "bulk_add") {
    $prefix = trim($_POST["prefix"] ?? "STU");
    if(empty($prefix)) $prefix = "STU";
    
    $studentsData = $_POST["students"] ?? [];
    $inserted = 0;
    $failed = 0;
    
    if (is_array($studentsData)) {
        $stmt = $conn->prepare("INSERT INTO students (full_name, email, gender, student_id) VALUES (?, ?, ?, ?)");
        
        foreach ($studentsData as $row) {
            $name = trim($row['name'] ?? '');
            $email = trim($row['email'] ?? '');
            $gender = $row['gender'] ?? null; // 'M' or 'F'
            
            if (empty($name) || empty($email)) continue;
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed++; continue;
            }
            
            // Generate Unique ID
            $sid = "";
            $added = false;
            for ($i=0; $i<5; $i++) {
                $sid = $prefix . str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                try {
                    $stmt->bind_param("ssss", $name, $email, $gender, $sid);
                    if ($stmt->execute()) {
                        $inserted++;
                        $added = true;
                        break;
                    }
                } catch (Exception $e) {
                    continue; // Retry
                }
            }
            if (!$added) $failed++;
        }
    }
    
    if ($inserted > 0) $msg = "Successfully added $inserted students.";
    if ($failed > 0) $err = "Failed to add $failed rows (duplicates or errors).";
}

// Handle Edit
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "edit_student") {
    $id = (int)$_POST["id"];
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $gender = $_POST["gender"] ?? null;
    $sid = trim($_POST["student_id"]);

    if ($id && $name && $email) {
        $stmt = $conn->prepare("UPDATE students SET full_name=?, email=?, gender=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $gender, $id);
        if ($stmt->execute()) {
            $msg = "Student updated successfully.";
        } else {
            $err = "Failed to update student (email or ID might be taken).";
        }
    } else {
        $err = "All fields are required.";
    }
}

// Handle Delete
if (isset($_GET["del"])) {
    $delId = (int)$_GET["del"];
    $conn->query("DELETE FROM students WHERE id=$delId");
    echo "<script>location.href='index.php?view=students';</script>"; 
    exit;
}

// Fetch Students
$res = $conn->query("SELECT * FROM students ORDER BY id DESC");
$students = [];
if ($res) while ($r = $res->fetch_assoc()) $students[] = $r;
?>

<div class="row">
    <div class="col-12">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="fw-semibold">Student Directory</div>
                    <div class="muted small"><?= count($students) ?> students registered</div>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalBulkStudent">
                    <i class="bi bi-plus-lg me-1"></i> Add Students
                </button>
            </div>

            <?php if($msg): ?><div class="alert alert-success small"><?= $msg ?></div><?php endif; ?>
            <?php if($err): ?><div class="alert alert-warning small"><?= $err ?></div><?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Gender</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="5" class="text-center muted py-4">No students found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td class="mono small"><?= htmlspecialchars($s['student_id']) ?></td>
                                    <td><?= htmlspecialchars($s['full_name']) ?></td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['gender'] ?? '-') ?></span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($s)) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="index.php?view=students&del=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this student?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Import Modal -->
<div class="modal fade" id="modalBulkStudent" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl">
    <form class="modal-content" method="post">
      <input type="hidden" name="action" value="bulk_add">
      <div class="modal-header">
        <h5 class="modal-title">Batch Add Students</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        
        <div class="row mb-3 align-items-center">
            <div class="col-md-5">
                <label class="form-label fw-bold small">ID Prefix</label>
                <div class="input-group">
                    <input type="text" name="prefix" id="idPrefixInput" class="form-control" placeholder="e.g. STU" value="STU" oninput="updateIdPreview()" required>
                    <span class="input-group-text bg-light text-muted small" id="idPreview">Ex: STU123456</span>
                </div>
            </div>
            <div class="col-md-7 text-end pt-4">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addStudentRow()">
                    <i class="bi bi-plus-circle me-1"></i> Add Row
                </button>
            </div>
        </div>

        <div class="table-responsive border rounded" style="max-height: 50vh; overflow-y: auto;">
            <table class="table table-borderless mb-0 align-middle">
                <thead class="table-light sticky-top" style="z-index: 1;">
                    <tr>
                        <th style="width: 35%">Full Name</th>
                        <th style="width: 35%">Email</th>
                        <th style="width: 20%">Gender</th>
                        <th style="width: 10%"></th>
                    </tr>
                </thead>
                <tbody id="bulkRows">
                    <!-- Rows injected via JS -->
                </tbody>
            </table>
        </div>

      </div>
      <div class="modal-footer">
        <div class="me-auto text-muted small">
            <i class="bi bi-info-circle me-1"></i> Checkboxes are mutually exclusive (M/F).
        </div>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Save Students</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="modalEditStudent" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <input type="hidden" name="action" value="edit_student">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-header">
        <h5 class="modal-title">Edit Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" id="edit_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="edit_email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Student ID</label>
            <input type="text" name="student_id" id="edit_sid" class="form-control bg-light" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label d-block">Gender</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="gender" id="edit_gender_m" value="M">
                <label class="form-check-label" for="edit_gender_m">Male</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="gender" id="edit_gender_f" value="F">
                <label class="form-check-label" for="edit_gender_f">Female</label>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Update Student</button>
      </div>
    </form>
  </div>
</div>

<script>
function updateIdPreview() {
    const p = document.getElementById('idPrefixInput').value || '...';
    document.getElementById('idPreview').textContent = 'Ex: ' + p + '123456';
}

function openEditModal(student) {
    document.getElementById('edit_id').value = student.id;
    document.getElementById('edit_name').value = student.full_name;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_sid').value = student.student_id;
    
    if (student.gender === 'M') document.getElementById('edit_gender_m').checked = true;
    else if (student.gender === 'F') document.getElementById('edit_gender_f').checked = true;
    else {
        document.getElementById('edit_gender_m').checked = false;
        document.getElementById('edit_gender_f').checked = false;
    }
    
    new bootstrap.Modal(document.getElementById('modalEditStudent')).show();
}

function addStudentRow() {
    const tbody = document.getElementById("bulkRows");
    const idx = tbody.children.length;
    const tr = document.createElement("tr");
    tr.innerHTML = `
        <td><input name="students[${idx}][name]" class="form-control" placeholder="Name" required></td>
        <td><input name="students[${idx}][email]" type="email" class="form-control" placeholder="Email" required></td>
        <td>
            <div class="d-flex gap-3 align-items-center pt-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="students[${idx}][gender]" value="M" id="g_m_${idx}" onclick="checkGender(this, 'g_f_${idx}')">
                    <label class="form-check-label" for="g_m_${idx}">Male</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="students[${idx}][gender]" value="F" id="g_f_${idx}" onclick="checkGender(this, 'g_m_${idx}')">
                    <label class="form-check-label" for="g_f_${idx}">Female</label>
                </div>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-light text-danger" onclick="this.closest('tr').remove()"><i class="bi bi-x-lg"></i></button>
        </td>
    `;
    tbody.appendChild(tr);
}

function checkGender(current, otherId) {
    if (current.checked) {
        document.getElementById(otherId).checked = false;
    }
}

// Init with 3 rows
window.addEventListener('DOMContentLoaded', () => {
    // Check if empty, add initial rows
    if(document.getElementById("bulkRows").children.length === 0) {
        addStudentRow();
        addStudentRow();
        addStudentRow();
    }
});
</script>


