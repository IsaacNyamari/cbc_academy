<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$quiz_id = (int)$_GET['id'];

try {
    // Get quiz details and latest attempt
    $stmt = $pdo->prepare("
        SELECT q.*, t.title AS topic_title, s.name AS subject_name,
               sqa.id AS attempt_id, sqa.score, sqa.passed, sqa.attempt_date
        FROM quizzes q
        JOIN topics t ON q.topic_id = t.id
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        JOIN student_quiz_attempts sqa ON q.id = sqa.quiz_id
        WHERE q.id = ? AND sqa.student_id = ?
        ORDER BY sqa.attempt_date DESC
        LIMIT 1
    ");
    $stmt->execute([$quiz_id, $_SESSION['user_id']]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        redirect('index.php');
    }
    
    // Get all attempts for this quiz
    $stmt = $pdo->prepare("
        SELECT * FROM student_quiz_attempts
        WHERE quiz_id = ? AND student_id = ?
        ORDER BY attempt_date DESC
    ");
    $stmt->execute([$quiz_id, $_SESSION['user_id']]);
    $attempts = $stmt->fetchAll();
    
    // Get questions with correct answers and student responses
    $stmt = $pdo->prepare("
        SELECT q.id AS question_id, q.question_text, q.question_type, q.points,
               a.id AS correct_answer_id, a.answer_text AS correct_answer_text,
               sr.answer_id AS student_answer_id, sr.answer_text AS student_answer_text,
               CASE 
                   WHEN a.id IS NULL AND sr.answer_text IS NOT NULL THEN sr.answer_text
                   WHEN a.id = sr.answer_id AND a.is_correct = 1 THEN 'Correct'
                   ELSE 'Incorrect'
               END AS result
        FROM questions q
        LEFT JOIN answers a ON q.id = a.question_id AND a.is_correct = 1
        LEFT JOIN student_quiz_responses sr ON q.id = sr.question_id AND sr.attempt_id = ?
        WHERE q.quiz_id = ?
        ORDER BY q.id
    ");
    $stmt->execute([$quiz['attempt_id'], $quiz_id]);
    $questions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Quiz Results: " . $quiz['title'];
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

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card text-white bg-<?php echo $quiz['passed'] ? 'success' : 'danger'; ?>">
                        <div class="card-body text-center">
                            <h3 class="card-title">Your Score</h3>
                            <div class="display-4"><?php echo $quiz['score']; ?>%</div>
                            <p class="card-text">
                                <?php if ($quiz['passed']): ?>
                                    <i class="fas fa-check-circle"></i> You passed this quiz!
                                <?php else: ?>
                                    <i class="fas fa-times-circle"></i> You did not pass (needed <?php echo $quiz['passing_score']; ?>%)
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Quiz Details</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Topic:</strong> <?php echo htmlspecialchars($quiz['topic_title']); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Subject:</strong> <?php echo htmlspecialchars($quiz['subject_name']); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Attempt Date:</strong> <?php echo date('F j, Y \a\t g:i a', strtotime($quiz['attempt_date'])); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Passing Score:</strong> <?php echo $quiz['passing_score']; ?>%
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Attempt History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Attempt Date</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attempts as $attempt): ?>
                                <tr>
                                    <td><?php echo date('M j, Y g:i a', strtotime($attempt['attempt_date'])); ?></td>
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
                                        <a href="review.php?attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-search"></i> Review
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Question Review</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $current_question_id = null;
                    foreach ($questions as $index => $question): 
                        if ($current_question_id !== $question['question_id']) {
                            $current_question_id = $question['question_id'];
                            $is_correct = $question['result'] === 'Correct';
                    ?>
                            <div class="card mb-3 border-<?php echo $is_correct ? 'success' : 'danger'; ?>">
                                <div class="card-header bg-<?php echo $is_correct ? 'success' : 'danger'; ?> text-white">
                                    <h5 class="mb-0">Question <?php echo $index + 1; ?> (<?php echo $question['points']; ?> pts)</h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                    
                                    <div class="mb-3 p-3 bg-light rounded">
                                        <strong>Your Answer:</strong><br>
                                        <?php if ($question['student_answer_id'] === 'Correct'): ?>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle"></i> 
                                                <?php echo htmlspecialchars($question['student_answer_text'] ?: 'Correct'); ?>
                                            </span>
                                        <?php elseif ($question['student_answer_id'] === 'Incorrect'): ?>
                                            <span class="text-danger">
                                                <i class="fas fa-times-circle"></i> 
                                                <?php echo htmlspecialchars($question['student_answer_text'] ?: 'Incorrect'); ?>
                                            </span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($question['student_answer_text']); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!$is_correct && $question['correct_answer_text']): ?>
                                    <div class="mb-3 p-3 bg-success bg-opacity-10 rounded">
                                        <strong>Correct Answer:</strong><br>
                                        <?php echo htmlspecialchars($question['correct_answer_text']); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="alert alert-<?php echo $is_correct ? 'success' : 'danger'; ?>">
                                        <strong>Result:</strong> 
                                        <?php if ($is_correct): ?>
                                            <i class="fas fa-check-circle"></i> Correct (+<?php echo $question['points']; ?> pts)
                                        <?php else: ?>
                                            <i class="fas fa-times-circle"></i> Incorrect (0 pts)
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                    <?php 
                        }
                    endforeach; 
                    ?>
                    
                    <?php if (!$quiz['passed']): ?>
                    <div class="d-grid mt-3">
                        <a href="take.php?id=<?php echo $quiz_id; ?>" class="btn btn-warning btn-lg">
                            <i class="fas fa-redo"></i> Retake Quiz
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>