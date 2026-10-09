
<?php
// classes/Enrollment.php

class Enrollment
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all()
    {
        $stmt = $this->db->prepare(
            "SELECT e.*,
                    s.full_name,
                    c.course_name
             FROM enrollments e
             INNER JOIN students s
                ON e.student_id = s.student_id
             INNER JOIN courses c
                ON e.course_id = c.course_id
             ORDER BY e.enrollment_id DESC"
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM enrollments
             WHERE enrollment_id = :id"
        );

        $stmt->execute(['id' => $id]);

        return $stmt->fetch();
    }

    public function create($studentId, $courseId)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO enrollments
                (student_id, course_id)
             VALUES
                (:student_id, :course_id)"
        );

        return $stmt->execute([
            'student_id' => $studentId,
            'course_id' => $courseId
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM enrollments
             WHERE enrollment_id = :id"
        );

        return $stmt->execute(['id' => $id]);
    }
}