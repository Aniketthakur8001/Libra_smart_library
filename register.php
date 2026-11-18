
<?php
require_once "config/db.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {

    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" || $password === "") {
        $message = "Please fill all required fields!";
        $message_type = "error";

    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $message_type = "error";

    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters!";
        $message_type = "error";

    } else {

        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $message = "Email already registered!";
            $message_type = "error";

        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users
                    (full_name, email, mobile, password)
                    VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if ($stmt->execute([$name, $email, $mobile, $hashed_password])) {
                $message = "Registration successful! You can now login.";
                $message_type = "success";
            } else {
                $message = "Registration failed!";
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | LIBRA</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-page">

<div class="login-container register-container">

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

            <span class="eyebrow">JOIN OUR KNOWLEDGE HUB</span>

            <h1>
                Every book<br>
                opens a new<br>
                <span>possibility.</span>
            </h1>

            <p>
                Create your LIBRA account and discover
                a smarter world of reading, learning and knowledge.
            </p>

        </div>

        <div class="banner-footer">

            <span>READ. LEARN. GROW.</span>

            <span>EST. 2026</span>

        </div>

    </div>


    <!-- RIGHT REGISTRATION SECTION -->
    <div class="login-form-section register-form-section">

        <div class="login-top-link">

            <span>Already a member?</span>

            <a href="login.php">Sign in</a>

        </div>


        <div class="login-form-content register-form-content">

            <div class="welcome-tag">
                GET STARTED
            </div>

            <h2>Create your account</h2>

            <p class="form-subtitle">
                Join LIBRA and begin your learning journey.
            </p>


            <?php if ($message !== ""): ?>

                <div class="login-error <?php echo $message_type === 'success' ? 'register-success' : ''; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="">

                <label for="full_name">Full Name</label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    value="<?php echo htmlspecialchars($_POST["full_name"] ?? ""); ?>"
                    required
                >


                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email address"
                    value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
                    required
                >


                <label for="mobile">Mobile Number</label>

                <input
                    type="tel"
                    id="mobile"
                    name="mobile"
                    placeholder="Enter your mobile number"
                    value="<?php echo htmlspecialchars($_POST["mobile"] ?? ""); ?>"
                >


                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Minimum 6 characters"
                    required
                >


                <label for="confirm_password">Confirm Password</label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Re-enter your password"
                    required
                >


                <button type="submit" name="register" class="login-submit">
                    Create Account <span>→</span>
                </button>

            </form>


            <div class="login-divider">
                <span>LIBRA DIGITAL PORTAL</span>
            </div>


            <p class="login-register">
                Already have an account?
                <a href="login.php">Login now</a>
            </p>


            <a href="index.php" class="back-home">
                ← Back to Home
            </a>

        </div>

    </div>

</div>

</body>
</html>
