<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    redirect('login.php');
}

if (!isset($_GET['attempt_id'])) {
    redirect('index.php');
}

$attempt_id = (int)$_GET['attempt_id'];

try {
    // Get attempt details
    $stmt = $pdo->prepare("
        SELECT sqa.*, q.title AS quiz_title, q.passing_score,
               t.title AS topic_title, s.name AS subject_name
        FROM student_quiz_attempts sqa
        JOIN quizzes q ON sqa.quiz_id = q.id
        JOIN topics t ON q.topic_id = t.id
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        WHERE sqa.id = ? AND sqa.student_id = ?
    ");
    $stmt->execute([$attempt_id, $_SESSION['user_id']]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        redirect('index.php');
    }
    
    // Get questions with correct answers and student responses
    $stmt = $pdo->prepare("
        SELECT q.id AS question_id, q.title, q.question_type, q.points,
               a.id AS correct_answer_id, a.answer_text AS correct_answer_text,
               sr.answer_id AS student_answer_id, sr.answer_text AS student_answer_text,
               CASE 
                   WHEN a.id IS NULL AND sr.answer_text IS NOT NULL THEN sr.answer_text
                   WHEN a.id = sr.answer_id AND a.is_correct = 1 THEN 'Correct'
                   ELSE 'Incorrect'
               END AS result
        FROM quizzes q
        LEFT JOIN answers a ON q.id = a.question_id AND a.is_correct = 1
        LEFT JOIN student_quiz_responses sr ON q.id = sr.question_id AND sr.attempt_id = ?
        WHERE q.id = ?
        ORDER BY q.id
    ");
    $stmt->execute([$attempt_id, $attempt['quiz_id']]);
    $questions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Quiz Review: " . $attempt['quiz_title'];
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Quiz Review: <?php echo htmlspecialchars($attempt['quiz_title']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="results.php?id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Results
                    </a>
                </div>
            </div>

            <div class="alert alert-<?php echo $attempt['passed'] ? 'success' : 'danger'; ?>">
                <h5>
                    <i class="fas <?php echo $attempt['passed'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                    You <?php echo $attempt['passed'] ? 'passed' : 'did not pass'; ?> this attempt
                </h5>
                <p>Your score: <strong><?php echo $attempt['score']; ?>%</strong> (Passing: <?php echo $attempt['passing_score']; ?>%)</p>
                <p>Attempt date: <?php echo date('F j, Y \a\t g:i a', strtotime($attempt['attempt_date'])); ?></p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Detailed Review</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $current_question_id = null;
                    foreach ($questions as $index => $question): 
                        if ($current_question_id !== $question['question_id']) {
                            $current_question_id = $question['question_id'];
                            $is_correct = $question['result'];
                    ?>
                            <div class="card mb-3 border-<?php echo $is_correct ? 'success' : 'danger'; ?>">
                                <div class="card-header bg-<?php echo $is_correct ? 'success' : 'danger'; ?> text-white">
                                    <h5 class="mb-0">Question <?php echo $index + 1; ?> (<?php echo $question['points']; ?> pts)</h5>
                                </div>
                                <div class="card-body">
                                  
                                    <div class="mb-3 p-3 bg-light rounded">
                                        <strong>Your Answer:</strong><br>
                                        <?php echo $question['correct_answer_text']; ?>
                                        <?php if ($question['result'] === 'Correct'): ?>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle"></i> 
                                                <?php echo htmlspecialchars($question['student_answer_text'] ?: 'Correct'); ?>
                                            </span>
                                        <?php elseif ($question['result'] === 'Incorrect'): ?>
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
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>