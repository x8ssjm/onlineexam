<?php
// admin/pages/groups.php

// --- PHP HANDLERS ---

// Save Group (Add/Edit)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_group'])) {
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $name = mysqli_real_escape_string($conn, $_POST['group_name']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);

    if ($group_id > 0) {
        $query = "UPDATE `groups` SET group_name='$name', description='$desc' WHERE group_id=$group_id";
    } else {
        $query = "INSERT INTO `groups` (group_name, description) VALUES ('$name', '$desc')";
    }

    if (mysqli_query($conn, $query)) {
        $gid = ($group_id > 0) ? $group_id : mysqli_insert_id($conn);
        $student_ids = $_POST['student_ids'] ?? [];

        // 1. Remove this group from everyone who was in it
        mysqli_query($conn, "UPDATE students SET group_id = NULL WHERE group_id = $gid");

        // 2. Assign selected students to this group
        if (!empty($student_ids)) {
            $ids = implode(',', array_map('intval', $student_ids));
            mysqli_query($conn, "UPDATE students SET group_id = $gid WHERE id IN ($ids)");
        }

        header("Location: index.php?view=groups&success=Group saved");
        exit;
    } else {
        $error = mysqli_error($conn);
    }
}

// Delete Group
if (isset($_GET['delete_group'])) {
    $gid = (int)$_GET['delete_group'];
    if (mysqli_query($conn, "DELETE FROM `groups` WHERE group_id=$gid")) {
        header("Location: index.php?view=groups&success=Group deleted");
        exit;
    }
}

// --- DATA FETCHING ---
$query = "SELECT g.*, (SELECT COUNT(*) FROM students WHERE group_id = g.group_id) as student_count FROM `groups` g ORDER BY group_name";
$res = mysqli_query($conn, $query);
$groups = [];
while($row = mysqli_fetch_assoc($res)) $groups[] = $row;
?>

<?php if (isset($_GET['success'])): ?>
<script>
    Swal.fire({ icon: 'success', title: 'Success', text: '<?= htmlspecialchars($_GET['success']) ?>', timer: 2000, showConfirmButton: false });
</script>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-0 fw-bold">Student Groups</h5>
                    <p class="small text-muted mb-0">Manage groups for targeted examinations.</p>
                </div>
                <button class="btn btn-primary" onclick="openAddGroupModal()">
                    <i class="bi bi-plus-lg me-1"></i> Create Group
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead class="bg-light">
                        <tr>
                            <th>Group Name</th>
                            <th>Description</th>
                            <th>Student Count</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($groups)): foreach ($groups as $g): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($g['group_name']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($g['description']) ?: 'No description' ?></td>
                            <td><span class="badge bg-info-subtle text-info border border-info-subtle px-3"><?= $g['student_count'] ?> Students</span></td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <button class="btn btn-sm btn-outline-secondary" onclick='openEditGroupModal(<?= htmlspecialchars(json_encode($g), ENT_QUOTES) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteGroup(<?= $g['group_id'] ?>, '<?= htmlspecialchars($g['group_name'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="4" class="py-5 text-center muted">No groups found. Create one to begin.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- GROUP MODAL -->
<div class="modal fade" id="modalGroup" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="groupModalTitle">Group Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="group_id" id="group_id">
                <div class="mb-3">
                    <label class="form-label fw-bold">Group Name</label>
                    <input type="text" name="group_name" id="group_name" class="form-control" required placeholder="e.g. Science Section A">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                </div>
                <hr>
                <div class="mb-2 d-flex justify-content-between align-items-center">
                    <label class="form-label fw-bold mb-0">Assign Students</label>
                    <div class="small">
                        <input type="checkbox" id="selectAllInGroup" class="form-check-input">
                        <label for="selectAllInGroup" class="form-check-label small muted">Select All</label>
                    </div>
                </div>
                <div id="groupStudentList" class="list-group border rounded shadow-sm" style="max-height: 250px; overflow-y: auto;">
                    <!-- Injected via JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit" name="save_group">Save Group</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const groupModal = new bootstrap.Modal(document.getElementById('modalGroup'));

    window.openAddGroupModal = function() {
        document.getElementById('groupModalTitle').textContent = "Create New Group";
        document.getElementById('group_id').value = "";
        document.getElementById('group_name').value = "";
        document.getElementById('description').value = "";
        fetchAndPopulateStudents(0);
        groupModal.show();
    }

    window.openEditGroupModal = function(g) {
        document.getElementById('groupModalTitle').textContent = "Edit Group";
        document.getElementById('group_id').value = g.group_id;
        document.getElementById('group_name').value = g.group_name;
        document.getElementById('description').value = g.description;
        fetchAndPopulateStudents(g.group_id);
        groupModal.show();
    }

    function fetchAndPopulateStudents(groupId) {
        const list = document.getElementById('groupStudentList');
        list.innerHTML = '<div class="p-3 text-center small muted">Loading students...</div>';
        document.getElementById('selectAllInGroup').checked = false;

        fetch(`ajax_groups.php?action=get_group_students&group_id=${groupId}`)
        .then(r => r.json())
        .then(students => {
            if(students.length === 0) {
                list.innerHTML = '<div class="p-3 text-center small muted">No students found in system.</div>';
                return;
            }
            list.innerHTML = "";
            students.forEach(s => {
                const isChecked = (parseInt(s.group_id) === parseInt(groupId));
                const otherGroup = (s.group_id && parseInt(s.group_id) !== parseInt(groupId)) ? `<span class="badge bg-light text-muted border ms-2">Already in: ${s.group_name}</span>` : '';
                
                const label = document.createElement('label');
                label.className = 'list-group-item d-flex align-items-center gap-2 py-2';
                label.innerHTML = `
                    <input type="checkbox" name="student_ids[]" value="${s.id}" class="form-check-input student-group-cb" ${isChecked ? 'checked' : ''}>
                    <div class="flex-grow-1">
                        <div class="small fw-bold">${s.name}</div>
                        <div class="muted" style="font-size: 11px;">${s.sid}${otherGroup}</div>
                    </div>
                `;
                list.appendChild(label);
            });
        });
    }

    document.getElementById('selectAllInGroup').addEventListener('change', function() {
        document.querySelectorAll('.student-group-cb').forEach(cb => cb.checked = this.checked);
    });

    window.confirmDeleteGroup = function(id, name) {
        Swal.fire({
            title: 'Delete Group?',
            text: `Are you sure you want to delete this group?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete'
        }).then((r) => { if(r.isConfirmed) window.location.href = `index.php?view=groups&delete_group=${id}`; });
    }
});
</script>
