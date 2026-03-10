<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/orders.php?status=to-rate");
}

if (!isLoggedIn() || isAdmin()) {
    $_SESSION['error_message'] = "Please login as a customer first.";
    redirect("/lilian-online-store/auth/login.php");
}

$userId = (int) $_SESSION['user_id'];
$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
$review = sanitize($_POST['review'] ?? '');

if ($orderId <= 0 || $productId <= 0) {
    $_SESSION['error_message'] = "Invalid rating request.";
    redirect("/lilian-online-store/orders.php?status=to-rate");
}

if ($rating < 1 || $rating > 5) {
    $_SESSION['error_message'] = "Please select a rating from 1 to 5 stars.";
    redirect("/lilian-online-store/rate-order.php?order_id=" . $orderId);
}

$orderStmt = $conn->prepare("
    SELECT id, status
    FROM orders
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$orderStmt->bind_param("ii", $orderId, $userId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderResult->num_rows === 0) {
    $_SESSION['error_message'] = "Order not found.";
    redirect("/lilian-online-store/orders.php?status=to-rate");
}

$order = $orderResult->fetch_assoc();

if ($order['status'] !== 'delivered') {
    $_SESSION['error_message'] = "Only delivered orders can be rated.";
    redirect("/lilian-online-store/orders.php");
}

$itemStmt = $conn->prepare("
    SELECT id
    FROM order_items
    WHERE order_id = ? AND product_id = ?
    LIMIT 1
");
$itemStmt->bind_param("ii", $orderId, $productId);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

if ($itemResult->num_rows === 0) {
    $_SESSION['error_message'] = "That product does not belong to this order.";
    redirect("/lilian-online-store/orders.php?status=to-rate");
}

if (hasUserRatedProductInOrder($conn, $userId, $orderId, $productId)) {
    $_SESSION['error_message'] = "You already rated this product for this order.";
    redirect("/lilian-online-store/rate-order.php?order_id=" . $orderId);
}

$insertStmt = $conn->prepare("
    INSERT INTO ratings (order_id, product_id, user_id, rating, review)
    VALUES (?, ?, ?, ?, ?)
");
$insertStmt->bind_param("iiiis", $orderId, $productId, $userId, $rating, $review);
$insertStmt->execute();

updateProductAverageRating($conn, $productId);

$_SESSION['success_message'] = "Rating submitted successfully.";
redirect("/lilian-online-store/rate-order.php?order_id=" . $orderId);
?>