<?php

use GuzzleHttp\Psr7\Header;

require_once './includes/config.php';


$page_title = "Reset Password";

// Function to send the reset email

// Step 1: Email submission
if (isset($_POST['email_request'])) {
    $email = sanitizeInput($_POST['email_request']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = strtoupper(bin2hex(random_bytes(3))); // 6-char code
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ? WHERE id = ?");
            $stmt->execute([$token, $user['id']]);

            $_SESSION['password_reset_email'] = $email;
            $_SESSION['password_reset_user_id'] = $user['id'];
            $_SESSION['password_reset_step'] = 'token';

            if (sendResetCode($email, $token)) {
                $success = "A reset code has been sent to your email address.";
            } else {
                $error = "Failed to send email. Please try again.";
            }
        } else {
            $error = "No user found with that email address.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Step 2: Token confirmation
if (isset($_POST['token_confirm'])) {
    $entered_token = sanitizeInput($_POST['reset_token']);
    $user_id = $_SESSION['password_reset_user_id'] ?? null;

    if ($user_id) {
        $stmt = $pdo->prepare("SELECT reset_token FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();

        if ($row && hash_equals($row['reset_token'], $entered_token)) {
            $_SESSION['password_reset_step'] = 'reset';
            $success = "Token confirmed. Please enter your new password.";
        } else {
            $error = "Invalid token. Please try again.";
        }
    } else {
        $error = "Session expired. Please start again.";
    }
}

// Step 3: Password reset
if (isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['password_reset_user_id'] ?? null;

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif ($user_id) {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE id = ?");
        $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);

        $success = "Password reset successfully.";
        session_unset();
        header('Location: login.php'); // Redirect to login after reset
    } else {
        $error = "Session expired. Please start again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
     <style>
        :root {
            --kenya-red: #BB0000;
            --kenya-green: #006600;
            --kenya-black: #000000;
            --kenya-white: #FFFFFF;
            --accent-color: #F0C808;
            /* Added as accent to complement the flag colors */
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            background-image: url('assets/images/background.png');
            background-repeat: repeat;
            background-size: cover;
        }

        .navbar {
            padding: 15px 0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background-color: var(--kenya-white) !important;
        }

        .navbar.scrolled {
            padding: 10px 0;
            background-color: var(--kenya-white) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--kenya-black) !important;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            margin-right: 10px;
        }

        .nav-link {
            color: var(--kenya-black) !important;
            font-weight: 500;
            margin: 0 10px;
            position: relative;
        }

        .nav-link:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: var(--kenya-red);
            bottom: 0;
            left: 0;
            transition: width 0.3s ease;
        }

        .nav-link:hover:after {
            width: 100%;
        }

        .btn-primary {
            background-color: var(--kenya-red);
            border-color: var(--kenya-red);
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #9e0000;
            border-color: #9e0000;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(187, 0, 0, 0.3);
        }

        .btn-outline-primary {
            color: var(--kenya-red);
            border-color: var(--kenya-red);
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background-color: var(--kenya-red);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(187, 0, 0, 0.3);
        }

        .hero {
            background: linear-gradient(135deg, var(--kenya-red), var(--kenya-green));
            color: white;
            padding: 120px 0 100px;
            position: relative;
            overflow: hidden;
        }

        .hero:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1588072432836-e10032774350?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80') no-repeat center center;
            background-size: cover;
            opacity: 0.5;
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .hero p {
            font-size: 1.25rem;
            max-width: 600px;
            margin: 0 auto 30px;
            opacity: 0.9;
        }

        .hero-btns .btn {
            margin: 0 10px;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .hero-btns .btn-light {
            background-color: white;
            color: var(--kenya-red);
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .hero-btns .btn-light:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(255, 255, 255, 0.3);
        }

        section {
            padding: 80px 0;
        }

        .section-title {
            font-weight: 700;
            color: var(--kenya-black);
            margin-bottom: 50px;
            position: relative;
            display: inline-block;
        }

        .section-title:after {
            content: '';
            position: absolute;
            width: 50%;
            height: 3px;
            background: linear-gradient(90deg, var(--kenya-red), var(--kenya-green));
            bottom: -10px;
            left: 0;
            border-radius: 3px;
        }

        .about-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        .contact-form {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .form-control {
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 10px 15px;
            margin-bottom: 20px;
        }

        .form-control:focus {
            border-color: var(--kenya-green);
            box-shadow: 0 0 0 0.25rem rgba(0, 102, 0, 0.25);
        }

        textarea.form-control {
            height: auto;
            min-height: 150px;
        }

        footer {
            background-color: var(--kenya-black);
            color: white;
            padding: 40px 0 20px;
        }

        .social-icons a {
            color: white;
            font-size: 1.2rem;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .social-icons a:hover {
            color: var(--accent-color);
            transform: translateY(-3px);
        }

        .footer-links {
            margin-bottom: 20px;
        }

        .footer-links a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--kenya-red);
        }

        /* Kenyan flag inspired decorative elements */
        .kenya-flag-theme {
            position: relative;
        }

        .kenya-flag-theme:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg,
                    var(--kenya-black) 0%,
                    var(--kenya-black) 33%,
                    var(--kenya-red) 33%,
                    var(--kenya-red) 66%,
                    var(--kenya-green) 66%,
                    var(--kenya-green) 100%);
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1rem;
            }

            section {
                padding: 60px 0;
            }
        }
    </style>
</head>
<body>
<div class="container py-5">
    <main class="mx-auto" style="max-width: 400px;">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php
        $step = $_SESSION['password_reset_step'] ?? 'email';

        if ($step === 'email'): ?>
            <!-- Step 1: Email -->
            <h3>Enter Your Registered Email</h3>
            <form method="POST">
                <input type="email" name="email_request" class="form-control mb-3" placeholder="Email address" required>
                <button type="submit" class="btn btn-primary w-100">Send Code</button>
            </form>

        <?php elseif ($step === 'token'): ?>
            <!-- Step 2: Token -->
            <p>Enter The Code Sent To Your Email</p>
            <form method="POST">
                <input type="text" name="reset_token" class="form-control mb-3" placeholder="Enter code" required>
                <button type="submit" name="token_confirm" class="btn btn-primary w-100">Verify Code</button>
            </form>

        <?php elseif ($step === 'reset'): ?>
            <!-- Step 3: New Password -->
            <h3>Reset Your Password</h3>
            <form method="POST">
                <input type="password" name="new_password" class="form-control mb-3" placeholder="New password" required>
                <input type="password" name="confirm_password" class="form-control mb-3" placeholder="Confirm password" required>
                <button type="submit" name="reset_password" class="btn btn-primary w-100">Reset Password</button>
            </form>
        <?php endif; ?>
    </main>
</div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
