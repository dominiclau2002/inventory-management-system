<?php
session_start();
require_once "../../config/config.php";
require_once "../../send_email.php";

$current_page = 'my_borrows';
$page_title = 'My Borrowings';

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . url("auth/login.php"));
    exit;
}

require_once "../../config/db.php";
require_once "../../includes/borrowing_table.php";

// Process return product request
if(isset($_GET["return"]) && !empty($_GET["return"])){
    $borrow_id = $_GET["return"];

    // Get borrow details for email notification before updating
    $detail_sql = "SELECT b.borrow_date, b.return_date, u.email, u.name, p.product_name, p.serial_number, p.alt_serial_number
                   FROM borrows b
                   JOIN users u ON b.user_id = u.id
                   JOIN products p ON b.product_id = p.id
                   WHERE b.id = ? AND b.user_id = ?";

    $borrow_details = null;
    if($detail_stmt = mysqli_prepare($conn, $detail_sql)){
        mysqli_stmt_bind_param($detail_stmt, "ii", $borrow_id, $_SESSION["id"]);
        if(mysqli_stmt_execute($detail_stmt)){
            $detail_result = mysqli_stmt_get_result($detail_stmt);
            $borrow_details = mysqli_fetch_array($detail_result);
        }
    }

    $sql = "UPDATE borrows SET actual_return_date = NOW() WHERE id = ? AND user_id = ?";

    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $borrow_id, $_SESSION["id"]);

        if(mysqli_stmt_execute($stmt)){
            // Update product status
            $sql = "UPDATE products SET status = 'available' WHERE id = (SELECT product_id FROM borrows WHERE id = ?)";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $borrow_id);
                mysqli_stmt_execute($stmt);
            }

            // Send return confirmation email
            if($borrow_details && !empty($borrow_details["email"])) {
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
            }

            header("location: ../borrows/my_borrows.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

require_once "../../includes/header.php";

// Get user's active borrows
$borrows = array();
$sql = "SELECT borrows.*, products.product_name, products.category
        FROM borrows
        INNER JOIN products ON borrows.product_id = products.id
        WHERE borrows.user_id = ? AND actual_return_date IS NULL
        ORDER BY borrow_date DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_array($result)){
            $borrows[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get user's borrow history
$history = array();
$sql = "SELECT borrows.*, products.product_name, products.category
        FROM borrows
        INNER JOIN products ON borrows.product_id = products.id
        WHERE borrows.user_id = ? AND actual_return_date IS NOT NULL
        ORDER BY actual_return_date DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_array($result)){
            $history[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
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
                        <i class="fas fa-box me-2"></i>My Current Borrowings
                    </h4>
                </div>
            </div>
            <div class="card-body">
                <?php
                render_borrowing_table(
                    $borrows,
                    false, // Don't show borrower column (user view)
                    "my_borrows.php", // Return URL
                    "You currently have no borrowed products."
                );
                ?> 
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-history me-2"></i>History
                </h4>
            </div>
            <div class="card-body">
                <?php if(empty($history)): ?>
                    <p class="text-muted mb-0">You haven't returned any products yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach($history as $item): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item["product_name"]); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date("Y.m.d", strtotime($item["actual_return_date"])); ?>
                                    </small>
                                </div>
                                <p class="mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item["category"]); ?>
                                    </small>
                                </p>
                                <small class="text-success">
                                    <i class="fas fa-check me-1"></i>Returned
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?> 