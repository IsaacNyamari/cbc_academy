<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: edit.php');
}

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_data = $stmt->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Process form data
$full_name = sanitizeInput($_POST['full_name']);
$email = sanitizeInput($_POST['email']);
$username = sanitizeInput($_POST['username']);
$profile_pic = $current_data['profile_pic'];

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format";
    header('Location: edit.php');
}
// avatar_5_1753257314.jpg
// Handle file upload
if (!empty($_FILES['profile_pic']['name'])) {
    $target_dir = "../../assets/images/avatars/";
    unlink($target_dir . $_SESSION["avartar"]);
    $target_file = $target_dir . basename($_FILES['profile_pic']['name']);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image
    $check = getimagesize($_FILES['profile_pic']['tmp_name']);
    if ($check === false) {
        $_SESSION['error'] = "File is not an image.";
        header('Location: edit.php');
    }

    // Check file size (2MB max)
    if ($_FILES['profile_pic']['size'] > 2000000) {
        $_SESSION['error'] = "Sorry, your file is too large (max 2MB).";
        header('Location: edit.php');
    }

    // Allow certain file formats
    if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
        $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        header('Location: edit.php');
    }

    // Generate unique filename
    $new_filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;

    // Try to upload file
    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
        // Delete old profile pic if it exists and isn't the default
        if ($profile_pic && $profile_pic !== 'default.png') {
            $old_file = $target_dir . $profile_pic;
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        $profile_pic = $new_filename;
    } else {
        $_SESSION['error'] = "Sorry, there was an error uploading your file.";
        header('Location: edit.php');
    }
}

// Update database
try {
    $stmt = $pdo->prepare("
        UPDATE users 
        SET full_name = ?, email = ?, username = ?, profile_pic = ?
        WHERE id = ?
    ");
    $stmt->execute([$full_name, $email, $username, $profile_pic, $_SESSION['user_id']]);

    // Update session data
    $_SESSION['full_name'] = $full_name;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;

    header('Location: edit.php?success=1');
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry (username or email)
        $_SESSION['error'] = "Username or email already exists";
        header('Location: edit.php');
    } else {
        die("Database error: " . $e->getMessage());
    }
}
