<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

$students = new Student();
$student_ids = $students->getStudents();


$subject_id = (int) $_GET['subject_id'];


foreach ($student_ids as $student) {
    $student_id = $student['id'];
    $student_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $student_stmt->execute([$student_id]);
    $student = $student_stmt->fetch();

    $subject_stmt = $pdo->prepare("SELECT `name` FROM subjects WHERE id = ?");
    $subject_stmt->execute([$subject_id]);
    $subject = $subject_stmt->fetch();

    $progress_stmt = $pdo->prepare("SELECT DISTINCT
        c.title AS chapter_title,
        t.title AS topic_title,
        s.name AS subject_name,
        u.id AS teacher,
        u.full_name as full_name,
        c.id AS chapter_id,
        sp.quiz_score,
        sp.completed_at,
        sp.completion_status,
        sp.student_id AS user
    FROM student_progress sp
    LEFT JOIN topics t ON sp.topic_id = t.id
    LEFT JOIN chapters c ON t.chapter_id = c.id
    LEFT JOIN subjects s ON c.subject_id = s.id
    LEFT JOIN users u ON s.created_by = u.id
    WHERE s.id = ? AND sp.student_id = ?");
    $progress_stmt->execute([$subject_id, $student_id]);
    $progress = $progress_stmt->fetchAll();

    $grouped = [];
    foreach ($progress as $row) {
        $grouped[$row['chapter_title']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Progress for <?= htmlspecialchars($student['full_name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>

<body>
    <div class="container my-5">
        <h2 class="mb-4">Progress for <?= $student['full_name'] ?> - <?= htmlspecialchars($subject['name']) ?></h2>

        <div class="d-flex justify-content-between mb-3">
            <div>
                <select id="filterStatus" class="form-select">
                    <option value="">-- Filter by Status --</option>
                    <option value="Completed">Completed</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Not Started">Not Started</option>
                </select>
            </div>
            <button class="btn btn-primary" id="downloadPDF">Download PDF</button>
        </div>

        <div id="progressData">
            <?php foreach ($grouped as $chapter => $topics): ?>
                <?php
                $total = count($topics);
                $completed = count(array_filter($topics, fn($r) => $r['completion_status'] === 'Completed'));
                ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <?= htmlspecialchars($chapter) ?> - <?= $completed ?>/<?= $total ?> Completed
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topics as $topic): ?>
                            <li class="list-group-item topic-row" data-status="<?= $topic['completion_status'] ?>">
                                <strong><?= htmlspecialchars($topic['topic_title']) ?></strong><br>
                                Score: <?= htmlspecialchars($topic['quiz_score']) ?? 'N/A' ?> <br>
                                Completed: <?= htmlspecialchars($topic['completed_at']) ?? 'Not yet' ?> <br>
                                Status: <span class="badge bg-info"><?= $topic['completion_status'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#filterStatus').on('change', function() {
                const filter = $(this).val();
                $('.topic-row').each(function() {
                    const status = $(this).data('status');
                    if (!filter || status === filter) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            $('#downloadPDF').click(function() {
                const element = document.getElementById('progressData');
                html2canvas(element).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const {
                        jsPDF
                    } = window.jspdf;
                    const pdf = new jsPDF();
                    const imgProps = pdf.getImageProperties(imgData);
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                    pdf.save("student-progress.pdf");
                });
            });
        });
    </script>

</body>

</html>