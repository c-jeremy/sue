<?php
//就是为了防止v0看到而设计的（（（（
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 12; // Number of items per page

$url = 'https://api.seiue.com/chalk/me/received-messages?expand=sender_reflection&owner.id='.$ares.'&sort=-published_at%2C-created_at&type=message&page='.$page.'&per_page='.$perPage;

$headers = [
    'Accept: application/json, text/plain, */*',
    'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
    'Authorization: Bearer ' . $res,
    'X-Reflection-Id:'.$ares
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

if(curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}

curl_close($ch);

// Decode the JSON response
$messages = json_decode($response, true);

?>