<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['2fa_status']) || $_SESSION['2fa_status'] !== 'verified') {
    header("Location: verify.php");
    exit();
}
?>