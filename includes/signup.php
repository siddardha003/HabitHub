<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// Set user property values
$user->username = $data->username;
$user->email = $data->email;
$user->password = $data->password;

// Check if email already exists
if($user->emailExists()) {
    http_response_code(400);
    echo json_encode(array("message" => "Email already exists."));
} else {
    // Create the user
    if($user->signup()) {
        http_response_code(200);
        echo json_encode(array("message" => "User was created."));
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to create user."));
    }
}
?>
