<?php
session_start();
include 'connect.php';
require 'vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\GoogleChartsQRCodeProvider;

if(!isset($_SESSION['temp_email'])){
    header("Location: index.php");
    exit();
}

if(isset($_POST['submit_pin'])){

    $email = $_SESSION['temp_email'];
    $code  = trim($_POST['pin']);

    if(empty($code)){
        echo "<script>alert('Please enter the authentication code');</script>";
    } else {

        $res = $conn->query("SELECT * FROM users WHERE email='$email'");
        $user = $res->fetch_assoc();

        if(!$user){
            session_destroy();
            header("Location: index.php");
            exit();
        }

        if(empty($user['ga_secret'])){
            echo "<script>
                    alert('Authenticator not set up. Please login again.');
                    window.location.href='index.php';
                  </script>";
            exit();
        }

        $tfa = new TwoFactorAuth(
            new GoogleChartsQRCodeProvider(),
            'Company Name'
        );

        if($tfa->verifyCode($user['ga_secret'], $code, 1)){

            $_SESSION['email'] = $user['email'];
            $_SESSION['role']  = $user['role'];

            unset($_SESSION['temp_email']);

            header("Location: homepage.php");
            exit();

        } else {
            echo "<script>alert('Invalid Authenticator Code');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify PIN</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    background: #f1f4f8;
}

.header {
    background: #0b3d91;
    color: white;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 4px solid #d4a017;
}

.header img {
    width: 55px;
    height: 55px;
    filter: drop-shadow(0 0 2px white)
            drop-shadow(0 0 3px white)
            drop-shadow(0 0 4px white);
}

.header-text h2 {
    font-size: 16px;
}

.header-text p {
    font-size: 12px;
}


.main {
    display: flex;
    justify-content: center;
    align-items: center;
    height: calc(100vh - 80px);
}


.container {
    background: #ffffff;
    width: 380px;
    padding: 30px;
    border-radius: 6px;
    border-top: 6px solid #0b3d91;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}


.form-title {
    text-align: center;
    margin-bottom: 20px;
    color: #0b3d91;
}


.input-group {
    position: relative;
    margin-bottom: 20px;
}

.input-group i {
    position: absolute;
    top: 12px;
    left: 10px;
    color: #0b3d91;
}

.input-group input {
    width: 100%;
    padding: 10px 10px 10px 35px;
    border: 1px solid #ccc;
    border-radius: 4px;
    text-align: center;
    font-size: 18px;
    letter-spacing: 6px;
}

.input-group label {
    position: absolute;
    top: 10px;
    left: 35px;
    font-size: 13px;
    color: #888;
}

.input-group input:focus ~ label,
.input-group input:not(:placeholder-shown) ~ label {
    top: -8px;
    left: 30px;
    background: white;
    font-size: 11px;
    color: #0b3d91;
}


.btn {
    width: 100%;
    padding: 10px;
    background: #0b3d91;
    color: white;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
}

.btn:hover {
    background: #082c6c;
}


.info {
    text-align: center;
    font-size: 13px;
    color: #555;
    margin-bottom: 15px;
}
</style>
</head>

<body>

<div class="header">
    <img src="logo.png">
    <div class="header-text">
        <h2>Office</h2>
        <p>System</p>
    </div>
</div>

<div class="main">
    <div class="container">

        <h1 class="form-title">Verify PIN</h1>

        <p class="info">
            Enter the 6-digit code from your Google Authenticator app
        </p>

        <form method="POST">

            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="text" name="pin" placeholder=" " maxlength="6" required>
                <label>Enter PIN</label>
            </div>

            <input type="submit" name="submit_pin" class="btn" value="Verify">

        </form>

    </div>
</div>

</body>
</html>