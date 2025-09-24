<?php
session_start();
$current_page = 'home';
$page_title = 'Home';

$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Set a flag to indicate this is the root level
$is_root_level = true;
require_once "includes/header.php";
?>

<style>
    /* Landing page specific gradient background */
    body {
        background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #0a0a0a 100%) !important;
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow-x: hidden;
    }

    /* Make navbar more transparent for landing page */
    .navbar {
        background-color: rgba(40, 54, 24, 0.9) !important;
        backdrop-filter: blur(10px);
        position: relative;
        z-index: 1050;
    }

    /* Fix navbar dropdown positioning */
    .navbar .dropdown-menu {
        z-index: 1051 !important;
    }

    /* Adjust container for landing page */
    .container.mt-4 {
        margin-top: 0 !important;
        max-width: 100%;
        padding: 0;
        position: relative;
    }

    .landing-container {
        min-height: calc(100vh - 76px);
        /* Subtract navbar height */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        position: relative;
        padding: 2rem;
    }

    .logo-container {
        width: 180px;
        height: 180px;
        margin-bottom: 2rem;
        transition: all 0.3s ease;
    }

    .logo-container:hover {
        transform: scale(1.05);
    }

    .logo-container img {
        width: 180px;
        height: 180px;
        object-fit: contain;
    }

    .main-title {
        color: #ffffff;
        font-size: 3.5rem;
        font-weight: 700;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        margin-bottom: 3rem;
        letter-spacing: -0.5px;
        animation: fadeInUp 1s ease-out;
    }

    .enter-btn {
        background: linear-gradient(45deg, #4CAF50, #66BB6A);
        border: none;
        color: white;
        font-size: 1.3rem;
        font-weight: 600;
        padding: 1rem 3rem;
        border-radius: 50px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.8rem;
        transition: all 0.3s ease;
        box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
        text-transform: uppercase;
        letter-spacing: 1px;
        animation: fadeInUp 1s ease-out 0.3s both;
    }

    .enter-btn:hover {
        background: linear-gradient(45deg, #66BB6A, #81C784);
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(76, 175, 80, 0.4);
        color: white;
    }

    .enter-btn:active {
        transform: translateY(-1px);
    }

    .secondary-buttons {
        max-width: 800px;
        width: 100%;
        animation: fadeInUp 1s ease-out 0.6s both;
    }

    .secondary-btn {
        background: rgba(76, 175, 80, 0.2);
        border: 2px solid rgba(76, 175, 80, 0.5);
        color: #ffffff;
        font-size: 1rem;
        font-weight: 500;
        padding: 1rem 1.5rem;
        border-radius: 15px;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        min-height: 100px;
        justify-content: center;
    }

    .secondary-btn:hover {
        background: rgba(76, 175, 80, 0.3);
        border-color: rgba(76, 175, 80, 0.8);
        transform: translateY(-2px);
        color: #ffffff;
        box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
    }

    .secondary-btn:active {
        transform: translateY(0px);
    }

    .secondary-btn i {
        font-size: 1.5rem;
        opacity: 0.9;
    }

    .particles {
        position: absolute;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: -1;
    }

    .particle {
        position: absolute;
        background: rgba(76, 175, 80, 0.3);
        border-radius: 50%;
        animation: float 6s ease-in-out infinite;
    }

    .particle:nth-child(1) {
        width: 4px;
        height: 4px;
        left: 10%;
        animation-delay: 0s;
    }

    .particle:nth-child(2) {
        width: 6px;
        height: 6px;
        left: 20%;
        animation-delay: 1s;
    }

    .particle:nth-child(3) {
        width: 3px;
        height: 3px;
        left: 30%;
        animation-delay: 2s;
    }

    .particle:nth-child(4) {
        width: 5px;
        height: 5px;
        left: 40%;
        animation-delay: 1.5s;
    }

    .particle:nth-child(5) {
        width: 4px;
        height: 4px;
        left: 60%;
        animation-delay: 0.5s;
    }

    .particle:nth-child(6) {
        width: 7px;
        height: 7px;
        left: 70%;
        animation-delay: 2.5s;
    }

    .particle:nth-child(7) {
        width: 3px;
        height: 3px;
        left: 80%;
        animation-delay: 3s;
    }

    .particle:nth-child(8) {
        width: 5px;
        height: 5px;
        left: 90%;
        animation-delay: 1.8s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(100vh) rotate(0deg);
            opacity: 0;
        }

        10%,
        90% {
            opacity: 1;
        }

        50% {
            transform: translateY(-10vh) rotate(180deg);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .main-title {
            font-size: 2.5rem;
            margin-bottom: 2rem;
        }

        .enter-btn {
            font-size: 1.1rem;
            padding: 0.8rem 2rem;
        }

        .logo-container {
            width: 140px;
            height: 140px;
        }

        .logo-container img {
            width: 140px;
            height: 140px;
        }
    }

    @media (max-width: 576px) {
        .main-title {
            font-size: 2rem;
            line-height: 1.2;
        }
    }
</style>
<div class="landing-container">
    <!-- Animated background particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- Logo -->
    <div class="logo-container" >
        <img src="./assets/images/logo.png" alt="CA Logo"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="logo-placeholder" style="display: none; width: 180px; height: 180px; border: 3px dashed #4CAF50; border-radius: 50%; align-items: center; justify-content: center; background: rgba(76, 175, 80, 0.1);">
            <i class="fas fa-image" style="font-size: 3rem; color: #4CAF50; opacity: 0.7;"></i>
        </div>
    </div>

    <!-- Main title -->
    <h1 class="main-title">CA Product Sample<br>Inventory Management</h1>

    <!-- Main Enter button -->
    <a href="<?php echo $is_logged_in ? 'books/books.php' : 'auth/login.php'; ?>" class="enter-btn mb-4">
        <i class="fas fa-box-open"></i>
        <?php echo $is_logged_in ? 'View All Products' : 'Get Started'; ?>
    </a>

    <?php if ($is_logged_in): ?>
        <!-- Additional navigation buttons for logged-in users -->
        <div class="secondary-buttons">
            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                <!-- Admin buttons -->
                <div class="row g-3 justify-content-center">
                    <div class="col-md-6 col-lg-3">
                        <a href="admin/dashboard.php" class="secondary-btn">
                            <i class="fas fa-chart-line"></i>
                            Dashboard
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="books/borrows/borrow.php" class="secondary-btn">
                            <i class="fas fa-handshake"></i>
                            Manage Loans
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="admin/users.php" class="secondary-btn">
                            <i class="fas fa-users"></i>
                            Manage Users
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="books/add_book.php" class="secondary-btn">
                            <i class="fas fa-plus"></i>
                            Add Product
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Regular user buttons -->
                <div class="row g-3 justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <a href="books/borrows/my_borrows.php" class="secondary-btn">
                            <i class="fas fa-clipboard-list"></i>
                            My Loans
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>


<?php require_once "includes/footer.php"; ?>