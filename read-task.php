<?php


$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();

($_SERVER["REQUEST_METHOD"] === "POST") || die("Nasty hacker, shoo!");

require_once "./credentials.php";

if (! isset($_POST["the_biz_id"])) {
    die("{'message':'Ya sure ya request's correct? BIZ_ID missing!!'}");
}

$the_biz_id = $_POST["the_biz_id"];

require_once "./logger.php";
 __($_SESSION["user_id"], "Viewed task " . $_POST["the_title"], $_ENV["ENV"], 1);


$url = 'https://api.seiue.com/chalk/task/v2/tasks/' . $the_biz_id . '/events';
$data = json_encode(['event_name' => 'view_task']);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

$headers = [
    'accept: application/json, text/plain, */*',
    'authorization: Bearer ' . $res,
    'cache-control: no-cache',
    'content-type: application/json',
    'x-reflection-id: ' . $ares
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

if ($response === false) {
    echo '{"message":"cURL Error: ' . curl_error($ch) . '"}';
} else {
    echo '{"message":"Completed"}';
}

curl_close($ch);
?>