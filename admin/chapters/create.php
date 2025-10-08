<?php
require_once '../../includes/config.php';


// Get subject ID from URL
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

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
    }

    // If teacher, verify they created this subject
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND created_by = ?");
        $stmt->execute([$subject_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'You are not authorized to add chapters to this subject!'
            ];
            header('Location:index.php');
        }
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Validate inputs
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sequence = (int)($_POST['sequence'] ?? 0);

    if (empty($title)) {
        $errors['title'] = 'Chapter title is required';
    } elseif (strlen($title) > 255) {
        $errors['title'] = 'Chapter title cannot exceed 255 characters';
    }

    if (empty($sequence) || $sequence <= 0) {
        $errors['sequence'] = 'Sequence must be a positive integer';
    }

    // Check if chapter title already exists for this subject
    try {
        $stmt = $pdo->prepare("SELECT id FROM chapters WHERE title = ? AND subject_id = ?");
        $stmt->execute([$title, $subject_id]);
        if ($stmt->fetch()) {
            $errors['title'] = 'A chapter with this title already exists in this subject';
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert the new chapter
            $stmt = $pdo->prepare("
                INSERT INTO chapters (subject_id, title, description, sequence, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $subject_id,
                $title,
                $description,
                $sequence
            ]);

            $chapter_id = $pdo->lastInsertId();

            $pdo->commit();

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Chapter created successfully!'
            ];

            header("Location:view.php?id=$chapter_id");

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
                <h1 class="h2">Create New Chapter</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Chapters
                    </a>
                </div>
            </div>

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="../subjects/index.php">Subjects</a></li>
                    <li class="breadcrumb-item"><a href="../subjects/view.php?id=<?php echo $subject_id; ?>"><?php echo htmlspecialchars($subject['name']); ?></a></li>
                    <li class="breadcrumb-item"><a href="index.php?subject_id=<?php echo $subject_id; ?>">Chapters</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>

            <!-- Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Chapter Details</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['message']['text']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message']); endif; ?>

                    <form method="post" action="create.php?subject_id=<?php echo $subject_id; ?>">
                        <div class="mb-3">
                            <label for="title" class="form-label">Chapter Title *</label>
                            <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                                   id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['title']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                echo htmlspecialchars($_POST['description'] ?? ''); 
                            ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="sequence" class="form-label">Sequence Number *</label>
                            <input type="number" class="form-control <?php echo isset($errors['sequence']) ? 'is-invalid' : ''; ?>" 
                                   id="sequence" name="sequence" min="1" 
                                   value="<?php echo htmlspecialchars($_POST['sequence'] ?? ''); ?>" required>
                            <?php if (isset($errors['sequence'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['sequence']; ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Determines the order of chapters in the subject</small>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Create Chapter</button>
                            <a href="index.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>