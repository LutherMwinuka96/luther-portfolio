<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
include_once 'config/database.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (
    !empty($data->name) &&
    !empty($data->email) &&
    !empty($data->subject) &&
    !empty($data->message)
) {
    // Sanitize input
    $name = htmlspecialchars(strip_tags($data->name));
    $email = htmlspecialchars(strip_tags($data->email));
    $subject = htmlspecialchars(strip_tags($data->subject));
    $message = htmlspecialchars(strip_tags($data->message));
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid email format."));
        exit;
    }

    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    // Insert query
    $query = "INSERT INTO contact_messages 
              SET name=:name, email=:email, subject=:subject, 
                  message=:message, ip_address=:ip_address, user_agent=:user_agent";

    $stmt = $db->prepare($query);

    // Bind values
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":subject", $subject);
    $stmt->bindParam(":message", $message);
    $stmt->bindParam(":ip_address", $ip_address);
    $stmt->bindParam(":user_agent", $user_agent);

    // Execute query
    if ($stmt->execute()) {
        // Send email notification (you'll need to configure your SMTP settings)
        $to = "luther@example.com";
        $email_subject = "New Contact Form Message: " . $subject;
        $email_body = "
            Name: $name\n
            Email: $email\n
            Subject: $subject\n
            Message:\n$message\n\n
            IP: $ip_address\n
            User Agent: $user_agent
        ";
        $headers = "From: $email";

        // Uncomment to send email (configure your server first)
        // mail($to, $email_subject, $email_body, $headers);

        http_response_code(201);
        echo json_encode(array("message" => "Message sent successfully."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to send message."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to send message. Data is incomplete."));
}
?>