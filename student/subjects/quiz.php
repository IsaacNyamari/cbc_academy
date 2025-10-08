<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    redirect('login.php');
}

if (!isset($_GET['quiz_id'])) {
    redirect('./view.php');
}

$quiz_id = (int)$_GET['quiz_id'];

try {
    // Get quiz details
    $stmt = $pdo->prepare("
        SELECT q.*, t.title AS topic_title, c.title AS chapter_title, s.name AS subject_name
        FROM quizzes q
        JOIN topics t ON q.topic_id = t.id
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        WHERE q.id = ?
    ");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        redirect('../subjects/');
    }
    
    // Get questions with answers
    $stmt = $pdo->prepare("
        SELECT q.*, a.id AS answer_id, a.answer_text, a.is_correct
        FROM questions q
        LEFT JOIN answers a ON q.id = a.question_id
        WHERE q.quiz_id = ?
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
                'question_text' => $row['question_text'],
                'question_type' => $row['question_type'],
                'points' => $row['points'],
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

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = 0;
    $total_points = 0;
    $responses = [];
    
    foreach ($questions as $question) {
        $total_points += $question['points'];
        $question_id = $question['id'];
        $correct = false;
        
        if ($question['question_type'] === 'multiple_choice') {
            $answer_id = isset($_POST['question_'.$question_id]) ? (int)$_POST['question_'.$question_id] : 0;
            
            // Find if selected answer is correct
            foreach ($question['answers'] as $answer) {
                if ($answer['id'] === $answer_id && $answer['is_correct']) {
                    $score += $question['points'];
                    $correct = true;
                    break;
                }
            }
            
            $responses[] = [
                'question_id' => $question_id,
                'answer_id' => $answer_id,
                'correct' => $correct
            ];
        } 
        // Handle other question types (true_false, short_answer) similarly
    }
    
    $percentage = $total_points > 0 ? round(($score / $total_points) * 100) : 0;
    $passed = $percentage >= $quiz['passing_score'];
    
    // Save attempt to database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO student_quiz_attempts 
            (student_id, quiz_id, score, passed) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $quiz_id, $percentage, $passed ? 1 : 0]);
        
        // Update topic progress if passed
        if ($passed) {
            $stmt = $pdo->prepare("
                UPDATE student_progress 
                SET completion_status = 'completed', quiz_score = ?
                WHERE student_id = ? AND topic_id = ?
            ");
            $stmt->execute([$percentage, $_SESSION['user_id'], $quiz['topic_id']]);
        }
        
        // Redirect to results page
        header("Location: quiz_results.php?attempt_id=" . $pdo->lastInsertId());
        
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

$page_title = "Quiz: " . $quiz['title'];
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
        
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../subjects/">Subjects</a></li>
                        <li class="breadcrumb-item"><a href="../subjects/view.php?id=<?php echo $quiz['id']; ?>"><?php echo htmlspecialchars($quiz['subject_name']); ?></a></li>
                        <li class="breadcrumb-item"><?php echo htmlspecialchars($quiz['chapter_title']); ?></li>
                        <li class="breadcrumb-item"><?php echo htmlspecialchars($quiz['topic_title']); ?></li>
                        <li class="breadcrumb-item active" aria-current="page">Quiz</li>
                    </ol>
                </nav>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0"><?php echo htmlspecialchars($quiz['title']); ?></h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($quiz['instructions'])): ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Instructions</h5>
                        <?php echo nl2br(htmlspecialchars($quiz['instructions'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <p class="text-muted">
                        Passing Score: <?php echo $quiz['passing_score']; ?>% | 
                        Time Limit: None | 
                        Attempts: Unlimited
                    </p>
                    
                    <hr>
                    
                    <form id="quizForm" method="POST">
                        <?php foreach ($questions as $index => $question): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Question <?php echo $index + 1; ?></h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                
                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                    <?php foreach ($question['answers'] as $answer): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" 
                                               name="question_<?php echo $question['id']; ?>" 
                                               id="answer_<?php echo $answer['id']; ?>" 
                                               value="<?php echo $answer['id']; ?>" required>
                                        <label class="form-check-label" for="answer_<?php echo $answer['id']; ?>">
                                            <?php echo htmlspecialchars($answer['answer_text']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                <?php elseif ($question['question_type'] === 'true_false'): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" 
                                               name="question_<?php echo $question['id']; ?>" 
                                               id="question_<?php echo $question['id']; ?>_true" 
                                               value="true" required>
                                        <label class="form-check-label" for="question_<?php echo $question['id']; ?>_true">
                                            True
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" 
                                               name="question_<?php echo $question['id']; ?>" 
                                               id="question_<?php echo $question['id']; ?>_false" 
                                               value="false">
                                        <label class="form-check-label" for="question_<?php echo $question['id']; ?>_false">
                                            False
                                        </label>
                                    </div>
                                <?php elseif ($question['question_type'] === 'short_answer'): ?>
                                    <div class="form-group">
                                        <textarea class="form-control" 
                                                  name="question_<?php echo $question['id']; ?>" 
                                                  rows="3" required></textarea>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Submit Quiz
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>