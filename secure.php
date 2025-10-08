<?php
require_once 'includes/config.php';
$key = 1235468; // Use a secure key for encryption/decryption
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;
function encryptString($string, $key)
{
    $iv = random_bytes(16); // random initialization vector
    $ciphertext = openssl_encrypt($string, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $ciphertext);
}

function decryptString($encrypted, $key)
{
    $data = base64_decode($encrypted);
    $iv = substr($data, 0, 16);
    $ciphertext = substr($data, 16);
    return openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);
}
$encryptedSearch = encryptString(trim("stanley@gmail.com	"), $key);
// Search functionality
$search = decryptString($encryptedSearch, $key);
$search_condition = '';
$params = [];

if (!empty($search)) {
    $search_condition = "WHERE (username LIKE :search OR full_name LIKE :search OR email LIKE :search) AND role IN ('teacher', 'student')";
    $params[':search'] = "%$search%";
} else {
    $search_condition = "WHERE role IN ('teacher', 'student')";
}

try {
    // Get total count for pagination
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $search_condition");
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    $total_users = $stmt->fetchColumn();

    $total_pages = ceil($total_users / $per_page);

    // Get users with pagination
    $query = "SELECT id, username, full_name, email, role, created_at, is_active
              FROM users $search_condition 
              ORDER BY role DESC, created_at DESC 
              LIMIT :offset, :per_page";

    $stmt = $pdo->prepare($query);

    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }

    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();

    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
var_dump($users); // Debugging line to check fetched users