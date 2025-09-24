<?php
session_start();
$current_page = 'products';
$page_title = 'Delete Product';

// Check if the user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";

// Process delete operation
if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    // Check if the product has active borrows
    $sql = "SELECT COUNT(*) as active_borrows FROM borrows WHERE product_id = ? AND actual_return_date IS NULL";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = trim($_GET["id"]);

        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_array($result);

            if($row["active_borrows"] > 0){
                $_SESSION["error_message"] = "The product cannot be deleted because it has active borrowings.";
                header("location: ../products/products.php");
                exit();
            }
        }
        mysqli_stmt_close($stmt);
    }

    // Prepare a delete statement
    $sql = "DELETE FROM products WHERE id = ?";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);

        if(mysqli_stmt_execute($stmt)){
            $_SESSION["success_message"] = "Product successfully deleted.";
            header("location: ../products/products.php");
            exit();
        } else{
            $_SESSION["error_message"] = "An error occurred during deletion. Please try again later.";
            header("location: ../products/products.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
} else{
    header("location: ../products/products.php");
    exit();
}

mysqli_close($conn);
?>