<?php
require_once '../../includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['attempt_id'])) {
    redirect('index.php');
}

$attempt_id = (int)$_GET['attempt_id'];

try {
    // Get attempt details with student name for admin display
    $stmt = $pdo->prepare("
        SELECT sqa.*, q.title AS quiz_title, q.passing_score,
               t.title AS topic_title, s.name AS subject_name,
               u.full_name AS student_name
        FROM student_quiz_attempts sqa
        JOIN quizzes q ON sqa.quiz_id = q.id
        JOIN topics t ON q.topic_id = t.id
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        JOIN users u ON sqa.student_id = u.id
        WHERE sqa.id = ?
    ");
    $stmt->execute([$attempt_id]);
    $attempt = $stmt->fetch();
    
    if (!$attempt) {
        redirect('index.php');
    }
    
    // Get questions, correct answers, and student responses
    $stmt = $pdo->prepare("
        SELECT ques.id AS question_id, ques.question_text, ques.question_type, ques.points,
               ans.id AS correct_answer_id, ans.answer_text AS correct_answer_text,
               sr.answer_id AS student_answer_id, sr.answer_text AS student_answer_text,
               CASE 
                   WHEN ans.id IS NULL AND sr.answer_text IS NOT NULL THEN 'No correct answer set'
                   WHEN ans.id = sr.answer_id AND ans.is_correct = 1 THEN 'Correct'
                   ELSE 'Incorrect'
               END AS result
        FROM questions ques
        LEFT JOIN answers ans ON ques.id = ans.question_id AND ans.is_correct = 1
        LEFT JOIN student_quiz_responses sr ON ques.id = sr.question_id AND sr.attempt_id = ?
        WHERE ques.quiz_id = ?
        ORDER BY ques.id
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
                <h1 class="h2">
                    Quiz Review: <?php echo htmlspecialchars($attempt['quiz_title']); ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="results.php?id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Results
                    </a>
                </div>
            </div>

            <p><strong>Student:</strong> <?php echo htmlspecialchars($attempt['student_name']); ?></p>
            <p><strong>Subject:</strong> <?php echo htmlspecialchars($attempt['subject_name']); ?></p>
            <p><strong>Topic:</strong> <?php echo htmlspecialchars($attempt['topic_title']); ?></p>

            <div class="alert alert-<?php echo $attempt['passed'] ? 'success' : 'danger'; ?>">
                <h5>
                    <i class="fas <?php echo $attempt['passed'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                    <?php echo $attempt['passed'] ? 'Passed' : 'Did not pass'; ?> this attempt
                </h5>
                <p>Score: <strong><?php echo $attempt['score']; ?>%</strong> (Passing: <?php echo $attempt['passing_score']; ?>%)</p>
                <p>Attempt date: <?php echo date('F j, Y \a\t g:i a', strtotime($attempt['attempt_date'])); ?></p>
            </div>

        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
