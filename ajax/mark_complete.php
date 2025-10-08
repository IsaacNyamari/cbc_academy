<?php
require_once '../includes/config.php';

header('Content-Type: application/json');
require_once "../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['topic_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$topic_id = (int)$_POST['topic_id'];
$topic_title = (string)$_POST['topic_title'];
$teacher_email = (string)$_POST['teacher_email'];
$student_id = $_SESSION['user_id'];

try {
    // Check if progress record exists
    $stmt = $pdo->prepare("SELECT * FROM student_progress WHERE student_id = ? AND topic_id = ?");
    $stmt->execute([$student_id, $topic_id]);
    $progress = $stmt->fetch();

    if ($progress) {
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE student_progress 
            SET completion_status = 'completed', last_accessed = NOW(), completed_at = NOW()
            WHERE student_id = ? AND topic_id = ?
        ");
        $stmt->execute([$student_id, $topic_id]);
    } else {
        // Create new record
        $stmt = $pdo->prepare("
            INSERT INTO student_progress 
            (student_id, topic_id, completion_status, last_accessed, completed_at) 
            VALUES (?, ?, 'completed', NOW(), NOW())
        ");
        $stmt->execute([$student_id, $topic_id]);
    }

    echo json_encode(['success' => true]);
    // send mail to the teacher or admin if needed using phpmailer installed via composer
    sendCompletionEmail($teacher_email, $_SESSION['full_name'], $topic_id, $topic_title);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
function sendCompletionEmail($recipientEmail, $full_name, $topic_id, $topic_name)
{
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true); // Passing true enables exceptions

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.procodestechnologies.co.ke'; // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'school@procodestechnologies.co.ke'; // SMTP username
        $mail->Password   = 'school@procodestechnologies.co.ke';   // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = 587; // TCP port to connect to

        // Recipients
        $mail->setFrom('school@procodestechnologies.co.ke', 'Yeah Kenyan Academy');
        $mail->addAddress($recipientEmail); // Teacher's email
        $mail->addReplyTo('support@example.com', 'Yeah Kenyan Academy Support');

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = "Topic Completed: $topic_name";

        // HTML email body
        $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                    .content { margin: 20px 0; }
                    .footer { color: #7f8c8d; font-size: 0.9em; border-top: 1px solid #eee; padding-top: 10px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Topic Completion Notification</h2>
                    </div>
                    <div class='content'>
                        <p>Dear Teacher,</p>
                        <p><strong>$full_name</strong> has successfully completed the topic:</p>
                        <p><strong>Topic Name:</strong> $topic_name</p>
                        <p>You can review their progress in the system.</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message from the CBC System. Please do not reply directly to this email.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        // Plain text version for non-HTML mail clients
        $mail->AltBody = "Dear Teacher,\n\n$full_name has successfully completed the topic:\n\nTopic ID: $topic_id\nTopic Name: $topic_name\n\nYou can review their progress in the system.\n\nThis is an automated message from the CBC System.";

        // Send the email
        $mail->send();

        return true;
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Mailer Error: " . $e->getMessage());
        return false;
    }
}
