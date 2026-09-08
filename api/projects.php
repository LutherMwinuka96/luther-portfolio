<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get category filter from query string
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Build query
$query = "SELECT * FROM projects WHERE 1=1";
$params = array();

if ($category !== 'all') {
    $query .= " AND category = :category";
    $params[':category'] = $category;
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$num = $stmt->rowCount();

$projects_arr = array();
$projects_arr["projects"] = array();

if ($num > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Parse tech_stack JSON
        $row['tech_stack'] = json_decode($row['tech_stack'], true);
        
        array_push($projects_arr["projects"], $row);
    }
    
    http_response_code(200);
    echo json_encode($projects_arr);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "No projects found."));
}
?>