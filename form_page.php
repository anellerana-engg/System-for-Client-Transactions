<?php
include("connect.php");
include("session_check.php");

$client = [
    'name' => '',
    'age' => '',
    'civil_status' => '',
    'birthday' => '',
    'address' => '',
    'education' => '',
    'phone' => '',
    'occupation' => ''
];

if(isset($_GET['id']) && !empty($_GET['id'])){
    $id = $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        $client = $row;
    }
}

if(isset($_POST['save_intake'])){

    $client_id = $_GET['id'] ?? null;

    $intake_data = json_encode($_POST);

    $stmt = $conn->prepare("INSERT INTO intake_records (client_id, data) VALUES (?, ?)");
    $stmt->bind_param("is", $client_id, $intake_data);
    $stmt->execute();

    $intake_id = $conn->insert_id;

    header("Location: generate_pdf.php?intake_id=" . $intake_id);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>General Intake Sheet</title>

<style>
:root { 
    --mswdo-blue: #0038a8; 
    --mswdo-blue-light: #eef2ff;
    --bg-gray: #f8fafc; 
    --card-border: #e2e8f0; 
    --text-main: #1e293b;
    --text-muted: #64748b;
    --shadow: 0 4px 6px rgba(0,0,0,0.05);
}

body {
    font-family: 'Segoe UI', sans-serif;
    background: var(--bg-gray);
    margin: 0;
    padding: 40px;
    color: var(--text-main);
}

.form-container {
    max-width: 900px;
    margin: auto;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 14px;
    border: 1px solid var(--card-border);
    box-shadow: var(--shadow);
    margin-bottom: 20px;
}

h2 {
    text-align: center;
    color: var(--mswdo-blue);
    margin-bottom: 15px;
}

h3 {
    color: var(--mswdo-blue);
    border-bottom: 2px solid var(--mswdo-blue-light);
    padding-bottom: 6px;
    margin: 0 0 10px 0;
    font-size: 16px;
}

p {
    margin: 10px 0;
    line-height: 1.8;
    text-align: justify;
}

.family-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.family-table th {
    background: var(--mswdo-blue-light);
    color: var(--mswdo-blue);
    font-size: 12px;
    padding: 8px;
    border: 1px solid var(--card-border);
}

.family-table td {
    border: 1px solid var(--card-border);
    padding: 5px;
}

.family-table input {
    width: 100%;
    border: none;
    outline: none;
    padding: 5px;
    font-size: 13px;
}

input, select, textarea {
    width: 100%;
    padding: 8px;
    margin-top: 3px;
    margin-bottom: 10px;
    border: 1px solid var(--card-border);
    border-radius: 6px;
    font-size: 13px;
    box-sizing: border-box;
}

.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.inline-field {
    display: inline-block;
    width: auto;
    vertical-align: middle;
    margin: 0 5px;
}

button {
    background: var(--mswdo-blue);
    color: white;
    padding: 12px;
    border: none;
    border-radius: 10px;
    font-weight: bold;
    cursor: pointer;
    width: 100%;
    margin-bottom: 10px;
}

button:hover {
    background: #002a80;
}

::placeholder {
    color: #94a3b8;
}

.back-btn {
    display: inline-block;

    background: #c63927;
    color: white;

    padding: 10px 16px;
    border-radius: 8px;

    text-decoration: none;
    font-size: 13px;
    font-weight: 700;

    margin-bottom: 15px;

    transition: 0.2s ease;
}

.back-btn:hover {
    background: #a5281c;
    text-decoration: none;
}
</style>
</head>

<script>
function addRow() {
    const table = document.getElementById("familyBody");

    const row = `
        <tr>
            <td><input type="text" name="fam_name[]"></td>
            <td><input type="text" name="fam_age[]"></td>
            <td><input type="text" name="fam_relation[]"></td>
            <td><input type="text" name="fam_education[]"></td>
            <td><input type="text" name="fam_occupation[]"></td>
            <td>
                <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
            </td>
        </tr>
    `;

    table.insertAdjacentHTML('beforeend', row);
}

function removeRow(btn) {
    const table = document.getElementById("familyBody");

    if (table.rows.length <= 1) {
        alert("At least one family member is required.");
        return;
    }

    btn.closest("tr").remove();
}
</script>

<body>

<div class="form-container">

<a href="entry.php" class="back-btn">← BACK</a>

<h2>GENERAL INTAKE SHEET</h2>

<form method="POST" action="generate_pdf.php">


<div class="card">
<label>Date of Application</label>

<div style="display:flex; align-items:center; gap:10px;">
    <input type="date" name="date" id="dateField">

    <label style="display:flex; align-items:center; gap:5px; font-size:12px; color:var(--text-muted);">
        <input type="checkbox" id="todayCheck">
        Use today's date
    </label>
</div>
</div>


<div class="card">
<h3>I. Identifying Information</h3>

<label>Name</label>
<input type="text" name="name" pattern="^[A-Za-z\s]+,\s[A-Za-z\s]+(\s[A-Za-z]{1,2}\.)?$" value="<?= htmlspecialchars($client['name']) ?>" required>

<div class="grid-2">
    <div>
        <label>Age</label>
        <input type="number" name="age" min="0" max="150" value="<?= $client['age'] ?>" required>
    </div>

    <div>
        <label>Civil Status</label>
        <select name="civil_status" required>
    <option value="" disabled <?= empty($prefill['civil_status']) ? 'selected' : '' ?>>
    Select Civil Status
</option>
    <option <?= ($client['civil_status'] == "Single") ? "selected" : "" ?>>Single</option>
<option <?= ($client['civil_status'] == "Married") ? "selected" : "" ?>>Married</option>
<option <?= ($client['civil_status'] == "Separated") ? "selected" : "" ?>>Separated</option>
<option <?= ($client['civil_status'] == "Live-in") ? "selected" : "" ?>>Live-in</option>
<option <?= ($client['civil_status'] == "Annulled") ? "selected" : "" ?>>Annulled</option>
<option <?= ($client['civil_status'] == "Widow") ? "selected" : "" ?>>Widow</option>
</select>
    </div>
</div>

<div class="grid-2">
    <div>
        <label>Birthdate (YYYY-MM-DD)</label>
        <input type="text" name="birthday" class="date-format" maxlength="10" pattern="\d{4}-\d{2}-\d{2}" value="<?= $client['birthday'] ?>" required>
    </div>

    <div>
        <label>Birthplace</label>
        <input type="text" name="birthplace" required>
    </div>
</div>

<label>Address (Building/Unit, Street Name, Subdivision)</label>
<input type="text" name="address" value="<?= htmlspecialchars($client['address']) ?>" required>

<div>
        <label>Barangay</label>
<select name="barangay" required>

<option value="" disabled <?= empty($prefill['barangay']) ? 'selected' : '' ?>>
    Select Barangay
</option>


<?php 
$brgys = [
    "Brgy. A", "Brgy. B", "Brgy. C",
    "Brgy. D", "Brgy. E", "Brgy. F",
    "Brgy. G", "Brgy. H",
    "Brgy. I", "Brgy. J"
];

foreach($brgys as $b) {
    $sel = ($client['barangay'] == $b) ? "selected" : "";
    echo "<option $sel>$b</option>";
}
?>
</select>

<label>Educational Attainment</label>
<select name="education">
<option value="" disabled <?php echo empty($prefill['education']) ? 'selected' : ''; ?>>
    Select Educational Attainment
</option>

<?php 
$ed = ["Doctorate", "Professional Degree", "Master's Degree", "Bachelor's Degree", "College Level", "Vocational Training", "High School Graduate", "High School Level", "Grade School Graduate","Grade School Level", "No Educational Background"];

foreach($ed as $e) {
    $selected = ($prefill['education'] == $e) ? "selected" : "";
    echo "<option value=\"$e\" $selected>$e</option>";
}
?>
</select>

<label>Contact No</label>
<input type="text" name="phone" pattern="^09\d{9}$" placeholder="09XXXXXXXXX" maxlength="11" value="<?= $client['phone'] ?>" required>

<label>Nature of Need</label>
<input type="text" name="need" required>

<label>Occupation</label>
<input type="text" name="occupation" value="<?= $client['occupation'] ?>" required>
</div>


<div class="card">
<h3>II. Family Composition</h3>

<table class="family-table">
    <thead>
        <tr>
    <th>Name</th>
    <th>Age</th>
    <th>Relationship</th>
    <th>Education</th>
    <th>Occupation</th>
    <th></th>
</tr>
    </thead>
    <tbody id="familyBody">
    <tr>
        <td><input type="text" name="fam_name[]"></td>
        <td>
    <input 
        type="number" 
        name="fam_age[]" 
        min="0" 
        max="150"
    >
</td>
        <td><input type="text" name="fam_relation[]"></td>
        <td>
    <select name="fam_education[]">
        <option value="" disabled selected>Select Education</option>
        <option>Doctorate</option>
        <option>Professional Degree</option>
        <option>Master's Degree</option>
        <option>Bachelor's Degree</option>
        <option>College Level</option>
        <option>Vocational Training</option>
        <option>High School Graduate</option>
        <option>High School Level</option>
        <option>Grade School Graduate</option>
        <option>Grade School Level</option>
        <option>No Educational Background</option>
    </select>
</td>
        <td><input type="text" name="fam_occupation[]"></td>
        <td>
            <button type="button" class="btn-remove" onclick="removeRow(this)">✕</button>
        </td>
    </tr>
</tbody>
</table>

<button type="button" onclick="addRow()" style="margin-top:10px;">
+ Add Family Member
</button>

</div>


<div class="card">
<h3>III. Problem Presented</h3>

<p>
Client came to the office requesting for 
<select name="assistance" class="inline-field">
    <option value="" disabled selected>Select assistance</option>
    <option>Medical</option>
    <option>Burial</option>
    <option>Financial</option>
    <option>Educational</option>
</select>
assistance due to 

<textarea name="problem" rows="2" placeholder="state the reason..." class="inline-field" style="width:300px;"></textarea>, 

and was advised to 

<textarea name="advice" rows="2" placeholder="recommended action..." class="inline-field" style="width:300px;"></textarea>. 

Client has no capacity to 

<select name="capacity" class="inline-field">
    <option value="" disabled selected>choose action</option>
    <option>purchase</option>
    <option>buy</option>
    <option>pay</option>
</select>

the amount needed, thus other 

<select name="support" class="inline-field">
    <option value="" disabled selected>type of support</option>
    <option>resources</option>
    <option>assistance</option>
</select>

is being sought.
</p>
</div>


<div class="card">
<h3>IV. Findings</h3>

<textarea name="findings_1" rows="2" placeholder="Insert findings"></textarea>

<label>Civil Status</label>
        <select name="status" required>
    <option value="" disabled <?= empty($prefill['civil_status']) ? 'selected' : '' ?>>
    Select Civil Status
</option>
    <option <?= ($client['civil_status'] == "Single") ? "selected" : "" ?>>Single</option>
<option <?= ($client['civil_status'] == "Married") ? "selected" : "" ?>>Married</option>
<option <?= ($client['civil_status'] == "Separated") ? "selected" : "" ?>>Separated</option>
<option <?= ($client['civil_status'] == "Live-in") ? "selected" : "" ?>>Live-in</option>
<option <?= ($client['civil_status'] == "Widow") ? "selected" : "" ?>>Widow</option>
</select>

<textarea name="findings_2" rows="2" placeholder="Additional notes"></textarea>
</div>


<div class="card">
<h3>V. Recommendation</h3>

<label>Initial Interview by</label>
<input type="text" name="interviewer">

<label>Name of Client</label>
<input type="text" name="name" value="<?= htmlspecialchars($client['name']) ?>">

<p>
In view of the above information, the undersigned worker is respectfully recommending the extension of 

<textarea name="amount_words" rows="2" placeholder="Amount in words" class="inline-field" style="width:250px;"></textarea>

(Php 

<input type="number" name="amount" placeholder="Amount in pesos" class="inline-field" style="width:150px;">)

assistance in behalf of <input type="text" name="behalf" placeholder="behalf of" class="inline-field" style="width:150px;">
</p>

<p> Evaluated and Recommended by:
<select name="officer">
    <option value="" disabled selected>Select Social Worker</option>
    <option>Mr. Matthew</option>
    <option>Ms. Mary</option>
</select>

<form method="POST">
<button type="submit" name="save_intake">📄 Save & Generate PDF</button>

</form>
</div>

<script>
const checkbox = document.getElementById('todayCheck');
const dateInput = document.getElementById('dateField');

const today = new Date().toISOString().split('T')[0];
dateInput.max = today;

checkbox.addEventListener('change', function() {
    if (this.checked) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
        dateInput.readOnly = true;
    } else {
        dateInput.value = '';
        dateInput.readOnly = false;
    }
});
</script>

</body>
</html>