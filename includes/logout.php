<?php
session_start();

// Clear all session data
session_unset();
session_destroy();

// Send JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);
?>
