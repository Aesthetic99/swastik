<?php
// payments.php - Cashier records payments
session_start();
include 'config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'cashier') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $payment_method = $_POST['payment_method'];

    // Calculate total amount from order_items
    $stmt = $conn->prepare("SELECT IFNULL(SUM(subtotal),0) as total FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $amount = $res['total'] ?? 0.00;

    // Insert payment record
    $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, payment_method) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $order_id, $amount, $payment_method);
    $stmt->execute();

    // Update order status and total_amount
    $stmt = $conn->prepare("UPDATE orders SET status = 'paid', total_amount = ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $order_id);
    $stmt->execute();

    echo "Payment successful!";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head><title>Process Payment</title></head>
<body>
<h1>Process Payment</h1>
<form method="POST">
    <input type="hidden" name="order_id" value="<?= $_GET['order_id'] ?>">
    <label>Payment Method:</label>
    <select name="payment_method">
        <option value="cash">Cash</option>
        <option value="card">Card</option>
    </select>
    <button type="submit">Confirm Payment</button>
</form>
</body>
</html>

