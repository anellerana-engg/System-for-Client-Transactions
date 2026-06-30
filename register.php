<?php 
session_start();
include 'connect.php';
include 'mailer.php'; 

require 'vendor/autoload.php';
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\GoogleChartsQRCodeProvider;

$tfa = new TwoFactorAuth(
    new GoogleChartsQRCodeProvider(),
    'Office'
);


if(isset($_POST['signUp'])){

    $firstName = mysqli_real_escape_string($conn, $_POST['fName']);
    $lastName  = mysqli_real_escape_string($conn, $_POST['lName']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $password  = md5($_POST['password']); 
    $role      = mysqli_real_escape_string($conn, $_POST['role']);


    $assigned_type = isset($_POST['assistance']) 
        ? mysqli_real_escape_string($conn, $_POST['assistance']) 
        : NULL;

    $token = bin2hex(random_bytes(16)); 

    $checkEmail = $conn->query("SELECT * FROM users WHERE email='$email'");

    if($checkEmail->num_rows > 0){
        echo "<script>alert('Email Address Already Exists!'); window.location.href='index.php';</script>";
        exit();
    }


    $insertQuery = "INSERT INTO users(firstName, lastName, email, password, role, assigned_type, is_verified, token)
                    VALUES ('$firstName', '$lastName', '$email', '$password', '$role', '$assigned_type', 0, '$token')";
    
    if($conn->query($insertQuery)){
        
        $ownerEmail = "owner@gmail.com"; 
        $approveLink = "http://localhost/login/approve.php?token=$token"; 
        
        $message = "
            <div style='font-family:Arial; padding:20px; border:1px solid #ddd;'>
                <h3 style='color:#0b3d91;'>New Registration Request</h3>
                <ul>
                    <li><b>Name:</b> $firstName $lastName</li>
                    <li><b>Email:</b> $email</li>
                    <li><b>Role:</b> $role</li>";


        if(!empty($assigned_type)){
            $message .= "<li><b>Assigned Type:</b> $assigned_type</li>";
        }

        $message .= "
                </ul>
                <a href='$approveLink' style='padding:10px 20px; background:#0b3d91; color:#fff; text-decoration:none;'>Approve</a>
            </div>
        ";

        sendSystemEmail($ownerEmail, "New User Signup", $message);

        echo "<script>alert('Registered successfully! Wait for admin approval.'); window.location.href='index.php';</script>";
    }
}


if(isset($_POST['signIn'])){
$email = mysqli_real_escape_string($conn, $_POST['email']);
$password = $_POST['password'];

$checkEmail = $conn->query("SELECT * FROM users WHERE email='$email'");

if($checkEmail->num_rows == 0){
    echo "<script>alert('The email or password does not exist.'); window.location.href='index.php';</script>";
    exit();
}

$user = $checkEmail->fetch_assoc();
$storedPassword = $user['password'];


if(
    password_verify($password, $storedPassword) || 
    md5($password) === $storedPassword
){

} else {
    echo "<script>alert('The email or password is incorrect.'); window.location.href='index.php';</script>";
    exit();
}

if($user['is_verified'] != 1){
    echo "<script>alert('Waiting for admin approval'); window.location.href='index.php';</script>";
    exit();
}


$_SESSION['temp_email'] = $email;

if(empty($user['ga_secret'])){

    $secret = $tfa->createSecret();
    $conn->query("UPDATE users SET ga_secret='$secret' WHERE email='$email'");

    $_SESSION['ga_secret'] = $secret;
    $_SESSION['setup_email'] = $email;

    header("Location: setup_2fa.php");
    exit();

} else {
    header("Location: verify.php");
    exit();
}
}
?>