<?php
/*Based on uploadify.php v1.0.*/
$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();

header('Content-Type: application/json');
($_SERVER["REQUEST_METHOD"]==="POST") || die("{'Nasty hacker':'Go away'}");

require_once "./logger.php";
require_once "./credentials.php";

 __($_SESSION["user_id"], "Submitted task", $_ENV["ENV"], 1);

$the_biz_id = isset($_POST["biz_id"]) ? $_POST['biz_id'] : null;
if (!$the_biz_id) {
    echo json_encode(['response_code' => 400, 'message' => 'Missing biz_id']);
    exit;
}

$attachments = [];
$submitText = isset($_POST["SUBMIT_TEXT"]) ? $_POST["SUBMIT_TEXT"]  : '';

$url = 'https://api.seiue.com/chalk/task/v2/assignees/' . $ares . '/tasks/'.$the_biz_id  .'/submissions';
if($_POST["max_file_id"] === -1){
    
    $data = [
        'content' => $submitText,
        'attachments' => [],
        'status' => 'published'
    ];
    
}else{ // 表明有上传文件

    for ($i = 0; $i <= $_POST["max_file_id"]; $i++){
        $attachments[] = [
            'created_at' => date("Y-m-d H:i:s"),
            'name' => $_POST["filename" . $i],
            'size' => 112,
            'hash' => $_POST["filehash" . $i]
        ];
    }

    $data = [
        'content' => $submitText,
        'attachments' => $attachments,
        'status' => 'published'
    ];

}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json, text/plain, */*',
    'authorization: Bearer ' . $res,
    'cache-control: no-cache',
    'content-type: application/json',
    'pragma: no-cache',
    'x-reflection-id: '.$ares
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);

if ($response === false) {
    echo json_encode(['response_code' => 520, 'message' => "Unexpected Error: Failed to submit attachments."]);
} else {
    echo json_encode(['response_code' => 200, 'message' => "Submitted successfully."]);
}

curl_close($ch);
?>