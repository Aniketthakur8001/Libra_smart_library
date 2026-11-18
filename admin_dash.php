
<?php
session_start();
require_once "config/db.php";

// Admin login check
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// Dashboard statistics
$totalBooks = $conn->query(
    "SELECT COUNT(*) FROM books"
)->fetchColumn();

$totalStudents = $conn->query(
    "SELECT COUNT(*) FROM users WHERE role = 'student'"
)->fetchColumn();

$totalIssued = $conn->query(
    "SELECT COUNT(*) FROM book_issues WHERE status = 'issued'"
)->fetchColumn();

$totalMemberships = $conn->query(
    "SELECT COUNT(*) FROM memberships WHERE status = 'active'"
)->fetchColumn();

$adminName = $_SESSION["user_name"] ?? "Administrator";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIBRA | Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="libra-dashboard admin-dashboard">

    <div class="libra-layout">

        <!-- Sidebar -->
        <aside class="libra-sidebar">

            <div class="libra-logo">
                <div class="brand-icon">📖</div>
                <div>
                    <h2>LIBRA</h2>
                    <span>ADMIN PANEL</span>
                </div>
            </div>

            <nav class="libra-navigation">

                <p class="sidebar-heading">MAIN MENU</p>

                <a href="admin_dash.php" class="libra-nav-link active">
                    <span>▦</span> Dashboard
                </a>

                <a href="manage_books.php" class="libra-nav-link">
                    <span>📚</span> Manage Books
                </a>

                <a href="manage_students.php" class="libra-nav-link">
                    <span>👨‍🎓</span> Manage Students
                </a>

                <a href="manage_issues.php" class="libra-nav-link">
                    <span>📋</span> Manage Issues
                </a>

                <a href="manage_memberships.php" class="libra-nav-link">
                    <span>💳</span> Memberships
                </a>

                <a href="reports.php" class="libra-nav-link">
                    <span>📊</span> Reports & Analytics
                </a>

            </nav>

            <div class="sidebar-bottom">
                <a href="logout.php" class="libra-nav-link logout-link">
                    <span>↪</span> Logout
                </a>
            </div>

        </aside>

        <!-- Main Content -->
        <main class="libra-main">

            <!-- Topbar -->
            <header class="libra-topbar">
                <div>
                    <h3>Admin Dashboard</h3>
                    <p>Library Management Overview</p>
                </div>

                <div class="libra-user-profile">
                    <div class="user-avatar">A</div>
                    <div>
                        <strong><?php echo htmlspecialchars($adminName); ?></strong>
                        <span>Administrator</span>
                    </div>
                </div>
            </header>

            <!-- Welcome Section -->
            <section class="admin-welcome">
                <h1>Welcome back, <?php echo htmlspecialchars($adminName); ?>! 👋</h1>
                <p>Manage your library, students, books and memberships from one place.</p>
            </section>

            <!-- Statistics -->
            <section class="libra-stats-grid">

                <div class="libra-stat-card admin-stat-card">
                    <div class="admin-stat-icon">📚</div>
                    <h3>Total Books</h3>
                    <p><?php echo $totalBooks; ?></p>
                    <span>Books in library</span>
                </div>

                <div class="libra-stat-card admin-stat-card">
                    <div class="admin-stat-icon">👨‍🎓</div>
                    <h3>Total Students</h3>
                    <p><?php echo $totalStudents; ?></p>
                    <span>Registered students</span>
                </div>

                <div class="libra-stat-card admin-stat-card">
                    <div class="admin-stat-icon">📖</div>
                    <h3>Books Issued</h3>
                    <p><?php echo $totalIssued; ?></p>
                    <span>Currently issued books</span>
                </div>

                <div class="libra-stat-card admin-stat-card">
                    <div class="admin-stat-icon">💳</div>
                    <h3>Active Memberships</h3>
                    <p><?php echo $totalMemberships; ?></p>
                    <span>Active members</span>
                </div>

            </section>

            <!-- Management Actions -->
            <section class="libra-content-section">

                <h2 class="admin-section-heading">Library Management</h2>
                <p class="admin-section-subtitle">
                    Choose a section to manage your library operations.
                </p>

                <div class="admin-actions-grid">

                    <a href="manage_books.php" class="admin-action-card">
                        <div class="admin-action-icon">📚</div>
                        <h3>Manage Books</h3>
                        <p>Add, edit, search and manage library books.</p>
                    </a>

                    <a href="manage_students.php" class="admin-action-card">
                        <div class="admin-action-icon">👨‍🎓</div>
                        <h3>Manage Students</h3>
                        <p>View student accounts and manage their status.</p>
                    </a>

                    <a href="manage_issues.php" class="admin-action-card">
                        <div class="admin-action-icon">📋</div>
                        <h3>Manage Issues</h3>
                        <p>Approve book requests and manage returns.</p>
                    </a>

                    <a href="manage_memberships.php" class="admin-action-card">
                        <div class="admin-action-icon">💳</div>
                        <h3>Manage Memberships</h3>
                        <p>Review and approve membership requests.</p>
                    </a>

                    <a href="reports.php" class="admin-action-card">
                        <div class="admin-action-icon">📊</div>
                        <h3>Reports & Analytics</h3>
                        <p>View library statistics and performance reports.</p>
                    </a>

                </div>

            </section>

        </main>

    </div>

</div>

</body>
</html>
