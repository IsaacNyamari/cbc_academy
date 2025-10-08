<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    redirect('login.php');
}

try {
    // Get all subjects with progress
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, 
               COUNT(t.id) AS total_topics, 
               COUNT(sp.topic_id) AS completed_topics
        FROM subjects s
        JOIN chapters c ON s.id = c.subject_id
        JOIN topics t ON c.id = t.chapter_id
        LEFT JOIN student_progress sp ON t.id = sp.topic_id AND sp.student_id = ? AND sp.completion_status = 'completed'
        GROUP BY s.id
        ORDER BY s.name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $subjects = $stmt->fetchAll();
    
    // Get recent completed topics
    $stmt = $pdo->prepare("
        SELECT t.title AS topic_title, s.name AS subject_name, sp.last_accessed
        FROM student_progress sp
        JOIN topics t ON sp.topic_id = t.id
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        WHERE sp.student_id = ? AND sp.completion_status = 'completed'
        ORDER BY sp.last_accessed DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_completed = $stmt->fetchAll();
    
    // Get quiz performance
    $stmt = $pdo->prepare("
        SELECT s.name AS subject_name, 
               AVG(sqa.score) AS avg_score,
               COUNT(sqa.id) AS quiz_count
        FROM student_quiz_attempts sqa
        JOIN quizzes q ON sqa.quiz_id = q.id
        JOIN topics t ON q.topic_id = t.id
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        WHERE sqa.student_id = ?
        GROUP BY s.id
        ORDER BY avg_score DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $quiz_performance = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "My Progress";
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Learning Progress</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                </div>
            </div>

            <!-- Overall Progress -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Overall Progress</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($subjects as $subject): 
                            $percentage = $subject['total_topics'] > 0 ? round(($subject['completed_topics'] / $subject['total_topics']) * 100) : 0;
                        ?>
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($subject['name']); ?></h6>
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                             aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $percentage; ?>%
                                        </div>
                                    </div>
                                    <p class="card-text small">
                                        <?php echo $subject['completed_topics']; ?> of <?php echo $subject['total_topics']; ?> topics completed
                                    </p>
                                    <a href="../subjects/view.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Completed Topics -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Recently Completed Topics</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_completed)): ?>
                                <p>You haven't completed any topics yet.</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($recent_completed as $topic): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($topic['topic_title']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($topic['subject_name']); ?></small>
                                            </div>
                                            <small><?php echo date('M j', strtotime($topic['last_accessed'])); ?></small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quiz Performance -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Quiz Performance by Subject</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($quiz_performance)): ?>
                                <p>No quiz attempts yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Average Score</th>
                                                <th>Quizzes Taken</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($quiz_performance as $subject): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar <?php echo $subject['avg_score'] >= 70 ? 'bg-success' : ($subject['avg_score'] >= 50 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                             role="progressbar" style="width: <?php echo $subject['avg_score']; ?>%" 
                                                             aria-valuenow="<?php echo $subject['avg_score']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                            <?php echo round($subject['avg_score']); ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $subject['quiz_count']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>