<?php
require "../../includes/db.php";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['setting_id']) && isset($_POST['setting_name']) && isset($_POST['setting_value'])) {
    $setting_name = $_POST['setting_name'];
    $setting_value = $_POST['setting_value'];

    $stmt = $pdo->prepare("INSERT INTO site_settings(name,setting_value)VALUES(?,?)");
    if ($stmt->execute([$setting_name, $setting_value])) {
        header("Location: ./?success=1");
    }
}
