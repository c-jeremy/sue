<?php
// 定义API接口

// 获取传入的参数
function try_auth($email, $password){
// 检查参数是否为空
if (empty($email) || empty($password)) {
    echo json_encode(['message' => '请输入用户名和密码']);
    exit;
}

// 设置cURL请求
$url = 'https://passport.seiue.com/login?school_id=3';
$data = [
    'email' => $email,
    'password' => $password,
    'school_id' => 3,
    'submit' => '提交'
];

$ch = curl_init();

// 设置cURL选项
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true); // 返回响应头
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
    'Cache-Control: no-cache',
    'Content-Type: application/x-www-form-urlencoded'
]);

// 执行cURL请求
$response = curl_exec($ch);

// 检查cURL错误
if (curl_errno($ch)) {
    echo json_encode(['message' => curl_error($ch)]);
} else {
    // 解析响应头和响应体
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

   
    // 提取所需的Cookie
    $cookies = [];
    preg_match_all('/Set-Cookie:\s*([^;]+)/', $header, $matches);
    foreach ($matches[1] as $match) {
        list($name, $value) = explode('=', $match, 2);
        $cookies[$name] = $value;
    }
if (!$cookies["active_reflection"]){

    return [];
}
   return ["psi"=>$cookies["PHPSESSID"], "ares"=>$cookies["active_reflection"]];


}

// 关闭cURL会话
curl_close($ch);}
?>