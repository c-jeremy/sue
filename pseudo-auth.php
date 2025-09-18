<?php

$canspeak = isset($externalPACanSpeak) ? 0 : 1;

require_once "./create_conn.php";
$conn = create_conn();

// 查询语句
$sql = "SELECT * FROM users WHERE user_type != 'suspended'";

if(isset($externalUserIndicator)){

    $sql = "SELECT * FROM users WHERE `seiue_uid` = '$externalUserIndicator' AND `user_type` != 'suspended'";
}
// 执行查询
$result = $conn->query($sql);

// 初始化数组
$usersArray = [];

// 处理查询结果
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $usersArray[$row['id']] = $row['seiue_sessid'];
        $usersAres[$row['id']] = $row['seiue_uid'];
    }
} else {
    echo $canspeak? "No any data\n" :"";
}

// 关闭连接
$conn->close();
print_r($usersAres);

// foreach 循环框架
foreach ($usersArray as $id => $seiue_sessid) {
    if($seiue_sessid == ""){
        // skip those who have missing `seiue_sessid` to avoid unmeaningful errors
        echo $canspeak ? "User $id skipped for having no available seiue_sessid.\n" : "";
        continue;
    }
    if($usersAres[$id] == ""){
        // skip those who have missing `seiue_uid` to avoid unmeaningful errors
        echo $canspeak ? "User $id skipped for having no available ares.\n":"";
        continue;
    }
    // 初始化 cURL 会话
    $ch = curl_init();
    
    // 设置请求的 URL
    curl_setopt($ch, CURLOPT_URL, 'https://passport.seiue.com/authorize');
    $this_activeref = $usersAres[$id];
    // 设置请求头
    $headers = [
       'accept: application/json, text/plain, */*',
       'accept-language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
        'cache-control: no-cache',
        'content-type: application/x-www-form-urlencoded',
        'cookie: PHPSESSID=' . $seiue_sessid . '; active_reflection=' . $this_activeref
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
    
    if (( !json_decode($response, true)["access_token"]) || (!json_decode($response, true)["active_reflection_id"]) ) {
    
        echo  $canspeak ? "Sorry; we did not get any data for user $id. We will try again later. Response:". $response."\n" : "";
    }
    $result = file_put_contents("./credentials/keys-$id.auth", json_decode($response, true)["access_token"]);
    $the_key = json_decode($response, true)["access_token"];
    $the_key2 = json_decode($response, true)["active_reflection_id"];
    if ($result === false) {
    
        echo $canspeak ? "Could not write at user $id.\n" :"";
    }
    else {
    
        $finally = file_put_contents("./credentials/activeref-$id.auth",json_decode($response, true)["active_reflection_id"]);
        if ( $finally === false){
            echo $canspeak ?"Failed to write active ref at user $id.\n" :"";
        }
        else{
            echo $canspeak ?"Done for user $id." . "The Keys: '$the_key' and activeref '$the_key2'.\n" :"";
        }
    }
}


?>