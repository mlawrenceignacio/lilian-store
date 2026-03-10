<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/orders.php");
}

if (!isLoggedIn() || isAdmin()) {
    $_SESSION['error_message'] = "Please login as a customer first.";
    redirect("/lilian-online-store/auth/login.php");
}

$userId = (int) $_SESSION['user_id'];
$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;

if ($orderId <= 0) {
    $_SESSION['error_message'] = "Invalid order selected.";
    redirect("/lilian-online-store/orders.php");
}

$stmt = $conn->prepare("
    SELECT id, order_number, status, customer_received_enabled, full_name
    FROM orders
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Order not found.";
    redirect("/lilian-online-store/orders.php");
}

$order = $result->fetch_assoc();

if ($order['status'] !== 'to receive') {
    $_SESSION['error_message'] = "This order is not ready to be marked as received.";
    redirect("/lilian-online-store/orders.php");
}

if ((int)$order['customer_received_enabled'] !== 1) {
    $_SESSION['error_message'] = "The order received button is not enabled yet by admin.";
    redirect("/lilian-online-store/orders.php");
}

$updateStmt = $conn->prepare("
    UPDATE orders
    SET status = 'delivered'
    WHERE id = ?
");
$updateStmt->bind_param("i", $orderId);
$updateStmt->execute();

addNotification(
    $conn,
    'order_received',
    "{$order['full_name']} marked order #{$order['order_number']} as received.",
    $orderId
);

$_SESSION['success_message'] = "Order marked as received successfully.";
redirect("/lilian-online-store/orders.php?status=to-rate");
?>