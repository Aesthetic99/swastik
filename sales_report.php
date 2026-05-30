<?php
// sales_report.php - Admin can view sales reports
session_start();
include 'config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$report = $conn->query("SELECT DATE(order_time) as date, SUM(total_amount) as total FROM orders WHERE status = 'paid' GROUP BY DATE(order_time)");
?>
<!DOCTYPE html>
<html>
<head><title>Sales Report</title></head>
<body>
<h1>Sales Report</h1>
<table>
    <tr><th>Date</th><th>Total Sales</th></tr>
    <?php while ($row = $report->fetch_assoc()): ?>
        <tr><td><?= $row['date'] ?></td><td>$<?= $row['total'] ?></td></tr>
    <?php endwhile; ?>
</table>
</body>
</html>
