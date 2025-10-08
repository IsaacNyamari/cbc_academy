<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    redirect('login.php');
}

if (!isset($_GET['attempt_id'])) {
    redirect('../subjects/');
}

$attempt_id = (int)$_GET['attempt_id'];

try {
    // Get attempt details
    $stmt = $pdo->prepare("
        SELECT sqa.*, q.title AS quiz_title, q.passing_score,
               t.title AS topic_title, c.title AS chapter_title, s.name AS subject_name
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
        redirect('../subjects/');
    }

    // Get questions with correct answers and student responses
    $stmt = $pdo->prepare("
        SELECT q.id AS question_id, q.question_text, q.question_type, q.points,
               a.id AS correct_answer_id, a.answer_text AS correct_answer_text,
               sr.answer_id AS student_answer_id, 
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
    $stmt->execute([$attempt_id, $attempt['quiz_id']]);
    $questions = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Quiz Results: " . $attempt['quiz_title'];
include '../../includes/header.php';
?>


<?php include '../../includes/sidebar.php'; ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../subjects/">Subjects</a></li>
            <li class="breadcrumb-item"><a href="../subjects/view.php?id=<?php echo $attempt['id']; ?>"><?php echo htmlspecialchars($attempt['subject_name']); ?></a></li>
            <li class="breadcrumb-item"><?php echo htmlspecialchars($attempt['chapter_title']); ?></li>
            <li class="breadcrumb-item"><?php echo htmlspecialchars($attempt['topic_title']); ?></li>
            <li class="breadcrumb-item active" aria-current="page">Quiz Results</li>
        </ol>
    </nav>
</div>

<div class="card mb-4">
    <div class="card-header <?php echo $attempt['passed'] ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
        <h2 class="mb-0">Quiz Results: <?php echo htmlspecialchars($attempt['quiz_title']); ?></h2>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title">Your Score</h3>
                        <div class="display-4 <?php echo $attempt['passed'] ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $attempt['score']; ?>%
                        </div>
                        <p class="card-text">
                            <?php if ($attempt['passed']): ?>
                                <span class="badge bg-success">Passed</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Not Passed</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title">Passing Score</h3>
                        <div class="display-4"><?php echo $attempt['passing_score']; ?>%</div>
                        <p class="card-text">
                            Attempt Date: <?php echo date('M j, Y \a\t g:i a', strtotime($attempt['attempt_date'])); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($attempt['passed']): ?>
            <div class="alert alert-success">
                <h4><i class="fas fa-check-circle"></i> Congratulations!</h4>
                <p>You have passed this quiz. You can now continue to the next topic.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <h4><i class="fas fa-exclamation-circle"></i> Try Again</h4>
                <p>You didn't pass this quiz. Review the material and try again.</p>
                <a href="quiz.php?quiz_id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Retake Quiz
                </a>
            </div>
        <?php endif; ?>

        <hr>

        <h4>Question Review</h4>

        <?php
        $current_question_id = null;
        foreach ($questions as $index => $question):
            if ($current_question_id !== $question['question_id']) {
                $current_question_id = $question['question_id'];
                $first_answer = true;
        ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Question <?php echo $index + 1; ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo htmlspecialchars($question['question_text']); ?></p>

                        <div class="mb-3">
                            <strong>Your Answer:</strong>
                            <?php if ($question['student_answer_id'] === 'Correct'): ?>
                                <span class="text-success">
                                    <i class="fas fa-check-circle"></i> Correct
                                </span>
                            <?php elseif ($question['student_answer_id'] === 'Incorrect'): ?>
                                <span class="text-danger">
                                    <i class="fas fa-times-circle"></i> Incorrect
                                </span>
                            <?php else: ?>
                                <?php echo htmlspecialchars($question['student_answer_id']); ?>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <strong>Correct Answer:</strong>
                            <?php echo htmlspecialchars($question['correct_answer_text']); ?>
                        </div>

                        <div class="alert alert-light">
                            <strong>Points:</strong> <?php echo $question['points']; ?>
                        </div>
                    </div>
                </div>
        <?php
            }
        endforeach;
        ?>

        <div class="d-grid">
            <a href="../subjects/topic.php?id=<?php echo $attempt['id']; ?>" class="btn btn-primary btn-lg">
                <i class="fas fa-arrow-left"></i> Back to Topic
            </a>
        </div>
    </div>
</div>
</main>
</div>
</div>

<?php include '../../includes/footer.php'; ?>