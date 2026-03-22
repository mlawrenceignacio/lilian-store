<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/admin-check.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/admin/admins.php");
}

$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

$_SESSION['form_old'] = [
    'full_name' => $fullName,
    'username' => $username,
    'email' => $email,
    'phone' => $phone,
    'address' => $address,
];

if ($fullName === '' || $username === '' || $email === '' || $password === '' || $confirmPassword === '') {
    $_SESSION['error_message'] = "Please fill in all required admin account fields.";
    redirect("/lilian-online-store/admin/admins.php");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Please enter a valid email address.";
    redirect("/lilian-online-store/admin/admins.php");
}

if (strlen($password) < 8) {
    $_SESSION['error_message'] = "Password must be at least 8 characters long.";
    redirect("/lilian-online-store/admin/admins.php");
}

if ($password !== $confirmPassword) {
    $_SESSION['error_message'] = "Passwords do not match.";
    redirect("/lilian-online-store/admin/admins.php");
}

$checkStmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE email = ? OR username = ?
    LIMIT 1
");
$checkStmt->bind_param("ss", $email, $username);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();

if ($existing) {
    $_SESSION['error_message'] = "An account with that email or username already exists.";
    redirect("/lilian-online-store/admin/admins.php");
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$insertStmt = $conn->prepare("
    INSERT INTO users (
        full_name,
        username,
        email,
        phone,
        address,
        password,
        role,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, 'admin', NOW())
");
$insertStmt->bind_param("ssssss", $fullName, $username, $email, $phone, $address, $passwordHash);

if (!$insertStmt->execute()) {
    $_SESSION['error_message'] = "Failed to register the new admin account.";
    redirect("/lilian-online-store/admin/admins.php");
}

if (function_exists('logAdminActivity')) {
    logAdminActivity(
        $conn,
        (int)($_SESSION['user_id'] ?? 0),
        "Registered a new admin account: {$fullName} ({$email})"
    );
}

unset($_SESSION['form_old']);
$_SESSION['success_message'] = "New admin account registered successfully.";
redirect("/lilian-online-store/admin/admins.php");