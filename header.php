<?php
// header.php - shared header with Bootstrap and nav
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restaurant Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header class="app-header glass container">
    <div class="d-flex align-items-center justify-content-between w-100">
        <h1 class="m-0">Restaurant Management</h1>
        <nav class="app-nav">
            <ul class="d-flex list-unstyled mb-0">
                <li><a class="px-3 py-2" href="dashboard.php">Dashboard</a></li>
                <li><a class="px-3 py-2" href="take_order.php">Take Orders</a></li>
                <li><a class="px-3 py-2" href="view_orders.php">Orders</a></li>
                <li><a class="px-3 py-2" href="manage_menu.php">Menu</a></li>
                <li><a class="px-3 py-2" href="manage_tables.php">Tables</a></li>
                <li><a class="px-3 py-2" href="manage_users.php">Users</a></li>
                <li><a class="px-3 py-2" href="sales_report.php">Reports</a></li>
                <li><a class="px-3 py-2" href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>
<main class="container mt-4">
