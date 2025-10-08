<?php
require_once '../../includes/config.php';

// Get all quizzes created by this teacher with subject info and attempt statistics
try {

    if (isAdmin()) {
        $stmt = $pdo->prepare("SELECT q.id,s.id AS subject_id, q.title, q.passing_score, q.created_at,
               t.title AS topic_title, s.name AS subject_name,
               COUNT(sqa.id) AS attempt_count,
               AVG(sqa.score) AS avg_score,
               SUM(CASE WHEN sqa.passed = 1 THEN 1 ELSE 0 END) AS passed_count
        FROM quizzes q
        LEFT JOIN topics t ON q.topic_id = t.id
        LEFT JOIN chapters c ON t.chapter_id = c.id
        LEFT JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN student_quiz_attempts sqa ON q.id = sqa.quiz_id
        GROUP BY q.id
        ORDER BY s.name, t.title, q.created_at DESC
    ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT q.id,s.id AS subject_id, q.title, q.passing_score, q.created_at,
               t.title AS topic_title, s.name AS subject_name,
               COUNT(sqa.id) AS attempt_count,
               AVG(sqa.score) AS avg_score,
               SUM(CASE WHEN sqa.passed = 1 THEN 1 ELSE 0 END) AS passed_count
        FROM quizzes q
        LEFT JOIN topics t ON q.topic_id = t.id
        LEFT JOIN chapters c ON t.chapter_id = c.id
        LEFT JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN student_quiz_attempts sqa ON q.id = sqa.quiz_id
        WHERE s.created_by = ? OR s.created_by = 1
        GROUP BY q.id
        ORDER BY s.name, t.title, q.created_at DESC
    ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $quizzes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Manage Quizzes";
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Quizzes</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="create.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Create New Quiz
                        </a>
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
                            <th>Attempts</th>
                            <th>Avg. Score</th>
                            <th>Pass Rate</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quizzes)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No quizzes created yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($quizzes as $quiz): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                    <td><?php echo htmlspecialchars($quiz['topic_title']); ?></td>
                                    <td><?php echo htmlspecialchars($quiz['subject_name']); ?></td>
                                    <td><?php echo $quiz['passing_score']; ?>%</td>
                                    <td><?php echo $quiz['attempt_count']; ?></td>
                                    <td>
                                        <?php if ($quiz['attempt_count'] > 0): ?>
                                            <?php echo round($quiz['avg_score'], 1); ?>%
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($quiz['attempt_count'] > 0): ?>
                                            <?php echo round(($quiz['passed_count'] / $quiz['attempt_count']) * 100, 1); ?>%
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo timeAgo($quiz['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="view.php?id=<?php echo $quiz['id']; ?>" class="btn btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $quiz['id']; ?>&subject_id=<?php echo $quiz['subject_id']; ?>" class="btn btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="./results.php?id=<?php echo $quiz['id']; ?>" class="btn btn-success" title="Results">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $quiz['id']; ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this quiz? All attempts will be lost.');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
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