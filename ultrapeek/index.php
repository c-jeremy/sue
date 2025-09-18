<?php
header('Content-Type: application/json');

function isWithinNext20HoursOrExpired($dateTimeString) {
    // 将输入的日期时间字符串转换为DateTime对象
    if ($dateTimeString === "UNSET"){

        return 0;
    }
    $inputDateTime = new DateTime($dateTimeString);

    // 获取当前时间
    $currentDateTime = new DateTime();

    // 计算当前时间之后20小时的时间点
    $futureDateTime = clone $currentDateTime;
    $futureDateTime->modify('+20 hours');

    // 判断输入的时间是否在当前时间之后的20小时之内或者已经过期
    if ($inputDateTime <= $futureDateTime) {
        return 1;
    } else {
        return 0;
    }
}

// 读取请求体
/*
$input = file_get_contents('php://input');
$requestData = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// 验证请求参数
if (!isset($requestData['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid user_id parameter']);
    exit;
}

$user_id = $requestData['user_id'];*/

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid user_id parameter']);
    exit;
}
$user_id = $_GET["user_id"];


$ares = file_get_contents("../credentials/activeref-".$user_id.".auth");
if ($ares === false) {
    echo json_encode(['error' => 'No ares']);
    exit;
}
$url = 'https://api.seiue.com/chalk/me/received-messages?expand=sender_reflection&owner.id='.$ares.'&sort=-published_at%2C-created_at&type=message';

$res = file_get_contents("../credentials/keys-".$user_id.".auth");
if ($res === false) {
    echo json_encode(['error' => 'No res']);
    exit;
}

$headers = [
    'Accept: application/json, text/plain, */*',
    'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
    'Authorization: Bearer ' . $res,
    'Connection: keep-alive',
    'Origin: https://chalk-c3.seiue.com',
    'Referer: https://chalk-c3.seiue.com/',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-site',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0',
    'X-Reflection-Id:'.$ares,
    'X-Role: student',
    'X-School-Id: 3',
    'sec-ch-ua: "Chromium";v="130", "Microsoft Edge";v="130", "Not?A_Brand";v="99"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"'
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

if(curl_errno($ch)) {
   echo json_encode(['error' => 'Curl err']);
   curl_close($ch);
    exit;
}

curl_close($ch);

// Decode the JSON response
$messages = json_decode($response, true);

// Initialize an array to store the extracted data
$items = [];

foreach ($messages as $message) {
    /*$content = json_decode($message['content'], true);
    $textContent = '';
    $fullContent = '';
    $urls = [];

    foreach ($content['blocks'] as $block) {
        $textContent .= $block['text'] . ' ';
        $fullContent .= '<p>' . htmlspecialchars($block['text']) . '</p>';

        // Extract URLs from entityRanges
        if (isset($block['entityRanges']) && !empty($block['entityRanges'])) {
            foreach ($block['entityRanges'] as $entityRange) {
                $entityKey = $entityRange['key'];
                if (isset($content['entityMap'][$entityKey]) && $content['entityMap'][$entityKey]['type'] === 'LINK') {
                    $urls[] = $content['entityMap'][$entityKey]['data']['url'];
                }
            }
        }
    }

    $textContent = substr($textContent, 0, 100) . '...'; // Preview of content

    // Add URLs to fullContent if any exist
    if (!empty($urls)) {
        $fullContent .= '<p class="font-bold mt-4">URLs:</p><ul>';
        foreach ($urls as $url) {
            $fullContent .= '<li><a href="' . htmlspecialchars($url) . '" target="_blank" class="text-pink-500 hover:underline">' . htmlspecialchars($url) . '</a></li>';
        }
        $fullContent .= '</ul>';
    }*/

    $items[] = [
        'title' => $message['title'],
        'sign' => isset($message['sign']) ? $message['sign'] : $message['sender']['name']
    ];
}

$latest_notice_items = array_slice($items, 0, 3);

$url = 'https://api.seiue.com/chalk/todo/executors/'.$ares.'/todos?expand=related&page=1&paginated=1&per_page=4&status=pending';

$headers = [
    'Accept: application/json, text/plain, */*',
    'Accept-Language: zh-CN,zh;q=0.9',
    'Authorization: Bearer ' . $res,
    'Connection: keep-alive',
    'Origin: https://chalk-c3.seiue.com',
    'Referer: https://chalk-c3.seiue.com/',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-site',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0',
    'X-Reflection-Id:'.$ares,
    'X-Role: student',
    'X-School-Id: 3',
    'sec-ch-ua: "Chromium";v="130", "Microsoft Edge";v="130", "Not?A_Brand";v="99"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"'
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
$data = json_decode($response, true);

// Initialize an array to store the extracted data
$items = [];



foreach ($data as $item) {
    // Extract the required fields and store them in the array
   /*if(isWithinNext20HoursOrExpired(isset($item["related"]["expired_at"]) ? $item["related"]["expired_at"] : "UNSET")){*/
        
           $items[] = [
        'title' => $item['title'],
       'expire' => $item['related']['expired_at'] ?? 'Not set',
        'sign' => $item['related']['creator']['name'] ?? "Anonymous"
    ];
    // }
 
}

$userTasks = array_merge($items, $latest_notice_items);

// 返回结果
http_response_code(200);
echo json_encode(['tasks' => array_values($items), 'notices' => array_values($latest_notice_items)]);
?>