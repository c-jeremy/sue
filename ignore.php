<?php
session_start();

require_once "./credentials.php";
require_once "./logger.php";

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Fuck nasty hackers']);
    exit;
}

// Get the task ID from the request
$taskId = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;



// Log the action
__($_SESSION["user_id"], "Ignoring task ID: $taskId", $_ENV["ENV"], 1);

// API URL to update the task status
$url = "https://api.seiue.com/chalk/todo/todos/ignore";

$headers = [
    'Accept: application/json, text/plain, */*',
    'Content-Type: application/json',
    'Authorization: Bearer ' . $res,
    'X-Reflection-Id:' . $ares
];

// Prepare the data according to the required format
$data = json_encode([
    'ids' => [(int)$taskId],
    'ignored' => true
]);

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
if (curl_errno($ch)) {
    __($_SESSION["user_id"], "Error ignoring task: $taskId" . curl_error($ch) , $_ENV["ENV"], 0);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Process the response
if ($httpCode >= 200 && $httpCode < 300) {
    __($_SESSION["user_id"], "Successfully ignored task ID: $taskId", $_ENV["ENV"], 1);
    echo json_encode(['success' => true, 'message' => 'Task has been ignored']);
} else {
    __($_SESSION["user_id"], "Failed to ignore task ID: $taskId. HTTP Code: $httpCode", $_ENV["ENV"], 0);
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => 'Failed to ignore task', 'response' => $response]);
}
?>