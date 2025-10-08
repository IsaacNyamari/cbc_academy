<?php
require_once "../../includes/db.php";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    http_response_code(200);
//     $data = json_decode(file_get_contents("php://input"), true);
  echo  $topic_id = $_POST['topic_id'];
    exit;
    if (!empty($topic_id) && $topic_id !== 0) {
        $title = $_POST['title'] ?? '';
        $chapter_id = $_POST['chapter_id'] ?? '';
        $content = $_POST['content'] ?? '';
        $video_url = $_POST['video_url'] ?? '';
        $sequence = $_POST['sequence'] ?? '';

        $stmt = $pdo->prepare("UPDATE topics SET chapter_id = ?, content = ?, title = ?, video_url = ?, sequence = ? WHERE id = ?");
        $success = $stmt->execute([$chapter_id, $content, $title, $video_url, $sequence, $topic_id]);

        if ($success) {
            echo json_encode([
                "status" => "success",
                "message" => "Topic updated successfully."
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to update topic."
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "topic id not provided!"
        ]);
    }
} else {
    http_response_code(404);
}
