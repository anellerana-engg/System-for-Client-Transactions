<?php
include 'connect.php';

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);

    $query = "SELECT * FROM users WHERE token='$token' LIMIT 1";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];

        $updateQuery = "UPDATE users SET is_verified = 1, token = '' WHERE email = '$email'";

        if ($conn->query($updateQuery)) {
            echo "
            <div style='text-align:center; margin-top:50px; font-family:Arial;'>
                <h2 style='color:green;'>Success!</h2>
                <p>The account for <b>$email</b> has been approved.</p>
                <p>The staff member can now log in to the system.</p>
                <br>
                <a href='index.php' style='padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>Go to Login</a>
            </div>";
        } else {
            echo "Error updating record: " . $conn->error;
        }
    } else {
        echo "
        <div style='text-align:center; margin-top:50px; font-family:Arial;'>
            <h2 style='color:red;'>Invalid or Expired Link</h2>
            <p>This approval link is no longer valid or the user was already approved.</p>
            <a href='index.php'>Return Home</a>
        </div>";
    }
} else {
    echo "No token provided.";
}
?>