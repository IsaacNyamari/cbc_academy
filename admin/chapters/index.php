<?php
require_once '../../includes/config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    redirect('../student/dashboard.php');
}

try {
    // Get all chapters with their subject information
    $query = "
        SELECT c.*, s.name AS subject_title, s.id AS subject_id 
        FROM chapters c
        JOIN subjects s ON c.subject_id = s.id
    ";
    
    // If teacher, only show chapters from subjects they created
    if ($_SESSION['role'] === 'teacher') {
        $query .= " WHERE s.created_by = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->query($query);
    }
    
    $chapters = $stmt->fetchAll();

    // Get topics for all chapters at once (more efficient than querying per chapter)
    $chapterIds = array_column($chapters, 'id');
    $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
    
    $stmt = $pdo->prepare("
        SELECT * FROM topics 
        WHERE chapter_id IN ($placeholders)
        ORDER BY chapter_id, sequence ASC
    ");
    $stmt->execute($chapterIds);
    $allTopics = $stmt->fetchAll();
    
    // Organize topics by chapter_id
    $topicsByChapter = [];
    foreach ($allTopics as $topic) {
        $topicsByChapter[$topic['chapter_id']][] = $topic;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<?php include '../../includes/header.php'; ?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">All Chapters</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subjects
                    </a>
                </div>
            </div>

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="../subjects/index.php">Subjects</a></li>
                    <li class="breadcrumb-item active" aria-current="page">All Chapters</li>
                </ol>
            </nav>

            <?php if (empty($chapters)): ?>
                <div class="alert alert-info">No chapters found.</div>
            <?php else: ?>
                <!-- Chapters Accordion -->
                <div class="accordion" id="chaptersAccordion">
                    <?php foreach ($chapters as $chapter): 
                        $chapterTopics = $topicsByChapter[$chapter['id']] ?? [];
                        $topicCount = count($chapterTopics);
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $chapter['id']; ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#collapse<?php echo $chapter['id']; ?>" 
                                aria-expanded="false" aria-controls="collapse<?php echo $chapter['id']; ?>">
                                <div class="d-flex justify-content-between w-100 pe-3">
                                    <span>
                                        <?php echo htmlspecialchars($chapter['title']); ?> 
                                        <span class="badge bg-secondary ms-2"><?php echo $topicCount; ?> topics</span>
                                    </span>
                                    <span class="text-muted">
                                        <?php echo htmlspecialchars($chapter['subject_title']); ?>
                                    </span>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $chapter['id']; ?>" class="accordion-collapse collapse" 
                            aria-labelledby="heading<?php echo $chapter['id']; ?>" 
                            data-bs-parent="#chaptersAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-8">
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
                                    <div class="col-md-4">
                                        <div class="d-flex justify-content-end mb-3">
                                            <a href="view.php?id=<?php echo $chapter['id']; ?>" class="btn btn-sm btn-primary me-2">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                        </div>
                                        <div class="progress mb-3" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                style="width: <?php echo min(($topicCount * 10), 100); ?>%;" 
                                                aria-valuenow="<?php echo min(($topicCount * 10), 100); ?>" 
                                                aria-valuemin="0" aria-valuemax="100">
                                                <?php echo min(($topicCount * 10), 100); ?>%
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Topics List -->
                                <div class="mt-4">
                                    <h5>Topics (<?php echo $topicCount; ?>)</h5>
                                    <?php if (empty($chapterTopics)): ?>
                                        <div class="alert alert-info mb-0">No topics found for this chapter.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Title</th>
                                                        <th>Description</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($chapterTopics as $topic): ?>
                                                        <tr>
                                                            <td><?php echo $topic['sequence']; ?></td>
                                                            <td>
                                                                <a href="../topics/view.php?id=<?php echo $topic['id']; ?>">
                                                                    <?php echo htmlspecialchars($topic['title']); ?>
                                                                </a>
                                                            </td>
                                                            <td><?php echo !empty($topic['description']) ? htmlspecialchars(substr($topic['description'], 0, 50) . '...') : 'N/A'; ?></td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm" role="group">
                                                                    <a href="../topics/view.php?id=<?php echo $topic['id']; ?>" class="btn btn-info" title="View">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="../topics/edit.php?id=<?php echo $topic['id']; ?>" class="btn btn-primary" title="Edit">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <a href="../topics/delete.php?id=<?php echo $topic['id']; ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this topic?');">
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
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>