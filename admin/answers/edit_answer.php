<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    header('Location: ../student/dashboard.php');
}

// Get answer ID from URL
$answer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$question_id = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;

try {
    // Get the answer
    $stmt = $pdo->prepare("SELECT * FROM answers WHERE id = ?");
    $stmt->execute([$answer_id]);
    $answer = $stmt->fetch();

    if (!$answer) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Answer not found!'
        ];
        header("Location: view_answers.php?question_id=" . $question_id);
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $answer_text = trim($_POST['answer_text']);
        $is_correct = isset($_POST['is_correct']) ? 1 : 0;

        if (empty($answer_text)) {
            $error = "Answer text cannot be empty.";
        } else {
            $stmt = $pdo->prepare("UPDATE answers SET answer_text = ?, is_correct = ? WHERE id = ?");
            $stmt->execute([$answer_text, $is_correct, $answer_id]);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Answer updated successfully!'
            ];
            header("Location: view_answers.php?question_id=" . $question_id);
        }
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Edit Answer";
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Answer</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view_answers.php?question_id=<?php echo $question_id; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Answers
                    </a>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Answer</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="answer_text" class="form-label">Answer Text</label>
                            <textarea name="answer_text" id="answer_text" class="form-control" rows="3" required><?php echo htmlspecialchars($answer['answer_text']); ?></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_correct" id="is_correct" value="1"
                                <?php echo $answer['is_correct'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_correct">
                                Mark as correct answer
                            </label>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
