<?php
require "../../includes/db.php";
$data = file_get_contents("php://input");
$data = json_decode($data);
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $data->user_id;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $results = $stmt->fetch();
    header("Content-Type: Application/json");
    echo json_encode($results);
}
