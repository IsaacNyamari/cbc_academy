<?php
require_once '../../includes/config.php';


$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $system_id = sanitizeInput($_POST['system_id']);
    $description = sanitizeInput($_POST['description']);
    $created_by = $_SESSION['user_id'];
    // Validation
    if (empty($name)) {
        $errors['name'] = 'Subject name is required';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Subject name must be less than 100 characters';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Description is required';
    }
    
    // Check if subject already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM subjects WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                $errors['name'] = 'A subject with this name already exists';
            }
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
    
    // Create subject if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (name, description, system_id, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $system_id, $_SESSION['user_id']]);

            $subject_id = $pdo->lastInsertId();
            $success = 'Subject created successfully!';
            
            // header('Location: view.php?id=' . $subject_id); to view page after creation
            $_SESSION['success'] = $success;
            header('Location: view.php?id=' . $subject_id);
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
}

$page_title = "Create New Subject";
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Create New Subject</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subjects
                    </a>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Subject Name *</label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                   id="name" name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                      id="description" name="description" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Subject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>