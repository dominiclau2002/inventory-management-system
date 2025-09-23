<?php
session_start();
$current_page = 'my_borrows';
$page_title = 'My Borrowings';

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../auth/login.php");
    exit;
}

require_once "../../config/db.php";
require_once "../../includes/header.php";

// Process return book request
if(isset($_GET["return"]) && !empty($_GET["return"])){
    $sql = "UPDATE borrows SET actual_return_date = NOW() WHERE id = ? AND user_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $_GET["return"], $_SESSION["id"]);
        
        if(mysqli_stmt_execute($stmt)){
            // Update book status
            $sql = "UPDATE books SET status = 'available' WHERE id = (SELECT book_id FROM borrows WHERE id = ?)";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $_GET["return"]);
                mysqli_stmt_execute($stmt);
            }
            header("location: ../borrows/my_borrows.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Get user's active borrows
$borrows = array();
$sql = "SELECT borrows.*, books.title as book_title, books.author 
        FROM borrows 
        INNER JOIN books ON borrows.book_id = books.id 
        WHERE borrows.user_id = ? AND actual_return_date IS NULL 
        ORDER BY borrow_date DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_array($result)){
            $borrows[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get user's borrow history
$history = array();
$sql = "SELECT borrows.*, books.title as book_title, books.author 
        FROM borrows 
        INNER JOIN books ON borrows.book_id = books.id 
        WHERE borrows.user_id = ? AND actual_return_date IS NOT NULL 
        ORDER BY actual_return_date DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_array($result)){
            $history[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-book me-2"></i>My Current Borrowings
                </h4>
            </div>
            <div class="card-body">
                <?php if(empty($borrows)): ?>
                    <p class="text-muted mb-0">You currently have no borrowed books.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Borrow Date</th>
                                    <th>Due Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($borrows as $borrow): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($borrow["book_title"]); ?></td>
                                        <td><?php echo htmlspecialchars($borrow["author"]); ?></td>
                                        <td><?php echo date("Y.m.d", strtotime($borrow["borrow_date"])); ?></td>
                                        <td>
                                            <?php 
                                            $return_date = strtotime($borrow["return_date"]);
                                            $now = time();
                                            $days_left = round(($return_date - $now) / (60 * 60 * 24));
                                            
                                            if($days_left < 0) {
                                                echo '<span class="text-danger">';
                                                echo date("Y.m.d", $return_date);
                                                echo ' ('. abs($days_left) .' days overdue)';
                                                echo '</span>';
                                            } else {
                                                echo date("Y.m.d", $return_date);
                                                echo ' ('. $days_left .' days left)';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="my_borrows.php?return=<?php echo $borrow["id"]; ?>" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to return the book?');" data-tooltip="Return book">
                                                <i class="fas fa-check me-1"></i>Return
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
                    <i class="fas fa-history me-2"></i>History
                </h4>
            </div>
            <div class="card-body">
                <?php if(empty($history)): ?>
                    <p class="text-muted mb-0">You haven't returned any books yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach($history as $item): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item["book_title"]); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date("Y.m.d", strtotime($item["actual_return_date"])); ?>
                                    </small>
                                </div>
                                <p class="mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($item["author"]); ?>
                                    </small>
                                </p>
                                <small class="text-success">
                                    <i class="fas fa-check me-1"></i>Returned
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?> 