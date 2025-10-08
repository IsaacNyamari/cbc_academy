<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    redirect('../student/dashboard.php');
}

// Get question ID from URL
$question_id = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;

try {
    // Get question details
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();
    
    if (!$question) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Question not found!'
        ];
        redirect('index.php');
    }

    // Get all answers for this question
    $stmt = $pdo->prepare("
        SELECT *
        FROM answers
        WHERE question_id = ?
        ORDER BY id ASC
    ");
    $stmt->execute([$question_id]);
    $answers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Answers for: " . htmlspecialchars($question['title']);
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Answers for: <?php echo htmlspecialchars($question['title']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="create_answer.php?question_id=<?php echo $question_id; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add Answer
                    </a>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary ms-2">
                        <i class="fas fa-arrow-left"></i> Back to Questions
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Answer List</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($answers)): ?>
                        <div class="alert alert-info">No answers have been added for this question yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Answer Text</th>
                                        <th>Correct?</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($answers as $i => $answer): ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td><?php echo htmlspecialchars($answer['answer_text']); ?></td>
                                            <td>
                                                <?php if ($answer['is_correct']): ?>
                                                    <span class="badge bg-success">Yes</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="edit_answer.php?id=<?php echo $answer['id']; ?>&question_id=<?php echo $question_id; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="delete_answer.php?id=<?php echo $answer['id']; ?>&question_id=<?php echo $question_id; ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Are you sure you want to delete this answer?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>