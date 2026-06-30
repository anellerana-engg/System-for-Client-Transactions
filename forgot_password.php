<?php
include 'connect.php';
include 'mailer.php';

if(isset($_POST['submit'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");

    if($check->num_rows > 0){

        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        $conn->query("UPDATE users 
                      SET reset_token='$token', token_expire='$expiry' 
                      WHERE email='$email'");

        $link = "#";

        $message = "
            <h3>Password Reset Request</h3>
            <p>Click below to reset your password:</p>
            <a href='$link'>Reset Password</a>
            <p>This link expires in 15 minutes.</p>
        ";

        sendSystemEmail($email, "Reset Password", $message);

        echo "<script>alert('Reset link sent to your email');</script>";
    } else {
        echo "<script>alert('Email not found');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Portal</title>

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

            filter: 
                drop-shadow(0 0 2px white)
                drop-shadow(0 0 3px white)
                drop-shadow(0 0 4px white);
        }

        .header-text {
            line-height: 1.2;
        }

        .header-text h2 {
            font-size: 16px;
            font-weight: 600;
        }

        .header-text p {
            font-size: 12px;
            opacity: 0.9;
        }

        .main {
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 60px);
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
            font-weight: 500;
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
            outline: none;
        }

        .input-group input:focus {
            border-color: #0b3d91;
        }

        .input-group label {
            position: absolute;
            top: 10px;
            left: 35px;
            font-size: 13px;
            color: #888;
            transition: 0.3s;
            pointer-events: none;
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

        .links p {
            font-size: 13px;
            color: #555;
        }

        .links button {
            background: none;
            border: none;
            color: #d4a017;
            font-weight: bold;
            cursor: pointer;
        }

        .links button:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="header">
    <img src="logo.png" alt="Logo">
    <div class="header-text">
        <h2>Office</h2>
        <p>Client Registration and Information System</p>
    </div>
</div>

<div class="main">

    <div class="container">

        <h2 class="form-title">Forgot Password</h2>

        <form method="POST">

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder=" " required>
                <label>Email Address</label>
            </div>

            <button class="btn" type="submit" name="submit">Send Reset Link</button>

        </form>

        
<div class="links">
    <p><a href="index.php" style="color:#d4a017;">← Back to Login</a></p>
</div>

    </div>

</div>

</body>
</html>