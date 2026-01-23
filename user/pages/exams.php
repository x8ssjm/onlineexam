<?php
// user/pages/exams.php
$student_id = $_SESSION['student_id'];

// Fetch ongoing exams for this student's group (or all groups)
$q_exams = "SELECT e.*, qb.bank_name 
            FROM exams e 
            JOIN students s ON (e.group_id = s.group_id OR e.group_id IS NULL)
            JOIN question_banks qb ON e.bank_id = qb.bank_id
            WHERE s.id = $student_id 
            AND NOW() BETWEEN e.start_time AND e.end_time
            AND e.exam_id NOT IN (SELECT exam_id FROM exam_submissions WHERE student_id = $student_id AND status = 'submitted')
            ORDER BY e.start_time ASC";
$res_exams = mysqli_query($conn, $q_exams);
$res_exams = mysqli_query($conn, $q_exams);
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold">My Available Exams</h4>
        <div class="muted small text-primary">Ongoing exams you are assigned to. Once submitted, they will disappear from this list.</div>
    </div>
</div>

<div class="row g-4">
    <?php if(mysqli_num_rows($res_exams) > 0): while($e = mysqli_fetch_assoc($res_exams)): ?>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card p-4 h-100 exam-card border border-light" onclick="window.location.href='take_exam.php?id=<?= $e['exam_id'] ?>'">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3">Ongoing</span>
                <div class="small muted"><i class="bi bi-clock me-1"></i> <?= $e['duration'] ?> mins</div>
            </div>
            <h5 class="fw-bold mb-2"><?= htmlspecialchars($e['title']) ?></h5>
            <p class="small text-muted mb-4"><?= htmlspecialchars($e['description']) ?: 'No description provided.' ?></p>
            <hr class="mt-auto">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small">
                    <div class="fw-bold">Subject:</div>
                    <div class="muted"><?= htmlspecialchars($e['bank_name']) ?></div>
                </div>
                <button class="btn btn-primary btn-sm px-4 shadow-sm">Start Now</button>
            </div>
        </div>
    </div>
    <?php endwhile; else: ?>
    <div class="col-12">
        <div class="card p-5 text-center border-dashed">
            <div class="mb-3 text-muted opacity-25"><i class="bi bi-journal-x" style="font-size: 4rem;"></i></div>
            <h5 class="fw-bold">No Exams available right now.</h5>
            <div class="muted small">Check back during your scheduled exam window.</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .border-dashed { border: 2px dashed #e5e7eb; background: transparent; box-shadow: none; }
    .bg-primary-subtle { background: rgba(13, 110, 253, 0.1) !important; }
</style>
