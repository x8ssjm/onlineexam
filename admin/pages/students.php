<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <div>
      <div class="fw-semibold">Students</div>
      <div class="muted small">Add, edit details, remove students</div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalStudent">
      <i class="bi bi-plus-lg me-1"></i> Add Student
    </button>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th class="text-end">Student ID</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="studentsTbody"></tbody>
    </table>
  </div>
</div>
