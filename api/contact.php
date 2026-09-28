<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid request data.']);
    exit;
}

$name = trim((string) ($payload['name'] ?? ''));
$email = trim((string) ($payload['email'] ?? ''));
$subject = trim((string) ($payload['subject'] ?? ''));
$message = trim((string) ($payload['message'] ?? ''));
$website = trim((string) ($payload['website'] ?? ''));

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    http_response_code(422);
    echo json_encode(['message' => 'Please complete every field.']);
    exit;
}

if (mb_strlen($name) > 255 || mb_strlen($email) > 255 || mb_strlen($subject) > 500 || mb_strlen($message) > 5000) {
    http_response_code(422);
    echo json_encode(['message' => 'One or more fields are too long.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['message' => 'Please enter a valid email address.']);
    exit;
}

// Quietly accept and discard submissions caught by the hidden spam trap.
if ($website !== '') {
    http_response_code(201);
    echo json_encode(['message' => 'Thank you! Your message has been sent.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $ipAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $rateCheck = $db->prepare('SELECT COUNT(*) FROM contact_messages WHERE ip_address = :ip_address AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $rateCheck->execute([':ip_address' => $ipAddress]);
    if ((int) $rateCheck->fetchColumn() >= 5) {
        http_response_code(429);
        echo json_encode(['message' => 'Please wait a few minutes before sending another message.']);
        exit;
    }

    $query = 'INSERT INTO contact_messages (name, email, subject, message, ip_address, user_agent)
              VALUES (:name, :email, :subject, :message, :ip_address, :user_agent)';
    $statement = $db->prepare($query);
    $statement->execute([
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip_address' => $ipAddress,
        ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 65535),
    ]);

    http_response_code(201);
    echo json_encode(['message' => 'Thank you! Your message has been sent.']);
} catch (Throwable $exception) {
    error_log('Portfolio contact form error: ' . $exception->getMessage());
    http_response_code(503);
    echo json_encode(['message' => 'Unable to send your message right now. Please try again later.']);
}
