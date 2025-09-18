<?php
die("deprecated.");
// 初始化 cURL 会话
$ch = curl_init();

// 设置请求的 URL
curl_setopt($ch, CURLOPT_URL, 'https://passport.seiue.com/authorize');

// 设置请求头
$headers = [
    'accept: application/json, text/plain, */*',
    'accept-language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
    'cache-control: no-cache',
    'content-type: application/x-www-form-urlencoded',
    'cookie: PHPSESSID=3b1060edde4fe5e56a69ecba63b30669; active_reflection=3364349',
    'origin: https://chalk-c3.seiue.com',
    'pragma: no-cache',
    'priority: u=1, i',
    'referer: https://chalk-c3.seiue.com/',
    'sec-ch-ua: "Chromium";v="128", "Not;A=Brand";v="24", "Microsoft Edge";v="128"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-site',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// 设置请求方法为 POST
curl_setopt($ch, CURLOPT_POST, true);

// 设置 POST 数据
$postData = [
    'client_id' => 'GpxvnjhVKt56qTmnPWH1sA',
    'response_type' => 'token'
];
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

// 设置返回响应而不是直接输出
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// 执行请求
$response = curl_exec($ch);

// 检查是否有错误发生
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} 

// 关闭 cURL 会话
curl_close($ch);

if (! json_decode($response, true)["access_token"] ) {

    die("Sorry; we did not get any data. We will try again later. Response:". $response);
}
$result = file_put_contents("./keys.auth", json_decode($response, true)["access_token"]);
if ($result === false) {

    echo "Could not write.";
}
else {

    $finally = file_put_contents("./activeref.auth",json_decode($response, true)["active_reflection_id"]);
    if ( $finally === false){
        echo "Failed to write active ref.";
    }
    else{
        echo "Done.";
    }
}
?>