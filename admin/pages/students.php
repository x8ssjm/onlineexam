<?php
// admin/pages/students.php

// Determine if we need to include DB (direct access case)
if (!isset($conn)) {
    require_once __DIR__ . "/../includes/auth.php";
    start_secure_session();
    require_admin();
    require_once __DIR__ . "/../../connection/db.php";
}

// Include Mailer
require_once __DIR__ . "/../../includes/Mailer.php";
use App\Mailer;

// Handle Email Check (AJAX)
if (isset($_POST["action"]) && $_POST["action"] === "check_email") {
    // Suppress errors for AJAX
    error_reporting(0);
    @ini_set('display_errors', 0);
    
    $email = trim($_POST["email"] ?? "");
    if (empty($email)) {
        echo "invalid"; exit;
    }
    
    // Ensure DB connection if not already present
    if (!isset($conn)) {
        // ... handled above but just in case
    }
    
    $stmt = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    echo $stmt->num_rows > 0 ? "exists" : "ok";
    exit;
}

// Handle Reset Password (AJAX)
if (isset($_POST["action"]) && $_POST["action"] === "reset_pass") {
    // Suppress errors for AJAX response
    error_reporting(0);
    @ini_set('display_errors', 0);
    header('Content-Type: application/json');

    try {
        $uid = (int)$_POST["id"];
        
        // 1. Get Student Info
        $stmt = $conn->prepare("SELECT full_name, email FROM students WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($student = $res->fetch_assoc()) {
            $u = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $l = "abcdefghijklmnopqrstuvwxyz";
            $d = "0123456789";
            $plainPass = substr(str_shuffle($u),0,1) . substr(str_shuffle($l),0,1) . substr(str_shuffle($d),0,1) . substr(str_shuffle($u.$l.$d),0,5);
            $plainPass = str_shuffle($plainPass);
            $hashedPass = password_hash($plainPass, PASSWORD_DEFAULT);
            
            $upd = $conn->prepare("UPDATE students SET password=? WHERE id=?");
            $upd->bind_param("si", $hashedPass, $uid);
            
            if ($upd->execute()) {
                 // 2. Send Email via Mailer
                 $mailer = new Mailer($conn);
                 $subject = "Password Reset - Online Exam Portal";
                 $body = "
                    Hello {$student['full_name']},<br><br>
                    your password has been reset successfully<br><br>
                    your new password: <strong>{$plainPass}</strong><br><br>
                    Please login and change if needed.<br>
                    Regards,<br>
                    Online Exam Portal
                ";
                 $result = $mailer->send($student['email'], $student['full_name'], $subject, $body);
                 
                 if ($result['success']) {
                     echo json_encode(['status'=>'success']);
                 } else {
                     echo json_encode(['status'=>'success', 'warning'=>'Password reset but email failed: ' . $result['message']]);
                 }
            } else {
                echo json_encode(['status'=>'error', 'message'=>'Database error']);
            }
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Student not found']);
        }
    } catch (Throwable $e) {
        echo json_encode(['status'=>'error', 'message'=>'Server error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle Bulk Insert
$msg = "";
$err = "";
if(isset($_GET['status'])) {
    if($_GET['status'] == 'deleted') $msg = "Student removed successfully.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "bulk_add") {
    $prefix = trim($_POST["prefix"] ?? "STU");
    if(empty($prefix)) $prefix = "STU";
    
    $studentsData = $_POST["students"] ?? [];
    $inserted = 0;
    $failed = 0;
    $emailCount = 0;

    $sendCreds = isset($_POST["send_creds"]);
    $mailer = new Mailer($conn);

    if (is_array($studentsData)) {
        // Updated INSERT to include login_token and token_expiry
        $stmt = $conn->prepare("INSERT INTO students (full_name, email, gender, student_id, password, login_token, token_expiry) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($studentsData as $row) {
            $name = trim($row['name'] ?? '');
            $email = trim($row['email'] ?? '');
            $gender = $row['gender'] ?? null; // 'M' or 'F'
            
            if (empty($name) || empty($email)) continue;
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed++; continue;
            }
            
            // Generate Unique ID & Password
            $sid = "";
            $u = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $l = "abcdefghijklmnopqrstuvwxyz";
            $d = "0123456789";
            $plainPass = substr(str_shuffle($u),0,1) . substr(str_shuffle($l),0,1) . substr(str_shuffle($d),0,1) . substr(str_shuffle($u.$l.$d),0,5);
            $plainPass = str_shuffle($plainPass);
            $hashedPass = password_hash($plainPass, PASSWORD_DEFAULT);
            
            $added = false;
            for ($i=0; $i<5; $i++) {
                $sid = $prefix . str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                try {
                    $stmt->bind_param("sssssss", $name, $email, $gender, $sid, $hashedPass, $token, $expiry);
                    if ($stmt->execute()) {
                        $inserted++;
                        $added = true;
                        
                        if ($sendCreds) {
                             $subject = "Welcome to Online Exam Portal";
                             // Determine base URL dynamically
                             $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                             $host = $_SERVER['HTTP_HOST'];
                             $pathParts = explode('/', $_SERVER['SCRIPT_NAME']);
                             array_pop($pathParts); // index.php
                             array_pop($pathParts); // admin
                             $webRoot = implode('/', $pathParts);
                             $magicLink = $protocol . "://" . $host . $webRoot . "/student/login_token.php?token=" . $token;

                             $body = "
                                <h3>Welcome, {$name}!</h3>
                                <p>Your account has been created successfully.</p>
                                <p><strong>Student ID:</strong> {$sid}</p>
                                <p><strong>Password:</strong> {$plainPass}</p>
                                <br>
                                <div style='text-align: center; margin: 20px 0;'>
                                    <a href='{$magicLink}' style='background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Login to Dashboard</a>
                                </div>
                                <p style='font-size: 12px; color: #666;'>Or copy this link: <a href='{$magicLink}'>{$magicLink}</a></p>
                                <br>
                                <p>Regards,<br>Online Exam Portal</p>
                             ";
                             $res = $mailer->send($email, $name, $subject, $body);
                             if ($res['success']) $emailCount++;
                        }
                        break;
                    }
                } catch (Exception $e) {
                    continue; // Retry
                }
            }
            if (!$added) $failed++;
        }
    }
    
    if ($inserted > 0) {
        $msg = "Successfully added $inserted students.";
        if ($sendCreds) $msg .= " Sent $emailCount emails.";
    }
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
    echo "<script>location.href='index.php?view=students&status=deleted';</script>"; 
    exit;
}

// Fetch Students with group names
$res = $conn->query("SELECT s.*, g.group_name FROM students s LEFT JOIN `groups` g ON s.group_id = g.group_id ORDER BY s.id DESC");
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
                            <th>Group</th>
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
                                    <td>
                                        <?php if($s['group_name']): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2"><?= htmlspecialchars($s['group_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['gender'] ?? '-') ?></span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-warning me-1" 
                                            onclick="handleResetPassword(this)"
                                            data-id="<?= $s['id'] ?>"
                                            data-name="<?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?>"
                                            data-email="<?= htmlspecialchars($s['email'], ENT_QUOTES) ?>"
                                            title="Reset Password">
                                            <i class="bi bi-key"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)"
                                            title="Edit Student">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?= $s['id'] ?>, '<?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?>')"
                                            title="Delete Student">
                                            <i class="bi bi-trash"></i>
                                        </button>
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
                <button type="button" class="btn btn-outline-primary" onclick="addStudentRow()">
                    <i class="bi bi-plus-lg me-1"></i> Add Row
                </button>
            </div>
        </div>
        
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="chkSendCreds" name="send_creds" checked>
            <label class="form-check-label" for="chkSendCreds">Send login credentials to students via Email</label>
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
            <button type="button" class="btn btn-sm btn-light text-danger" onclick="this.closest('tr').remove(); validateBulkForm();"><i class="bi bi-x-lg"></i></button>
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
    if(document.getElementById("bulkRows").children.length === 0) {
        addStudentRow();
        addStudentRow();
        addStudentRow();
    }
});
</script>

<script>
// Improved Password Reset Logic
function handleResetPassword(btn) {
    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name');
    const email = btn.getAttribute('data-email');

    if (!email || email === "null") {
        Swal.fire('Error', 'This student has no email address associated.', 'error');
        return;
    }

    Swal.fire({
        title: 'Reset Password?',
        text: `Reset and send new credentials to ${name}?`,
        icon: 'warning',
        width: 350,
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reset and email'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Resetting...',
                text: 'Updating database and sending email...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData();
            formData.append('action', 'reset_pass');
            formData.append('id', id);
            
            fetch('pages/students.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if(res.status === 'success') {
                    if (res.warning) {
                        Swal.fire('Warning', res.warning, 'warning');
                    } else {
                        Swal.fire('Success', 'Password reset and email sent.', 'success');
                    }
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            })
            .catch(e => Swal.fire('Error', 'Request failed: ' + e, 'error'));
        }
    });
}

function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete Student?',
        text: `Remove ${name}?`,
        icon: 'warning',
        width: 320,
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?view=students&del=${id}`;
        }
    });
}

// --- Validation Logic ---
const btnSave = document.querySelector('#modalBulkStudent button[type="submit"]');

document.addEventListener('input', (e) => {
    if(e.target.closest('#bulkRows')) validateBulkForm();
});
document.addEventListener('change', (e) => {
    if(e.target.closest('#bulkRows')) validateBulkForm();
});

function validateBulkForm() {
    const rows = document.querySelectorAll('#bulkRows tr');
    let allValid = true;
    let hasRows = rows.length > 0;

    if (!hasRows) allValid = false;

    rows.forEach(tr => {
        const nameInput = tr.querySelector('input[placeholder="Name"]');
        const emailInput = tr.querySelector('input[placeholder="Email"]');
        if(!nameInput || !emailInput) return;

        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const genderM = tr.querySelector('input[value="M"]').checked;
        const genderF = tr.querySelector('input[value="F"]').checked;
        
        if (!name || !email || (!genderM && !genderF)) allValid = false;
        if (emailInput.classList.contains('is-invalid')) allValid = false;
    });
    
    if(btnSave) btnSave.disabled = !allValid;
}

// Debounce for email check
let emailTimeout;
document.addEventListener('input', (e) => {
    if(e.target.closest('#bulkRows') && e.target.type === 'email') {
        clearTimeout(emailTimeout);
        const input = e.target;
        input.classList.remove('is-invalid', 'is-valid');
        
        emailTimeout = setTimeout(() => {
            checkEmailDuplicate(input);
        }, 500);
    }
});

function checkEmailDuplicate(input) {
    const email = input.value.trim();
    if(!email) return;

    const allEmails = Array.from(document.querySelectorAll('#bulkRows input[type="email"]'))
        .filter(el => el !== input)
        .map(el => el.value.trim());
    
    if(allEmails.includes(email)) {
        markInvalid(input, "Duplicate in list");
        validateBulkForm();
        return;
    }

    const fd = new FormData();
    fd.append('action', 'check_email');
    fd.append('email', email);

    fetch('pages/students.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(res => {
        if(res === 'exists') {
            markInvalid(input, "Email already registered");
        } else if (res === 'ok') {
            input.classList.add('is-valid');
            input.classList.remove('is-invalid');
            const next = input.nextElementSibling;
            if(next && next.classList.contains('invalid-feedback')) next.remove();
        }
        validateBulkForm();
    });
}

function markInvalid(input, msg) {
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');
    let feed = input.nextElementSibling;
    if(!feed || !feed.classList.contains('invalid-feedback')) {
        feed = document.createElement('div');
        feed.className = 'invalid-feedback';
        input.parentNode.appendChild(feed);
    }
    feed.textContent = msg;
}

window.addEventListener('DOMContentLoaded', () => {
    validateBulkForm();
    const params = new URLSearchParams(window.location.search);
    if(params.has('status')) {
        const url = new URL(window.location);
        url.searchParams.delete('status');
        window.history.replaceState({}, '', url.toString());
    }
});
</script>
