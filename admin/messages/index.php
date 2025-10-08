<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Only admin can access settings
if ($_SESSION['role'] !== 'support') {
    header('Location: ../dashboard.php');
    exit();
}

// Initialize message variable
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Update each setting
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $name = substr($key, 8);
                $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE name = ?");
                $stmt->execute([trim($value), $name]);
            }
        }

        $pdo->commit();

        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Settings updated successfully!'
        ];

        // Refresh to show updated values
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Error updating settings: ' . $e->getMessage()
        ];
    }
}

// Get all system settings
try {
    $stmt = $pdo->query("SELECT * FROM site_settings");
    $settings = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include '../../includes/header.php';
?>
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Messages</h1>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message']['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); ?>

            <?php endif; ?>
            <div class="table-responsive">
                <div class="container">
                    <div class="row">
                        <div class="col-sm-2 p-1 border-end" style="height: 600px!important;">
                            <div class="messages-holder">
                                <i class="fa fa-user userImage" aria-hidden="true"></i>
                                <span class="userName">Isaac Nyamari
                                    <i class="d-block">Hello..</i>
                                </span>
                            </div>
                            <div class="messages-holder">
                                <i class="fa fa-user userImage" aria-hidden="true"></i>
                                <span class="userName">Isaac Nyamari
                                    <i class="d-block">I need to..</i>
                                </span>
                            </div>
                        </div>
                        <div class="col-sm-10 chatContainer">
                            <div class="messages">
                                <div class="supportMessage">Hello how can I help!</div>
                                <div class="myMessage">I need to know how to code</div>
                            </div>
                            <div class="card-footer chat-footer supportChatFooter">
                                <i class="fa fa-paperclip fa-2x attachFile" aria-hidden="true"></i>
                                <div class="inputWrapper">
                                    <textarea class="chatInput" rows="1"></textarea>
                                </div>
                                <i class="fas fa-paper-plane sendMessageBtn fa-2x" id="sendMessageBtn"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>


<script>
    // Show/hide options field based on input type selection
    document.getElementById('input_type').addEventListener('change', function() {
        const optionsContainer = document.getElementById('optionsContainer');
        optionsContainer.style.display = this.value === 'select' ? 'block' : 'none';
    });
</script>

<?php include '../../includes/footer.php'; ?>