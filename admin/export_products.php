<?php
session_start();
require_once "../config/config.php";

// Check if the user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: " . url("auth/login.php"));
    exit;
}

require_once "../config/db.php";

// Get export type from URL parameter
$export_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query based on export type
switch($export_type) {
    case 'available':
        $sql = "SELECT p.*, 'Available' as status_text, NULL as borrower_name, NULL as borrow_date, NULL as return_date
                FROM products p
                WHERE p.id NOT IN (SELECT product_id FROM borrows WHERE actual_return_date IS NULL)
                ORDER BY p.product_name";
        $filename = "available_products_" . date('Y-m-d');
        break;

    case 'on_loan':
        $sql = "SELECT p.*, 'On Loan' as status_text, u.name as borrower_name, b.borrow_date, b.return_date
                FROM products p
                INNER JOIN borrows b ON p.id = b.product_id
                INNER JOIN users u ON b.user_id = u.id
                WHERE b.actual_return_date IS NULL
                ORDER BY p.product_name";
        $filename = "products_on_loan_" . date('Y-m-d');
        break;

    case 'overdue':
        $sql = "SELECT p.*, 'Overdue' as status_text, u.name as borrower_name, b.borrow_date, b.return_date,
                DATEDIFF(NOW(), b.return_date) as days_overdue
                FROM products p
                INNER JOIN borrows b ON p.id = b.product_id
                INNER JOIN users u ON b.user_id = u.id
                WHERE b.actual_return_date IS NULL AND b.return_date < NOW()
                ORDER BY days_overdue DESC";
        $filename = "overdue_products_" . date('Y-m-d');
        break;

    default: // 'all'
        $sql = "SELECT p.*,
                CASE
                    WHEN b.id IS NULL THEN 'Available'
                    WHEN b.return_date < NOW() THEN 'Overdue'
                    ELSE 'On Loan'
                END as status_text,
                u.name as borrower_name,
                b.borrow_date,
                b.return_date,
                CASE
                    WHEN b.return_date < NOW() THEN DATEDIFF(NOW(), b.return_date)
                    ELSE NULL
                END as days_overdue
                FROM products p
                LEFT JOIN borrows b ON p.id = b.product_id AND b.actual_return_date IS NULL
                LEFT JOIN users u ON b.user_id = u.id
                ORDER BY p.product_name";
        $filename = "all_products_" . date('Y-m-d');
        break;
}

// Execute query
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for proper Excel UTF-8 handling
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Define CSV headers based on export type
if ($export_type == 'available') {
    $headers = [
        'Product ID',
        'Product Name',
        'Category',
        'Serial Number',
        'Alt Serial Number',
        'Main Owner',
        'Prototype Version',
        'Description',
        'Status',
        'Remarks',
        'Created Date'
    ];
} else {
    $headers = [
        'Product ID',
        'Product Name',
        'Category',
        'Serial Number',
        'Alt Serial Number',
        'Main Owner',
        'Prototype Version',
        'Description',
        'Status',
        'Borrower',
        'Borrow Date',
        'Due Date',
        'Days Overdue',
        'Remarks',
        'Created Date'
    ];
}

// Write headers
fputcsv($output, $headers);

// Write data rows
while($row = mysqli_fetch_assoc($result)) {
    if ($export_type == 'available') {
        $data = [
            $row['id'],
            $row['product_name'],
            $row['category'],
            $row['serial_number'] ?? '',
            $row['alt_serial_number'] ?? '',
            $row['main_owner'],
            $row['prototype_version'] ?? '',
            $row['description'] ?? '',
            $row['status_text'],
            $row['remarks'] ?? '',
            $row['created_at']
        ];
    } else {
        $data = [
            $row['id'],
            $row['product_name'],
            $row['category'],
            $row['serial_number'] ?? '',
            $row['alt_serial_number'] ?? '',
            $row['main_owner'],
            $row['prototype_version'] ?? '',
            $row['description'] ?? '',
            $row['status_text'],
            $row['borrower_name'] ?? '',
            $row['borrow_date'] ? date('Y-m-d', strtotime($row['borrow_date'])) : '',
            $row['return_date'] ? date('Y-m-d', strtotime($row['return_date'])) : '',
            $row['days_overdue'] ?? '',
            $row['remarks'] ?? '',
            $row['created_at']
        ];
    }
    fputcsv($output, $data);
}

// Close output stream
fclose($output);

// Close database connection
mysqli_close($conn);
exit;
?>