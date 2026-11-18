
<?php
session_start();
require_once "config/db.php";

// Student security
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "student") {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$message = "";
$messageType = "success";

// Submit membership request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["choose_plan"])) {

    $plan_id = (int) $_POST["plan_id"];

    try {
        $stmt = $conn->prepare(
            "SELECT * FROM membership_plans WHERE id = ?"
        );
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            throw new Exception("Invalid membership plan selected.");
        }

        // Check existing pending or active membership
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM memberships
             WHERE user_id = ?
             AND (
                status = 'requested'
                OR (status = 'active' AND end_date >= CURDATE())
             )"
        );
        $stmt->execute([$user_id]);
        $existingMembership = $stmt->fetchColumn();

        if ($existingMembership > 0) {
            throw new Exception(
                "You already have a pending request or an active membership."
            );
        }

        // Create membership request
        $stmt = $conn->prepare(
            "INSERT INTO memberships
             (user_id, plan_id, start_date, end_date, status)
             VALUES (
                ?,
                ?,
                CURDATE(),
                DATE_ADD(CURDATE(), INTERVAL ? DAY),
                'requested'
             )"
        );

        $stmt->execute([
            $user_id,
            $plan_id,
            $plan["duration_days"]
        ]);

        $message = "Membership request submitted successfully!";

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}

// Fetch membership plans
$stmt = $conn->query(
    "SELECT * FROM membership_plans ORDER BY price ASC"
);
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch membership history
$stmt = $conn->prepare(
    "SELECT
        memberships.*,
        membership_plans.plan_name,
        membership_plans.price,
        membership_plans.duration_days
     FROM memberships
     INNER JOIN membership_plans
        ON memberships.plan_id = membership_plans.id
     WHERE memberships.user_id = ?
     ORDER BY memberships.id DESC"
);
$stmt->execute([$user_id]);
$myMemberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Plans | LIBRA</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="libra-main">

    <!-- Top Navigation -->
    <div class="libra-topbar">
        <div>
            <div class="libra-page-title">LIBRA Membership</div>
            <div class="libra-page-subtitle">
                Choose the right plan for your reading journey.
            </div>
        </div>

        <a href="student_dash.php" class="libra-btn libra-btn-light">
            Back to Dashboard
        </a>
    </div>

    <!-- Page Heading -->
    <div class="libra-welcome" style="margin-bottom: 28px;">
        <div>
            <span class="libra-welcome-tag">MEMBERSHIP PROGRAM</span>
            <h1>Unlock More Knowledge</h1>
            <p>
                Select a membership plan and enjoy a better library experience
                with LIBRA – Where Knowledge Meets Innovation.
            </p>
        </div>
    </div>

    <!-- Message -->
    <?php if ($message !== ""): ?>
        <div class="libra-alert libra-alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Membership Plans -->
    <div class="libra-section-heading">
        <div>
            <h2>Choose Your Plan</h2>
            <p>Simple plans designed for every reader.</p>
        </div>
    </div>

    <div class="membership-plans-grid">

        <?php foreach ($plans as $plan): ?>

            <?php
                $planName = strtolower($plan["plan_name"]);
                $isFeatured = ($planName === "gold");
            ?>

            <div class="membership-plan-card <?php echo $isFeatured ? 'membership-featured' : ''; ?>">

                <?php if ($isFeatured): ?>
                    <div class="membership-popular">MOST POPULAR</div>
                <?php endif; ?>

                <div class="membership-plan-icon">
                    <?php
                        if ($planName === "basic") {
                            echo "📘";
                        } elseif ($planName === "silver") {
                            echo "📚";
                        } else {
                            echo "👑";
                        }
                    ?>
                </div>

                <h3>
                    <?php echo htmlspecialchars($plan["plan_name"]); ?>
                </h3>

                <div class="membership-price">
                    <?php
                        echo $plan["price"] == 0
                            ? "FREE"
                            : "₹" . number_format((float)$plan["price"], 0);
                    ?>
                </div>

                <div class="membership-duration">
                    <?php echo (int)$plan["duration_days"]; ?> Days Validity
                </div>

                <div class="membership-description">
                    <?php
                        echo htmlspecialchars(
                            $plan["description"] ?? "Library membership plan."
                        );
                    ?>
                </div>

                <div class="membership-divider"></div>

                <div class="membership-benefit">
                    <span>✓</span> Library Membership Access
                </div>

                <div class="membership-benefit">
                    <span>✓</span> Student Account Support
                </div>

                <form method="POST">
                    <input
                        type="hidden"
                        name="plan_id"
                        value="<?php echo (int)$plan["id"]; ?>"
                    >

                    <button
                        type="submit"
                        name="choose_plan"
                        class="libra-btn <?php echo $isFeatured ? 'libra-btn-success' : 'libra-btn-light'; ?> membership-choose-btn"
                        onclick="return confirm('Submit request for <?php echo htmlspecialchars($plan["plan_name"], ENT_QUOTES); ?> membership?');"
                    >
                        Choose Plan
                    </button>
                </form>

            </div>

        <?php endforeach; ?>

    </div>

    <!-- Membership History -->
    <div class="libra-content-section membership-history-section">

        <div class="libra-section-heading">
            <div>
                <h2>My Membership History</h2>
                <p>Track your membership requests and plans.</p>
            </div>

            <span class="libra-record-count">
                <?php echo count($myMemberships); ?> Records
            </span>
        </div>

        <div class="libra-table-wrapper">

            <table class="libra-table">

                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($myMemberships) > 0): ?>

                        <?php foreach ($myMemberships as $membership): ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($membership["plan_name"]); ?>
                                    </strong>
                                </td>

                                <td>
                                    ₹<?php echo number_format((float)$membership["price"], 2); ?>
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
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="libra-empty">
                                You have not selected any membership plan yet.
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
