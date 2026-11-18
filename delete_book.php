
<?php
session_start();
require_once "config/db.php";

// Admin security
if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: login.php");
    exit;
}

// Check book ID
if (!isset($_GET["id"])) {
    header("Location: manage_books.php");
    exit;
}

$id = (int) $_GET["id"];
$message = "";
$success = false;

// Fetch book
$stmt = $conn->prepare(
    "SELECT * FROM books WHERE id = ?"
);
$stmt->execute([$id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    die("Book not found!");
}

// Check if already archived
if ((int)$book["is_active"] === 0) {
    $message = "This book is already archived.";
}

// Confirm archive
if (isset($_POST["confirm_delete"])) {

    if ((int)$book["is_active"] === 0) {
        $message = "This book is already archived.";
    } else {

        try {
            $stmt = $conn->prepare(
                "UPDATE books SET is_active = 0 WHERE id = ?"
            );

            $stmt->execute([$id]);

            $success = true;
            $message = "Book successfully removed from the active library collection.";

            // Refresh book details
            $stmt = $conn->prepare(
                "SELECT * FROM books WHERE id = ?"
            );
            $stmt->execute([$id]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $message = "Unable to archive this book. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Archive Book | LIBRA</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="libra-main">

    <div class="libra-topbar">
        <div>
            <h1 class="libra-page-title">Archive Book</h1>
            <p class="libra-page-subtitle">
                Remove a book from the active library collection.
            </p>
        </div>

        <a href="manage_books.php" class="libra-btn libra-btn-light">
            Back to Manage Books
        </a>
    </div>

    <div class="libra-content-section">

        <div class="libra-section-heading">
            <div>
                <h2>Book Details</h2>
                <p class="libra-page-subtitle">
                    Review the selected book before proceeding.
                </p>
            </div>
        </div>

        <?php if ($message !== ""): ?>

            <div class="libra-alert <?php echo $success ? 'libra-alert-success' : ($book["is_active"] == 0 ? 'libra-alert-success' : 'libra-alert-error'); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <div class="libra-summary-grid">

            <div class="libra-summary-card">
                <h3>Book Title</h3>
                <p><?php echo htmlspecialchars($book["title"]); ?></p>
            </div>

            <div class="libra-summary-card">
                <h3>Author</h3>
                <p><?php echo htmlspecialchars($book["author"]); ?></p>
            </div>

            <div class="libra-summary-card">
                <h3>Category</h3>
                <p><?php echo htmlspecialchars($book["category"] ?? ""); ?></p>
            </div>

            <div class="libra-summary-card">
                <h3>Total Copies</h3>
                <p><?php echo (int)$book["total_copies"]; ?></p>
            </div>

        </div>

        <?php if ((int)$book["is_active"] === 1): ?>

            <p>
                Are you sure you want to remove this book from the active library collection?
                Its issue history will remain محفوظ and unchanged.
            </p>

            <form method="POST" class="libra-table-actions">

                <button
                    type="submit"
                    name="confirm_delete"
                    class="libra-btn libra-btn-danger"
                    onclick="return confirm('Are you sure you want to archive this book?');"
                >
                    Yes, Archive Book
                </button>

                <a href="manage_books.php" class="libra-btn libra-btn-light">
                    Cancel
                </a>

            </form>

        <?php else: ?>

            <a href="manage_books.php" class="libra-btn">
                Back to Books
            </a>

        <?php endif; ?>

    </div>

    <div class="libra-footer">
        LIBRA – Smart Library Management System
        <br>
        Where Knowledge Meets Innovation.
    </div>

</div>

</body>
</html>
