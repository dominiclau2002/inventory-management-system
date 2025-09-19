<?php
session_start();
$current_page = 'register';
$page_title = 'Regisztráció';

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

require_once "../config/db.php";

$username = $password = $confirm_password = $name = "";
$username_err = $password_err = $confirm_password_err = $name_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Kérem adja meg a nevét.";
    } else{
        $name = trim(htmlspecialchars($_POST["name"]));
        if(strlen($name) < 2 || strlen($name) > 100) {
            $name_err = "A név 2-100 karakter hosszú lehet.";
        }
    }

    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Kérem adjon meg egy felhasználónevet.";
    } else{
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim(htmlspecialchars($_POST["username"]));
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "Ez a felhasználónév már foglalt.";
                } else{
                    $username = $param_username;
                }
            } else{
                echo "Hiba történt. Kérjük próbálja újra később.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Kérem adjon meg egy jelszót.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "A jelszónak legalább 6 karakterből kell állnia.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Kérem erősítse meg a jelszót.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "A jelszavak nem egyeznek.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($name_err)){
        $sql = "INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, 'user')";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password, $param_name);
            
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_name = $name;
            
            if(mysqli_stmt_execute($stmt)){
                header("location: ../auth/login.php");
            } else{
                echo "Hiba történt. Kérjük próbálja újra később.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}

require_once "../includes/header.php";
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center mb-0">
                    <i class="fas fa-user-plus me-2"></i>Regisztráció
                </h3>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group mb-3">
                        <label>
                            <i class="fas fa-user me-2"></i>Teljes név
                        </label>
                        <input type="text" name="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>" data-tooltip="Adja meg a teljes nevét">
                        <span class="invalid-feedback"><?php echo $name_err; ?></span>
                    </div>
                    <div class="form-group mb-3">
                        <label>
                            <i class="fas fa-user-circle me-2"></i>Felhasználónév
                        </label>
                        <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" data-tooltip="Válasszon egy egyedi felhasználónevet">
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    </div>    
                    <div class="form-group mb-3">
                        <label>
                            <i class="fas fa-lock me-2"></i>Jelszó
                        </label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>" data-tooltip="A jelszónak legalább 6 karakter hosszúnak kell lennie">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group mb-3">
                        <label>
                            <i class="fas fa-lock me-2"></i>Jelszó megerősítése
                        </label>
                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>" data-tooltip="Írja be újra a jelszavát">
                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary btn-block w-100" data-tooltip="Kattintson a regisztrációhoz">
                            <i class="fas fa-user-plus me-2"></i>Regisztráció
                        </button>
                    </div>
                    <p class="text-center mt-3">
                        Már van fiókja? 
                        <a href="../auth/login.php" class="text-decoration-none" data-tooltip="Jelentkezzen be meglévő fiókjával">
                            <i class="fas fa-sign-in-alt me-1"></i>Jelentkezzen be itt
                        </a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?> 