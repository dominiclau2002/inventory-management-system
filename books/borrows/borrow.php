<?php
session_start();
require_once "../../config/config.php";

$current_page = 'borrows';
$page_title = 'Borrowings';

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . url("auth/login.php"));
    exit;
}

// For non-admin users, only allow direct product borrowing
if($_SESSION["role"] !== "admin" && !isset($_GET["product_id"])) {
    header("location: " . url("index.php"));
    exit;
}

require_once "../../config/db.php";

// Process return product
if(isset($_GET["return"]) && !empty($_GET["return"])){
    $sql = "UPDATE borrows SET actual_return_date = NOW() WHERE id = ?";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $_GET["return"];

        if(mysqli_stmt_execute($stmt)){
            header("location: " . url("books/borrows/borrow.php"));
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Process new borrow
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $product_id = $_POST["product_id"];
    $user_id = $_POST["user_id"];
    $return_date = $_POST["return_date"];

    $sql = "INSERT INTO borrows (product_id, user_id, borrow_date, return_date) VALUES (?, ?, NOW(), ?)";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "iis", $product_id, $user_id, $return_date);

        if(mysqli_stmt_execute($stmt)){
            header("location: " . url("books/borrows/borrow.php"));
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Get all active borrows
$borrows = array();
$sql = "SELECT borrows.*, products.product_name, users.name as user_name
        FROM borrows
        INNER JOIN products ON borrows.product_id = products.id
        INNER JOIN users ON borrows.user_id = users.id
        WHERE actual_return_date IS NULL
        ORDER BY borrow_date DESC";

if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $borrows[] = $row;
    }
}

// Get available products for new borrow
$available_products = array();
$sql = "SELECT id, product_name, category, serial_number, alt_serial_number FROM products WHERE id NOT IN (SELECT product_id FROM borrows WHERE actual_return_date IS NULL)";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $available_products[] = $row;
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

// Handle direct product borrowing
if(isset($_GET["product_id"]) && !empty($_GET["product_id"])) {
    $product_id = $_GET["product_id"];
    $user_id = $_SESSION["id"];
    $return_date = date('Y-m-d', strtotime('+30 days'));
    
    // Check if product is available
    $sql = "SELECT status FROM products WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_array($result)) {
                if($row["status"] == "available") {
                    // Insert borrow record
                    $sql = "INSERT INTO borrows (product_id, user_id, borrow_date, return_date) VALUES (?, ?, NOW(), ?)";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "iis", $product_id, $user_id, $return_date);
                        if(mysqli_stmt_execute($stmt)) {
                            // Update product status (main_owner stays unchanged)
                            $sql = "UPDATE products SET status = 'borrowed' WHERE id = ?";
                            if($stmt = mysqli_prepare($conn, $sql)) {
                                mysqli_stmt_bind_param($stmt, "i", $product_id);
                                mysqli_stmt_execute($stmt);
                            }
                            header("location: " . url("books/view_book.php?id=" . $product_id));
                            exit();
                        }
                    }
                }
            }
        }
    }
    header("location: " . url("books/view_book.php?id=" . $product_id . "&error=1"));
    exit();
}

mysqli_close($conn);

// Set flag for header path resolution
$is_root_level = false;
require_once "../../includes/borrowing_table.php";
require_once "../../includes/header.php";
?>
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>Products on Loan
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php
                        render_borrowing_table(
                            $borrows,
                            true, // Show borrower column (admin view)
                            "borrow.php", // Return URL
                            "No active borrowings at the moment."
                        );
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-plus me-2"></i>New Product Loan
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($available_products)): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>No products available for loan at the moment.
                            </div>
                        <?php elseif(empty($users)): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>No registered users.
                            </div>
                        <?php else: ?>
                            <form method="POST" action="<?php echo url('books/borrows/borrow.php'); ?>">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-box me-1"></i>Product
                                    </label>
                                    <select name="product_id" class="form-select" required data-tooltip="Select the product you want to borrow">
                                        <option value="">Select a product...</option>
                                        <?php foreach($available_products as $product): ?>
                                            <option value="<?php echo $product["id"]; ?>">
                                                <?php
                                                echo htmlspecialchars($product["product_name"]);

                                                // Display serial number if available
                                                if (!empty($product["serial_number"])) {
                                                    echo " - SN: " . htmlspecialchars($product["serial_number"]);
                                                } elseif (!empty($product["alt_serial_number"])) {
                                                    echo " - Alt SN: " . htmlspecialchars($product["alt_serial_number"]);
                                                }

                                                // Add category for additional context
                                                echo " (" . htmlspecialchars($product["category"]) . ")";
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user me-1"></i>Loanee
                                    </label>
                                    <select name="user_id" class="form-select" required data-tooltip="Select the borrowing user">
                                        <option value="">Select a user...</option>
                                        <?php foreach($users as $user): ?>
                                            <option value="<?php echo $user["id"]; ?>">
                                                <?php echo htmlspecialchars($user["name"]) . " (" . htmlspecialchars($user["username"]) . ")"; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Return Due Date
                                    </label>
                                    <input type="date" name="return_date" class="form-control" required 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                           value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                                           data-tooltip="Enter the return due date">
                                </div>
                                <button type="submit" class="btn btn-primary w-100" data-tooltip="Record borrowing">
                                    <i class="fas fa-plus me-1"></i>Borrow
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<?php require_once "../../includes/footer.php"; ?> 