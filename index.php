<?php
session_start();
include 'config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['user_role'];
include 'header.php';
?>
<div class="card glass">
    <div class="card-body">
        <h2 class="card-title">Welcome, <?php echo ucfirst($user_role); ?>!</h2>
        <p class="small">Use the navigation above to manage restaurant operations.</p>
    </div>
</div>

<?php include 'footer.php'; ?>
