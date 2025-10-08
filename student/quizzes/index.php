<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    redirect('login.php');
}

// Get all available quizzes with subject info and attempt status
try {
    $stmt = $pdo->prepare("
        SELECT q.id, q.title, q.passing_score, 
               t.title AS topic_title, s.name AS subject_name,
               sqa.score, sqa.passed, sqa.attempt_date
        FROM quizzes q
        JOIN topics t ON q.topic_id = t.id
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN (
            SELECT quiz_id, score, passed, attempt_date 
            FROM student_quiz_attempts 
            WHERE student_id = ?
            ORDER BY attempt_date DESC
        ) sqa ON q.id = sqa.quiz_id
        GROUP BY q.id
        ORDER BY s.name, t.title, q.passing_score DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $quizzes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "My Quizzes";
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Quizzes</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Filter</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Topic</th>
                            <th>Subject</th>
                            <th>Passing Score</th>
                            <th>Your Result</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quizzes)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No quizzes available at this time.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($quizzes as $quiz): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                <td><?php echo htmlspecialchars($quiz['topic_title']); ?></td>
                                <td><?php echo htmlspecialchars($quiz['subject_name']); ?></td>
                                <td><?php echo $quiz['passing_score']; ?>%</td>
                                <td>
                                    <?php if ($quiz['score'] !== null): ?>
                                        <span class="badge bg-<?php echo $quiz['passed'] ? 'success' : 'danger'; ?>">
                                            <?php echo $quiz['score']; ?>%
                                        </span>
                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($quiz['attempt_date'])); ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not Attempted</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($quiz['score'] === null): ?>
                                        <a href="take.php?id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-play"></i> Take Quiz
                                        </a>
                                    <?php else: ?>
                                        <a href="results.php?id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-chart-bar"></i> View Results
                                        </a>
                                        <?php if (!$quiz['passed']): ?>
                                            <a href="take.php?id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-redo"></i> Retake
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>