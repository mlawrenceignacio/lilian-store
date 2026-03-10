<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("/lilian-online-store/admin/profile.php");
}

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = "You are not allowed to perform that action.";
    redirect("/lilian-online-store/auth/login.php");
}

$adminUserId = (int) $_SESSION['user_id'];

$fullName = sanitize($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$username = sanitize($_POST['username'] ?? '');
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($fullName === '' || $email === '' || $username === '') {
    $_SESSION['error_message'] = "Full name, email, and username are required.";
    redirect("/lilian-online-store/admin/profile.php");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Please enter a valid email address.";
    redirect("/lilian-online-store/admin/profile.php");
}

$checkUserStmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE email = ? AND id != ?
    LIMIT 1
");
$checkUserStmt->bind_param("si", $email, $adminUserId);
$checkUserStmt->execute();
$checkUserResult = $checkUserStmt->get_result();

if ($checkUserResult->num_rows > 0) {
    $_SESSION['error_message'] = "That email is already being used by another account.";
    redirect("/lilian-online-store/admin/profile.php");
}

$checkAdminStmt = $conn->prepare("
    SELECT id
    FROM admins
    WHERE username = ? AND user_id != ?
    LIMIT 1
");
$checkAdminStmt->bind_param("si", $username, $adminUserId);
$checkAdminStmt->execute();
$checkAdminResult = $checkAdminStmt->get_result();

if ($checkAdminResult->num_rows > 0) {
    $_SESSION['error_message'] = "That admin username is already taken.";
    redirect("/lilian-online-store/admin/profile.php");
}

$changePassword = false;

if ($newPassword !== '' || $confirmPassword !== '') {
    if (strlen($newPassword) < 8) {
        $_SESSION['error_message'] = "New password must be at least 8 characters long.";
        redirect("/lilian-online-store/admin/profile.php");
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['error_message'] = "New password and confirm password do not match.";
        redirect("/lilian-online-store/admin/profile.php");
    }

    $changePassword = true;
}

$conn->begin_transaction();

try {
    if ($changePassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $userStmt = $conn->prepare("
            UPDATE users
            SET full_name = ?, email = ?, phone = ?, address = ?, password = ?
            WHERE id = ?
        ");
        $userStmt->bind_param("sssssi", $fullName, $email, $phone, $address, $hashedPassword, $adminUserId);
    } else {
        $userStmt = $conn->prepare("
            UPDATE users
            SET full_name = ?, email = ?, phone = ?, address = ?
            WHERE id = ?
        ");
        $userStmt->bind_param("ssssi", $fullName, $email, $phone, $address, $adminUserId);
    }
    $userStmt->execute();

    $adminStmt = $conn->prepare("
        UPDATE admins
        SET username = ?
        WHERE user_id = ?
    ");
    $adminStmt->bind_param("si", $username, $adminUserId);
    $adminStmt->execute();

    $conn->commit();

    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;

    $activityText = $changePassword
        ? "Admin updated profile details and changed the admin password."
        : "Admin updated profile details and admin username.";

    logAdminActivity($conn, $adminUserId, $activityText);

    $_SESSION['success_message'] = "Admin profile updated successfully.";
    redirect("/lilian-online-store/admin/profile.php");
} catch (Throwable $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Failed to update admin profile.";
    redirect("/lilian-online-store/admin/profile.php");
}
?>