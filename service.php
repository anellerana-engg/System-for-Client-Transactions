<?php
session_start();
include("connect.php");

include("session_check.php");

if(!isset($_SESSION['email'])){ 
    header("Location: index.php"); 
    exit(); 
}

if(!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}


if($_SESSION['role'] === 'intern'){
    header("Location: entry.php");
    exit();
}

$user_email = $_SESSION['email'];


if(isset($_POST['request_delete'])){
    $record_id = mysqli_real_escape_string($conn, $_POST['record_id']);
    $reason_text = $_POST['delete_reason'];
    
    $proposed_json = json_encode(["reason" => $reason_text]);
    
    $stmt = $conn->prepare("INSERT INTO edit_requests (client_id, request_type, proposed_data, requested_by) VALUES (?, 'delete', ?, ?)");
    $stmt->bind_param("iss", $record_id, $proposed_json, $user_email);
    
    if($stmt->execute()){

        $action = "DELETE";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_email, action_performed) VALUES (?, ?)");
        $log_stmt->bind_param("ss", $_SESSION['email'], $action);
        $log_stmt->execute();

        echo "<script>alert('Deletion request submitted for Admin authorization.'); window.location.href='service.php';</script>";
        exit();
    }
}


$query = "SELECT * FROM clients ORDER BY date_received DESC";
$result = mysqli_query($conn, $query);
$transactions = [];

while($row = mysqli_fetch_assoc($result)){

    $details = json_decode($row['details'], true);

    $row['aics_subtype'] = $details['aics_subtype'] ?? '';


    $row['subject_name'] = $details['aics_subject_name'] ?? '';

    $transactions[] = $row;
}

$json_transactions = json_encode($transactions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office - Service Records</title>
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

        body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; margin: 0; background: var(--bg-gray); color: var(--text-main); padding-bottom: 80px; }
        
        .header {
            display: flex; align-items: center; justify-content: space-between;
            background: white; padding: 12px 40px; border-bottom: 1px solid var(--card-border);
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); position: sticky; top: 0; z-index: 1001;
        }
       
        .header img { height: 50px; margin-right: 15px; }
        .header-left { display: flex; align-items: center; }
        .header-text h1 { margin: 0; font-size: 18px; color: var(--mswdo-blue); }


        .logout-btn { 
            background: var(--mswdo-red); color: white; text-decoration: none; 
            padding: 8px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; 
        }

        .container { display: grid; grid-template-columns: 300px 1fr; min-height: calc(100vh - 75px); }

        .sidebar { 
    background: white;
    padding: 30px 20px;
    border-right: 1px solid var(--card-border);

    position: sticky;
    top: 75px;

    height: auto;
    overflow: visible;
}
        
        .sidebar h3 { color: var(--text-main); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; border-bottom: 2px solid var(--mswdo-blue-light); padding-bottom: 10px; }

        .filter-group { margin-bottom: 20px; }
        .filter-group label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; }

        select, input[type="text"], input[type="date"] { 
            width: 100%;
            padding: 12px;
            border: 1.5px solid var(--card-border);
            border-radius: 8px;
            background-color: #fcfcfd;
            font-size: 14px;
            box-sizing: border-box;
        }

        .main { padding: 40px; max-width: 1200px; margin: 0 auto; width: 100%; }

        .metrics-row {
    display: flex;
    gap: 20px;

    position: sticky;
    top: 70px;
    z-index: 100;
    background: var(--bg-gray);
    padding: 10px 0;
}
        
        .metric-card { 
            background: white; border: 1px solid var(--card-border); border-radius: 16px; 
            padding: 20px 40px; text-align: center; box-shadow: var(--shadow); flex: 1;
        }
        
        .metric-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .metric-value { font-size: 28px; font-weight: 800; color: var(--mswdo-blue); display: block; margin-top: 5px; }

        .table-card { 
            background: white; border: 2px solid var(--card-border); border-radius: 16px; 
            overflow: hidden; box-shadow: var(--shadow); 
        }

        table { width: 100%; border-collapse: collapse; }
       th {
    position: sticky;
    top: 0;
    z-index: 90;
    background: #f8fafc;
    text-align: center;

    background: #f8fafc;
    padding: 10px 12px;
    font-size: 11px;
    text-transform: uppercase;
    color: var(--text-muted);
    border-bottom: 2px solid var(--card-border);

    text-align: center;
        }
        td { padding: 18px 20px; border-bottom: 2px solid #f1f5f9; font-size: 14px; color: #475569; }
        tr:hover td { background-color: var(--mswdo-blue-light); color: var(--mswdo-blue); }

        .bottom-nav { display: flex; background: #1e293b; position: fixed; bottom: 0; width: 100%; z-index: 1000; }
        .nav-item { color: #94a3b8; text-decoration: none; padding: 10px; flex-grow: 1; text-align: center; font-size: 13px; font-weight: 600; }
        .nav-item.active { color: white; background: #334155; border-top: 4px solid var(--mswdo-red); }
        
        .button-group {
    display: flex;
    gap: 6px;
    margin-top: 8px;
}

.table-wrapper {
    max-height: 300px;
    overflow-y: auto;
}

html, body {
    height: 100%;
    overflow: hidden;
}

.button-group button {
    flex: 1;
    padding: 8px 0;
    font-size: 11px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    font-weight: bold;
}

.btn-enter {
    background: var(--mswdo-blue);
    color: white;
}

.btn-reset {
    background: var(--mswdo-red);
    color: white;
}

        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
        .modal-content { background: white; margin: 12% auto; padding: 30px; border-radius: 16px; width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-content h3 { color: var(--mswdo-red); margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .btn-confirm-del { background: var(--mswdo-red); color: white; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 15px; }
        .btn-remove {
    background: #ffeaea;
    color: #c62828;
    border: 1px solid #f5a5a5;

    padding: 5px 10px;
    font-size: 11px;
    font-weight: 600;

    border-radius: 6px;
    cursor: pointer;
    transition: 0.2s ease;
}

.btn-remove:hover {
    background: #c62828;
    color: #fff;
    border-color: #c62828;
}

.sidebar {
    padding: 12px 10px;
}

.sidebar h3 {
    font-size: 11px;
    margin-bottom: 10px;
    padding-bottom: 4px;
}

.filter-group {
    margin-bottom: 10px;
}

.filter-group label {
    font-size: 9px;
    margin-bottom: 4px;
}

select,
input[type="text"],
input[type="date"] {
    padding: 6px;
    font-size: 12px;
    border-radius: 5px;
}

.main {
    max-width: 900px;
    margin: 0 auto;
    padding: 16px !important;
}

.metrics-row {
    gap: 14px !important;
    margin-bottom: 16px !important;
}

.metric-card {
    padding: 12px 16px !important;
    border-radius: 12px;
}

.metric-label {
    font-size: 10px !important;
}

.metric-value {
    font-size: 22px !important;
    margin-top: 4px;
}

th, td {
    padding: 8px 10px !important;
    font-size: 12px !important;
}

.table-card {
    border-radius: 12px;
}

.header {
    padding: 10px 24px;
}

.header img {
    height: 45px;
}

    </style>
</head>
<body>

<div id="removeModal" class="modal">
    <div class="modal-content">
        <h3>Request Record Removal</h3>
        <p style="font-size:13px; color: #64748b;">This will send a request to the Admin. The record will only be removed once authorized.</p>
        <form method="POST">
            <input type="hidden" name="record_id" id="modal_record_id">
            <label style="font-size:11px; font-weight:800; color:var(--text-muted);">REASON FOR REMOVAL</label>
            <input type="text" name="delete_reason" placeholder="e.g., Duplicate Entry, Error in encoding" required 
                   style="width:100%; padding:12px; margin-top:8px; border:1px solid var(--card-border); border-radius:8px; box-sizing: border-box;">
            <button type="submit" name="request_delete" class="btn-confirm-del">SUBMIT FOR AUTHORIZATION</button>
            <button type="button" onclick="closeRemoveModal()" style="width:100%; background:none; border:none; margin-top:10px; cursor:pointer; color:var(--text-muted); font-size:12px;">Cancel</button>
        </form>
    </div>
</div>

<div class="header">
    <div class="header-left">
        <img src="logo.png" alt="Logo">
        <div class="header-text">
            <h1>Office | <span style="font-weight:400; color:var(--text-muted)">Service Records</span></h1>
        </div>
    </div>
    <a href="logout.php" class="logout-btn">LOGOUT</a>
</div>

<div class="container">
    <div class="sidebar">
        <h3>Filter Controls</h3>
        <div class="filter-group">
            <label>Search Client's/Subject's Name</label>
            <input type="text" id="nameSearch" placeholder="Enter name...">
        </div>
        <div class="filter-group">
            <label>Barangay / Address</label>
            <select id="barangayFilter">
                <option value="All">All Barangays</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Gender</label>
            <select id="genderFilter">
                <option value="All">All Genders</option>
                <option value="Female">Female</option>
                <option value="Male">Male</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Assistance Type</label>
            <select id="assistFilter">
                <option value="All">All Types</option>
            </select>
        </div>
        <div class="filter-group">
    <label>Assistance Subtype</label>
    <select id="assistSubFilter">
        <option value="All">All Subtypes</option>
    </select>
</div>
        <div class="filter-group">
    <label>Date Received Range</label>
    <div style="display:flex; gap:6px;">
        <input type="date" id="dateMin">
        <input type="date" id="dateMax">
    </div>
</div>
        <div class="button-group">
    <button class="btn-enter" onclick="handleFilters()">ENTER</button>
    <button class="btn-reset" onclick="window.location.reload()">RESET</button>
</div>
    </div>

    <div class="main">
        <div class="metrics-row">
            <div class="metric-card">
                <span class="metric-label">Total Transactions</span>
                <span class="metric-value" id="countPayouts">0</span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Latest Entry</span>
                <span class="metric-value" id="latestDate">---</span>
            </div>
        </div>

        <div class="table-card">
            <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Assistance Type</th>
                        <th>Assistance Subtype</th>
                        <th>Subject Name</th>
                        <th>Date Received</th>
                        <th>Manage</th>
                    </tr>
                </thead>
                <tbody id="transactionTable"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="bottom-nav">
    <a href="homepage.php" class="nav-item">Dashboard</a>
    <a href="service.php" class="nav-item active">Service Records</a>
    <a href="profiles.php" class="nav-item">Client Profiles</a>
    <a href="entry.php" class="nav-item">Entry Page</a>
    <a href="settings.php" class="nav-item">Settings</a>
</div>

<script>
const allData = <?php echo $json_transactions ?: '[]'; ?>;

function populateFilters(data) {
    const assists = [...new Set(data.map(d => d.assistance_type))].sort();
    const barangays = [...new Set(data.map(d => d.barangay))].sort();
    const subtypes = [...new Set(data.map(d => d.aics_subtype))].sort();

    const assistDropdown = document.getElementById("assistFilter");
    const addrDropdown = document.getElementById("barangayFilter");
    const subDropdown = document.getElementById("assistSubFilter");

    assistDropdown.innerHTML = '<option value="All">All Types</option>';
    addrDropdown.innerHTML = '<option value="All">All Barangays</option>';
    subDropdown.innerHTML = '<option value="All">All Subtypes</option>';

    assists.forEach(a => { if(a) assistDropdown.add(new Option(a, a)); });
    barangays.forEach(b => { if(b) addrDropdown.add(new Option(b, b)); });
    subtypes.forEach(s => { if(s) subDropdown.add(new Option(s, s)); });
}

function updateTable(data) {
    const tbody = document.getElementById("transactionTable");
    tbody.innerHTML = "";

    data.sort((a, b) => new Date(b.date_received) - new Date(a.date_received));

    data.forEach(row => {
        let tr = document.createElement("tr");

        tr.style.cursor = "pointer";
        tr.onclick = () => {
            window.location.href = "view.php?id=" + row.id;
        };

        tr.innerHTML = `
            <td style="font-weight:600; color:var(--mswdo-blue);">${row.name}</td>
            <td>${row.address}</td>
            <td>${row.gender}</td>
            <td>${row.age}</td>
            <td><span style="font-weight:700;">${row.assistance_type}</span></td>
            <td>${row.aics_subtype || ''}</td>
            <td>${row.subject_name || ''}</td>
            <td>${row.date_received || ''}</td>
            <td>
                <button class="btn-remove" onclick="event.stopPropagation(); openRemoveModal(${row.id})">REMOVE</button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById("countPayouts").innerText = data.length;
    document.getElementById("latestDate").innerText = data.length ? data[0].date_received : "---";
}


function openRemoveModal(id) {
    document.getElementById('modal_record_id').value = id;
    document.getElementById('removeModal').style.display = 'block';
}

function closeRemoveModal() {
    document.getElementById('removeModal').style.display = 'none';
}


function handleFilters() {
    const nameVal = document.getElementById("nameSearch").value.toLowerCase();
    const addrVal = document.getElementById("barangayFilter").value;
    const genVal = document.getElementById("genderFilter").value;
    const assistVal = document.getElementById("assistFilter").value;
    const subVal = document.getElementById("assistSubFilter").value;

    const minDate = document.getElementById("dateMin").value;
    const maxDate = document.getElementById("dateMax").value;

    const filtered = allData.filter(d => {
        const dDate = d.date_received ? new Date(d.date_received) : null;

        const nameMatch =
            (d.name && d.name.toLowerCase().includes(nameVal)) ||
            (d.subject_name && d.subject_name.toLowerCase().includes(nameVal));

        const addrMatch = (addrVal === "All" || d.barangay === addrVal);
        const genMatch = (genVal === "All" || d.gender === genVal);
        const assistMatch = (assistVal === "All" || d.assistance_type === assistVal);

        const subMatch = (subVal === "All" || d.aics_subtype === subVal);

        let dateMatch = true;
        if(minDate && dDate) dateMatch = dateMatch && (dDate >= new Date(minDate));
        if(maxDate && dDate) dateMatch = dateMatch && (dDate <= new Date(maxDate));

        return nameMatch && addrMatch && genMatch && assistMatch && subMatch && dateMatch;
    });

    updateTable(filtered);
}

document.addEventListener('DOMContentLoaded', () => {
    populateFilters(allData);
    updateTable(allData);

    document.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('input', handleFilters);
    });
});
</script>

</body>
</html>