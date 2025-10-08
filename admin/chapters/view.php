<?php
require_once '../../includes/config.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    header('Location: ../student/dashboard.php');
}

// Get chapter ID from URL
$chapter_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Get chapter details
    $stmt = $pdo->prepare("
        SELECT c.*, s.name AS subject_title, s.id AS subject_id 
        FROM chapters c
        JOIN subjects s ON c.subject_id = s.id
        WHERE c.id = ?
    ");
    $stmt->execute([$chapter_id]);
    $chapter = $stmt->fetch();

    if (!$chapter) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Chapter not found!'
        ];
        header('Location: ../dashboard.php');
    }

    // Verify teacher ownership if user is a teacher
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND created_by = ?");
        $stmt->execute([$chapter['subject_id'], $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'You are not authorized to view this chapter!'
            ];
            header('Location: ../dashboard.php');
        }
    }

    // Get topics for this chapter
    $stmt = $pdo->prepare("
        SELECT * FROM topics 
        WHERE chapter_id = ? 
        ORDER BY sequence ASC
    ");
    $stmt->execute([$chapter_id]);
    $topics = $stmt->fetchAll();

    // Get topic counts
    $topic_count = count($topics);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<?php include '../../includes/header.php'; ?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Chapter: <?php echo htmlspecialchars($chapter['title']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a data-id="<?php echo $chapter_id; ?>" href="edit.php?id=<?php echo $chapter_id; ?>&subject_id=<?php echo $chapter['subject_id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit Chapter
                        </a>
                        <a href="../topics/create.php?chapter_id=<?php echo $chapter_id; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Add Topic
                        </a>
                    </div>
                    <a href="index.php?subject_id=<?php echo $chapter['subject_id']; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Chapters
                    </a>
                </div>
            </div>

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="../subjects/index.php">Subjects</a></li>
                    <li class="breadcrumb-item"><a href="../subjects/view.php?id=<?php echo $chapter['subject_id']; ?>"><?php echo htmlspecialchars($chapter['subject_title']); ?></a></li>
                    <li class="breadcrumb-item"><a href="index.php?subject_id=<?php echo $chapter['subject_id']; ?>">Chapters</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($chapter['title']); ?></li>
                </ol>
            </nav>

            <!-- Chapter Details -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Chapter Information</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">Title</dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($chapter['title']); ?></dd>

                                <dt class="col-sm-3">Subject</dt>
                                <dd class="col-sm-9">
                                    <a href="../subjects/view.php?id=<?php echo $chapter['subject_id']; ?>">
                                        <?php echo htmlspecialchars($chapter['subject_title']); ?>
                                    </a>
                                </dd>

                                <dt class="col-sm-3">Sequence</dt>
                                <dd class="col-sm-9"><?php echo $chapter['sequence']; ?></dd>

                                <?php if (!empty($chapter['description'])): ?>
                                    <dt class="col-sm-3">Description</dt>
                                    <dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($chapter['description'])); ?></dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Chapter Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Topics</span>
                                <span class="badge bg-primary rounded-pill"><?php echo $topic_count; ?></span>
                            </div>
                            <div class="progress mb-3" style="height: 20px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">25%</div>
                            </div>
                            <small class="text-muted">Overall completion (sample data)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Topics List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Topics</h5>
                    <span class="badge bg-primary"><?php echo $topic_count; ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($topics)): ?>
                        <div class="alert alert-info mb-0">No topics found for this chapter.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Sequence</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topics as $topic): ?>
                                        <tr>
                                            <td><?php echo $topic['sequence']; ?></td>
                                            <td>
                                                <a href="../topics/view.php?id=<?php echo $topic['id']; ?>">
                                                    <?php echo htmlspecialchars($topic['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo !empty($topic['description']) ? htmlspecialchars(substr($topic['description'], 0, 50) . '...') : 'N/A'; ?></td>
                                            <td><?php echo $topic['sequence']; ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="../topics/view.php?id=<?php echo $topic['id']; ?>" class="btn btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="../topics/edit.php?id=<?php echo $topic['id']; ?>" class="btn btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="../topics/delete.php?topic_id=<?php echo $topic['id']; ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this topic?');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
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

<!-- Modal Body -->
<!-- if you want to close by clicking outside the modal, delete the last endpoint:data-bs-backdrop and data-bs-keyboard -->
<div
    class="modal fade"
    id="editChapterModal"
    tabindex="-1"
    data-bs-backdrop="static"
    data-bs-keyboard="false"

    role="dialog"
    aria-labelledby="modalTitleId"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-sm"
        role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitleId">
                    Modal title
                </h5>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">Body</div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    Close
                </button>
                <button type="button" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>


<?php include '../../includes/footer.php'; ?>