<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
//     redirect('login.php');
// }
$userId = $_SESSION['user_id'];
$subject = new Subject();
if (!isset($_GET['id'])) {
    redirect('index.php');
}

$topic_id = (int)$_GET['id'];
$subject_id = $_COOKIE["subject_id"];
try {
    // Get topic details with chapter and subject info
    $stmt = $pdo->prepare("
        SELECT t.*, c.title AS chapter_title, c.id AS chapter_id, 
               s.name AS subject_name, s.id AS subject_id,
               q.id AS quiz_id, q.title AS quiz_title, u.full_name AS teacher_name, u.email AS teacher_email
        FROM topics t
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN quizzes q ON t.id = q.topic_id
        LEFT JOIN users u ON s.created_by= u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch();

    if (!$topic) {
        redirect('index.php');
    }

    // Get student progress for this topic
    $stmt = $pdo->prepare("
        SELECT * FROM student_progress 
        WHERE student_id = ? AND topic_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $topic_id]);
    $progress = $stmt->fetch();

    // Update or create progress record
    $now = date('Y-m-d H:i:s');
    if ($progress) {
        // Update last accessed time
        $stmt = $pdo->prepare("
            UPDATE student_progress 
            SET last_accessed = now(), completion_status = ?
            WHERE student_id = ? AND topic_id = ?
        ");
        $status = $progress['completion_status'] === 'completed' ? 'completed' : 'in progress';
        $stmt->execute([$status, $_SESSION['user_id'], $topic_id]);
    } else {
        // Create new progress record
        $stmt = $pdo->prepare("
            INSERT INTO student_progress 
            (student_id, topic_id, completion_status, last_accessed) 
            VALUES (?, ?, 'in_progress', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $topic_id, $now]);
    }
    // lets get from answers table and make sure the student completes the topic after taking the answers
    $checkAnswersStmt = $pdo->prepare("
        SELECT COUNT(*) AS total_answers 
        FROM answers 
        WHERE question_id = ? AND is_correct = 1
    ");
    $checkAnswersStmt->execute([$topic["quiz_id"]]);
    $answers = $checkAnswersStmt->fetch();
    if ($answers['total_answers'] > 0) {
        // If there are answers, mark the topic as completed
        $stmt = $pdo->prepare("
            UPDATE student_progress 
            SET completion_status = 'completed', completed_at = now()
            WHERE student_id = ? AND topic_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $topic_id]);
        $answersCount = $answers['total_answers'];
    } else {
        $answersCount = $answers['total_answers'];
        // If no answers, just update last accessed
        $stmt = $pdo->prepare("
            UPDATE student_progress 
            SET last_accessed = ?
            WHERE student_id = ? AND topic_id = ?
        ");
        $stmt->execute([$now, $_SESSION['user_id'], $topic_id]);
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
try {
    $stmt = $pdo->prepare("SELECT * FROM student_progress where student_id = ? and topic_id = ? ORDER BY completed_at LIMIT 1");
    $stmt->execute([$userId, $topic_id]);
    $results = $stmt->fetch();
    $completion_status = $results['completion_status'];
} catch (PDOException $e) {
    die("An Error occured: " . $e);
}
$page_title = $topic['title'];
include '../../includes/header.php';

?>

<?php include '../../includes/sidebar.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../subjects/">Subjects</a></li>
            <li class="breadcrumb-item"><a href="../subjects/view.php?id=<?php echo $topic['subject_id']; ?>"><?php echo htmlspecialchars($topic['subject_name']); ?></a></li>
            <li class="breadcrumb-item">Chapter <?php echo $topic['chapter_title']; ?></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($topic['title']); ?></li>
        </ol>
    </nav>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">Print</button>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="card-title"><?php echo htmlspecialchars($topic['title']); ?></h2>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <small class="text-muted">
                Subject: <?php echo htmlspecialchars($topic['subject_name']); ?> |
                Chapter: <?php echo htmlspecialchars($topic['chapter_title']); ?> |
                Teacher: <?php echo htmlspecialchars($topic['teacher_name']); ?>
            </small>
            <?php if ($completion_status !== "completed" && $answersCount == 0): ?>
                <button id="markCompleteBtn" class="btn btn-sm btn-success">
                    <i class="fas fa-check"></i> Mark as Complete
                </button>
            <?php endif; ?>
            <?php if ($completion_status == "completed"): ?>
                <button id="markCompleteBtn" class="btn btn-sm btn-secondary" disabled="true">
                    <i class="fas fa-check"></i> Completed
                </button>
            <?php endif; ?>
        </div>

        <?php if ($topic['video_url']): ?>
            <div class="ratio ratio-16x9 mb-4">
                <iframe src="<?php echo htmlspecialchars($topic['video_url']); ?>"
                    title="<?php echo htmlspecialchars($topic['title']); ?>"
                    allowfullscreen></iframe>
            </div>
        <?php endif; ?>

        <div class="topic-content">
            <?php echo nl2br(($topic['content'])); ?>
        </div>
    </div>
</div>
<?php if ($completion_status == "completed"): ?>
    <?php if ($topic['quiz_id']): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Quiz: <?php echo htmlspecialchars($topic['quiz_title']); ?></h5>
            </div>
            <div class="card-body">
                <p>Test your knowledge with this quiz. You'll need to score at least 70% to pass.</p>

                <?php
                // Check if student has already taken this quiz
                $stmt = $pdo->prepare("
                        SELECT * FROM student_quiz_attempts 
                        WHERE student_id = ? AND quiz_id = ?
                        ORDER BY attempt_date DESC
                        LIMIT 1
                    ");
                $stmt->execute([$_SESSION['user_id'], $topic['quiz_id']]);
                $attempt = $stmt->fetch();

                if ($attempt):
                    $passed_class = $attempt['passed'] ? 'text-success' : 'text-danger';
                ?>
                    <div class="alert <?php echo $attempt['passed'] ? 'alert-success' : 'alert-danger'; ?>">
                        <h5>
                            <i class="fas <?php echo $attempt['passed'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            You <?php echo $attempt['passed'] ? 'passed' : 'did not pass'; ?> this quiz
                        </h5>
                        <p>Your score: <strong class="<?php echo $passed_class; ?>"><?php echo $attempt['score']; ?>%</strong></p>
                        <p>Attempt date: <?php echo date('M j, Y \a\t g:i a', strtotime($attempt['attempt_date'])); ?></p>

                        <?php if (!$attempt['passed']): ?>
                            <a href="quiz.php?quiz_id=<?php echo $topic['quiz_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Try Again
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <a href="../quizzes/take.php?id=<?php echo $topic['quiz_id']; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-play"></i> Start Quiz
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
</main>
</div>
</div>

<script>
    // AJAX to mark topic as complete
    document.getElementById('markCompleteBtn').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        fetch('../../ajax/mark_complete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `topic_id=<?php echo $topic_id; ?>&topic_title=<?php echo $topic['title']; ?>&teacher_email=<?php echo $topic['teacher_email']; ?>`
            })
            .then(response => response.json())
            .then(data => {

                if (data.success) {
                    this.innerHTML = '<i class="fas fa-check"></i> Completed';
                    document.getElementById('markCompleteBtn').innerHTML = '<i class="fas fa-check"></i> Completed';
                    document.getElementById('markCompleteBtn').classList.remove('btn-success');
                    document.getElementById('markCompleteBtn').classList.add('btn-secondary');
                    document.getElementById('markCompleteBtn').disabled = true;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
    });
</script>

<?php include '../../includes/footer.php'; ?>