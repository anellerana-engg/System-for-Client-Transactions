<?php
session_start();
include("connect.php");
include("session_check.php");

if(!isset($_SESSION['email'])){
    header("Location: index.php");
    exit();
}

if(!isset($_GET['id'])){
    echo "No record selected.";
    exit();
}

$id = $_GET['id'];

$user_email = $_SESSION['email'];
$user_role = $_SESSION['role'] ?? 'staff';


$user_q = $conn->prepare("SELECT assigned_type FROM users WHERE email=?");
$user_q->bind_param("s", $user_email);
$user_q->execute();
$user_res = $user_q->get_result()->fetch_assoc();
$user_type = $user_res['assigned_type'] ?? '';



$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    echo "Record not found.";
    exit();
}

$row = $result->fetch_assoc();
$details = json_decode($row['details'], true);


$client_type = $row['assistance_type'] ?? '';
$can_edit = ($user_role === 'admin' || $user_type === $client_type);



if(isset($_POST['request_edit'])){


    if($user_role !== 'admin' && $user_type !== $row['assistance_type']){
    echo "<script>
        alert('You can only edit your assigned assistance type ($user_type)');
        window.location.href='view.php?id=".$row['id']."';
    </script>";
    exit();
}

    $record_id = $_POST['record_id'];
    $details_data = $_POST['details'] ?? [];

    $proposed_data = [
        "name" => $_POST['name'] ?? '',
        "gender" => $_POST['gender'] ?? '',
        "age" => $_POST['age'] ?? '',
        "civil_status" => $_POST['civil_status'] ?? '',
        "address" => $_POST['address'] ?? '',
        "barangay" => $_POST['barangay'] ?? '',
        "phone" => $_POST['phone'] ?? '',
        "birthday" => $_POST['birthday'] ?? '',
        "income" => $_POST['income'] ?? '',
        "occupation" => $_POST['occupation'] ?? '',
        "education" => $_POST['education'] ?? '',
        "email" => $_POST['email'] ?? '',
        "assistance_type" => $_POST['assistance_type'] ?? '',
        "date_received" => $_POST['date_received'] ?? '',
        "social_worker" => $_POST['social_worker'] ?? '',
        "details" => $details_data
    ];

    $json_data = json_encode($proposed_data);

    $stmt = $conn->prepare("
        INSERT INTO edit_requests (client_id, request_type, proposed_data, requested_by)
        VALUES (?, 'edit', ?, ?)
    ");

    $stmt->bind_param("iss", $record_id, $json_data, $user_email);
    $stmt->execute();

    echo "<script>alert('Edit request sent for approval'); window.location.href='view.php?id=$record_id';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Record</title>

<style>
    body {
        font-family: Arial, sans-serif;
        background: #f8fafc;
        margin: 0;
        padding: 30px;
    }

    .card {
        background: white;
        padding: 30px;
        border-radius: 12px;
        max-width: 850px;
        margin: auto;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    h2 {
        margin: 0;
        color: #0038a8;
        text-align: center;
    }

    .subtitle {
        text-align: center;
        font-size: 13px;
        margin-bottom: 10px;
    }

    hr {
        margin: 15px 0;
    }

    .section {
        margin-top: 20px;
    }

    .section h3 {
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
        color: #c63927;
    }

    p {
        margin: 6px 0;
        font-size: 14px;
        line-height: 1.5;
    }

    .label {
        font-weight: bold;
        color: #1e293b;
    }

    .btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    margin-bottom: 15px;
    text-decoration: none;

    color: white;
    background: linear-gradient(135deg, #0038a8, #2563eb);

    padding: 10px 16px;
    border-radius: 8px;

    font-size: 13px;
    font-weight: 600;

    border: none;
    cursor: pointer;

    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    transition: all 0.25s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(0,0,0,0.12);
    background: linear-gradient(135deg, #002a80, #1d4ed8);
}

.btn:active {
    transform: scale(0.97);
}

    .btn-green {
        background: #0F9D58;
    }


.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
}


.modal-content {
    background: white;
    margin: 3% auto;
    padding: 30px;
    border-radius: 16px;
    width: 650px;
    max-height: 90vh;
    overflow-y: auto;

    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    animation: fadeInScale 0.25s ease;
}


@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}


.modal-content h3 {
    margin-top: 0;
    color: #0038a8;
    text-align: center;
}

.modal-content h4 {
    margin-top: 25px;
    margin-bottom: 10px;
    color: #c63927;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}


.modal-content label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-top: 12px;
}


.modal-content input,
.modal-content select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    background: #f8fafc;

    box-sizing: border-box;
}


.modal-content input:last-of-type,
.modal-content select:last-of-type {
    margin-bottom: 10px;
    outline: none;
    border-color: #2563eb;
    background: white;
    box-shadow: 0 0 0 2px rgba(37,99,235,0.15);
}


.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}


.btn-save {
    flex: 1;
    background: linear-gradient(135deg, #0F9D58, #16a34a);
}

.btn-save:hover {
    background: linear-gradient(135deg, #0c7c45, #15803d);
}


.btn-cancel {
    flex: 1;
    background: #64748b;
}

.btn-cancel:hover {
    background: #475569;
}

    @media print {
        body {
            background: white;
            padding: 0;
        }

        .btn {
            display: none;
        }

        .card {
            box-shadow: none;
            border: none;
            margin: 0;
            width: 100%;
        }
    }
</style>

<script>
function downloadPDF() {
    window.print();
}
</script>

</head>
<body>

<div class="card">


<a href="service.php" class="btn">← Back</a>
<button onclick="downloadPDF()" class="btn btn-green">Download PDF</button>

<button onclick="openEditModal()" class="btn" style="background:#f59e0b;">
    Edit Record
</button>

<div id="editModal" class="modal">
    <div class="modal-content" style="width:650px; max-height:85vh; overflow-y:auto;">

        <h3>Edit Full Record (For Approval)</h3>

        <form method="POST">

<input type="hidden" name="record_id" value="<?= $row['id'] ?>">


<h4>Personal Information</h4>

<label>Name</label>
<input type="text" name="name" pattern="^[A-Za-z\s]+,\s[A-Za-z\s]+(\s[A-Za-z]{1,2}\.)?$" value="<?= htmlspecialchars($row['name']) ?>">

<label>Gender</label>
<select name="gender" required>

    <option value="" disabled <?= empty($row['gender']) ? 'selected' : '' ?>>
        Select Gender
    </option>

    <option value="Male" <?= ($row['gender'] == "Male") ? 'selected' : '' ?>>
        Male
    </option>

    <option value="Female" <?= ($row['gender'] == "Female") ? 'selected' : '' ?>>
        Female
    </option>

</select>

<label>Age</label>
<input type="number" name="age" min="0" max="150" value="<?= htmlspecialchars($row['age']) ?>">


<label>Civil Status</label>
<select name="civil_status">

<option value="" disabled <?= empty($row['civil_status']) ? 'selected' : '' ?>>
    Select Civil Status
</option>

<?php 
$statuses = ["Single", "Married", "Separated", "Live-in", "Widow"];

foreach($statuses as $s){
    $sel = ($row['civil_status'] == $s) ? "selected" : "";
    echo "<option value='$s' $sel>$s</option>";
}
?>

</select>

<label>Address</label>
<input type="text" name="address" value="<?= htmlspecialchars($row['address']) ?>">


<label>Barangay</label>
<select name="barangay">
    <option value="" disabled>Select Barangay</option>
    <?php 
    $brgys = [
        "Brgy. A","Brgy. B","Brgy. C",
        "Brgy. D","Brgy. E","Brgy. F",
        "Brgy. G","Brgy. H",
        "Brgy. I","Brgy. J"
    ];
    foreach($brgys as $b){
        $sel = ($row['barangay'] == $b) ? "selected" : "";
        echo "<option $sel>$b</option>";
    }
    ?>
</select>

<label>Phone</label>
<input type="text" name="phone" pattern="^09\d{9}$" placeholder="09XXXXXXXXX" maxlength="11" value="<?= htmlspecialchars($row['phone']) ?>">

<label>Birthday</label>
<input type="text" name="birthday" class="date-format" placeholder="Birthday (YYYY-MM-DD)" maxlength="10" pattern="\d{4}-\d{2}-\d{2}">

<label>Income</label>
<input type="number" name="income" placeholder="Monthly Income" value="<?= htmlspecialchars($row['income']) ?>">

<label>Occupation</label>
<input type="text" name="occupation" value="<?= htmlspecialchars($row['occupation']) ?>">


<label>Education</label>
<select name="education">

<option value="" disabled <?= empty($row['education']) ? 'selected' : '' ?>>
    Select Education
</option>

<?php 
$education = [
    "Doctorate",
    "Professional Degree",
    "Master's Degree",
    "Bachelor's Degree",
    "College Level",
    "Vocational Training",
    "High School Graduate",
    "Grade School Graduate",
    "Grade School Level"
];

foreach($education as $e){
    $sel = ($row['education'] == $e) ? "selected" : "";
    echo "<option $sel>$e</option>";
}
?>

</select>

<label>Email</label>
<input type="text" name="email" value="<?= htmlspecialchars($row['email']) ?>">

<hr>


<h4>Transaction Details</h4>

<label>Assistance Type</label>
<select name="assistance_type" id="assistance_type" required>

    <option value="" disabled <?= empty($row['assistance_type']) ? 'selected' : '' ?>>
        Assistance Category
    </option>

    <?php
    $types = [
        "AICS","Balik Probinsya","Burial","Cash for Work","ESA",
        "Food and Non-Food Items","Indigency (Court)",
        "Indigent (PhilHealth)","Pag-Abot Program","PMC",
        "PWD","Referral (Medical)","Solo Parent","Women's Kalipi"
    ];

    foreach($types as $t){
        $sel = ($row['assistance_type'] == $t) ? "selected" : "";
        echo "<option value='$t' $sel>$t</option>";
    }
    ?>
</select>


<label>Date Received</label>
<input type="text" 
       name="date_received"
       class="date-format"
       placeholder="YYYY-MM-DD"
       maxlength="10"
       pattern="\d{4}-\d{2}-\d{2}"
       value="<?= htmlspecialchars($row['date_received']) ?>">

<label>Social Worker</label>
<input type="text" name="social_worker" value="<?= htmlspecialchars($row['social_worker']) ?>">

<hr>


<h4>Additional Details</h4>

<?php
if(!empty($details)){
    foreach($details as $key => $value){

        $label = ucwords(str_replace("_", " ", $key));
        $safe_value = htmlspecialchars($value);

        echo "<label>$label</label>";


        if($key === 'aics_subtype'){

            $options = [
                "Medical",
                "Special Case",
                "Transportation",
                "Social Case Study Report"
            ];

            echo "<select name='details[$key]'>";

            echo "<option value='' disabled ".(empty($safe_value) ? 'selected' : '').">
                    Select Subtype
                  </option>";

            foreach($options as $opt){
                $selected = ($safe_value == $opt) ? "selected" : "";
                echo "<option value='$opt' $selected>$opt</option>";
            }

            echo "</select>";
        }

        elseif(str_contains($key, 'aics_subject_bday')){

            echo "<input type='text' 
                        class='date-format' 
                        name='details[$key]' 
                        placeholder='YYYY-MM-DD'
                        maxlength='10'
                        pattern='\\d{4}-\\d{2}-\\d{2}'
                        value='$safe_value'>";
        }

        elseif(str_contains($key, 'time')){

            echo "<input type='text' 
                        class='time-format'
                        name='details[$key]' 
                        placeholder='HH:MM'
                        maxlength='5'
                        value='$safe_value'>";
        }

        elseif(str_contains($key, 'amount') || str_contains($key, 'payout') || str_contains($key, 'age')){

            echo "<input type='number' 
                        name='details[$key]' 
                        placeholder='Enter value'
                        value='$safe_value'>";
        }

        else{

            echo "<input type='text' 
                        name='details[$key]' 
                        value='$safe_value'>";
        }
    }
}
?>

<br>

<button type="submit" name="request_edit" class="btn btn-green">
    Save Changes
</button>

<button type="button" onclick="closeEditModal()" class="btn" style="background:#64748b;">
    Cancel
</button>

</form>
    </div>
</div>

<script>
function openEditModal() {
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

document.addEventListener("input", function(e){

    if(e.target.classList.contains("date-format")){
        let v = e.target.value.replace(/\D/g, '').slice(0,8);

        if (v.length >= 5) v = v.slice(0,4) + '-' + v.slice(4);
        if (v.length >= 8) v = v.slice(0,7) + '-' + v.slice(7);

        e.target.value = v;
    }

    if(e.target.classList.contains("time-format")){
        let v = e.target.value.replace(/\D/g, '').slice(0,4);

        if (v.length >= 3) v = v.slice(0,2) + ':' + v.slice(2);

        e.target.value = v;
    }
});
</script>

<h2>Office</h2>
<div class="subtitle">Client Assistance Record</div>
<hr>


<div class="section">
    <h3>Personal Information</h3>
    <p><span class="label">Name:</span> <?= htmlspecialchars($row['name']) ?></p>
    <p><span class="label">Gender:</span> <?= htmlspecialchars($row['gender']) ?></p>
    <p><span class="label">Age:</span> <?= htmlspecialchars($row['age']) ?></p>
    <p><span class="label">Civil Status:</span> <?= htmlspecialchars($row['civil_status']) ?></p>
    <p><span class="label">Address:</span> <?= htmlspecialchars($row['address']) ?></p>
    <p><span class="label">Barangay:</span> <?= htmlspecialchars($row['barangay']) ?></p>
    <p><span class="label">Phone:</span> <?= htmlspecialchars($row['phone']) ?></p>
    <p><span class="label">Birthday:</span> <?= htmlspecialchars($row['birthday']) ?></p>
    <p><span class="label">Income:</span> <?= htmlspecialchars($row['income']) ?></p>
    <p><span class="label">Occupation:</span> <?= htmlspecialchars($row['occupation']) ?></p>
    <p><span class="label">Education:</span> <?= htmlspecialchars($row['education']) ?></p>
    <p><span class="label">Email:</span> <?= htmlspecialchars($row['email']) ?></p>
</div>


<div class="section">
    <h3>Transaction Details</h3>
    <p><span class="label">Assistance Type:</span> <?= htmlspecialchars($row['assistance_type']) ?></p>
    <p><span class="label">Date Received:</span> <?= htmlspecialchars($row['date_received']) ?></p>
    <p><span class="label">Social Worker:</span> <?= htmlspecialchars($row['social_worker']) ?></p>
</div>


<div class="section">
    <h3>Additional Details</h3>

    <?php
    if(!empty($details)){

        foreach($details as $key => $value){
            $label = ucwords(str_replace("_", " ", $key));
            echo "<p><span class='label'>$label:</span> " . htmlspecialchars($value) . "</p>";
        }

    } else {

        echo "<p style='color:#64748b;'>No other details were recorded for this transaction.</p>";
    }
    ?>
</div>

</div>

</body>
</html>