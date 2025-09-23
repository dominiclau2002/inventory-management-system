<?php
session_start();
$current_page = 'books';
$page_title = 'Books';

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";
require_once "../includes/header.php";

// Initialize search variables
$search_title = $search_author = "";
$books = array();

// Process search form
if($_SERVER["REQUEST_METHOD"] == "GET" && (isset($_GET["search_title"]) || isset($_GET["search_author"]))){
    // Sanitize inputs
    $search_title = isset($_GET["search_title"]) ? trim(htmlspecialchars($_GET["search_title"])) : "";
    $search_author = isset($_GET["search_author"]) ? trim(htmlspecialchars($_GET["search_author"])) : "";
    
    // Prepare the base query
    $sql = "SELECT * FROM books WHERE 1=1";
    $params = array();
    $types = "";
    
    // Add title search condition if provided
    if(!empty($search_title)){
        $sql .= " AND title LIKE ?";
        $params[] = "%" . $search_title . "%";
        $types .= "s";
    }
    
    // Add author search condition if provided
    if(!empty($search_author)){
        $sql .= " AND author LIKE ?";
        $params[] = "%" . $search_author . "%";
        $types .= "s";
    }
    
    $sql .= " ORDER BY title ASC";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        if(!empty($params)){
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            while($row = mysqli_fetch_array($result)){
                $books[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Get all books if no search parameters
    $sql = "SELECT * FROM books ORDER BY title ASC";
    if($stmt = mysqli_prepare($conn, $sql)){
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_array($result)){
                $books[] = $row;
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
                    <i class="fas fa-search me-2"></i>Search Books
                </h4>
                <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                <a href="../books/add_book.php" class="btn btn-primary btn-sm" data-tooltip="Add new book">
                    <i class="fas fa-plus me-1"></i>New Book
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-book me-1"></i>Title
                            </label>
                            <input type="text" name="search_title" class="form-control" value="<?php echo htmlspecialchars($search_title); ?>" data-tooltip="Search by title">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user me-1"></i>Author
                            </label>
                            <input type="text" name="search_author" class="form-control" value="<?php echo htmlspecialchars($search_author); ?>" data-tooltip="Search by author">
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
                <p class="mb-0">
                    <i class="fas fa-book text-primary me-2"></i>Total books: <?php echo count($books); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php if(empty($books)): ?>
        <div class="col-12">
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>No books found with the specified criteria.
            </div>
        </div>
    <?php else: ?>
        <?php foreach($books as $book): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-book me-2"></i><?php echo htmlspecialchars($book["title"]); ?>
                        </h5>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>Author: <?php echo htmlspecialchars($book["author"]); ?>
                            </small>
                        </p>
                        <p class="card-text">
                            <?php echo htmlspecialchars(substr($book["description"], 0, 150)) . "..."; ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="../books/view_book.php?id=<?php echo $book["id"]; ?>" class="btn btn-primary btn-sm" data-tooltip="Book details">
                                <i class="fas fa-info-circle me-1"></i>Details
                            </a>
                            <?php if(isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                            <div class="btn-group">
                                <a href="/books/edit_book.php?id=<?php echo $book["id"]; ?>" class="btn btn-warning btn-sm me-2" data-tooltip="Edit book">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../books/delete_book.php?id=<?php echo $book["id"]; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this book?');" data-tooltip="Delete book">
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