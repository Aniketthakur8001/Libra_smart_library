
<?php
session_start();
require_once "config/db.php";

// Admin security
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";
$messageType = "";

// Approve book request
if (isset($_POST["approve_request"])) {

    $issue_id = (int) $_POST["issue_id"];

    try {
        $conn->beginTransaction();

        // Lock requested issue record
        $stmt = $conn->prepare(
            "SELECT * FROM book_issues
             WHERE id = ?
             AND (status = 'requested' OR status = '' OR status IS NULL)
             FOR UPDATE"
        );

        $stmt->execute([$issue_id]);
        $issue = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$issue) {
            throw new Exception("Request not found or already processed.");
        }

        // Lock book record
        $stmt = $conn->prepare(
            "SELECT available_copies FROM books
             WHERE id = ? FOR UPDATE"
        );

        $stmt->execute([$issue["book_id"]]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$book || $book["available_copies"] < 1) {
            throw new Exception("Sorry! No copies of this book are currently available.");
        }

        // Reduce available copies
        $stmt = $conn->prepare(
            "UPDATE books
             SET available_copies = available_copies - 1
             WHERE id = ? AND available_copies > 0"
        );

        $stmt->execute([$issue["book_id"]]);

        if ($stmt->rowCount() !== 1) {
            throw new Exception("Book is no longer available.");
        }

        // Set issue and due dates
        $stmt = $conn->prepare(
            "UPDATE book_issues
             SET issue_date = CURDATE(),
                 due_date = DATE_ADD(CURDATE(), INTERVAL 14 DAY),
                 return_date = NULL,
                 fine_amount = 0,
                 status = 'issued'
             WHERE id = ?"
        );

        $stmt->execute([$issue_id]);

        $conn->commit();

        $message = "Book request approved successfully!";
        $messageType = "success";

    } catch (Exception $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $message = $e->getMessage();
        $messageType = "error";
    }
}


// Return book
if (isset($_POST["return_book"])) {

    $issue_id = (int) $_POST["issue_id"];

    try {
        $conn->beginTransaction();

        // Lock issue record
        $stmt = $conn->prepare(
            "SELECT * FROM book_issues
             WHERE id = ? AND status = 'issued'
             FOR UPDATE"
        );

        $stmt->execute([$issue_id]);
        $issue = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$issue) {
            throw new Exception("Issue record not found or already returned.");
        }

        // Calculate late fine: Rs. 5 per day
        $today = new DateTime(date("Y-m-d"));
        $dueDate = new DateTime($issue["due_date"]);

        $lateDays = 0;

        if ($today > $dueDate) {
            $lateDays = $dueDate->diff($today)->days;
        }

        $fine = $lateDays * 5;

        // Update issue record
        $stmt = $conn->prepare(
            "UPDATE book_issues
             SET return_date = CURDATE(),
                 fine_amount = ?,
                 status = 'returned'
             WHERE id = ?"
        );

        $stmt->execute([$fine, $issue_id]);

        // Increase available copies
        $stmt = $conn->prepare(
            "UPDATE books
             SET available_copies = available_copies + 1
             WHERE id = ?"
        );

        $stmt->execute([$issue["book_id"]]);

        $conn->commit();

        $message = "Book returned successfully! Fine: Rs. " . $fine;
        $messageType = "success";

    } catch (Exception $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $message = $e->getMessage();
        $messageType = "error";
    }
}


// Fetch all issue records
$sql = "SELECT
            book_issues.*,
            users.full_name,
            books.title
        FROM book_issues
        INNER JOIN users
            ON book_issues.user_id = users.id
        INNER JOIN books
            ON book_issues.book_id = books.id
        ORDER BY book_issues.id DESC";

$stmt = $conn->query($sql);
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dashboard counts
$totalRequests = 0;
$totalIssued = 0;
$totalReturned = 0;

foreach ($issues as $item) {

    $status = $item["status"] ?? "";

    if ($status === "requested" || $status === "") {
        $totalRequests++;
    } elseif ($status === "issued") {
        $totalIssued++;
    } elseif ($status === "returned") {
        $totalReturned++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Book Issues | LIBRA</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="libra-main">

    <!-- Page Header -->
    <div class="libra-topbar">

        <div>
            <h1 class="libra-page-title">Manage Book Issues</h1>

            <p class="libra-page-subtitle">
                Review requests, approve books and manage returns.
            </p>
        </div>

        <a href="admin_dash.php" class="libra-btn libra-btn-light">
            Back to Dashboard
        </a>

    </div>


    <!-- Statistics -->
    <div class="libra-summary-grid">

        <div class="libra-summary-card">
            <h3>Total Requests</h3>
            <p><?php echo $totalRequests; ?></p>
        </div>

        <div class="libra-summary-card">
            <h3>Currently Issued</h3>
            <p><?php echo $totalIssued; ?></p>
        </div>

        <div class="libra-summary-card">
            <h3>Books Returned</h3>
            <p><?php echo $totalReturned; ?></p>
        </div>

        <div class="libra-summary-card">
            <h3>Total Records</h3>
            <p><?php echo count($issues); ?></p>
        </div>

    </div>


    <!-- Message -->
    <?php if ($message !== ""): ?>

        <div class="libra-alert libra-alert-<?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <!-- Issue Records -->
    <div class="libra-content-section">

        <div class="libra-section-heading">

            <div>
                <h2>Book Issue Records</h2>

                <p class="libra-page-subtitle">
                    Manage all student book requests and returns.
                </p>
            </div>

            <span class="libra-record-count">
                <?php echo count($issues); ?> Records
            </span>

        </div>


        <div class="libra-table-wrapper">

            <table class="libra-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Book</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Fine</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>


                <tbody>

                    <?php if (count($issues) > 0): ?>

                        <?php foreach ($issues as $issue): ?>

                            <?php
                                $status = $issue["status"] ?? "";

                                // Blank status is treated as pending
                                $isPending = ($status === "requested" || $status === "");
                            ?>

                            <tr>

                                <td>
                                    <?php echo (int) $issue["id"]; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($issue["full_name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($issue["title"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($issue["issue_date"] ?? "-"); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($issue["due_date"] ?? "-"); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($issue["return_date"] ?? "Not Returned"); ?>
                                </td>

                                <td>
                                    Rs. <?php echo htmlspecialchars($issue["fine_amount"] ?? "0"); ?>
                                </td>

                                <td>

                                    <?php if ($isPending): ?>

                                        <span class="libra-status pending">
                                            Requested
                                        </span>

                                    <?php elseif ($status === "issued"): ?>

                                        <span class="libra-status active">
                                            Issued
                                        </span>

                                    <?php elseif ($status === "returned"): ?>

                                        <span class="libra-status returned">
                                            Returned
                                        </span>

                                    <?php else: ?>

                                        <span class="libra-status inactive">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <div class="libra-table-actions">

                                        <?php if ($isPending): ?>

                                            <form method="POST"
                                                onsubmit="return confirm('Approve this book request?');">

                                                <input
                                                    type="hidden"
                                                    name="issue_id"
                                                    value="<?php echo (int) $issue["id"]; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="approve_request"
                                                    class="libra-btn"
                                                >
                                                    Approve
                                                </button>

                                            </form>

                                        <?php elseif ($status === "issued"): ?>

                                            <form method="POST"
                                                onsubmit="return confirm('Confirm book return?');">

                                                <input
                                                    type="hidden"
                                                    name="issue_id"
                                                    value="<?php echo (int) $issue["id"]; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="return_book"
                                                    class="libra-btn libra-btn-success"
                                                >
                                                    Return Book
                                                </button>

                                            </form>

                                        <?php elseif ($status === "returned"): ?>

                                            <span class="libra-status returned">
                                                Completed
                                            </span>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="libra-empty">
                                No book issue records found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- Footer -->
    <div class="libra-footer">
        LIBRA – Smart Library Management System
        <br>
        Where Knowledge Meets Innovation.
    </div>

</div>

</body>
</html>
