<?php
session_start();
require 'vendor/autoload.php';

if(!isset($_SESSION['ga_secret'])){
    die("No secret found.");
}

$secret = $_SESSION['ga_secret'];

$issuer = "Company Name";
$email = $_SESSION['setup_email'] ?? 'user';

$qrText = "otpauth://totp/" . urlencode($issuer . ":" . $email) .
          "?secret=" . $secret .
          "&issuer=" . urlencode($issuer);

$qrImage = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrText);
?>

<!DOCTYPE html>
<html>
<head>
<title>Setup Authenticator</title>
</head>

<body style="text-align:center; font-family:Arial;">

<h2>Scan QR Code</h2>

<p>Open Google Authenticator → Tap "+" → Scan QR</p>

<img src="<?php echo $qrImage; ?>">

<p><b>Manual Setup Key:</b></p>
<p><?php echo $secret; ?></p>

<p>After scanning, log in normally.</p>

<a href="index.php">Go to Login</a>

</body>
</html>