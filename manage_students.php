
<?php
session_start();
require_once "config/db.php";

// Admin security
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";
$messageType = "success";

// Activate / Deactivate student
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["toggle_status"])) {

    $studentId = (int)($_POST["student_id"] ?? 0);

    $stmt = $conn->prepare(
        "SELECT account_status FROM users
         WHERE id = ? AND role = 'student'"
    );

    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {

        $newStatus = ($student["account_status"] === "active")
            ? "inactive"
            : "active";

        $update = $conn->prepare(
            "UPDATE users SET account_status = ?
             WHERE id = ? AND role = 'student'"
        );

        $update->execute([$newStatus, $studentId]);

        $message = "Student account successfully " . $newStatus . "!";
    } else {
        $message = "Student not found!";
        $messageType = "error";
    }
}

// Search students
$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

$stmt = $conn->prepare(
    "SELECT id, full_name, email, mobile, created_at, account_status
     FROM users
     WHERE role = 'student'
     AND (full_name LIKE ? OR email LIKE ? OR mobile LIKE ?)
     ORDER BY id DESC"
);

$term = "%$search%";
$stmt->execute([$term, $term, $term]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary counts
$totalStudents = count($students);
$activeStudents = 0;
$inactiveStudents = 0;

foreach ($students as $student) {
    if ($student["account_status"] === "active") {
        $activeStudents++;
    } else {
        $inactiveStudents++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | LIBRA</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="libra-main">

    <!-- Topbar -->
    <div class="libra-topbar">
        <div>
            <div class="libra-page-title">Student Management</div>
            <div class="libra-page-subtitle">
                Manage registered students and their account access.
            </div>
        </div>

        <a href="admin_dash.php" class="libra-btn libra-btn-light">
            Back to Dashboard
        </a>
    </div>

    <!-- Welcome Section -->
    <div class="libra-welcome" style="margin-bottom: 28px;">
        <div>
            <span class="libra-welcome-tag">ADMIN CONTROL PANEL</span>
            <h1>Manage Students</h1>
            <p>
                View student information, search accounts and control
                student access to the LIBRA library system.
            </p>
        </div>
    </div>

    <!-- Message -->
    <?php if ($message !== ""): ?>
        <div class="libra-alert libra-alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="libra-summary-grid">

        <div class="libra-summary-card">
            <span>Students Found</span>
            <h2><?php echo $totalStudents; ?></h2>
        </div>

        <div class="libra-summary-card">
            <span>Active Accounts</span>
            <h2><?php echo $activeStudents; ?></h2>
        </div>

        <div class="libra-summary-card">
            <span>Inactive Accounts</span>
            <h2><?php echo $inactiveStudents; ?></h2>
        </div>

    </div>

    <!-- Students Table -->
    <div class="libra-content-section">

        <div class="libra-section-heading">
            <div>
                <h2>Registered Students</h2>
                <p>Search and manage all student accounts.</p>
            </div>

            <span class="libra-record-count">
                <?php echo $totalStudents; ?> Records
            </span>
        </div>

        <!-- Search -->
        <form method="GET" class="libra-search-form">

            <input
                type="text"
                name="search"
                placeholder="Search by name, email or mobile..."
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <button type="submit" class="libra-btn">
                Search
            </button>

            <?php if ($search !== ""): ?>
                <a href="manage_students.php" class="libra-btn libra-btn-light">
                    Clear
                </a>
            <?php endif; ?>

        </form>

        <!-- Table -->
        <div class="libra-table-wrapper">

            <table class="libra-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($students) > 0): ?>

                        <?php foreach ($students as $student): ?>

                            <tr>

                                <td>
                                    <?php echo (int)$student["id"]; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($student["full_name"]); ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student["email"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student["mobile"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student["created_at"]); ?>
                                </td>

                                <td>
                                    <span class="libra-status <?php echo $student["account_status"] === "active" ? "active" : "inactive"; ?>">
                                        <?php echo ucfirst(htmlspecialchars($student["account_status"])); ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="libra-table-actions">

                                        <form method="POST"
                                              onsubmit="return confirm('Are you sure you want to change this student account status?');">

                                            <input
                                                type="hidden"
                                                name="student_id"
                                                value="<?php echo (int)$student["id"]; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="toggle_status"
                                                value="1"
                                                class="libra-btn <?php echo $student["account_status"] === "active" ? "libra-btn-danger" : "libra-btn-success"; ?>"
                                            >
                                                <?php
                                                    echo $student["account_status"] === "active"
                                                        ? "Deactivate"
                                                        : "Activate";
                                                ?>
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="libra-empty">
                                No students found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    <!-- Footer -->
    <div class="libra-footer">
        © <?php echo date("Y"); ?> LIBRA – Smart Library Management System.
        Where Knowledge Meets Innovation.
    </div>

</div>

</body>
</html>
