
<?php
session_start();
require_once "config/db.php";

// Admin security
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";

// Approve membership request
if (isset($_POST["approve_membership"])) {

    $membership_id = (int) $_POST["membership_id"];

    try {
        $conn->beginTransaction();

        // Lock membership request
        $stmt = $conn->prepare(
            "SELECT * FROM memberships
             WHERE id = ? AND status = 'requested'
             FOR UPDATE"
        );
        $stmt->execute([$membership_id]);
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$membership) {
            throw new Exception("Membership request not found or already processed.");
        }

        // Check existing active membership
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM memberships
             WHERE user_id = ?
             AND status = 'active'
             AND end_date >= CURDATE()"
        );
        $stmt->execute([$membership["user_id"]]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception("This student already has an active membership.");
        }

        // Get plan duration
        $stmt = $conn->prepare(
            "SELECT duration_days FROM membership_plans WHERE id = ?"
        );
        $stmt->execute([$membership["plan_id"]]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            throw new Exception("Membership plan not found.");
        }

        // Activate membership
        $stmt = $conn->prepare(
            "UPDATE memberships
             SET start_date = CURDATE(),
                 end_date = DATE_ADD(CURDATE(), INTERVAL ? DAY),
                 status = 'active'
             WHERE id = ? AND status = 'requested'"
        );

        $stmt->execute([
            (int)$plan["duration_days"],
            $membership_id
        ]);

        $conn->commit();

        $message = "Membership activated successfully!";

    } catch (Exception $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $message = $e->getMessage();
    }
}

// Fetch membership records
$sql = "SELECT
            memberships.*,
            users.full_name,
            users.email,
            membership_plans.plan_name,
            membership_plans.price,
            membership_plans.duration_days
        FROM memberships
        INNER JOIN users
            ON memberships.user_id = users.id
        INNER JOIN membership_plans
            ON memberships.plan_id = membership_plans.id
        ORDER BY memberships.id DESC";

$stmt = $conn->query($sql);
$memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dashboard counts
$totalMemberships = count($memberships);
$pendingCount = 0;
$activeCount = 0;
$expiredCount = 0;

foreach ($memberships as $membership) {
    if ($membership["status"] === "requested") {
        $pendingCount++;
    } elseif ($membership["status"] === "active") {
        $activeCount++;
    } elseif ($membership["status"] === "expired") {
        $expiredCount++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Memberships | LIBRA</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="libra-main">

    <!-- Topbar -->
    <div class="libra-topbar">
        <div>
            <div class="libra-page-title">Membership Management</div>
            <div class="libra-page-subtitle">
                Review and manage student membership requests.
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
            <h1>Manage Memberships</h1>
            <p>
                Approve student requests and monitor membership activity
                across your library.
            </p>
        </div>
    </div>

    <!-- Success/Error Message -->
    <?php if ($message !== ""): ?>
        <div class="libra-alert <?php echo strpos($message, 'successfully') !== false ? 'libra-alert-success' : 'libra-alert-error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="libra-summary-grid">

        <div class="libra-summary-card">
            <span>Total Memberships</span>
            <h2><?php echo $totalMemberships; ?></h2>
        </div>

        <div class="libra-summary-card">
            <span>Pending Requests</span>
            <h2><?php echo $pendingCount; ?></h2>
        </div>

        <div class="libra-summary-card">
            <span>Active Memberships</span>
            <h2><?php echo $activeCount; ?></h2>
        </div>

        <div class="libra-summary-card">
            <span>Expired Memberships</span>
            <h2><?php echo $expiredCount; ?></h2>
        </div>

    </div>

    <!-- Membership Records -->
    <div class="libra-content-section">

        <div class="libra-section-heading">
            <div>
                <h2>All Membership Records</h2>
                <p>View student plans, validity and approval status.</p>
            </div>

            <span class="libra-record-count">
                <?php echo $totalMemberships; ?> Records
            </span>
        </div>

        <div class="libra-table-wrapper">

            <table class="libra-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($memberships) > 0): ?>

                        <?php foreach ($memberships as $membership): ?>

                            <tr>

                                <td>
                                    <?php echo (int)$membership["id"]; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($membership["full_name"]); ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($membership["email"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($membership["plan_name"]); ?>
                                </td>

                                <td>
                                    ₹<?php echo number_format((float)$membership["price"], 2); ?>
                                </td>

                                <td>
                                    <?php echo (int)$membership["duration_days"]; ?> Days
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($membership["start_date"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($membership["end_date"]); ?>
                                </td>

                                <td>
                                    <span class="libra-status <?php echo htmlspecialchars($membership["status"]); ?>">
                                        <?php echo ucfirst(htmlspecialchars($membership["status"])); ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="libra-table-actions">

                                        <?php if ($membership["status"] === "requested"): ?>

                                            <form method="POST"
                                                  onsubmit="return confirm('Approve this membership request?');">

                                                <input
                                                    type="hidden"
                                                    name="membership_id"
                                                    value="<?php echo (int)$membership["id"]; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="approve_membership"
                                                    class="libra-btn libra-btn-success"
                                                >
                                                    Approve
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span class="libra-record-count">Processed</span>

                                        <?php endif; ?>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="10" class="libra-empty">
                                No membership records found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    <div class="libra-footer">
        © <?php echo date("Y"); ?> LIBRA – Smart Library Management System.
        Where Knowledge Meets Innovation.
    </div>

</div>

</body>
</html>
