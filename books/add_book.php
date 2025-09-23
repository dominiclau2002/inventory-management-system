<?php
session_start();
$current_page = 'books';
$page_title = 'Add New Book';

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
        $title_err = "Please enter the book title.";
    } else {
        $title = trim($_POST["title"]);
    }
    
    // Validate author
    if(empty(trim($_POST["author"]))){
        $author_err = "Please enter the author's name.";
    } else {
        $author = trim($_POST["author"]);
    }
    
    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter the book description.";
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Get optional fields
    $isbn = !empty($_POST["isbn"]) ? trim($_POST["isbn"]) : null;
    $year = !empty($_POST["year"]) ? trim($_POST["year"]) : null;
    $language = !empty($_POST["language"]) ? trim($_POST["language"]) : null;
    $publisher = !empty($_POST["publisher"]) ? trim($_POST["publisher"]) : null;
    
    // Check input errors before inserting in database
    if(empty($title_err) && empty($author_err) && empty($description_err)){
        // Prepare an insert statement
        $sql = "INSERT INTO books (title, author, description, isbn, year, language, publisher, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'available')";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sssssss", $param_title, $param_author, $param_description, $param_isbn, $param_year, $param_language, $param_publisher);
            
            $param_title = $title;
            $param_author = $author;
            $param_description = $description;
            $param_isbn = $isbn;
            $param_year = $year;
            $param_language = $language;
            $param_publisher = $publisher;
            
            if(mysqli_stmt_execute($stmt)){
                header("location: ../books/books.php");
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

mysqli_close($conn);

require_once "../includes/header.php";
?>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">
            <i class="fas fa-plus me-2"></i>Add New Book
        </h4>
    </div>
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-book me-1"></i>Title
                        </label>
                        <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($title); ?>">
                        <div class="invalid-feedback"><?php echo $title_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>Author
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
                            <i class="fas fa-calendar me-1"></i>Publication Year
                        </label>
                        <input type="number" name="year" class="form-control" value="<?php echo htmlspecialchars($year); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-language me-1"></i>Language
                        </label>
                        <input type="text" name="language" class="form-control" value="<?php echo htmlspecialchars($language); ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-building me-1"></i>Publisher
                        </label>
                        <input type="text" name="publisher" class="form-control" value="<?php echo htmlspecialchars($publisher); ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-align-left me-1"></i>Description
                        </label>
                        <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                        <div class="invalid-feedback"><?php echo $description_err; ?></div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary" data-tooltip="Add book">
                    <i class="fas fa-save me-1"></i>Save
                </button>
                <a href="../books/books.php" class="btn btn-secondary" data-tooltip="Back to books list">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?> 