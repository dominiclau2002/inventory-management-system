<?php
session_start();
$current_page = 'books';
$page_title = 'Product Details';

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";

// Check if product ID is provided
if (!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: ../books/books.php");
    exit;
}

// Get product details
$product = null;
$sql = "SELECT * FROM products WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $param_id);
    $param_id = $_GET["id"];

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $product = mysqli_fetch_array($result);
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
        LEFT JOIN users ON borrows.user_id = users.id
        WHERE borrows.product_id = ?
        ORDER BY borrow_date DESC";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_GET["id"]);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_array($result)) {
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
                    <div class="d-flex align-items-center">
                        <a href="../books/books.php" class="btn btn-success btn-sm me-3" data-tooltip="Back to products">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                        <h4 class="mb-0">
                            <i class="fas fa-box me-2"></i><?php echo htmlspecialchars($product["product_name"]); ?>
                        </h4>
                    </div>
                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                        <div class="btn-group">
                            <a href="../books/edit_book.php?id=<?php echo $product["id"]; ?>" class="btn btn-warning btn-sm" data-tooltip="Edit product">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                            <a href="../books/delete_book.php?id=<?php echo $product["id"]; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?');" data-tooltip="Delete product">
                                <i class="fas fa-trash me-1"></i>Delete
                            </a>
                        </div>
                    <?php elseif (isset($_SESSION["role"]) && $_SESSION["role"] == "user" && $product["status"] == "available"): ?>
                        <a href="../books/borrows/borrow.php?product_id=<?php echo $product["id"]; ?>" class="btn btn-primary btn-sm" data-tooltip="Borrow product">
                            <i class="fas fa-hand-holding me-1"></i>Borrow
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p>
                            <i class="fas fa-user me-2"></i><strong>Main Owner:</strong>
                            <?php echo htmlspecialchars($product["main_owner"]); ?>
                        </p>
                        <p>
                            <i class="fas fa-tag me-2"></i><strong>Category:</strong>
                            <?php echo htmlspecialchars($product["category"]); ?>
                        </p>
                        <p>
                            <i class="fas fa-barcode me-2"></i>
                            <?php if (!empty($product["serial_number"]) && empty($product["alt_serial_number"])): ?><strong>Serial Number:</strong>
                                <?php elseif (!empty($product["alt_serial_number"]) && empty($product["serial_number"])): ?><strong>Alt Serial Number:</strong>
                                <?php endif; ?>

                            <?php echo !empty($product["serial_number"]) ? htmlspecialchars($product["serial_number"]) : (!empty($product["alt_serial_number"]) ? 'Alt: ' . htmlspecialchars($product["alt_serial_number"]) : 'Not specified'); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <i class="fas fa-cog me-2"></i><strong>Prototype Version:</strong>
                            <?php echo htmlspecialchars($product["prototype_version"]); ?>
                        </p>
                        <p>
                            <i class="fas fa-info-circle me-2"></i><strong>Status:</strong>
                            <span class="badge <?php echo $product["status"] == "available" ? "bg-success" : "bg-warning"; ?>">
                                <?php echo ucfirst($product["status"]); ?>
                            </span>
                        </p>
                        <p>
                            <i class="fas fa-clock me-2"></i><strong>Added:</strong>
                            <?php echo date("Y.m.d", strtotime($product["created_at"])); ?>
                        </p>
                    </div>
                </div>
                <hr>
                <h5 class="mb-3">
                    <i class="fas fa-align-left me-2"></i>Description
                </h5>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($product["description"])); ?></p>

                <?php if (!empty($product["remarks"])): ?>
                    <hr>
                    <h5 class="mb-3">
                        <i class="fas fa-sticky-note me-2"></i>Remarks
                    </h5>
                    <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($product["remarks"])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-history me-2"></i>Borrowing History
                </h4>
            </div>
            <div class="card-body">
                <?php if (empty($borrows)): ?>
                    <p class="text-muted mb-0">This product has not been borrowed yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($borrows as $borrow): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($borrow["user_name"]); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date("Y.m.d", strtotime($borrow["borrow_date"])); ?>
                                    </small>
                                </div>
                                <p class="mb-1">
                                    <small>
                                        Due: <?php echo date("Y.m.d", strtotime($borrow["return_date"])); ?>
                                    </small>
                                </p>
                                <?php if ($borrow["actual_return_date"]): ?>
                                    <small class="text-success">
                                        <i class="fas fa-check me-1"></i>Returned: <?php echo date("Y.m.d", strtotime($borrow["actual_return_date"])); ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-primary">
                                        <i class="fas fa-clock me-1"></i>Currently borrowed
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