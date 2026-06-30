<?php 
session_start();
include("connect.php");
include("session_check.php");

function logActivity($conn, $email, $action){
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_email, action_performed) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $action);
    $stmt->execute();
}

if (!isset($_SESSION['email'])) { 
    header("Location: index.php"); 
    exit(); 
}

$user_email = $_SESSION['email'];
$user_role = $_SESSION['role'] ?? 'staff';

if ($user_role !== 'admin' && $user_email !== 'user@gmail.com') {
    echo "<script>alert('Access Denied'); window.location.href='homepage.php';</script>";
    exit();
}

$check_col = $conn->query("SELECT * FROM users LIMIT 1");
$first_row = $check_col->fetch_assoc();
$idColumn = $first_row ? array_keys($first_row)[0] : "id";

if (isset($_GET['approve_req'])) {

    $req_id = intval($_GET['approve_req']);

    $stmt = $conn->prepare("SELECT * FROM edit_requests WHERE id=?");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();
    $req_query = $stmt->get_result();

    if ($req_query->num_rows > 0) {

        $req_data = $req_query->fetch_assoc();
        $requester = $req_data['requested_by'];
        $client_id = $req_data['client_id'];
        $type = $req_data['request_type'];

        if ($type === 'delete') {

            $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->bind_param("i", $client_id);

            if ($stmt->execute()) {
                logActivity(
                $conn,
                $requester,
                "Deletion request approved by " . $user_email . " (Client ID: " . $client_id . ")"
                );

                $conn->query("DELETE FROM edit_requests WHERE id = $req_id");
                header("Location: admin_manage.php?status=record_deleted");
                exit();
            }
        } 
        else {

            $proposed = json_decode($req_data['proposed_data'], true);

            if (!$proposed) {
                die("Invalid JSON data.");
            }

            $user_q = $conn->prepare("SELECT assigned_type FROM users WHERE email=?");
            $user_q->bind_param("s", $requester);
            $user_q->execute();
            $user_res = $user_q->get_result()->fetch_assoc();

            $user_type = $user_res['assigned_type'] ?? '';

            $client_q = $conn->prepare("SELECT assistance_type FROM clients WHERE id=?");
            $client_q->bind_param("i", $client_id);
            $client_q->execute();
            $client_res = $client_q->get_result()->fetch_assoc();

            $client_type = $client_res['assistance_type'] ?? '';

            if (!empty($user_type) && $user_type !== $client_type) {
                die("❌ Unauthorized edit");
            }

            $fields = [];
            $values = [];
            $types  = "";

            foreach ($proposed as $column => $value) {

                if ($column === 'details') {
                    $fields[] = "details = ?";
                    $values[] = json_encode($value);
                    $types   .= "s";
                } else {
                    $fields[] = "$column = ?";
                    $values[] = $value;
                    $types   .= "s";
                }
            }

            $values[] = $client_id;
            $types   .= "i";

            $sql = "UPDATE clients SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);

            $stmt->bind_param($types, ...$values);

            if ($stmt->execute()) {
                logActivity(
                $conn,
                $requester,
                "Edit request approved by " . $user_email . " (Client ID: " . $client_id . ")"
                );

                $conn->query("DELETE FROM edit_requests WHERE id = $req_id");
                header("Location: admin_manage.php?status=Edit Approved");
                exit();
            }
        }
    }
}

if (isset($_GET['reject_req'])) {
    $req_id = intval($_GET['reject_req']);

    $stmt = $conn->prepare("SELECT requested_by FROM edit_requests WHERE id=?");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    $requester = $res['requested_by'] ?? $user_email;

    logActivity($conn, $requester, "Request ID " . $req_id . " was rejected");

    $conn->query("DELETE FROM edit_requests WHERE id = $req_id");
    header("Location: admin_manage.php?status=request_rejected");
    exit();
}

if (isset($_GET['approve_id'])) {
    $id = $_GET['approve_id'];

    $conn->query("UPDATE users SET is_verified = 1 WHERE $idColumn = '$id'");
    logActivity($conn, $user_email, "Approved user ID " . $id);

    header("Location: admin_manage.php?status=approved");
    exit();
}

if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    $conn->query("DELETE FROM users WHERE $idColumn = '$id'");
    logActivity($conn, $user_email, "Deleted user ID " . $id);

    header("Location: admin_manage.php?status=user_deleted");
    exit();
}

if (isset($_GET['revoke_id'])) {
    $id = $_GET['revoke_id'];

    $conn->query("UPDATE users SET is_verified = 0 WHERE $idColumn = '$id'");
    logActivity($conn, $user_email, "Revoked access for user ID " . $id);

    header("Location: admin_manage.php?status=revoked");
    exit();
}

if (isset($_GET['change_role']) && isset($_GET['to'])) {
    $id = $_GET['change_role'];
    $new_role = $_GET['to'];

    $conn->query("UPDATE users SET role = '$new_role' WHERE $idColumn = '$id'");
    logActivity($conn, $user_email, "Changed role of user ID " . $id . " to " . $new_role);

    header("Location: admin_manage.php?status=role_updated");
    exit();
}

$pending_users = $conn->query("SELECT * FROM users WHERE is_verified = 0");
$active_users = $conn->query("SELECT * FROM users WHERE is_verified = 1");

$requests = $conn->query("
    SELECT er.*, 
           c.name as client_name
    FROM edit_requests er 
    JOIN clients c ON er.client_id = c.id 
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Office Management</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 30px; margin: 0; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { color: #0038a8; border-bottom: 2px solid #0038a8; padding-bottom: 10px; margin-top: 0; }
        h3 { margin-top: 30px; font-size: 16px; color: #444; text-transform: uppercase; letter-spacing: 1px; }
        .alert { padding: 10px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px; font-weight: bold; border: 1px solid #c3e6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { background: #f8f9fa; color: #666; text-transform: uppercase; font-size: 11px; }
        .btn { padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; cursor: pointer; border:none; }
        .btn-approve { background: #28a745; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-role { background: #0038a8; color: white; }
        .btn-revoke { background: #6c757d; color: white; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #0038a8; text-decoration: none; font-weight: bold; }
        .diff-text { font-size: 11px; color: #666; display: block; }
        .new-val { color: #28a745; font-weight: bold; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-edit { background: #eef2ff; color: #0038a8; }
        .badge-delete { background: #fff1f0; color: #cf1322; }
        .reason-box { background: #f8f9fa; padding: 8px; border-radius: 4px; border-left: 3px solid #dc3545; font-style: italic; color: #555; }
    </style>
</head>
<body>

<div class="container">
    <a href="homepage.php" class="back-link">← Back to Dashboard</a>
    <h2>System Administration</h2>

    <?php if(isset($_GET['status'])): ?>
        <div class="alert">Action successful: <?php echo htmlspecialchars($_GET['status']); ?></div>
    <?php endif; ?>

    <h3 style="color: #c63927;">🔔 Pending Transaction Requests</h3>

        <?php if($requests->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Requested By</th>
                    <th>Client Name</th>
                    <th>Details / Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

            <?php while($row = $requests->fetch_assoc()): 
                $proposed = json_decode($row['proposed_data'], true);
                $isDelete = ($row['request_type'] === 'delete');
            ?>
                <tr>
                    <td>
                        <span class="badge <?php echo $isDelete ? 'badge-delete' : 'badge-edit'; ?>">
                            <?php echo strtoupper($row['request_type']); ?>
                        </span>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row['requested_by']); ?><br>
                        <small><?php echo $row['created_at']; ?></small>
                    </td>

                    <td>
                        <strong><?php echo htmlspecialchars($row['client_name']); ?></strong>
                    </td>

                    <td>

<?php if(!$isDelete): ?>

    <?php
    $proposed = json_decode($row['proposed_data'], true);
    ?>

    <!-- PERSONAL + TRANSACTION -->
    <?php foreach($proposed as $key => $value): ?>

        <?php if($key === 'details') continue; ?>

        <span class="diff-text">
            <?php echo ucwords(str_replace("_"," ",$key)); ?>:
            <span class="new-val"><?php echo htmlspecialchars($value); ?></span>
        </span>

    <?php endforeach; ?>

    <!-- DYNAMIC DETAILS -->
    <?php if(!empty($proposed['details'])): ?>
        <div style="margin-top:8px; background:#f1f5f9; padding:8px; border-radius:6px;">
            <strong>Additional Details:</strong>

            <?php foreach($proposed['details'] as $k => $v): ?>
                <span class="diff-text">
                    <?php echo ucwords(str_replace("_"," ",$k)); ?>:
                    <span class="new-val"><?php echo htmlspecialchars(is_array($v) ? implode(', ', $v) : $v); ?></span>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php else: ?>

    <div class="reason-box">
        <?php echo htmlspecialchars($proposed['reason'] ?? 'No reason provided'); ?>
    </div>

<?php endif; ?>
                    </td>

                    <td>
                        <a href="admin_manage.php?approve_req=<?php echo $row['id']; ?>" 
                        class="btn btn-approve"
                        onclick="return confirm('<?php echo $isDelete ? 'WARNING: This will PERMANENTLY DELETE the client record. Continue?' : 'Approve these changes?'; ?>')">
                        APPROVE
                        </a>

                        <a href="admin_manage.php?reject_req=<?php echo $row['id']; ?>" 
                        class="btn btn-delete">
                        REJECT
                        </a>
                    </td>
                </tr>

            <?php endwhile; ?>

            </tbody>
        </table>

        <?php else: ?>

        <p style="font-size: 13px; color: #888; padding: 15px; border: 1px dashed #ccc; border-radius: 8px; text-align: center;">
            No pending requests at this time.
        </p>

        <?php endif; ?>

    <hr style="margin: 40px 0; border: 0; border-top: 1px solid #eee;">

    <h3>⏳ New User Signup Requests</h3>
    <table>
        <thead>
            <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Assigned Type</th>
            <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $pending_users->fetch_assoc()): $uid = $row[$idColumn]; ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['firstName'] . " " . $row['lastName']); ?></td>

                    <td><?php echo htmlspecialchars($row['email']); ?></td>

                    <td>
                        <strong><?php echo strtoupper($row['role']); ?></strong>
                    </td>

                    <td>
                        <?php 
                            echo !empty($row['assigned_type']) 
                                ? htmlspecialchars($row['assigned_type']) 
                                : "<span style='color:#888;'>N/A</span>";
                        ?>
                    </td>

                    <td>
                        <a href="admin_manage.php?approve_id=<?php echo $uid; ?>" class="btn btn-approve">APPROVE USER</a>
                        <a href="admin_manage.php?delete_id=<?php echo $uid; ?>" class="btn btn-delete" onclick="return confirm('Delete permanently?')">DELETE</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <br>

    <h3>✅ Authorized Staffs</h3>
    <table>
        <thead>
            <tr>
    <th>Name</th>
    <th>Email</th>
    <th>Role</th>
    <th>Assigned Type</th>
    <th>Manage</th>
    <th>Access</th>
</tr>
        </thead>
        <tbody>
            <?php while($row = $active_users->fetch_assoc()): 
                $uid = $row[$idColumn];
                $current_r = strtolower($row['role']);
                $target_r = ($current_r === 'admin') ? 'staff' : 'admin';
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['firstName'] . " " . $row['lastName']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><strong><?php echo strtoupper($current_r); ?></strong></td>

                <td>
                    <?php 
                        echo !empty($row['assigned_type']) 
                            ? htmlspecialchars($row['assigned_type']) 
                            : "<span style='color:#888;'>N/A</span>";
                    ?>
                </td>
                    <td>
                        <?php if($row['email'] !== $user_email): ?>
                            <a href="admin_manage.php?change_role=<?php echo $uid; ?>&to=<?php echo $target_r; ?>" class="btn btn-role">
                                MAKE <?php echo strtoupper($target_r); ?>
                            </a>
                        <?php else: ?>
                            <span style="color:#888; font-style:italic;">You</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($row['email'] !== $user_email): ?>
                            <a href="admin_manage.php?revoke_id=<?php echo $uid; ?>" class="btn btn-revoke" onclick="return confirm('Revoke access?')">REVOKE</a>
                        <?php else: ?>
                            <span style="color:#888; font-style:italic;">Protected</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>