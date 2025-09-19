<?php
session_start();

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
        $username_err = "Kérem adja meg a felhasználónevét.";
    } else{
        // Sanitize username input
        $username = trim(htmlspecialchars($_POST["username"]));
        if(strlen($username) < 3 || strlen($username) > 50) {
            $username_err = "A felhasználónév 3-50 karakter hosszú lehet.";
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Kérem adja meg a jelszavát.";
    } else{
        // Sanitize password input
        $password = trim($_POST["password"]);
        if(strlen($password) < 6) {
            $password_err = "A jelszónak legalább 6 karakter hosszúnak kell lennie.";
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
                            $login_err = "Érvénytelen felhasználónév vagy jelszó.";
                        }
                    }
                } else{
                    $login_err = "Érvénytelen felhasználónév vagy jelszó.";
                }
            } else{
                echo "Hiba történt. Kérjük próbálja újra később.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés - BookHive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-book-reader me-2"></i>BookHive
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="../auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Bejelentkezés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>Regisztráció
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">
                            <i class="fas fa-sign-in-alt me-2"></i>Bejelentkezés
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
                                    <i class="fas fa-user me-2"></i>Felhasználónév
                                </label>
                                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" data-tooltip="Adja meg a felhasználónevét">
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>    
                            <div class="form-group mb-3">
                                <label>
                                    <i class="fas fa-lock me-2"></i>Jelszó
                                </label>
                                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" data-tooltip="Adja meg a jelszavát">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="form-group text-center">
                                <button type="submit" class="btn btn-primary btn-block w-100" data-tooltip="Kattintson a bejelentkezéshez">
                                    <i class="fas fa-sign-in-alt me-2"></i>Bejelentkezés
                                </button>
                            </div>
                            <p class="text-center mt-3">
                                Még nincs fiókja? 
                                <a href="../auth/register.php" class="text-decoration-none" data-tooltip="Hozzon létre új fiókot">
                                    <i class="fas fa-user-plus me-1"></i>Regisztráljon itt
                                </a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 