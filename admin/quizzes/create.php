<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('../login.php');
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    header('../student/dashboard.php');
}

// Get available topics for the teacher/admin
try {
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("
            SELECT t.id, t.title, c.title AS chapter_title, s.name AS subject_title
            FROM topics t
            JOIN chapters c ON t.chapter_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            WHERE s.created_by = ?
            ORDER BY s.name, c.title, t.title
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("
            SELECT t.id, t.title, c.title AS chapter_title, s.name AS subject_title
            FROM topics t
            JOIN chapters c ON t.chapter_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            ORDER BY s.name, c.title, t.title
        ");
        $stmt->execute();
    }
    $topics = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Validate inputs
    $title = trim($_POST['title'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $topic_id = (int)($_POST['topic_id'] ?? 0);
    $passing_score = (int)($_POST['passing_score'] ?? 0);
    $time_limit = (int)($_POST['time_limit'] ?? 0); // Will be stored elsewhere if needed
    $is_published = isset($_POST['is_published']) ? 1 : 0; // Will be stored elsewhere if needed

    if (empty($title)) {
        $errors['title'] = 'Quiz title is required';
    } elseif (strlen($title) > 255) {
        $errors['title'] = 'Quiz title cannot exceed 255 characters';
    }

    if (strlen($instructions) > 1000) {
        $errors['instructions'] = 'Instructions cannot exceed 1000 characters';
    }

    if ($topic_id <= 0) {
        $errors['topic_id'] = 'Please select a valid topic';
    }

    if ($passing_score <= 0 || $passing_score > 100) {
        $errors['passing_score'] = 'Passing score must be between 1 and 100';
    }

    // Check if quiz title already exists for this topic
    try {
        $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE title = ? AND topic_id = ?");
        $stmt->execute([$title, $topic_id]);
        if ($stmt->fetch()) {
            $errors['title'] = 'A quiz with this title already exists for the selected topic';
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert the new quiz with the available columns
            $stmt = $pdo->prepare("
                INSERT INTO quizzes (topic_id, title, instructions, passing_score, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $topic_id,
                $title,
                $instructions,
                $passing_score
            ]);

            $quiz_id = $pdo->lastInsertId();

            // If you need to store time_limit or is_published, you would:
            // 1. Either add these columns to your quizzes table
            // 2. Or store them in a separate quiz_settings table
            // For now, they're validated but not stored

            $pdo->commit();

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Quiz created successfully! You can now add questions.'
            ];

            header("Location: ./");

        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Database error: " . $e->getMessage());
        }
    }
}
?>

<?php include '../../includes/header.php'; ?>
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Create New Quiz</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Quizzes
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
                    <form method="post" action="create.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Quiz Title *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                                           id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                    <?php if (isset($errors['title'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['title']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="topic_id" class="form-label">Topic *</label>
                                    <select class="form-select <?php echo isset($errors['topic_id']) ? 'is-invalid' : ''; ?>" 
                                            id="topic_id" name="topic_id" required>
                                        <option value="">Select a topic</option>
                                        <?php foreach ($topics as $topic): ?>
                                        <option value="<?php echo $topic['id']; ?>" 
                                            <?php echo ($_POST['topic_id'] ?? '') == $topic['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($topic['subject_title'] . ' > ' . $topic['chapter_title'] . ' > ' . $topic['title']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['topic_id'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['topic_id']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="instructions" class="form-label">Instructions</label>
                                    <textarea class="form-control" id="instructions" name="instructions" rows="3"><?php 
                                        echo htmlspecialchars($_POST['instructions'] ?? ''); 
                                    ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="passing_score" class="form-label">Passing Score (%) *</label>
                                    <input type="number" class="form-control <?php echo isset($errors['passing_score']) ? 'is-invalid' : ''; ?>" 
                                           id="passing_score" name="passing_score" min="1" max="100" 
                                           value="<?php echo htmlspecialchars($_POST['passing_score'] ?? '70'); ?>" required>
                                    <?php if (isset($errors['passing_score'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['passing_score']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="time_limit" class="form-label">Time Limit (minutes)</label>
                                    <input type="number" class="form-control <?php echo isset($errors['time_limit']) ? 'is-invalid' : ''; ?>" 
                                           id="time_limit" name="time_limit" min="0" 
                                           value="<?php echo htmlspecialchars($_POST['time_limit'] ?? '30'); ?>">
                                    <?php if (isset($errors['time_limit'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['time_limit']; ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted">Set to 0 for no time limit</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_published" name="is_published" 
                                           <?php echo isset($_POST['is_published']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_published">Publish immediately</label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">Create Quiz</button>
                                    <a href="index.php" class="btn btn-secondary">Cancel</a>
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