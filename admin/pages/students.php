<?php
// Handle Email Check (AJAX)
if (isset($_POST["action"]) && $_POST["action"] === "check_email") {
    // Determine if we need to include DB (direct access case)
    if (!isset($conn)) {
        require_once __DIR__ . "/../includes/auth.php";
        start_secure_session();
        require_admin();
        require_once __DIR__ . "/../../connection/db.php";
    }

    $email = trim($_POST["email"] ?? "");
    if (empty($email)) {
        echo "invalid"; exit;
    }
    
    $stmt = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    echo $stmt->num_rows > 0 ? "exists" : "ok";
    exit;
}

// Handle Reset Password
if (isset($_POST["action"]) && $_POST["action"] === "reset_pass") {
    require_once __DIR__ . "/../includes/auth.php";
    start_secure_session();
    require_admin();
    
    require_once __DIR__ . "/../../connection/db.php";

    $uid = (int)$_POST["id"];
    $plainPass = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
    $hashedPass = password_hash($plainPass, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE students SET password=? WHERE id=?");
    $stmt->bind_param("si", $hashedPass, $uid);
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success', 'new_pass'=>$plainPass]);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Database error']);
    }
    exit;
}

// Handle Bulk Insert
$msg = "";
$err = "";
if(isset($_GET['status'])) {
    if($_GET['status'] == 'deleted') $msg = "Student removed successfully.";
    if($_GET['status'] == 'reset') $msg = "Password reset & sent to email.";
}

// Fetch EmailJS Settings
$settings_res = $conn->query("SELECT * FROM settings");
$email_settings = [];
if($settings_res) {
    while ($row = $settings_res->fetch_assoc()) {
        $email_settings[$row['setting_key']] = $row['setting_value'];
    }
}
$ejs_pub_reg = $email_settings['reg_public_key'] ?? '';
$ejs_srv_reg = $email_settings['reg_service_id'] ?? '';
$ejs_tpl_reg = $email_settings['reg_template_id'] ?? '';

$ejs_pub_rst = $email_settings['reset_public_key'] ?? '';
$ejs_srv_rst = $email_settings['reset_service_id'] ?? '';
$ejs_tpl_rst = $email_settings['reset_template_id'] ?? '';


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "bulk_add") {
    $prefix = trim($_POST["prefix"] ?? "STU");
    if(empty($prefix)) $prefix = "STU";
    
    $studentsData = $_POST["students"] ?? [];
    $inserted = 0;
    $failed = 0;

    $sendCreds = isset($_POST["send_creds"]);
    $newStudents = []; // For EmailJS

    if (is_array($studentsData)) {
        $stmt = $conn->prepare("INSERT INTO students (full_name, email, gender, student_id, password) VALUES (?, ?, ?, ?, ?)");
        
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
            $plainPass = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
            $hashedPass = password_hash($plainPass, PASSWORD_DEFAULT);
            
            $added = false;
            for ($i=0; $i<5; $i++) {
                $sid = $prefix . str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                try {
                    $stmt->bind_param("sssss", $name, $email, $gender, $sid, $hashedPass);
                    if ($stmt->execute()) {
                        $inserted++;
                        $added = true;
                        if ($sendCreds) {
                            $newStudents[] = [
                                'name' => $name, 
                                'email' => $email, 
                                'password' => $plainPass,
                                'student_id' => $sid
                            ];
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
    // Check if empty, add initial rows
    if(document.getElementById("bulkRows").children.length === 0) {
        addStudentRow();
        addStudentRow();
        addStudentRow();
    }
});
// Clean up stray script tags above
</script>

<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
<script>
// No global init, using per-call authorization below


// Restore Registration Email Logic
function sendWelcomeEmails(students) {
    if (!students || students.length === 0) return;
    
    Swal.fire({
        title: 'Sending Welcome Emails...',
        text: `Processing ${students.length} students. Please wait...`,
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    let sent = 0;
    let failed = 0;
    
    const sendNext = (index) => {
        if (index >= students.length) {
            Swal.fire({
                icon: (failed === 0) ? 'success' : 'info',
                title: 'Registration Complete',
                text: `Successfully added students. Emails sent: ${sent}${failed > 0 ? ', Failed: ' + failed : ''}`
            }).then(() => {
                window.location.href = 'index.php?view=students';
            });
            return;
        }

        const s = students[index];
        emailjs.send("<?= $ejs_srv_reg ?>", "<?= $ejs_tpl_reg ?>", {
            to_name: s.name,
            to_email: s.email,
            password: s.password,
            student_id: s.student_id
        }, "<?= $ejs_pub_reg ?>").then(() => {
            sent++;
            sendNext(index + 1);
        }).catch(err => {
            console.error("Welcome email failed for", s.email, err);
            failed++;
            sendNext(index + 1);
        });
    };

    sendNext(0);
}

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
                    // Send Email - Using dynamic reset-specific settings including Public Key
                    const ejsParams = {
                        to_name: name,
                        to_email: email,
                        password: res.new_pass
                    };
                    console.log("Attempting EmailJS Send:", {
                        service: "<?= $ejs_srv_rst ?>",
                        template: "<?= $ejs_tpl_rst ?>",
                        public_key: "<?= $ejs_pub_rst ?>",
                        params: ejsParams
                    });
                    
                    emailjs.send("<?= $ejs_srv_rst ?>", "<?= $ejs_tpl_rst ?>", ejsParams, "<?= $ejs_pub_rst ?>").then(() => {
                        // Success Redirect
                        window.location.href = 'index.php?view=students&status=reset';
                    }).catch(err => {
                        console.error('EmailJS Failed:', err);
                        Swal.fire({
                            icon: 'warning',
                            title: 'Email Failed',
                            text: 'The password was reset in the database, but the notification email failed to send.',
                            footer: 'EmailJS Error: ' + (err.text || JSON.stringify(err))
                        });
                        // IMPORTANT: No redirect here to avoid false success message
                    });
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

// Trigger Registration Emails if PHP processed a bulk add
<?php if (!empty($newStudents)): ?>
window.addEventListener('DOMContentLoaded', () => {
    sendWelcomeEmails(<?= json_encode($newStudents) ?>);
});
<?php endif; ?>

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
