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
    $search_condition = "WHERE role = 'student'";
}

try {
    // Get total count for pagination
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $search_condition");
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmt->execute();
    $total_students = $stmt->fetchColumn();

    $total_pages = ceil($total_students / $per_page);

    // Get students with pagination
    $query = "
SELECT 
    u.id,
    u.username,
    latest_p.transaction_code AS transaction_code,
    u.subscription_status AS subscription_status,
    u.full_name,
    u.email,
    u.created_at,
    u.is_active
FROM users u
LEFT JOIN (
    SELECT p1.*
    FROM payments p1
    INNER JOIN (
        SELECT student_id, MAX(date_paid) AS latest_date
        FROM payments
        GROUP BY student_id
    ) p2 ON p1.student_id = p2.student_id AND p1.date_paid = p2.latest_date
) latest_p ON u.id = latest_p.student_id
$search_condition
ORDER BY u.created_at DESC
LIMIT :offset, :per_page";


    $stmt = $pdo->prepare($query);

    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }

    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();

    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle student activation/deactivation
if (isset($_POST['toggle_status'])) {
    $student_id = (int)$_POST['student_id'];
    $action = $_POST['action'];

    $is_active = $action === 'activate' ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE users SET `is_active` = ? WHERE id = ? AND role = 'student'");
        $stmt->execute([$is_active, $student_id]);

        $_SESSION['message'] = [
            'type' => 'success',
            'text' => "Student account has been " . ($action === 'activate' ? 'activated' : 'deactivated') . " successfully!"
        ];

        header('Location:./students.php');
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>

<?php include '../../includes/header.php'; ?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Students</h1>
              <div class="btn-toolbar gap-2">
                    <a onclick="exportUsers('pdf','students')" data-send="pdf" class="btn btn-sm btn-warning">
                        <i class="fa fa-file-pdf" aria-hidden="true"></i> Export Pdf</a>
                    <a onclick="exportUsers('xls','students')" data-send="xls" class="btn btn-sm btn-success">
                        <i class="fa fa-file-excel" aria-hidden="true"></i> Export Excel</a>
                    <a onclick="exportUsers('csv','students')" data-send="csv" class="btn btn-sm btn-dark">
                        <i class="fa fa-file-csv" aria-hidden="true"></i> Export CSV</a>
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
                    <form method="get" action="students.php">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search by name, username or email..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="students.php" class="btn btn-outline-secondary">
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

            <!-- Students Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Students List</h5>
                    <small class="text-muted">Total: <?php echo $total_students; ?> students</small>
                </div>
                <div class="card-body">
                    <?php if (empty($students)): ?>
                        <div class="alert alert-info">No students found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Full Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Payment Code</th>
                                        <th>Subscription Status</th>
                                        <th>Joined On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $index => $student): ?>
                                        <tr>
                                            <td><?php echo $offset + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td>
                                                <?php if ($student['transaction_code']): ?>
                                                    <a name="checkTransactionStatusButton" id="checkTransactionStatusButton" data-target="modal" data-toggle="#paymentModal" onclick="checkStatus('<?php echo $student['transaction_code'] ?>')" class="btn btn-primary" href="#" role="button"><?php echo $student['transaction_code'] ?></a>
                                                    <p class="form-text text-muted">
                                                        Click to check transaction is valid.
                                                    </p>
                                                <?php endif ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['subscription_status']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($student['created_at'])); ?></td>

                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-info" title="View Profile">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="login_as_student.php?email=<?php echo $student['email']; ?>" class="btn btn-danger" title="Login as <?php echo $student['email']?>">
                                                        <i class="fas fa-sign-in-alt"></i>
                                                    </a>
                                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                                        <a data-id="<?php echo $student['id']; ?>" data-bs-toggle="modal"
                                                            data-bs-target="#editUserDetailsModal" class="btn btn-primary" id="editUserDetailsButton" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="post" class="d-inline">
                                                            <div class="input-group">
                                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                                <input type="hidden" name="action" value="<?php echo $student['is_active'] ? 'deactivate' : 'activate'; ?>">
                                                                <button type="submit" name="toggle_status" class="btn btn-<?php echo $student['is_active'] ? 'danger' : 'success'; ?>" title="<?php echo $student['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                                    <i class="fas fa-power-off"></i>
                                                                </button>
                                                            </div>

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
<!-- Add this modal HTML to your page -->


<div class="modal fade" id="paymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">


        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Payment Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="payment-details">
                    <div id="receiptContent">
                        <div class="payment-details">
                            <div class="detail-row d-flex justify-content-between">
                                <span class="detail-label fw-bold">Reference:</span>
                                <span class="detail-value" id="refNumber"></span>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span class="detail-label fw-bold">Amount:</span>
                                <span class="detail-value" id="amountPaid"></span>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span class="detail-label fw-bold">Payment Method:</span>
                                <span class="detail-value" id="paymentMethod"></span>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span class="detail-label fw-bold">Date/Time:</span>
                                <span class="detail-value" id="paymentDate"></span>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span class="detail-label fw-bold">Email:</span>
                                <span class="detail-value" id="customerEmail"></span>
                            </div>
                            <div class="detail-row d-flex justify-content-between">
                                <span class="detail-label fw-bold">Status:</span>
                                <span class="detail-value text-success fw-bold">Success</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <button id="printReceipt" class="btn btn-secondary me-2">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button id="downloadPDF" class="btn btn-primary">
                            <i class="fas fa-file-pdf"></i> Download PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>



<style>
    /* Payment Details Styles */
    .payment-details {
        margin: 20px 0;
    }

    .detail-row {
        display: flex;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #eee;
    }

    .detail-label {
        font-weight: bold;
        width: 120px;
        color: #555;
    }

    .detail-value {
        flex: 1;
    }

    .status-success {
        color: #2ecc71;
        font-weight: bold;
    }
</style>

<script>
    function checkStatus(reference) {
        fetch('../../transaction_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reference: String(reference)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    showPaymentDetails(data.transaction);
                } else {
                    console.error('Verification failed:', data.message);
                    alert('Payment verification failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while verifying payment');
            });
    }

    function showPaymentDetails(transaction) {

        // Format the amount (assuming it's in cents)
        const amountInShillings = (transaction.amount / 100).toLocaleString('en-KE', {
            style: 'currency',
            currency: transaction.currency
        });

        // Format the date
        const paymentDate = new Date(transaction.paid_at);
        const formattedDate = paymentDate.toLocaleString('en-KE', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        // Format payment method
        const paymentMethod = formatPaymentMethod(transaction.channel);

        // Update modal content
        document.getElementById("refNumber").textContent = transaction.reference;
        document.getElementById("amountPaid").textContent = amountInShillings;
        document.getElementById("paymentMethod").textContent = paymentMethod;
        document.getElementById("paymentDate").textContent = formattedDate;
        document.getElementById("customerEmail").textContent = transaction.customer.email;

        // Show the modal (Bootstrap 5)
        const myModal = new bootstrap.Modal(document.getElementById('paymentModal'));
        myModal.show();
    }

    function formatPaymentMethod(channel) {
        const methods = {
            'mobile_money': 'Mobile Money',
            'card': 'Credit/Debit Card',
            'bank_transfer': 'Bank Transfer',
            'ussd': 'USSD'
        };
        return methods[channel] || channel;
    }
</script>


<?php include '../../includes/footer.php'; ?>