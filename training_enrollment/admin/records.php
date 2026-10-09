
<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$repo = new EnrollmentRepository($db);
$error = '';
$message = '';
$search = trim($_GET['search'] ?? '');

// Cancel enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['cancel_enrollment_id'])) {

    $enrollmentId = filter_var(
        $_POST['cancel_enrollment_id'],
        FILTER_VALIDATE_INT
    );

    if (!$enrollmentId || $enrollmentId < 1) {
        header('Location: records.php?message=invalid');
        exit;
    }

    try {
        $repo->cancel($enrollmentId);
        header('Location: records.php?message=cancelled');
        exit;
    } catch (Throwable $e) {
        header('Location: records.php?message=failed');
        exit;
    }
}

// Notifications
switch ($_GET['message'] ?? '') {
    case 'cancelled':
        $message = 'Enrollment cancelled. The class slot has been restored.';
        break;
    case 'invalid':
        $error = 'Invalid enrollment record.';
        break;
    case 'failed':
        $error = 'Unable to cancel this enrollment. It may already be cancelled.';
        break;
}

// Load records, optionally filtered by student name or email
if ($search !== '') {
    $stmt = $db->prepare(
        "SELECT e.enrollment_id, e.enrollment_date, e.status,
                s.full_name, s.email,
                c.class_code, c.schedule, c.slots,
                co.course_name
         FROM enrollments e
         INNER JOIN students s ON e.student_id = s.student_id
         INNER JOIN classes c ON e.class_id = c.class_id
         INNER JOIN courses co ON c.course_id = co.course_id
         WHERE s.full_name LIKE ? OR s.email LIKE ?
         ORDER BY e.enrollment_id DESC"
    );

    $term = '%' . $search . '%';
    $stmt->execute([$term, $term]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $enrollments = $repo->allWithDetails();
}

$totalRecords = count($enrollments);
$activeCount = 0;
$cancelledCount = 0;

foreach ($enrollments as $enrollment) {
    if ($enrollment['status'] === 'active') {
        $activeCount++;
    } elseif ($enrollment['status'] === 'cancelled') {
        $cancelledCount++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Records</title>

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
            max-width: 1250px;
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

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .stat-card {
            padding: 20px;
            background: #edf4fc;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card h2 {
            margin: 0 0 10px;
            font-size: 15px;
            color: #345;
        }

        .number {
            font-size: 28px;
            font-weight: bold;
            color: #2463a6;
        }

        .search-card {
            background: #edf4fc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .search-form input {
            flex: 1;
            min-width: 200px;
            padding: 11px;
            border: 1px solid #ccd5df;
            border-radius: 5px;
            font: inherit;
        }

        .clear-link {
            display: inline-block;
            padding: 11px 5px;
            color: #2463a6;
            text-decoration: none;
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
            padding: 12px;
            border-bottom: 1px solid #e1e6ec;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #edf4fc;
            color: #243b55;
            white-space: nowrap;
        }

        tr:hover td {
            background: #fafcff;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .active {
            background: #e6f4ea;
            color: #24613a;
        }

        .cancelled {
            background: #fdeaea;
            color: #9b2424;
        }

        .cancel-button {
            background: #b83232;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 10px;
            cursor: pointer;
        }

        .cancel-button:hover {
            background: #8f2020;
        }

        .muted {
            color: #777;
            font-size: 13px;
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
    <h1>Enrollment Records</h1>
    <p class="subtitle">
        View, search, and manage student training enrollments.
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

    <div class="stats">
        <div class="stat-card">
            <h2>Records Found</h2>
            <div class="number"><?= $totalRecords ?></div>
        </div>

        <div class="stat-card">
            <h2>Active Enrollments</h2>
            <div class="number"><?= $activeCount ?></div>
        </div>

        <div class="stat-card">
            <h2>Cancelled Enrollments</h2>
            <div class="number"><?= $cancelledCount ?></div>
        </div>
    </div>

    <div class="search-card">
        <h2>Search Enrollments</h2>

        <form class="search-form" method="GET" action="records.php">
            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Search by student name or email"
            >
            <button type="submit" class="button">Search</button>
            <a href="records.php" class="clear-link">Clear Search</a>
        </form>
    </div>

    <h2>Enrollment List</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Class</th>
                    <th>Schedule</th>
                    <th>Enrollment Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($enrollments)): ?>
                    <tr>
                        <td colspan="9">No enrollment records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($enrollments as $enrollment): ?>
                        <tr>
                            <td><?= (int) $enrollment['enrollment_id'] ?></td>
                            <td><?= htmlspecialchars($enrollment['full_name']) ?></td>
                            <td><?= htmlspecialchars($enrollment['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars($enrollment['course_name']) ?></td>
                            <td><?= htmlspecialchars($enrollment['class_code']) ?></td>
                            <td><?= htmlspecialchars($enrollment['schedule']) ?></td>
                            <td><?= htmlspecialchars($enrollment['enrollment_date']) ?></td>
                            <td>
                                <span class="status <?= $enrollment['status'] === 'active' ? 'active' : 'cancelled' ?>">
                                    <?= htmlspecialchars($enrollment['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($enrollment['status'] === 'active'): ?>
                                    <form method="POST" action="records.php"
                                          onsubmit="return confirm('Cancel this enrollment? The class slot will be restored.');">
                                        <input
                                            type="hidden"
                                            name="cancel_enrollment_id"
                                            value="<?= (int) $enrollment['enrollment_id'] ?>"
                                        >
                                        <button class="cancel-button" type="submit">
                                            Cancel
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">Already cancelled</span>
                                <?php endif; ?>
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