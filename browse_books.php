
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
$messageType = "";

// Book request
if (isset($_POST["request_book"])) {

    $book_id = (int) $_POST["book_id"];

    $stmt = $conn->prepare(
        "SELECT * FROM books WHERE id = ? AND is_active = 1"
    );
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {

        $message = "Book not found or no longer available.";
        $messageType = "error";

    } elseif ($book["available_copies"] <= 0) {

        $message = "Sorry! This book is not available.";
        $messageType = "error";

    } else {

        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM book_issues
             WHERE user_id = ?
             AND book_id = ?
             AND status IN ('requested', 'issued')"
        );

        $stmt->execute([$user_id, $book_id]);
        $existing = $stmt->fetchColumn();

        if ($existing > 0) {

            $message = "You have already requested or issued this book.";
            $messageType = "error";

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO book_issues
                (user_id, book_id, issue_date, due_date,
                 fine_amount, status)
                VALUES (?, ?, CURDATE(),
                DATE_ADD(CURDATE(), INTERVAL 14 DAY), 0, 'requested')"
            );

            $stmt->execute([$user_id, $book_id]);

            $message = "Book request submitted successfully!";
            $messageType = "success";
        }
    }
}


// Search books - only active books
$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if ($search !== "") {

    $stmt = $conn->prepare(
        "SELECT * FROM books
         WHERE is_active = 1
         AND (
             title LIKE ?
             OR author LIKE ?
             OR category LIKE ?
         )
         ORDER BY title ASC"
    );

    $keyword = "%" . $search . "%";
    $stmt->execute([$keyword, $keyword, $keyword]);

} else {

    $stmt = $conn->query(
        "SELECT * FROM books
         WHERE is_active = 1
         ORDER BY title ASC"
    );
}

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Browse Books | LIBRA</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .catalogue-cover {
            width: 58px;
            height: 78px;
            object-fit: cover;
            border-radius: 7px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 3px 8px rgba(15, 35, 75, 0.12);
            display: block;
        }

        .catalogue-cover-placeholder {
            width: 58px;
            height: 78px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            border-radius: 7px;
            font-size: 25px;
        }
    </style>
</head>

<body>

<div class="libra-main">

    <!-- Page Header -->
    <div class="libra-topbar">

        <div>
            <h1 class="libra-page-title">Explore Our Library</h1>

            <p class="libra-page-subtitle">
                Discover books, explore knowledge and request your favourites.
            </p>
        </div>

        <a href="student_dash.php" class="libra-btn libra-btn-light">
            Back to Dashboard
        </a>

    </div>


    <!-- Welcome Section -->
    <div class="libra-content-section">

        <div class="libra-welcome">

            <div>
                <span class="libra-welcome-tag">
                    LIBRA BOOK COLLECTION
                </span>

                <h2>
                    Welcome,
                    <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!
                </h2>

                <p>
                    Your next favourite book is waiting for you.
                    Explore our collection and submit a book request.
                </p>
            </div>

        </div>

    </div>


    <!-- Search Section -->
    <div class="libra-content-section">

        <div class="libra-section-heading">

            <div>
                <h2>Search Books</h2>

                <p class="libra-page-subtitle">
                    Find books by title, author or category.
                </p>
            </div>

            <span class="libra-record-count">
                <?php echo count($books); ?> Books Found
            </span>

        </div>


        <form method="GET" class="libra-search-form">

            <input
                type="text"
                name="search"
                placeholder="Search title, author or category..."
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <button type="submit" class="libra-btn">
                Search
            </button>

            <a href="browse_books.php" class="libra-btn libra-btn-light">
                Clear
            </a>

        </form>


        <!-- Request Message -->
        <?php if ($message !== ""): ?>

            <div class="libra-alert libra-alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

    </div>


    <!-- Books Catalogue -->
    <div class="libra-content-section">

        <div class="libra-section-heading">

            <div>
                <h2>Available Book Catalogue</h2>

                <p class="libra-page-subtitle">
                    Browse the collection and request books you want to read.
                </p>
            </div>

        </div>


        <div class="libra-table-wrapper">

            <table class="libra-table">

                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Total Copies</th>
                        <th>Available</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>


                <tbody>

                    <?php if (count($books) > 0): ?>

                        <?php foreach ($books as $book): ?>

                            <tr>

                                <!-- Book Cover -->
                                <td>
                                    <?php
                                    $coverPath = "uploads/book_covers/" . ($book["cover_image"] ?? "");
                                    ?>

                                    <?php if (
                                        !empty($book["cover_image"]) &&
                                        file_exists($coverPath)
                                    ): ?>

                                        <img
                                            src="<?php echo htmlspecialchars($coverPath); ?>"
                                            alt="<?php echo htmlspecialchars($book["title"]); ?>"
                                            class="catalogue-cover"
                                        >

                                    <?php else: ?>

                                        <div class="catalogue-cover-placeholder">
                                            📚
                                        </div>

                                    <?php endif; ?>
                                </td>


                                <td>
                                    <?php echo htmlspecialchars($book["title"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($book["author"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($book["category"] ?? ""); ?>
                                </td>

                                <td>
                                    <?php echo (int) $book["total_copies"]; ?>
                                </td>

                                <td>
                                    <?php echo (int) $book["available_copies"]; ?>
                                </td>

                                <td>

                                    <?php if ($book["available_copies"] > 0): ?>

                                        <span class="libra-status active">
                                            Available
                                        </span>

                                    <?php else: ?>

                                        <span class="libra-status inactive">
                                            Not Available
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if ($book["available_copies"] > 0): ?>

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="book_id"
                                                value="<?php echo (int) $book["id"]; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="request_book"
                                                class="libra-btn"
                                            >
                                                Request Book
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <button
                                            type="button"
                                            class="libra-btn libra-btn-light"
                                            disabled
                                        >
                                            Unavailable
                                        </button>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="libra-empty">
                                No books found. Try another search.
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
