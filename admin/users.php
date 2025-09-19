<?php
session_start();
$current_page = 'users';
$page_title = 'Felhasználók kezelése';

// Check if the user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";
require_once "../includes/header.php";

// Process role change
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"]) && isset($_POST["new_role"])){
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "si", $param_role, $param_id);
        
        $param_role = $_POST["new_role"];
        $param_id = $_POST["user_id"];
        
        if(mysqli_stmt_execute($stmt)){
            $_SESSION["success_message"] = "A felhasználó szerepköre sikeresen módosítva.";
        } else{
            $_SESSION["error_message"] = "Hiba történt a szerepkör módosítása során.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Get all users
$users = array();
$sql = "SELECT * FROM users ORDER BY name ASC";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $users[] = $row;
    }
}

mysqli_close($conn);
?>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">
            <i class="fas fa-users me-2"></i>Felhasználók kezelése
        </h4>
    </div>
    <div class="card-body">
        <?php if(isset($_SESSION["success_message"])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION["success_message"]; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION["success_message"]); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION["error_message"])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION["error_message"]; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION["error_message"]); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Név</th>
                        <th>Felhasználónév</th>
                        <th>Szerepkör</th>
                        <th>Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user["name"]); ?></td>
                            <td><?php echo htmlspecialchars($user["username"]); ?></td>
                            <td>
                                <span class="badge <?php echo $user["role"] == "admin" ? "bg-danger" : "bg-primary"; ?>">
                                    <?php echo $user["role"] == "admin" ? "Adminisztrátor" : "Felhasználó"; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($user["id"] != $_SESSION["id"]): ?>
                                <div class="btn-group">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Biztosan módosítja a felhasználó szerepkörét?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user["id"]; ?>">
                                        <input type="hidden" name="new_role" value="<?php echo $user["role"] == "admin" ? "user" : "admin"; ?>">
                                        <button type="submit" class="btn btn-warning btn-sm" data-tooltip="Szerepkör módosítása">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    </form>
                                    <a href="../admin/delete_user.php?id=<?php echo $user["id"]; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Biztosan törölni szeretné ezt a felhasználót?');" data-tooltip="Felhasználó törlése">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?> 