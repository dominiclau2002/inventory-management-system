<?php
session_start();

$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - " : ""; ?>BookHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/index.php">
                <i class="fas fa-book-reader me-2"></i>BookHive
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'home' ? 'active' : ''; ?>" href="/index.php">
                            <i class="fas fa-home me-1"></i>Kezdőlap
                        </a>
                    </li>
                    <?php if($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'books' ? 'active' : ''; ?>" href="/books/books.php">
                            <i class="fas fa-book me-1"></i>Könyvek
                        </a>
                    </li>
                    <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'borrows' ? 'active' : ''; ?>" href="/books/borrows/borrow.php">
                            <i class="fas fa-clipboard-list me-1"></i>Kölcsönzések
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>" href="/admin/users.php">
                            <i class="fas fa-users me-1"></i>Felhasználók
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'my_borrows' ? 'active' : ''; ?>" href="/books/borrows/my_borrows.php">
                            <i class="fas fa-clipboard-list me-1"></i>Kölcsönzéseim
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
                                <a class="dropdown-item" href="/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-1"></i>Kijelentkezés
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'login' ? 'active' : ''; ?>" href="/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Bejelentkezés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'register' ? 'active' : ''; ?>" href="/auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>Regisztráció
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4"> 