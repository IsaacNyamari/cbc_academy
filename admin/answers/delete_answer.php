<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
}

// Only admin and teachers can delete
if ($_SESSION['role'] === 'student') {
    header('Location: ../student/dashboard.php');
}

// Get answer ID and question ID from URL
$answer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$question_id = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;

try {
    // Check if answer exists
    $stmt = $pdo->prepare("SELECT * FROM answers WHERE id = ?");
    $stmt->execute([$answer_id]);
    $answer = $stmt->fetch();

    if (!$answer) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Answer not found!'
        ];
        header("Location: view_answers.php?question_id=" . $question_id);
    }

    // Delete the answer
    $stmt = $pdo->prepare("DELETE FROM answers WHERE id = ?");
    $stmt->execute([$answer_id]);

    $_SESSION['message'] = [
        'type' => 'success',
        'text' => 'Answer deleted successfully!'
    ];
    header("Location: view_answers.php?question_id=" . $question_id);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
