
<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$classModel = new ClassSection($db);
$repo = new EnrollmentRepository($db);

$error = '';
$message = '';
$fullName = '';
$email = '';
$phone = '';
$classId = '';

if (($_GET['message'] ?? '') === 'success') {
    $message = 'Student registered and enrolled successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $classId = trim($_POST['class_id'] ?? '');

    if ($fullName === '' || $classId === '') {
        $error = 'Student name and class are required.';
    } elseif (strlen($fullName) > 100) {
        $error = 'Student name must not exceed 100 characters.';
    } elseif ($email !== '' && (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 100
    )) {
        $error = 'Please enter a valid email address of up to 100 characters.';
    } elseif (strlen($phone) > 30) {
        $error = 'Phone number must not exceed 30 characters.';
    } elseif (!ctype_digit($classId) || (int) $classId < 1) {
        $error = 'Please select a valid class.';
    } else {
        try {
            $repo->recordStudent(
                $fullName,
                $email,
                $phone,
                (int) $classId
            );

            header('Location: students.php?message=success');
            exit;
        } catch (Throwable $e) {
            $error = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Unable to register the student. Please try again.';
        }
    }
}

$classes = $classModel->allWithCourse();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>

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
            margin-bottom: 17px;
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

        .hint {
            color: #555;
            font-size: 13px;
            margin-top: 6px;
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
    <h1>Student Registration</h1>
    <p class="subtitle">
        Register a student and enroll them in an available training class.
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
        <h2>Student Information</h2>

        <form method="POST" action="students.php">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars($fullName) ?>"
                    placeholder="Enter student's full name"
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="100"
                    value="<?= htmlspecialchars($email) ?>"
                    placeholder="student@example.com"
                >
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    maxlength="30"
                    value="<?= htmlspecialchars($phone) ?>"
                    placeholder="Enter phone number"
                >
            </div>

            <div class="form-group">
                <label for="class_id">Select Training Class *</label>
                <select id="class_id" name="class_id" required>
                    <option value="">-- Select a Class --</option>

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
                Register Student and Enroll
            </button>
        </form>
    </div>
</div>

</body>
</html>