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

// Validate question_id
$question_id = (int)($_GET['question_id'] ?? 0);
if ($question_id <= 0) {
    die("Invalid question ID.");
}

// Fetch question details
try {
    $stmt = $pdo->prepare("
        SELECT id, title AS question_text
        FROM quizzes
        WHERE id = ?
    ");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();

    if (!$question) {
        die("Question not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    $answer_text = trim($_POST['answer_text'] ?? '');
    $is_correct = isset($_POST['is_correct']) ? 1 : 0;

    if (empty($answer_text)) {
        $errors['answer_text'] = 'Answer text is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO answers (question_id, answer_text, is_correct)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$question_id, $answer_text, $is_correct]);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Answer created successfully!'
            ];

            header("Location: view_answers.php?question_id=$question_id");
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
}

?>

<?php include '../../includes/header.php'; ?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Create New Answer</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view_answers.php?question_id=<?php echo $question_id; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Answers
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php unset($_SESSION['message']); endif; ?>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Question: <?php echo htmlspecialchars($question['question_text']); ?></h5>
                    <form method="post" action="create_answer.php?question_id=<?php echo $question_id; ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="answer_text" class="form-label">Answer Text *</label>
                                    <textarea class="form-control <?php echo isset($errors['answer_text']) ? 'is-invalid' : ''; ?>"
                                              id="answer_text" name="answer_text" rows="3" required><?php
                                              echo htmlspecialchars($_POST['answer_text'] ?? '');
                                              ?></textarea>
                                    <?php if (isset($errors['answer_text'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['answer_text']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="is_correct" name="is_correct"
                                           <?php echo isset($_POST['is_correct']) && $_POST['is_correct'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_correct">Mark as Correct Answer</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">Create Answer</button>
                                    <a href="view_answers.php?question_id=<?php echo $question_id; ?>" class="btn btn-secondary">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
