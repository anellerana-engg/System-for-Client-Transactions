<?php
session_start();
include("connect.php");

if(!isset($_SESSION['email'])){
    header("Location: index.php");
    exit();
}

$email = $_SESSION['email'];


$stmt = $conn->prepare("SELECT firstName, lastName, email, password, role, assigned_type FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();


$logs_stmt = $conn->prepare("SELECT action_performed, timestamp FROM activity_logs WHERE user_email=? ORDER BY timestamp DESC LIMIT 10");
$logs_stmt->bind_param("s", $email);
$logs_stmt->execute();
$logs = $logs_stmt->get_result();


if(isset($_POST['change_password'])){

    $current = trim($_POST['current_password']);
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    $email = $_SESSION['email'];

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    $user = $check->fetch_assoc();

    if(md5($current) !== $user['password']){
        echo "<script>alert('Current password is incorrect'); window.location='settings.php';</script>";
        exit();
    }

    if(md5($new) === $user['password']){
    echo "<script>alert('New password cannot be the same as the current password'); window.location='settings.php';</script>";
    exit();
}

    if($new !== $confirm){
        echo "<script>alert('Passwords do not match'); window.location='settings.php';</script>";
        exit();
    }


    $newPass = md5($new);

    $update = $conn->query("
        UPDATE users 
        SET password='$newPass'
        WHERE email='$email'
    ");

    if(!$update){
        die("Update failed: " . $conn->error);
    }

    echo "<script>alert('Password updated!'); window.location='settings.php';</script>";
}
?>

<!DOCTYPE html>

<html>
<head>
<title>Settings</title>

<div class="header">
    <div style="display:flex; align-items:center;">
        <img src="logo.png">
        <div class="header-text">
            <h1>Office Name | <span style="font-weight:400; color:var(--text-muted)">Settings</span></h1>
        </div>
    </div>
    <a href="logout.php" class="logout-btn">LOGOUT</a>
</div>

<style>

:root { 
    --mswdo-blue: #0038a8; 
    --mswdo-blue-light: #eef2ff;
    --mswdo-red: #c63927; 
    --bg-gray: #f8fafc; 
    --card-border: #e2e8f0; 
    --text-main: #1e293b;
    --text-muted: #64748b;
    --shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
}

html, body {
    height: 100%;
    margin: 0;

}

body {
    font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
    background: var(--bg-gray);
    color: var(--text-main);
    padding-bottom: 80px;
}

.header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: white;
    padding: 12px 40px;
    border-bottom: 1px solid var(--card-border);
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    position: sticky;
    top: 0;
    z-index: 1001;
}

.header img {
    height: 45px;
    margin-right: 15px;
}

.header-text h1 {
    margin: 0;
    font-size: 18px;
    color: var(--mswdo-blue);
}

.logout-btn {
    background: var(--mswdo-red);
    color: white;
    text-decoration: none;
    padding: 8px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
}

.container {
    max-width: 850px;
    margin: 25px auto 90px auto;
    padding: 20px;
    background: white;
    border: 1px solid var(--card-border);
    border-radius: 16px;
    box-shadow: var(--shadow);

    height: calc(100vh - 220px);
    overflow-y: auto;
}


h2 {
    color: var(--mswdo-blue);
    font-size: 18px;
    margin-top: 35px;
    margin-bottom: 25px;
    border-bottom: 2px solid var(--mswdo-blue-light);
    padding-bottom: 8px;
}

h2:first-of-type {
    margin-top: 0;
}

.info-box {
    background: #f8fafc;
    border: 1px solid var(--card-border);
    padding: 14px;
    border-radius: 12px;
    margin-bottom: 12px;
    font-size: 14px;
    line-height: 1.5;
}

input {
    width: 100%;
    padding: 12px;
    border: 1.5px solid var(--card-border);
    border-radius: 8px;
    background-color: #fcfcfd;
    font-size: 14px;
    box-sizing: border-box;
    margin-bottom: 12px;
}

button {
    width: 100%;
    padding: 12px;
    background: var(--mswdo-blue);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.2s ease;
}

button:hover {
    background: #002a80;
}

.log-item {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
}

.log-item:last-child {
    border-bottom: none;
}

a {
    color: var(--mswdo-blue);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}

.bottom-nav {
    display: flex;
    background: #1e293b;
    position: fixed;
    bottom: 0;
    width: 100%;
    z-index: 1000;
}

.nav-item {
    color: #94a3b8;
    text-decoration: none;
    padding: 10px;
    flex-grow: 1;
    text-align: center;
    font-size: 13px;
    font-weight: 600;
}

.nav-item.active {
    color: white;
    background: #334155;
    border-top: 4px solid var(--mswdo-red);
}
</style>

<div class="bottom-nav">
    <a href="homepage.php" class="nav-item">Dashboard</a>
    <a href="service.php" class="nav-item">Service Records</a>
    <a href="profiles.php" class="nav-item">Client Profiles</a>
    <a href="entry.php" class="nav-item">Entry Page</a>
    <a href="settings.php" class="nav-item active">Settings</a>
</div>
</head>
<body>

<div class="container">

<h2>👤 Profile</h2>

<div class="info-box">
<strong>Name:</strong><br>
<?= htmlspecialchars($user['firstName'].' '.$user['lastName']) ?>
</div>

<div class="info-box">
<strong>Email:</strong><br>
<?= htmlspecialchars($user['email']) ?>
</div>

<div class="info-box">
<strong>Role:</strong><br>
<?= htmlspecialchars($user['role']) ?>
</div>

<div class="info-box">
<strong>Assigned Assistance:</strong><br>
<?= htmlspecialchars($user['assigned_type'] ?? 'Not Assigned') ?>
</div>

<h2>🔒 Change Password</h2>

<form method="POST">
<input type="password" name="current_password" placeholder="Current Password" required>
<input type="password" name="new_password" placeholder="New Password" required>
<input type="password" name="confirm_password" placeholder="Confirm Password" required>

<button type="submit" name="change_password">Update Password</button>

</form>

<h2>📊 Activity History</h2>

<?php if($logs->num_rows > 0): ?>

<?php while($log = $logs->fetch_assoc()): ?>

<div class="log-item">
<strong><?= htmlspecialchars($log['action_performed']) ?></strong><br>
<small><?= htmlspecialchars($log['timestamp']) ?></small>
</div>
<?php endwhile; ?>
<?php else: ?>
<p>No activity yet.</p>
<?php endif; ?>

</div>

</body>
</html>
