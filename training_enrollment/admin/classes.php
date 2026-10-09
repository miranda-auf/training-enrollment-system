
<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$classModel = new ClassSection($db);
$error = '';
$message = '';
$editClass = null;

// Load class for editing
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $stmt = $db->prepare(
        "SELECT * FROM classes WHERE class_id = ?"
    );
    $stmt->execute([(int) $_GET['edit']]);
    $editClass = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editClass) {
        $error = 'Class not found.';
    }
}

// Add or update class
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classId = trim($_POST['class_id'] ?? '');
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $classCode = trim($_POST['class_code'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');
    $instructor = trim($_POST['instructor'] ?? '');
    $slotsInput = trim($_POST['slots'] ?? '');

    if (
        $courseId < 1 ||
        $classCode === '' ||
        $schedule === '' ||
        $instructor === '' ||
        $slotsInput === ''
    ) {
        $error = 'Please complete all fields.';
    } elseif (
        strlen($classCode) > 50 ||
        strlen($schedule) > 100 ||
        strlen($instructor) > 100
    ) {
        $error = 'One or more fields are too long.';
    } elseif (
        !ctype_digit($slotsInput) ||
        (int) $slotsInput < 0
    ) {
        $error = 'Slots must be a non-negative whole number.';
    } else {
        $courseCheck = $db->prepare(
            "SELECT COUNT(*) FROM courses WHERE course_id = ?"
        );
        $courseCheck->execute([$courseId]);

        if (!$courseCheck->fetchColumn()) {
            $error = 'Please select a valid course.';
        } else {
            try {
                if ($classId !== '') {
                    $db->beginTransaction();

                    $lock = $db->prepare(
                        "SELECT class_id FROM classes
                         WHERE class_id = ? FOR UPDATE"
                    );
                    $lock->execute([(int) $classId]);

                    if (!$lock->fetchColumn()) {
                        throw new RuntimeException('Class not found.');
                    }

                    $stmt = $db->prepare(
                        "UPDATE classes
                         SET course_id = ?, class_code = ?,
                             schedule = ?, instructor = ?, slots = ?
                         WHERE class_id = ?"
                    );
                    $stmt->execute([
                        $courseId,
                        $classCode,
                        $schedule,
                        $instructor,
                        (int) $slotsInput,
                        (int) $classId
                    ]);

                    $db->commit();
                    header('Location: classes.php?message=updated');
                    exit;
                } else {
                    $classModel->create(
                        $courseId,
                        $classCode,
                        $schedule,
                        $instructor,
                        (int) $slotsInput
                    );

                    header('Location: classes.php?message=added');
                    exit;
                }
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                if (
                    $e instanceof PDOException &&
                    $e->getCode() === '23000'
                ) {
                    $error = 'Unable to save. Check the course and class details.';
                } else {
                    $error = 'Unable to save the class. Please try again.';
                }
            }
        }
    }

    $editClass = [
        'class_id' => $classId,
        'course_id' => $courseId,
        'class_code' => $classCode,
        'schedule' => $schedule,
        'instructor' => $instructor,
        'slots' => $slotsInput
    ];
}

// Success messages
if (($_GET['message'] ?? '') === 'added') {
    $message = 'Class added successfully.';
} elseif (($_GET['message'] ?? '') === 'updated') {
    $message = 'Class updated successfully.';
}

// Load courses and classes
$courses = $db->query(
    "SELECT course_id, course_code, course_name
     FROM courses ORDER BY course_name"
)->fetchAll(PDO::FETCH_ASSOC);

$classes = $classModel->allWithCourse();

$isEditing = $editClass && !empty($editClass['class_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management</title>

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
            max-width: 1100px;
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
            color: #555;
            text-align: center;
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
            padding: 11px 15px;
            background: #2463a6;
            color: white;
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

        input, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccd5df;
            border-radius: 5px;
            background: white;
            font: inherit;
        }

        .message, .error {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .message {
            background: #e6f4ea;
            color: #24613a;
        }

        .error {
            background: #fdeaea;
            color: #9b2424;
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
    <h1>Class Management</h1>
    <p class="subtitle">
        Create and manage training classes and available slots.
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
        <h2><?= $isEditing ? 'Edit Class' : 'Add New Class' ?></h2>

        <form method="POST" action="classes.php<?= $isEditing ? '?edit=' . (int) $editClass['class_id'] : '' ?>">
            <input
                type="hidden"
                name="class_id"
                value="<?= htmlspecialchars((string) ($editClass['class_id'] ?? '')) ?>"
            >

            <div class="form-group">
                <label for="course_id">Course</label>
                <select id="course_id" name="course_id" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option
                            value="<?= (int) $course['course_id'] ?>"
                            <?= (string) ($editClass['course_id'] ?? '') === (string) $course['course_id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="class_code">Class Code</label>
                <input
                    type="text"
                    id="class_code"
                    name="class_code"
                    maxlength="50"
                    required
                    value="<?= htmlspecialchars((string) ($editClass['class_code'] ?? '')) ?>"
                    placeholder="e.g. PHP-A"
                >
            </div>

            <div class="form-group">
                <label for="schedule">Schedule</label>
                <input
                    type="text"
                    id="schedule"
                    name="schedule"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars((string) ($editClass['schedule'] ?? '')) ?>"
                    placeholder="e.g. Saturday, 9:00 AM - 12:00 PM"
                >
            </div>

            <div class="form-group">
                <label for="instructor">Instructor</label>
                <input
                    type="text"
                    id="instructor"
                    name="instructor"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars((string) ($editClass['instructor'] ?? '')) ?>"
                    placeholder="Enter instructor name"
                >
            </div>

            <div class="form-group">
                <label for="slots">Remaining Slots</label>
                <input
                    type="number"
                    id="slots"
                    name="slots"
                    min="0"
                    required
                    value="<?= htmlspecialchars((string) ($editClass['slots'] ?? '')) ?>"
                    placeholder="e.g. 10"
                >
            </div>

            <button class="button" type="submit">
                <?= $isEditing ? 'Update Class' : 'Add Class' ?>
            </button>

            <?php if ($isEditing): ?>
                <a class="cancel-link" href="classes.php">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <h2>Class List</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Course</th>
                    <th>Class Code</th>
                    <th>Schedule</th>
                    <th>Instructor</th>
                    <th>Remaining Slots</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="7">No classes found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($classes as $class): ?>
                        <tr>
                            <td><?= (int) $class['class_id'] ?></td>
                            <td><?= htmlspecialchars($class['course_name']) ?></td>
                            <td><?= htmlspecialchars($class['class_code']) ?></td>
                            <td><?= htmlspecialchars($class['schedule']) ?></td>
                            <td><?= htmlspecialchars($class['instructor']) ?></td>
                            <td><?= (int) $class['slots'] ?></td>
                            <td>
                                <a class="edit-link"
                                   href="classes.php?edit=<?= (int) $class['class_id'] ?>">
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