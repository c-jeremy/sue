<?php
// 设置目标URL

if (empty($_GET['url'])) {
    // 如果'url'为空，则尝试获取'urlf'的值
    $url = isset($_GET['urlf']) ? $_GET['urlf'] : '';
} else {
    // 如果'url'不为空，则获取'url'的值
    $url = 'http://api.seiue.com/chalk/netdisk/files/'. $_GET['url']. '/url';
}
//$url = 'http://api.seiue.com/chalk/netdisk/files/aaddbd8fd83ad84f07da5d07421ba4ee/url';

$filename = empty($_GET['fname']) ? $url : $_GET['fname'];

// 初始化cURL会话
$ch = curl_init($url);

// 设置cURL选项以获取响应头
curl_setopt($ch, CURLOPT_NOBODY, true); // 不需要响应体
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 将响应作为字符串返回
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // 不跟随重定向
curl_setopt($ch, CURLOPT_HEADER, true); // 返回响应头

// 执行cURL请求
$response = curl_exec($ch);

// 检查是否有错误发生
if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch);
} else {
    // 获取响应状态码
    $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // 解析响应头
    $headers = explode("\r\n", substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE)));

    // 初始化Location变量
    $location = '';

    // 遍历响应头以查找Location
    foreach ($headers as $header) {
        if (strtolower(substr($header, 0, 9)) == 'location:') {
            // 移除'Location:'前缀并存储到变量中
            $location = trim(substr($header, 9));
            break;
        }
    }

    // 输出Location值
    if ($_GET['ru']) {
        echo $location;
        exit;
        //echo "Location header value: " . $location;
    } else {
        //echo "Location header not found.";
    }
    // 初始化cURL会话
    $ch2 = curl_init($location);
    
    // 设置cURL选项
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true); // 将响应作为字符串返回
    curl_setopt($ch2, CURLOPT_HTTPGET, true); // 设置为GET请求
    
    // 设置HTTP头
    $headers2 = [
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
        'Accept: */*',
        'Host: oss-seiue-attachment.seiue.com',
        'Referer: https://chalk-c3.seiue.com/',
        'Origin: null',
        'Connection: keep-alive'
    ];
    curl_setopt($ch2, CURLOPT_HTTPHEADER,$headers2);
    
    // 执行cURL请求
    $response2 = curl_exec($ch2);
    
    // 检查是否有错误发生
    if (curl_errno($ch2)) {
        echo 'cURL error: ' . curl_error($ch2);
    } else {
        // 获取响应状态码
        $httpStatusCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        
        // 检查响应状态码
        if ($httpStatusCode2 == 200) {
            // 获取Content-Type
            $contentType2 = curl_getinfo($ch2, CURLINFO_CONTENT_TYPE);
            // 设置正确的Content-Type头
            header('Content-Type: ' . $contentType2);
            //header('Content-Disposition: attachment; filename="' . $filename . '"');
            // 输出图片内容
            echo $response2;
        } else {
            echo "Failed to retrieve the file. HTTP status code: " . $httpStatusCode2;
        }
    }
    // 关闭cURL会话
    curl_close($ch2);
}
// 关闭cURL会话
curl_close($ch);
?>



