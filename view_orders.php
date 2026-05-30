<?php 
session_start();
include 'config.php';

if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
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

$orders = $conn->query($sql);

if (!$orders) {
    die("Query failed: " . $conn->error);
}

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $conn->real_escape_string($_POST['status']);

    // Fetch the table_id for the order
    $order_query = $conn->query("SELECT table_id FROM orders WHERE id = $order_id");
    $order_data = $order_query->fetch_assoc();
    $table_id = $order_data['table_id'];

    // Check if the user has permission to update
    if ($user_role == 'chef' && in_array($new_status, ['preparing', 'ready'])) {
        $conn->query("UPDATE orders SET status = '$new_status' WHERE id = $order_id");

        // If order is marked as "ready", update the table status too
        if ($new_status == 'ready') {
            $conn->query("UPDATE tables SET status = 'occupied' WHERE id = $table_id");
        }
    } elseif ($user_role == 'waiter' && $new_status == 'served') {
        $conn->query("UPDATE orders SET status = '$new_status' WHERE id = $order_id");
    }

    header("Location: view_orders.php");
    exit();
}
?>

<?php include 'header.php'; ?>

<div class="card glass">
    <div class="card-body">
        <h2>View Orders</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="orders-body">
                    <?php while ($row = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>Table <?= $row['table_number'] ?></td>
                            <td><?= htmlspecialchars($row['order_items']) ?></td>
                            <td><?= ucfirst($row['status']) ?></td>
                            <td><?= $row['order_time'] ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <?php if ($user_role === 'chef'): ?>
                                            <option value="preparing" <?= $row['status']==='preparing'?'selected':'' ?>>Preparing</option>
                                            <option value="ready" <?= $row['status']==='ready'?'selected':'' ?>>Ready</option>
                                        <?php elseif ($user_role === 'waiter' && $row['status']==='ready'): ?>
                                            <option value="served">Served</option>
                                        <?php endif; ?>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
