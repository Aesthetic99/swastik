<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_role'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'] ?? null;

$sql = "SELECT o.id, o.table_id, t.table_number, o.status, o.order_time, 
               IFNULL(GROUP_CONCAT(m.name, ' (', oi.quantity, ')'), 'No items') AS order_items
        FROM orders o
        JOIN tables t ON o.table_id = t.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu m ON oi.menu_id = m.id";

// Restrict order view based on role
if ($user_role == 'waiter') {
    $sql .= " WHERE o.waiter_id = $user_id";
} elseif ($user_role == 'chef') {
    $sql .= " WHERE o.status IN ('pending', 'preparing', 'ready')";
}

$sql .= " GROUP BY o.id ORDER BY o.order_time DESC";

$result = $conn->query($sql);

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode($orders);
?>
