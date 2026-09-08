<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM certificates ORDER BY issue_date DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$certificates_arr = array();
$certificates_arr["certificates"] = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    array_push($certificates_arr["certificates"], $row);
}

http_response_code(200);
echo json_encode($certificates_arr);
?>