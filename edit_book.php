
<?php
session_start();
require_once "config/db.php";

// Admin security
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: login.php");
    exit;
}

if (!isset($_GET["id"])) {
    header("Location: manage_books.php");
    exit;
}

$id = (int) $_GET["id"];
$message = "";
$messageType = "";

// Get existing book
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    die("Book not found!");
}

// Update book
if (isset($_POST["update_book"])) {

    $title = trim($_POST["title"]);
    $author = trim($_POST["author"]);
    $category = trim($_POST["category"]);
    $isbn = trim($_POST["isbn"]);
    $total = (int) $_POST["total_copies"];

    $newCover = null;
    $oldCover = $book["cover_image"];

    if ($title !== "" && $author !== "" && $total > 0) {

        $issued = (int) $book["total_copies"] - (int) $book["available_copies"];

        if ($total < $issued) {

            $message = "Total copies cannot be less than issued copies.";
            $messageType = "error";

        } else {

            $available = $total - $issued;
            $isbn = ($isbn === "") ? null : $isbn;

            try {

                // Cover image upload (only if a new image is selected)
                if (
                    isset($_FILES["cover_image"]) &&
                    $_FILES["cover_image"]["error"] !== UPLOAD_ERR_NO_FILE
                ) {

                    if ($_FILES["cover_image"]["error"] !== UPLOAD_ERR_OK) {
                        throw new Exception("Image upload failed. Please try again.");
                    }

                    if ($_FILES["cover_image"]["size"] > 2 * 1024 * 1024) {
                        throw new Exception("Image size must be less than 2 MB.");
                    }

                    $allowedTypes = [
                        "image/jpeg" => "jpg",
                        "image/png" => "png",
                        "image/webp" => "webp"
                    ];

                    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $fileInfo->file($_FILES["cover_image"]["tmp_name"]);

                    if (!isset($allowedTypes[$mimeType])) {
                        throw new Exception("Only JPG, PNG and WEBP images are allowed.");
                    }

                    $uploadDir = "uploads/book_covers/";

                    if (!is_dir($uploadDir)) {
                        throw new Exception("Book cover upload folder not found.");
                    }

                    $extension = $allowedTypes[$mimeType];
                    $fileName = bin2hex(random_bytes(16)) . "." . $extension;
                    $destination = $uploadDir . $fileName;

                    if (!move_uploaded_file(
                        $_FILES["cover_image"]["tmp_name"],
                        $destination
                    )) {
                        throw new Exception("Unable to save book cover.");
                    }

                    $newCover = $fileName;
                }

                // Keep old cover if no new image is selected
                $coverToSave = ($newCover !== null) ? $newCover : $oldCover;

                $stmt = $conn->prepare(
                    "UPDATE books SET
                    title = ?,
                    author = ?,
                    category = ?,
                    isbn = ?,
                    total_copies = ?,
                    available_copies = ?,
                    cover_image = ?
                    WHERE id = ?"
                );

                $stmt->execute([
                    $title,
                    $author,
                    $category,
                    $isbn,
                    $total,
                    $available,
                    $coverToSave,
                    $id
                ]);

                // Delete old cover only after successful database update
                if (
                    $newCover !== null &&
                    !empty($oldCover) &&
                    file_exists("uploads/book_covers/" . $oldCover)
                ) {
                    unlink("uploads/book_covers/" . $oldCover);
                }

                header("Location: manage_books.php");
                exit;

            } catch (PDOException $e) {

                if ($newCover !== null && file_exists("uploads/book_covers/" . $newCover)) {
                    unlink("uploads/book_covers/" . $newCover);
                }

                if ($e->getCode() == "23000") {
                    $message = "This ISBN already belongs to another book.";
                } else {
                    $message = "Database error: " . $e->getMessage();
                }

                $messageType = "error";

            } catch (Exception $e) {

                if ($newCover !== null && file_exists("uploads/book_covers/" . $newCover)) {
                    unlink("uploads/book_covers/" . $newCover);
                }

                $message = $e->getMessage();
                $messageType = "error";
            }
        }

    } else {

        $message = "Please enter valid book details.";
        $messageType = "error";
    }

    // Refresh book details after update attempt
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book | LIBRA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="libra-main">

    <div class="libra-topbar">
        <div>
            <h1 class="libra-page-title">Edit Book</h1>
            <p class="libra-page-subtitle">
                Update book details and cover image.
            </p>
        </div>

        <a href="manage_books.php" class="libra-btn libra-btn-light">
            Back to Manage Books
        </a>
    </div>

    <div class="libra-content-section">

        <div class="libra-section-heading">
            <div>
                <h2>Update Book Information</h2>
                <p class="libra-page-subtitle">
                    Make changes to the selected book details.
                </p>
            </div>
        </div>

        <?php if ($message !== ""): ?>
            <div class="libra-alert libra-alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="libra-form">

            <div class="libra-form-grid">

                <div class="libra-form-group">
                    <label>Book Title</label>
                    <input type="text" name="title"
                        value="<?php echo htmlspecialchars($book["title"]); ?>"
                        placeholder="Enter book title" required>
                </div>

                <div class="libra-form-group">
                    <label>Author Name</label>
                    <input type="text" name="author"
                        value="<?php echo htmlspecialchars($book["author"]); ?>"
                        placeholder="Enter author name" required>
                </div>

                <div class="libra-form-group">
                    <label>Category</label>
                    <input type="text" name="category"
                        value="<?php echo htmlspecialchars($book["category"] ?? ""); ?>"
                        placeholder="Enter book category">
                </div>

                <div class="libra-form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn"
                        value="<?php echo htmlspecialchars($book["isbn"] ?? ""); ?>"
                        placeholder="Enter ISBN number">
                </div>

                <div class="libra-form-group">
                    <label>Total Copies</label>
                    <input type="number" name="total_copies"
                        value="<?php echo (int) $book["total_copies"]; ?>"
                        min="1" required>
                </div>

                <div class="libra-form-group">
                    <label>Book Cover Image</label>

                    <?php if (!empty($book["cover_image"])): ?>
                        <p>Current Cover:</p>
                        <img
                            src="uploads/book_covers/<?php echo htmlspecialchars($book["cover_image"]); ?>"
                            alt="Book Cover"
                            class="book-cover-thumb"
                            style="width:110px;height:145px;margin-bottom:12px;"
                        >
                    <?php else: ?>
                        <p>No cover image uploaded yet.</p>
                    <?php endif; ?>

                    <input type="file" name="cover_image"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                    <small>JPG, PNG or WEBP. Maximum size: 2 MB.</small>
                </div>

            </div>

            <div class="libra-table-actions">

                <button type="submit" name="update_book" class="libra-btn">
                    Update Book
                </button>

                <a href="manage_books.php" class="libra-btn libra-btn-light">
                    Cancel
                </a>

            </div>

        </form>

    </div>

    <div class="libra-footer">
        LIBRA – Smart Library Management System
        <br>
        Where Knowledge Meets Innovation.
    </div>

</div>

</body>
</html>
