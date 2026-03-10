<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/shop.php");
}

if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Please login first to add items to your cart.";
    redirect("/lilian-online-store/auth/login.php");
}

$userId = (int) $_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

if ($productId <= 0 || $quantity <= 0) {
    $_SESSION['error_message'] = "Invalid product or quantity.";
    redirect("/lilian-online-store/shop.php");
}

if ($quantity > 99) {
    $quantity = 99;
}

/* Check if product exists */
$productStmt = $conn->prepare("SELECT id, name FROM products WHERE id = ?");
$productStmt->bind_param("i", $productId);
$productStmt->execute();
$productResult = $productStmt->get_result();

if ($productResult->num_rows === 0) {
    $_SESSION['error_message'] = "Product not found.";
    redirect("/lilian-online-store/shop.php");
}

$product = $productResult->fetch_assoc();

/* Find or create cart */
$cartStmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? LIMIT 1");
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();

if ($cartResult->num_rows > 0) {
    $cart = $cartResult->fetch_assoc();
    $cartId = (int) $cart['id'];
} else {
    $createCartStmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
    $createCartStmt->bind_param("i", $userId);
    $createCartStmt->execute();
    $cartId = $createCartStmt->insert_id;
}

/* Check if item already exists */
$itemStmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1");
$itemStmt->bind_param("ii", $cartId, $productId);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

if ($itemResult->num_rows > 0) {
    $existing = $itemResult->fetch_assoc();
    $newQuantity = (int) $existing['quantity'] + $quantity;

    if ($newQuantity > 99) {
        $newQuantity = 99;
    }

    $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $newQuantity, $existing['id']);
    $updateStmt->execute();
} else {
    $insertStmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
    $insertStmt->bind_param("iii", $cartId, $productId, $quantity);
    $insertStmt->execute();
}

$_SESSION['success_message'] = $product['name'] . " added to cart successfully.";
redirect("/lilian-online-store/shop.php");
?>