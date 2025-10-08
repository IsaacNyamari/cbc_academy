<?php
require_once '../../includes/config.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    header('Location: ../student/dashboard.php');
}

// Handle topic edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_topic'])) {
    $topic_id = (int)$_POST['edit_topic_id'];
    $title = trim($_POST['title']);
    $sequence = trim($_POST['sequence']);
    $content = trim($_POST['content']);
    $video_url = trim($_POST['video_url']);

    // Validate required fields
    if ($title === '' || $sequence === '' || $content === '') {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Please fill in all required fields.'
        ];
        header("Location: edit.php?id=$topic_id");
    }

    // Get topic details for authorization
    $stmt = $pdo->prepare("
        SELECT t.*, c.subject_id
        FROM topics t
        JOIN chapters c ON t.chapter_id = c.id
        WHERE t.id = ?
    ");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch();

    if (!$topic) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Topic not found!'
        ];
        header('Location: ./');
    }

    // Verify teacher ownership if user is a teacher
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND created_by = ?");
        $stmt->execute([$topic['subject_id'], $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'You are not authorized to edit this topic!'
            ];
            header('Location: ./');
        }
    }

    // Update topic
    $update_stmt = $pdo->prepare("
        UPDATE topics
        SET title = ?, sequence = ?, content = ?, video_url = ?
        WHERE id = ?
    ");
    $update_stmt->execute([$title, $sequence, $content, $video_url, $topic_id]);

    $_SESSION['message'] = [
        'type' => 'success',
        'text' => 'Topic updated successfully!'
    ];
    header("Location: edit.php?id=$topic_id");
} else {
    // Get topic details for form
    $topic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $stmt = $pdo->prepare("
        SELECT t.*, c.subject_id
        FROM topics t
        JOIN chapters c ON t.chapter_id = c.id
        WHERE t.id = ?
    ");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch();

    if (!$topic) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Topic not found!'
        ];
        header('Location: ./');
    }

    // Authorization check for teachers
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND created_by = ?");
        $stmt->execute([$topic['subject_id'], $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'You are not authorized to edit this topic!'
            ];
            header('Location: ./');
        }
    }

    // Display the edit form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit Topic</title>
        <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    </head>
    <body>
    <div class="container mt-5">
        <h2>Edit Topic</h2>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message']['type']; ?>">
                <?php echo $_SESSION['message']['text']; ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <form method="post" action="edit.php?id=<?php echo $topic_id; ?>">
            <input type="hidden" name="edit_topic_id" value="<?php echo $topic_id; ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($topic['title']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="sequence" class="form-label">Sequence <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="sequence" name="sequence" value="<?php echo htmlspecialchars($topic['sequence']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                <textarea class="form-control" id="content" name="content" rows="5" required><?php echo htmlspecialchars($topic['content']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="video_url" class="form-label">Video URL</label>
                <input type="url" class="form-control" id="video_url" name="video_url" value="<?php echo htmlspecialchars($topic['video_url']); ?>">
            </div>
            <button type="submit" name="edit_topic" class="btn btn-primary">Update Topic</button>
            <a href="./" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    </body>
    </html>
    <?php
}
