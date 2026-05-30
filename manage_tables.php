<?php
session_start();
include 'config.php';

// Check if the user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle AJAX actions
if ($_SERVER["REQUEST_METHOD"] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    header('Content-Type: application/json');

    if ($action === 'add_table') {
        $table_number = intval($_POST['table_number']);
        $stmt = $conn->prepare("INSERT INTO tables (table_number, status) VALUES (?, 'available')");
        $stmt->bind_param("i", $table_number);
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            echo json_encode(['success' => true, 'table' => ['id'=>$id, 'table_number'=>$table_number, 'status'=>'available']]);
            exit();
        }
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit();
    }

    if ($action === 'delete_table') {
        $table_id = intval($_POST['table_id']);
        $stmt = $conn->prepare("DELETE FROM tables WHERE id = ?");
        $stmt->bind_param("i", $table_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit();
        }
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit();
    }

    if ($action === 'toggle_status') {
        $table_id = intval($_POST['table_id']);
        $new_status = $_POST['status'];
        $allowed = ['available','occupied','reserved'];
        if (!in_array($new_status, $allowed)) {
            echo json_encode(['success'=>false,'error'=>'Invalid status']);
            exit();
        }
        $stmt = $conn->prepare("UPDATE tables SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $table_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit();
        }
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit();
    }
}

// Fetch all tables
$tables = $conn->query("SELECT * FROM tables ORDER BY table_number ASC");
include 'header.php';
?>

<div class="card glass">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0">Manage Tables</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTableModal">Add Table</button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="tables-table">
                <thead><tr><th>Table Number</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php while ($row = $tables->fetch_assoc()): ?>
                    <tr data-id="<?= $row['id'] ?>">
                        <td><?= $row['table_number']; ?></td>
                        <td>
                            <?php $s = $row['status'];
                                  $cls = $s==='available'? 'badge bg-success' : ($s==='occupied'? 'badge bg-warning text-dark' : 'badge bg-secondary'); ?>
                            <span class="<?= $cls ?> table-status"><?= ucfirst($s) ?></span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm status-select" style="width:140px;">
                                    <option value="available" <?= $row['status']==='available'?'selected':'' ?>>Available</option>
                                    <option value="occupied" <?= $row['status']==='occupied'?'selected':'' ?>>Occupied</option>
                                    <option value="reserved" <?= $row['status']==='reserved'?'selected':'' ?>>Reserved</option>
                                </select>
                                <button class="btn btn-sm btn-danger btn-delete-table">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Table Modal -->
<div class="modal fade" id="addTableModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Table</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="add-table-form">
          <div class="mb-3">
            <label class="form-label">Table Number</label>
            <input type="number" class="form-control" name="table_number" required>
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-primary">Add</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
