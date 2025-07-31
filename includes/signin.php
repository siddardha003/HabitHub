<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and user files
include_once '../config/database.php';
include_once '../classes/User.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize user object
$user = new User($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Set user email
$user->email = $data->email;
$password = $data->password;

// Check if email exists and get user data
if($user->emailExists()) {
    // Verify password
    if(password_verify($password, $user->password)) {
        // Create session
        session_start();
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        
        http_response_code(200);
        echo json_encode(array(
            "message" => "Login successful",
            "user_id" => $user->id,
            "username" => $user->username
        ));
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Invalid password."));
    }
} else {
    http_response_code(404);
    echo json_encode(array("message" => "Email not found."));
}
?>
