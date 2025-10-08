<?php
require_once '../../includes/config.php';
include '../../includes/header.php';

$subject_id = (int)$_GET['id'];
setcookie("subject_id",$subject_id);
try {
    // Check if student is enrolled in this subject
    $stmt = $pdo->prepare("
        SELECT 1 FROM student_subjects 
        WHERE student_id = ? AND subject_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $subject_id]);

    if (!$stmt->fetch()) {
        $_SESSION['error'] = "You are not enrolled in this subject";
        header('Location: ../dashboard.php?status=error&message=You are not enrolled in this subject');
        exit;
    }

    // Get subject details
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();

    if (!$subject) {
        header('Location: ../dashboard.php?status=error&message=You are not enrolled in this subject');
        exit;
    }



    // Rest of the view.php code remains the same...
    // [Keep all the existing code for displaying chapters and topics]

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
try {
    // Get subject details
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();

    if (!$subject) {
        header('Location: ../dashboard.php?status=error&message=Subject ID is required');
    }

    // Get chapters with topics and progress
    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.description, c.sequence,
               t.id AS topic_id, t.title AS topic_title, t.sequence AS topic_sequence,
               sp.completion_status, sp.last_accessed
        FROM chapters c
        LEFT JOIN topics t ON c.id = t.chapter_id
        LEFT JOIN student_progress sp ON t.id = sp.topic_id AND sp.student_id = ?
        WHERE c.subject_id = ?
        ORDER BY c.sequence, t.sequence
    ");
    $stmt->execute([$_SESSION['user_id'], $subject_id]);
    $chapters = $stmt->fetchAll();

    // Organize data by chapter
    $organized_chapters = [];
    foreach ($chapters as $row) {
        $chapter_id = $row['id'];
        if (!isset($organized_chapters[$chapter_id])) {
            $organized_chapters[$chapter_id] = [
                'title' => $row['title'],
                'description' => $row['description'],
                'sequence' => $row['sequence'],
                'topics' => []
            ];
        }

        if ($row['topic_id']) {
            $organized_chapters[$chapter_id]['topics'][] = [
                'id' => $row['topic_id'],
                'title' => $row['topic_title'],
                'sequence' => $row['topic_sequence'],
                'completion_status' => $row['completion_status'] ?? 'not_started',
                'last_accessed' => $row['last_accessed']
            ];
        }
    }

    // Calculate overall progress
    $total_topics = 0;
    $completed_topics = 0;

    foreach ($organized_chapters as $chapter) {
        foreach ($chapter['topics'] as $topic) {
            $total_topics++;
            if ($topic['completion_status'] === 'completed') {
                $completed_topics++;
            }
        }
    }

    $progress_percentage = $total_topics > 0 ? round(($completed_topics / $total_topics) * 100) : 0;
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

 
        <?php include '../../includes/sidebar.php'; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo htmlspecialchars($subject['name']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subjects
                    </a>
                </div>
            </div>

            <!-- Subject Progress -->
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">Your Progress</h4>
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                            role="progressbar" style="width: <?php echo $progress_percentage; ?>%"
                            aria-valuenow="<?php echo $progress_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo $progress_percentage; ?>% Complete
                        </div>
                    </div>
                    <p class="card-text">
                        You've completed <?php echo $completed_topics; ?> out of <?php echo $total_topics; ?> topics in this subject.
                    </p>
                </div>
            </div>

            <!-- Chapters Accordion -->
            <div class="accordion" id="chaptersAccordion">
                <?php foreach ($organized_chapters as $chapter_id => $chapter): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $chapter_id; ?>">
                            <button class="accordion-button <?php echo $chapter_id !== array_key_first($organized_chapters) ? 'collapsed' : ''; ?>"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse<?php echo $chapter_id; ?>"
                                aria-expanded="<?php echo $chapter_id === array_key_first($organized_chapters) ? 'true' : 'false'; ?>"
                                aria-controls="collapse<?php echo $chapter_id; ?>">
                                Chapter <?php echo $chapter['sequence']; ?>: <?php echo htmlspecialchars($chapter['title']); ?>
                                <span class="badge bg-primary rounded-pill ms-2">
                                    <?php echo count($chapter['topics']); ?> Topics
                                </span>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $chapter_id; ?>"
                            class="accordion-collapse collapse <?php echo $chapter_id === array_key_first($organized_chapters) ? 'show' : ''; ?>"
                            aria-labelledby="heading<?php echo $chapter_id; ?>"
                            data-bs-parent="#chaptersAccordion">
                            <div class="accordion-body">
                                <p><?php echo htmlspecialchars($chapter['description']); ?></p>

                                <div class="list-group">
                                    <?php foreach ($chapter['topics'] as $topic):
                                        $status_class = '';
                                        if ($topic['completion_status'] === 'completed') {
                                            $status_class = 'list-group-item-success';
                                        } elseif ($topic['completion_status'] === 'in_progress') {
                                            $status_class = 'list-group-item-warning';
                                        }
                                    ?>
                                        <a href="topic.php?id=<?php echo $topic['id']; ?>"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $status_class; ?>">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($topic['title']); ?></h6>
                                                <?php if ($topic['last_accessed']): ?>
                                                    <small class="text-muted">Last accessed: <?php echo date('M j, Y', strtotime($topic['last_accessed'])); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-light text-dark">
                                                <?php
                                                switch ($topic['completion_status']) {
                                                    case 'completed':
                                                        echo '<i class="fas fa-check-circle text-success"></i> Completed';
                                                        break;
                                                    case 'in_progress':
                                                        echo '<i class="fas fa-spinner text-warning"></i> In Progress';
                                                        break;
                                                    default:
                                                        echo '<i class="fas fa-book"></i> Not Started';
                                                        break;
                                                }
                                                ?>
                                            </span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>