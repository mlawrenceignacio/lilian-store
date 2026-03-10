<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/admin/orders.php");
}

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = "You are not allowed to perform that action.";
    redirect("/lilian-online-store/auth/login.php");
}

$adminUserId = (int) $_SESSION['user_id'];
$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$newStatus = trim($_POST['status'] ?? '');
$enableReceived = isset($_POST['customer_received_enabled']) ? (int) $_POST['customer_received_enabled'] : 0;

$allowedStatuses = ['to pay', 'to ship', 'to receive', 'delivered', 'cancelled'];

if ($orderId <= 0) {
    $_SESSION['error_message'] = "Invalid order selected.";
    redirect("/lilian-online-store/admin/orders.php");
}

if (!in_array($newStatus, $allowedStatuses, true)) {
    $_SESSION['error_message'] = "Invalid order status selected.";
    redirect("/lilian-online-store/admin/orders.php");
}

$orderStmt = $conn->prepare("
    SELECT id, order_number, full_name, status
    FROM orders
    WHERE id = ?
    LIMIT 1
");
$orderStmt->bind_param("i", $orderId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderResult->num_rows === 0) {
    $_SESSION['error_message'] = "Order not found.";
    redirect("/lilian-online-store/admin/orders.php");
}

$order = $orderResult->fetch_assoc();
$oldStatus = $order['status'];

if ($newStatus !== 'to receive') {
    $enableReceived = 0;
}

$updateStmt = $conn->prepare("
    UPDATE orders
    SET status = ?, customer_received_enabled = ?
    WHERE id = ?
");
$updateStmt->bind_param("sii", $newStatus, $enableReceived, $orderId);
$updateStmt->execute();

logAdminActivity(
    $conn,
    $adminUserId,
    "Admin updated {$order['full_name']}'s order status with order #{$order['order_number']} from {$oldStatus} to {$newStatus}."
);

$_SESSION['success_message'] = "Order updated successfully.";
redirect("/lilian-online-store/admin/orders.php");
?>