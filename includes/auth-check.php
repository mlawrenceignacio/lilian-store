<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/functions.php";

if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Please login first to continue.";
    redirect("/lilian-online-store/auth/login.php");
}
?>