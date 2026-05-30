<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle AJAX actions
if ($_SERVER["REQUEST_METHOD"] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'waiter';

        if ($username === '' || $password === '') {
            echo json_encode(['success' => false, 'error' => 'Username and password are required']);
            exit();
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hash, $role);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'user' => ['id' => $stmt->insert_id, 'username' => $username, 'role' => $role]]);
            exit();
        }
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit();
    }

    if ($action === 'edit_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $role = $_POST['role'] ?? 'waiter';
        $password = $_POST['password'] ?? '';

        if ($user_id <= 0 || $username === '') {
            echo json_encode(['success' => false, 'error' => 'Username is required']);
            exit();
        }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $hash, $role, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $role, $user_id);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'user' => ['id' => $user_id, 'username' => $username, 'role' => $role]]);
            exit();
        }
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit();
    }

    if ($action === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit();
        }
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit();
    }
}

// Fetch all users
$result = $conn->query("SELECT id, username, role FROM users ORDER BY id DESC");

include 'header.php';
?>

<div class="card glass mb-4">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="mb-1">Manage Users</h2>
            <div class="small text-muted">Create, edit, and remove users without leaving the page.</div>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">Add User</button>
    </div>
</div>

<div class="card glass">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr data-id="<?= $row['id'] ?>" data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>" data-role="<?= htmlspecialchars($row['role'], ENT_QUOTES) ?>">
                        <td><?= $row['id']; ?></td>
                        <td class="user-username"><?= htmlspecialchars($row['username']); ?></td>
                        <td class="user-role"><span class="badge bg-info text-dark"><?= ucfirst($row['role']); ?></span></td>
                        <td>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-user">Edit</button>
                                <button type="button" class="btn btn-sm btn-danger btn-delete-user">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalTitle">Add User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="user-form">
            <input type="hidden" name="user_id" id="user_id">
            <input type="hidden" name="action" id="user_action" value="add_user">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" id="username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password <span class="small text-muted" id="password-help">required for new users</span></label>
                <input type="password" class="form-control" name="password" id="password" placeholder="Leave blank to keep current password">
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-select" name="role" id="role">
                    <option value="admin">Admin</option>
                    <option value="waiter">Waiter</option>
                    <option value="chef">Chef</option>
                    <option value="cashier">Cashier</option>
                </select>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary" id="user-submit-btn">Save User</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
