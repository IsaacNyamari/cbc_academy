<?php
require_once '../../includes/config.php';
// Handle topic edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_topic_id'])) {
    $topic_id = $_POST['edit_topic_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $sequence = $_POST['sequence'];
    $video_url = $_POST['video_url'];
    // Validate and update topic in the database
    $stmt = $pdo->prepare("
        UPDATE topics SET title = ?, content = ?,sequence= ?, video_url = ? WHERE id = ?
    ");
    $stmt->execute([$title, $content, $sequence, $video_url, $topic_id]);
    header("Location: view.php?id=" . $topic_id);
    exit;   
}
