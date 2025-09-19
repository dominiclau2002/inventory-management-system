<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../auth/login.php");
    exit;
}

// For non-admin users, only allow direct book borrowing
if($_SESSION["role"] !== "admin" && !isset($_GET["book_id"])) {
    header("location: ../../index.php");
    exit;
}

require_once "../../config/db.php";

// Process return book
if(isset($_GET["return"]) && !empty($_GET["return"])){
    $sql = "UPDATE borrows SET actual_return_date = NOW() WHERE id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $_GET["return"];
        
        if(mysqli_stmt_execute($stmt)){
            header("location: books/borrows/borrow.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Process new borrow
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $book_id = $_POST["book_id"];
    $user_id = $_POST["user_id"];
    $return_date = $_POST["return_date"];
    
    $sql = "INSERT INTO borrows (book_id, user_id, borrow_date, return_date) VALUES (?, ?, NOW(), ?)";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "iis", $book_id, $user_id, $return_date);
        
        if(mysqli_stmt_execute($stmt)){
            header("location: books/borrows/borrow.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Get all active borrows
$borrows = array();
$sql = "SELECT borrows.*, books.title as book_title, users.name as user_name 
        FROM borrows 
        INNER JOIN books ON borrows.book_id = books.id 
        INNER JOIN users ON borrows.user_id = users.id 
        WHERE actual_return_date IS NULL 
        ORDER BY borrow_date DESC";

if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $borrows[] = $row;
    }
}

// Get available books for new borrow
$available_books = array();
$sql = "SELECT id, title FROM books WHERE id NOT IN (SELECT book_id FROM borrows WHERE actual_return_date IS NULL)";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $available_books[] = $row;
    }
}

// Get all users for new borrow
$users = array();
$sql = "SELECT id, name, username FROM users WHERE role = 'user'";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $users[] = $row;
    }
}

// Handle direct book borrowing
if(isset($_GET["book_id"]) && !empty($_GET["book_id"])) {
    $book_id = $_GET["book_id"];
    $user_id = $_SESSION["id"];
    $return_date = date('Y-m-d', strtotime('+30 days'));
    
    // Check if book is available
    $sql = "SELECT status FROM books WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $book_id);
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_array($result)) {
                if($row["status"] == "available") {
                    // Insert borrow record
                    $sql = "INSERT INTO borrows (book_id, user_id, borrow_date, return_date) VALUES (?, ?, NOW(), ?)";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "iis", $book_id, $user_id, $return_date);
                        if(mysqli_stmt_execute($stmt)) {
                            // Update book status
                            $sql = "UPDATE books SET status = 'borrowed' WHERE id = ?";
                            if($stmt = mysqli_prepare($conn, $sql)) {
                                mysqli_stmt_bind_param($stmt, "i", $book_id);
                                mysqli_stmt_execute($stmt);
                            }
                            header("location: ../view_book.php?id=" . $book_id);
                            exit();
                        }
                    }
                }
            }
        }
    }
    header("location: ../view_book.php?id=" . $book_id . "&error=1");
    exit();
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kölcsönzések - BookHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <i class="fas fa-book-reader me-2"></i>BookHive
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../../index.php">
                            <i class="fas fa-home me-1"></i>Kezdőlap
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../books.php">
                            <i class="fas fa-book me-1"></i>Könyvek
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="borrow.php">
                            <i class="fas fa-clipboard-list me-1"></i>Kölcsönzések
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../admin/users.php">
                            <i class="fas fa-users me-1"></i>Felhasználók
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION["name"]); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="../../auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-1"></i>Kijelentkezés
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>Aktív kölcsönzések
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($borrows)): ?>
                            <p class="text-muted mb-0">Jelenleg nincsenek aktív kölcsönzések.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Könyv</th>
                                            <th>Kölcsönző</th>
                                            <th>Kölcsönzés dátuma</th>
                                            <th>Határidő</th>
                                            <th>Művelet</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($borrows as $borrow): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($borrow["book_title"]); ?></td>
                                                <td><?php echo htmlspecialchars($borrow["user_name"]); ?></td>
                                                <td><?php echo date("Y.m.d", strtotime($borrow["borrow_date"])); ?></td>
                                                <td>
                                                    <?php 
                                                    $return_date = strtotime($borrow["return_date"]);
                                                    $now = time();
                                                    $days_left = round(($return_date - $now) / (60 * 60 * 24));
                                                    
                                                    if($days_left < 0) {
                                                        echo '<span class="text-danger">';
                                                        echo date("Y.m.d", $return_date);
                                                        echo ' ('. abs($days_left) .' napja lejárt)';
                                                        echo '</span>';
                                                    } else {
                                                        echo date("Y.m.d", $return_date);
                                                        echo ' ('. $days_left .' nap van hátra)';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="../borrows/borrow.php?return=<?php echo $borrow["id"]; ?>" class="btn btn-success btn-sm" data-tooltip="Könyv visszavétele">
                                                        <i class="fas fa-check me-1"></i>Visszavétel
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-plus me-2"></i>Új kölcsönzés
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($available_books)): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>Jelenleg nincs kölcsönözhető könyv.
                            </div>
                        <?php elseif(empty($users)): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>Nincsenek regisztrált felhasználók.
                            </div>
                        <?php else: ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-book me-1"></i>Könyv
                                    </label>
                                    <select name="book_id" class="form-select" required data-tooltip="Válassza ki a kölcsönözni kívánt könyvet">
                                        <option value="">Válasszon könyvet...</option>
                                        <?php foreach($available_books as $book): ?>
                                            <option value="<?php echo $book["id"]; ?>">
                                                <?php echo htmlspecialchars($book["title"]); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user me-1"></i>Kölcsönző
                                    </label>
                                    <select name="user_id" class="form-select" required data-tooltip="Válassza ki a kölcsönző felhasználót">
                                        <option value="">Válasszon felhasználót...</option>
                                        <?php foreach($users as $user): ?>
                                            <option value="<?php echo $user["id"]; ?>">
                                                <?php echo htmlspecialchars($user["name"]) . " (" . htmlspecialchars($user["username"]) . ")"; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Visszahozási határidő
                                    </label>
                                    <input type="date" name="return_date" class="form-control" required 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                           value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                                           data-tooltip="Adja meg a visszahozási határidőt">
                                </div>
                                <button type="submit" class="btn btn-primary w-100" data-tooltip="Kölcsönzés rögzítése">
                                    <i class="fas fa-plus me-1"></i>Kölcsönzés
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 