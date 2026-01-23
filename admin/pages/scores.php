<?php
// admin/pages/scores.php
// View and export student results

$exam_filter = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

$q_exams = "SELECT exam_id, title FROM exams ORDER BY created_at DESC";
$res_exams = mysqli_query($conn, $q_exams);

$q_scores = "SELECT s.*, e.title as exam_title, e.passing_marks, st.full_name, st.student_id 
             FROM exam_submissions s 
             JOIN exams e ON s.exam_id = e.exam_id 
             JOIN students st ON s.student_id = st.id 
             WHERE s.status = 'submitted'";
if($exam_filter) $q_scores .= " AND s.exam_id = $exam_filter";
$q_scores .= " ORDER BY s.end_time DESC";
$res_scores = mysqli_query($conn, $q_scores);
?>

<div class="card p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h5 class="mb-0 fw-bold">Examination Scores</h5>
            <p class="small text-muted mb-0">Detailed results and performance analytics.</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" style="min-width:200px" onchange="location.href='index.php?view=scores&exam_id='+this.value">
                <option value="0">All Exams</option>
                <?php while($e = mysqli_fetch_assoc($res_exams)): ?>
                <option value="<?= $e['exam_id'] ?>" <?= ($exam_filter == $e['exam_id']) ? 'selected' : '' ?>><?= htmlspecialchars($e['title']) ?></option>
                <?php endwhile; ?>
            </select>
            <button class="btn btn-outline-primary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light small">
                <tr>
                    <th>Student Info</th>
                    <th>Exam</th>
                    <th>Score / Percentage</th>
                    <th>Submission Date</th>
                    <th>Result</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($res_scores) > 0): while($s = mysqli_fetch_assoc($res_scores)): ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?= htmlspecialchars($s['full_name']) ?></div>
                        <div class="small muted"><?= $s['student_id'] ?></div>
                    </td>
                    <td><div class="small fw-semibold"><?= htmlspecialchars($s['exam_title']) ?></div></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="flex-grow-1" style="max-width:100px">
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?= ($s['score'] >= $s['passing_marks']) ? 'bg-success' : 'bg-danger' ?>" style="width: <?= $s['score'] ?>%"></div>
                                </div>
                            </div>
                            <span class="fw-bold"><?= $s['score'] ?>%</span>
                        </div>
                    </td>
                    <td class="small"><?= date('M d, Y H:i', strtotime($s['end_time'])) ?></td>
                    <td>
                        <?php if($s['score'] >= $s['passing_marks']): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">PASSED</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">FAILED</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick="viewDetails(<?= $s['submission_id'] ?>)"><i class="bi bi-eye"></i></button>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="6" class="text-center py-5 muted">No scores found for the selected criteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.bg-success-subtle { background-color: rgba(25, 135, 84, 0.1) !important; }
.bg-danger-subtle { background-color: rgba(220, 53, 69, 0.1) !important; }
</style>

<script>
function viewDetails(sid) {
    Swal.fire({
        title: 'Score Details',
        text: 'Detailed answer-by-answer breakdown coming soon in next update.',
        icon: 'info'
    });
}
</script>
