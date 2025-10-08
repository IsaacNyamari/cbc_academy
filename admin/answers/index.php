<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    header('Location: ../student/dashboard.php');
    exit;
}

// Fetch all answers (with question title)
try {
    $stmt = $pdo->query("
        SELECT a.id, a.answer_text, a.is_correct, q.title AS question_title
        FROM answers a
        JOIN quizzes q ON a.question_id = q.id
        ORDER BY q.title ASC, a.id ASC
    ");
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">All Answers</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="create_answer.php" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Add New Answer
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['message']['text']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($answers)): ?>
            <div class="alert alert-info">No answers found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Answer</th>
                            <th>Correct</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($answers as $index => $answer): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($answer['question_title']); ?></td>
                                <td><?php echo htmlspecialchars($answer['answer_text']); ?></td>
                                <td>
                                    <?php if ($answer['is_correct']): ?>
                                        <span class="badge bg-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_answer.php?id=<?php echo $answer['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_answer.php?id=<?php echo $answer['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Are you sure you want to delete this answer?');">
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

<?php include '../../includes/footer.php'; ?>
