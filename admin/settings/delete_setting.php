<?php
require "../../includes/db.php";
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['setting_id'])) {
    $id = $_GET['setting_id'];
    $stmt = $pdo->prepare("DELETE FROM site_settings WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: ./");
    }
}
