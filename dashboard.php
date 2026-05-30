<?php
session_start();
if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['user_role'];
include 'header.php';
?>

<div class="card glass">
    <div class="card-body">
        <h2 class="card-title">Welcome, <?php echo ucfirst($role); ?>!</h2>
        <?php if ($role == 'admin'): ?>
            <h4>Admin Panel</h4>
            <div class="d-flex gap-3 flex-wrap">
                <a class="btn btn-secondary" href="manage_users.php">Manage Users</a>
                <a class="btn btn-secondary" href="manage_menu.php">Manage Menu</a>
                <a class="btn btn-secondary" href="view_orders.php">View Orders</a>
                <a class="btn btn-secondary" href="manage_tables.php">Manage Tables</a>
                <a class="btn btn-secondary" href="sales_report.php">Sales Report</a>
            </div>

        <?php elseif ($role == 'waiter'): ?>
            <h4>Waiter Dashboard</h4>
            <a class="btn btn-primary" href="take_order.php">Take Orders</a>
            <a class="btn btn-outline-primary" href="view_orders.php">View Orders</a>

        <?php elseif ($role == 'chef'): ?>
            <h4>Chef Dashboard</h4>
            <a class="btn btn-primary" href="view_orders.php">View Pending Orders</a>

        <?php elseif ($role == 'cashier'): ?>
            <h4>Cashier Dashboard</h4>
            <a class="btn btn-primary" href="payments.php">Process Payments</a>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
