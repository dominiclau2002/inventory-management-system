<?php
session_start();
$current_page = 'home';
$page_title = 'Home';

require_once "config/db.php";

$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Get user's borrowed products if logged in
$borrowed_products = array();
if($is_logged_in && isset($_SESSION["id"])) {
    $sql = "SELECT products.*, borrows.borrow_date, borrows.return_date, users.username as borrower_username
            FROM products
            INNER JOIN borrows ON products.id = borrows.product_id
            INNER JOIN users ON borrows.user_id = users.id
            WHERE borrows.user_id = ? AND borrows.actual_return_date IS NULL
            ORDER BY borrows.borrow_date DESC";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            while($row = mysqli_fetch_array($result)){
                $borrowed_products[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Get latest products
$latest_products = array();
$sql = "SELECT * FROM products ORDER BY created_at DESC LIMIT ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $limit);
    $limit = 5;
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_array($result)){
            $latest_products[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);

// Set a flag to indicate this is the root level
$is_root_level = true;
require_once "includes/header.php";
?>
        <?php if(!$is_logged_in): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="display-4 mb-4">
                            <i class="fas fa-book-open text-primary me-3"></i>
                            Welcome to CA-APS Inventory Management System!
                        </h1>
                        <p class="lead mb-4">Browse our inventory and manage your borrowings here.</p>
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                            <a href="auth/login.php" class="btn btn-primary btn-lg px-4 gap-3" data-tooltip="Sign in to your account">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </a>
                            <a href="auth/register.php" class="btn btn-outline-primary btn-lg px-4" data-tooltip="Create a new account">
                                <i class="fas fa-user-plus me-2"></i>Register
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
                            <i class="fas fa-clock me-2"></i>Latest Products
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($latest_products)): ?>
                            <p class="text-muted mb-0">Currently no products in the system.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach($latest_products as $product): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <i class="fas fa-box me-2"></i><?php echo htmlspecialchars($product["product_name"]); ?>
                                                </h5>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <i class="fas fa-tag me-1"></i>Category: <?php echo htmlspecialchars($product["category"]); ?>
                                                    </small>
                                                </p>
                                                <p class="card-text">
                                                    <?php echo htmlspecialchars("Description: ". substr($product["description"], 0, 100)); ?>
                                                </p>
                                                <p class="card-text">
                                                    <?php if(!empty($product["serial_number"])): ?>
                                                        Serial Number: <?php echo htmlspecialchars($product["serial_number"]); ?>
                                                    <?php else: ?>
                                                        Alt. Serial Number: <?php echo htmlspecialchars($product["alt_serial_number"]); ?>
                                                    <?php endif; ?>
                                                </p>

                                                <p class="card-text">
                                                    <?php echo htmlspecialchars("Prototype Version: " . substr($product["prototype_version"], 0, 100)); ?>
                                                </p>

                                                <p class="card-text">
                                                    <?php echo htmlspecialchars("Main Owner: " . substr($product["main_owner"], 0, 100)); ?>
                                                </p>

                                                
                                                <?php if($is_logged_in): ?>
                                                <a href="books/view_book.php?id=<?php echo $product["id"]; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-info-circle me-1"></i>Details
                                                </a>
                                                <?php else: ?>
                                                <div class="alert alert-info mb-0 py-2">
                                                    <small><i class="fas fa-info-circle me-1"></i>Please log in to view details</small>
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
                            <i class="fas fa-clipboard-list me-2"></i>My Borrowed Products
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($borrowed_products)): ?>
                            <p class="text-muted mb-0">You currently have no borrowed products.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach($borrowed_products as $product): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($product["product_name"]); ?></h5>
                                            <small class="text-muted">
                                                Due: <?php echo date("Y.m.d", strtotime($product["return_date"])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1">
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>Borrowed by: <?php echo htmlspecialchars($product["borrower_username"]); ?>
                                            </small>
                                        </p>
                                        <p class="mb-1">
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>Main Owner: <?php echo htmlspecialchars($product["main_owner"]); ?>
                                            </small>
                                        </p>

                                        <a href="books/view_book.php?id=<?php echo $product["id"]; ?>" class="btn btn-primary btn-sm mt-2">
                                            <i class="fas fa-info-circle me-1"></i>Details
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