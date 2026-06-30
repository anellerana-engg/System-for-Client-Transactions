<?php
ob_start();

require('fpdf/fpdf.php');
require('fpdi/src/autoload.php');

use setasign\Fpdi\Fpdi;

include("connect.php");
include("session_check.php");


$pdf = new Fpdi();
$data = [];
$intake_id = null;


if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $intake_json = json_encode($_POST);
    $client_id = $_GET['id'] ?? null;

    $stmt = $conn->prepare("INSERT INTO intake_records (client_id, data) VALUES (?, ?)");
    $stmt->bind_param("is", $client_id, $intake_json);
    $stmt->execute();

    $intake_id = $conn->insert_id;

    $data = $_POST;
}


elseif(isset($_GET['intake_id']) && !empty($_GET['intake_id'])){

    $intake_id = $_GET['intake_id'];

    $stmt = $conn->prepare("SELECT data FROM intake_records WHERE id=?");
    $stmt->bind_param("i", $intake_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        $data = json_decode($row['data'], true);

        if(!is_array($data)){
            die("Invalid intake data.");
        }
    } else {
        die("Intake record not found.");
    }
}


else{
    die("No data source.");
}


if(empty($data['name'])){
    die("Name is required.");
}


$fam_name = $data['fam_name'] ?? [];
$fam_age = $data['fam_age'] ?? [];
$fam_relation = $data['fam_relation'] ?? [];
$fam_education = $data['fam_education'] ?? [];
$fam_occupation = $data['fam_occupation'] ?? [];


$templatePath = "pdf/intake_template.pdf";

if(!file_exists($templatePath)){
    die("Template file not found.");
}

$pdf->setSourceFile($templatePath);
$template = $pdf->importPage(1);

$size = $pdf->getTemplateSize($template);
$pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
$pdf->useTemplate($template);

$pdf->SetFont('Arial', '', 10);


function writeText($pdf, $x, $y, $text, $w = 30){
    $pdf->SetXY($x, $y);
    $pdf->Cell($w, 5, $text, 0, 0, 'C'); 
}

function writeMulti($pdf, $x, $y, $w, $text){
    $pdf->SetXY($x, $y);
    $pdf->MultiCell($w, 5, $text);
}


writeText($pdf, 65, 70, $data['date'] ?? '');
writeText($pdf, 65, 84, $data['name'] ?? '');
writeText($pdf, 60, 95, $data['age'] ?? '');

writeText($pdf, 60, 100, $data['birthday'] ?? '');
writeText($pdf, 135, 100, $data['birthplace'] ?? '');

$pdf->SetFont('Arial', '', 10);
$pdf->SetXY(52, 104);
$pdf->MultiCell(110, 4, substr($data['address'] ?? '', 0, 90));
$pdf->SetFont('Arial', '', 10);
$pdf->SetXY(38, 108);
$pdf->MultiCell(40, 4, $data['barangay'] ?? '');
$pdf->SetFont('Arial', '', 10);

writeText($pdf, 60, 112, $data['phone'] ?? '');
writeText($pdf, 130, 95, $data['civil_status'] ?? '');

writeText($pdf, 135, 108, $data['occupation'] ?? '');
writeText($pdf, 150, 104, $data['education'] ?? '');
writeText($pdf, 135, 112, $data['need'] ?? '');


$y = 138;
for($i = 0; $i < count($fam_name); $i++){
    if(empty($fam_name[$i])) continue;

    writeText($pdf, 25, $y, $fam_name[$i]);
    writeText($pdf, 70, $y, $fam_age[$i]);
    writeText($pdf, 95, $y, $fam_relation[$i]);
    writeText($pdf, 128, $y, $fam_education[$i]);
    writeText($pdf, 162, $y, $fam_occupation[$i]);

    $y += 7;
}


writeText($pdf, 105, 179, $data['assistance'] ?? '');
writeMulti($pdf, 25, 183, 200, $data['problem'] ?? '');
writeMulti($pdf, 25, 191, 145, $data['advice'] ?? '');
writeText($pdf, 25, 195, $data['capacity'] ?? '');
writeText($pdf, 115, 195, $data['support'] ?? '');


writeMulti($pdf, 25, 212, 150, $data['findings_1'] ?? '');
writeText($pdf, 25, 216, $data['civil_status'] ?? '');
writeMulti($pdf, 65, 216, 100, $data['findings_2'] ?? '');


writeText($pdf, 45, 240, $data['interviewer'] ?? '');
writeText($pdf, 130, 240, $data['name'] ?? '');
writeMulti($pdf, 65, 259, 110, $data['amount_words'] ?? '');
writeText($pdf, 48, 263.5, $data['amount'] ?? '');
writeText($pdf, 125, 263.5, $data['behalf'] ?? '');
writeText($pdf, 95, 283, $data['officer'] ?? '');


$folder = "generated/";
if (!file_exists($folder)) {
    mkdir($folder, 0777, true);
}

$fileName = 'Intake_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['name']) . '.pdf';
$filePath = $folder . $fileName;

$pdf->Output('F', $filePath);

ob_end_flush();
?>

<!DOCTYPE html>
<html>
<head>
<title>PDF Generated</title>

<style>
:root { 
    --mswdo-blue: #0038a8; 
    --mswdo-blue-light: #eef2ff;
    --mswdo-red: #c63927; 
    --bg-gray: #f8fafc; 
    --card-border: #e2e8f0; 
    --text-main: #1e293b;
    --text-muted: #64748b;
    --shadow: 0 10px 25px rgba(0,0,0,0.08);
}

body {
    font-family: 'Inter', 'Segoe UI', sans-serif;
    background: var(--bg-gray);
    margin: 0;
    padding: 0;
}


.wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}


.card {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: var(--shadow);
    text-align: center;
    width: 420px;
    border: 1px solid var(--card-border);
}


.card h2 {
    color: var(--mswdo-blue);
    margin-bottom: 10px;
}


.card p {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 25px;
}


.btn {
    display: block;
    width: 100%;
    padding: 14px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    margin-bottom: 12px;
    transition: 0.25s ease;
}

.btn-primary {
    background: var(--mswdo-blue);
    color: white;
}

.btn-primary:hover {
    background: #002a80;
}

.btn-secondary {
    background: #64748b;
    color: white;
}

.btn-secondary:hover {
    background: #475569;
}


.btn-success {
    background: #16a34a;
    color: white;
}

.btn-success:hover {
    background: #15803d;
}

a {
    text-decoration: none;
}
</style>
</head>

<body>

<div class="wrapper">
    <div class="card">

        <h2>✅ PDF Generated Successfully</h2>
        <p>Your intake form has been saved and converted into a document.</p>

        <a href="<?php echo $filePath; ?>" target="_blank">
            <button class="btn btn-primary">📄 View PDF</button>
        </a>

        <a href="entry.php?intake_id=<?php echo $intake_id; ?>">
            <button class="btn btn-secondary">➡ Proceed to Client Assessment</button>
        </a>

    </div>
</div>

<script>
window.open("<?php echo $filePath; ?>", "_blank");
</script>

</body>
</html>