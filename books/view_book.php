<?php
session_start();
$current_page = 'books';
$page_title = 'Könyv részletei';

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";

// Check if book ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: ../books/books.php");
    exit;
}

// Get book details
$book = null;
$sql = "SELECT * FROM books WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $param_id);
    $param_id = $_GET["id"];
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $book = mysqli_fetch_array($result);
        } else {
            header("location: ../books/books.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get borrowing history
$borrows = array();
$sql = "SELECT borrows.*, users.name as user_name 
        FROM borrows 
        INNER JOIN users ON borrows.user_id = users.id 
        WHERE book_id = ? 
        ORDER BY borrow_date DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $param_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_array($result)){
            $borrows[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);

require_once "../includes/header.php";
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-book me-2"></i><?php echo htmlspecialchars($book["title"]); ?>
                    </h4>
                    <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                    <div class="btn-group">
                        <a href="/books/edit_book.php?id=<?php echo $book["id"]; ?>" class="btn btn-warning btn-sm" data-tooltip="Könyv szerkesztése">
                            <i class="fas fa-edit me-1"></i>Szerkesztés
                        </a>
                        <a href="../books/delete_book.php?id=<?php echo $book["id"]; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Biztosan törölni szeretné ezt a könyvet?');" data-tooltip="Könyv törlése">
                            <i class="fas fa-trash me-1"></i>Törlés
                        </a>
                    </div>
                    <?php elseif(isset($_SESSION["role"]) && $_SESSION["role"] == "user" && $book["status"] == "available"): ?>
                    <a href="../books/borrows/borrow.php?book_id=<?php echo $book["id"]; ?>" class="btn btn-primary btn-sm" data-tooltip="Könyv kölcsönzése">
                        <i class="fas fa-hand-holding me-1"></i>Kölcsönzés
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p>
                            <i class="fas fa-user me-2"></i><strong>Szerző:</strong>
                            <?php echo htmlspecialchars($book["author"]); ?>
                        </p>
                        <p>
                            <i class="fas fa-calendar me-2"></i><strong>Kiadás éve:</strong>
                            <?php echo !empty($book["year"]) ? htmlspecialchars($book["year"]) : 'Nincs megadva'; ?>
                        </p>
                        <p>
                            <i class="fas fa-barcode me-2"></i><strong>ISBN:</strong>
                            <?php echo !empty($book["isbn"]) ? htmlspecialchars($book["isbn"]) : 'Nincs megadva'; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <i class="fas fa-language me-2"></i><strong>Nyelv:</strong>
                            <?php echo !empty($book["language"]) ? htmlspecialchars($book["language"]) : 'Nincs megadva'; ?>
                        </p>
                        <p>
                            <i class="fas fa-building me-2"></i><strong>Kiadó:</strong>
                            <?php echo !empty($book["publisher"]) ? htmlspecialchars($book["publisher"]) : 'Nincs megadva'; ?>
                        </p>
                        <p>
                            <i class="fas fa-clock me-2"></i><strong>Hozzáadva:</strong>
                            <?php echo date("Y.m.d", strtotime($book["created_at"])); ?>
                        </p>
                    </div>
                </div>
                <hr>
                <h5 class="mb-3">
                    <i class="fas fa-align-left me-2"></i>Leírás
                </h5>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($book["description"])); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-history me-2"></i>Kölcsönzési előzmények
                </h4>
            </div>
            <div class="card-body">
                <?php if(empty($borrows)): ?>
                    <p class="text-muted mb-0">A könyvet még nem kölcsönözték ki.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach($borrows as $borrow): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($borrow["user_name"]); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date("Y.m.d", strtotime($borrow["borrow_date"])); ?>
                                    </small>
                                </div>
                                <p class="mb-1">
                                    <small>
                                        Határidő: <?php echo date("Y.m.d", strtotime($borrow["return_date"])); ?>
                                    </small>
                                </p>
                                <?php if($borrow["actual_return_date"]): ?>
                                    <small class="text-success">
                                        <i class="fas fa-check me-1"></i>Visszahozva: <?php echo date("Y.m.d", strtotime($borrow["actual_return_date"])); ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-primary">
                                        <i class="fas fa-clock me-1"></i>Jelenleg kikölcsönözve
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?> 