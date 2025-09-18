<?php
($_SERVER["REQUEST_METHOD"]==="POST") || die("{'Nasty hacker':'Go away'}");
session_start();

require_once "./credentials.php";

$id = isset($_POST['id']) ? $_POST['id'] : null;


// 初始化 cURL 会话
$ch = curl_init();

// 设置 cURL 选项
curl_setopt($ch, CURLOPT_URL, "https://api.seiue.com/chalk/me/received-messages/".$id);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("readed" => true)));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "Pragma: no-cache",
    "Accept: application/json, text/plain, */*",
    "Authorization: Bearer ". $res,
    "X-Reflection-Id: ".$ares
));

// 执行 cURL 会话
$response = curl_exec($ch);

// 检查是否有错误发生
if(curl_errno($ch)) {
    echo json_encode(['cURL error: ' => curl_error($ch)]);
}
echo json_encode(['Message: ' => "success"]);
// 关闭 cURL 会话
curl_close($ch);
exit;


?>