<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location:../login.php');
    exit;
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    header('Location:../student/dashboard.php');
    exit;
}

// Fetch all available questions
try {
    $stmt = $pdo->query("SELECT id, title FROM quizzes ORDER BY id DESC");
    $questions = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_id = (int)($_POST['question_id'] ?? 0);
    $answer_text = trim($_POST['answer_text'] ?? '');
    $is_correct = isset($_POST['is_correct']) ? 1 : 0;
    $errors = [];

    if ($question_id <= 0) $errors['question_id'] = 'Please select a question';
    if (empty($answer_text)) $errors['answer_text'] = 'Answer text is required';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO answers (question_id, answer_text, is_correct)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$question_id, $answer_text, $is_correct]);

            $_SESSION['message'] = ['type' => 'success', 'text' => 'Answer created successfully!'];
            header('Location: index.php');
            exit;
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
        <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Answers
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="">
            <div class="mb-3">
                <label for="question_id" class="form-label">Select Question *</label>
                <select id="question_id" name="question_id" class="form-select <?php echo isset($errors['question_id']) ? 'is-invalid' : ''; ?>">
                    <option value="">-- Choose Question --</option>
                    <?php foreach ($questions as $question): ?>
                        <option value="<?php echo $question['id']; ?>" 
                            <?php echo (isset($_POST['question_id']) && $_POST['question_id'] == $question['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($question['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['question_id'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['question_id']; ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="answer_text" class="form-label">Answer Text *</label>
                <textarea id="answer_text" name="answer_text" rows="3"
                          class="form-control <?php echo isset($errors['answer_text']) ? 'is-invalid' : ''; ?>"><?php
                    echo htmlspecialchars($_POST['answer_text'] ?? '');
                ?></textarea>
                <?php if (isset($errors['answer_text'])): ?>
                    <div class="invalid-feedback"><?php echo $errors['answer_text']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="is_correct" name="is_correct"
                    <?php echo isset($_POST['is_correct']) ? 'checked' : ''; ?>>
                <label for="is_correct" class="form-check-label">Mark as Correct Answer</label>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">Save Answer</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</main>
</div>
</div>

<?php include '../../includes/footer.php'; ?>
