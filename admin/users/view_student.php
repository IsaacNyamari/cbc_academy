<?php
require_once '../../includes/config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    redirect('../student/dashboard.php');
}

// Get student ID from URL
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    redirect('students.php');
}

try {
    // Get student basic information
    $stmt = $pdo->prepare("SELECT id, username, full_name, email,is_active,profile_pic as avatar, created_at FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Student not found!'
        ];
        redirect('students.php');
    }

    // Get student progress statistics
    $progress_stmt = $pdo->prepare("
        SELECT 
            COUNT(sp.id) AS total_topics,
            SUM(CASE WHEN sp.completion_status = 'completed' THEN 1 ELSE 0 END) AS completed_topics,
            SUM(CASE WHEN sp.completion_status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_topics,
            SUM(CASE WHEN sp.completion_status = 'not_started' OR sp.completion_status IS NULL THEN 1 ELSE 0 END) AS not_started_topics
        FROM users u
        LEFT JOIN student_progress sp ON u.id = sp.student_id
        WHERE u.id = ?
    ");
    $progress_stmt->execute([$student_id]);
    $progress = $progress_stmt->fetch();

    // Get recent activities
    $activities_stmt = $pdo->prepare("
        SELECT 
            t.title AS topic_title,
            c.title AS chapter_name,
            s.name AS subject_name,
            sp.completion_status,
            sp.completed_at,
            sp.quiz_score
        FROM student_progress sp
        JOIN topics t ON sp.topic_id = t.id
        JOIN chapters c ON t.chapter_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        WHERE sp.student_id = ?
        ORDER BY sp.completed_at DESC
        LIMIT 5
    ");
    $activities_stmt->execute([$student_id]);
    $recent_activities = $activities_stmt->fetchAll();

    // Get enrolled subjects
    $subjects_stmt = $pdo->prepare("
        SELECT s.id, s.name, s.id
        FROM student_subjects ss
        JOIN subjects s ON ss.subject_id = s.id
        WHERE ss.student_id = ?
        ORDER BY s.name
    ");
    $subjects_stmt->execute([$student_id]);
    $enrolled_subjects = $subjects_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<?php include '../../includes/header.php'; ?>


<?php include '../../includes/sidebar.php'; ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Student Profile</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="students.php" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Students
        </a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Student Profile Header -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-2 text-center">
                <div class="mb-3">
                    <img src="../../../assets/images/avatars/<?php echo $student["avatar"] ?>" alt="Student Avatar" class="student-avatar zoomable" width="120" height="120">
                </div>
                <span class="badge bg-<?php echo $student['is_active'] !== 0 ? 'success' : 'danger'; ?>">

                    <?php 
                   echo $student['is_active'] !== 0 ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
            <div class="col-md-10">
                <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
                <p class="text-muted">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($student['username']); ?> |
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?>
                </p>
                <hr>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Joined On:</strong><br>
                            <?php
                            // Helper function to show "how long ago"

                            echo date('F j, Y', strtotime($student['created_at']));
                            echo ' <small class="text-muted">(' . timeAgo($student['created_at']) . ')</small>';
                            ?></p>
                    </div>

                    <div class="col-md-4">
                        <p><strong>Student ID:</strong><br>
                            #<?php echo str_pad($student['id'], 5, '0', STR_PAD_LEFT); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Progress Statistics -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Learning Progress</h5>
            </div>
            <div class="card-body">
                <div class="progress mb-3" style="height: 30px;">
                    <?php
                    $completion_percentage = $progress['total_topics'] > 0
                        ? round(($progress['completed_topics'] / $progress['total_topics']) * 100, 1)
                        : 0;
                    ?>
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_percentage; ?>%"
                        aria-valuenow="<?php echo $completion_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                        <?php echo $completion_percentage; ?>%
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Completed Topics
                        <span class="badge bg-success rounded-pill"><?php echo $progress['completed_topics']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        In Progress
                        <span class="badge bg-warning rounded-pill"><?php echo $progress['in_progress_topics']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Not Started
                        <span class="badge bg-secondary rounded-pill"><?php echo $progress['not_started_topics']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total Topics
                        <span class="badge bg-primary rounded-pill"><?php echo $progress['total_topics']; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Enrolled Subjects -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Enrolled Subjects</h5>
                <span class="badge bg-primary"><?php echo count($enrolled_subjects); ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($enrolled_subjects)): ?>
                    <div class="alert alert-info mb-0">No enrolled subjects found.</div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($enrolled_subjects as $subject): ?>
                            <a href="../../subjects/view.php?id=<?php echo $subject['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($subject['name']); ?></h6>
                                    <small><?php echo htmlspecialchars($subject['id']); ?></small>
                                </div>
                                <small class="text-muted">Click to view subject details</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Activities</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_activities)): ?>
                    <div class="alert alert-info mb-0">No recent activities found.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recent_activities as $activity): ?>
                            <li class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 pointer" title="<?php echo htmlspecialchars($activity['topic_title']); ?>">
                                        <?php
                                        $words = explode(' ', htmlspecialchars($activity['topic_title']));
                                        echo implode(' ', array_slice($words, 0, 3));
                                        ?>
                                    </h6>
                                    <span class="badge bg-<?php
                                                            echo $activity['completion_status'] === 'completed' ? 'success' : ($activity['completion_status'] === 'in_progress' ? 'warning' : 'secondary');
                                                            ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $activity['completion_status'])); ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($activity['subject_name']); ?> &raquo;
                                    <?php echo htmlspecialchars($activity['chapter_name']); ?>
                                </small>
                                <?php if ($activity['completed_at']): ?>
                                    <div class="mt-1">
                                        <small>
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo timeAgo((($activity['completed_at']))); ?>
                                            <?php if ($activity['quiz_score'] !== null): ?>
                                                | Score: <?php echo $activity['quiz_score']; ?>%
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Additional Student Information -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Detailed Progress by Subject</h5>
    </div>
    <div class="card-body">
        <?php
        try {
            $subject_progress_stmt = $pdo->prepare("
                            SELECT 
                                s.id AS subject_id,
                                s.name AS subject_name,
                                COUNT(t.id) AS total_topics,
                                SUM(CASE WHEN sp.completion_status = 'completed' THEN 1 ELSE 0 END) AS completed_topics,
                                ROUND(SUM(CASE WHEN sp.completion_status = 'completed' THEN 1 ELSE 0 END) * 100.0 / COUNT(t.id), 1) AS completion_percentage
                            FROM student_subjects ss
                            JOIN subjects s ON ss.subject_id = s.id
                            LEFT JOIN chapters c ON s.id = c.subject_id
                            LEFT JOIN topics t ON c.id = t.chapter_id
                            LEFT JOIN student_progress sp ON t.id = sp.topic_id AND sp.student_id = ?
                            WHERE ss.student_id = ?
                            GROUP BY s.id, s.name
                            ORDER BY s.name
                        ");
            $subject_progress_stmt->execute([$student_id, $student_id]);
            $subject_progress = $subject_progress_stmt->fetchAll();

            if (empty($subject_progress)) {
                echo '<div class="alert alert-info">No subject progress data available.</div>';
            } else {
                echo '<div class="table-responsive">';
                echo '<table class="table table-striped">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Subject</th>';
                echo '<th>Progress</th>';
                echo '<th>Completed</th>';
                echo '<th>Actions</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                foreach ($subject_progress as $subject) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($subject['subject_name']) . '</td>';
                    echo '<td>';
                    echo '<div class="progress" style="height: 20px;">';
                    echo '<div class="progress-bar" role="progressbar" style="width: ' . $subject['completion_percentage'] . '%;" ';
                    echo 'aria-valuenow="' . $subject['completion_percentage'] . '" aria-valuemin="0" aria-valuemax="100">';
                    echo $subject['completion_percentage'] . '%';
                    echo '</div>';
                    echo '</div>';
                    echo '</td>';
                    echo '<td>' . $subject['completed_topics'] . ' / ' . $subject['total_topics'] . '</td>';
                    echo '<td>';
                    echo '<a href="../../subjects/view_subject_progress.php?student_id=' . $student_id . '&subject_id=' . $subject['subject_id'] . '" class="btn btn-sm btn-outline-primary">';
                    echo 'View Details';
                    echo '</a>';
                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
                echo '</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Error loading subject progress: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</div>
</main>
</div>
</div>

<?php include '../../includes/footer.php'; ?>