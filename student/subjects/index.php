<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    header('Location: ../../login.php');
}

// Handle subject selection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject_ids'])) {
    try {
        // First, remove all existing subject selections for this student
        $stmt = $pdo->prepare("DELETE FROM student_subjects WHERE student_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Add new selections
        $stmt = $pdo->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
        foreach ($_POST['subject_ids'] as $subject_id) {
            $stmt->execute([$_SESSION['user_id'], (int)$subject_id]);
        }
        
        $_SESSION['success'] = "Subjects updated successfully!";
        header('Location: index.php');
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Get all available subjects
try {
    $stmt = $pdo->prepare("SELECT id, name, description FROM subjects ORDER BY name");
    $stmt->execute();
    $all_subjects = $stmt->fetchAll();
    
    // Get student's currently selected subjects
    $stmt = $pdo->prepare("
        SELECT s.id, s.name 
        FROM student_subjects ss
        JOIN subjects s ON ss.subject_id = s.id
        WHERE ss.student_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $selected_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Get subjects with progress data
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.description,
               COUNT(t.id) AS total_topics,
               COUNT(sp.topic_id) AS completed_topics
        FROM student_subjects ss
        JOIN subjects s ON ss.subject_id = s.id
        JOIN chapters c ON s.id = c.subject_id
        JOIN topics t ON c.id = t.chapter_id
        LEFT JOIN student_progress sp ON t.id = sp.topic_id AND sp.student_id = ? AND sp.completion_status = 'completed'
        WHERE ss.student_id = ?
        GROUP BY s.id
        ORDER BY s.name
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "My Subjects";
include '../../includes/header.php';
?>
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Subjects</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#selectSubjectsModal">
                        <i class="fas fa-plus"></i> Select Subjects
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <div class="row">
                <?php if (empty($subjects)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h4 class="alert-heading">No Subjects Selected</h4>
                            <p>You haven't selected any subjects yet. Click the "Select Subjects" button to choose which subjects you want to study.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($subjects as $subject): 
                        $percentage = $subject['total_topics'] > 0 ? round(($subject['completed_topics'] / $subject['total_topics']) * 100) : 0;
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($subject['name']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($subject['description']); ?></p>
                                
                                <div class="progress mb-3" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                         aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $percentage; ?>%
                                    </div>
                                </div>
                                
                                <p class="card-text small">
                                    <?php echo $subject['completed_topics']; ?> of <?php echo $subject['total_topics']; ?> topics completed
                                </p>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="view.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View Subject
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Subject Selection Modal -->
<div class="modal fade" id="selectSubjectsModal" tabindex="-1" aria-labelledby="selectSubjectsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="selectSubjectsModalLabel">Select Your Subjects</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Select the subjects you want to study. You can change this selection at any time.</p>
                    
                    <div class="row">
                        <?php foreach ($all_subjects as $subject): ?>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       id="subject_<?php echo $subject['id']; ?>" 
                                       name="subject_ids[]" 
                                       value="<?php echo $subject['id']; ?>"
                                       <?php echo in_array($subject['id'], $selected_subjects) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="subject_<?php echo $subject['id']; ?>">
                                    <strong><?php echo htmlspecialchars($subject['name']); ?></strong>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($subject['description']); ?></p>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>