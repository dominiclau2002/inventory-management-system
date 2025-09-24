<?php
session_start();
$current_page = 'products';
$page_title = 'Edit Product';

// Check if the user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";

// Define variables and initialize with empty values
$product_name = $category = $serial_number = $alt_serial_number = $main_owner = $prototype_version = $description = $remarks = "";
$product_name_err = $category_err = $main_owner_err = $prototype_version_err = $description_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate product name
    if(empty(trim($_POST["product_name"]))){
        $product_name_err = "Please enter the product name.";
    } else {
        $product_name = trim($_POST["product_name"]);
    }

    // Validate category
    if(empty(trim($_POST["category"]))){
        $category_err = "Please select a category.";
    } else {
        $category = trim($_POST["category"]);
    }

    // Validate main owner
    if(empty(trim($_POST["main_owner"]))){
        $main_owner_err = "Please enter the main owner.";
    } else {
        $main_owner = trim($_POST["main_owner"]);
    }

    // Validate prototype version
    if(empty(trim($_POST["prototype_version"]))){
        $prototype_version_err = "Please select a prototype version.";
    } else {
        $prototype_version = trim($_POST["prototype_version"]);
    }

    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter the product description.";
    } else {
        $description = trim($_POST["description"]);
    }

    // Get optional fields
    $serial_number = !empty($_POST["serial_number"]) ? trim($_POST["serial_number"]) : null;
    $alt_serial_number = !empty($_POST["alt_serial_number"]) ? trim($_POST["alt_serial_number"]) : null;
    $remarks = !empty($_POST["remarks"]) ? trim($_POST["remarks"]) : null;

    // Check input errors before updating in database
    if(empty($product_name_err) && empty($category_err) && empty($main_owner_err) && empty($prototype_version_err) && empty($description_err)){
        // Prepare an update statement
        $sql = "UPDATE products SET product_name = ?, category = ?, serial_number = ?, alt_serial_number = ?, main_owner = ?, prototype_version = ?, description = ?, remarks = ? WHERE id = ?";

        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssssssi", $param_product_name, $param_category, $param_serial_number, $param_alt_serial_number, $param_main_owner, $param_prototype_version, $param_description, $param_remarks, $param_id);

            $param_product_name = $product_name;
            $param_category = $category;
            $param_serial_number = $serial_number;
            $param_alt_serial_number = $alt_serial_number;
            $param_main_owner = $main_owner;
            $param_prototype_version = $prototype_version;
            $param_description = $description;
            $param_remarks = $remarks;
            $param_id = $_POST["id"];

            if(mysqli_stmt_execute($stmt)){
                header("location: ../products/view_product.php?id=".$_POST["id"]);
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
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
        $sql = "SELECT * FROM products WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $id;

            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);

                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result);

                    $product_name = $row["product_name"];
                    $category = $row["category"];
                    $serial_number = $row["serial_number"];
                    $alt_serial_number = $row["alt_serial_number"];
                    $main_owner = $row["main_owner"];
                    $prototype_version = $row["prototype_version"];
                    $description = $row["description"];
                    $remarks = $row["remarks"];
                } else{
                    header("location: ../products/products.php");
                    exit();
                }
            } else{
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    } else{
        header("location: ../products/products.php");
        exit();
    }
}

mysqli_close($conn);

require_once "../includes/header.php";
?>

<div class="card">
    <div class="card-header">
        <h4 class="mb-0">
            <i class="fas fa-edit me-2"></i>Edit Product
        </h4>
    </div>
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $_GET["id"]; ?>" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-box me-1"></i>Product Name
                        </label>
                        <input type="text" name="product_name" class="form-control <?php echo (!empty($product_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($product_name); ?>">
                        <div class="invalid-feedback"><?php echo $product_name_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-tag me-1"></i>Category
                        </label>
                        <select name="category" class="form-select <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select Category</option>
                            <?php
                            $categories = ['Headset(PCD)', 'Keyboard', 'Mouse', 'Mouse Mat', 'Speaker', 'Smart Home', 'Headset(MCD)', 'Broadcaster', 'Systems', 'Systems Accessories', 'Controller', 'Accessories'];
                            foreach($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo ($category == $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?php echo $category_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-barcode me-1"></i>Serial Number
                        </label>
                        <input type="text" name="serial_number" class="form-control" value="<?php echo htmlspecialchars($serial_number ?? ''); ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-barcode me-1"></i>Alt. Serial Number
                        </label>
                        <input type="text" name="alt_serial_number" class="form-control" value="<?php echo htmlspecialchars($alt_serial_number ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>Main Owner
                        </label>
                        <input type="text" name="main_owner" class="form-control <?php echo (!empty($main_owner_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($main_owner); ?>">
                        <div class="invalid-feedback"><?php echo $main_owner_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-cog me-1"></i>Prototype Version
                        </label>
                        <select name="prototype_version" class="form-select <?php echo (!empty($prototype_version_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select Version</option>
                            <?php
                            $versions = ['DVT', 'DVT2', 'EVT', 'PVT', 'MP/Golden Sample'];
                            foreach($versions as $version): ?>
                                <option value="<?php echo $version; ?>" <?php echo ($prototype_version == $version) ? 'selected' : ''; ?>><?php echo $version; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?php echo $prototype_version_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-align-left me-1"></i>Description
                        </label>
                        <textarea name="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                        <div class="invalid-feedback"><?php echo $description_err; ?></div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-sticky-note me-1"></i>Remarks
                        </label>
                        <textarea name="remarks" class="form-control" rows="2"><?php echo htmlspecialchars($remarks ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            <input type="hidden" name="id" value="<?php echo $_GET["id"]; ?>">
            <div class="mt-3">
                <button type="submit" class="btn btn-primary" data-tooltip="Save changes">
                    <i class="fas fa-save me-1"></i>Save
                </button>
                <a href="../products/view_product.php?id=<?php echo $_GET["id"]; ?>" class="btn btn-secondary" data-tooltip="Back to product details">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>