<div class="card p-3">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
      <div class="fw-semibold">Questions (MCQ)</div>
      <div class="muted small">Add, edit, or delete questions; organize by bank</div>
    </div>
    <div class="d-flex gap-2">
      <select class="form-select" id="filterBank" style="min-width:220px"></select>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalQuestion">
        <i class="bi bi-plus-lg me-1"></i> Add Question
      </button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>Question</th>
          <th>Bank</th>
          <th class="text-end">Marks</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="questionsTbody"></tbody>
    </table>
  </div>
</div>
