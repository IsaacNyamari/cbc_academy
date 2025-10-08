<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    redirect('../student/dashboard.php');
}

// Get attempt ID from URL
$attempt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Get attempt details with student and quiz info
    $stmt = $pdo->prepare("
        SELECT sqa.*, 
               u.username, u.full_name AS student_name,
               q.title AS quiz_title, q.passing_score, q.instructions
        FROM student_quiz_attempts sqa
        JOIN users u ON sqa.student_id = u.id
        JOIN quizzes q ON sqa.quiz_id = q.id
        WHERE sqa.id = ?
    ");
    $stmt->execute([$attempt_id]);
    $attempt = $stmt->fetch();

    if (!$attempt) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Quiz attempt not found!'
        ];
        redirect('index.php');
    }

    // Get any additional attempt data stored in JSON format
    $attempt_data = json_decode($attempt['attempt_data'] ?? '{}', true);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Attempt Details: " . htmlspecialchars($attempt['quiz_title']);
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Attempt Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view.php?id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Quiz
                    </a>
                </div>
            </div>

            <!-- Attempt Summary -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Student Information</h5>
                            <dl class="row">
                                <dt class="col-sm-4">Name</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($attempt['student_name']); ?></dd>

                                <dt class="col-sm-4">Username</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($attempt['username']); ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h5>Quiz Results</h5>
                            <dl class="row">
                                <dt class="col-sm-4">Quiz</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($attempt['quiz_title']); ?></dd>

                                <dt class="col-sm-4">Score</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-<?php echo $attempt['passed'] ? 'success' : 'danger'; ?>">
                                        <?php echo $attempt['score']; ?>%
                                    </span>
                                </dd>

                                <dt class="col-sm-4">Status</dt>
                                <dd class="col-sm-8">
                                    <?php if ($attempt['passed']): ?>
                                        <span class="badge bg-success">Passed</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Failed</span>
                                    <?php endif; ?>
                                </dd>

                                <dt class="col-sm-4">Attempt Date</dt>
                                <dd class="col-sm-8"><?php echo date('M j, Y H:i', strtotime($attempt['attempt_date'])); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quiz Instructions -->
            <?php if (!empty($attempt['instructions'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quiz Instructions</h5>
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($attempt['instructions'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Attempt Data -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Attempt Details</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($attempt_data)): ?>
                        <div class="alert alert-info">No detailed attempt data available.</div>
                    <?php else: ?>
                        <pre><?php echo htmlspecialchars(json_encode($attempt_data, JSON_PRETTY_PRINT)); ?></pre>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Score Summary -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Score Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="progress mb-3" style="height: 30px;">
                                <div class="progress-bar bg-<?php echo $attempt['passed'] ? 'success' : 'danger'; ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $attempt['score']; ?>%" 
                                     aria-valuenow="<?php echo $attempt['score']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo $attempt['score']; ?>%
                                </div>
                            </div>
                            <p class="text-center">
                                <strong>Student Score:</strong> <?php echo $attempt['score']; ?>%
                            </p>
                        </div>
                        <div class="col-md-6">
                            <div class="progress mb-3" style="height: 30px;">
                                <div class="progress-bar bg-warning" 
                                     role="progressbar" 
                                     style="width: <?php echo $attempt['passing_score']; ?>%" 
                                     aria-valuenow="<?php echo $attempt['passing_score']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    Passing: <?php echo $attempt['passing_score']; ?>%
                                </div>
                            </div>
                            <p class="text-center">
                                <strong>Passing Threshold:</strong> <?php echo $attempt['passing_score']; ?>%
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>