<?php
require_once '../../includes/config.php';
$subject_id = (int)$_GET['id'];

// Check if the request is a GET (confirmation)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Begin transaction
        $pdo->beginTransaction();

        // 1. First, delete all student_subjects associations for this subject
        $stmt = $pdo->prepare("DELETE FROM student_subjects WHERE subject_id = ?");
        $stmt->execute([$subject_id]);

        // 2. Get all chapters for this subject to delete their topics
        $stmt = $pdo->prepare("SELECT id FROM chapters WHERE subject_id = ?");
        $stmt->execute([$subject_id]);
        $chapters = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($chapters)) {
            // Create chapter placeholders
            $chapterPlaceholders = implode(',', array_fill(0, count($chapters), '?'));

            // First get topic IDs
            $stmt = $pdo->prepare("SELECT id FROM topics WHERE chapter_id IN ($chapterPlaceholders)");
            $stmt->execute($chapters);
            $topics = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($topics)) {
                // Create topic placeholders
                $topicPlaceholders = implode(',', array_fill(0, count($topics), '?'));

                // Delete quiz attempts
                $stmt = $pdo->prepare("
                    DELETE sqa FROM student_quiz_attempts sqa
                    JOIN quizzes q ON sqa.quiz_id = q.id
                    WHERE q.topic_id IN ($topicPlaceholders)
                ");
                $stmt->execute($topics);

                // Delete quiz responses
                $stmt = $pdo->prepare("
                    DELETE sqr FROM student_quiz_responses sqr
                    JOIN student_quiz_attempts sqa ON sqr.attempt_id = sqa.id
                    JOIN quizzes q ON sqa.quiz_id = q.id
                    WHERE q.topic_id IN ($topicPlaceholders)
                ");
                $stmt->execute($topics);

                // Delete questions and answers
                $stmt = $pdo->prepare("
                    DELETE a FROM answers a
                    JOIN questions q ON a.question_id = q.id
                    JOIN quizzes qu ON q.quiz_id = qu.id
                    WHERE qu.topic_id IN ($topicPlaceholders)
                ");
                $stmt->execute($topics);

                $stmt = $pdo->prepare("
                    DELETE q FROM questions q
                    JOIN quizzes qu ON q.quiz_id = qu.id
                    WHERE qu.topic_id IN ($topicPlaceholders)
                ");
                $stmt->execute($topics);

                // Delete quizzes
                $stmt = $pdo->prepare("DELETE FROM quizzes WHERE topic_id IN ($topicPlaceholders)");
                $stmt->execute($topics);

                // Delete student progress records
                $stmt = $pdo->prepare("DELETE FROM student_progress WHERE topic_id IN ($topicPlaceholders)");
                $stmt->execute($topics);

                // Finally delete topics
                $stmt = $pdo->prepare("DELETE FROM topics WHERE id IN ($topicPlaceholders)");
                $stmt->execute($topics);
            }

            // 4. Delete chapters
            $stmt = $pdo->prepare("DELETE FROM chapters WHERE id IN ($chapterPlaceholders)");
            $stmt->execute($chapters);
        }

        // 5. Delete the subject itself
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);

        // Commit transaction
        $pdo->commit();

        $_SESSION['success'] = "Subject and all related content deleted successfully!";
        header('Location: ./index.php');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Database error: " . $e->getMessage());
    }
}

// Get subject details for confirmation
try {
    $stmt = $pdo->prepare("SELECT name FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();

    if (!$subject) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
