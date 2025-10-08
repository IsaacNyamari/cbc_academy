<?php
require_once '../../includes/config.php';

if (isset($_GET['topic_id'])) {
    $topic_id = (int)$_GET['topic_id'];
    // Delete the topic
    $stmt = $pdo->prepare("DELETE FROM topics WHERE id = ?");
    if ($stmt->execute([$topic_id])) {
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Topic deleted successfully.'
        ];
        header('Location: ./');
        exit;
    } else {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Failed to delete topic. Please try again.'
        ];
    }
} else {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'No topic ID provided.'
    ];
    header('Location: ./');
    exit;
}
