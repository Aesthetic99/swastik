<?php
// generate_bill.php - Calculate bill based on ordered items
session_start();
include 'config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'cashier') {
    header("Location: login.php");
    exit();
}
$order_id = $_GET['order_id'];
$result = $conn->query("SELECT oi.quantity, m.name, m.price FROM order_items oi JOIN menu m ON oi.menu_id = m.id WHERE oi.order_id = $order_id");
$total = 0;
?>
<!DOCTYPE html>
<html>
<head><title>Generate Bill</title></head>
<body>
<h1>Bill Summary</h1>
<table>
    <tr><th>Item</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr>
    <?php while ($row = $result->fetch_assoc()): 
        $subtotal = $row['price'] * $row['quantity'];
        $total += $subtotal;
    ?>
        <tr><td><?= $row['name'] ?></td><td><?= $row['price'] ?></td><td><?= $row['quantity'] ?></td><td><?= $subtotal ?></td></tr>
    <?php endwhile; ?>
</table>
<p><strong>Total: $<?= $total ?></strong></p>
<a href="payments.php?order_id=<?= $order_id ?>">Proceed to Payment</a>
</body>
</html>
