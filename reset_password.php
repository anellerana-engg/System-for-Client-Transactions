<?php
include 'connect.php';

if(!isset($_GET['token'])){
    die("Invalid request");
}

$token = $_GET['token'];

$check = $conn->query("SELECT * FROM users WHERE reset_token='$token'");

if($check->num_rows == 0){
    die("Invalid or expired token");
}

$user = $check->fetch_assoc();

if(strtotime($user['token_expire']) < time()){
    die("Token expired");
}

if(isset($_POST['reset'])){
    $newPass = md5($_POST['password']);

    $conn->query("UPDATE users 
                  SET password='$newPass', reset_token=NULL, token_expire=NULL 
                  WHERE reset_token='$token'");

    echo "<script>alert('Password updated!'); window.location='index.php';</script>";
}
?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>

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
    filter: drop-shadow(0 0 2px white) drop-shadow(0 0 3px white);
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
    height: calc(100vh - 60px);
}


.container {
    background: white;
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
}

.input-group label {
    position: absolute;
    top: 10px;
    left: 35px;
    font-size: 13px;
    color: #888;
    transition: 0.3s;
}

.input-group input:focus ~ label,
.input-group input:not(:placeholder-shown) ~ label {
    top: -8px;
    left: 30px;
    background: white;
    padding: 0 5px;
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

.links {
    text-align: center;
    margin-top: 15px;
}
</style>

</head>

<body>

<div class="header">
    <img src="logo.png">
    <div class="header-text">
        <h2>Office Name</h2>
        <p>System</p>
    </div>
</div>

<div class="main">

<div class="container">

<h2 class="form-title">Reset Password</h2>

<form method="POST">

<div class="input-group">
    <i class="fas fa-lock"></i>
    <input type="password" name="password" placeholder=" " required>
    <label>New Password</label>
</div>

<button class="btn" name="reset">Reset Password</button>

</form>

<div class="links">
    <p><a href="index.php" style="color:#d4a017;">← Back to Login</a></p>
</div>

</div>

</div>

</body>
</html>
