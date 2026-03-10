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
$reason = sanitize($_POST['cancellation_reason'] ?? '');

if ($orderId <= 0) {
    $_SESSION['error_message'] = "Invalid order selected.";
    redirect("/lilian-online-store/orders.php");
}

$stmt = $conn->prepare("
    SELECT id, order_number, status, full_name
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

if (!in_array($order['status'], ['to pay', 'to ship'], true)) {
    $_SESSION['error_message'] = "This order can no longer be cancelled.";
    redirect("/lilian-online-store/orders.php");
}

$updateStmt = $conn->prepare("
    UPDATE orders
    SET status = 'cancelled', cancellation_reason = ?
    WHERE id = ?
");
$updateStmt->bind_param("si", $reason, $orderId);
$updateStmt->execute();

addNotification(
    $conn,
    'order_cancelled',
    "{$order['full_name']} cancelled order #{$order['order_number']}.",
    $orderId
);

$_SESSION['success_message'] = "Order cancelled successfully.";
redirect("/lilian-online-store/orders.php");
?>