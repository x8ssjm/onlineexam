<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <div>
      <div class="fw-semibold">Question Banks</div>
      <div class="muted small">Add, rename, or delete banks</div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalBank">
      <i class="bi bi-plus-lg me-1"></i> Add Bank
    </button>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th>Bank Name</th>
          <th class="text-end">Questions</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="banksTbody"></tbody>
    </table>
  </div>
</div>
