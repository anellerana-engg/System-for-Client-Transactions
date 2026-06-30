<?php
session_start();
include("connect.php");
include("session_check.php");

if(!isset($_SESSION['email'])){
    header("Location: index.php");
    exit();
}

$user_email = $_SESSION['email'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'staff';

$prefill = [
    'name' => '', 'gender' => '', 'age' => '', 'civil_status' => '', 
    'address' => '', 'barangay' => '', 'phone' => '', 'birthday' => '', 'income' => '', 
    'occupation' => '', 'education' => '', 'email' => ''
];

if (isset($_GET['name']) && !empty($_GET['name'])) {
    $search_name = $_GET['name'];
    $stmt_fetch = $conn->prepare("SELECT * FROM clients WHERE name = ? ORDER BY date_received DESC LIMIT 1");
    $stmt_fetch->bind_param("s", $search_name);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();

    if ($row = $result->fetch_assoc()) {
        $prefill = $row;
    }
}

if(isset($_GET['intake_id']) && !empty($_GET['intake_id'])){

    $stmt = $conn->prepare("SELECT data FROM intake_records WHERE id=?");
    $stmt->bind_param("i", $_GET['intake_id']);
    $stmt->execute();

    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        $data = json_decode($row['data'], true);
        $value = array_merge($client ?? [], $data ?? []);

if(isset($value['assistance'])){
    if($value['assistance'] === 'Medical' || $value['assistance'] === 'Financial'){
        $value['assistance_type'] = 'AICS';
        $value['aics_subtype'] = 'Medical';
    }
}

        if(is_array($data)){
            $prefill['name'] = $data['name'] ?? $prefill['name'];
            $prefill['age'] = $data['age'] ?? $prefill['age'];
            $prefill['civil_status'] = $data['civil_status'] ?? $prefill['civil_status'];
            $prefill['address'] = $data['address'] ?? $prefill['address'];
            $prefill['phone'] = $data['phone'] ?? $prefill['phone'];
            $prefill['birthday'] = $data['birthday'] ?? $prefill['birthday'];
            $prefill['occupation'] = $data['occupation'] ?? $prefill['occupation'];
            $prefill['education'] = $data['education'] ?? $prefill['education'];

            $prefill['email'] = $data['email'] ?? '';
            $prefill['date_received'] = $data['date_received'] ?? '';
            $prefill['assistance_given'] = $data['assistance_given'] ?? '';
            $prefill['social_worker'] = $data['social_worker'] ?? '';
        }
    }
}

if(isset($_POST['submit'])){

    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $civil_status = $_POST['civil_status'];
    $address = $_POST['address'];
    $barangay = $_POST['barangay'];

    $assistance_type = $_POST['assistance_type'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $income = $_POST['income'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $education = $_POST['education'] ?? '';
    $date_received = $_POST['date_received'] ?? '';
    $email = $_POST['email'] ?? '';
    $assist_given = $_POST['assistance_given'] ?? '';
    $social_worker = $_POST['social_worker'] ?? '';

    $amount = !empty($_POST['amount']) ? $_POST['amount'] : 0;

    $excluded = [
        'name','gender','age','civil_status','address','barangay','assistance_type',
        'phone','birthday','income','occupation','education','date_received',
        'email','assistance_given','amount','social_worker','submit'
    ];

    $dynamic_data = [];

    foreach($_POST as $key => $value){
        if(!in_array($key, $excluded)){
            $dynamic_data[$key] = $value;
        }
    }

    $details = json_encode($dynamic_data);

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

        $action = "INPUT";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_email, action_performed) VALUES (?, ?)");
        $log_stmt->bind_param("ss", $_SESSION['email'], $action);
        $log_stmt->execute();

        echo "<script>alert('Saved successfully!'); window.location.href='service.php';</script>";

    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Name - Entry Page</title>
    <style>
        :root { 
            --blue: #0038a8; 
            --blue-light: #eef2ff;
            --red: #c63927; 
            --bg-gray: #f8fafc; 
            --card-border: #e2e8f0; 
            --text-main: #1e293b;
            --text-muted: #64748b;
            --admin-gold: #b8860b;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; margin: 0; background: var(--bg-gray); color: var(--text-main); padding-bottom: 80px; }
        
        .header {
            display: flex; align-items: center; justify-content: space-between;
            background: white; padding: 12px 40px; border-bottom: 1px solid var(--card-border);
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); position: sticky; top: 0; z-index: 1001;
        }
       
        .header img { height: 50px; margin-right: 15px; }
        .header-left { display: flex; align-items: center; }
        .header-text h1 { margin: 0; font-size: 18px; color: var(--blue); }
        .logout-btn { background: var(--red); color: white; text-decoration: none; padding: 8px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; }
        .container {
    display: grid;
    grid-template-columns: 220px 1fr;
    height: calc(100vh - 75px);
    padding: 10px;
}

        .sidebar {
    background: white;
    padding: 18px 12px;
    border-right: 1px solid var(--card-border);

    overflow: hidden;
    height: calc(100vh - 95px);
}
        
        .sidebar h3 { color: var(--text-main); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; border-bottom: 2px solid var(--blue-light); padding-bottom: 10px; }

        .filter-group { margin-bottom: 20px; }
        .filter-group label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; }

        select, input[type="text"], input[type="number"], input[type="date"], input[type="email"] { 
            width: 100%; padding: 12px; border: 1.5px solid var(--card-border); 
            border-radius: 8px; background-color: #fcfcfd; font-size: 14px; box-sizing: border-box;
            margin-bottom: 15px;
        }

        button {
        background: var(--blue);
        color: white;
        padding: 12px;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        cursor: pointer;
        width: 100%;
        margin-bottom: 10px;
        }

        .main {
    padding: 15px;
    max-width: 800px;
    margin: 0 auto;
    width: 100%;

    overflow-y: hidden;
    padding-bottom: 90px;
}

        .form-card { 
            background: white; border: 1px solid var(--card-border); border-radius: 16px; 
            padding: 40px; box-shadow: var(--shadow); max-width: 800px; width: 130%; max-height: calc(100vh - 120px);
    overflow-y: auto; margin-left: -100px; 
        }

        .form-title { font-size: 20px; font-weight: 800; color: var(--blue); margin-bottom: 25px; text-align: center; }

        .section-label { display: block; font-size: 11px; font-weight: 700; color: var(--red); text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px; }

        .btn-submit { width: 100%; padding: 8px; background: var(--blue); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 16px; transition: 0.3s; }
        .btn-submit:hover { background: #002a80; }

        .bottom-nav { display: flex; background: #1e293b; position: fixed; bottom: 0; left: 0; width: 100%; z-index: 1000; }
        .nav-item { color: #94a3b8; text-decoration: none; padding: 10px; flex-grow: 1; text-align: center; font-size: 13px; font-weight: 600; }
        .nav-item.active { color: white; background: #334155; border-top: 4px solid var(--red); }
        
        .prefill-notice { background: #fff9eb; border: 1px solid var(--admin-gold); padding: 12px; border-radius: 8px; font-size: 12px; margin-bottom: 20px; color: #856404; }
    
        .header {
    padding: 10px 24px;
}

.header img {
    height: 45px;
}

.form-card {
    transform: scale(0.8);
    transform-origin: top center;
}
html, body {
    height: 100%;
    overflow: hidden;
}
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <img src="logo.png" alt="Logo">
        <div class="header-text">
            <h1>Company Name| <span style="font-weight:400; color:var(--text-muted)">Entry Module</span></h1>
        </div>
    </div>
    <a href="logout.php" class="logout-btn">LOGOUT</a>
</div>

<div class="container">
    <div class="sidebar">
        <h3>System User</h3>
        <div class="filter-group">
            <label>Current Account</label>
            <div style="font-size: 13px; font-weight: 600;"><?php echo htmlspecialchars($user_email); ?></div>
        </div>
        <div class="filter-group">
            <label>Access Level</label>
            <div style="font-size: 12px; color: var(--text-muted);"><?php echo strtoupper($user_role); ?></div>
        </div>

        <?php if(!empty($prefill['name'])): ?>
        <div class="prefill-notice">
            <strong>Returning Client:</strong><br>
            Data for <?php echo htmlspecialchars($prefill['name']); ?> has been auto-populated.
        </div>
        <?php endif; ?>

<div><button class="btn-submit" onclick="window.location.href='form_page.php'">
    OPEN FORM PAGE
</button></div>

        <button class="btn-submit" style="background: var(--red);" onclick="window.location.href='entry.php'">CLEAR FORM</button>
    </div>

    <div class="main">
        <div class="form-card">
            <div class="form-title">Client Assessment Form</div>
            <form method="POST">
                
                <span class="section-label">Personal Information</span>
                <input type="text" name="name" pattern="^[A-Za-z\s]+,\s[A-Za-z\s]+(\s[A-Za-z]{1,2}\.)?$" placeholder="Dela Cruz, Juan M." required value="<?php echo htmlspecialchars($prefill['name']); ?>" required>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <select name="gender" required>
                        <option value="" disabled <?php echo empty($prefill['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                        <option <?php if($prefill['gender'] == "Male") echo "selected"; ?>>Male</option>
                        <option <?php if($prefill['gender'] == "Female") echo "selected"; ?>>Female</option>
                    </select> 
                    <input type="number" name="age" min="0" max="150" placeholder="Age" required value="<?php echo htmlspecialchars($prefill['age']); ?>" required>
                </div>

                <select name="civil_status">
                    <option value="" disabled <?php echo empty($value['civil_status']) ? 'selected' : ''; ?>>Select Civil Status</option>
                    <?php 
                    $statuses = ["Single", "Married", "Separated", "Widow/er", "Annulled", "Live-in"];
                    foreach($statuses as $s) {
                        $sel = ($value['civil_status'] == $s) ? "selected" : "";
                        echo "<option $sel>$s</option>";
                    }
                    ?>
                </select>
                <input type="text" name="address" required placeholder="Address (Building/Unit, Street Name, Subdivision)" value="<?php echo htmlspecialchars($prefill['address']); ?>" required>
                
                <select name="barangay" required>
    <option value="" disabled <?php if(empty($value['barangay'])) echo 'selected'; ?>>
        Select Barangay
    </option>

    <?php 
    $brgys = [
        "Brgy. A",
        "Brgy. B",
        "Brgy. C",
        "Brgy. D",
        "Brgy. E",
        "Brgy. F",
        "Brgy. G",
        "Brgy. H",
        "Brgy. I",
        "Brgy. J"
    ];

    foreach($brgys as $b) {
        $selected = ($value['barangay'] === $b) ? 'selected' : '';
        echo "<option value=\"$b\" $selected>$b</option>";
    }
    ?>
</select>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <input type="text" name="phone" pattern="^09\d{9}$" placeholder="09XXXXXXXXX" maxlength="11" required value="<?php echo htmlspecialchars($prefill['phone']); ?>">
                    <input type="text" name="birthday"
class="date-format"
placeholder="Birthday (YYYY-MM-DD)"
maxlength="10"
pattern="\d{4}-\d{2}-\d{2}"
required
value="<?php echo htmlspecialchars($prefill['birthday']); ?>"> </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <input type="number" name="income" placeholder="Monthly Income" value="<?php echo htmlspecialchars($prefill['income']); ?>">
                    <input type="text" name="occupation" placeholder="Occupation" value="<?php echo htmlspecialchars($prefill['occupation']); ?>">
                </div>

                <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($prefill['email'] ?? ''); ?>">

                <select name="education">
                <option value="" disabled <?php echo empty($value['education']) ? 'selected' : ''; ?>>Select Education</option>
                <?php 
                $ed = ["Doctorate", "Professional Degree", "Master's Degree", "Bachelor's Degree", "College Level", "Vocational Training", "High School Graduate", "High School Level", "Grade School Graduate","Grade School Level", "No Educational Background"];
                foreach($ed as $e) {
                    $selected = ($value['education'] === $e) ? 'selected' : '';
                    echo "<option $selected>$e</option>";
                }
                ?>
                </select>

               <span class="section-label">Transaction Details</span>
                <select name="assistance_type" id="assistance_type" required>
                <option value="" disabled selected>Assistance Category</option>
                <option value="AICS">AICS</option>
                <option>Balik Probinsya</option>
                <option>Burial</option>
                <option>Cash for Work</option>
                <option>ESA</option>
                <option>Food and Non-Food Items</option>
                <option>Indigency (Court)</option>
                <option>Indigent (PhilHealth)</option>
                <option>Pag-Abot Program</option>
                <option>PMC</option>
                <option>PWD</option>
                <option>Referral (Medical)</option>
                <option>Solo Parent</option>
                <option>Women's Kalipi</option>
                </select>

                <input type="text" name="date_received" class="date-format" placeholder="Date Received (YYYY-MM-DD)" maxlength="10" pattern="\d{4}-\d{2}-\d{2}">

                <!-- AICS SUBCATEGORY -->
                <select name="aics_subtype" id="aics_type" style="display:none;">
                <option value="" disabled selected>Select AICS Type</option>
                <option value="Medical">Medical</option>
                <option value="Special Case">Special Case</option>
                <option value="Transportation">Transportation</option>
                <option value="Social Case Study Report">Social Case Study Report</option>
                </select>

                <input type="text" name="social_worker" pattern="^[A-Za-z\s]+$" placeholder="Assigned Social Worker" required>
 
                <!-- DYNAMIC FIELDS -->
                <div id="dynamic-fields"></div>

                <button type="submit" name="submit" class="btn-submit">SAVE TRANSACTION</button>
            </div>
            <button onclick="window.location.href='settings.php'">Settings</button>
        </div>

<script>
const assistance = document.getElementById("assistance_type");
const aicsDropdown = document.getElementById("aics_type");
const dynamicFields = document.getElementById("dynamic-fields");

assistance.addEventListener("change", function () {
    let value = this.value;

    if (value === "AICS") {
        aicsDropdown.style.display = "block";
    } else {
        aicsDropdown.style.display = "none";
    }

    dynamicFields.innerHTML = "";

    if (value === "AICS") {
        dynamicFields.innerHTML = `
        <span class="section-label">Subject</span>
        <input type="text" name="aics_subject_name" placeholder=" Subject's Name" required>
        <input type="text" name="aics_subject_bday" class="date-format" placeholder="Subject's Birthday (YYYY-MM-DD)" required>
        <select name="gender" required>
                        <option value="" disabled <?php echo empty($prefill['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                        <option <?php if($prefill['gender'] == "Male") echo "selected"; ?>>Male</option>
                        <option <?php if($prefill['gender'] == "Female") echo "selected"; ?>>Female</option>
                    </select> 
        <input type="number" name="aics_subject_age" placeholder="Age" required>
        <input type="text" name="aics_subject_class" placeholder="Classification" required>
        <input type="text" name="aics_diagnosed" placeholder="Diagnosed" required>

        <span class="section-label">Other</span>
        <input type="number" name="aics_amount" step="1" min="0" placeholder="Amount">
        <input type="text" name="aics_time" class="time-format" placeholder="Time (HH:MM)" required>
        <input type="text" name="aics_payout" class="date-format" placeholder="Payout Date (YYYY-MM-DD)">
        <input type="text" name="aics_remark" placeholder="Remark">
        `;
    }

    else if (value === "Burial") {
        dynamicFields.innerHTML = `
        <span class="section-label">Deceased</span>
        <input type="text" name="deceased_name" placeholder="Name" required>
        <select name="gender" required>
                        <option value="" disabled <?php echo empty($prefill['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                        <option <?php if($prefill['gender'] == "Male") echo "selected"; ?>>Male</option>
                        <option <?php if($prefill['gender'] == "Female") echo "selected"; ?>>Female</option>
                    </select> 
        <input type="number" name="deceased_age" placeholder="Age" required>
        <input type="text" name="cause_death" placeholder="Cause of Death" required>

        <span class="section-label">Other</span>
        <input type="number" name="burial_amount" placeholder="Amount">
        <input type="text" name="dswd_fp" placeholder="DSWD FP">
        `;
    }

    else if (value === "Cash for Work") {
        dynamicFields.innerHTML = `
        <span class="section-label">Classification</span>
        <label><input type="checkbox" name="cfw_class[]" value="Listahan Poor"> Listahan Poor</label><br>
        <label><input type="checkbox" name="cfw_class[]" value="Non-Listahan"> Non-Listahan</label><br>
        <label><input type="checkbox" name="cfw_class[]" value="4Ps"> 4Ps</label><br>
        <label><input type="checkbox" name="cfw_class[]" value="Indigeneous People"> Indigeneous People</label><br>
        <label><input type="checkbox" name="cfw_class[]" value="Womens"> Womens</label><br>
        <label><input type="checkbox" name="cfw_class[]" value="Fisherfolk"> Fisherfolk</label><br>
        <label><input type="checkbox" name="cfw_class[]" value="PWD"> PWD</label><br>
        <label><input type="checkbox" name="cfw_class[]" value="Senior Citizen"> Senior Citizen</label><br>
        <label><input type="checkbox" name="cfw_class[]" value="Decommisioned Combatant/Former Rebel"> Decommisioned Combatant/Former Rebel</label><br>
        <label><input type="checkbox" name="cfw_class[]" value="Out of School Youth"> Out of School Youth</label><br>
        <label><input type="checkbox" name="cfw_class[]" value="YAKAP Bayan/Persons Who Used Drugs"> YAKAP Bayan/Persons Who Used Drugs</label><br>
        <br>

        <span class="section-label">Household Member</span>
        <input type="text" name="cfw_member_name" placeholder="Household Member's Name" required>
        <input type="text" name="cfw_relationship" placeholder="Relationship to Client" required>
        <input type="text" name="cfw_engagement" placeholder="Engagement in Current Association from other services/programs">
        `;
    }

    else if (value === "Solo Parent") {
        dynamicFields.innerHTML = `
        <span class="section-label">Application Type</span>
        <select id="solo_type" name="solo_type">
            <option value="">Select Type</option>
            <option value="new">New Application</option>
            <option value="renewal">Renewal Application</option>
        </select>

        <div id="solo_fields"></div>
        `;

        document.getElementById("solo_type").addEventListener("change", function(){
            let sf = document.getElementById("solo_fields");

            if(this.value === "new"){
                sf.innerHTML = `
                    <input type="text" name="solo_id" placeholder="ID No" required>
                    <input type="text" name="solo_date_applied" class="date-format" placeholder="Date Applied (YYYY-MM-DD)" required>
                    <input type="number" name="solo_dependents" placeholder=" Number of Dependents" required>
                    <input type="text" name="solo_classification" placeholder="Classification" required>
                    <input type="text" name="solo_category" placeholder="Category" required>
                    <input type="text" name="solo_remark" placeholder="Remark">
                `;
            }
            else if(this.value === "renewal"){
                sf.innerHTML = `
                    <input type="text" name="solo_id" placeholder="ID No" required>
                    <input type="text" name="solo_date_applied" class="date-format" placeholder="Date Applied (YYYY-MM-DD)" required>
                    <input type="text" name="solo_expired" class="date-format" placeholder="Date Expired (YYYY-MM-DD)" required>
                    <input type="number" name="solo_dependents" placeholder=" Number of Dependents" required>
                    <input type="text" name="solo_classification" placeholder="Classification" required>
                    <input type="text" name="solo_category" placeholder="Category" required>
                    <input type="text" name="solo_remark" placeholder="Remark">
                `;
            }
        });
    }
});

    aicsDropdown.addEventListener("change", function () {
    let value = this.value;

    dynamicFields.innerHTML = "";

    if (value === "Social Case Study Report") {
        dynamicFields.innerHTML = `
        <span class="section-label">Social Case Study</span>
        <input type="text" name="scs_subject_name" placeholder="Name" required>
        <input type="number" name="scs_subject_age" placeholder="Age" required>
        <input type="text" name="scs_relation" placeholder="Relation" required>
        <input type="text" name="scs_problem" placeholder="Problem Presented" required>
        <input type="text" name="scs_need" placeholder="Nature of Need" required>
        `;
    }
    else if (value === "Medical") {
        dynamicFields.innerHTML = `
        <span class="section-label">Subject</span>
        <input type="text" name="aics_subject_name" placeholder="Patient's Name" required>
        <input type="text" name="aics_subject_bday" class="date-format" placeholder="Subject's Birthday (YYYY-MM-DD)" required>
        <select name="gender" required>
                        <option value="" disabled <?php echo empty($prefill['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                        <option <?php if($prefill['gender'] == "Male") echo "selected"; ?>>Male</option>
                        <option <?php if($prefill['gender'] == "Female") echo "selected"; ?>>Female</option>
                    </select> 
        <input type="number" name="aics_subject_age" placeholder="Age" required>
        <input type="text" name="aics_subject_class" placeholder="Classification" required>
        <input type="text" name="aics_diagnosed" placeholder="Diagnosed of" required>

        <span class="section-label">Other</span>
        <input type="number" name="aics_amount" placeholder="Amount" required>
        <input type="text" name="aics_time" class="time-format" placeholder="Time (HH:MM)" required>
        <input type="text" name="aics_payout" class="date-format" placeholder="Payout Date (YYYY-MM-DD)" required>
        <input type="text" name="aics_remark" placeholder="Remark">
        `;
    }
    else {
        dynamicFields.innerHTML = `
        <span class="section-label">Subject</span>
        <input type="text" name="aics_subject_name" placeholder="Name" required>
        <input type="text" name="aics_subject_bday" class="date-format" placeholder="Subject's Birthday (YYYY-MM-DD)" required>
        <select name="gender" required>
                        <option value="" disabled <?php echo empty($prefill['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                        <option <?php if($prefill['gender'] == "Male") echo "selected"; ?>>Male</option>
                        <option <?php if($prefill['gender'] == "Female") echo "selected"; ?>>Female</option>
                    </select> 
        <input type="number" name="aics_subject_age" placeholder="Age" required>
        <input type="text" name="aics_subject_class" placeholder="Classification" required>

        <span class="section-label">Other</span>
        <input type="number" name="aics_amount" placeholder="Amount" required>
        <input type="text" name="aics_time" class="time-format" placeholder="Time (HH:MM)" required>
        <input type="text" name="aics_payout" class="date-format" placeholder="Payout Date (YYYY-MM-DD)" required>
        <input type="text" name="aics_remark" placeholder="Remark">
        `;
    }
});

document.addEventListener("input", function(e){
    if(e.target.classList.contains("date-format")){
        let v = e.target.value.replace(/\D/g, '').slice(0,8);

        if (v.length >= 5) v = v.slice(0,4) + '-' + v.slice(4);
        if (v.length >= 8) v = v.slice(0,7) + '-' + v.slice(7);

        e.target.value = v;
    }
});

document.addEventListener("input", function(e){
    if(e.target.classList.contains("time-format")){

        let v = e.target.value.replace(/\D/g, '').slice(0,4);

        if (v.length >= 3) v = v.slice(0,2) + ':' + v.slice(2);

        let [hh, mm] = v.split(":");

        if (hh && parseInt(hh) > 23) hh = "23";

        if (mm && parseInt(mm) > 59) mm = "59";

        e.target.value = hh + (mm ? ":" + mm : "");
    }
});

</script>

<script>
window.addEventListener("DOMContentLoaded", function () {

    let assistanceFromForm = "<?= $value['assistance'] ?? '' ?>";

    if (assistanceFromForm.toLowerCase().includes("medical") || 
        assistanceFromForm.toLowerCase().includes("financial")) {

        document.getElementById("assistance_type").value = "AICS";
        document.getElementById("aics_type").style.display = "block";
        document.getElementById("aics_type").value = "Medical";
        document.getElementById("assistance_type")
            .dispatchEvent(new Event('change'));

        document.getElementById("aics_type")
            .dispatchEvent(new Event('change'));
    }
});
</script>

<div class="bottom-nav">
    <a href="homepage.php" class="nav-item">Dashboard</a>
    <a href="service.php" class="nav-item">Service Records</a>
    <a href="profiles.php" class="nav-item">Client Profiles</a>
    <a href="entry.php" class="nav-item active">Entry Page</a>
    <a href="settings.php" class="nav-item">Settings</a>
</div>
        </form>
</body>
</html>