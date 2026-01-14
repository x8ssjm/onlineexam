<div class="card p-3">
  <!-- Tabs -->
  <ul class="nav nav-pills mb-3 gap-2" id="pills-tab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="pills-questions-tab" data-bs-toggle="pill" data-bs-target="#pills-questions" type="button" role="tab">Questions</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="pills-banks-tab" data-bs-toggle="pill" data-bs-target="#pills-banks" type="button" role="tab">Question Banks</button>
    </li>
  </ul>

  <div class="tab-content" id="pills-tabContent">
    
    <!-- QUESTIONS TAB -->
    <div class="tab-pane fade show active" id="pills-questions" role="tabpanel">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
            <div class="fw-semibold">Questions (MCQ)</div>
            <div class="muted small">Manage questions for your exams</div>
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

    <!-- BANKS TAB -->
    <div class="tab-pane fade" id="pills-banks" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <div>
            <div class="fw-semibold">Question Banks</div>
            <div class="muted small">Categories for organizing questions</div>
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

  </div>
</div>
