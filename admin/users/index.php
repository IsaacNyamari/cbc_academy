<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Only admin can access this page
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

// Pagination setup
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;
function encryptString($string, $key)
{
    $iv = random_bytes(16); // random initialization vector
    $ciphertext = openssl_encrypt($string, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $ciphertext);
}

function decryptString($encrypted, $key)
{
    $data = base64_decode($encrypted);
    $iv = substr($data, 0, 16);
    $ciphertext = substr($data, 16);
    return openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);
}
$term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$key = "SECURE_KEY_7644";

if (empty($term) || $term === '') {
    $encryptedSearch = '';
} else {
    $encryptedSearch = encryptString(trim($term), $key);
}

// Search functionality
$search = decryptString($encryptedSearch, $key);
$search_condition = '';
$params = [];

if (!empty($search)) {
    $search_condition = "WHERE (username LIKE :search OR full_name LIKE :search OR email LIKE :search) AND role IN ('teacher', 'student')";
    $params[':search'] = "%$search%";
} else {
    $search_condition = "WHERE role IN ('teacher', 'student')";
}

try {
    // Get total count for pagination
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $search_condition");
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    $total_users = $stmt->fetchColumn();

    $total_pages = ceil($total_users / $per_page);

    // Get users with pagination
    $query = "SELECT id, username, full_name, email, role, created_at, is_active
              FROM users $search_condition 
              ORDER BY role DESC, created_at DESC 
              LIMIT :offset, :per_page";

    $stmt = $pdo->prepare($query);

    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }

    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();

    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle user activation/deactivation
if (isset($_POST['toggle_status'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    $is_active = $action === 'activate' ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role IN ('teacher', 'student')");
        $stmt->execute([$is_active, $user_id]);

        $_SESSION['message'] = [
            'type' => 'success',
            'text' => "User account has been " . ($action === 'activate' ? 'activated' : 'deactivated') . " successfully!"
        ];

        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Users</h1>
               <div class="btn-toolbar gap-2">
                    <a onclick="exportUsers('pdf','users')" data-send="pdf" class="btn btn-sm btn-warning">
                        <i class="fa fa-file-pdf" aria-hidden="true"></i> Export Pdf</a>
                    <a onclick="exportUsers('xls','users')" data-send="xls" class="btn btn-sm btn-success">
                        <i class="fa fa-file-excel" aria-hidden="true"></i> Export Excel</a>
                    <a onclick="exportUsers('csv','users')" data-send="csv" class="btn btn-sm btn-dark">
                        <i class="fa fa-file-csv" aria-hidden="true"></i> Export CSV</a>
                </div>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search by name, username or email..." value="<?php echo $term; ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="./" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <!-- Role filter can be added here if needed -->
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Users List</h5>
                    <small class="text-muted">Total: <?php echo $total_users; ?> users</small>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="alert alert-info">No users found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Full Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined On</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $index => $user): ?>
                                        <tr>
                                            <td><?php echo $offset + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] === 'teacher' ? 'info' : 'primary'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="view_<?php echo $user['role']; ?>.php?id=<?php echo $user['id']; ?>" class="btn btn-info" title="View Profile">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a data-id="<?php echo $user['id']; ?>" data-bs-toggle="modal"
                                                        data-bs-target="#editUserDetailsModal" class="btn btn-primary" id="editUserDetailsButton" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="post" class="d-inline input-group">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="<?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?>">
                                                        <button type="submit" name="toggle_status" class="btn btn-<?php echo $user['is_active'] ? 'danger' : 'success'; ?>" title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                    </form>
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