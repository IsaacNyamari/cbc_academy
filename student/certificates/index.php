<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
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

$page_title = "My Certificates";
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Certificates</h1>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-body">
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM certificates WHERE student_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $certificates = $stmt->fetchAll();

                            if ($certificates): ?>
                                <ul class="list-group">
                                    <?php foreach ($certificates as $cert): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Course ID: <?= htmlspecialchars($cert['course_id']) ?> | Issued: <?= htmlspecialchars($cert['issued_date']) ?></span>
                                            <a href="certificates/<?= htmlspecialchars($cert['file_path']) ?>" class="btn btn-primary btn-sm" target="_blank">View Certificate</a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No certificates found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>