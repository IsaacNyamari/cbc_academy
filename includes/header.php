<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define trial period if not defined
if (!defined('TRIAL_PERIOD')) {
    define('TRIAL_PERIOD', 14); // 14-day trial
}

if (isset($_SESSION['role']) && $_SESSION['role'] === "student") {
    $userId = $_SESSION["user_id"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Example: update session with fresh data if needed
        $is_active = $user["is_active"] === "1" ? "approved" : "unapproved";
        $subscription_status = $user["subscription_status"];
        $created_at = $user["created_at"];
    }
    // var_dump($pdo);
    $now = new DateTime();
    $dateCreated = new DateTime($created_at);

    // Calculate trial end date
    $trialEndDate = (clone $dateCreated)->modify('+' . TRIAL_PERIOD . ' days');

    if ($now < $trialEndDate) {
        // Trial still active
        $daysRemaining = $now->diff($trialEndDate)->days;
        // Optionally use $daysRemaining to show remaining time
    } else {
        // Trial expired
        if (isset($subscription_status) && $subscription_status === "inactive") {
            // Redirect to payment
            header("Location: ../../payment.php");
            exit;
        }
    }
    if ($is_active) {
        $account_status =  $is_active;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yeah Kenyan Academy | Dashboard</title>
    <link rel="shortcut icon" href="../../assets/images/logo.png" type="image/x-icon">
    <!-- Bootstrap 5 -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../assets/css/fontawesome/css/all.min.css">

    <link rel="stylesheet" href="../../assets/css/adminlte.min.css"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="../../includes/style.css">
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">

    <div class="app-wrapper">

        <!-- Navbar -->
        <nav class="app-header navbar navbar-expand bg-dark navbar-dark">
            <!-- Sidebar toggle -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <li>
                    <a class="btn btn-success">
                        <?php echo $_SESSION['role'] === 'admin' ? 'Admin' : ucfirst($_SESSION['role']); ?> Dashboard
                    </a>
                </li>
            </ul>

            <!-- Right side -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo htmlspecialchars($_SESSION["full_name"] ?? $_SESSION["role"]); ?>
                        <i class="fas fa-user-circle ms-2"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="<?php echo "../../" . strtolower($_SESSION['role']) . "/profile/" ?>">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>


        </nav>
        <!-- /.navbar -->