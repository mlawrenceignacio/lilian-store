<?php
require_once __DIR__ . "/../config/database.php";

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function getCartCount($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0) AS cart_count
        FROM carts c
        LEFT JOIN cart_items ci ON c.id = ci.cart_id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['cart_count'] ?? 0;
}

function formatPeso($amount) {
    return "₱" . number_format((float)$amount, 2);
}

function generateOrderNumber() {
    return "LSS-" . date("Ymd") . "-" . strtoupper(substr(md5(uniqid()), 0, 6));
}

function addNotification($conn, $type, $message, $orderId = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (type, message, related_order_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $type, $message, $orderId);
    $stmt->execute();
}

function logAdminActivity($conn, $adminUserId, $activity) {
    $stmt = $conn->prepare("INSERT INTO admin_activity_logs (admin_user_id, activity) VALUES (?, ?)");
    $stmt->bind_param("is", $adminUserId, $activity);
    $stmt->execute();
}

function getUserCartItems($conn, $userId) {
    $items = [];
    $stmt = $conn->prepare("
        SELECT
            ci.id AS cart_item_id,
            ci.quantity,
            p.id AS product_id,
            p.name,
            p.category,
            p.price,
            p.description,
            p.image
        FROM carts c
        INNER JOIN cart_items ci ON c.id = ci.cart_id
        INNER JOIN products p ON ci.product_id = p.id
        WHERE c.user_id = ?
        ORDER BY ci.id DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['item_subtotal'] = (float)$row['price'] * (int)$row['quantity'];
        $items[] = $row;
    }

    return $items;
}

function uploadPaymentProof($file, $targetFolder) {
    if (!isset($file) || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return [false, null, "Please upload a valid payment proof image."];
    }

    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        return [false, null, "Only JPG, PNG, and WEBP images are allowed."];
    }

    if ($file['size'] > $maxSize) {
        return [false, null, "Payment proof must not exceed 5MB."];
    }

    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    $extension = $extensionMap[$mimeType] ?? 'jpg';

    if (!is_dir($targetFolder)) {
        mkdir($targetFolder, 0777, true);
    }

    $filename = uniqid('proof_', true) . '.' . $extension;
    $destination = rtrim($targetFolder, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return [false, null, "Failed to upload payment proof."];
    }

    $relativePath = str_replace('\\', '/', $destination);

    if (strpos($relativePath, $_SERVER['DOCUMENT_ROOT']) === 0) {
        $relativePath = ltrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', $relativePath), '/');
    } else {
        $relativePath = str_replace(
            str_replace('\\', '/', dirname(__DIR__)),
            'lilian-online-store',
            str_replace('\\', '/', $destination)
        );
    }

    return [true, $relativePath, null];
}

function updateProductAverageRating($conn, $productId) {
    $stmt = $conn->prepare("
        SELECT AVG(rating) AS avg_rating
        FROM ratings
        WHERE product_id = ?
    ");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $average = $result && $result['avg_rating'] !== null
        ? round((float)$result['avg_rating'], 2)
        : 0.00;

    $updateStmt = $conn->prepare("
        UPDATE products
        SET avg_rating = ?
        WHERE id = ?
    ");
    $updateStmt->bind_param("di", $average, $productId);
    $updateStmt->execute();
}

function hasUserRatedProductInOrder($conn, $userId, $orderId, $productId) {
    $stmt = $conn->prepare("
        SELECT id
        FROM ratings
        WHERE user_id = ? AND order_id = ? AND product_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("iii", $userId, $orderId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf() {
    $posted = $_POST['csrf_token'] ?? '';
    $saved = $_SESSION['csrf_token'] ?? '';

    if (!$posted || !$saved || !hash_equals($saved, $posted)) {
        $_SESSION['error_message'] = "Invalid form request. Please try again.";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/lilian-online-store/index.php'));
        exit;
    }
}
?>