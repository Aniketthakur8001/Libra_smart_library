
<?php
session_start();
require_once "config/db.php";

// Admin security
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// Total students
$totalStudents = $conn->query(
    "SELECT COUNT(*) FROM users WHERE role = 'student'"
)->fetchColumn();

// Total book titles
$totalBooks = $conn->query(
    "SELECT COUNT(*) FROM books"
)->fetchColumn();

// Total book copies
$totalCopies = $conn->query(
    "SELECT COALESCE(SUM(total_copies), 0) FROM books"
)->fetchColumn();

// Available copies
$availableCopies = $conn->query(
    "SELECT COALESCE(SUM(available_copies), 0) FROM books"
)->fetchColumn();

// Issued books
$issuedBooks = $conn->query(
    "SELECT COUNT(*) FROM book_issues WHERE status = 'issued'"
)->fetchColumn();

// Pending book requests
$pendingBooks = $conn->query(
    "SELECT COUNT(*) FROM book_issues WHERE status = 'requested'"
)->fetchColumn();

// Active memberships
$activeMemberships = $conn->query(
    "SELECT COUNT(*) FROM memberships
     WHERE status = 'active' AND end_date >= CURDATE()"
)->fetchColumn();

// Pending memberships
$pendingMemberships = $conn->query(
    "SELECT COUNT(*) FROM memberships WHERE status = 'requested'"
)->fetchColumn();

// Approved membership value (not actual collected payment)
$membershipValue = $conn->query(
    "SELECT COALESCE(SUM(mp.price), 0)
     FROM memberships m
     INNER JOIN membership_plans mp ON m.plan_id = mp.id
     WHERE m.status = 'active'"
)->fetchColumn();

// Total fines
$totalFines = $conn->query(
    "SELECT COALESCE(SUM(fine_amount), 0) FROM book_issues"
)->fetchColumn();

// Category-wise books
$categoryStmt = $conn->query(
    "SELECT category, SUM(total_copies) AS copies
     FROM books
     GROUP BY category
     ORDER BY copies DESC"
);
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

$maxCopies = 1;

foreach ($categories as $category) {
    if ((int)$category["copies"] > $maxCopies) {
        $maxCopies = (int)$category["copies"];
    }
}

// Membership plan distribution
$planStmt = $conn->query(
    "SELECT mp.plan_name, COUNT(m.id) AS total
     FROM membership_plans mp
     LEFT JOIN memberships m
        ON mp.id = m.plan_id AND m.status = 'active'
     GROUP BY mp.id, mp.plan_name
     ORDER BY total DESC"
);
$plans = $planStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports & Analytics | LIBRA</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="libra-main">

    <!-- Topbar -->
    <div class="libra-topbar">
        <div>
            <div class="libra-page-title">Reports & Analytics</div>
            <div class="libra-page-subtitle">
                Monitor library performance and activity.
            </div>
        </div>

        <a href="admin_dash.php" class="libra-btn libra-btn-light">
            Back to Dashboard
        </a>
    </div>

    <!-- Welcome Banner -->
    <div class="libra-welcome report-welcome">
        <div>
            <span class="libra-welcome-tag">LIBRA ANALYTICS CENTER</span>

            <h1>Library Performance Overview</h1>

            <p>
                Get insights into books, students, memberships
                and overall library activity.
            </p>
        </div>
    </div>

    <!-- Statistics -->
    <div class="report-stats-grid">

        <div class="report-stat-card">
            <div class="report-stat-icon">👨‍🎓</div>
            <span>Total Students</span>
            <h2><?php echo $totalStudents; ?></h2>
            <small>Registered student accounts</small>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon">📚</div>
            <span>Book Titles</span>
            <h2><?php echo $totalBooks; ?></h2>
            <small>Different books in library</small>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon">📖</div>
            <span>Total Book Copies</span>
            <h2><?php echo $totalCopies; ?></h2>
            <small>Total physical copies</small>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon">✅</div>
            <span>Available Copies</span>
            <h2><?php echo $availableCopies; ?></h2>
            <small>Currently available books</small>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon">📤</div>
            <span>Issued Books</span>
            <h2><?php echo $issuedBooks; ?></h2>
            <small>Currently issued books</small>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon">⏳</div>
            <span>Pending Book Requests</span>
            <h2><?php echo $pendingBooks; ?></h2>
            <small>Awaiting approval</small>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon">⭐</div>
            <span>Active Memberships</span>
            <h2><?php echo $activeMemberships; ?></h2>
            <small>Currently valid memberships</small>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon">📝</div>
            <span>Pending Memberships</span>
            <h2><?php echo $pendingMemberships; ?></h2>
            <small>Awaiting admin approval</small>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon">💰</div>
            <span>Approved Membership Value</span>
            <h2>₹<?php echo number_format((float)$membershipValue, 2); ?></h2>
            <small>Value of active membership plans</small>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon">🪙</div>
            <span>Total Recorded Fines</span>
            <h2>₹<?php echo number_format((float)$totalFines, 2); ?></h2>
            <small>Recorded fines in issue records</small>
        </div>

    </div>

    <!-- Category Distribution -->
    <div class="libra-content-section report-section">

        <div class="libra-section-heading">
            <div>
                <h2>Category-wise Book Distribution</h2>
                <p>Book copy distribution across different categories.</p>
            </div>
        </div>

        <?php if (count($categories) > 0): ?>

            <div class="report-category-list">

                <?php foreach ($categories as $category): ?>

                    <?php
                        $percentage = (
                            (int)$category["copies"] / $maxCopies
                        ) * 100;
                    ?>

                    <div class="report-category-item">

                        <div class="report-category-info">
                            <strong>
                                <?php echo htmlspecialchars($category["category"]); ?>
                            </strong>

                            <span>
                                <?php echo (int)$category["copies"]; ?> Copies
                            </span>
                        </div>

                        <div class="report-progress-bg">
                            <div
                                class="report-progress-fill"
                                style="width: <?php echo $percentage; ?>%;"
                            ></div>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="libra-empty">
                No book categories available yet.
            </div>

        <?php endif; ?>

    </div>

    <!-- Membership Plan Overview -->
    <div class="libra-content-section report-section">

        <div class="libra-section-heading">
            <div>
                <h2>Membership Plan Overview</h2>
                <p>Active memberships under each available plan.</p>
            </div>
        </div>

        <?php if (count($plans) > 0): ?>

            <div class="report-plan-list">

                <?php foreach ($plans as $plan): ?>

                    <div class="report-plan-row">

                        <div class="report-plan-name">
                            <span class="report-plan-icon">👑</span>

                            <strong>
                                <?php echo htmlspecialchars($plan["plan_name"]); ?>
                            </strong>
                        </div>

                        <span class="report-plan-count">
                            <?php echo (int)$plan["total"]; ?>
                            Active Memberships
                        </span>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="libra-empty">
                No membership plans available.
            </div>

        <?php endif; ?>

        <p class="report-note">
            Note: Approved Membership Value is calculated from active
            membership plan prices. It does not represent confirmed
            payments or actual collected revenue.
        </p>

    </div>

    <!-- Footer -->
    <div class="libra-footer">
        © <?php echo date("Y"); ?> LIBRA – Smart Library Management System.
        Where Knowledge Meets Innovation.
    </div>

</div>

</body>
</html>
