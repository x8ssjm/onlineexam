<div class="row g-3">
  <div class="col-12">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div>
          <div class="fw-semibold">Exams</div>
          <div class="muted small">Create exams, assign students, set start/end time, run multiple exams</div>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalExam">
          <i class="bi bi-plus-lg me-1"></i> Create Exam
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Exam</th>
              <th>Bank</th>
              <th>Schedule</th>
              <th class="text-end">Assigned</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="examsTbody"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
