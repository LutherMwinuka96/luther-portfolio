<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get parameters
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Build query for published posts only
$query = "SELECT id, title, excerpt, image_url, category, tags, published_at 
          FROM blog_posts 
          WHERE published = 1 
          ORDER BY published_at DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$posts_arr = array();
$posts_arr["posts"] = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Parse tags JSON
    $row['tags'] = json_decode($row['tags'], true);
    array_push($posts_arr["posts"], $row);
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM blog_posts WHERE published = 1";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_rows = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$posts_arr["pagination"] = array(
    "current_page" => $page,
    "total_pages" => ceil($total_rows / $limit),
    "total_posts" => $total_rows
);

http_response_code(200);
echo json_encode($posts_arr);
?>