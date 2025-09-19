<?php
session_start();

require_once "config/db.php";

$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Get user's borrowed books if logged in
$borrowed_books = array();
if($is_logged_in && isset($_SESSION["id"])) {
    $sql = "SELECT books.*, borrows.borrow_date, borrows.return_date 
            FROM books 
            INNER JOIN borrows ON books.id = borrows.book_id 
            WHERE borrows.user_id = ? AND borrows.actual_return_date IS NULL 
            ORDER BY borrows.borrow_date DESC";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            while($row = mysqli_fetch_array($result)){
                $borrowed_books[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Get latest books
$latest_books = array();
$sql = "SELECT * FROM books ORDER BY created_at DESC LIMIT ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $limit);
    $limit = 5;
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_array($result)){
            $latest_books[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-book-reader me-2"></i>BookHive
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="../index.php">
                            <i class="fas fa-home me-1"></i>Kezdőlap
                        </a>
                    </li>
                    <?php if($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="books/books.php">
                            <i class="fas fa-book me-1"></i>Könyvek
                        </a>
                    </li>
                    <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="books/borrows/borrow.php">
                            <i class="fas fa-clipboard-list me-1"></i>Kölcsönzések
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/users.php">
                            <i class="fas fa-users me-1"></i>Felhasználók
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="books/borrows/my_borrows.php">
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
                                <a class="dropdown-item" href="auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-1"></i>Kijelentkezés
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Bejelentkezés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>Regisztráció
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if(!$is_logged_in): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="display-4 mb-4">
                            <i class="fas fa-book-open text-primary me-3"></i>
                            Üdvözöljük a BookHive-ban!
                        </h1>
                        <p class="lead mb-4">Böngésszen könyveink között és kezelje kölcsönzéseit online.</p>
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                            <a href="auth/login.php" class="btn btn-primary btn-lg px-4 gap-3" data-tooltip="Jelentkezzen be fiókjába">
                                <i class="fas fa-sign-in-alt me-2"></i>Bejelentkezés
                            </a>
                            <a href="auth/register.php" class="btn btn-outline-primary btn-lg px-4" data-tooltip="Hozzon létre új fiókot">
                                <i class="fas fa-user-plus me-2"></i>Regisztráció
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="<?php echo $is_logged_in ? 'col-md-8' : 'col-12'; ?>">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Legújabb könyvek
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($latest_books)): ?>
                            <p class="text-muted mb-0">Jelenleg nincsenek könyvek a rendszerben.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach($latest_books as $book): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <i class="fas fa-book me-2"></i><?php echo htmlspecialchars($book["title"]); ?>
                                                </h5>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i>Szerző: <?php echo htmlspecialchars($book["author"]); ?>
                                                    </small>
                                                </p>
                                                <p class="card-text">
                                                    <?php echo htmlspecialchars(substr($book["description"], 0, 100)) . "..."; ?>
                                                </p>
                                                <?php if($is_logged_in): ?>
                                                <a href="books/view_book.php?id=<?php echo $book["id"]; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-info-circle me-1"></i>Részletek
                                                </a>
                                                <?php else: ?>
                                                <div class="alert alert-info mb-0 py-2">
                                                    <small><i class="fas fa-info-circle me-1"></i>A részletek megtekintéséhez jelentkezzen be</small>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if($is_logged_in): ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-book-reader me-2"></i>Kölcsönzött könyveim
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($borrowed_books)): ?>
                            <p class="text-muted mb-0">Jelenleg nincs kölcsönzött könyved.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach($borrowed_books as $book): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($book["title"]); ?></h5>
                                            <small class="text-muted">
                                                Határidő: <?php echo date("Y.m.d", strtotime($book["return_date"])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1">
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($book["author"]); ?>
                                            </small>
                                        </p>
                                        <a href="books/view_book.php?id=<?php echo $book["id"]; ?>" class="btn btn-primary btn-sm mt-2">
                                            <i class="fas fa-info-circle me-1"></i>Részletek
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 