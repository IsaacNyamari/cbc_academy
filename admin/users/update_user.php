<?php
require_once '../../includes/db.php'; // Make sure this includes your PDO connection

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate required fields
$requiredFields = ['user_id', 'username', 'email', 'full_name', 'role', 'subscription_plan', 'subscription_status'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Prepare the update statement
    $stmt = $pdo->prepare("
        UPDATE users 
        SET 
            username = :username,
            email = :email,
            full_name = :full_name,
            role = :role,
            subscription_plan = :subscription_plan,
            subscription_status = :subscription_status,
            is_active = :is_active
        WHERE id = :user_id
    ");

    // Bind parameters
    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':full_name', $data['full_name']);
    $stmt->bindParam(':role', $data['role']);
    $stmt->bindParam(':subscription_plan', $data['subscription_plan']);
    $stmt->bindParam(':subscription_status', $data['subscription_status']);
    $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_BOOL);
    $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);

    // Execute the query
    $success = $stmt->execute();

    if ($success) {
        // Check if any rows were actually updated
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or user not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }

} catch (PDOException $e) {
    // Handle database errors
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'error' => $e->getMessage() // Only include in development
    ]);
} catch (Exception $e) {
    // Handle other errors
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred',
        'error' => $e->getMessage() // Only include in development
    ]);
}