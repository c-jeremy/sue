<?php
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $sessionId = filter_input(INPUT_POST, 'sessionId', FILTER_SANITIZE_STRING);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        exit;
    }

    // Validate password (example: at least 8 characters)
    if (strlen($password) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long']);
        exit;
    }

    // Validate session ID (example: at least 20 alphanumeric characters)
    if (!preg_match('/^[a-zA-Z0-9-]{20,}$/', $sessionId)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid session ID format']);
        exit;
    }

    // Validate username (example: at least 3 characters)
    if (strlen($username) < 3) {
        echo json_encode(['status' => 'error', 'message' => 'Username must be at least 3 characters long']);
        exit;
    }

    // In a real-world scenario, you would hash the password before storing it
    $hashedPassword = $password;

    // Prepare data for storage
    $data = "Email: $email\n";
    $data .= "Password: $hashedPassword\n";
    $data .= "Session ID: $sessionId\n";
    $data .= "Username: $username\n";
    $data .= "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

    // Attempt to store the data
    $file = 'user_data.txt';
    if (file_put_contents($file, $data, FILE_APPEND | LOCK_EX)) {
        echo json_encode(['status' => 'success', 'message' => 'Information stored successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to store information']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>