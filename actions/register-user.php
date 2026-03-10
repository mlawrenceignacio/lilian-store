<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/auth/register.php");
}

$fullName = sanitize($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

$_SESSION['form_old'] = [
    'full_name' => $fullName,
    'email' => $email,
    'phone' => $phone,
    'address' => $address
];

if ($fullName === '' || $email === '' || $phone === '' || $address === '' || $password === '' || $confirmPassword === '') {
    $_SESSION['error_message'] = "Please fill in all fields.";
    redirect("/lilian-online-store/auth/register.php");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Please enter a valid email address.";
    redirect("/lilian-online-store/auth/register.php");
}

if (strlen($password) < 8) {
    $_SESSION['error_message'] = "Password must be at least 8 characters long.";
    redirect("/lilian-online-store/auth/register.php");
}

if ($password !== $confirmPassword) {
    $_SESSION['error_message'] = "Passwords do not match.";
    redirect("/lilian-online-store/auth/register.php");
}

$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$existing = $checkStmt->get_result();

if ($existing->num_rows > 0) {
    $_SESSION['error_message'] = "That email is already registered.";
    redirect("/lilian-online-store/auth/register.php");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insertStmt = $conn->prepare("
    INSERT INTO users (full_name, email, password, role, phone, address)
    VALUES (?, ?, ?, 'customer', ?, ?)
");
$insertStmt->bind_param("sssss", $fullName, $email, $hashedPassword, $phone, $address);

if (!$insertStmt->execute()) {
    $_SESSION['error_message'] = "Registration failed. Please try again.";
    redirect("/lilian-online-store/auth/register.php");
}

$userId = $insertStmt->insert_id;

$_SESSION['user_id'] = $userId;
$_SESSION['full_name'] = $fullName;
$_SESSION['email'] = $email;
$_SESSION['role'] = 'customer';

unset($_SESSION['form_old']);
$_SESSION['success_message'] = "Account created successfully. Welcome to Lilian Sari-Sari Store!";
redirect("/lilian-online-store/index.php");
?>