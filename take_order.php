<?php
session_start();
include 'config.php';

// Check if the logged-in user is a waiter
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'waiter') {
    header("Location: login.php");
    exit();
}

// Fetch available tables
$tables = $conn->query("SELECT id, table_number FROM tables WHERE status = 'available'");

// Fetch menu items from the database
$menu_items = $conn->query("SELECT id, name, price, category FROM menu ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $table_id = intval($_POST['table_id']);
    $waiter_id = $_SESSION['user_id']; // Assuming waiter ID is stored in session
    $order_details = $_POST['order'];

    if (!empty($order_details)) {
        // Insert into orders table
        $stmt = $conn->prepare("INSERT INTO orders (table_id, waiter_id, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("ii", $table_id, $waiter_id);
        $stmt->execute();
        $order_id = $stmt->insert_id; // Get the last inserted order ID

        // Insert items into order_items table
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
        foreach ($order_details as $menu_id => $quantity) {
            $menu_id = intval($menu_id);
            $quantity = intval($quantity);
            if ($quantity > 0) {
                // Fetch item price
                $priceStmt = $conn->prepare("SELECT price FROM menu WHERE id = ?");
                $priceStmt->bind_param("i", $menu_id);
                $priceStmt->execute();
                $priceRes = $priceStmt->get_result()->fetch_assoc();
                $subtotal = ($priceRes['price'] ?? 0) * $quantity;

                $stmt->bind_param("iiid", $order_id, $menu_id, $quantity, $subtotal);
                $stmt->execute();
            }
        }

        // Mark table as occupied
        $upd = $conn->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?");
        $upd->bind_param("i", $table_id);
        $upd->execute();

        header("Location: take_order.php?success=1");
        exit();
    }
}

include 'header.php';
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Order placed successfully!</div>
<?php endif; ?>

<div class="card glass">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="mb-1">Take Order</h2>
                <div class="small">Search items quickly, build the order, then review before confirming.</div>
            </div>
            <div class="badge bg-info text-dark px-3 py-2" id="selected-count-badge">0 items selected</div>
        </div>

        <form method="POST" action="" id="order-form">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Table Number</label>
                    <select name="table_id" class="form-select" required>
                        <?php while ($row = $tables->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>">Table <?php echo $row['table_number']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Search Menu</label>
                    <input type="search" class="form-control" id="menu-search" placeholder="Search by item or category">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <h5 class="mb-3">Select Items</h5>
                    <div class="table-responsive mb-3">
                        <table class="table align-middle" id="menu-table">
                            <thead><tr><th>Item</th><th>Category</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr></thead>
                            <tbody>
                            <?php while ($row = $menu_items->fetch_assoc()): ?>
                                <tr data-menu-id="<?= $row['id'] ?>" data-price="<?= $row['price'] ?>" data-search="<?= strtolower(trim($row['name'] . ' ' . ($row['category'] ?? ''))) ?>">
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category'] ?? '-'); ?></td>
                                    <td>$<?php echo number_format($row['price'], 2); ?></td>
                                    <td style="max-width:120px;"><input type="number" class="form-control qty-input" name="order[<?php echo $row['id']; ?>]" min="0" value="0" data-price="<?= $row['price'] ?>" aria-label="Quantity for <?php echo htmlspecialchars($row['name']); ?>"></td>
                                    <td class="item-subtotal">$0.00</td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card glass h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Order Review</h5>
                            <div class="small text-muted mb-2">Selected items</div>
                            <div id="order-summary" class="mb-3 small">No items selected yet.</div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="small">Total</div>
                                <div class="h4 mb-0" id="order-total">$0.00</div>
                            </div>
                            <button class="btn btn-outline-primary w-100 mb-2" type="button" id="review-order-btn" disabled>Review Order</button>
                            <button class="btn btn-primary w-100" id="place-order-btn" type="submit" disabled>Confirm & Place Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Review Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="review-order-list" class="mb-3"></div>
        <div class="d-flex justify-content-between align-items-center border-top pt-3">
          <div class="small text-muted">Total</div>
          <div class="h4 mb-0" id="review-order-total">$0.00</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back to edit</button>
        <button type="button" class="btn btn-primary" id="confirm-place-order-btn">Confirm Order</button>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
