<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    redirect('login.php');
}

// Get student details
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();

    if (!$student) {
        redirect('login.php');
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Edit Profile";
include '../../includes/header.php';
?>


<?php include '../../includes/sidebar.php'; ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Profile</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Profile updated successfully!</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form action="update.php" method="POST" id="editProfileForm" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name"
                        value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?php echo htmlspecialchars($student['email']); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                    value="<?php echo htmlspecialchars($student['username']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="profile_pic" class="form-label">Profile Picture</label>
                <input class="form-control" type="file" id="profile_pic" name="profile_pic" accept="image/*">
                <small class="text-muted">Max size 2MB. JPG, PNG, or GIF.</small>
            </div>
            <div id="image form-group mb-3">
                <img id="imagePreview" class="img-fluid mb-2"></img>
            </div>
            <div class="d-grid">
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
<script>
    let editProfileForm = document.getElementById("editProfileForm");
    editProfileForm.addEventListener("submit", (e) => {
        if (confirm("Your profile picture and other details will change forever. Are you sure?")) {
            return true
        } else {
            this.action = "#"
            e.preventDefault()
            return false
        }
    })


    let profile_pic = document.getElementById("profile_pic")

    let fileReader = new FileReader()
    profile_pic.addEventListener("change", (e) => {
        fileReader.onload = function(e) {
            let data = e.target.result;
            document.getElementById("imagePreview").src = data
        }
        fileReader.readAsDataURL(e.target.files[0]);
    })
</script>
<?php include '../../includes/footer.php'; ?>