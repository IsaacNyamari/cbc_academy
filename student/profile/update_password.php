<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: change_password.php');
}

$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Validate inputs
if (strlen($new_password) < 8) {
    header('Location: change_password.php?error=Password must be at least 8 characters long');
}

if ($new_password !== $confirm_password) {
    header('Location: change_password.php?error=New passwords do not match');
}

// Verify current password
try {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($current_password, $user['password'])) {
        header('Location: change_password.php?error=Current password is incorrect');
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
    
    header('Location: change_password.php?success=1');
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>