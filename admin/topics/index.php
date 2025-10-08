<?php
require_once '../../includes/config.php';

if (!isLoggedIn() || !isTeacher()) {
    redirect('login.php');
}

// Get all topics with subject and chapter information
try {
    if (isAdmin()) {
        $stmt = $pdo->prepare("
            SELECT t.*, c.title AS chapter_title, s.name AS subject_name 
            FROM topics t
            JOIN chapters c ON t.chapter_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            ORDER BY s.name, c.sequence, t.sequence
        ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT t.*, c.title AS chapter_title, s.name AS subject_name 
            FROM topics t
            JOIN chapters c ON t.chapter_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            WHERE s.created_by = ?
            ORDER BY s.name, c.sequence, t.sequence
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $topics = $stmt->fetchAll();

    // Get subjects for filter dropdown
    $stmt = $pdo->prepare("
        SELECT id, name FROM subjects 
        WHERE created_by = ? OR ? = 1
        ORDER BY name
    ");
    $stmt->execute([$_SESSION['user_id'], isAdmin() ? 1 : 0]);
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$page_title = "Manage Topics";
include '../../includes/header.php';
?>

 
        <?php include '../../includes/sidebar.php'; ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Topics</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTopicModal">
                        <i class="fas fa-plus"></i> Add New Topic
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Topic has been <?php echo $_GET['success']; ?> successfully.</div>
            <?php endif; ?>

            <div class="card mb-3">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="subjectFilter" class="form-label">Filter by Subject</label>
                            <select class="form-select" id="subjectFilter" name="subject">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo isset($_GET['subject']) && $_GET['subject'] == $subject['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="searchFilter" class="form-label">Search</label>
                            <input type="text" class="form-control" id="searchFilter" name="search" placeholder="Search topics..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sync-alt"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Topic Title</th>
                                    <th>Chapter</th>
                                    <th>Subject</th>
                                    <th>Sequence</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topics)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No topics found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($topics as $index => $topic): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($topic['title']); ?></td>
                                            <td><?php echo htmlspecialchars($topic['chapter_title']); ?></td>
                                            <td><?php echo htmlspecialchars($topic['subject_name']); ?></td>
                                            <td><?php echo $topic['sequence']; ?></td>
                                            <td>
                                                <a href="view.php?id=<?php echo $topic['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a data-id="<?php echo $topic['id']; ?>" data-bs-toggle="modal" data-bs-target="#editTopicModal" class="btn btn-sm btn-warning" title="Edit" id="editTopicButton">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $topic['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this topic?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Topic Modal -->
<div class="modal fade" id="addTopicModal" tabindex="-1" aria-labelledby="addTopicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="create.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTopicModalLabel">Add New Topic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="chapter_id" class="form-label">Chapter</label>
                            <select class="form-select" id="chapter_id" name="chapter_id" required>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Topic Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="sequence" class="form-label">Sequence</label>
                        <input type="number" class="form-control" id="sequence" name="sequence" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="video_url" class="form-label">Video URL (optional)</label>
                        <input type="url" class="form-control" id="video_url" name="video_url" placeholder="https://youtube.com/embed/...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Topic</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Topic Modal -->
<div class="modal fade" id="editTopicModal" tabindex="-1" data-bs-backdrop="static" aria-labelledby="editTopicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="update.php" method="POST" id="editTopicForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTopicModalLabel">Edit Topic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <input type="hidden" id="edit_topic_id" name="edit_topic_id">
                </div>
                <div class="modal-body">
                    <input type="hidden" name="topic_id" id="topicId">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Topic Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_sequence" class="form-label">Sequence</label>
                        <input type="number" class="form-control" id="edit_sequence" name="sequence" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_content" class="form-label">Content</label>
                        <textarea class="form-control" id="edit_content" name="content" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_video_url" class="form-label">Video URL (optional)</label>
                        <input type="url" class="form-control" id="edit_video_url" name="video_url" placeholder="https://youtube.com/embed/...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Topic</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // AJAX to load chapters based on selected subject
    document.getElementById('subject_id').addEventListener('change', function() {
        const subjectId = this.value;
        const chapterSelect = document.getElementById('chapter_id');

        if (subjectId) {
            fetch(`get_chapters.php?subject_id=${subjectId}`)
                .then(response => response.json())
                .then(data => {
                    chapterSelect.innerHTML = '<option value="">Select Chapter</option>';
                    data.forEach(chapter => {
                        const option = document.createElement('option');
                        option.value = chapter.id;
                        option.textContent = chapter.title;
                        chapterSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error:', error));
        } else {
            chapterSelect.innerHTML = '<option value="">Select Chapter</option>';
        }
    });
    let editTopicButtons = document.querySelectorAll("#editTopicButton")
    editTopicButtons.forEach(editTopicButton => {
        editTopicButton.addEventListener("click", () => {
            let topic_id = editTopicButton.getAttribute("data-id");
            let edit_video_url = document.getElementById("edit_video_url")
            let edit_content = document.getElementById("edit_content")
            let edit_sequence = document.getElementById("edit_sequence")
            let edit_title = document.getElementById("edit_title")
            let edit_topic_id = document.getElementById("edit_topic_id")
            fetch("get_topic.php", {
                method: "POST",
                body: JSON.stringify({
                    topic_id: topic_id
                })
            }).then(res => res.json()).then(data => {
                edit_content.value = data.content
                edit_sequence.value = data.sequence
                edit_title.value = data.title
                edit_topic_id.value = data.topic_id
                data.video_url ? edit_video_url.value = data.video_url : edit_video_url.setAttribute("placeholder", "This topic has no video_url. Add url")
            }).catch(err => {
                logger(err);
            })
        })


    });
    let editTopicForm = document.getElementById("editTopicForm")
    editTopicForm.addEventListener("submit", (e) => {
        e.preventDefault();
        let formData = new FormData(editTopicForm);
        fetch("edit_topic.php", {
            method: "POST",
            body: formData
        }).then(res => res.json()).then(data => {
            alert(data.message)
        }).catch(err => {
            logger(err)
        })
    })

    function logger(query) {
        console.log(query);
    }
</script>

<?php include '../../includes/footer.php'; ?>