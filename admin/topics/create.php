<?php
require_once '../../includes/config.php';


// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    header('Location: ../student/dashboard.php');
}

// Get chapter ID from URL
$chapter_id = isset($_POST['chapter_id']) ? (int)$_POST['chapter_id'] : 0;

// Validate chapter exists and belongs to authorized teacher
try {
    $stmt = $pdo->prepare("
        SELECT c.*, s.name AS subject_title, s.id AS subject_id 
        FROM chapters c
        JOIN subjects s ON c.subject_id = s.id
        WHERE c.id = ?
    ");
    $stmt->execute([$chapter_id]);
    $chapter = $stmt->fetch();

    if (!$chapter) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Chapter not found!'
        ];
        header('Location: index.php');
    }

    // If teacher, verify they created the parent subject
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND created_by = ?");
        $stmt->execute([$chapter['subject_id'], $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'You are not authorized to add topics to this chapter!'
            ];
            header('Location: index.php');
        }
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Validate inputs
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $sequence = (int)($_POST['sequence'] ?? 0);

    if (empty($title)) {
        $errors['title'] = 'Topic title is required';
    } elseif (strlen($title) > 255) {
        $errors['title'] = 'Topic title cannot exceed 255 characters';
    }

    if (empty($content)) {
        $errors['content'] = 'Content is required';
    }

    if (empty($sequence) || $sequence <= 0) {
        $errors['sequence'] = 'Sequence must be a positive integer';
    }

    // Validate video URL format if provided
    if (!empty($video_url)) {
        if (!filter_var($video_url, FILTER_VALIDATE_URL)) {
            $errors['video_url'] = 'Please enter a valid URL';
        }
    }

    // Check if topic title already exists in this chapter
    try {
        $stmt = $pdo->prepare("SELECT id FROM topics WHERE title = ? AND chapter_id = ?");
        $stmt->execute([$title, $chapter_id]);
        if ($stmt->fetch()) {
            $errors['title'] = 'A topic with this title already exists in this chapter';
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert the new topic
            $stmt = $pdo->prepare("
                INSERT INTO topics (chapter_id, title, content, video_url, sequence, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $chapter_id,
                $title,
                $content,
                $video_url,
                $sequence
            ]);

            $topic_id = $pdo->lastInsertId();

            $pdo->commit();

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Topic created successfully!'
            ];

            header("Location: view.php?id=$topic_id");

        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Database error: " . $e->getMessage());
        }
    }
}
