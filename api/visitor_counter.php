<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get today's date
$today = date('Y-m-d');

try {
    // Track unique visitors active within the last five minutes.
    $db->exec("CREATE TABLE IF NOT EXISTS visitor_online (
        visitor_key CHAR(64) PRIMARY KEY,
        last_seen DATETIME NOT NULL
    )");

    $visitor_key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    $online_query = "INSERT INTO visitor_online (visitor_key, last_seen)
                     VALUES (:visitor_key, NOW())
                     ON DUPLICATE KEY UPDATE last_seen = NOW()";
    $online_stmt = $db->prepare($online_query);
    $online_stmt->bindParam(':visitor_key', $visitor_key);
    $online_stmt->execute();

    $db->exec("DELETE FROM visitor_online WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $online_count = (int) $db->query("SELECT COUNT(*) FROM visitor_online")->fetchColumn();

    $is_heartbeat = isset($_GET['heartbeat']) && $_GET['heartbeat'] === '1';

    if (!$is_heartbeat) {
        // Count a visit only on the initial page request, not on heartbeats.
        $query = "UPDATE visitor_counter SET visit_count = visit_count + 1 WHERE visit_date = :visit_date";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':visit_date', $today);
        $stmt->execute();

        // If no rows affected, insert new record
        if ($stmt->rowCount() === 0) {
            $query = "INSERT INTO visitor_counter (visit_date, visit_count) VALUES (:visit_date, 1)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':visit_date', $today);
            $stmt->execute();
        }
    }

    // Get visitor totals for the current day, week, month, and year.
    $stats_query = "
        SELECT
            COALESCE(SUM(CASE WHEN visit_date = CURDATE() THEN visit_count ELSE 0 END), 0) AS today,
            COALESCE(SUM(CASE WHEN visit_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) THEN visit_count ELSE 0 END), 0) AS week,
            COALESCE(SUM(CASE WHEN visit_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN visit_count ELSE 0 END), 0) AS month,
            COALESCE(SUM(CASE WHEN YEAR(visit_date) = YEAR(CURDATE()) THEN visit_count ELSE 0 END), 0) AS year,
            COALESCE(SUM(visit_count), 0) AS total_visitors
        FROM visitor_counter
    ";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(array(
        "status" => "success",
        "online_visitors" => $online_count,
        "today_visitors" => (int) $stats['today'],
        "week_visitors" => (int) $stats['week'],
        "month_visitors" => (int) $stats['month'],
        "year_visitors" => (int) $stats['year'],
        "total_visitors" => (int) $stats['total_visitors'],
        "today" => $today
    ));

} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(array("message" => "Unable to update visitor count."));
}
?>