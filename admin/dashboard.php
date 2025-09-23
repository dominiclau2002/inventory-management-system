<?php
session_start();
require_once "../config/config.php";

$current_page = 'dashboard';
$page_title = 'Admin Dashboard';

// Check if the user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: " . url("auth/login.php"));
    exit;
}

require_once "../config/db.php";

// Dashboard Metrics Queries

// 1. Total products on loan
$total_on_loan = 0;
$sql = "SELECT COUNT(*) as count FROM borrows WHERE actual_return_date IS NULL";
if($result = mysqli_query($conn, $sql)){
    $row = mysqli_fetch_array($result);
    $total_on_loan = $row['count'];
}

// 2. Total products in inventory (not on loan)
$total_in_inventory = 0;
$sql = "SELECT COUNT(*) as count FROM products WHERE id NOT IN (SELECT product_id FROM borrows WHERE actual_return_date IS NULL)";
if($result = mysqli_query($conn, $sql)){
    $row = mysqli_fetch_array($result);
    $total_in_inventory = $row['count'];
}

// 3. Total products
$total_products = $total_on_loan + $total_in_inventory;

// 4. Products on loan by category
$categories_on_loan = [];
$sql = "SELECT p.category, COUNT(*) as count
        FROM products p
        INNER JOIN borrows b ON p.id = b.product_id
        WHERE b.actual_return_date IS NULL
        GROUP BY p.category
        ORDER BY count DESC";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $categories_on_loan[] = $row;
    }
}

// 5. Products by loanee (current borrowers)
$products_by_loanee = [];
$sql = "SELECT u.name, u.username, COUNT(*) as count
        FROM users u
        INNER JOIN borrows b ON u.id = b.user_id
        WHERE b.actual_return_date IS NULL
        GROUP BY u.id, u.name, u.username
        ORDER BY count DESC";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $products_by_loanee[] = $row;
    }
}

// 6. Products by main owner
$products_by_owner = [];
$sql = "SELECT main_owner, COUNT(*) as total_count,
        SUM(CASE WHEN id IN (SELECT product_id FROM borrows WHERE actual_return_date IS NULL) THEN 1 ELSE 0 END) as on_loan_count
        FROM products
        GROUP BY main_owner
        ORDER BY total_count DESC";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $row['available_count'] = $row['total_count'] - $row['on_loan_count'];
        $products_by_owner[] = $row;
    }
}

// 7. Recent loan activity
$recent_loans = [];
$sql = "SELECT p.product_name, u.name as user_name, b.borrow_date, b.return_date
        FROM borrows b
        INNER JOIN products p ON b.product_id = p.id
        INNER JOIN users u ON b.user_id = u.id
        WHERE b.actual_return_date IS NULL
        ORDER BY b.borrow_date DESC
        LIMIT 10";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $recent_loans[] = $row;
    }
}

// 8. Overdue loans
$overdue_loans = [];
$sql = "SELECT p.product_name, u.name as user_name, b.return_date, DATEDIFF(NOW(), b.return_date) as days_overdue
        FROM borrows b
        INNER JOIN products p ON b.product_id = p.id
        INNER JOIN users u ON b.user_id = u.id
        WHERE b.actual_return_date IS NULL AND b.return_date < NOW()
        ORDER BY days_overdue DESC";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_array($result)){
        $overdue_loans[] = $row;
    }
}

mysqli_close($conn);

require_once "../includes/header.php";
?>



<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-chart-line me-2"></i>Inventory Dashboard</h2>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download me-1"></i>Export Data
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                        <li><a class="dropdown-item" href="<?php echo url('admin/export_products.php?type=all'); ?>">
                            <i class="fas fa-file-excel me-2"></i>All Products
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo url('admin/export_products.php?type=available'); ?>">
                            <i class="fas fa-warehouse me-2"></i>Available Products
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo url('admin/export_products.php?type=on_loan'); ?>">
                            <i class="fas fa-handshake me-2"></i>Products on Loan
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo url('admin/export_products.php?type=overdue'); ?>">
                            <i class="fas fa-exclamation-triangle me-2"></i>Overdue Products
                        </a></li>
                    </ul>
                </div>
                <div class="text-muted">
                    <i class="fas fa-clock me-1"></i>Last updated: <?php echo date('Y-m-d H:i:s'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Key Metrics Row -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card metric-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-boxes fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo $total_products; ?></h3>
                <p class="mb-0">Total Products</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card metric-card success h-100">
            <div class="card-body text-center">
                <i class="fas fa-warehouse fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo $total_in_inventory; ?></h3>
                <p class="mb-0">Available in Inventory</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card metric-card info h-100">
            <div class="card-body text-center">
                <i class="fas fa-handshake fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo $total_on_loan; ?></h3>
                <p class="mb-0">Currently on Loan</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card dashboard-card metric-card warning h-100">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <h3 class="mb-1"><?php echo count($overdue_loans); ?></h3>
                <p class="mb-0">Overdue Loans</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Products on Loan by Category -->
    <div class="col-lg-6 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Products on Loan by Category</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Products by Loanee -->
    <div class="col-lg-6 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Active Borrowers</h5>
            </div>
            <div class="card-body">
                <?php if(empty($products_by_loanee)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                        <p>No active loans</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Borrower</th>
                                    <th>Username</th>
                                    <th class="text-center">Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products_by_loanee as $loanee): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($loanee['name']); ?></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($loanee['username']); ?></small></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?php echo $loanee['count']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Products by Main Owner -->
    <div class="col-lg-8 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Products by Main Owner</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Main Owner</th>
                                <th class="text-center">Total Products</th>
                                <th class="text-center">On Loan</th>
                                <th class="text-center">Available</th>
                                <th class="text-center">% on Loan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products_by_owner as $owner): ?>
                                <?php $utilization = $owner['total_count'] > 0 ? round(($owner['on_loan_count'] / $owner['total_count']) * 100) : 0; ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($owner['main_owner']); ?></strong></td>
                                    <td class="text-center"><?php echo $owner['total_count']; ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?php echo $owner['on_loan_count']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?php echo $owner['available_count']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?php echo $utilization > 75 ? 'bg-danger' : ($utilization > 50 ? 'bg-warning' : 'bg-success'); ?>"
                                                 style="width: <?php echo $utilization; ?>%">
                                                <?php echo $utilization; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Loan Activity -->
    <div class="col-lg-4 mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Loans</h5>
            </div>
            <div class="card-body">
                <?php if(empty($recent_loans)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                        <p>No recent activity</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach(array_slice($recent_loans, 0, 5) as $loan): ?>
                            <div class="timeline-item mb-3">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($loan['product_name']); ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($loan['user_name']); ?><br>
                                        <i class="fas fa-calendar me-1"></i><?php echo date('M j, Y', strtotime($loan['borrow_date'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if(count($recent_loans) > 5): ?>
                        <div class="text-center">
                            <a href="<?php echo url('books/borrows/borrow.php'); ?>" class="btn btn-sm btn-outline-primary">
                                View All Loans
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Overdue Loans Alert -->
<?php if(!empty($overdue_loans)): ?>
<div class="row">
    <div class="col-12 mb-4">
        <div class="card dashboard-card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Overdue Loans Alert</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Borrower</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($overdue_loans as $overdue): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($overdue['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($overdue['user_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($overdue['return_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $overdue['days_overdue']; ?> days</span>
                                    </td>
                                    <td>
                                        <a href="<?php echo url('books/borrows/borrow.php'); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.dashboard-card {
    transition: transform 0.2s ease-in-out;
}
.dashboard-card:hover {
    /* transform: translateY(-2px); */
    box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
}
.metric-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.metric-card.success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}
.metric-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}
.metric-card.info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}
.chart-container {
    position: relative;
    height: 300px;
}
</style>

<style>
.timeline {
    position: relative;
    padding-left: 20px;
}
.timeline-item {
    position: relative;
}
.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 15px;
    width: 1px;
    height: calc(100% + 15px);
    background-color: #dee2e6;
}

.dropdown-item{
    z-index: 1000;
}

.dropdown-menu {
    z-index: 1000;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Category Chart
const categoryData = <?php echo json_encode($categories_on_loan); ?>;
const categoryLabels = categoryData.map(item => item.category);
const categoryValues = categoryData.map(item => parseInt(item.count));

const categoryColors = [
    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
];

if (categoryData.length > 0) {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categoryLabels,
            datasets: [{
                label: 'Products on Loan',
                data: categoryValues,
                backgroundColor: categoryColors.slice(0, categoryData.length),
                borderWidth: 1,
                borderColor: categoryColors.slice(0, categoryData.length).map(color => color + '80')
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
} else {
    document.getElementById('categoryChart').parentElement.innerHTML =
        '<div class="text-center text-muted py-4"><i class="fas fa-info-circle fa-2x mb-2"></i><p>No products currently on loan</p></div>';
}

// Auto-refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<?php require_once "../includes/footer.php"; ?>