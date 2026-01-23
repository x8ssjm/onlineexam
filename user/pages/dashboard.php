<?php
// user/pages/dashboard.php
$student_id = $_SESSION['student_id'];

// Get some stats
$stats = [
    'total_exams' => 0,
    'avg_score' => 0,
    'ongoing' => 0
];

if (isset($conn)) {
    // Ongoing exams count (based on student group + exams for everyone)
    $q_ongoing = "SELECT COUNT(*) FROM exams e 
                  JOIN students s ON (e.group_id = s.group_id OR e.group_id IS NULL)
                  WHERE s.id = $student_id 
                  AND NOW() BETWEEN e.start_time AND e.end_time";
    $res_ongoing = mysqli_query($conn, $q_ongoing);
    if($res_ongoing) $stats['ongoing'] = mysqli_fetch_row($res_ongoing)[0];

    // Completed exams (submissions)
    $q_completed = "SELECT COUNT(*), AVG(score) FROM exam_submissions 
                    WHERE student_id = $student_id AND status = 'submitted'";
    $res_comp = mysqli_query($conn, $q_completed);
    if($res_comp) {
        $row = mysqli_fetch_row($res_comp);
        $stats['total_exams'] = $row[0];
        $stats['avg_score'] = round((float)$row[1], 1);
    }
}
?>

<div class="row g-4 mb-5">
    <div class="col-12 col-md-4">
        <div class="card p-4 h-100 bg-gradient-primary">
            <div class="muted small text-white-50">Active Exams</div>
            <div class="fs-1 fw-bold"><?= $stats['ongoing'] ?></div>
            <div class="small mt-2"><i class="bi bi-clock-history me-1"></i> Available right now</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card p-4 h-100">
            <div class="muted small">Exams Completed</div>
            <div class="fs-1 fw-bold"><?= $stats['total_exams'] ?></div>
            <div class="small mt-2 text-success"><i class="bi bi-check2-all me-1"></i> Great job!</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card p-4 h-100">
            <div class="muted small">Average Score</div>
            <div class="fs-1 fw-bold"><?= $stats['avg_score'] ?><span class="fs-6 text-muted ms-1">%</span></div>
            <div class="small mt-2 text-warning"><i class="bi bi-graph-up me-1"></i> Keep improving</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0 fw-bold">Recent Activity</h5>
                <a href="index.php?view=exams" class="btn btn-outline-primary btn-sm">View All Exams</a>
            </div>
            
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="text-muted small">
                        <tr>
                            <th>Exam Title</th>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q_recent = "SELECT e.title, s.end_time, s.score, s.status, e.passing_marks 
                                     FROM exam_submissions s 
                                     JOIN exams e ON s.exam_id = e.exam_id 
                                     WHERE s.student_id = $student_id 
                                     ORDER BY s.end_time DESC LIMIT 5";
                        $res_recent = mysqli_query($conn, $q_recent);
                        if(mysqli_num_rows($res_recent) > 0): 
                            while($r = mysqli_fetch_assoc($res_recent)):
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($r['title']) ?></td>
                            <td class="small"><?= date('M d, Y', strtotime($r['end_time'])) ?></td>
                            <td>
                                <span class="fw-bold <?= ($r['score'] >= $r['passing_marks']) ? 'text-success' : 'text-danger' ?>">
                                    <?= $r['score'] ?>%
                                </span>
                            </td>
                            <td><span class="badge rounded-pill bg-light text-dark border"><?= ucfirst($r['status']) ?></span></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="4" class="text-center py-4 muted small">No exam activity yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
