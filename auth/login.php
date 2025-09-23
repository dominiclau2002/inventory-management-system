<?php
session_start();
$current_page = 'login';
$page_title = 'Login';

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: ../index.php");
    exit;
}

require_once "../config/db.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter your username.";
    } else{
        // Sanitize username input
        $username = trim(htmlspecialchars($_POST["username"]));
        if(strlen($username) < 3 || strlen($username) > 50) {
            $username_err = "Username must be 3-50 characters long.";
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        // Sanitize password input
        $password = trim($_POST["password"]);
        if(strlen($password) < 6) {
            $password_err = "Password must be at least 6 characters long.";
        }
    }
    
    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT id, username, password, role, name FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role, $name);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            session_start();

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = htmlspecialchars($username);
                            $_SESSION["role"] = htmlspecialchars($role);
                            $_SESSION["name"] = htmlspecialchars($name);

                            header("location: ../index.php");
                        } else{
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    $login_err = "Invalid username or password.";
                }
            } else{
                echo "An error occurred. Please try again later.";
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
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php 
                        if(!empty($login_err)){
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>' . $login_err . '
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                  </div>';
                        }        
                        ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group mb-3">
                                <label>
                                    <i class="fas fa-user me-2"></i>Username
                                </label>
                                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" data-tooltip="Enter your username">
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>    
                            <div class="form-group mb-3">
                                <label>
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" data-tooltip="Enter your password">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="form-group text-center">
                                <button type="submit" class="btn btn-primary btn-block w-100" data-tooltip="Click to log in">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                            <p class="text-center mt-3">
                                Don't have an account yet? 
                                <a href="../auth/register.php" class="text-decoration-none" data-tooltip="Create a new account">
                                    <i class="fas fa-user-plus me-1"></i>Register here
                                </a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<?php require_once "../includes/footer.php"; ?> 