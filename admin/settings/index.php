<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Only admin can access settings
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

// Initialize message variable
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Update each setting
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $name = substr($key, 8);
                $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE name = ?");
                $stmt->execute([trim($value), $name]);
            }
        }

        $pdo->commit();

        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Settings updated successfully!'
        ];

        // Refresh to show updated values
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Error updating settings: ' . $e->getMessage()
        ];
    }
}

// Get all system settings
try {
    $stmt = $pdo->query("SELECT * FROM site_settings");
    $settings = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">System Settings</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addSettingModal">
                        <i class="fas fa-plus"></i> Add New Setting
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); ?>

            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Value</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($settings as $setting): ?>
                            <tr>
                                <td><?php echo $setting["name"] ?></td>
                                <td><?php echo $setting["setting_value"] ?></td>
                                <td><a href="delete_setting.php?setting_id=<?php echo $setting["id"] ?>" class="btn btn-danger"><i class="fas fa-trash-alt"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Add New Setting Modal -->
<div class="modal fade" id="addSettingModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="addSettingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="add_setting.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSettingModalLabel">Add New Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Setting Name</label>
                        <input type="text" class="form-control" id="name" name="setting_name" required>
                        <small class="text-muted">Lowercase with underscores (e.g. site_title)</small>
                    </div>
                    <div class="mb-3">
                        <label for="setting_value" class="form-label">Value</label>
                        <input type="text" class="form-control" id="setting_value" name="setting_value">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Setting</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show/hide options field based on input type selection
    document.getElementById('input_type').addEventListener('change', function() {
        const optionsContainer = document.getElementById('optionsContainer');
        optionsContainer.style.display = this.value === 'select' ? 'block' : 'none';
    });
</script>

<?php include '../../includes/footer.php'; ?>