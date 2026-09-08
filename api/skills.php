<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get skills by category
$query = "SELECT * FROM skills ORDER BY category, display_order";
$stmt = $db->prepare($query);
$stmt->execute();

$skills_arr = array();
$skills_arr["skills"] = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    array_push($skills_arr["skills"], $row);
}

http_response_code(200);
echo json_encode($skills_arr);
?>