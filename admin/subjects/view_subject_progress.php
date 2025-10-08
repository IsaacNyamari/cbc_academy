<?php
require_once '../../includes/functions.php';
require_once '../../includes/config.php';
include '../../includes/header.php';

$student_progress = new Subject();
$student = new Student();

// Check if student_id is provided in GET
if (!isset($_GET['student_id'])) {
    // If not, get all student IDs
    $all_students = $student->getStudents(); // Assuming you have this method
    $subject_id = $_GET['subject_id'] ?? null;
    
    // Display progress for all students
    ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>

            <main class="col-md-9 mx-auto" style="width:85% !important; margin-left: 15% !important;">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">All Students' Progress</h1>
                </div>
                
                <?php foreach ($all_students as $student_data): 
                    $progress = $student_progress->getProgressByStudentId($student_data['id']);
                    if ($subject_id) {
                        $subject = $student_progress->getSubjectById($subject_id);
                        $chapter = $student_progress->getChapterBySubjectId($subject_id);
                    }
                ?>
                <div class="details mb-4">
                    <div class="row shadow p-3">
                        <div class="col-md-6">
                            <h3><?php echo htmlspecialchars($student_data['username']); ?></h3>
                            <h4>Status: <?php echo htmlspecialchars($progress['completion_status'] ?? 'Not started'); ?></h4>
                            <br>
                            <?php if ($subject_id): ?>
                            <h4><?php echo $subject['name'] ?? '' ?> Progress:</h4>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped" role="progressbar" style="width: <?php echo ($progress['completion_status'] ?? '') == "completed" ? 100 : 1; ?>%;">
                                    <span class="sr-only"><?php echo ($progress['completion_status'] ?? '') == "completed" ? 100 : 1; ?>% Complete</span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <?php if ($subject_id && !empty($chapter)): ?>
                            <h4>Chapters Covered:</h4>
                            <div class="list-group">
                                <?php foreach ($chapter as $ch): ?>
                                    <a href="../chapters/view.php?id=<?php echo $ch['id']; ?>" class="list-group-item list-group-item-action">
                                        <strong><?php echo htmlspecialchars($ch['title']); ?></strong>
                                    </a>
                                    <?php
                                    $topic = $student_progress->getTopicByChapter($ch['id']);
                                    foreach ($topic as $t): ?>
                                        <div class="list-group-item">
                                            <strong>Topic:</strong> <?php echo htmlspecialchars($t['title']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </main>
        </div>
    </div>
    <?php
    include '../../includes/footer.php';
    exit();
}

// Original code for when student_id is provided
$student_id = $_GET['student_id'];
$subject_id = $_GET['subject_id'] ?? null;
$student_info = $student->getStudentById($student_id);

if (!$student_info) {
    die("Student not found");
}

if ($student_id) {
    $progress = $student_progress->getProgressByStudentId($student_id);
    if ($subject_id) {
        $subject = $student_progress->getSubjectById($subject_id);
    }
}

$chapter = $student_progress->getChapterBySubjectId($subject_id);
$chapter_id = null;
foreach ($chapter as $ch) {
    $chapter_id = $ch['id'];
    break;
}
?>
 
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 mx-auto" style="width:85% !important; margin-left: 15% !important;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo ucfirst(htmlspecialchars($student_info['username'])); ?>`s Progress</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button class="btn btn-secondary" onclick="history.back()"><i class="fa fa-backward" aria-hidden="true"></i> Go Back</button>
                </div>
            </div>
            <!-- Student progress details direct to point -->
            <div class="details">
                <div class="row shadow p-3">
                    <div class="col-md-6">
                        <h3>Status: <?php echo htmlspecialchars($progress['completion_status']); ?></h3>
                        <br>
                        <h4><?php echo $subject['name'] ?> Progress:</h4>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped" role="progressbar" aria-valuenow="" aria-valuemin="" aria-valuemax="" style="width: <?php echo htmlspecialchars($progress['completion_status']) == "completed" ? 100 : 1; ?>%;">
                                <span class="sr-only"> <?php echo htmlspecialchars($progress['completion_status']) == "completed" ? 100 : 1; ?>% Complete</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <h4>Chapters Covered:</h4>
                        <div class="list-group">
                            <?php foreach ($chapter as $ch): ?>
                                <a href="../chapters/view.php?id=<?php echo $ch['id']; ?>" class="list-group-item list-group-item-action">
                                    <strong><?php echo htmlspecialchars($ch['title']); ?></strong>
                                </a>
                                <?php
                                $topic = $student_progress->getTopicByChapter($ch['id']);
                                foreach ($topic as $t): ?>
                                    <div class="list-group-item">
                                        <strong>Topic:</strong> <?php echo htmlspecialchars($t['title']); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php
include '../../includes/footer.php';