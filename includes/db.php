<?php
$host = 'localhost';
$dbname = 'gumyombf_cbc_system';
$username = 'gumyombf_cbc_system';
$password = 'gumyombf_cbc_system';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables if they don't exist

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE name ='site_name'");
$stmt->execute();
$result = $stmt->fetch();
$site_name = $result['setting_value'];
define("SITE_NAME", $site_name);