<?php
if (isset($_GET['student_id']) && isset($_GET['subject_id'])) {
    require_once '../../includes/config.php';
    require_once '../../includes/db.php';

    $student_id = sanitizeInput($_GET['student_id']);
    $subject_id = sanitizeInput($_GET['subject_id']);

    if (isLoggedIn() && isTeacher()) {
        try {
            $stmt = $pdo->prepare("DELETE FROM student_subjects WHERE student_id = :student_id AND subject_id = :subject_id");
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->execute();
            // delete the data from students_progress table and student_quiz_attempts table
            $stmt1 = $pdo->prepare("DELETE FROM student_progress WHERE student_id = :student_id");
            $stmt1->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt1->execute();
            $stmt2 = $pdo->prepare("DELETE FROM student_quiz_attempts WHERE student_id = :student_id");
            $stmt2->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt2->execute();
            if ($stmt2->rowCount() > 0) {
                header('Location: ./?status=success&message=Unenrollment successful');
            } else {
                header('Location: ./?status=error&message=Unenrollment failed or already unenrolled');
            }
        } catch (PDOException $e) {
            header('Location: ./?status=error&message=' . urlencode($e->getMessage()));
        }
    } else {
        header('Location: ../../login.php?error=Unauthorized access');
    }
} else {
    header('Location: ./?status=error&message=Invalid request');
}
