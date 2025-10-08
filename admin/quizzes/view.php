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

// Get quiz ID from URL
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Get quiz details
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Quiz not found!'
        ];
        redirect('index.php');
    }

    // Get all attempts for this quiz with student info
    $stmt = $pdo->prepare("
        SELECT sqa.*, u.username, u.full_name 
        FROM student_quiz_attempts sqa
        JOIN users u ON sqa.student_id = u.id
        WHERE sqa.quiz_id = ?
        ORDER BY sqa.attempt_date DESC
    ");
    $stmt->execute([$quiz_id]);
    $attempts = $stmt->fetchAll();

    // Calculate statistics
    $total_attempts = count($attempts);
    $passed_count = 0;
    $scores = [];
    
    foreach ($attempts as $attempt) {
        if ($attempt['passed']) {
            $passed_count++;
        }
        $scores[] = $attempt['score'];
    }

    $avg_score = $total_attempts > 0 ? round(array_sum($scores) / $total_attempts, 1) : 0;
    $highest_score = $total_attempts > 0 ? max($scores) : 0;
    $lowest_score = $total_attempts > 0 ? min($scores) : 0;
    $pass_rate = $total_attempts > 0 ? round(($passed_count / $total_attempts) * 100) : 0;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Quiz Results: " . htmlspecialchars($quiz['title']);
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Quiz Results: <?php echo htmlspecialchars($quiz['title']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Quizzes
                    </a>
                </div>
            </div>

            <!-- Quiz Summary -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body text-center">
                            <h5 class="card-title">Total Attempts</h5>
                            <h2 class="card-text"><?php echo $total_attempts; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body text-center">
                            <h5 class="card-title">Average Score</h5>
                            <h2 class="card-text"><?php echo $avg_score; ?>%</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body text-center">
                            <h5 class="card-title">Pass Rate</h5>
                            <h2 class="card-text"><?php echo $pass_rate; ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attempts Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Student Attempts</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($attempts)): ?>
                        <div class="alert alert-info">No students have attempted this quiz yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Username</th>
                                        <th>Score</th>
                                        <th>Status</th>
                                        <th>Attempt Date</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attempts as $attempt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($attempt['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($attempt['username']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $attempt['score'] >= $quiz['passing_score'] ? 'success' : 'danger';
                                            ?>">
                                                <?php echo $attempt['score']; ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($attempt['passed']): ?>
                                                <span class="badge bg-success">Passed</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($attempt['attempt_date'])); ?></td>
                                        <td>
                                            <a href="attempt_details.php?id=<?php echo $attempt['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-search"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Score Distribution -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Score Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Highest Score: <?php echo $highest_score; ?>%</h6>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $highest_score; ?>%" 
                                     aria-valuenow="<?php echo $highest_score; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            
                            <h6>Lowest Score: <?php echo $lowest_score; ?>%</h6>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-danger" role="progressbar" 
                                     style="width: <?php echo $lowest_score; ?>%" 
                                     aria-valuenow="<?php echo $lowest_score; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Passing Threshold: <?php echo $quiz['passing_score']; ?>%</h6>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: <?php echo $quiz['passing_score']; ?>%" 
                                     aria-valuenow="<?php echo $quiz['passing_score']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            
                            <h6>Class Average: <?php echo $avg_score; ?>%</h6>
                            <div class="progress">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: <?php echo $avg_score; ?>%" 
                                     aria-valuenow="<?php echo $avg_score; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>