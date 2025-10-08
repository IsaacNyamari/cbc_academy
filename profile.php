<?php
require_once "includes/db.php"; // Database connection

// Get username from URL
$username = isset($_GET['username']) ? trim($_GET['username']) : '';

if (empty($username)) {
    die("Username not provided.");
}

// Fetch user details from existing table
$stmt = $stmt->prepare("
    SELECT full_name, username, email, profile_pic, role, created_at, subscription_plan, subscription_status
    FROM users
    WHERE username = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?> - Yeah Kenyan Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="assets/images/logo.png" type="image/x-icon">
    <style>
        <?php include "kenya-theme.css"; ?>
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-top: 100px;
        }
        .profile-card img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid var(--kenya-red);
            object-fit: cover;
            margin-bottom: 15px;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<?php include "navbar.php"; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="profile-card">
                <img src="<?= htmlspecialchars($user['profile_pic'] ?: 'assets/images/default-avatar.png') ?>" alt="Profile Picture">
                <h2><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></h2>
                <p class="text-muted">@<?= htmlspecialchars($user['username']) ?></p>

                <hr>
                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                <p><i class="fas fa-user-tag"></i> <?= ucfirst($user['role']) ?></p>
                <p>
                    <i class="fas fa-calendar-alt"></i> Joined on <?= date("F j, Y", strtotime($user['created_at'])) ?>
                </p>
                <p>
                    <i class="fas fa-credit-card"></i> <?= ucfirst($user['subscription_plan']) ?> Plan
                    <span class="status-badge <?= $user['subscription_status'] == 'active' ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                        <?= ucfirst($user['subscription_status']) ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>

<footer class="mt-5 kenya-flag-theme">
    <div class="container text-center py-3">
        <p class="mb-0">&copy; <?= date("Y") ?> Yeah Kenyan Academy. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
