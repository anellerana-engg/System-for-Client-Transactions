<?php
session_start();
include("connect.php");
include("session_check.php");

if(!isset($_SESSION['email'])){ 
    header("Location: index.php"); 
    exit(); 
}

if($_SESSION['role'] === 'intern'){
    header("Location: entry.php");
    exit();
}

$user_email = $_SESSION['email'];
$user_role = $_SESSION['role'] ?? 'staff';


$user_q = $conn->prepare("SELECT assigned_type FROM users WHERE email=?");
$user_q->bind_param("s", $user_email);
$user_q->execute();
$user_res = $user_q->get_result()->fetch_assoc();

$user_type = $user_res['assigned_type'] ?? '';


if(isset($_POST['request_edit'])){

    $record_id = $_POST['record_id'];


    $client_q = $conn->prepare("SELECT assistance_type FROM clients WHERE id=?");
    $client_q->bind_param("i", $record_id);
    $client_q->execute();
    $client_res = $client_q->get_result()->fetch_assoc();

    $client_type = $client_res['assistance_type'] ?? '';


    if($user_role !== 'admin' && $user_type !== $row['assistance_type']){
    echo "<script>
        alert('You can only edit your assigned assistance type ($user_type)');
        window.location.href='view.php?id=".$row['id']."';
    </script>";
    exit();
}

    $proposed = [
        'assistance_type' => $_POST['edit_assist_type'] ?? '',
        'date_received' => $_POST['edit_date'] ?? '',
        'social_worker' => $_POST['edit_social_worker'] ?? ''
    ];
    
    $json_data = json_encode($proposed);

    $stmt = $conn->prepare("
        INSERT INTO edit_requests (client_id, request_type, proposed_data, requested_by) 
        VALUES (?, 'edit', ?, ?)
    ");
    $stmt->bind_param("iss", $record_id, $json_data, $user_email);
    $stmt->execute();

    echo "<script>alert('Edit request submitted! Awaiting Admin approval.');</script>";
}


$result = $conn->query("SELECT * FROM clients ORDER BY date_received DESC");

$all_clients = [];
while($row = $result->fetch_assoc()){

    $details = json_decode($row['details'], true);
    

    $row['income'] = $row['income'] ?? '';
    $row['education'] = $row['education'] ?? '';

    $all_clients[] = $row;
}

$json_clients = json_encode($all_clients);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Office</title>
    <style>
        :root { 
            --mswdo-blue: #0038a8; 
            --mswdo-blue-light: #eef2ff;
            --mswdo-red: #c63927; 
            --mswdo-green: #0F9D58;
            --bg-gray: #f8fafc; 
            --card-border: #e2e8f0; 
            --text-main: #1e293b;
            --text-muted: #64748b;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .suggest-item {
            padding: 10px;
            cursor: pointer;
            font-size: 14px;
        }

.suggest-item:hover {
    background: #eef2ff;
}

        body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; margin: 0; background: var(--bg-gray); color: var(--text-main); padding-bottom: 80px; }
        
    
        .header {
            display: flex; align-items: center; justify-content: space-between;
            background: white; padding: 12px 40px; border-bottom: 1px solid var(--card-border);
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); position: sticky; top: 0; z-index: 1001;
        }
       
        .header img { height: 50px; margin-right: 15px; }
        .header-left { display: flex; align-items: center; }
        .header-text h1 { margin: 0; font-size: 18px; color: var(--mswdo-blue); }
        .logout-btn { background: var(--mswdo-red); color: white; text-decoration: none; padding: 8px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; }


        .container {
    display: grid;
    grid-template-columns: 220px 1fr;
    height: calc(100vh - 75px);
    padding: 10px; 
    padding-bottom: 20px;
}

.sidebar {
    background: white;
    padding: 18px 12px;
    border-right: 1px solid var(--card-border);
    overflow: hidden;
    top: 10px;
    height: calc(100vh - 95px); z-index: 500;
}
        .sidebar h3 { color: var(--text-main); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; border-bottom: 2px solid var(--mswdo-blue-light); padding-bottom: 10px; }
        .sidebar input, .sidebar select { width: 100%; padding: 12px; margin-bottom: 20px; border: 1.5px solid var(--card-border); border-radius: 8px; background-color: #fcfcfd; font-size: 14px; box-sizing: border-box; }

        .main { padding: 15px; max-width: 800px; margin: 0 auto; width: 100%; overflow-y: auto; padding-bottom: 90px; }

 
        .profile-card { background: white; border: 1px solid var(--card-border); border-radius: 16px; padding: 35px; box-shadow: var(--shadow); margin-bottom: 30px; }
        .profile-card h2 { color: var(--mswdo-blue); margin-top: 0; font-size: 20px; border-bottom: 2px solid var(--mswdo-blue-light); padding-bottom: 15px; margin-bottom: 25px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        .info-item label { font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-item p { margin: 6px 0 0 0; font-size: 16px; font-weight: 600; color: #334155; }

 
        .table-card { 
    background: white; 
    border: 1px solid var(--card-border); 
    border-radius: 14px; 
    overflow: visible;
    box-shadow: var(--shadow); 
}

table { 
    width: 100%; 
    border-collapse: collapse; 
    table-layout: auto;
}

th, td {
    text-align: center;
}

th { 
    background: #f8fafc; 
    padding: 20px; 
    font-size: 12px; 
    text-transform: uppercase; 
    color: var(--text-muted); 
    border-bottom: 1px solid var(--card-border); 
}

td { 
    padding: 18px 20px; 
    border-bottom: 1px solid #f1f5f9; 
    font-size: 14px; 
    color: #475569; 
}


        .btn-action { display: block; background: var(--mswdo-blue); color: white; padding: 14px; text-decoration: none; border-radius: 10px; font-weight: 700; text-align: center; margin-top: 10px; box-shadow: 0 4px 12px rgba(0, 56, 168, 0.2); font-size: 13px; }
        .btn-drive { display: block; background: var(--mswdo-green); color: white; padding: 14px; text-decoration: none; border-radius: 10px; font-weight: 700; text-align: center; margin-top: 10px; font-size: 13px; box-shadow: 0 4px 10px rgba(15, 157, 88, 0.2); transition: transform 0.2s; }
        .btn-drive:hover { transform: translateY(-2px); background: #0b8043; }
        
        .btn-edit-small { background: #f1f5f9; color: var(--text-muted); border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 11px; font-weight: bold; }
        .btn-edit-small:hover { background: var(--mswdo-blue-light); color: var(--mswdo-blue); }


        .modal {
    display: none;
    position: fixed;
    z-index: 2000;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
}


.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}
        .modal-content {
    background: white;
    padding: 30px;
    border-radius: 16px;

    width: 700px;
    max-width: 95%;
    
    box-shadow: var(--shadow);
    animation: fadeIn 0.2s ease;
}
        .btn-save-req { background: var(--mswdo-blue); color: white; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; }


.modal-content h3 { margin-top: 0; color: var(--mswdo-blue); font-size: 18px; }

.modal-content p { margin-bottom: 20px; }


.modal-content label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; }

.modal-content input,
.modal-content select {
    width: 100%;
    padding: 12px 14px;

    border: 1.5px solid var(--card-border);
    border-radius: 10px;

    margin-bottom: 18px;
    font-size: 14px;

    box-sizing: border-box;
    background: #f8fafc;

    transition: 0.2s ease;
}


.btn-save-req { background: var(--mswdo-blue); color: white; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }

.btn-save-req:hover { background: #002a80; }

.modal-content button[type="button"] { margin-top: 10px; font-size: 13px; }


@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

        .bottom-nav { display: flex; background: #1e293b; position: fixed; bottom: 0; width: 100%; z-index: 1000; }
        .nav-item { color: #94a3b8; text-decoration: none; padding: 10px; flex-grow: 1; text-align: center; font-size: 13px; font-weight: 600; }
        .nav-item.active { color: white; background: #334155; border-top: 4px solid var(--mswdo-red); }
    
        .header {
    padding: 10px 24px;
}

.header img {
    height: 45px;
}

html, body {
    height: 100%;
    overflow: hidden; 
}

.profile-card {
    transform: scale(0.9);
    transform-origin: top center;
    margin-bottom: -30px;
}

.table-card {
    transform: scale(0.9);
    transform-origin: top center;
}

    </style>
</head>
<body>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Request Edit</h3>
        <p style="font-size: 12px; color: var(--text-muted);">
            This change must be approved by an Admin.
        </p>

        <form method="POST">
            <input type="hidden" name="record_id" id="modal_id">

            <label style="font-size: 11px;">Date Received</label>
            <input type="text" name="edit_date" id="modal_date"
       placeholder="YYYY-MM-DD"
       maxlength="10"
       pattern="\d{4}-\d{2}-\d{2}"
       required>

            <label style="font-size: 11px;">Assistance Type</label>
            <select name="edit_assist_type" id="modal_type">
                <option>AICS</option>    
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

            <label style="font-size: 11px;">Social Worker</label>
            <input type="text" name="edit_social_worker" id="modal_social_worker">

            <button type="submit" name="request_edit" class="btn-save-req">
                Submit for Approval
            </button>

            <button type="button" onclick="closeModal()"
                style="background:none; border:none; color:var(--text-muted); cursor:pointer; width:100%; margin-top:10px;">
                Cancel
            </button>
        </form>
    </div>
</div>

<div class="header">
    <div class="header-left"><img src="logo.png" alt="Logo"><div class="header-text"><h1>Office | <span style="font-weight:400; color:var(--text-muted)">Client Database</span></h1></div></div>
    <a href="logout.php" class="logout-btn">LOGOUT</a>
</div>

<div class="container">
    <div class="sidebar">
    <h3>Quick Search</h3>

    <input type="text" id="searchInput" placeholder="Find client by name..." autocomplete="off">

    <div id="suggestions" style="
        background:white;
        border:1px solid #e2e8f0;
        border-radius:8px;
        max-height:200px;
        overflow-y:auto;
        display:none;
        margin-top:-10px;
        margin-bottom:15px;
    "></div>

    <div id="actionArea"></div>
</div>

    <div class="main">
        <div class="profile-card">
            <h2>Personal Details</h2>
            <div class="info-grid">
    <div class="info-item"><label>Name</label><p id="disp-name">---</p></div>
    <div class="info-item"><label>Address</label><p id="disp-addr">---</p></div>
    <div class="info-item"><label>Barangay</label><p id="disp-brgy">---</p></div>
    <div class="info-item"><label>Gender</label><p id="disp-gender">---</p></div>
    <div class="info-item"><label>Age</label><p id="disp-age">---</p></div>
    <div class="info-item"><label>Birthday</label><p id="disp-bday">---</p></div>
    <div class="info-item"><label>Civil Status</label><p id="disp-civil">---</p></div>
    <div class="info-item"><label>Income</label><p id="disp-income">---</p></div>
    <div class="info-item"><label>Occupation</label><p id="disp-job">---</p></div>
    <div class="info-item"><label>Education</label><p id="disp-edu">---</p></div>
    <div class="info-item"><label>Phone No.</label><p id="disp-phone">---</p></div>
    <div class="info-item"><label>Email Address</label><p id="disp-email">---</p></div>
</div>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr><th>Date</th><th>Assistance</th><th>Case Worker</th><th>Edit</th></tr>
                </thead>
                <tbody id="historyTable">
                    <tr><td colspan="4" style="text-align:center; color:#94a3b8; padding: 50px;">Select a client profile.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="bottom-nav">
    <a href="homepage.php" class="nav-item">Dashboard</a>
    <a href="service.php" class="nav-item">Service Records</a>
    <a href="profiles.php" class="nav-item active">Client Profiles</a>
    <a href="entry.php" class="nav-item">Entry Page</a>
    <a href="settings.php" class="nav-item">Settings</a>
</div>

<script>
const clients = <?php echo $json_clients; ?>;

const search = document.getElementById('searchInput');
const suggestions = document.getElementById('suggestions');


const uniqueNames = [...new Set(clients.map(c => c.name))];

document.addEventListener("input", function(e) {
    if (e.target.id === "modal_date") {

        let v = e.target.value.replace(/\D/g, '').slice(0,8);

        if (v.length >= 5) v = v.slice(0,4) + '-' + v.slice(4);
        if (v.length >= 8) v = v.slice(0,7) + '-' + v.slice(7);

        e.target.value = v;
    }
});


search.addEventListener('input', function () {
    const value = this.value.toLowerCase();

    if (!value) {
        suggestions.style.display = "none";
        return;
    }

    const filtered = uniqueNames.filter(name =>
        name.toLowerCase().includes(value)
    );

    suggestions.innerHTML = filtered.map(name =>
        `<div class="suggest-item">${name}</div>`
    ).join('');

    suggestions.style.display = filtered.length ? "block" : "none";
});


suggestions.addEventListener('click', function (e) {
    if (e.target.classList.contains('suggest-item')) {
        const selectedName = e.target.innerText;
        search.value = selectedName;
        suggestions.style.display = "none";
        loadClient(selectedName);
    }
});


function loadClient(name) {
    const history = clients.filter(c => c.name === name);
    const p = history[0];

    if (!p) return;

    document.getElementById('disp-name').innerText = p.name;
    document.getElementById('disp-addr').innerText = p.address;
    document.getElementById('disp-brgy').innerText = p.barangay;
    document.getElementById('disp-gender').innerText = p.gender;
    document.getElementById('disp-age').innerText = p.age + " yrs old";
    document.getElementById('disp-bday').innerText = p.birthday;
    document.getElementById('disp-civil').innerText = p.civil_status;
    document.getElementById('disp-income').innerText =
    p.income !== null && p.income !== undefined
        ? '₱' + Number(p.income).toLocaleString()
        : '₱0';
    document.getElementById('disp-job').innerText = p.occupation;
    document.getElementById('disp-edu').innerText = p.education;
    document.getElementById('disp-phone').innerText = p.phone;
    document.getElementById('disp-email').innerText = p.email;

    const driveUrl = `https://drive.google.com/drive/search?q=${encodeURIComponent(p.name)}`;

    document.getElementById('actionArea').innerHTML = `
        <a href="entry.php?name=${encodeURIComponent(p.name)}" class="btn-action">+ NEW TRANSACTION</a>

        <a href="form_page.php?id=${p.id}" class="btn-action"> GENERAL INTAKE SHEET </a>

        <a href="${driveUrl}" target="_blank" class="btn-drive">📂 OPEN CLIENT DRIVE</a>

        <a href="#" onclick="downloadPDF()" class="btn-action" style="background:#0F9D58;">
        ⬇ DOWNLOAD PROFILE (PDF)
        </a>

        <p style="font-size: 11px; color: var(--text-muted); margin-top: 15px; text-align: center;">
            <i>Filing system will search for scanned documents associated with this name.</i>
        </p>
    `;


document.getElementById('historyTable').innerHTML = history.map(h => `
    <tr>
        <td>${h.date_received}</td>

        <td>
            <span style="font-weight:700;">
                ${h.assistance_type}
            </span>
        </td>

        <td>${h.social_worker}</td>

        <td>
            <button class="btn-edit-small"
                onclick="openEditModal(this)"
                data-id="${h.id}"
                data-type="${h.assistance_type}"
                data-date="${h.date_received}"
                data-worker="${h.social_worker}">
                EDIT
            </button>
        </td>
    </tr>
`).join('');
}


document.addEventListener('click', function (e) {
    if (!search.contains(e.target) && !suggestions.contains(e.target)) {
        suggestions.style.display = "none";
    }
});




function openEditModal(btn) {
    document.getElementById('modal_id').value = btn.dataset.id || '';
    document.getElementById('modal_type').value = btn.dataset.type || '';
    document.getElementById('modal_date').value = btn.dataset.date || '';


    document.getElementById('modal_social_worker').value = btn.dataset.worker || '';

    document.getElementById('editModal').classList.add('show');
}

function closeModal() {
    document.getElementById('editModal').classList.remove('show');
}



function downloadPDF() {
    const content = document.querySelector('.main').innerHTML;

    const styles = document.querySelectorAll('style, link[rel="stylesheet"]');
    let styleString = "";

    styles.forEach(style => {
        styleString += style.outerHTML;
    });

    const win = window.open('', '', 'width=900,height=700');

    win.document.write(`
        <html>
        <head>
            <title>Client Profile</title>
            ${styleString}
        </head>
        <body>
            <h2 style="text-align:center;">Client Profile</h2>
            ${content}
        </body>
        </html>
    `);

    win.document.close();
    win.focus();
    win.print();
}
</script>