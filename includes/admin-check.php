<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/functions.php";

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error_message'] = "You are not allowed to access that page.";
    redirect("/lilian-online-store/index.php");
}
?>