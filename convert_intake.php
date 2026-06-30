<?php
session_start();
include("connect.php");
include("session_check.php");

if(!isset($_GET['intake_id']) || empty($_GET['intake_id'])){
    die("No intake ID provided.");
}

$intake_id = $_GET['intake_id'];

$stmt = $conn->prepare("SELECT data FROM intake_records WHERE id=?");
$stmt->bind_param("i", $intake_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    die("Intake record not found.");
}

$row = $result->fetch_assoc();
$data = json_decode($row['data'], true);

if(!is_array($data)){
    die("Invalid intake data.");
}

$name = $data['name'] ?? '';
$age = isset($data['age']) ? (int)$data['age'] : 0;
$civil_status = $data['civil_status'] ?? '';
$address = $data['address'] ?? '';

$phone = $data['phone'] ?? '';
$birthday = $data['birthday'] ?? '';
$occupation = $data['occupation'] ?? '';
$education = $data['education'] ?? '';

$barangay = $data['barangay'] ?? '';

$gender = $data['gender'] ?? '';

$date_received = !empty($data['date']) 
    ? date('Y-m-d', strtotime($data['date'])) 
    : date('Y-m-d');

$assistance_type = "AICS";

$aics_subtype = $data['assistance'] ?? '';

$email = $data['email'] ?? '';
$income = $data['income'] ?? '';
$assist_given = '';
$social_worker = '';
$amount = 0;

$details_array = $data;

$details_array['aics_subtype'] = $aics_subtype;

$details = json_encode($details_array);

$stmt = $conn->prepare("INSERT INTO clients 
(name, gender, age, civil_status, address, barangay, assistance_type, phone, birthday, income, occupation, education, date_received, email, assistance_given, amount, social_worker, details)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

$stmt->bind_param(
    "ssissssssssssssdss",
    $name,
    $gender,
    $age,
    $civil_status,
    $address,
    $barangay,
    $assistance_type,
    $phone,
    $birthday,
    $income,
    $occupation,
    $education,
    $date_received,
    $email,
    $assist_given,
    $amount,
    $social_worker,
    $details
);

if($stmt->execute()){

    $client_id = $conn->insert_id;

    $action = "CONVERT INTAKE → CLIENT";
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_email, action_performed) VALUES (?, ?)");
    $log_stmt->bind_param("ss", $_SESSION['email'], $action);
    $log_stmt->execute();

    header("Location: entry.php?name=" . urlencode($name));
    exit();

} else {
    echo "Error: " . $stmt->error;
}
?>