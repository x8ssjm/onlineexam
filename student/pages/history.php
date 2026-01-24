<?php
// user/pages/history.php
// Completely rewritten from scratch
declare(strict_types=1);

$student_id = (int)$_SESSION['student_id'];

// 1. Get Student's Group ID for checking missed exams
$group_res = mysqli_query($conn, "SELECT group_id FROM students WHERE id = $student_id");
$student_group_row = mysqli_fetch_assoc($group_res);
$my_group_id = $student_group_row['group_id'] ?? 0;
// Handle NULL case for SQL safety (0 usually safely means 'no group' or specific ID, but for validation we need to be careful with NULLs in DB)
$group_condition = $my_group_id ? "(e.group_id = $my_group_id OR e.group_id IS NULL OR e.group_id = 0)" : "(e.group_id IS NULL OR e.group_id = 0)";


// 2. Fetch History using UNION
// Part A: Exams the student actually attempted (Submission exists)
// Part B: Exams the student missed (Assigned + Expired + No Submission)

$sql = "
(
    SELECT 
        e.exam_id,
        e.title,
        e.end_time,
        e.passing_marks,
        qb.bank_name,
        es.score,
        es.status as final_status, -- 'submitted' or 'ongoing'
        es.end_time as submission_time
    FROM exam_submissions es
    JOIN exams e ON es.exam_id = e.exam_id
    LEFT JOIN question_banks qb ON e.bank_id = qb.bank_id
    WHERE es.student_id = $student_id
)
UNION
(
    SELECT 
        e.exam_id,
        e.title,
        e.end_time,
        e.passing_marks,
        qb.bank_name,
        NULL as score,
        'missed' as final_status,
        NULL as submission_time
    FROM exams e
    LEFT JOIN question_banks qb ON e.bank_id = qb.bank_id
    WHERE $group_condition
    AND e.end_time < NOW()
    AND e.exam_id NOT IN (SELECT exam_id FROM exam_submissions WHERE student_id = $student_id)
)
ORDER BY end_time DESC
";

$res = mysqli_query($conn, $sql);
if (!$res) {
    echo "<div class='alert alert-danger'>Error loading history: " . mysqli_error($conn) . "</div>";
    $history = [];
} else {
    $history = mysqli_fetch_all($res, MYSQLI_ASSOC);
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold">Exam History</h4>
        <div class="muted small text-primary">A complete record of your exams.</div>
    </div>
</div>

<div class="row g-4">
    <?php if (count($history) > 0): ?>
        <?php foreach ($history as $exam): ?>
            <?php 
                // Color Logic
                $badgeColor = 'bg-secondary';
                $statusLabel = 'Unknown';
                
                if ($exam['final_status'] === 'submitted') {
                    $statusLabel = 'Submitted';
                    $badgeColor = 'bg-success';
                } elseif ($exam['final_status'] === 'ongoing') {
                    $statusLabel = 'Incomplete'; // Ongoing but passed end_time effectively means incomplete/missed submission
                    $badgeColor = 'bg-warning text-dark';
                } elseif ($exam['final_status'] === 'missed') {
                    $statusLabel = 'Missed';
                    $badgeColor = 'bg-danger';
                }

                // Score Display
                $scoreTxt = '-';
                $passStatus = '';
                if ($exam['score'] !== null) {
                    $scoreTxt = $exam['score'] . '%';
                    if ($exam['score'] >= $exam['passing_marks']) {
                        $passStatus = '<span class="text-success fw-bold small"><i class="bi bi-check-circle"></i> Passed</span>';
                    } else {
                        $passStatus = '<span class="text-danger fw-bold small"><i class="bi bi-x-circle"></i> Failed</span>';
                    }
                }
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 border border-light p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge <?= $badgeColor ?> rounded-pill px-3"><?= $statusLabel ?></span>
                        <div class="small text-muted">
                            <?= date('M d, Y', strtotime($exam['end_time'])) ?>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($exam['title'] ?? 'Untitled') ?></h5>
                    <div class="small text-muted mb-3"><?= htmlspecialchars($exam['bank_name'] ?? 'General') ?></div>
                    
                    <div class="bg-light p-3 rounded d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted text-uppercase fw-bold" style="font-size:0.7rem;">Score</div>
                            <div class="fs-4 fw-bold"><?= $scoreTxt ?></div>
                        </div>
                        <div class="text-end">
                            <div><?= $passStatus ?></div>
                            <?php if($exam['submission_time']): ?>
                                <div class="small text-muted" style="font-size:0.75rem;">
                                    Sub: <?= date('H:i', strtotime($exam['submission_time'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card p-5 text-center border-dashed">
                <div class="mb-3 text-muted opacity-25"><i class="bi bi-folder-x" style="font-size: 3rem;"></i></div>
                <h5 class="fw-bold">No History Found</h5>
                <p class="text-muted small">You haven't participated in or missed any exams yet.</p>
                <!-- Debug Info (Hidden in Production, visible now for verify) -->
                <!-- Group ID: <?= $my_group_id ?> -->
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .border-dashed { border: 2px dashed #e5e7eb; background: transparent; box-shadow: none; }
</style>
