<?php
// Include configuration
require_once dirname(__DIR__) . '/config/config.php';

$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - " : ""; ?><?php echo APP_SHORT_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo asset('css/style.css'); ?>" rel="stylesheet">
    <style>
        /* Global black background override */
        body {
            background: #000000 !important;
            color: #ffffff;
        }

        /* Ensure cards and content remain readable with dark theme */
        .card {
            background-color: #1a1a1a !important;
            color: #ffffff;
            border: 1px solid #333;
        }

        .card-header {
            background-color: #2a2a2a !important;
            color: #ffffff !important;
            border-bottom: 1px solid #333;
        }

        .table {
            color: #ffffff;
        }

        .table tbody tr {
            background-color: #1a1a1a !important;
        }

        .table thead th {
            background-color: #2a2a2a !important;
            color: #ffffff !important;
        }

        .list-group-item {
            background-color: #1a1a1a !important;
            color: #ffffff !important;
            border-color: #333 !important;
        }

        .form-control, .form-select {
            background-color: #1a1a1a !important;
            color: #ffffff !important;
            border-color: #333 !important;
        }

        .form-control:focus, .form-select:focus {
            background-color: #1a1a1a !important;
            color: #ffffff !important;
            border-color: #4CAF50 !important;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25) !important;
        }

        /* Fix text-muted to be white instead of grey on dark background */
        .text-muted {
            color: #ffffff !important;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo url('index.php'); ?>">
                <img src="<?php echo asset('images/logo.png'); ?>" alt="Logo" style="width: 30px; height: 30px; object-fit: contain;" class="me-2"><?php echo APP_SHORT_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'home' ? 'active' : ''; ?>" href="<?php echo url('index.php'); ?>">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <?php if($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'products' ? 'active' : ''; ?>" href="<?php echo url('products/products.php'); ?>">
                            <i class="fas fa-warehouse me-1"></i>All Products
                        </a>
                    </li>
                    <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'borrows' ? 'active' : ''; ?>" href="<?php echo url('products/borrows/borrow.php'); ?>">
                            <i class="fas fa-clipboard-list me-1"></i>Loans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>" href="<?php echo url('admin/users.php'); ?>">
                            <i class="fas fa-users me-1"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="<?php echo url('admin/dashboard.php'); ?>">
                            <i class="fas fa-line-chart me-1"></i>Dashboard
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'my_borrows' ? 'active' : ''; ?>" href="<?php echo url('products/borrows/my_borrows.php'); ?>">
                            <i class="fas fa-clipboard-list me-1"></i>My Loans
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION["name"]); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?php echo url('auth/logout.php'); ?>">
                                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'login' ? 'active' : ''; ?>" href="<?php echo url('auth/login.php'); ?>">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'register' ? 'active' : ''; ?>" href="<?php echo url('auth/register.php'); ?>">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4"> 