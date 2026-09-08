<?php
session_start();
header('Content-Type: application/json');

include_once 'config/database.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();

    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT id, username, password_hash FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $id = $row['id'];
        $username = $row['username'];
        $hashed_password = $row['password_hash'];

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            
            echo json_encode(array(
                "status" => "success",
                "message" => "Login successful.",
                "user" => array(
                    "id" => $id,
                    "username" => $username
                )
            ));
        } else {
            echo json_encode(array("status" => "error", "message" => "Invalid password."));
        }
    } else {
        echo json_encode(array("status" => "error", "message" => "User not found."));
    }
}
?>