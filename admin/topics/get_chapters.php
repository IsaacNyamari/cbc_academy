<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['subject_id'])) {
    echo json_encode([]);
    exit;
}

$subject_id = (int)$_GET['subject_id'];

try {
    $stmt = $pdo->prepare("SELECT id, title FROM chapters WHERE subject_id = ? ORDER BY sequence");
    $stmt->execute([$subject_id]);
    $chapters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($chapters);
} catch (PDOException $e) {
    echo json_encode([]);
}
