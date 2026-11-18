
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LIBRA - Smart Library</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<!-- NAVBAR -->
<header class="navbar">
    <div class="nav-container">
        <a href="#" class="logo"><span>LIBRA</span> Library</a>

        <nav class="nav-links">
            <a href="#">Home</a>
            <a href="#books">Books</a>
            <a href="#features">Features</a>
            <a href="#membership">Membership</a>
            <a href="login.php" class="login-btn">Login</a>
        </nav>
    </div>
</header>

<!-- HERO SECTION -->
<section class="hero">
    <div class="hero-container">

        <div class="hero-content">
            <span class="hero-tag">SMART LIBRARY MANAGEMENT</span>

            <h1>
                Discover Your Next
                <span>Favourite Book.</span>
            </h1>

            <p>
                Explore books, manage your borrowing history
                and enjoy a smarter library experience.
            </p>

            <div class="hero-buttons">
                <a href="#books" class="primary-btn">Explore Books</a>
                <a href="register.php" class="secondary-btn">Join Library</a>
            </div>
        </div>

        <div class="hero-image-box">
            <img
                src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?w=900"
                alt="Beautiful library">
        </div>

    </div>
</section>

<!-- BOOK SEARCH -->
<section class="search-section" id="books">
    <div class="section-heading">
        <h2>Find Your Next Read</h2>
        <p>Search through our library collection.</p>
    </div>

    <div class="search-box">
        <input type="text" placeholder="Search books or authors...">
        <button type="button">Search</button>
    </div>
</section>

<!-- FEATURES -->
<section class="features-section" id="features">
    <div class="section-heading">
        <h2>Everything in One Place</h2>
        <p>Smart features for a better reading experience.</p>
    </div>

    <div class="features-container">

        <div class="feature-card">
            <div class="feature-icon">📚</div>
            <h3>Book Management</h3>
            <p>Explore books and check their availability.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📅</div>
            <h3>Issue & Return</h3>
            <p>Track issued books, due dates and return history.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">💎</div>
            <h3>Premium Membership</h3>
            <p>Enjoy priority reservations and premium benefits.</p>
        </div>

    </div>
</section>

<!-- MEMBERSHIP -->
<section class="membership-section" id="membership">
    <div class="section-heading">
        <span class="hero-tag">UPGRADE YOUR EXPERIENCE</span>
        <h2>Choose Your Membership</h2>
        <p>Get more out of your library experience.</p>
    </div>

    <div class="plans-container">

        <div class="plan-card">
            <h3>Free Plan</h3>
            <h2>₹0</h2>
            <p>Basic library access</p>
            <p>Book borrowing</p>
            <p>Transaction history</p>
            <a href="register.php" class="plan-btn">Get Started</a>
        </div>

        <div class="plan-card premium">
            <span class="popular-label">POPULAR</span>
            <h3>Monthly Premium</h3>
            <h2>₹99</h2>
            <p>Priority reservations</p>
            <p>Extended reservation access</p>
            <p>Reading history</p>
            <a href="register.php" class="plan-btn premium-btn">
                Join Premium
            </a>
        </div>

    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <h3>LIBRA Library</h3>
    <p>Making Reading Smarter & Simpler.</p>
    <p>© 2026 Smart Library Automation System</p>
</footer>

</body>
</html>
