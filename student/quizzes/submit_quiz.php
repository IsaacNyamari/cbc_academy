<?php
require_once '../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['question_id']) || !isset($_POST['answer_id'])) {
    header('Location: index.php');
    exit;
}

$question_id = (int)$_POST['question_id'];
$answer_id  = (int)$_POST['answer_id'];
$student_id = $_SESSION['user_id'];
try {
    // Get quiz details
    $stmt = $pdo->prepare("SELECT id, points, passing_score FROM quizzes WHERE id = ?");
    $stmt->execute([$question_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($quiz); // Debugging line, can be removed later
    if (!$quiz) {
        header('Location: index.php');
        exit;
    }

    // Check if the selected answer is correct
    $stmt = $pdo->prepare("SELECT is_correct FROM answers WHERE id = ? AND question_id = ?");
    $stmt->execute([$answer_id, $question_id]); // here question_id = quiz_id in your design
    $answer = $stmt->fetch(PDO::FETCH_ASSOC);

    $correct   = $answer && $answer['is_correct'];
    $score     = $correct ? $quiz['points'] : 0;
    $percentage = $correct ? 100 : 0;
    $passed    = $percentage >= $quiz['passing_score'] ? 1 : 0;

    // Save the attempt and response
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO student_quiz_attempts (student_id, quiz_id, score, passed) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$student_id, $question_id, $percentage, $passed]);
    $attempt_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO student_quiz_responses (attempt_id, question_id, answer_id)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$attempt_id, $question_id, $answer_id]);

    $pdo->commit();

    // Redirect to results page with quiz ID
    header("Location: ./results.php?id=" . $question_id);
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Database error: " . $e->getMessage());
}
