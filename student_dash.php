
<?php
session_start();
require_once "config/db.php";

// Student login security
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "student") {
    header("Location: login.php");
    exit;
}

$name = $_SESSION["user_name"];
$user_id = $_SESSION["user_id"];

// Total books
$totalBooks = $conn->query(
    "SELECT COUNT(*) FROM books"
)->fetchColumn();

// Available books
$availableBooks = $conn->query(
    "SELECT COALESCE(SUM(available_copies), 0) FROM books"
)->fetchColumn();

// Student's issued books
$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM book_issues
     WHERE user_id = ? AND status = 'issued'"
);
$stmt->execute([$user_id]);
$issuedBooks = $stmt->fetchColumn();

// Active membership
$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM memberships
     WHERE user_id = ? AND status = 'active'
     AND end_date >= CURDATE()"
);
$stmt->execute([$user_id]);
$activeMembership = $stmt->fetchColumn();

// Pending requests
$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM book_issues
     WHERE user_id = ? AND status = 'requested'"
);
$stmt->execute([$user_id]);
$pendingRequests = $stmt->fetchColumn();

// Total fine
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(fine_amount), 0)
     FROM book_issues
     WHERE user_id = ?"
);
$stmt->execute([$user_id]);
$totalFine = $stmt->fetchColumn();

// Book activity
$stmt = $conn->prepare(
    "SELECT
        book_issues.id,
        book_issues.issue_date,
        book_issues.due_date,
        book_issues.return_date,
        book_issues.fine_amount,
        book_issues.status,
        books.title,
        books.author
     FROM book_issues
     INNER JOIN books ON book_issues.book_id = books.id
     WHERE book_issues.user_id = ?
     ORDER BY book_issues.id DESC"
);
$stmt->execute([$user_id]);
$bookActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | LIBRA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="libra-dashboard">

<div class="libra-layout">

    <!-- SIDEBAR -->
    <aside class="libra-sidebar">

        <a href="index.php" class="libra-logo">
            <span class="libra-logo-icon">📖</span>
            <span>
                <strong>LIBRA</strong>
                <small>SMART LIBRARY</small>
            </span>
        </a>

        <div class="libra-menu-label">MAIN MENU</div>

        <nav class="libra-navigation">

            <a href="student_dash.php" class="libra-nav-link active">
                <span>▦</span> Dashboard
            </a>

            <a href="browse_books.php" class="libra-nav-link">
                <span>📚</span> Browse Books
            </a>

            <a href="memberships.php" class="libra-nav-link">
                <span>✦</span> Membership Plans
            </a>

        </nav>

        <div class="libra-sidebar-bottom">

            <div class="libra-help-card">
                <div class="libra-help-icon">💡</div>
                <strong>Keep Learning!</strong>
                <p>Every book opens a new world of knowledge.</p>
            </div>

            <a href="logout.php" class="libra-logout">
                <span>↪</span> Logout
            </a>

        </div>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="libra-main">

        <!-- TOPBAR -->
        <header class="libra-topbar">

            <div>
                <h2>Student Dashboard</h2>
                <p>Manage your library activities</p>
            </div>

            <div class="libra-user-profile">

                <div class="libra-avatar">
                    <?php echo strtoupper(substr($name, 0, 1)); ?>
                </div>

                <div>
                    <strong><?php echo htmlspecialchars($name); ?></strong>
                    <small>Student Account</small>
                </div>

            </div>

        </header>


        <!-- WELCOME SECTION -->
        <section class="libra-welcome">

            <div>

                <span class="libra-welcome-tag">
                    YOUR PERSONAL LIBRARY
                </span>

                <h1>
                    Welcome back,
                    <?php echo htmlspecialchars($name); ?>!
                </h1>

                <p>
                    Discover books, track your reading activity
                    and explore new knowledge.
                </p>

                <a href="browse_books.php" class="libra-primary-btn">
                    Explore Books <span>→</span>
                </a>

            </div>

            <div class="libra-welcome-art">📚</div>

        </section>


        <!-- STATISTICS -->
        <section class="libra-stats-grid">

            <div class="libra-stat-card">

                <div class="libra-stat-top">
                    <span>Total Books</span>
                    <div class="libra-stat-icon blue">📚</div>
                </div>

                <h2><?php echo $totalBooks; ?></h2>
                <p>Books in library</p>

            </div>


            <div class="libra-stat-card">

                <div class="libra-stat-top">
                    <span>Available Books</span>
                    <div class="libra-stat-icon green">✓</div>
                </div>

                <h2><?php echo $availableBooks; ?></h2>
                <p>Copies available</p>

            </div>


            <div class="libra-stat-card">

                <div class="libra-stat-top">
                    <span>My Issued Books</span>
                    <div class="libra-stat-icon orange">📖</div>
                </div>

                <h2><?php echo $issuedBooks; ?></h2>
                <p>Currently issued</p>

            </div>


            <div class="libra-stat-card">

                <div class="libra-stat-top">
                    <span>Membership</span>
                    <div class="libra-stat-icon purple">✦</div>
                </div>

                <h2 class="libra-membership-value">
                    <?php echo $activeMembership ? "Active" : "None"; ?>
                </h2>

                <p>Your membership status</p>

            </div>

        </section>


        <!-- QUICK ACTIONS -->
        <section class="libra-content-section">

            <div class="libra-section-heading">

                <div>
                    <h2>Quick Actions</h2>
                    <p>Access your library services</p>
                </div>

            </div>


            <div class="libra-actions-grid">

                <a href="browse_books.php" class="libra-action-card">

                    <div class="libra-action-icon">📚</div>

                    <div>
                        <h3>Browse Books</h3>
                        <p>Explore the book collection</p>
                    </div>

                    <span class="libra-action-arrow">→</span>

                </a>


                <a href="memberships.php" class="libra-action-card">

                    <div class="libra-action-icon">⭐</div>

                    <div>
                        <h3>Membership Plans</h3>
                        <p>Explore available plans</p>
                    </div>

                    <span class="libra-action-arrow">→</span>

                </a>

            </div>

        </section>


        <!-- BOOK ACTIVITY -->
        <section class="libra-content-section">

            <div class="libra-section-heading">

                <div>
                    <h2>My Book Activity</h2>
                    <p>Track your book requests and issued books</p>
                </div>

                <span class="libra-record-count">
                    <?php echo count($bookActivities); ?> Records
                </span>

            </div>


            <div class="libra-table-wrapper">

                <table class="libra-table">

                    <thead>
                        <tr>
                            <th>Book Name</th>
                            <th>Author</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Fine</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($bookActivities) > 0): ?>

                        <?php foreach ($bookActivities as $activity): ?>

                            <tr>

                                <td class="libra-book-title">
                                    <?php echo htmlspecialchars($activity["title"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($activity["author"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($activity["issue_date"] ?? "-"); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($activity["due_date"] ?? "-"); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($activity["return_date"] ?? "Not Returned"); ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format((float)$activity["fine_amount"], 2); ?>
                                </td>

                                <td>

                                    <?php
                                    $status = $activity["status"] ?? "";

                                    if ($status === "requested") {
                                        echo '<span class="libra-status requested">Requested</span>';
                                    } elseif ($status === "issued") {
                                        echo '<span class="libra-status issued">Issued</span>';
                                    } elseif ($status === "returned") {
                                        echo '<span class="libra-status returned">Returned</span>';
                                    } else {
                                        echo '<span class="libra-status requested">Pending</span>';
                                    }
                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="libra-empty">

                                <div>📖</div>

                                <strong>No book activity yet</strong>

                                <p>Browse books and request your first book.</p>

                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- ACCOUNT SUMMARY -->
        <section class="libra-summary-grid">

            <div class="libra-summary-card">

                <div class="libra-summary-icon">⏳</div>

                <div>
                    <p>Pending Book Requests</p>
                    <h2><?php echo $pendingRequests; ?></h2>
                </div>

            </div>


            <div class="libra-summary-card">

                <div class="libra-summary-icon">₹</div>

                <div>
                    <p>Total Fine</p>
                    <h2>
                        ₹<?php echo number_format((float)$totalFine, 2); ?>
                    </h2>
                </div>

            </div>

        </section>


        <!-- FOOTER -->
        <footer class="libra-footer">

            © <?php echo date("Y"); ?>
            LIBRA – Smart Library Management System.
            Where Knowledge Meets Innovation.

        </footer>

    </main>

</div>

</body>
</html>
