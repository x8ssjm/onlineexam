<?php
// admin/pages/settings.php

// 1. Ensure Table Exists
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
)");

// 2. Handle Save
$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }
    $msg = "All EmailJS configurations updated successfully.";
}

// 3. Fetch current settings
$res = $conn->query("SELECT * FROM settings");
$settings = [];
if($res) {
    while ($row = $res->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Default values mapping
$defaults = [
    'reg_public_key'     => '',
    'reg_service_id'     => '',
    'reg_template_id'    => '',
    'reset_public_key'   => '',
    'reset_service_id'   => '',
    'reset_template_id'  => ''
];

// Migration/Fallback logic
if (isset($settings['emailjs_public_key'])) {
    if (!isset($settings['reg_public_key'])) $settings['reg_public_key'] = $settings['emailjs_public_key'];
    if (!isset($settings['reset_public_key'])) $settings['reset_public_key'] = $settings['emailjs_public_key'];
}

// Final defaults fill
foreach ($defaults as $k => $v) {
    if (!isset($settings[$k]) || empty($settings[$k])) $settings[$k] = $v;
}
?>

<div class="row settings-container">
    <div class="col-md-10 mx-auto">
        <div class="d-flex align-items-center mb-4">
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                <i class="bi bi-gear-fill fs-3 text-primary"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">Advanced EmailJS Manager</h4>
                <p class="text-muted small mb-0">Completely separate configurations for each email functionality.</p>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div><?= $msg ?></div>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="save_settings" value="1">
            
            <div class="row g-4">
                <!-- Registration Template -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden">
                        <div class="card-header bg-primary text-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-person-plus me-2"></i>Welcome Email</h6>
                            <small class="opacity-75">Registration</small>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Public Key</label>
                                <input type="text" name="settings[reg_public_key]" class="form-control mono" value="<?= htmlspecialchars($settings['reg_public_key']) ?>" placeholder="user_xxxxxxxx">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Service ID</label>
                                <input type="text" name="settings[reg_service_id]" class="form-control mono" value="<?= htmlspecialchars($settings['reg_service_id']) ?>" placeholder="service_xxxxxxx">
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-bold">Template ID</label>
                                <input type="text" name="settings[reg_template_id]" class="form-control mono" value="<?= htmlspecialchars($settings['reg_template_id']) ?>" placeholder="template_xxxxxxx">
                            </div>
                        </div>
                        <div class="card-footer bg-light border-0 py-3">
                            <i class="bi bi-info-circle me-1"></i> <small class="text-muted">Used for bulk student registration.</small>
                        </div>
                    </div>
                </div>

                <!-- Password Reset Template -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden">
                        <div class="card-header bg-primary text-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-key me-2"></i>Reset Notification</h6>
                            <small class="opacity-75">Security</small>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Public Key</label>
                                <input type="text" name="settings[reset_public_key]" class="form-control mono" value="<?= htmlspecialchars($settings['reset_public_key']) ?>" placeholder="user_xxxxxxxx">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Service ID</label>
                                <input type="text" name="settings[reset_service_id]" class="form-control mono" value="<?= htmlspecialchars($settings['reset_service_id']) ?>" placeholder="service_xxxxxxx">
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-bold">Template ID</label>
                                <input type="text" name="settings[reset_template_id]" class="form-control mono" value="<?= htmlspecialchars($settings['reset_template_id']) ?>" placeholder="template_xxxxxxx">
                            </div>
                        </div>
                        <div class="card-footer bg-light border-0 py-3">
                            <i class="bi bi-info-circle me-1"></i> <small class="text-muted">Used for individual password resets.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <button type="submit" class="btn btn-primary px-5 py-2 shadow fw-bold">
                    <i class="bi bi-save me-2"></i> Save All Dynamic Settings
                </button>
                <div class="mt-3 text-muted small">
                    Changes take effect immediately on next email trigger.
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.settings-container .mono { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 0.85rem; background-color: #f8f9fa !important; border: 1px solid #dee2e6 !important; }
.settings-container .mono:focus { background-color: #fff !important; border-color: #0d6efd !important; }
.settings-container .card { border-radius: 12px; }
.settings-container .card-header { border-bottom: 0; }
</style>
