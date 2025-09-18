<?php
($_SERVER["REQUEST_METHOD"]==="POST") || die("{'Nasty hacker':'Go away'}");
session_start();
require_once "./credentials.php";

// 初始化 cURL 会话
$ch = curl_init();

// 设置 cURL 选项
$url = "https://api.seiue.com/chalk/me/received-messages?owner.id=$ares&type=message";


// 设置选项
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 返回结果而不是直接输出
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); // 指定请求方法为PATCH

// 设置请求头
$headers = [
    'accept: application/json, text/plain, */*',
    'authorization: Bearer '.$res,
    'cache-control: no-cache',
    'content-type: application/json',
    'x-reflection-id: '.$ares
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// 设置请求体
$data = json_encode(['readed' => true]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

// 执行请求并获取响应
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