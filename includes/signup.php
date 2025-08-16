<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

try {
    include_once '../config/database.php';
    include_once '../classes/User.php';

    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);

    $input = file_get_contents("php://input");
    $data = json_decode($input);

    if (!$data || !isset($data->username) || !isset($data->email) || !isset($data->password)) {
        throw new Exception("Missing required fields: username, email, password");
    }

    $user->username = $data->username;
    $user->email = $data->email;
    $user->password = $data->password;

    // Debug: Check if email exists
    $emailExists = $user->emailExists();
    
    if($emailExists) {
        http_response_code(400);
        echo json_encode(array("message" => "Email already exists."));
    } else {
        // Debug: Try to create user and get more info if it fails
        $signupResult = $user->signup();
        
        if($signupResult) {
            // Verify the user was actually inserted
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
            $stmt->execute([$data->email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(array(
                "message" => "User was created successfully!",
                "debug" => "Users in database: " . $result['count']
            ));
        } else {
            // Get more detailed error info
            $errorInfo = $db->errorInfo();
            http_response_code(400);
            echo json_encode(array(
                "message" => "Unable to create user.",
                "debug" => "Database error: " . implode(', ', $errorInfo)
            ));
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Server error: " . $e->getMessage()));
}
?>
