<?php
session_start();
include("connect.php");
include("session_check.php");


$user_email = $_SESSION['email'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'staff';

if ($user_role !== 'admin' && $user_email !== 'owner@gmail.com') {
    echo "<script>alert('Access Denied: Admins Only'); window.location.href='homepage.php';</script>";
    exit();
}


$search = "";
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
}


$sql = "
SELECT activity_logs.id, activity_logs.user_email, activity_logs.action_performed, activity_logs.timestamp,
       users.firstName, users.lastName
FROM activity_logs 
LEFT JOIN users ON activity_logs.user_email = users.email
";

if (!empty($search)) {
    $sql .= " WHERE users.firstName LIKE '%$search%' OR users.lastName LIKE '%$search%' ";
}

$sql .= " ORDER BY activity_logs.id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>System Activity Logs</title>

<style>
body { font-family: sans-serif; background: #f4f7f6; padding: 40px; }
.log-container { max-width: 900px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
h2 { color: #c63927; border-bottom: 2px solid #eee; padding-bottom: 10px; }

table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #0038a8; color: white; }
tr:hover { background: #f9f9f9; }

.back-btn { text-decoration: none; color: #0038a8; font-weight: bold; }

.search-box { margin-bottom: 15px; }
.search-box input { padding: 8px; width: 70%; }
.search-box button { padding: 8px 12px; background: #0038a8; color: white; border: none; }

.badge { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; }
.dashboard { background: #e0f2fe; color: #0369a1; }
.input { background: #dcfce7; color: #166534; }
.edit { background: #fef9c3; color: #854d0e; }
.delete { background: #fee2e2; color: #991b1b; }
.other { background: #e5e7eb; color: #374151; }

</style>
</head>

<body>

<div class="log-container">

<a href="homepage.php" class="back-btn">← Back to Dashboard</a>

<h2>System Activity Logs</h2>
<form method="GET" class="search-box">
    <input type="text" name="search" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit">Search</button>
</form>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Action</th>
    <th>Timestamp</th>
</tr>
</thead>

<tbody>

<?php
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $action = strtolower($row['action_performed']);

        if (strpos($action, 'dashboard') !== false) {
            $action_display = "Accessed Dashboard";
            $class = "dashboard";
        } 
        elseif (
            strpos($action, 'input') !== false ||
            strpos($action, 'add') !== false ||
            strpos($action, 'new') !== false ||
            strpos($action, 'submit') !== false
        ) {
            $action_display = "Input";
            $class = "input";
        } 
        elseif (
            strpos($action, 'edit') !== false ||
            strpos($action, 'update') !== false
        ) {
            $action_display = "Edit";
            $class = "edit";
        } 
        elseif (
            strpos($action, 'delete') !== false ||
            strpos($action, 'remove') !== false
        ) {
            $action_display = "Delete";
            $class = "delete";
        } 
        else {
            $action_display = $row['action_performed'];
            $class = "other";
        }

        echo "<tr>
                <td>{$row['id']}</td>
                <td>" . ($row['firstName'] ?? 'Unknown') . " " . ($row['lastName'] ?? '') . "</td>
                <td>{$row['user_email']}</td>
                <td><span class='badge $class'>{$action_display}</span></td>
                <td>{$row['timestamp']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='5' style='text-align:center;'>No results found.</td></tr>";
}
?>

</tbody>
</table>

</div>

</body>
</html>