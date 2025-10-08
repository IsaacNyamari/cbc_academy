<?php
require_once '../../includes/config.php';

// Get subject ID and quiz ID from URL
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate subject exists and belongs to the teacher (if teacher)
try {
    $stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();

    if (!$subject) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Subject not found!'
        ];
        header('Location:index.php');
        exit;
    }

    // If teacher, verify they created this subject
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND created_by = ?");
        $stmt->execute([$subject_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'You are not authorized to edit quizzes in this subject!'
            ];
            header('Location:index.php');
            exit;
        }
    }

    // Fetch quiz
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Quiz not found!'
        ];
        header("Location:index.php?subject_id=$subject_id");
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Collect and sanitize inputs
    $title = trim($_POST['title'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $points = (int)($_POST['points'] ?? 0);
    $passing_score = (int)($_POST['passing_score'] ?? 0);
    $question_type = trim($_POST['question_type'] ?? '');

    // Validate inputs
    if (empty($title)) {
        $errors['title'] = 'Quiz title is required';
    } elseif (strlen($title) > 255) {
        $errors['title'] = 'Quiz title cannot exceed 255 characters';
    }

    if ($points <= 0) {
        $errors['points'] = 'Points must be a positive integer';
    }

    if ($passing_score <= 0) {
        $errors['passing_score'] = 'Passing score must be a positive integer';
    }

    if (empty($question_type)) {
        $errors['question_type'] = 'Question type is required';
    }

    // Check if quiz title already exists for this subject (excluding current quiz)
    try {
        $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE title = ? AND  id != ?");
        $stmt->execute([$title, $quiz_id]);
        if ($stmt->fetch()) {
            $errors['title'] = 'A quiz with this title already exists in this subject';
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update the quiz with all 5 columns
            $stmt = $pdo->prepare("
                UPDATE quizzes
                SET title = ?, instructions = ?, points = ?, passing_score = ?, question_type = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $title,
                $instructions,
                $points,
                $passing_score,
                $question_type,
                $quiz_id
            ]);

            $pdo->commit();

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Quiz updated successfully!'
            ];

            header("Location:view.php?id=$quiz_id");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Database error: " . $e->getMessage());
        }
    }
}

?>

<?php include '../../includes/header.php'; ?>

 </div>
    <?php include '../../includes/sidebar.php'; ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Edit Quiz</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="index.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Quizzes
                </a>
            </div>
        </div>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="../subjects/index.php">Subjects</a></li>
                <li class="breadcrumb-item"><a href="../subjects/view.php?id=<?php echo $subject_id; ?>"><?php echo htmlspecialchars($subject['name']); ?></a></li>
                <li class="breadcrumb-item"><a href="index.php?subject_id=<?php echo $subject_id; ?>">Quizzes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>

        <!-- Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Quiz Details</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['message']['text']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php unset($_SESSION['message']);
                endif; ?>

                <form method="post" action="edit.php?subject_id=<?php echo $subject_id; ?>&id=<?php echo $quiz_id; ?>">
                    <!-- Title -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Quiz Title *</label>
                        <input type="text"
                            class="form-control <?php echo isset($quiz['title_update']) ? 'is-invalid' : ''; ?>"
                            id="title" name="title"
                            value="<?php echo htmlspecialchars($quiz['title'] ?? ''); ?>" required>
                        <?php if (isset($quiz['title_update'])): ?>
                            <div class="invalid-feedback"><?php echo $quiz['title_update']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Instructions -->
                    <div class="mb-3">
                        <label for="instructions" class="form-label">Instructions</label>
                        <textarea class="form-control" id="instructions" name="instructions" rows="3"><?php
                                                                                                        echo htmlspecialchars(trim($quiz['instructions'] ?? ''));
                                                                                                        ?></textarea>
                    </div>

                    <!-- Points -->
                    <div class="mb-3">
                        <label for="points" class="form-label">Points *</label>
                        <input type="number"
                            class="form-control <?php echo isset($quiz['points_edit']) ? 'is-invalid' : ''; ?>"
                            id="points" name="points" min="1"
                            value="<?php echo htmlspecialchars($quiz['points'] ?? ''); ?>" required>
                        <?php if (isset($quiz['points_edit'])): ?>
                            <div class="invalid-feedback"><?php echo $quiz['points_edit']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Passing Score -->
                    <div class="mb-3">
                        <label for="passing_score" class="form-label">Passing Score *</label>
                        <input type="number"
                            class="form-control <?php echo isset($quiz['passing_score_edit']) ? 'is-invalid' : ''; ?>"
                            id="passing_score" name="passing_score" min="1"
                            value="<?php echo htmlspecialchars($quiz['passing_score'] ?? ''); ?>" required>
                        <?php if (isset($quiz['passing_score_edit'])): ?>
                            <div class="invalid-feedback"><?php echo $quiz['passing_score_edit']; ?></div>
                        <?php endif; ?>
                        <small class="text-muted">Determines the passing score</small>
                    </div>

                    <!-- Question Type -->
                    <div class="mb-3">
                        <label for="question_type" class="form-label">Question Type *</label>
                        <select class="form-select" id="question_type" name="question_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="multiple_choice" <?php echo ($quiz['question_type'] ?? '') === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                            <option value="true_false" <?php echo ($quiz['question_type'] ?? '') === 'true_false' ? 'selected' : ''; ?>>True/False</option>
                            <option value="short_answer" <?php echo ($quiz['question_type'] ?? '') === 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">Update Quiz</button>
                        <a href="index.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </main>
</div>
</div>

<?php include '../../includes/footer.php'; ?>