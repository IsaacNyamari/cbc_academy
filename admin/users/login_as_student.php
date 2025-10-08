<?php
require "../../includes/functions.php";

if (isset($_GET['email'])) {
    $email = $_GET['email'];

    class LoginAsStudent extends Dbh
    {
        public function loginAsStudent($email)
        {
            // Connect to DB
            $pdo = $this->connect();
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                die("User not found.");
            }

            session_start();


            $adminEmail = $_SESSION["email"];
            session_unset();
            session_destroy();

            // Start a new session with a new session ID
            session_start();
            session_regenerate_id(true);
            // Set new session variables
            $_SESSION["adminEmail"] = $adminEmail;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION["created_at"] = $user["created_at"];
            $_SESSION["is_active"] = $user["is_active"] ? "approved" : "unapproved";
            $_SESSION["subscription_status"] = $user["subscription_status"];
            $_SESSION['avatar'] = $user['profile_pic'] ?? 'default.png';
            $_SESSION["is_logged_in"] = true;

            // Redirect to student dashboard
            header("Location: ../../student/dashboard.php");
            exit;
        }
    }

    $login = new LoginAsStudent();
    $login->loginAsStudent($email);
} else {
    echo "No email provided.";
}
