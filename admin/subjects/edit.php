<?php
require_once '../../includes/config.php';


// Check if subject ID is provided
if (!isset($_GET['id'])) {
    header('Location: ./');
}

$subject_id = (int)$_GET['id'];
$errors = [];
$success = '';

// Get current subject data
try {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();
    
    if (!$subject) {
        header('Location: ./');
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    
    // Validation
    if (empty($name)) {
        $errors['name'] = 'Subject name is required';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Subject name must be less than 100 characters';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Description is required';
    }
    
    // Check if subject with this name already exists (excluding current subject)
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM subjects WHERE name = ? AND id != ?");
            $stmt->execute([$name, $subject_id]);
            if ($stmt->fetch()) {
                $errors['name'] = 'A subject with this name already exists';
            }
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
    
    // Update subject if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE subjects SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $subject_id]);
            
            $success = 'Subject updated successfully!';
            $_SESSION['success'] = $success;
            header('Location: ./view.php?id=' . $subject_id);
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
}

$page_title = "Edit Subject: " . htmlspecialchars($subject['name']);
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>
        
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Subject: <?php echo htmlspecialchars($subject['name']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Subjects
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Subject Name *</label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                   id="name" name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : htmlspecialchars($subject['name']); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                      id="description" name="description" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : htmlspecialchars($subject['description']); ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="view.php?id=<?php echo $subject_id; ?>" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>