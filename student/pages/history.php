<?php
// user/pages/history.php
// Completely rewritten from scratch
declare(strict_types=1);

$student_id = (int)$_SESSION['student_id'];

// 2. Fetch History using Explicit Assignments
// We fetch all exams assigned to this student that are either:
// a) Attempted (entry in exam_submissions)
// b) Expired (end_time passed) -> these are "Missed" if not attempted

$sql = "
    SELECT 
        e.exam_id,
        e.title,
        e.end_time,
        e.passing_marks,
        qb.bank_name,
        es.score,
        COALESCE(es.status, IF(e.end_time < NOW(), 'missed', 'upcoming')) as final_status,
        es.end_time as submission_time
    FROM exam_assignments ea
    JOIN exams e ON ea.exam_id = e.exam_id
    LEFT JOIN question_banks qb ON e.bank_id = qb.bank_id
    LEFT JOIN exam_submissions es ON (e.exam_id = es.exam_id AND es.student_id = ea.student_id)
    WHERE ea.student_id = $student_id
    AND (
        es.submission_id IS NOT NULL  -- Show if attempted (ongoing/submitted)
        OR 
        e.end_time < NOW()            -- Show if expired (missed)
    )
    ORDER BY e.end_time DESC
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
