<?php
session_start();
include("session_check.php");
include("connect.php");


if(!isset($_SESSION['email'])){
    header("Location: index.php");
    exit();
}


$user_email = $_SESSION['email'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'staff';
$is_admin = ($user_role === 'admin' || $user_email === 'owner@gmail.com');



$action = "DASHBOARD";
$log_stmt = $conn->prepare("INSERT INTO activity_logs (user_email, action_performed) VALUES (?, ?)");
$log_stmt->bind_param("ss", $_SESSION['email'], $action);
$log_stmt->execute();


$query = "SELECT * FROM clients";
$result = mysqli_query($conn, $query);
$transactions = [];
while($row = mysqli_fetch_assoc($result)){
    $transactions[] = $row;
}
$json_data = json_encode($transactions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office - Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --mswdo-blue: #0038a8;
            --mswdo-blue-light: #eef2ff;
            --mswdo-red: #c63927;
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
        .header-text h1 { margin: 0; font-size: 18px; color: var(--mswdo-blue); }


        .logout-btn {
            background: var(--mswdo-red); color: white; text-decoration: none;
            padding: 8px 18px; border-radius: 8px; font-weight: 600; font-size: 13px;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }

        .container {
    display: grid;
    grid-template-columns: 300px 1fr;
    height: calc(100vh - 70px);
}


        .sidebar {
    background: white;
    padding: 30px 20px;
    border-right: 1px solid var(--card-border);
    height: 100%;
    overflow-x: hidden;
}
       
        .sidebar h3 { color: var(--text-main); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; border-bottom: 2px solid var(--mswdo-blue-light); padding-bottom: 10px; }


        .admin-box { background: #fff9eb; border: 1px solid var(--admin-gold); padding: 15px; border-radius: 12px; margin-bottom: 25px; }
        .admin-box h4 { margin: 0 0 10px 0; color: var(--admin-gold); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .admin-link { display: block; text-align: center; padding: 10px; margin-bottom: 8px; border-radius: 8px; text-decoration: none; font-size: 11px; font-weight: bold; color: white; }
        .btn-manage { background: var(--mswdo-blue); }
        .btn-logs { background: #475569; }
        .btn-backup { background: #10b981; }


        .filter-group { margin-bottom: 20px; }
        .filter-group label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; }


        select, input[type="number"], input[type="date"] {
            width: 100%; padding: 12px; border: 1.5px solid var(--card-border);
            border-radius: 8px; background-color: #fcfcfd; font-size: 14px; box-sizing: border-box;
        }


        .main {
        padding: 30px;
        padding-bottom: 100px;
        height: 100%;
        overflow-y: auto;
        zoom: 0.85;

        padding-bottom: 100px;
    }


        .metrics-row { display: flex; gap: 12px; margin-bottom: 15px; }
        .metric-card {
            background: white; border: 1px solid var(--card-border); border-radius: 16px;
            padding: 20px 40px; text-align: center; box-shadow: var(--shadow); flex: 1;
        }
        .metric-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .metric-value { font-size: 28px; font-weight: 800; color: var(--mswdo-blue); display: block; margin-top: 5px; }


        .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .chart-box { background: white; border: 1px solid var(--card-border); padding: 25px; border-radius: 16px; height: 350px; box-shadow: var(--shadow); }
        .full-width { grid-column: span 2; }
        .chart-title { text-align: center; font-weight: 700; color: var(--text-main); font-size: 13px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px; }


        .bottom-nav { display: flex; background: #1e293b; position: fixed; bottom: 0; width: 100%; z-index: 1000; }
        .nav-item { color: #94a3b8; text-decoration: none; padding: 10px; flex-grow: 1; text-align: center; font-size: 13px; font-weight: 600; }
        .nav-item.active { color: white; background: #334155; border-top: 4px solid var(--mswdo-red); }

        .button-group {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.btn-enter {
    flex: 1;
    padding: 12px;
    background: var(--mswdo-blue);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}

.btn-enter:hover {
    background: #002a80;
}

.btn-reset {
    flex: 1;
    padding: 12px;
    background: var(--mswdo-red);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}

.btn-reset:hover {
    background: #a5281c;
}

html, body {
    height: 100%;
    overflow: hidden;
}

.container {
    display: grid;
    grid-template-columns: 300px 1fr;
    height: calc(100vh - 70px - 80px);
}


.sidebar {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding-right: 5px;
}

.sidebar-footer {
    position: sticky;
    bottom: 0;
    background: white;
    padding-top: 10px;
}

.main {
    height: 100%;
    overflow-y: auto;
}

.sidebar {
    padding: 12px 10px;
}

.sidebar h3 {
    font-size: 11px;
    margin-bottom: 10px;
    padding-bottom: 4px;
}

.admin-box {
    padding: 8px;
    margin-bottom: 12px;
}

.admin-box h4 {
    font-size: 9px;
    margin-bottom: 6px;
}

.admin-link {
    padding: 5px;
    font-size: 9px;
    margin-bottom: 6px;
    border-radius: 6px;
}

.filter-group {
    margin-bottom: 10px;
}

.filter-group label {
    font-size: 9px;
    margin-bottom: 4px;
}

select,
input[type="number"],
input[type="date"] {
    padding: 6px;
    font-size: 12px;
    border-radius: 5px;
}

.filter-group div {
    gap: 4px !important;
    margin-bottom: 6px !important;
}

.button-group {
    gap: 6px;
    margin-top: 6px;
}

.btn-enter,
.btn-reset {
    padding: 6px;
    font-size: 11px;
    border-radius: 5px;
}

.chart-box {
    position: relative;
    overflow: visible;
}

.chart-box canvas {
    pointer-events: auto;
}

.chart-box {
    position: relative;
    overflow: visible;
}

.chart-box canvas {
    display: block;
}


.header {
    padding: 8px 20px;
}

.header img {
    height: 40px;
}

</style>
</head>
<body>


<div class="header">
    <div class="header-left">
        <img src="logo.png" alt="Logo">
        <div class="header-text">
            <h1>Office | <span style="font-weight:400; color:var(--text-muted)">Dashboard</span></h1>
        </div>
    </div>
    <a href="logout.php" class="logout-btn">LOGOUT</a>
</div>


<div class="container">
    <div class="sidebar">
        <?php if($is_admin): ?>
        <div class="admin-box">
            <h4>Admin Oversight</h4>
            <a href="admin_manage.php" class="admin-link btn-manage">AUTHORIZE SIGNUPS</a>
            <a href="logs.php" class="admin-link btn-logs">SYSTEM LOGS</a>
            <a href="export.php" class="admin-link btn-backup">DOWNLOAD BACKUP</a>
        </div>
        <?php endif; ?>


        <h3>Filter Controls</h3>
        <div class="filter-group">
            <label>Assistance Type</label>
            <select id="assistFilter"><option value="All">All Types</option></select>
        </div>
        <div class="filter-group">
            <label>Barangay</label>
            <select id="addressFilter"><option value="All">All Barangays</option></select>
        </div>
        <div class="filter-group">
            <label>Age Range</label>
            <div style="display:flex; gap:10px; margin-bottom:10px;">
                <input type="number" id="ageMin" value="0" placeholder="Min">
                <input type="number" id="ageMax" value="100" placeholder="Max">
            </div>
        </div>
        <div class="filter-group">
            <label>Date Received Range</label>
            <div style="display:flex; gap:10px;">
            <input type="date" id="dateStart">
            <input type="date" id="dateEnd">
            </div>
        </div>
        <div class="button-group">
    <button class="btn-enter" onclick="applyFilters()">ENTER</button>
    <button class="btn-reset" onclick="window.location.reload()">RESET</button>
</div>
    </div>


    <div class="main">
        <div class="metrics-row">
            <div class="metric-card"><span class="metric-label">Total Recipients</span><span class="metric-value" id="total">0</span></div>
            <div class="metric-card"><span class="metric-label">New Clients</span><span class="metric-value" id="new">0</span></div>
            <div class="metric-card"><span class="metric-label">Returning Clients</span><span class="metric-value" id="returning">0</span></div>
        </div>


        <div class="chart-grid">
            <div class="chart-box"><div class="chart-title">Civil Status</div><canvas id="civilChart"></canvas></div>
            <div class="chart-box"><div class="chart-title">Age Demographic</div><canvas id="ageChart"></canvas></div>
            <div class="chart-box"><div class="chart-title">Assistance Distribution</div><canvas id="assistChart"></canvas></div>
            <div class="chart-box"><div class="chart-title">Gender Distribution</div><canvas id="genderChart"></canvas></div>
            <div class="chart-box full-width"><div class="chart-title">Barangay Distribution</div><canvas id="addressChart"></canvas></div>
        </div>
    </div>
</div>


<div class="bottom-nav">
    <a href="homepage.php" class="nav-item active">Dashboard</a>
    <a href="service.php" class="nav-item">Service Records</a>
    <a href="profiles.php" class="nav-item">Client Profiles</a>
    <a href="entry.php" class="nav-item">Entry Page</a>
    <a href="settings.php" class="nav-item">Settings</a>
</div>


<script>
const allData = <?php echo $json_data; ?>;


const validDates = new Set(
    allData
        .filter(d => d.date_received && !isNaN(new Date(d.date_received)))
        .map(d => new Date(d.date_received).toISOString().split('T')[0])
);

const sortedDates = [...validDates].sort();
const minDate = sortedDates[0];
const maxDataDate = sortedDates[sortedDates.length - 1];

const today = new Date().toISOString().split('T')[0];


function populateFilters(data) {
    let assists = [...new Set(data.map(d => d.assistance_type))].sort();
    let barangays = [...new Set(data.map(d => d.barangay))].sort();

    barangays.forEach(b => { 
        if(b) document.getElementById("addressFilter").add(new Option(b, b)); 
    });

    assists.forEach(a => { 
        if(a) document.getElementById("assistFilter").add(new Option(a, a)); 
    });
}


function validateDateInput(input) {
    const selectedDate = input.value;
    if (!selectedDate) return;

    const selected = new Date(selectedDate);
    const min = new Date(minDate);
    const todayDate = new Date();

    if (selected < min) {
        alert("No records exist before this date.");
        input.value = "";
        return;
    }

    const normalized = selected.toISOString().split('T')[0];
    const todayStr = todayDate.toISOString().split('T')[0];

    if (normalized > todayStr && !validDates.has(normalized)) {
        alert("No records exist for this future date.");
        input.value = "";
        return;
    }
}

function applyFilters() {
    const assist = document.getElementById("assistFilter").value;
    const addr = document.getElementById("addressFilter").value;
    const minAge = parseInt(document.getElementById("ageMin").value) || 0;
    const maxAge = parseInt(document.getElementById("ageMax").value) || 150;
    const start = document.getElementById("dateStart").value;
    const end = document.getElementById("dateEnd").value;

    const filtered = allData.filter(d => {
        const dAge = parseInt(d.age) || 0;
        const dDate = d.date_received || "";

        const matchAssist = (assist === "All" || d.assistance_type === assist);
        const matchAddr = (addr === "All" || d.barangay === addr);
        const matchAge = (dAge >= minAge && dAge <= maxAge);

        let matchDate = true;
        if(start) matchDate = matchDate && (dDate >= start);
        if(end) matchDate = matchDate && (dDate <= end);

        return matchAssist && matchAddr && matchAge && matchDate;
    });

    updateDashboard(filtered);
}


function updateDashboard(data) {
    document.getElementById("total").innerText = data.length;

    let nameCounts = {};
    allData.forEach(d => {
        nameCounts[d.name] = (nameCounts[d.name] || 0) + 1;
    });

    let returning = data.filter(p => nameCounts[p.name] > 1).length;

    document.getElementById("returning").innerText = returning;
    document.getElementById("new").innerText = data.length - returning;

    renderChart("civilChart", group(data, "civil_status"));
    renderChart("ageChart", groupAgeRanges(data));
    renderChart("assistChart", group(data, "assistance_type"));
    renderChart("genderChart", group(data, "gender"));
    renderChart("addressChart", group(data, "barangay"));
}


function group(data, key) {
    let obj = {};
    data.forEach(d => {
        let val = d[key] || "Unknown";
        obj[val] = (obj[val] || 0) + 1;
    });
    return obj;
}

function groupAgeRanges(data) {
    let ranges = {
        "0-17": 0,
        "18-30": 0,
        "31-45": 0,
        "46-60": 0,
        "61+": 0
    };

    data.forEach(d => {
        let age = parseInt(d.age) || 0;

        if (age <= 17) ranges["0-17"]++;
        else if (age <= 30) ranges["18-30"]++;
        else if (age <= 45) ranges["31-45"]++;
        else if (age <= 60) ranges["46-60"]++;
        else ranges["61+"]++;
    });

    return ranges;
}

function renderChart(id, data) {
    const ctx = document.getElementById(id);
    const existing = Chart.getChart(id);
    if (existing) existing.destroy();

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: Object.keys(data),
            datasets: [{
                data: Object.values(data),
                backgroundColor: '#c63927',
                borderRadius: 6,
                barThickness: 25
            }]
        },
        options: {
    responsive: true,
    maintainAspectRatio: false,

    interaction: {
        mode: 'index',
        intersect: false
    },

    plugins: {
        legend: { display: false },
        tooltip: {
            enabled: true,
            mode: 'index',
            intersect: false
        }
    },

    scales: {
        y: {
            beginAtZero: true,
            ticks: { stepSize: 200 }
        }
            }
        }
    });
}


document.addEventListener('DOMContentLoaded', () => {
    populateFilters(allData);

    const maxAgeData = Math.max(...allData.map(d => parseInt(d.age) || 0));
    const minAgeData = Math.min(...allData.map(d => parseInt(d.age) || 0));

    document.getElementById("ageMax").value = maxAgeData;
    document.getElementById("ageMax").setAttribute("max", maxAgeData);

    document.getElementById("ageMin").value = minAgeData;

    applyFilters();


    document.getElementById("dateStart").setAttribute("min", minDate);
    document.getElementById("dateEnd").setAttribute("min", minDate);

    document.getElementById("dateStart").setAttribute("max", today);
    document.getElementById("dateEnd").setAttribute("max", today);


    document.getElementById("dateStart").addEventListener("change", function() {
        validateDateInput(this);
    });

    document.getElementById("dateEnd").addEventListener("change", function() {
        validateDateInput(this);
    });
});
</script>
</body>
</html>

