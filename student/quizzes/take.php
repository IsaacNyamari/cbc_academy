<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
}

$quiz_id = (int)$_GET['id'];

try {
    // Get quiz details
    $stmt = $pdo->prepare("SELECT q.*, t.title AS topic_title, s.name AS subject_name
        FROM quizzes q
        JOIN topics t ON q.topic_id = t.id
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN answers a ON q.id = a.question_id
        WHERE q.id = ?
    ");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
  
    if (!$quiz) {
        header('Location: index.php');
    }

    // Check if student has already passed this quiz
    $stmt = $pdo->prepare("SELECT * FROM student_quiz_attempts 
        WHERE student_id = ? AND quiz_id = ? AND passed = 1
        ORDER BY attempt_date DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id'], $quiz_id]);
    $passed_attempt = $stmt->fetch();

    if ($passed_attempt) {
        header('Location: results.php?id=' . $quiz_id);
    }

    // Get questions with answers
    $stmt = $pdo->prepare("SELECT DISTINCT q.*, a.id AS answer_id, a.answer_text, a.is_correct
        FROM quizzes q
        LEFT JOIN answers a ON q.id = a.question_id
        WHERE q.id = ?
        ORDER BY q.id, a.id
    ");
    $stmt->execute([$quiz_id]);
    $questions_data = $stmt->fetchAll();

    // Organize questions with answers
    $questions = [];
    foreach ($questions_data as $row) {
        $question_id = $row['id'];
        if (!isset($questions[$question_id])) {
            $questions[$question_id] = [
                'id' => $question_id,
                'question_text' => $row['title'],
                'question_type' => $row['question_type'],
                'points' => $row['points'],
                'passing_score' => $row['passing_score'],
                'answers' => []
            ];
        }

        if ($row['answer_id']) {
            $questions[$question_id]['answers'][] = [
                'id' => $row['answer_id'],
                'answer_text' => $row['answer_text'],
                'is_correct' => $row['is_correct']
            ];
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Quiz: " . $quiz['title'];
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Quiz: <?php echo htmlspecialchars($quiz['title']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Quizzes
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Instructions</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($quiz['instructions'] ?: 'Answer all questions to the best of your ability.')); ?></p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Passing Score: <?php echo $quiz['passing_score']; ?>%
                    </div>
                </div>
            </div>
            <form id="quizForm" method="POST" action="submit_quiz.php">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                <?php $question_list = array_values($questions); ?>
                <?php foreach ($question_list as $index => $question): ?>
                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                Question <?php echo $index + 1; ?>
                                (Passing Score <?php echo $question['passing_score']; ?><?php echo $question['passing_score'] != 1 ? '%' : ''; ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                <?php foreach ($question['answers'] as $answer): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio"
                                            name="answer_id"
                                            id="answer_<?php echo $answer['id']; ?>"
                                            value="<?php echo $answer['id']; ?>" required>
                                        <label class="form-check-label" for="answer_<?php echo $answer['id']; ?>">
                                            <?php echo htmlspecialchars($answer['answer_text']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                
                            <?php elseif ($question['question_type'] === 'true_false'): ?>
                                <?php
                                foreach ($question['answers'] as $answer) {
                                    if ($answer['is_correct'] !== null) { // Only show true/false answers
                                        $value = $answer['is_correct'] ? 'true' : 'false';
                                        $label = $answer['answer_text'] ?: ($answer['is_correct'] ? 'True' : 'False');
                                        ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio"
                                                name="answer_id"
                                                id="answer_<?php echo $value . '_' . $question['id']; ?>"
                                                value="<?php echo $answer['id']; ?>" required>
                                            <label class="form-check-label" for="answer_<?php echo $value . '_' . $question['id']; ?>">
                                                <?php echo htmlspecialchars($label); ?>
                                            </label>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                              
                            <?php elseif ($question['question_type'] === 'short_answer'): ?>
                                <div class="form-group">
                                    <textarea class="form-control"
                                        name="answer_id"
                                        rows="3" required></textarea>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Submit Quiz
                            </button>
                        </div>
                    </div>
                </div>
            </form>


        </main>
    </div>
</div>

<script>
    // Timer functionality if time limit is set
    document.addEventListener('DOMContentLoaded', function() {
        const quizForm = document.getElementById('quizForm');
        quizForm.addEventListener('submit', function() {
            // Disable form submission if already submitted
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>