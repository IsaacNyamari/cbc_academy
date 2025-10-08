<?php
require_once '../includes/config.php';
include '../includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SESSION['role'] === 'student') {
    header('Location: ../../student/dashboard.php');
}

// Get statistics for admin dashboard
try {
    // Total students
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'student'");
    $stmt->execute();
    $total_students = $stmt->fetch()['total'];

    // Total teachers
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'teacher'");
    $stmt->execute();
    $total_teachers = $stmt->fetch()['total'];

    // Total subjects
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM subjects");
    $stmt->execute();
    $total_subjects = $stmt->fetch()['total'];

    // Recent students
    $stmt = $pdo->prepare("SELECT username, full_name, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_students = $stmt->fetchAll();

    // Recent activities (for teachers)
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("
            SELECT t.title AS topic_title, s.name AS subject_name, COUNT(sp.student_id) AS students_completed
            FROM topics t
            JOIN chapters c ON t.chapter_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN student_progress sp ON t.id = sp.topic_id AND sp.completion_status = 'completed'
            WHERE s.created_by = ?
            GROUP BY t.id
            ORDER BY t.id DESC
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $teacher_activities = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} ?>
<?php
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
    <script>
        var statusModal = document.getElementById('statusModal');

        $(document).ready(function() {
            // $('#statusModal .modal-body').text('<?php echo addslashes($message); ?>');
            $('#statusModal').modal('show');
        });
    </script>
<?php
}
// $page_title = $subject['name'];
?>


<?php include '../includes/sidebar.php'; ?>

<!-- Content Wrapper -->
<main class="app-main">
    <!-- Page Header -->
  

    <!-- Main Content -->
    <div class="app-content">

        <!-- Welcome Banner -->
        <div class="alert alert-primary mb-4">
            <h4 class="alert-heading">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h4>
            <p>Manage and track student progress.</p>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card text-white bg-primary h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Students</h5>
                        <h2 class="card-text"><?php echo $total_students; ?></h2>
                        <a href="users/students.php" class="text-white">View all students</a>
                    </div>
                </div>
            </div>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="col-md-4 mb-3">
                    <div class="card text-white bg-success h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Teachers</h5>
                            <h2 class="card-text"><?php echo $total_teachers; ?></h2>
                            <a href="users/teachers.php" class="text-white">View all teachers</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-md-4 mb-3">
                <div class="card text-white bg-info h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Subjects</h5>
                        <h2 class="card-text"><?php echo $total_subjects; ?></h2>
                        <a href="subjects/" class="text-white">Manage subjects</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Students + Teacher Activities -->
        <div class="row">
            <!-- Recent Students -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Recent Students</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php if (empty($recent_students)): ?>
                                <li class="list-group-item">No recent students found.</li>
                            <?php else: ?>
                                <?php foreach ($recent_students as $student): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                            <div class="text-muted small">@<?php echo htmlspecialchars($student['username']); ?></div>
                                        </div>
                                        <span class="badge bg-light text-dark">
                                            <?php echo date('M j, Y', strtotime($student['created_at'])); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Teacher Activities -->
            <?php if ($_SESSION['role'] === 'teacher'): ?>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Your Topics Progress</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($teacher_activities)): ?>
                                <p>No topics created yet.</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($teacher_activities as $activity): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['topic_title']); ?></h6>
                                                <span class="badge bg-primary rounded-pill">
                                                    <?php echo $activity['students_completed']; ?> completed
                                                </span>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($activity['subject_name']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<!-- /.content-wrapper -->


<?php include '../includes/footer.php'; ?>