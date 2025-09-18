<?php

session_start();
header("content-type:application/json");
require_once "./credentials.php";

if(!isset($_SESSION["user_id"])){
    header("location: /login.php");
}

// Handle status update request
if (isset($_GET['status']) && $_GET['status'] === 'submitted' && isset($_GET['biz_id'])) {
    $taskId = $_GET['biz_id'];
    $filePath = "./aurora-records/$ares-" . $taskId;
    
    // Check if the file exists
    if (file_exists($filePath)) {
        // Read the current data
        $content = file_get_contents($filePath);
        $taskData = json_decode($content, true);
        
        if ($taskData) {
            // Update the status
            $taskData['status'] = 'submitted';
            
            // Save the updated data back to the file
            if (file_put_contents($filePath, json_encode($taskData))) {
                echo json_encode(['response_code' => 200, 'message' => "Task status updated to submitted."]);
            } else {
                echo json_encode(['response_code' => 500, 'message' => "Failed to update task status."]);
            }
        } else {
            echo json_encode(['response_code' => 400, 'message' => "Invalid task data."]);
        }
    } else {
        echo json_encode(['response_code' => 404, 'message' => "Task not found."]);
    }
    
    exit; // End execution after handling the status update
}

// Original functionality for creating new submissions
function generateRandomString($length = 22) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return "SU_AURORA_" . $randomString;
}

$url = 'https://api.seiue.com/chalk/task/v2/assignees/' . $ares . '/tasks/'.$_POST["biz_id"]  .'/submissions';

$my_data = array();
$my_data["task_name"] = $_POST["title"];
$my_data["status"] = "pending";
$my_data["hash"] = array();
    for ($i = 1; $i <= $_POST["fileCount"]; $i++){
        $this_hash = generateRandomString();
        $my_data["hash"][] = $this_hash;
        $attachments[] = [
            'created_at' => date("Y-m-d H:i:s"),
            'name' => "File.jpg",
            'size' => 112,
            'hash' => $this_hash
        ];
    }

    $data = [
        'content' => "",
        'attachments' => $attachments,
        'status' => 'published'
    ];



$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json, text/plain, */*',
    'authorization: Bearer ' . $res,
    'cache-control: no-cache',
    'content-type: application/json',
    'pragma: no-cache',
    'x-reflection-id: ' . $ares
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);

if ($response === false) {
    echo json_encode(['response_code' => 520, 'message' => "Unexpected Error: Failed to submit attachments."]);
} else {
    echo json_encode(['response_code' => 200, 'message' => "Submitted successfully."]);
    file_put_contents("./aurora-records/$ares-" . $_POST["biz_id"], json_encode($my_data));
}

curl_close($ch);

?>