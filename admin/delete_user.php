<?php
session_start();
$current_page = 'users';
$page_title = 'Felhasználó törlése';

// Check if the user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";

// Process delete operation
if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    // Prevent admin from deleting themselves
    if($_GET["id"] == $_SESSION["id"]){
        $_SESSION["error_message"] = "Nem törölheti a saját fiókját.";
        header("location: ../admin/users.php");
        exit();
    }
    
    // Check if user has active borrows
    $sql = "SELECT COUNT(*) as active_borrows FROM borrows WHERE user_id = ? AND actual_return_date IS NULL";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = trim($_GET["id"]);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_array($result);
            
            if($row["active_borrows"] > 0){
                $_SESSION["error_message"] = "A felhasználó nem törölhető, mert aktív kölcsönzése van.";
                header("location: ../admin/users.php");
                exit();
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Prepare a delete statement
    $sql = "DELETE FROM users WHERE id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        
        if(mysqli_stmt_execute($stmt)){
            $_SESSION["success_message"] = "A felhasználó sikeresen törölve.";
            header("location: ../admin/users.php");
            exit();
        } else{
            $_SESSION["error_message"] = "Hiba történt a törlés során. Kérjük próbálja újra később.";
            header("location: ../admin/users.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
} else{
    header("location: ../admin/users.php");
    exit();
}

mysqli_close($conn);
?> 