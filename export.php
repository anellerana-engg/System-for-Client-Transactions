<?php
session_start();
include("connect.php");
include("session_check.php");

if(!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin'){
    header("Location: index.php");
    exit();
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="backup.csv"');

$output = fopen("php://output", "w");


$tables = ["clients", "edit_requests", "activity_logs"];

foreach($tables as $table){

    fputcsv($output, ["==== $table TABLE ===="]);

    $result = mysqli_query($conn, "SELECT * FROM $table");

    if(mysqli_num_rows($result) > 0){

        $fields = mysqli_fetch_fields($result);
        $headers = [];
        foreach($fields as $field){
            $headers[] = $field->name;
        }
        fputcsv($output, $headers);

        while($row = mysqli_fetch_assoc($result)){
            fputcsv($output, $row);
        }
    }

    fputcsv($output, []);
    fputcsv($output, []);
}

fclose($output);
exit();
?>