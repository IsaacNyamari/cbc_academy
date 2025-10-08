<?php
require_once '../../includes/config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    redirect('../student/dashboard.php');
}

// Get topic ID from URL
$topic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Get topic details with chapter and subject info
    $stmt = $pdo->prepare("
        SELECT t.*, c.title AS chapter_title, c.id AS chapter_id, 
               s.name AS subject_title, s.id AS subject_id
        FROM topics t
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        WHERE t.id = ?
    ");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch();

    if (!$topic) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Topic not found!'
        ];
        redirect('index.php');
    }

    // Verify teacher ownership if user is a teacher
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND created_by = ?");
        $stmt->execute([$topic['subject_id'], $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'You are not authorized to view this topic!'
            ];
            redirect('index.php');
        }
    }

    // Get student progress statistics for this topic
    $progress_stmt = $pdo->prepare("
        SELECT 
            COUNT(sp.id) AS total_students,
            SUM(CASE WHEN sp.completion_status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN sp.completion_status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count,
            SUM(CASE WHEN sp.completion_status = 'not_started' OR sp.completion_status IS NULL THEN 1 ELSE 0 END) AS not_started_count
        FROM student_subjects ss
        LEFT JOIN student_progress sp ON ss.student_id = sp.student_id AND sp.topic_id = ?
        WHERE ss.subject_id = ?
    ");
    $progress_stmt->execute([$topic_id, $topic['subject_id']]);
    $progress = $progress_stmt->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

?>

<?php include '../../includes/header.php'; ?>


<?php include '../../includes/sidebar.php'; ?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Topic: <?php echo htmlspecialchars($topic['title']); ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTopicModal">
                    <i class="fas fa-edit"></i> Edit Topic
                </button>
                <a href="delete.php?topic_id=<?php echo $topic_id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this topic?');">
                    <i class="fas fa-trash-alt"></i> Delete
                </a>
            </div>
            <a href="../chapters/view.php?id=<?php echo $topic['chapter_id']; ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Chapter
            </a>
        </div>
    </div>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="../subjects/index.php">Subjects</a></li>
            <li class="breadcrumb-item"><a href="../subjects/view.php?id=<?php echo $topic['subject_id']; ?>"><?php echo htmlspecialchars($topic['subject_title']); ?></a></li>
            <li class="breadcrumb-item"><a href="../chapters/view.php?id=<?php echo $topic['chapter_id']; ?>"><?php echo htmlspecialchars($topic['chapter_title']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($topic['title']); ?></li>
        </ol>
    </nav>

    <!-- Topic Details -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Topic Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Title</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($topic['title']); ?></dd>

                        <dt class="col-sm-3">Subject</dt>
                        <dd class="col-sm-9">
                            <a href="../subjects/view.php?id=<?php echo $topic['subject_id']; ?>">
                                <?php echo htmlspecialchars($topic['subject_title']); ?>
                            </a>
                        </dd>

                        <dt class="col-sm-3">Chapter</dt>
                        <dd class="col-sm-9">
                            <a href="../chapters/view.php?id=<?php echo $topic['chapter_id']; ?>">
                                <?php echo htmlspecialchars($topic['chapter_title']); ?>
                            </a>
                        </dd>

                        <dt class="col-sm-3">Sequence</dt>
                        <dd class="col-sm-9"><?php echo $topic['sequence']; ?></dd>

                        <?php if (!empty($topic['video_url'])): ?>

                            <br>
                            <iframe width="300" height="250" src="<?php echo $topic['video_url'] ?>" frameborder="0" allowfullscreen></iframe>

                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Student Progress</h5>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 20px;">
                        <?php
                        $completion_percentage = $progress['total_students'] > 0
                            ? round(($progress['completed_count'] / $progress['total_students']) * 100, 1)
                            : 0;
                        ?>
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_percentage; ?>%"
                            aria-valuenow="<?php echo $completion_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo $completion_percentage; ?>%
                        </div>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Completed
                            <span class="badge bg-success rounded-pill"><?php echo $progress['completed_count']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            In Progress
                            <span class="badge bg-warning rounded-pill"><?php echo $progress['in_progress_count']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Not Started
                            <span class="badge bg-secondary rounded-pill"><?php echo $progress['not_started_count']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Students
                            <span class="badge bg-primary rounded-pill"><?php echo $progress['total_students']; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Topic Content -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Content</h5>
        </div>
        <div class="card-body">
            <div class="topic-content">
                <?php echo nl2br(($topic['content'])); ?>
            </div>
        </div>
    </div>
</main>
</div>
</div>
<!-- edit topic modal -->
<div class="modal fade" id="editTopicModal" tabindex="-1" role="dialog" aria-labelledby="editTopicModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTopicModalLabel">Edit Topic</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editTopicForm" action="edit_topic.php" method="POST">
                    <input type="hidden" name="edit_topic_id" value="<?php echo $topic['id']; ?>">
                    <div class="mb-3">
                        <label for="topicTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="topicTitle" name="title" value="<?php echo htmlspecialchars($topic['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="topicSequence" class="form-label">Sequence</label>
                        <input type="text" class="form-control" id="topicSequence" name="sequence" value="<?php echo htmlspecialchars($topic['sequence']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="topicContent" class="form-label">Content</label>
                        <textarea class="form-control" id="topicContent" name="content" rows="5" required><?php echo htmlspecialchars($topic['content']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="topicVideoUrl" class="form-label">Video URL</label>
                        <input type="text" class="form-control" id="topicVideoUrl" name="video_url" value="<?php echo htmlspecialchars($topic['video_url']); ?>">
                    </div>
                    <button type="submit" name="edit_topic" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>