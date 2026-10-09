
<?php
require_once __DIR__ . '/config/db.php';

$totalStudents = $db->query(
    "SELECT COUNT(*) FROM students"
)->fetchColumn();

$totalClasses = $db->query(
    "SELECT COUNT(*) FROM classes"
)->fetchColumn();

$activeEnrollments = $db->query(
    "SELECT COUNT(*) FROM enrollments WHERE status = 'active'"
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Enrollment System</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px 20px;
        }

        .container {
            max-width: 850px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        h1, .subtitle {
            text-align: center;
        }

        h1 {
            color: #243b55;
        }

        .subtitle {
            color: #555;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }

        .stat-card {
            padding: 20px;
            background: #edf4fc;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card h2 {
            margin: 0 0 10px;
            font-size: 16px;
            color: #345;
        }

        .number {
            font-size: 30px;
            font-weight: bold;
            color: #2463a6;
        }

        .menu {
            display: grid;
            gap: 12px;
        }

        .menu a {
            display: block;
            padding: 15px;
            background: #2463a6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            text-align: center;
        }

        .menu a:hover {
            background: #174574;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Training Enrollment System</h1>

    <p class="subtitle">
        Manage students, courses, classes, and enrollments.
    </p>

    <div class="stats">
        <div class="stat-card">
            <h2>Total Students</h2>
            <div class="number">
                <?= (int) $totalStudents ?>
            </div>
        </div>

        <div class="stat-card">
            <h2>Total Classes</h2>
            <div class="number">
                <?= (int) $totalClasses ?>
            </div>
        </div>

        <div class="stat-card">
            <h2>Active Enrollments</h2>
            <div class="number">
                <?= (int) $activeEnrollments ?>
            </div>
        </div>
    </div>

    <h2>Navigation</h2>

    <div class="menu">
        <a href="admin/students.php">
            Register Student and Select Class
        </a>

        <a href="admin/enroll.php">
            Enroll Existing Student
        </a>

        <a href="admin/classes.php">
            View Courses and Classes
        </a>

        <a href="admin/records.php">
            View Enrollment Records
        </a>
    </div>
</div>

</body>
</html>