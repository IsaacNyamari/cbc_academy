<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'student' ? 'student/dashboard.php' : 'admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION["created_at"] = $user["created_at"];
            $_SESSION["is_active"] = $user["is_active"] ? "approved" : "unapproved";
            $_SESSION["subscription_status"] = $user["subscription_status"];
            $_SESSION['avatar'] = $user['profile_pic']; // Use default avatar if none set
            $_SESSION["is_logged_in"] = true;
            redirect($user['role'] === 'student' ? 'student/dashboard.php' : 'admin/dashboard.php');
        } else {
            $error = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeah Kenyan Academy - Login</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome/css/all.min.css">
    <style>
        body {
            background: url("assets/images/background.png");
            height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .login-header {
            background-color: #fff;
            padding: 30px;
            text-align: center;
        }

        .login-body {
            background-color: #f8f9fa;
            padding: 30px;
        }

        .logo {
            width: 100px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <div class="login-header">
                        <img src="assets/images/logo.png" alt="Yeah Kenyan Academy Logo" class="logo">
                        <h3>Yeah Kenyan Academy</h3>
                        <p class="text-muted">Sign in to continue</p>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label"><i class="fas fa-user"></i> Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label"><i class="fas fa-lock"></i> Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-sign-in-alt"></i> Login</button>
                            </div>
                            <div class="text-center">
                                <a href="register.php" class="text-decoration-none">Don't have an account? Register</a> || <a href="reset_pass.php" class="text-decoration-none">Forgot Password?</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>