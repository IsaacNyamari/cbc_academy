<?php
require_once '../../includes/config.php';
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get student details
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    if (!$student) {
        redirect('login.php');
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "My Profile";
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Profile</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="edit.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <img src="<?php echo BASE_URL . 'assets/images/avatars/' . ($student['profile_pic'] ?: 'default.png'); ?>" 
                                 class="rounded-circle mb-3" width="150" height="150" alt="Profile Picture">
                            <h4><?php echo htmlspecialchars($student['full_name']); ?></h4>
                            <p class="text-muted">@<?php echo htmlspecialchars($student['username']); ?></p>
                            <a href="change_password.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-key"></i> Change Password
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Full Name</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <?php echo htmlspecialchars($student['full_name']); ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Username</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <?php echo htmlspecialchars($student['username']); ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Email</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <?php echo htmlspecialchars($student['email']); ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Member Since</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <?php echo date('F j, Y', strtotime($student['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($_SESSION['role'] === 'student'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Academic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Enrolled Subjects</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <?php
                                    try {
                                        $stmt = $pdo->prepare("
                                            SELECT COUNT(DISTINCT s.id) AS subject_count
                                            FROM subjects s
                                            JOIN chapters c ON s.id = c.subject_id
                                            JOIN topics t ON c.id = t.chapter_id
                                            JOIN student_progress sp ON t.id = sp.topic_id
                                            WHERE sp.student_id = ?
                                        ");
                                        $stmt->execute([$_SESSION['user_id']]);
                                        $result = $stmt->fetch();
                                        echo $result['subject_count'] ?? 0;
                                    } catch (PDOException $e) {
                                        echo "Error loading data";
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <h6 class="mb-0">Completed Topics</h6>
                                </div>
                                <div class="col-sm-9 text-secondary">
                                    <?php
                                    try {
                                        $stmt = $pdo->prepare("
                                            SELECT COUNT(*) AS completed_topics
                                            FROM student_progress
                                            WHERE student_id = ? AND completion_status = 'completed'
                                        ");
                                        $stmt->execute([$_SESSION['user_id']]);
                                        $result = $stmt->fetch();
                                        echo $result['completed_topics'] ?? 0;
                                    } catch (PDOException $e) {
                                        echo "Error loading data";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif;?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>