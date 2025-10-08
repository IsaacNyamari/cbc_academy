<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!-- Brand -->
    <div class="sidebar-brand">
        <a href="#" class="brand-link">
            <i class="fas fa-graduation-cap ms-2"></i>
            <span class="brand-text fw-bold">Yeah Academy</span>
        </a>
    </div>
    <!-- Sidebar Menu -->
    <div class="sidebar">
        <nav class="navbar-dark">
            <ul class="nav nav-pills nav-sidebar flex-column" data-lte-toggle="treeview" role="menu">

                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php'
                                                        || $_SERVER['REQUEST_URI'] === BASE_URL . ($_SESSION['role'] === 'student' ? 'student/dashboard.php' : 'admin/dashboard.php'))
                                                        ? 'active' : ''; ?>"
                        href="<?php echo BASE_URL . ($_SESSION['role'] === 'student' ? 'student/dashboard.php' : 'admin/dashboard.php'); ?>">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>

                <?php if ($_SESSION["role"] === "support"): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/messages/') !== false ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>admin/messages/">
                            <i class="fa fa-comment me-2"></i> Messages
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'student'): ?>
                    <!-- Student Menu -->
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/student/subjects/') !== false ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>student/subjects/">
                            <i class="fas fa-book me-2"></i> My Subjects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/student/progress/') !== false ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>student/progress/">
                            <i class="fas fa-chart-line me-2"></i> My Progress
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/student/quizzes/') !== false ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>student/quizzes/">
                            <i class="fas fa-question-circle me-2"></i> Quizzes
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Admin/Teacher Menu -->
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/subjects/') !== false ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>admin/subjects/">
                            <i class="fas fa-book me-2"></i> Subjects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/topics/') !== false ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>admin/topics/">
                            <i class="fas fa-file-alt me-2"></i> Topics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/quizzes/') !== false ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>admin/quizzes/">
                            <i class="fas fa-question-circle me-2"></i> Quizzes
                        </a>
                    </li>

                    <!-- Collapsible User Management -->
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher'): ?>
                        <?php
                        $userActive = strpos($_SERVER['REQUEST_URI'], '/admin/users/') !== false ? 'active' : '';
                        $menuOpen   = strpos($_SERVER['REQUEST_URI'], '/admin/users/') !== false ? 'menu-open' : '';
                        ?>
                        <li class="nav-item has-treeview <?php echo $menuOpen; ?>">
                            <a href="#" class="nav-link text-white <?php echo $userActive; ?>">
                                <i class="fas fa-users me-2"></i>

                                User Management
                                <i class="right fas fa-angle-left"></i>

                            </a>
                            <ul class="nav nav-treeview ms-3">
                                <li class="nav-item">
                                    <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/users/students.php') !== false ? 'active' : ''; ?>"
                                        href="<?php echo BASE_URL; ?>admin/users/students.php">
                                        <i class="fa fa-graduation-cap me-2"></i> Students
                                    </a>
                                </li>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <li class="nav-item">
                                        <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/users/teachers.php') !== false ? 'active' : ''; ?>"
                                            href="<?php echo BASE_URL; ?>admin/users/teachers.php">
                                            <i class="fas fa-chalkboard-teacher me-2"></i> Teachers
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/settings/') !== false ? 'active' : ''; ?>"
                            href="<?php echo BASE_URL; ?>admin/settings/">
                            <i class="fas fa-cog me-2"></i> System Settings
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Profile -->
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/profile/') !== false ? 'active' : ''; ?>"
                        href="<?php echo BASE_URL . ($_SESSION['role'] === 'student' ? 'student/profile/' : 'admin/profile/'); ?>">
                        <i class="fas fa-user-circle me-2"></i> Profile
                    </a>
                </li>

            </ul>


            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                <span>Help & Support</span>
            </h6>
            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/faq') !== false ? 'active' : ''; ?>" href="#">
                        <i class="fas fa-question-circle me-2"></i>
                        FAQ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo strpos($_SERVER['REQUEST_URI'], '/contact') !== false ? 'active' : ''; ?>" href="#">
                        <i class="fas fa-envelope me-2"></i>
                        Contact
                    </a>
                </li>
            </ul>
        </nav>
    </div>

</aside>

<!-- Content Wrapper -->
<main class="app-main">
    <div class="app-content-header">
        <div class="app-content-header">
            <div class="container-fluid">
              
            </div>
        </div>