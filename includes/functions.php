<?php
class Dbh
{
    protected $host = 'localhost';
    protected $dbname = 'cbc_system';
    protected $username = 'root';
    protected $password = '';
/*



<?php
$host = 'localhost';
$dbname = 'gumyombf_cbc_system';
$username = 'gumyombf_cbc_system';
$password = 'gumyombf_cbc_system';




*/
    public function connect()
    {
        try {
            $pdo = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}
class Questions extends Dbh
{
    public function getQuestionByTopicId($question_id)
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
        $stmt->execute([$question_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
class Subject extends Dbh
{
    public function getTopicByChapter($chapter_id)
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("SELECT * FROM topics WHERE chapter_id = ?");
        $stmt->execute([$chapter_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getChapterBySubjectId($subject_id)
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("SELECT * FROM chapters WHERE subject_id = ?");
        $stmt->execute([$subject_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getProgressByStudentId($student_id)
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("SELECT * FROM student_progress WHERE student_id = ?");
        $stmt->execute([$student_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getSubjectById($subject_id)
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getSubjectsByTeacherId($teacher_id)
    {
        try {
            $pdo = $this->connect();
            $stmt = $pdo->prepare("SELECT * FROM subjects WHERE created_by = :teacher_id");
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Optional: log error or return a custom error message
            return [];
        }
    }
}
class Student extends Dbh
{
    public function getStudentById($student_id)
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$student_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getStudents()
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE `role` = 'student'");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
