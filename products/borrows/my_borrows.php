<?php
session_start();
require_once "../../config/config.php";

$current_page = 'my_borrows';
$page_title = 'My Borrowings';

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . url("auth/login.php"));
    exit;
}

require_once "../../config/db.php";
require_once "../../includes/borrowing_table.php";

// Process return product request
if(isset($_GET["return"]) && !empty($_GET["return"])){
    $sql = "UPDATE borrows SET actual_return_date = NOW() WHERE id = ? AND user_id = ?";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $_GET["return"], $_SESSION["id"]);

        if(mysqli_stmt_execute($stmt)){
            // Update product status
            $sql = "UPDATE products SET status = 'available' WHERE id = (SELECT product_id FROM borrows WHERE id = ?)";
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

require_once "../../includes/header.php";

// Get user's active borrows
$borrows = array();
$sql = "SELECT borrows.*, products.product_name, products.category
        FROM borrows
        INNER JOIN products ON borrows.product_id = products.id
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
$sql = "SELECT borrows.*, products.product_name, products.category
        FROM borrows
        INNER JOIN products ON borrows.product_id = products.id
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
                <div class="d-flex align-items-center">
                    <a href="../../products/products.php" class="btn btn-success btn-sm me-3" data-tooltip="Back to products">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                    <h4 class="mb-0">
                        <i class="fas fa-box me-2"></i>My Current Borrowings
                    </h4>
                </div>
            </div>
            <div class="card-body">
                <?php
                render_borrowing_table(
                    $borrows,
                    false, // Don't show borrower column (user view)
                    "my_borrows.php", // Return URL
                    "You currently have no borrowed products."
                );
                ?> 
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
                    <p class="text-muted mb-0">You haven't returned any products yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach($history as $item): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item["product_name"]); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date("Y.m.d", strtotime($item["actual_return_date"])); ?>
                                    </small>
                                </div>
                                <p class="mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item["category"]); ?>
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