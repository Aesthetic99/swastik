<?php
session_start();
include 'config.php';

// Check if the logged-in user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle AJAX actions
if ($_SERVER["REQUEST_METHOD"] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'add_item' || $action === 'edit_item') {
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');

        if ($name === '' || $price <= 0) {
            echo json_encode(['success' => false, 'error' => 'Name and price are required']);
            exit();
        }

        if ($action === 'add_item') {
            $stmt = $conn->prepare("INSERT INTO menu (name, category, price) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $name, $category, $price);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'item' => ['id' => $stmt->insert_id, 'name' => $name, 'category' => $category, 'price' => $price]]);
                exit();
            }
        } else {
            $item_id = intval($_POST['item_id'] ?? 0);
            $stmt = $conn->prepare("UPDATE menu SET name = ?, category = ?, price = ? WHERE id = ?");
            $stmt->bind_param("ssdi", $name, $category, $price, $item_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'item' => ['id' => $item_id, 'name' => $name, 'category' => $category, 'price' => $price]]);
                exit();
            }
        }

        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit();
    }

    if ($action === 'delete_item') {
        $id = intval($_POST['item_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit();
        }
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit();
    }
}

// Fetch all menu items
$menu_items = $conn->query("SELECT * FROM menu ORDER BY category ASC, name ASC");

include 'header.php';
?>

<div class="card glass mb-4">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="mb-1">Manage Menu</h2>
            <div class="small text-muted">Search, add, and edit menu items quickly.</div>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#menuModal">Add Item</button>
    </div>
</div>

<div class="card glass">
    <div class="card-body">
        <div class="mb-3">
            <input type="search" class="form-control" id="menu-filter" placeholder="Search menu items by name or category">
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="menu-items-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $menu_items->fetch_assoc()): ?>
                        <tr data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>" data-category="<?= htmlspecialchars($row['category'] ?? '', ENT_QUOTES) ?>" data-price="<?= $row['price'] ?>">
                            <td class="item-name"><?= htmlspecialchars($row['name']); ?></td>
                            <td class="item-category"><?= htmlspecialchars($row['category'] ?? '-'); ?></td>
                            <td class="item-price">$<?= number_format($row['price'], 2); ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-item">Edit</button>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete-item">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Menu Modal -->
<div class="modal fade" id="menuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="menuModalTitle">Add Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="menu-form">
            <input type="hidden" name="item_id" id="item_id">
            <input type="hidden" name="action" id="menu_action" value="add_item">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" id="menu_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <input type="text" class="form-control" name="category" id="menu_category" placeholder="Starter, Main, Drinks">
            </div>
            <div class="mb-3">
                <label class="form-label">Price</label>
                <input type="number" class="form-control" name="price" id="menu_price" step="0.01" min="0" required>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary" id="menu-submit-btn">Save Item</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
