
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

// Restore archived book
if (isset($_POST["restore_book"])) {

    $restoreId = (int) $_POST["book_id"];

    try {
        $stmt = $conn->prepare(
            "UPDATE books SET is_active = 1 WHERE id = ? AND is_active = 0"
        );

        $stmt->execute([$restoreId]);

        if ($stmt->rowCount() > 0) {
            $message = "Book restored successfully!";
            $messageType = "success";
        } else {
            $message = "Book could not be restored.";
            $messageType = "error";
        }

    } catch (PDOException $e) {
        $message = "Unable to restore book. Please try again.";
        $messageType = "error";
    }
}


// Add new book
if (isset($_POST["add_book"])) {

    $title = trim($_POST["title"]);
    $author = trim($_POST["author"]);
    $category = trim($_POST["category"]);
    $isbn = trim($_POST["isbn"]);
    $total = (int) $_POST["total_copies"];

    $coverImage = null;

    if ($title !== "" && $author !== "" && $total > 0) {

        try {

            // Cover image upload
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

                $extension = $allowedTypes[$mimeType];
                $uploadDir = "uploads/book_covers/";

                if (!is_dir($uploadDir)) {
                    throw new Exception("Book cover upload folder not found.");
                }

                $fileName = bin2hex(random_bytes(16)) . "." . $extension;
                $destination = $uploadDir . $fileName;

                if (!move_uploaded_file(
                    $_FILES["cover_image"]["tmp_name"],
                    $destination
                )) {
                    throw new Exception("Unable to save book cover.");
                }

                $coverImage = $fileName;
            }

            $isbn = ($isbn === "") ? null : $isbn;

            $sql = "INSERT INTO books
                    (title, author, category, isbn, total_copies,
                     available_copies, cover_image)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $title,
                $author,
                $category,
                $isbn,
                $total,
                $total,
                $coverImage
            ]);

            $message = "Book added successfully!";
            $messageType = "success";

        } catch (PDOException $e) {

            if ($coverImage !== null && file_exists("uploads/book_covers/" . $coverImage)) {
                unlink("uploads/book_covers/" . $coverImage);
            }

            if ($e->getCode() == "23000") {
                $message = "This ISBN already exists. Please use a different ISBN.";
            } else {
                $message = "Database Error: " . $e->getMessage();
            }

            $messageType = "error";

        } catch (Exception $e) {

            if ($coverImage !== null && file_exists("uploads/book_covers/" . $coverImage)) {
                unlink("uploads/book_covers/" . $coverImage);
            }

            $message = $e->getMessage();
            $messageType = "error";
        }

    } else {

        $message = "Please enter valid book details.";
        $messageType = "error";
    }
}


// Search books
$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if ($search !== "") {

    $stmt = $conn->prepare(
        "SELECT * FROM books
         WHERE title LIKE ?
         OR author LIKE ?
         OR category LIKE ?
         ORDER BY id DESC"
    );

    $keyword = "%" . $search . "%";
    $stmt->execute([$keyword, $keyword, $keyword]);

} else {

    $stmt = $conn->query(
        "SELECT * FROM books ORDER BY id DESC"
    );
}

$allBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate active and archived books
$activeBooks = [];
$archivedBooks = [];

foreach ($allBooks as $book) {

    if ((int)$book["is_active"] === 1) {
        $activeBooks[] = $book;
    } else {
        $archivedBooks[] = $book;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Books | LIBRA</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="libra-main">

    <!-- Page Header -->
    <div class="libra-topbar">

        <div>
            <h1 class="libra-page-title">Manage Books</h1>

            <p class="libra-page-subtitle">
                Add, manage and organize your library collection.
            </p>
        </div>

        <a href="admin_dash.php" class="libra-btn libra-btn-light">
            Back to Dashboard
        </a>

    </div>


    <!-- Messages -->
    <?php if ($message !== ""): ?>

        <div class="libra-alert libra-alert-<?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <!-- Add Book Section -->
    <div class="libra-content-section">

        <div class="libra-section-heading">
            <div>
                <h2>Add New Book</h2>

                <p class="libra-page-subtitle">
                    Enter book details and upload its cover.
                </p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="libra-form">

            <div class="libra-form-grid">

                <div class="libra-form-group">
                    <label>Book Title</label>
                    <input type="text" name="title" placeholder="Enter book title" required>
                </div>

                <div class="libra-form-group">
                    <label>Author Name</label>
                    <input type="text" name="author" placeholder="Enter author name" required>
                </div>

                <div class="libra-form-group">
                    <label>Category</label>
                    <input type="text" name="category" placeholder="e.g. Programming, Science">
                </div>

                <div class="libra-form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn" placeholder="Enter ISBN number">
                </div>

                <div class="libra-form-group">
                    <label>Total Copies</label>
                    <input type="number" name="total_copies" placeholder="Enter total copies" min="1" required>
                </div>

                <div class="libra-form-group">
                    <label>Book Cover Image</label>
                    <input
                        type="file"
                        name="cover_image"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >
                    <small>JPG, PNG or WEBP. Maximum size: 2 MB.</small>
                </div>

            </div>

            <button type="submit" name="add_book" class="libra-btn">
                + Add Book
            </button>

        </form>

    </div>


    <!-- Search Section -->
    <div class="libra-content-section">

        <div class="libra-section-heading">
            <div>
                <h2>Search Books</h2>
                <p class="libra-page-subtitle">
                    Search in both active and archived books.
                </p>
            </div>
        </div>

        <form method="GET" class="libra-search-form">

            <input
                type="text"
                name="search"
                placeholder="Search by title, author or category..."
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <button type="submit" class="libra-btn">Search</button>

            <a href="manage_books.php" class="libra-btn libra-btn-light">
                Clear
            </a>

        </form>

    </div>


    <!-- Active Books -->
    <div class="libra-content-section">

        <div class="libra-section-heading">

            <div>
                <h2>Active Books</h2>

                <p class="libra-page-subtitle">
                    Books currently available in your library collection.
                </p>
            </div>

            <span class="libra-record-count">
                Active: <?php echo count($activeBooks); ?>
            </span>

        </div>

        <div class="libra-table-wrapper">

            <table class="libra-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cover</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>ISBN</th>
                        <th>Total Copies</th>
                        <th>Available</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($activeBooks) > 0): ?>

                        <?php foreach ($activeBooks as $book): ?>

                            <tr>

                                <td><?php echo (int)$book["id"]; ?></td>

                                <td>
                                    <?php if (!empty($book["cover_image"])): ?>
                                        <img
                                            src="uploads/book_covers/<?php echo rawurlencode($book["cover_image"]); ?>"
                                            alt="Book Cover"
                                            class="book-cover-thumb"
                                        >
                                    <?php else: ?>
                                        <span class="book-cover-placeholder">📖</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo htmlspecialchars($book["title"]); ?></td>

                                <td><?php echo htmlspecialchars($book["author"]); ?></td>

                                <td><?php echo htmlspecialchars($book["category"] ?? ""); ?></td>

                                <td><?php echo htmlspecialchars($book["isbn"] ?? ""); ?></td>

                                <td><?php echo (int)$book["total_copies"]; ?></td>

                                <td><?php echo (int)$book["available_copies"]; ?></td>

                                <td>

                                    <div class="libra-table-actions">

                                        <a
                                            href="edit_book.php?id=<?php echo (int)$book['id']; ?>"
                                            class="libra-edit-btn"
                                        >Edit</a>

                                        <a
                                            href="delete_book.php?id=<?php echo (int)$book['id']; ?>"
                                            class="libra-delete-btn"
                                        >Archive</a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="libra-empty">
                                No active books found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- Archived Books -->
    <div class="libra-content-section">

        <div class="libra-section-heading">

            <div>
                <h2>Archived Books</h2>

                <p class="libra-page-subtitle">
                    Restore books to make them active again.
                </p>
            </div>

            <span class="libra-record-count">
                Archived: <?php echo count($archivedBooks); ?>
            </span>

        </div>

        <div class="libra-table-wrapper">

            <table class="libra-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cover</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>ISBN</th>
                        <th>Total Copies</th>
                        <th>Available</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($archivedBooks) > 0): ?>

                        <?php foreach ($archivedBooks as $book): ?>

                            <tr>

                                <td><?php echo (int)$book["id"]; ?></td>

                                <td>
                                    <?php if (!empty($book["cover_image"])): ?>
                                        <img
                                            src="uploads/book_covers/<?php echo rawurlencode($book["cover_image"]); ?>"
                                            alt="Book Cover"
                                            class="book-cover-thumb"
                                        >
                                    <?php else: ?>
                                        <span class="book-cover-placeholder">📖</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo htmlspecialchars($book["title"]); ?></td>

                                <td><?php echo htmlspecialchars($book["author"]); ?></td>

                                <td><?php echo htmlspecialchars($book["category"] ?? ""); ?></td>

                                <td><?php echo htmlspecialchars($book["isbn"] ?? ""); ?></td>

                                <td><?php echo (int)$book["total_copies"]; ?></td>

                                <td><?php echo (int)$book["available_copies"]; ?></td>

                                <td>

                                    <form method="POST" class="libra-table-actions">

                                        <input
                                            type="hidden"
                                            name="book_id"
                                            value="<?php echo (int)$book['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="restore_book"
                                            class="libra-btn libra-btn-success"
                                            onclick="return confirm('Restore this book to the active collection?');"
                                        >
                                            Restore
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="libra-empty">
                                No archived books found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <div class="libra-footer">
        LIBRA – Smart Library Management System
        <br>
        Where Knowledge Meets Innovation.
    </div>

</div>

</body>
</html>
