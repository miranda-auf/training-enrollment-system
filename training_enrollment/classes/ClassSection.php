<?php

class ClassSection
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function allWithCourse()
    {
        $sql = "SELECT c.*, co.course_name
                FROM classes c
                INNER JOIN courses co
                ON c.course_id = co.course_id
                ORDER BY co.course_name, c.class_code";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function create($course_id, $code, $schedule, $instructor, $slots)
    {
        $sql = "INSERT INTO classes
                (course_id, class_code, schedule, instructor, slots)
                VALUES
                (:course_id, :code, :schedule, :instructor, :slots)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'course_id' => $course_id,
            'code' => $code,
            'schedule' => $schedule,
            'instructor' => $instructor,
            'slots' => $slots
        ]);
    }

    public function getSlots($class_id)
    {
        $sql = "SELECT slots
                FROM classes
                WHERE class_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $class_id]);

        $row = $stmt->fetch();

        return $row ? (int) $row['slots'] : 0;
    }
}