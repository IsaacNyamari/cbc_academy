<?php
require_once '../../includes/config.php';

if (!isLoggedIn()) {
    header('Location:login.php');
}

// Only admin and teachers can access this page
if ($_SESSION['role'] === 'student') {
    header('Location:student/dashboard.php');
}

// Pagination setup
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$params = [];

if (!empty($search)) {
    $search_condition = "WHERE (username LIKE :search OR full_name LIKE :search OR email LIKE :search) AND role = 'student'";
    $params[':search'] = "%$search%";
} else {
    $search_condition = "WHERE role = 'teacher'";
}

try {
    // Get total count for pagination
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $search_condition");
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmt->execute();
    $total_teachers = $stmt->fetchColumn();

    $total_pages = ceil($total_teachers / $per_page);

    // Get teachers with pagination
    $query = "SELECT id, username, full_name, email, created_at,is_active
              FROM users $search_condition 
              ORDER BY created_at DESC 
              LIMIT :offset, :per_page";

    $stmt = $pdo->prepare($query);

    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }

    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();

    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle student activation/deactivation
if (isset($_POST['toggle_status'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $action = $_POST['action'];
    $is_active = $action === 'activate' ? 1 : 0;
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'teacher'");

        $stmt->execute([$is_active, $teacher_id]);

        $_SESSION['message'] = [
            'type' => 'success',
            'text' => "teachers account has been " . ($action === 'activate' ? 'activated' : 'deactivated') . " successfully!"
        ];

        header('Location: teachers.php');
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>

<?php include '../../includes/header.php'; ?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Teachers</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-toolbar gap-2">
                        <a onclick="exportUsers('pdf','teachers')" data-send="pdf" class="btn btn-sm btn-success">
                            <i class="fa fa-file-pdf" aria-hidden="true"></i> Export Pdf</a>
                        <a onclick="exportUsers('xls','teachers')" data-send="xls" class="btn btn-sm btn-success">
                            <i class="fa fa-file-excel" aria-hidden="true"></i> Export Excel</a>
                        <a onclick="exportUsers('csv','teachers')" data-send="csv" class="btn btn-sm btn-success">
                            <i class="fa fa-file-csv" aria-hidden="true"></i> Export CSV</a>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php unset($_SESSION['message']);
            endif; ?>

            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="teachers.php">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search by name, username or email..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="teachers.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <!-- Additional filters can be added here -->
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- teachers Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Teachers List</h5>
                    <small class="text-muted">Total: <?php echo $total_teachers; ?> teachers</small>
                </div>
                <div class="card-body">
                    <?php if (empty($teachers)): ?>
                        <div class="alert alert-info">No teachers found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Full Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Joined On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $index => $teacher): ?>
                                        <tr>
                                            <td><?php echo $offset + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($teacher['created_at'])); ?></td>

                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="view_teacher.php?id=<?php echo $teacher['id']; ?>" class="btn btn-info" title="View Profile">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                                        <a data-id="<?php echo $teacher['id']; ?>" data-bs-toggle="modal"
                                                            data-bs-target="#editUserDetailsModal" class="btn btn-primary" id="editUserDetailsButton" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                                            <input type="hidden" name="action" value="<?php echo $teacher['is_active'] ? 'deactivate' : 'activate'; ?>">
                                                            <button type="submit" name="toggle_status" class="btn btn-<?php echo $teacher['is_active'] ? 'warning' : 'success'; ?>" title="<?php echo $teacher['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                                <i class="fas fa-power-off"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                                    </li>

                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>