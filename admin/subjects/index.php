<?php
require_once '../../includes/config.php';
include '../../includes/header.php';
$systems = $pdo->query("SELECT * FROM systems")->fetchAll(PDO::FETCH_ASSOC);


if (isset($_GET["status"])) {
    // i want a bootstrap modal to show the status message
    $status = $_GET["status"];
    $message = isset($_GET["message"]) ? $_GET["message"] : '';
?>
    <div
        class="modal fade"
        id="statusModal"
        tabindex="-1"
        role="dialog"
        data-bs-backdrop="static"
        aria-labelledby="modalTitleId"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-capitalize" id="modalTitleId">
                        <?php echo htmlspecialchars($status); ?>
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex flex-column text-center justify-content-center align-items-center align-content-center">
                    <?php htmlspecialchars($status) === "error" ? "text-danger" : "" ?>
                    <i class="fa-solid fa-circle-xmark fa-2x"></i>
                    <?php ?>
                    <p class="text-capitalize mt-2 <?php echo htmlspecialchars($status) === "error" ? "text-danger" : "" ?>"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>


<?php

}
// Get all subjects
try {
    if (isAdmin()) {
        $stmt = $pdo->prepare("SELECT s.*, u.full_name AS teacher_name FROM subjects s LEFT JOIN users u ON s.created_by = u.id ORDER BY s.name");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT s.*, u.full_name AS teacher_name FROM subjects s LEFT JOIN users u ON s.created_by = u.id WHERE s.created_by = ? ORDER BY s.name");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Manage Subjects";

?>

<?php include '../../includes/sidebar.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage <?php echo $_SESSION['role'] === "teacher" ? "My" : ""; ?> Subjects</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i class="fas fa-plus"></i> Add New Subject
        </button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Subject has been <?php echo $_GET['success']; ?> successfully.</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject Name</th>
                        <th>Description</th>
                        <?php if (isAdmin()): ?>
                            <th>Created By</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                        <tr>
                            <td colspan="<?php echo isAdmin() ? 5 : 4; ?>" class="text-center">No subjects found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $index => $subject): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                <td><?php echo htmlspecialchars($subject['description']); ?></td>
                                <?php if (isAdmin()): ?>
                                    <td><?php echo htmlspecialchars($subject['teacher_name'] ?? 'N/A'); ?></td>
                                <?php endif; ?>
                                <td>
                                    <a href="view.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this subject?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>
</div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="create.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Subject Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="system_id" class="form-label">System</label>
                        <select class="form-select" id="system_id" name="system_id" required>
                            <option value="">Select a system</option>
                            <?php foreach ($systems as $system): ?>
                                <option value="<?php echo $system['system_id']; ?>"><?php echo htmlspecialchars($system['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>