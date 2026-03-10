<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/auth/login.php");
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$_SESSION['form_old'] = [
    'email' => $email
];

if ($email === '' || $password === '') {
    $_SESSION['error_message'] = "Please enter your email and password.";
    redirect("/lilian-online-store/auth/login.php");
}

$stmt = $conn->prepare("
    SELECT id, full_name, email, password, role
    FROM users
    WHERE email = ?
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Invalid email or password.";
    redirect("/lilian-online-store/auth/login.php");
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    $_SESSION['error_message'] = "Invalid email or password.";
    redirect("/lilian-online-store/auth/login.php");
}

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

unset($_SESSION['form_old']);

if ($user['role'] === 'admin') {
    logAdminActivity($conn, (int) $user['id'], "Admin logged in.");
    $_SESSION['success_message'] = "Welcome back, admin.";
    redirect("/lilian-online-store/admin/dashboard.php");
}

$_SESSION['success_message'] = "Welcome back, " . $user['full_name'] . ".";
redirect("/lilian-online-store/index.php");
?>