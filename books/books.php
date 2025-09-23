<?php
session_start();
$current_page = 'books';
$page_title = 'Products';

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";
require_once "../includes/header.php";

// Initialize search variables
$search_product_name = $search_category = "";
$products = array();

// Process search form
if($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET["search_product_name"]) || isset($_GET["search_category"]))){
    // Sanitize inputs
    $search_product_name = isset($_GET["search_product_name"]) ? trim(htmlspecialchars($_GET["search_product_name"])) : "";
    $search_category = isset($_GET["search_category"]) ? trim(htmlspecialchars($_GET["search_category"])) : "";
    
    // Prepare the base query
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = array();
    $types = "";
    
    // Add product name search condition if provided
    if(!empty($search_product_name)){
        $sql .= " AND product_name LIKE ?";
        $params[] = "%" . $search_product_name . "%";
        $types .= "s";
    }
    
    // Add category search condition if provided
    if(!empty($search_category) && $search_category != "All Categories"){
        $sql .= " AND category = ?";
        $params[] = $search_category;
        $types .= "s";
    }
    
    $sql .= " ORDER BY product_name ASC";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        if(!empty($params)){
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);

            while($row = mysqli_fetch_array($result)){
                $products[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Get all products if no search parameters
    $sql = "SELECT * FROM products ORDER BY product_name ASC";
    if($stmt = mysqli_prepare($conn, $sql)){
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_array($result)){
                $products[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-search me-2"></i>Search Products
                </h4>
                <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                <a href="../books/add_book.php" class="btn btn-primary btn-sm" data-tooltip="Add new product">
                    <i class="fas fa-plus me-1"></i>New Product
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-box me-1"></i>Product Name
                            </label>
                            <input type="text" name="search_product_name" class="form-control" value="<?php echo htmlspecialchars($search_product_name); ?>" data-tooltip="Search by product name">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-tag me-1"></i>Category
                            </label>
                            <select name="search_category" class="form-select" data-tooltip="Search by category">
                                <?php foreach(['All Categories','Headset(PCD)', 'Keyboard', 'Mouse', 'Mouse Mat', 'Speaker', 'Controller','Smart Home','Headset(MCD)','Broadcaster','Systems','Systems Accessories', 'Accessories'] as $category): ?>
                                    <option value="<?php echo $category; ?>" <?php echo ($search_category == $category) ? 'selected' : ''; ?>>
                                        <?php echo $category; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100" data-tooltip="Start search">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Information
                </h4>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    <i class="fas fa-box text-primary me-2"></i>Total products: <?php echo count($products); ?>
                </p>
                <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                <div class="dropdown">
                    <button class="btn btn-success btn-sm dropdown-toggle w-100" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download me-1"></i>Export Products
                    </button>
                    <ul class="dropdown-menu w-100" aria-labelledby="exportDropdown">
                        <li><a class="dropdown-item" href="<?php echo url('admin/export_products.php?type=all'); ?>">
                            <i class="fas fa-file-excel me-2"></i>All Products
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo url('admin/export_products.php?type=available'); ?>">
                            <i class="fas fa-warehouse me-2"></i>Available Only
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo url('admin/export_products.php?type=on_loan'); ?>">
                            <i class="fas fa-handshake me-2"></i>On Loan Only
                        </a></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php if(empty($products)): ?>
        <div class="col-12">
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>No products found with the specified criteria.
            </div>
        </div>
    <?php else: ?>
        <?php foreach($products as $product): ?>
            <div class="col-md-6 col-lg-4 mb-4">
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
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>Owner: <?php echo htmlspecialchars($product["main_owner"]); ?>
                            </small>
                        </p>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-barcode me-1"></i>Serial: <?php echo !empty($product["serial_number"]) ? htmlspecialchars($product["serial_number"]) : (!empty($product["alt_serial_number"]) ? htmlspecialchars($product["alt_serial_number"]) : 'Not specified'); ?>
                            </small>
                        </p>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-cog me-1"></i>Prototype Version: <?php echo htmlspecialchars($product["prototype_version"]); ?>
                            </small>
                        </p>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-circle-dot me-1"></i> Status:
                                <span class="badge <?php echo $product["status"] == "available" ? "bg-success" : "bg-warning"; ?> ms-2">
                                    <?php echo ucfirst($product["status"]); ?>
                                </span>
                            </small>
                        </p>

                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="../books/view_book.php?id=<?php echo $product["id"]; ?>" class="btn btn-primary btn-sm" data-tooltip="Product details">
                                <i class="fas fa-info-circle me-1"></i>Details
                            </a>
                            <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                            <div class="btn-group">
                                <a href="../books/edit_book.php?id=<?php echo $product["id"]; ?>" class="btn btn-warning btn-sm me-2" data-tooltip="Edit product">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../books/delete_book.php?id=<?php echo $product["id"]; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?');" data-tooltip="Delete product">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?> 