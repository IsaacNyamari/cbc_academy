<?php
require_once '../includes/config.php';
include '../includes/header.php';
if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SESSION['role'] !== 'student') {
    redirect('admin/dashboard.php');
}

// Get student progress data
try {
    $stmt = $pdo->prepare("SELECT s.name AS subject_name,s.id AS subject_id, COUNT(t.id) AS total_topics, 
               COUNT(sp.topic_id) AS completed_topics
        FROM subjects s
        JOIN chapters c ON s.id = c.subject_id
        JOIN topics t ON c.id = t.chapter_id
        LEFT JOIN student_progress sp ON t.id = sp.topic_id AND sp.student_id = ? AND sp.completion_status = 'completed'
        GROUP BY s.id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $subjects_progress = $stmt->fetchAll();

    // Get recent activities
    $stmt = $pdo->prepare("SELECT t.title AS topic_title, s.name AS subject_name, sp.last_accessed
        FROM student_progress sp
        JOIN topics t ON sp.topic_id = t.id
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        WHERE sp.student_id = ?
        ORDER BY sp.last_accessed DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_activities = $stmt->fetchAll();

    // Get upcoming quizzes
    $stmt = $pdo->prepare("SELECT q.title AS quiz_title, t.title AS topic_title, s.name AS subject_name
        FROM quizzes q
        JOIN topics t ON q.topic_id = t.id
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN student_quiz_attempts sqa ON q.id = sqa.quiz_id AND sqa.student_id = ?
        WHERE sqa.id IS NULL
        ORDER BY RAND()
        LIMIT 3
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $upcoming_quizzes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
// get all subjects completetion status. if all subjects have been completed, then the student is eligible for a certificate
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total_subjects, 
           SUM(CASE WHEN total_topics = completed_topics THEN 1 ELSE 0 END) AS completed_subjects
    FROM (
        SELECT COUNT(t.id) AS total_topics, 
               COUNT(sp.topic_id) AS completed_topics
        FROM subjects s
        JOIN chapters c ON s.id = c.subject_id
        JOIN topics t ON c.id = t.chapter_id
        LEFT JOIN student_progress sp 
               ON t.id = sp.topic_id 
              AND sp.student_id = ? 
              AND sp.completion_status = 'completed'
        GROUP BY s.id
    ) AS subject_completion
");
$stmt->execute([$_SESSION['user_id']]);
$subject_completion = $stmt->fetch(PDO::FETCH_ASSOC);

$total_subjects = (int) $subject_completion['total_subjects'];
$completed_subjects = (int) $subject_completion['completed_subjects'];

if ($total_subjects > 0 && $completed_subjects === $total_subjects) {
    // Student is eligible — check certificate status
    $certStmt = $pdo->prepare("SELECT * FROM certificates WHERE student_id = ? LIMIT 1");
    $certStmt->execute([$_SESSION['user_id']]);
    $certificate_exists = $certStmt->fetch(PDO::FETCH_ASSOC);
    // get the file path of the certificate
    $certificate_path = $certificate_exists ? $certificate_exists['file_path'] : null;
    if ($certificate_exists) {
        $account_status = "certificate_issued";
    } else {
        $account_status = "eligible_for_certificate";
    }
} else {
    $account_status = "not_eligible";
}
?>
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
                    <i class="fa-solid text-danger fa-circle-exclamation fa-fade fa-5x"></i>
                    <?php ?>
                    <h3 class="mt-2 <?php echo htmlspecialchars($status) === "error" ? "text-danger" : "" ?>"><?php echo htmlspecialchars($message) . " !" ?></h3>
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
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Student Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-3">
        <?php if ($account_status === "certificate_issued"): ?>
            <a href="../../certificates/<?php echo htmlspecialchars($certificate_path); ?>" download="../../certificates/<?php echo htmlspecialchars($certificate_path); ?>" class="btn btn-primary">
                <i class="fa fa-file-pdf" aria-hidden="true"></i> Download Certificate
            </a>
            <a href="../../certificates/<?php echo htmlspecialchars($certificate_path); ?>" class="btn btn-info" target="_blank">
                <i class="fa fa-eye" aria-hidden="true"></i> View Certificate
            </a>
        <?php elseif ($account_status === "eligible_for_certificate"): ?>
            <a href="../../pdf.php" class="btn btn-success" download="../../certificates/<?php echo htmlspecialchars($certificate_path); ?>">
                <i class="fa fa-file-pdf" aria-hidden="true"></i> Generate Certificate
            </a>
        <?php endif; ?>
        <button class="btn btn-secondary" onclick="history.back()"><i class="fa fa-backward" aria-hidden="true"></i> Go Back</button>
    </div>
</div>
<!-- Welcome Banner -->
<?php if ($account_status === "unapproved"): ?>
    <div class="alert alert-danger">
        <h4 class="alert-heading">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h4>
        <p>Your account is active but not approved. Please request approval to continue learning.</p>

        <button type="button" class="btn btn-dark" id="requestApprovalBtn">Request Approval</button>

    </div>
<?php endif ?>
<?php if ($account_status === "approved"): ?>
    <div class="alert alert-primary">
        <h4 class="alert-heading">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h4>
        <p>Continue your learning journey with our CBC platform. Check your progress and explore new topics.</p>
    </div>
<?php endif ?>
<!-- Progress Summary -->
<div class="row mb-4">
    <div class="col-md-12">
        <h4>Your Progress Summary</h4>
        <div class="row">
            <?php foreach ($subjects_progress as $subject):
                $percentage = $subject['total_topics'] > 0 ? round(($subject['completed_topics'] / $subject['total_topics']) * 100) : 0;
            ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($subject['subject_name']); ?></h5>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%"
                                    aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $percentage; ?>%
                                </div>
                            </div>
                            <p class="card-text">
                                <?php echo $subject['completed_topics']; ?> of <?php echo $subject['total_topics']; ?> topics completed
                            </p>
                            <?php if ($account_status === "approved"): ?>
                                <a href="subjects/view.php?id=<?php echo urlencode($subject['subject_id']); ?>" class="btn btn-sm btn-primary">
                                    Continue Learning
                                </a>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activities -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Activities</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php if (empty($recent_activities)): ?>
                        <li class="list-group-item">No recent activities found.</li>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($activity['topic_title']); ?></strong>
                                    <div class="text-muted small"><?php echo htmlspecialchars($activity['subject_name']); ?></div>
                                </div>
                                <span class="badge bg-light text-dark">
                                    <?php echo date('M j, Y', strtotime($activity['last_accessed'])); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Upcoming Quizzes -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Upcoming Quizzes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_quizzes)): ?>
                    <p>No upcoming quizzes at the moment.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($upcoming_quizzes as $quiz): ?>
                            <a href="quizzes/take.php?quiz=<?php echo urlencode($quiz['quiz_title']); ?>"
                                class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($quiz['quiz_title']); ?></h6>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($quiz['topic_title']); ?></p>
                                <small class="text-muted"><?php echo htmlspecialchars($quiz['subject_name']); ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</main>
</div>
</div>

<?php include '../includes/footer.php'; ?>