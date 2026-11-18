
<?php
session_start();
require_once "config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {

        if (
            $user["role"] === "student" &&
            $user["account_status"] !== "active"
        ) {
            $message = "Your account is inactive. Please contact the administrator.";
        } else {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["full_name"];
            $_SESSION["user_role"] = $user["role"];

            if ($user["role"] === "admin") {
                header("Location: admin_dash.php");
            } else {
                header("Location: student_dash.php");
            }

            exit;
        }

    } else {
        $message = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | LIBRA</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-page">

<div class="login-container">

    <!-- LEFT BRANDING SECTION -->
    <div class="login-banner">

        <div class="brand">

            <div class="brand-icon">
                <svg width="30" height="30" viewBox="0 0 32 32"
                     fill="none" xmlns="http://www.w3.org/2000/svg">

                    <path d="M16 8C12 5 7 5 3 7V25C7 23 12 23 16 26"
                          stroke="#173765" stroke-width="2.2"
                          stroke-linecap="round" stroke-linejoin="round"/>

                    <path d="M16 8C20 5 25 5 29 7V25C25 23 20 23 16 26"
                          stroke="#497CE0" stroke-width="2.2"
                          stroke-linecap="round" stroke-linejoin="round"/>

                    <path d="M16 8V26"
                          stroke="#173765" stroke-width="2"
                          stroke-linecap="round"/>

                    <path d="M7 11C10 10 12 11 14 12"
                          stroke="#497CE0" stroke-width="1.5"
                          stroke-linecap="round"/>

                    <path d="M25 11C22 10 20 11 18 12"
                          stroke="#497CE0" stroke-width="1.5"
                          stroke-linecap="round"/>

                </svg>
            </div>

            <span>LIBRA</span>

        </div>

        <div class="banner-content">

            <span class="eyebrow">KNOWLEDGE HUB</span>

            <h1>
                Knowledge is<br>
                the key to<br>
                <span>your future.</span>
            </h1>

            <p>
                Discover books, manage your reading journey
                and explore a smarter way to learn.
            </p>

        </div>

        <div class="banner-footer">

            <span>READ. LEARN. GROW.</span>

            <span>EST. 2026</span>

        </div>

    </div>


    <!-- RIGHT LOGIN SECTION -->
    <div class="login-form-section">

        <div class="login-top-link">

            <span>New to LIBRA?</span>

            <a href="register.php">Create account</a>

        </div>


        <div class="login-form-content">

            <div class="welcome-tag">
                WELCOME BACK
            </div>

            <h2>Sign in to your account</h2>

            <p class="form-subtitle">
                Enter your details to continue your library journey.
            </p>


            <?php if ($message !== ""): ?>

                <div class="login-error">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
                    required
                >


                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >


                <button type="submit" class="login-submit">
                    Sign In <span>→</span>
                </button>

            </form>


            <div class="login-divider">
                <span>LIBRA DIGITAL PORTAL</span>
            </div>


            <p class="login-register">
                Don't have an account?
                <a href="register.php">Register now</a>
            </p>


            <a href="index.php" class="back-home">
                ← Back to Home
            </a>

        </div>

    </div>

</div>

</body>
</html>
