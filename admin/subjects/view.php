<?php
require_once '../../includes/config.php';



if (!isset($_GET['id'])) {
    header('Location: ../dashboard.php?status=error&message=Subject ID is required');
}

$subject_id = (int)$_GET['id'];

try {
    // Get subject details
    $stmt = $pdo->prepare("
        SELECT s.*, u.username AS created_by_username, u.full_name AS created_by_name
        FROM subjects s
        LEFT JOIN users u ON s.created_by = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();
    if (!$subject) {
        header('Location: ../dashboard.php?status=error&message=Subject ID is required');
        exit;
    }

    // Get chapters with topic counts
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(t.id) AS topic_count
        FROM chapters c
        LEFT JOIN topics t ON c.id = t.chapter_id
        WHERE c.subject_id = ?
        GROUP BY c.id
        ORDER BY c.sequence
    ");
    $stmt->execute([$subject_id]);
    $chapters = $stmt->fetchAll();

    // Get student enrollment count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS student_count
        FROM student_subjects
        WHERE subject_id = ?
    ");
    $stmt->execute([$subject_id]);
    $enrollment = $stmt->fetch();

    // Get completion statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT t.id) AS total_topics,
            COUNT(DISTINCT sp.topic_id) AS completed_topics,
            COUNT(DISTINCT ss.student_id) AS enrolled_students,
            COUNT(DISTINCT CASE WHEN sp.completion_status = 'completed' THEN sp.student_id END) AS students_completed
        FROM subjects s
        JOIN chapters c ON s.id = c.subject_id
        JOIN topics t ON c.id = t.chapter_id
        LEFT JOIN student_subjects ss ON s.id = ss.subject_id
        LEFT JOIN student_progress sp ON t.id = sp.topic_id AND sp.student_id = ss.student_id
        WHERE s.id = ?
    ");
    $stmt->execute([$subject_id]);
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Subject: " . htmlspecialchars($subject['name']);
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Subject: <?php echo htmlspecialchars($subject['name']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="edit.php?id=<?php echo $subject_id; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete.php?id=<?php echo $subject_id; ?>" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subjects
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success'];
                                                    unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Subject Details</h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($subject['description'])); ?></p>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Created by:</strong> <?php echo htmlspecialchars($subject['created_by_name'] ?: $subject['created_by_username']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Created on:</strong> <?php echo timeAgo(($subject['created_at'])); ?></small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Statistics</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center">
                                        <h3><?php echo count($chapters); ?></h3>
                                        <p class="text-muted small">Chapters</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <h3><?php echo $stats['total_topics']; ?></h3>
                                        <p class="text-muted small">Topics</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <div class="text-center">
                                        <h3><?php echo $enrollment['student_count']; ?></h3>
                                        <p class="text-muted small">Enrolled Students</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center">
                                        <h3><?php echo $stats['students_completed']; ?></h3>
                                        <p class="text-muted small">Completed</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Chapters</h5>
                        <a href="../chapters/create.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Add Chapter
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($chapters)): ?>
                        <div class="alert alert-info">
                            No chapters found for this subject. Add your first chapter to get started.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Chapter Title</th>
                                        <th>Topics</th>
                                        <th>Sequence</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chapters as $chapter): ?>
                                        <tr>
                                            <td><?php echo $chapter['sequence']; ?></td>
                                            <td><?php echo htmlspecialchars($chapter['title']); ?></td>
                                            <td><?php echo $chapter['topic_count']; ?></td>
                                            <td><?php echo $chapter['sequence']; ?></td>
                                            <td>
                                                <a href="../chapters/view.php?id=<?php echo $chapter['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../chapters/edit.php?id=<?php echo $chapter['id']; ?>&subject_id=<?php echo $subject_id; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="../chapters/delete.php?id=<?php echo $chapter['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this chapter?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Enrolled Students</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT u.id, u.username, u.full_name, u.email, ss.enrolled_at,
                                   COUNT(sp.topic_id) AS completed_topics,
                                   (SELECT COUNT(*) FROM topics t
                                    JOIN chapters c ON t.chapter_id = c.id
                                    WHERE c.subject_id = ?) AS total_topics
                            FROM student_subjects ss
                            JOIN users u ON ss.student_id = u.id
                            LEFT JOIN student_progress sp ON u.id = sp.student_id AND sp.completion_status = 'completed'
                            LEFT JOIN topics t ON sp.topic_id = t.id
                            LEFT JOIN chapters c ON t.chapter_id = c.id AND c.subject_id = ?
                            WHERE ss.subject_id = ?
                            GROUP BY u.id
                            ORDER BY u.full_name
                        ");
                        $stmt->execute([$subject_id, $subject_id, $subject_id]);
                        $students = $stmt->fetchAll();
                    } catch (PDOException $e) {
                        die("Database error: " . $e->getMessage());
                    }
                    ?>

                    <?php if (empty($students)): ?>
                        <div class="alert alert-info">
                            No students are currently enrolled in this subject.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Progress</th>
                                        <th>Enrolled On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student):
                                        $progress = $student['total_topics'] > 0 ? round(($student['completed_topics'] / $student['total_topics']) * 100) : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%"
                                                        aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $progress; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($student['enrolled_at'])); ?></td>
                                            <td>
                                                <a href="../users/view_student.php/?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info" title="View Profile">
                                                    <i class="fas fa-user"></i>
                                                </a>
                                                <a href="unenroll.php?subject_id=<?php echo $subject_id; ?>&student_id=<?php echo $student['id']; ?>"
                                                    class="btn btn-sm btn-danger" title="Unenroll"
                                                    onclick="return confirm('Are you sure you want to unenroll this student?')">
                                                    <i class="fas fa-user-minus"></i>
                                                </a>
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

<?php include '../../includes/footer.php'; ?>