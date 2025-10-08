<?php
require_once '../../includes/config.php';

try {
    // Get all quizzes with latest attempt details
    $stmt = $pdo->prepare("
        SELECT q.id AS quiz_id, q.title, q.passing_score, 
               t.title AS topic_title, s.name AS subject_name,
               sqa.id AS attempt_id, sqa.student_id, sqa.score, sqa.passed, sqa.attempt_date
        FROM quizzes q
        LEFT JOIN topics t ON q.topic_id = t.id
        LEFT JOIN chapters c ON t.chapter_id = c.id
        LEFT JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN student_quiz_attempts sqa 
               ON q.id = sqa.quiz_id
        ORDER BY q.id ASC, sqa.attempt_date DESC
    ");
    $stmt->execute();
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group quizzes with their attempts
    $grouped_quizzes = [];
    foreach ($quizzes as $quiz) {
        $id = $quiz['quiz_id'];
        if (!isset($grouped_quizzes[$id])) {
            $grouped_quizzes[$id] = [
                'quiz_id' => $quiz['quiz_id'],
                'title' => $quiz['title'],
                'topic_title' => $quiz['topic_title'],
                'subject_name' => $quiz['subject_name'],
                'passing_score' => $quiz['passing_score'],
                'attempts' => []
            ];
        }
        if ($quiz['attempt_id']) {
            $grouped_quizzes[$id]['attempts'][] = [
                'attempt_id' => $quiz['attempt_id'],
                'student_id' => $quiz['student_id'],
                'score' => $quiz['score'],
                'passed' => $quiz['passed'],
                'attempt_date' => $quiz['attempt_date']
            ];
        }
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "All Quiz Results";
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">All Quiz Results</h1>
            </div>

            <?php foreach ($grouped_quizzes as $quiz): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <?php echo htmlspecialchars($quiz['title']); ?>  
                            <small class="text-muted">
                                (<?php echo htmlspecialchars($quiz['topic_title']); ?> - <?php echo htmlspecialchars($quiz['subject_name']); ?>)
                            </small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($quiz['attempts'])): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Attempt Date</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                            <th>Review</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($quiz['attempts'] as $attempt): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($attempt['student_id']); ?></td>
                                                <td><?php echo date('F j, Y \a\t g:i a', strtotime($attempt['attempt_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $attempt['passed'] ? 'success' : 'danger'; ?>">
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
                                                <td>
                                                    <a href="review.php?attempt_id=<?php echo $attempt['attempt_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-search"></i> Review
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No attempts found for this quiz.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
