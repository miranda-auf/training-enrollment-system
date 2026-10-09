
<?php
require_once __DIR__ . '/../config/db.php';

$message = '';
$error = '';
$editCourse = null;

// Load course for editing
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $stmt = $db->prepare(
        "SELECT * FROM courses WHERE course_id = ?"
    );
    $stmt->execute([(int) $_GET['edit']]);
    $editCourse = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editCourse) {
        $error = 'Course not found.';
    }
}

// Add or update course
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = trim($_POST['course_id'] ?? '');
    $courseCode = trim($_POST['course_code'] ?? '');
    $courseName = trim($_POST['course_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($courseCode === '' || $courseName === '') {
        $error = 'Course code and course name are required.';
    } elseif (strlen($courseCode) > 30 || strlen($courseName) > 100) {
        $error = 'Course code or course name is too long.';
    } else {
        try {
            if ($courseId !== '') {
                $stmt = $db->prepare(
                    "UPDATE courses
                     SET course_code = ?, course_name = ?, description = ?
                     WHERE course_id = ?"
                );
                $stmt->execute([
                    $courseCode,
                    $courseName,
                    $description,
                    (int) $courseId
                ]);

                header('Location: courses.php?message=updated');
                exit;
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO courses
                     (course_code, course_name, description)
                     VALUES (?, ?, ?)"
                );
                $stmt->execute([
                    $courseCode,
                    $courseName,
                    $description
                ]);

                header('Location: courses.php?message=added');
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'That course code already exists.';
            } else {
                $error = 'Unable to save the course. Please try again.';
            }
        }
    }

    $editCourse = [
        'course_id' => $courseId,
        'course_code' => $courseCode,
        'course_name' => $courseName,
        'description' => $description
    ];
}

// Success message
if (($_GET['message'] ?? '') === 'added') {
    $message = 'Course added successfully.';
} elseif (($_GET['message'] ?? '') === 'updated') {
    $message = 'Course updated successfully.';
}

// Get all courses with their class schedules and instructors
$courses = $db->query(
    "SELECT
        co.course_id,
        co.course_code,
        co.course_name,
        co.description,
        GROUP_CONCAT(
            CONCAT(
                c.class_code,
                ' - ',
                c.schedule,
                ' (Instructor: ',
                c.instructor,
                ', Slots: ',
                c.slots,
                ')'
            )
            ORDER BY c.class_code
            SEPARATOR '\n'
        ) AS class_details
     FROM courses co
     LEFT JOIN classes c ON co.course_id = c.course_id
     GROUP BY
        co.course_id,
        co.course_code,
        co.course_name,
        co.description
     ORDER BY co.course_id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$isEditing = $editCourse && !empty($editCourse['course_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            color: #243b55;
        }

        h1 {
            text-align: center;
            margin-top: 0;
        }

        .subtitle {
            text-align: center;
            color: #555;
            margin-bottom: 25px;
        }

        .navigation {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .navigation a, .button {
            display: inline-block;
            background: #2463a6;
            color: white;
            padding: 11px 15px;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .navigation a:hover, .button:hover {
            background: #174574;
        }

        .form-card {
            background: #edf4fc;
            padding: 22px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-weight: bold;
            color: #345;
            margin-bottom: 7px;
        }

        input, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccd5df;
            border-radius: 5px;
            font: inherit;
            background: white;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        .message {
            background: #e6f4ea;
            color: #24613a;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .error {
            background: #fdeaea;
            color: #9b2424;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 13px 12px;
            border-bottom: 1px solid #e1e6ec;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #edf4fc;
            color: #243b55;
        }

        tr:hover td {
            background: #fafcff;
        }

        .edit-link {
            color: #2463a6;
            font-weight: bold;
            text-decoration: none;
        }

        .edit-link:hover {
            text-decoration: underline;
        }

        .cancel-link {
            margin-left: 10px;
            color: #555;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            body {
                padding: 15px 10px;
            }

            .container {
                padding: 20px 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Course Management</h1>
    <p class="subtitle">
        Add, edit, and manage training courses.
    </p>

    <div class="navigation">
        <a href="../index.php">Dashboard</a>
        <a href="courses.php">Courses</a>
        <a href="classes.php">Classes</a>
        <a href="students.php">Register Student</a>
        <a href="enroll.php">Enroll Student</a>
        <a href="records.php">Enrollment Records</a>
    </div>

    <?php if ($message): ?>
        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <h2><?= $isEditing ? 'Edit Course' : 'Add New Course' ?></h2>

        <form method="POST" action="courses.php<?= $isEditing ? '?edit=' . (int) $editCourse['course_id'] : '' ?>">
            <input
                type="hidden"
                name="course_id"
                value="<?= htmlspecialchars((string) ($editCourse['course_id'] ?? '')) ?>"
            >

            <div class="form-group">
                <label for="course_code">Course Code</label>
                <input
                    type="text"
                    id="course_code"
                    name="course_code"
                    maxlength="30"
                    required
                    value="<?= htmlspecialchars($editCourse['course_code'] ?? '') ?>"
                    placeholder="e.g. PHP101"
                >
            </div>

            <div class="form-group">
                <label for="course_name">Course Name</label>
                <input
                    type="text"
                    id="course_name"
                    name="course_name"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars($editCourse['course_name'] ?? '') ?>"
                    placeholder="e.g. PHP Programming"
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="Enter a short course description"
                ><?= htmlspecialchars($editCourse['description'] ?? '') ?></textarea>
            </div>

            <button class="button" type="submit">
                <?= $isEditing ? 'Update Course' : 'Add Course' ?>
            </button>

            <?php if ($isEditing): ?>
                <a class="cancel-link" href="courses.php">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <h2>Course List</h2>

    <div class="table-wrap">
        <table>
            <thead>
    <tr>
        <th>ID</th>
        <th>Course Code</th>
        <th>Course Name</th>
        <th>Description</th>
        <th>Class Schedule & Instructor</th>
        <th>Action</th>
    </tr>
</thead>
            <tbody>
                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="6">No courses found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?= (int) $course['course_id'] ?></td>
                            <td><?= htmlspecialchars($course['course_code']) ?></td>
                            <td><?= htmlspecialchars($course['course_name']) ?></td>
                            <td><?= htmlspecialchars($course['description'] ?? '') ?></td>
<td style="white-space: pre-line;">
    <?= htmlspecialchars($course['class_details'] ?? 'No classes assigned') ?>
</td>
                            <td>
                                <a class="edit-link"
                                   href="courses.php?edit=<?= (int) $course['course_id'] ?>">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>