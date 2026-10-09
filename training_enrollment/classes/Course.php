
<?php
// classes/Course.php

class Course
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all()
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM courses ORDER BY course_name"
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM courses WHERE course_id = :id"
        );

        $stmt->execute(['id' => $id]);

        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO courses
            (course_name, description)
            VALUES (:course_name, :description)"
        );

        return $stmt->execute([
            'course_name' => $data['course_name'],
            'description' => $data['description']
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE courses
            SET course_name = :course_name,
                description = :description
            WHERE course_id = :id"
        );

        return $stmt->execute([
            'course_name' => $data['course_name'],
            'description' => $data['description'],
            'id' => $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM courses WHERE course_id = :id"
        );

        return $stmt->execute(['id' => $id]);
    }
}