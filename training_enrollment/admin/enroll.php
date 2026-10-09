
<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$studentModel = new Student($db);
$classModel = new ClassSection($db);
$repo = new EnrollmentRepository($db);

$error = '';
$message = '';
$studentId = '';
$classId = '';

if (($_GET['message'] ?? '') === 'success') {
    $message = 'Student enrolled successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id'] ?? '');
    $classId = trim($_POST['class_id'] ?? '');

    if (
        !ctype_digit($studentId) || (int) $studentId < 1 ||
        !ctype_digit($classId) || (int) $classId < 1
    ) {
        $error = 'Please select a valid student and class.';
    } else {
        try {
            $repo->enroll((int) $studentId, (int) $classId);

            header('Location: enroll.php?message=success');
            exit;
        } catch (Throwable $e) {
            $error = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Unable to enroll the student. Please try again.';
        }
    }
}

$students = $studentModel->all();
$classes = $classModel->allWithCourse();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Existing Student</title>

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
            max-width: 850px;
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
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: bold;
            color: #345;
            margin-bottom: 7px;
        }

        select {
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

        .hint {
            color: #555;
            font-size: 13px;
            margin-top: 6px;
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
    <h1>Enroll Existing Student</h1>
    <p class="subtitle">
        Select a registered student and an available training class.
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
        <h2>Enrollment Information</h2>

        <form method="POST" action="enroll.php">
            <div class="form-group">
                <label for="student_id">Select Student</label>
                <select id="student_id" name="student_id" required>
                    <option value="">-- Select Student --</option>

                    <?php foreach ($students as $student): ?>
                        <option
                            value="<?= (int) $student['student_id'] ?>"
                            <?= (string) $studentId === (string) $student['student_id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($student['full_name']) ?>
                            (ID: <?= (int) $student['student_id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="class_id">Select Training Class</label>
                <select id="class_id" name="class_id" required>
                    <option value="">-- Select Class --</option>

                    <?php foreach ($classes as $class): ?>
                        <option
                            value="<?= (int) $class['class_id'] ?>"
                            <?= (string) $classId === (string) $class['class_id'] ? 'selected' : '' ?>
                            <?= (int) $class['slots'] < 1 ? 'disabled' : '' ?>
                        >
                            <?= htmlspecialchars(
                                $class['course_name'] . ' - ' .
                                $class['class_code'] . ' (' .
                                $class['schedule'] . ') - ' .
                                $class['slots'] . ' slots left'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="hint">
                    Classes with no remaining slots cannot be selected.
                </p>
            </div>

            <button type="submit" class="button">
                Enroll Student
            </button>
        </form>
    </div>
</div>

</body>
</html>