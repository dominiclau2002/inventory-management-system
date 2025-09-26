<?php
session_start();
$current_page = 'products';
$page_title = 'Add New Product';

// Check if the user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";

// Helper function to validate a single product
function validate_product($product_data, $index) {
    $errors = [];
    $product_num = $index + 1;

    if(empty(trim($product_data["product_name"] ?? ""))){
        $errors[] = "Product {$product_num}: Please enter the product name.";
    }

    if(empty(trim($product_data["category"] ?? ""))){
        $errors[] = "Product {$product_num}: Please select a category.";
    }

    if(empty(trim($product_data["main_owner"] ?? ""))){
        $errors[] = "Product {$product_num}: Please enter the main owner.";
    }

    if(empty(trim($product_data["prototype_version"] ?? ""))){
        $errors[] = "Product {$product_num}: Please select a prototype version.";
    }

    if(empty(trim($product_data["project_name"] ?? ""))){
        $errors[] = "Product {$product_num}: Please enter the project name.";
    }

    // Description is optional - no validation needed

    $serial_number = trim($product_data["serial_number"] ?? "");
    $alt_serial_number = trim($product_data["alt_serial_number"] ?? "");

    if(empty($serial_number) && empty($alt_serial_number)){
        $errors[] = "Product {$product_num}: Either Serial Number or Alt. Serial Number must be provided.";
    }

    return $errors;
}

// Helper function to check for duplicate serial numbers
function check_duplicate_serials($conn, $products) {
    $errors = [];
    $serial_numbers = [];
    $alt_serial_numbers = [];

    // Check for duplicates within the submission
    foreach($products as $index => $product) {
        $product_num = $index + 1;
        $serial = trim($product["serial_number"] ?? "");
        $alt_serial = trim($product["alt_serial_number"] ?? "");

        // Check for duplicates within current submission
        if(!empty($serial)) {
            if(in_array($serial, $serial_numbers)) {
                $errors[] = "Product {$product_num}: Serial Number '{$serial}' is duplicated in this submission.";
            } else {
                $serial_numbers[] = $serial;
            }
        }

        if(!empty($alt_serial)) {
            if(in_array($alt_serial, $alt_serial_numbers)) {
                $errors[] = "Product {$product_num}: Alt. Serial Number '{$alt_serial}' is duplicated in this submission.";
            } else {
                $alt_serial_numbers[] = $alt_serial;
            }
        }
    }

    // Check for duplicates in database
    if(!empty($serial_numbers)) {
        $placeholders = str_repeat('?,', count($serial_numbers) - 1) . '?';
        $sql = "SELECT serial_number FROM products WHERE serial_number IN ($placeholders)";

        if($stmt = mysqli_prepare($conn, $sql)) {
            $types = str_repeat('s', count($serial_numbers));
            mysqli_stmt_bind_param($stmt, $types, ...$serial_numbers);

            if(mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while($row = mysqli_fetch_array($result)) {
                    $errors[] = "Serial Number '{$row['serial_number']}' already exists in the database.";
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    if(!empty($alt_serial_numbers)) {
        $placeholders = str_repeat('?,', count($alt_serial_numbers) - 1) . '?';
        $sql = "SELECT alt_serial_number FROM products WHERE alt_serial_number IN ($placeholders)";

        if($stmt = mysqli_prepare($conn, $sql)) {
            $types = str_repeat('s', count($alt_serial_numbers));
            mysqli_stmt_bind_param($stmt, $types, ...$alt_serial_numbers);

            if(mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while($row = mysqli_fetch_array($result)) {
                    $errors[] = "Alt. Serial Number '{$row['alt_serial_number']}' already exists in the database.";
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    return $errors;
}

// Helper function to insert a single product
function insert_product($conn, $product_data) {
    $sql = "INSERT INTO products (product_name, category, serial_number, alt_serial_number, main_owner, prototype_version, project_name, description, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')";

    if($stmt = mysqli_prepare($conn, $sql)){
        $serial_number = !empty($product_data["serial_number"]) ? trim($product_data["serial_number"]) : null;
        $alt_serial_number = !empty($product_data["alt_serial_number"]) ? trim($product_data["alt_serial_number"]) : null;
        $description = !empty($product_data["description"]) ? trim($product_data["description"]) : "";
        $remarks = !empty($product_data["remarks"]) ? trim($product_data["remarks"]) : "";

        mysqli_stmt_bind_param($stmt, "sssssssss",
            $product_data["product_name"],
            $product_data["category"],
            $serial_number,
            $alt_serial_number,
            $product_data["main_owner"],
            $product_data["prototype_version"],
            $product_data["project_name"],
            $description,
            $remarks
        );

        $result = mysqli_stmt_execute($stmt);

        // Check for specific database errors
        if(!$result) {
            $error = mysqli_stmt_error($stmt);
            // Log the specific error for debugging
            error_log("Product insertion failed: " . $error);
        }

        mysqli_stmt_close($stmt);
        return $result;
    }
    return false;
}

// Define variables and initialize with empty values
$product_name = $category = $serial_number = $alt_serial_number = $main_owner = $prototype_version = $description = $remarks = "";
$product_name_err = $category_err = $main_owner_err = $prototype_version_err = $description_err = "";
$success_count = 0;
$error_messages = [];

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Check if this is a multi-product submission
    if(isset($_POST['products']) && is_array($_POST['products'])){
        // Handle multiple products

        // First, validate all products and check for duplicates
        $validation_errors = [];
        foreach($_POST['products'] as $index => $product_data){
            $errors = validate_product($product_data, $index);
            $validation_errors = array_merge($validation_errors, $errors);
        }

        // Check for duplicate serial numbers
        $duplicate_errors = check_duplicate_serials($conn, $_POST['products']);
        $validation_errors = array_merge($validation_errors, $duplicate_errors);

        // Only proceed with insertion if no validation errors
        if(empty($validation_errors)){
            foreach($_POST['products'] as $index => $product_data){
                if(insert_product($conn, $product_data)){
                    $success_count++;
                } else {
                    $error_messages[] = "Failed to save product " . ($index + 1) . " - Database error occurred.";
                }
            }
        } else {
            $error_messages = $validation_errors;
        }

        if($success_count > 0 && empty($error_messages)){
            header("location: ../products/products.php?success=" . $success_count);
            exit();
        }
    } else {
        // Handle single product (legacy support)
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

        // Validate project name
    if(empty(trim($_POST["project_name"]))){
        $project_name_err = "Please enter the project name.";
    } else {
        $project_name = trim($_POST["project_name"]);
    }


    // Description is optional
    $description = trim($_POST["description"] ?? "");

    // Get optional fields
    $serial_number = !empty($_POST["serial_number"]) ? trim($_POST["serial_number"]) : "";
    $alt_serial_number = !empty($_POST["alt_serial_number"]) ? trim($_POST["alt_serial_number"]) : "";
    $remarks = !empty($_POST["remarks"]) ? trim($_POST["remarks"]) : "";

    // Validate that at least one serial number is provided
    if(empty($serial_number) && empty($alt_serial_number)){
        $product_name_err = $product_name_err ?: "Either Serial Number or Alt. Serial Number must be provided.";
    }

    // Check input errors before inserting in database
    if(empty($product_name_err) && empty($category_err) && empty($main_owner_err) && empty($prototype_version_err) && empty($description_err)){
        // Prepare an insert statement
        $sql = "INSERT INTO products (product_name, category, serial_number, alt_serial_number, main_owner, prototype_version, project_name, description, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')";

        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssssss", $param_product_name, $param_category, $param_serial_number, $param_alt_serial_number, $param_main_owner, $param_prototype_version, $param_project_name, $param_description, $param_remarks);

            $param_product_name = $product_name;
            $param_category = $category;
            $param_serial_number = $serial_number;
            $param_alt_serial_number = $alt_serial_number;
            $param_main_owner = $main_owner;
            $param_prototype_version = $prototype_version;
            $param_project_name = $project_name;
            $param_description = $description;
            $param_remarks = $remarks;

            if(mysqli_stmt_execute($stmt)){
                header("location: ../products/products.php");
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    } // End of legacy support
}

mysqli_close($conn);

require_once "../includes/header.php";
?>

<?php if(!empty($error_messages)): ?>
    <div class="alert alert-danger" role="alert">
        <h5><i class="fas fa-exclamation-triangle me-2"></i>Errors Found:</h5>
        <ul class="mb-0">
            <?php foreach($error_messages as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if($success_count > 0): ?>
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle me-2"></i>Successfully added <?php echo $success_count; ?> product(s)!
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">
            <i class="fas fa-plus me-2"></i>Add New Products
        </h4>
        <div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addNewProduct()">
                <i class="fas fa-plus me-1"></i>Add Another
            </button>
        </div>
    </div>
    <div class="card-body">
        <form id="multiProductForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div id="productsContainer">
                <!-- First product form will be inserted here by JavaScript -->
            </div>

            <div class="mt-4 border-top pt-3">
                <button type="submit" class="btn btn-success" data-tooltip="Save all products">
                    <i class="fas fa-save me-1"></i>Save All Products
                </button>
                <a href="../products/products.php" class="btn btn-secondary" data-tooltip="Back to products list">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
            </div>
        </form>
    </div>
</div>

<script>
let productCount = 0;

// Product form template
function getProductFormTemplate(index, data = {}) {
    const categories = ['Headset(PCD)', 'Keyboard', 'Mouse', 'Mouse Mat', 'Speaker', 'Smart Home', 'Headset(MCD)', 'Broadcaster', 'Systems', 'Systems Accessories', 'Controller', 'Accessories'];
    const versions = ['DVT', 'DVT2', 'EVT', 'PVT', 'MP/Golden Sample'];

    const categoryOptions = categories.map(cat =>
        `<option value="${cat}" ${data.category === cat ? 'selected' : ''}>${cat}</option>`
    ).join('');

    const versionOptions = versions.map(version =>
        `<option value="${version}" ${data.prototype_version === version ? 'selected' : ''}>${version}</option>`
    ).join('');

    return `
        <div class="product-form border rounded p-3 mb-3" id="product-${index}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Product ${index + 1}</h5>
                <div>
                    <button type="button" class="btn btn-outline-info btn-sm me-2" onclick="cloneProduct(${index})" title="Clone this product">
                        <i class="fas fa-clone"></i>
                    </button>
                    ${index > 0 ? `<button type="button" class="btn btn-outline-danger btn-sm" onclick="removeProduct(${index})" title="Remove this product">
                        <i class="fas fa-trash"></i>
                    </button>` : ''}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-box me-1"></i>Product Name *
                        </label>
                        <input type="text" name="products[${index}][product_name]" class="form-control" value="${data.product_name || ''}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-tag me-1"></i>Category *
                        </label>
                        <select name="products[${index}][category]" class="form-select" required>
                            <option value="">Select Category</option>
                            ${categoryOptions}
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-barcode me-1"></i>Serial Number
                        </label>
                        <input type="text" name="products[${index}][serial_number]" class="form-control" value="${data.serial_number || ''}">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-barcode me-1"></i>Alt. Serial Number
                        </label>
                        <input type="text" name="products[${index}][alt_serial_number]" class="form-control" value="${data.alt_serial_number || ''}">
                        <small class="form-text text-muted">Either Serial Number or Alt. Serial Number is required</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>Main Owner *
                        </label>
                        <input type="text" name="products[${index}][main_owner]" class="form-control" value="${data.main_owner || ''}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-cog me-1"></i>Prototype Version *
                        </label>
                        <select name="products[${index}][prototype_version]" class="form-select" required>
                            <option value="">Select Version</option>
                            ${versionOptions}
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-project-diagram me-1"></i>Project Name *
                        </label>
                        <input type="text" name="products[${index}][project_name]" class="form-control" value="${data.project_name || ''}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-align-left me-1"></i>Description
                        </label>
                        <textarea name="products[${index}][description]" class="form-control" rows="3">${data.description || ''}</textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-sticky-note me-1"></i>Remarks
                        </label>
                        <textarea name="products[${index}][remarks]" class="form-control" rows="2">${data.remarks || ''}</textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Add new empty product
function addNewProduct() {
    const container = document.getElementById('productsContainer');
    container.insertAdjacentHTML('beforeend', getProductFormTemplate(productCount));
    productCount++;
}

// Clone existing product
function cloneProduct(sourceIndex) {
    const sourceForm = document.getElementById(`product-${sourceIndex}`);
    const formData = {};

    // Extract data from source form
    const inputs = sourceForm.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        const name = input.name.replace(/products\[\d+\]\[(.+)\]/, '$1');
        formData[name] = input.value;
    });

    // Clear serial numbers for cloned product
    formData.serial_number = '';
    formData.alt_serial_number = '';

    const container = document.getElementById('productsContainer');
    container.insertAdjacentHTML('beforeend', getProductFormTemplate(productCount, formData));
    productCount++;
}

// Remove product
function removeProduct(index) {
    if (confirm('Are you sure you want to remove this product?')) {
        document.getElementById(`product-${index}`).remove();
    }
}

// Initialize first product on page load
document.addEventListener('DOMContentLoaded', function() {
    addNewProduct();
});
</script>

<?php require_once "../includes/footer.php"; ?>