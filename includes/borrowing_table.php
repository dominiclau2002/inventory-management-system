<?php
/**
 * Shared component for displaying borrowing tables
 * Used by both borrow.php (admin view) and my_borrows.php (user view)
 */
function render_borrowing_table($borrows, $show_borrower = false, $return_url_base = "", $empty_message = "No borrowings found.") {
?>
    <?php if(empty($borrows)): ?>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($empty_message); ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?php echo $show_borrower ? 'Product' : 'Product Name'; ?></th>
                        <?php if($show_borrower): ?>
                            <th>Loanee</th>
                        <?php else: ?>
                            <th>Category</th>
                        <?php endif; ?>
                        <th>Loan Date</th>
                        <th>Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($borrows as $borrow): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($borrow["product_name"]); ?></td>
                            <?php if($show_borrower): ?>
                                <td><?php echo htmlspecialchars($borrow["user_name"]); ?></td>
                            <?php else: ?>
                                <td><?php echo htmlspecialchars($borrow["category"]); ?></td>
                            <?php endif; ?>
                            <td><?php echo date("Y.m.d", strtotime($borrow["borrow_date"])); ?></td>
                            <td>
                                <?php
                                $return_date = strtotime($borrow["return_date"]);
                                $now = time();
                                $days_left = round(($return_date - $now) / (60 * 60 * 24));

                                if($days_left < 0) {
                                    echo '<span class="text-danger">';
                                    echo date("Y.m.d", $return_date);
                                    echo ' ('. abs($days_left) .' days overdue)';
                                    echo '</span>';
                                } else {
                                    echo date("Y.m.d", $return_date);
                                    echo ' ('. $days_left .' days left)';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo $return_url_base; ?>?return=<?php echo $borrow["id"]; ?>"
                                   class="btn btn-success btn-sm"
                                   onclick="return confirm('Are you sure you want to return this product?');"
                                   data-tooltip="Return product">
                                    <i class="fas fa-check me-1"></i>Return
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php
}
?>