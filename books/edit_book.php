<?php
session_start();
$current_page = 'books';
$page_title = 'Könyv szerkesztése';

// Check if the user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";

// Define variables and initialize with empty values
$title = $author = $description = $isbn = $year = $language = $publisher = "";
$title_err = $author_err = $description_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Kérem adja meg a könyv címét.";
    } else {
        $title = trim($_POST["title"]);
    }
    
    // Validate author
    if(empty(trim($_POST["author"]))){
        $author_err = "Kérem adja meg a szerző nevét.";
    } else {
        $author = trim($_POST["author"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Kérem adja meg a könyv leírását.";
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Get optional fields
    $isbn = !empty($_POST["isbn"]) ? trim($_POST["isbn"]) : null;
    $year = !empty($_POST["year"]) ? trim($_POST["year"]) : null;
    $language = !empty($_POST["language"]) ? trim($_POST["language"]) : null;
    $publisher = !empty($_POST["publisher"]) ? trim($_POST["publisher"]) : null;
    
    // Check input errors before updating in database
    if(empty($title_err) && empty($author_err) && empty($description_err)){
        // Prepare an update statement
        $sql = "UPDATE books SET title = ?, author = ?, description = ?, isbn = ?, year = ?, language = ?, publisher = ? WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sssssssi", $param_title, $param_author, $param_description, $param_isbn, $param_year, $param_language, $param_publisher, $param_id);
            
            $param_title = $title;
            $param_author = $author;
            $param_description = $description;
            $param_isbn = $isbn;
            $param_year = $year;
            $param_language = $language;
            $param_publisher = $publisher;
            $param_id = $_POST["id"];
            
            if(mysqli_stmt_execute($stmt)){
                header("location: ../books/view_book.php?id=".$_POST["id"]);
                exit();
            } else{
                echo "Valami hiba történt. Kérjük próbálja újra később.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Check existence of id parameter before processing further
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        // Get URL parameter
        $id = trim($_GET["id"]);
        
        // Prepare a select statement
        $sql = "SELECT * FROM books WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $id;
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                
                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result);
                    
                    $title = $row["title"];
                    $author = $row["author"];
                    $description = $row["description"];
                    $isbn = $row["isbn"];
                    $year = $row["year"];
                    $language = $row["language"];
                    $publisher = $row["publisher"];
                } else{
                    header("location: ../books/books.php");
                    exit();
                }
            } else{
                echo "Valami hiba történt. Kérjük próbálja újra később.";
            }
            
            mysqli_stmt_close($stmt);
        }
    } else{
        header("location: ../books/books.php");
        exit();
    }
}

mysqli_close($conn);

require_once "../includes/header.php";
?>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">
            <i class="fas fa-edit me-2"></i>Könyv szerkesztése
        </h4>
    </div>
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $_GET["id"]; ?>" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-book me-1"></i>Cím
                        </label>
                        <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($title); ?>">
                        <div class="invalid-feedback"><?php echo $title_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>Szerző
                        </label>
                        <input type="text" name="author" class="form-control <?php echo (!empty($author_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($author); ?>">
                        <div class="invalid-feedback"><?php echo $author_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-barcode me-1"></i>ISBN
                        </label>
                        <input type="text" name="isbn" class="form-control" value="<?php echo htmlspecialchars($isbn); ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar me-1"></i>Kiadás éve
                        </label>
                        <input type="number" name="year" class="form-control" value="<?php echo htmlspecialchars($year); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-language me-1"></i>Nyelv
                        </label>
                        <input type="text" name="language" class="form-control" value="<?php echo htmlspecialchars($language); ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-building me-1"></i>Kiadó
                        </label>
                        <input type="text" name="publisher" class="form-control" value="<?php echo htmlspecialchars($publisher); ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-align-left me-1"></i>Leírás
                        </label>
                        <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                        <div class="invalid-feedback"><?php echo $description_err; ?></div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="id" value="<?php echo $_GET["id"]; ?>">
            <div class="mt-3">
                <button type="submit" class="btn btn-primary" data-tooltip="Módosítások mentése">
                    <i class="fas fa-save me-1"></i>Mentés
                </button>
                <a href="../books/view_book.php?id=<?php echo $_GET["id"]; ?>" class="btn btn-secondary" data-tooltip="Vissza a könyv adatlapjára">
                    <i class="fas fa-arrow-left me-1"></i>Vissza
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?> 