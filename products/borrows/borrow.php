<?php
session_start();
require_once "../../config/config.php";
require_once "../../send_email.php";

$current_page = 'borrows';
$page_title = 'Borrowings';

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . url("auth/login.php"));
    exit;
}

// For non-admin users, only allow direct product borrowing or form submissions
if($_SESSION["role"] !== "admin" && !isset($_GET["product_id"]) && $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("location: " . url("index.php"));
    exit;
}

require_once "../../config/db.php";

// Process return product
if(isset($_GET["return"]) && !empty($_GET["return"])){
    $borrow_id = $_GET["return"];

    // Get borrow details for email notification before updating
    $detail_sql = "SELECT b.borrow_date, b.return_date, u.email, u.name, p.product_name, p.serial_number, p.alt_serial_number
                   FROM borrows b
                   JOIN users u ON b.user_id = u.id
                   JOIN products p ON b.product_id = p.id
                   WHERE b.id = ?";

    $borrow_details = null;
    if($detail_stmt = mysqli_prepare($conn, $detail_sql)){
        mysqli_stmt_bind_param($detail_stmt, "i", $borrow_id);
        if(mysqli_stmt_execute($detail_stmt)){
            $detail_result = mysqli_stmt_get_result($detail_stmt);
            $borrow_details = mysqli_fetch_array($detail_result);
        }
    }

    $sql = "UPDATE borrows SET actual_return_date = NOW() WHERE id = ?";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $borrow_id;

        if(mysqli_stmt_execute($stmt)){
            // Update product status to available
            $sql = "UPDATE products SET status = 'available' WHERE id = (SELECT product_id FROM borrows WHERE id = ?)";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $borrow_id);
                mysqli_stmt_execute($stmt);
            }

            // Send return confirmation email
            error_log("Borrow details: " . print_r($borrow_details, true)); // Debug log
            if($borrow_details && !empty($borrow_details["email"])) {
                error_log("Attempting to send return email to: " . $borrow_details["email"]); // Debug log
                $product_details = $borrow_details["product_name"];
                if (!empty($borrow_details["serial_number"])) {
                    $product_details .= " (SN: " . $borrow_details["serial_number"] . ")";
                } elseif (!empty($borrow_details["alt_serial_number"])) {
                    $product_details .= " (Alt SN: " . $borrow_details["alt_serial_number"] . ")";
                }

                $borrow_duration = floor((strtotime('now') - strtotime($borrow_details["borrow_date"])) / (60 * 60 * 24));
                $is_late = strtotime('now') > strtotime($borrow_details["return_date"]);

                // Load environment variables for company information
                loadEnvVariables();
                $company_name = $_ENV['COMPANY_NAME'] ?? 'Company';
                $team_name = $_ENV['TEAM_NAME'] ?? 'IT Team';
                $system_name = $_ENV['SYSTEM_NAME'] ?? 'Inventory Management System';

                $subject = "Product Returned - " . $system_name;
                $html_body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background-color: #00ff41; padding: 20px; text-align: center;'>
                        <h2 style='color: #000; margin: 0;'>Product Returned Successfully</h2>
                    </div>
                    <div style='padding: 20px; background-color: #f9f9f9;'>
                        <p>Hi " . htmlspecialchars($borrow_details["name"]) . ",</p>
                        <p>Thank you for returning the following item:</p>
                        <div style='background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                            <strong>Product:</strong> " . htmlspecialchars($product_details) . "<br>
                            <strong>Borrowed Date:</strong> " . htmlspecialchars($borrow_details["borrow_date"]) . "<br>
                            <strong>Due Date:</strong> " . htmlspecialchars($borrow_details["return_date"]) . "<br>
                            <strong>Returned Date:</strong> " . date('Y-m-d H:i:s') . "<br>
                            <strong>Duration:</strong> " . $borrow_duration . " day(s)" .
                            ($is_late ? " <span style='color: #dc3545;'>(Returned Late)</span>" : " <span style='color: #28a745;'>(On Time)</span>") . "
                        </div>";

                if($is_late) {
                    $html_body .= "<div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 15px 0;'>
                        <p style='color: #856404; margin: 0;'><strong>Note:</strong> This item was returned after the due date. Please try to return items on time in the future.</p>
                    </div>";
                } else {
                    $html_body .= "<p style='color: #28a745;'><strong>Thank you for returning the item on time!</strong></p>";
                }

                $html_body .= "
                        <p>The item has been successfully returned and is now available for other users.</p>
                        <hr>
                        <p style='color: #666; font-size: 12px;'>
                            Best regards,<br>
                            " . htmlspecialchars($system_name) . "<br>
                            This is an automated message, please do not reply.
                        </p>
                    </div>
                </div>";

                $alt_body = "Hi " . $borrow_details["name"] . ",\n\nThank you for returning: " . $product_details .
                          "\nBorrowed: " . $borrow_details["borrow_date"] .
                          "\nDue: " . $borrow_details["return_date"] .
                          "\nReturned: " . date('Y-m-d H:i:s') .
                          "\nDuration: " . $borrow_duration . " day(s)" .
                          ($is_late ? " (Returned Late)" : " (On Time)") .
                          "\n\nThe item has been successfully returned and is now available for other users.\n\nBest regards,\n" . $system_name;

                $email_result = sendEmail(
                    $borrow_details["email"],
                    $borrow_details["name"],
                    $subject,
                    $html_body,
                    $alt_body
                );

                if($email_result['success']) {
                    error_log("Return confirmation email sent successfully to: " . $borrow_details["email"]);
                } else {
                    error_log("Failed to send return confirmation email to: " . $borrow_details["email"] . " - " . $email_result['message']);
                }
            } else {
                error_log("Return email not sent. Reason: " .
                    (!$borrow_details ? "No borrow details found" :
                    (empty($borrow_details["email"]) ? "User has no email address" : "Unknown reason")));
            }

            header("location: " . url("products/borrows/borrow.php"));
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Process new borrow
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $product_id = $_POST["product_id"];
    $return_date = $_POST["return_date"];

    // For regular users, use their own user_id; for admins, use selected user_id
    if($_SESSION["role"] == "admin") {
        $user_id = $_POST["user_id"];
        $redirect_url = url("products/borrows/borrow.php");
    } else {
        $user_id = $_SESSION["id"];
        $redirect_url = url("products/view_product.php?id=" . $product_id);
    }

    $sql = "INSERT INTO borrows (product_id, user_id, borrow_date, return_date) VALUES (?, ?, NOW(), ?)";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "iis", $product_id, $user_id, $return_date);

        if(mysqli_stmt_execute($stmt)){
            // Update product status to borrowed
            $sql = "UPDATE products SET status = 'borrowed' WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $product_id);
                mysqli_stmt_execute($stmt);
            }

            // Get user email for notification
            $email_sql = "SELECT email, name FROM users WHERE id = ?";
            if($email_stmt = mysqli_prepare($conn, $email_sql)) {
                mysqli_stmt_bind_param($email_stmt, "i", $user_id);
                if(mysqli_stmt_execute($email_stmt)) {
                    $email_result = mysqli_stmt_get_result($email_stmt);
                    if($user_data = mysqli_fetch_array($email_result)) {
                        // Get product name
                        $product_sql = "SELECT product_name, serial_number, alt_serial_number FROM products WHERE id = ?";
                        if($prod_stmt = mysqli_prepare($conn, $product_sql)) {
                            mysqli_stmt_bind_param($prod_stmt, "i", $product_id);
                            if(mysqli_stmt_execute($prod_stmt)) {
                                $prod_result = mysqli_stmt_get_result($prod_stmt);
                                if($product_data = mysqli_fetch_array($prod_result)) {
                                    // Only send email if user has an email address
                                    if(!empty($user_data["email"])) {
                                        $product_details = $product_data["product_name"];
                                        if (!empty($product_data["serial_number"])) {
                                            $product_details .= " (SN: " . $product_data["serial_number"] . ")";
                                        } elseif (!empty($product_data["alt_serial_number"])) {
                                            $product_details .= " (Alt SN: " . $product_data["alt_serial_number"] . ")";
                                        }

                                        // Load environment variables for company information
                                        loadEnvVariables();
                                        $company_name = $_ENV['COMPANY_NAME'] ?? 'Company';
                                        $team_name = $_ENV['TEAM_NAME'] ?? 'IT Team';
                                        $system_name = $_ENV['SYSTEM_NAME'] ?? 'Inventory Management System';

                                        $subject = "Product Borrowed - " . $system_name;
                                        $html_body = "
                                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                            <div style='background-color: #00ff41; padding: 20px; text-align: center;'>
                                                <h2 style='color: #000; margin: 0;'>Product Borrowed Successfully</h2>
                                            </div>
                                            <div style='padding: 20px; background-color: #f9f9f9;'>
                                                <p>Hi " . htmlspecialchars($user_data["name"]) . ",</p>
                                                <p>You have successfully borrowed the following item:</p>
                                                <div style='background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                                                    <strong>Product:</strong> " . htmlspecialchars($product_details) . "<br>
                                                    <strong>Borrowed Date:</strong> " . date('Y-m-d H:i:s') . "<br>
                                                    <strong>Return Due Date:</strong> " . htmlspecialchars($return_date) . "
                                                </div>
                                                <p><strong>Please remember to return the item by the due date.</strong></p>
                                                <p>If you have any questions, please contact the " . htmlspecialchars($team_name) . ".</p>
                                                <hr>
                                                <p style='color: #666; font-size: 12px;'>
                                                    Best regards,<br>
                                                    " . htmlspecialchars($system_name) . "<br>
                                                    This is an automated message, please do not reply.
                                                </p>
                                            </div>
                                        </div>";

                                        $alt_body = "Hi " . $user_data["name"] . ",\n\nYou have borrowed: " . $product_details . "\nBorrowed: " . date('Y-m-d H:i:s') . "\nReturn due: " . $return_date . "\n\nPlease remember to return the item by the due date.\n\nBest regards,\n" . $system_name;

                                        $email_result = sendEmail(
                                            $user_data["email"],
                                            $user_data["name"],
                                            $subject,
                                            $html_body,
                                            $alt_body
                                        );

                                        if($email_result['success']) {
                                            error_log("Email sent successfully to: " . $user_data["email"]);
                                        } else {
                                            error_log("Failed to send email to: " . $user_data["email"] . " - " . $email_result['message']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            header("location: " . $redirect_url);
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

// Get all active borrows
$borrows = array();
$sql = "SELECT borrows.*, products.product_name, users.name as user_name
        FROM borrows
        INNER JOIN products ON borrows.product_id = products.id
        INNER JOIN users ON borrows.user_id = users.id
        WHERE actual_return_date IS NULL
        ORDER BY borrow_date DESC";

if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $borrows[] = $row;
    }
}

// Get available products for new borrow
$available_products = array();
$sql = "SELECT id, product_name, category, serial_number, alt_serial_number FROM products WHERE id NOT IN (SELECT product_id FROM borrows WHERE actual_return_date IS NULL)";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $available_products[] = $row;
    }
}

// Get all users for new borrow
$users = array();
$sql = "SELECT id, name, username FROM users WHERE role = 'user'";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $users[] = $row;
    }
}

// Handle direct product borrowing
if(isset($_GET["product_id"]) && !empty($_GET["product_id"])) {
    $product_id = $_GET["product_id"];
    $user_id = $_SESSION["id"];
    $return_date = date('Y-m-d', strtotime('+30 days'));
    
    // Check if product is available
    $sql = "SELECT status FROM products WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_array($result)) {
                if($row["status"] == "available") {
                    // Insert borrow record
                    $sql = "INSERT INTO borrows (product_id, user_id, borrow_date, return_date) VALUES (?, ?, NOW(), ?)";
                    if($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "iis", $product_id, $user_id, $return_date);
                        if(mysqli_stmt_execute($stmt)) {
                            // Update product status (main_owner stays unchanged)
                            $sql = "UPDATE products SET status = 'borrowed' WHERE id = ?";
                            if($stmt = mysqli_prepare($conn, $sql)) {
                                mysqli_stmt_bind_param($stmt, "i", $product_id);
                                mysqli_stmt_execute($stmt);
                            }
                            header("location: " . url("products/view_product.php?id=" . $product_id));
                            exit();
                        }
                    }
                }
            }
        }
    }
    header("location: " . url("products/view_product.php?id=" . $product_id . "&error=1"));
    exit();
}

mysqli_close($conn);

// Set flag for header path resolution
$is_root_level = false;
require_once "../../includes/borrowing_table.php";
require_once "../../includes/header.php";
?>
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <a href="../../products/products.php" class="btn btn-success btn-sm me-3" data-tooltip="Back to products">
                                <i class="fas fa-arrow-left me-1"></i>Back
                            </a>
                            <h4 class="mb-0">
                                <i class="fas fa-clipboard-list me-2"></i>Products on Loan
                            </h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        render_borrowing_table(
                            $borrows,
                            true, // Show borrower column (admin view)
                            "borrow.php", // Return URL
                            "No active borrowings at the moment."
                        );
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <?php if($_SESSION["role"] == "admin"): ?>
                    <!-- Admin Form -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i class="fas fa-plus me-2"></i>New Product Loan
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if(empty($available_products)): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>No products available for loan at the moment.
                                </div>
                            <?php elseif(empty($users)): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>No registered users.
                                </div>
                            <?php else: ?>
                                <form method="POST" action="<?php echo url('products/borrows/borrow.php'); ?>">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-box me-1"></i>Product
                                        </label>
                                        <select name="product_id" class="form-select" required data-tooltip="Select the product you want to borrow">
                                            <option value="">Select a product...</option>
                                            <?php foreach($available_products as $product): ?>
                                                <option value="<?php echo $product["id"]; ?>">
                                                    <?php
                                                    echo htmlspecialchars($product["product_name"]);

                                                    // Display serial number if available
                                                    if (!empty($product["serial_number"])) {
                                                        echo " - SN: " . htmlspecialchars($product["serial_number"]);
                                                    } elseif (!empty($product["alt_serial_number"])) {
                                                        echo " - Alt SN: " . htmlspecialchars($product["alt_serial_number"]);
                                                    }

                                                    // Add category for additional context
                                                    echo " (" . htmlspecialchars($product["category"]) . ")";
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-user me-1"></i>Loanee
                                        </label>
                                        <select name="user_id" class="form-select" required data-tooltip="Select the borrowing user">
                                            <option value="">Select a user...</option>
                                            <?php foreach($users as $user): ?>
                                                <option value="<?php echo $user["id"]; ?>">
                                                    <?php echo htmlspecialchars($user["name"]) . " (" . htmlspecialchars($user["username"]) . ")"; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-calendar me-1"></i>Return Due Date
                                        </label>
                                        <input type="date" name="return_date" class="form-control" required
                                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                               value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                                               data-tooltip="Enter the return due date">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100" data-tooltip="Record borrowing">
                                        <i class="fas fa-plus me-1"></i>Borrow
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

<style>
    /* Make date input calendar icon visible on dark background */
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
        opacity: 0.8;
    }

    input[type="date"]::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
        filter: invert(1) sepia(1) hue-rotate(100deg) saturate(2);
    }
</style>

<?php require_once "../../includes/footer.php"; ?> 