<?php
$base_path = '/'; // Change this if your project is in a subdirectory
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . $host . $base_path);
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
date_default_timezone_set("Africa/Nairobi"); // Set timezone to Nairobi, Kenya
// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Base URL configuration for VS Code PHP Live Server


// Include database connection
require_once 'db.php';

// Functions
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isTeacher()
{
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin');
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url)
{
    if (!headers_sent()) {
        header("Location: " . BASE_URL . ltrim($url, '/'));
        exit();
    } else {
        echo "<script>window.location.href='" . BASE_URL . ltrim($url, '/') . "';</script>";
        exit();
    }
}

function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Database configuration for local development
$db_config = [
    'host' => 'localhost',
    'name' => 'gumyombf_cbc_system',
    'user' => 'gumyombf_cbc_system',
    'pass' => 'gumyombf_cbc_system', // Empty password for local development
    'port' => '3306' // Default MySQL port
];
function timeAgo($datetime)
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}
function sendResetCode($email, $code)
{
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'mail.evanxcoolingsystems.co.ke';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@evanxcoolingsystems.co.ke';
        $mail->Password   = 'info@evanxcoolingsystems.co.ke';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@evanxcoolingsystems.co.ke', 'Yeah Kenyan Academy - Password Reset');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset Code';

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; background: #f9f9f9; border: 1px solid #ddd; padding: 20px;'>
            <div style='text-align: center; padding-bottom: 20px;'>
                <img src='https://evanxcoolingsystems.co.ke/logo.png' alt='Yeah Kenyan Academy' style='max-width: 180px;'>
            </div>
            <h2 style='color: #333; text-align: center;'>Password Reset Request</h2>
            <p style='color: #555; font-size: 15px; text-align: center;'>
                We received a request to reset your password. Use the code below to complete your reset:
            </p>
            <div style='background: #004aad; color: #fff; font-size: 22px; text-align: center; padding: 15px; margin: 20px auto; border-radius: 5px; letter-spacing: 2px;'>
                <b>$code</b>
            </div>
            <p style='color: #555; font-size: 14px;'>
                If you did not request a password reset, you can safely ignore this email. This code will expire in 15 minutes.
            </p>
            <hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>
            <p style='font-size: 12px; color: #999; text-align: center;'>
                &copy; " . date('Y') . " Yeah Kenyan Academy. All rights reserved.
            </p>
        </div>
        ";

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}

define("TRIAL_PERIOD", 14);
