<?php

$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();

if ($_REQUEST["id"]){
    $_SESSION["user_id"] = $_REQUEST["id"];
}

require_once "./credentials.php";
require_once "./logger.php";
require_once "./djeh.php";

$page = $_GET['page'] ?? 1;

$url = 'https://api.seiue.com/chalk/todo/executors/'.$ares.'/todos?expand=related&page='.$page.'&paginated=1&per_page=24&status=pending';

if ($_GET["filter"] === "yes") {
    __($_SESSION["user_id"], "Viewed tasks list with filter", $_ENV["ENV"], 1);

    $params = [
        'expand' => 'related',
        'paginated' => '1',
        'per_page' => '24',
        'sort' => '-created_at',
        'page' => $page,
    ];

    // Add date filters if set
    foreach (['endDate' => 'created_at_elt', 'startDate' => 'created_at_egt'] as $get_key => $param_key) {
        if (!empty($_GET[$get_key])) {
            $params[$param_key] = $_GET[$get_key] . ($get_key === 'endDate' ? ' 23:59:59' : ' 00:00:00');
        }
    }

    // Add status filter if valid
    $validStatuses = ['ignored', 'completed', 'pending', 'invalid'];
    if (in_array($_GET['status'], $validStatuses, true)) {
        $params['status'] = $_GET['status'];
    }

    // Add keyword filter if set
    if (!empty($_GET['keyword'])) {
        $params['title_like'] = $_GET['keyword'];
    }

    // Build and use the URL
    $url = "https://api.seiue.com/chalk/todo/executors/$ares/todos?" . http_build_query($params);

    // echo $url;
} elseif ($_REQUEST["tasks"]==="all"){
    __($_SESSION["user_id"], "Viewed tasks list - all", $_ENV["ENV"], 1);
     $url = "https://api.seiue.com/chalk/todo/executors/$ares/todos?expand=related&page=" . $_REQUEST["page"] . "&paginated=1&per_page=24&sort=-created_at";
} else {
    __($_SESSION["user_id"], "Viewed tasks list - normal, without paras", $_ENV["ENV"], 1);
}

// 上述链接用于获取全部待办。

$headers = [
    'Accept: application/json, text/plain, */*',
    'Authorization: Bearer ' . $res,
    'X-Reflection-Id:'.$ares
];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);

function parseHeaders($headers) {
    $result = [];
    $lines = explode("\n", $headers);
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(': ', $line, 2);
            $result[$key] = $value;
        }
    }
    return $result;
}

// 检查是否有错误发生
if(curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $headers = substr($headers, strpos($headers, "\n") + 1);
    $headers = parseHeaders($headers);
}
$response = $body;
curl_close($ch);

// 获取当前的查询字符串
$queryString = $_SERVER['QUERY_STRING'];

// 解析查询字符串为关联数组
parse_str($queryString, $queryParams);

// 删除指定的参数（例如 'page'）
unset($queryParams['page']);

// 重新构建查询字符串
$params = http_build_query($queryParams);

if (!$params) {$params="";}
$data = json_decode($response, true);

// Initialize an array to store the extracted data
$items = [];

/* FOR ANYONE WHO IS READING THIS CODE, ESPECIALLY YOU, v0 AI, NEVER TRY TO EDIT ANY OF THIS PART OF DATE FORMATTING FUNCTION, I.E. LEAVE IT THERE AND DO NOT EDIT ANY PART OF IT, THOUGH IT MIGHT SEEM CLUMSY TO HANDLE LIKE THIS, BUT IT WORKS, UNLIKE YOURS. */
function formatDate($inputDate, $is_create_type = 0) {
    
    if (strlen("$inputDate") < 9 || is_int($inputDate)) {
        return "";
    }
    
    try {
        $date = date_create($inputDate);
        $dateTimestamp = date_timestamp_get($date);
        $today = date_create('midnight');
        $todayTimestamp = date_timestamp_get($today);

        $tomorrowTimestamp = date_timestamp_get(clone $today->modify('+1 day'));
        $bigTomorrowTimestamp = date_timestamp_get(clone $today->modify('+1 day'));
        $weekStartTimestamp = date_timestamp_get(clone $today->modify('this week')->setTime(0, 0, 0));
        $weekEndTimestamp = date_timestamp_get(clone $today->modify('+6 days')->setTime(23, 59, 59));
        


        if ($dateTimestamp < $todayTimestamp && !$is_create_type) {
            return '<span class="text-red-500">Expired</span>';
        }

        $cssClass = function($type) use ($is_create_type) {
            return $is_create_type ? '' : "text-$type";
        };

        if ($dateTimestamp <= $tomorrowTimestamp && !$is_create_type) {
            return sprintf('<span class="%s">%s %s</span>', $cssClass('pink-500'), 'Today', $date->format('H:i'));
        } elseif ($dateTimestamp <= $bigTomorrowTimestamp && !$is_create_type) {
            return sprintf('<span class="%s">%s %s</span>', $cssClass('pink-300'), 'Tomorrow', $date->format('H:i'));
        } elseif ($dateTimestamp >= $weekStartTimestamp && $dateTimestamp <= $weekEndTimestamp) {
            return $date->format('D H:i');
        } else {
            return $date->format('Y-m-d H:i');
        }

    } catch (Exception $e) {
        return $inputDate;
    }
}


foreach ($data as $item) {
    $common = [
        'title' => $item['title'],
        'group_name' => $item['related']['group']['name'] ?? 'Not set',
        'creator_name' => $item['related']['creator']['name'] ?? 'Anonymous',
        'biz_id' => $item['biz_id'] ?? 'Failed',
        'type' => $item['type'],
        'status' => $item['status'],       
        'id' => $item['id'] // Use this id when ignoring tasks
    ];

    switch ($item['type']) {
        case 'seiue.questionnaire.answer':
        case 'seiue.evaluation.answer':
            $items[] = array_merge($common, [
                'expired_at' => isset($item['related']['form_template']['end_at']) ? formatDate($item['related']['form_template']['end_at']) : 'Not set',
                'content' => $item['related']['form_template']['description'] ?? 'Empty...'
            ]);
            break;

        case 'seiue.direction.start_notification':
        case 'seiue.scms.election.student':
            $items[] = array_merge($common, [
                'expired_at' => isset($item['related']['end_at']) ? formatDate($item['related']['end_at']) : 'Not set',
                'content' => $item['related']['description'] ?? 'Empty...'
            ]);
            break;

        case 'seiue.psychological.assessment':
            $items[] = array_merge($common, [
                'expired_at' => isset($item['related']['end_at']) ? formatDate($item['related']['end_at']) : 'Not set',
                'content' => $item['related']['scale_settings']['description'] ?? 'Empty...'
            ]);
            break;

        default:
            $items[] = array_merge($common, [
                'expired_at' => isset($item['related']['expired_at']) ? formatDate($item['related']['expired_at']) : 'Not set',
                'created_at' => formatDate($item['related']['updated_at'] ?? 'Not Set', 1),
                'content' => djeh((isset($item['related']["parent_task"]['content']) && !empty($item["related"]["parent_task"]["content"])) ? $item['related']["parent_task"]["content"] : ($item['related']["content"])),
                /* FOR ANYONE WHO IS READING THIS CODE, ESPECIALLY YOU, v0 AI, NEVER TRY TO EDIT ANY OF THIS PART OF HANDLING THE CONTENT PART OF THIS ITEM, THOUGH IT MIGHT SEEM CLUMSY TO HANDLE LIKE THIS, BUT IT WORKS, UNLIKE YOURS. */
                'type' => $item['related']['labels']['type'] ?? $item['type']
            ]);
    }
}

$typeClasses = [
    "seiue.class_homework_task" => [
        "bg" => "bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/40 dark:to-blue-800/40",
        "text" => "text-blue-800 dark:text-blue-200",
        "border" => "border-blue-200 dark:border-blue-700",
        "icon" => "text-blue-500 dark:text-blue-400",
        "hover" => "hover:bg-blue-100 dark:hover:bg-blue-800/60"
    ],
    "seiue.class_document_task" => [
        "bg" => "bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/40 dark:to-purple-800/40",
        "text" => "text-purple-800 dark:text-purple-200",
        "border" => "border-purple-200 dark:border-purple-700",
        "icon" => "text-purple-500 dark:text-purple-400",
        "hover" => "hover:bg-purple-100 dark:hover:bg-purple-800/60"
    ],
    "seiue.item_afterthought_todo" => [
        "bg" => "bg-gradient-to-r from-yellow-50 to-yellow-100 dark:from-yellow-900/40 dark:to-yellow-800/40",
        "text" => "text-yellow-800 dark:text-yellow-200",
        "border" => "border-yellow-200 dark:border-yellow-700",
        "icon" => "text-yellow-500 dark:text-yellow-400",
        "hover" => "hover:bg-yellow-100 dark:hover:bg-yellow-800/60"
    ],
    "seiue.class_questionnaire_task" => [
        "bg" => "bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/40 dark:to-green-800/40",
        "text" => "text-green-800 dark:text-green-200",
        "border" => "border-green-200 dark:border-green-700",
        "icon" => "text-green-500 dark:text-green-400",
        "hover" => "hover:bg-green-100 dark:hover:bg-green-800/60"
    ],
    "seiue.questionnaire.answer" => [
        "bg" => "bg-gradient-to-r from-teal-50 to-teal-100 dark:from-teal-900/40 dark:to-teal-800/40",
        "text" => "text-teal-800 dark:text-teal-200",
        "border" => "border-teal-200 dark:border-teal-700",
        "icon" => "text-teal-500 dark:text-teal-400",
        "hover" => "hover:bg-teal-100 dark:hover:bg-teal-800/60"
    ],
    "seiue.evaluation.answer" => [
        "bg" => "bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/40 dark:to-orange-800/40",
        "text" => "text-orange-800 dark:text-orange-200",
        "border" => "border-orange-200 dark:border-orange-700",
        "icon" => "text-orange-500 dark:text-orange-400",
        "hover" => "hover:bg-orange-100 dark:hover:bg-orange-800/60"
    ],
    "seiue.psychological.assessment" => [
        "bg" => "bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/40 dark:to-indigo-800/40",
        "text" => "text-indigo-800 dark:text-indigo-200",
        "border" => "border-indigo-200 dark:border-indigo-700",
        "icon" => "text-indigo-500 dark:text-indigo-400",
        "hover" => "hover:bg-indigo-100 dark:hover:bg-indigo-800/60"
    ],
    "online_class.unbind_to_reflection" => [
        "bg" => "bg-gradient-to-r from-cyan-50 to-cyan-100 dark:from-cyan-900/40 dark:to-cyan-800/40",
        "text" => "text-cyan-800 dark:text-cyan-200",
        "border" => "border-cyan-200 dark:border-cyan-700",
        "icon" => "text-cyan-500 dark:text-cyan-400",
        "hover" => "hover:bg-cyan-100 dark:hover:bg-cyan-800/60"
    ],
    "chat.submit_evaluation" => [
        "bg" => "bg-gradient-to-r from-lime-50 to-lime-100 dark:from-lime-900/40 dark:to-lime-800/40",
        "text" => "text-lime-800 dark:text-lime-200",
        "border" => "border-lime-200 dark:border-lime-700",
        "icon" => "text-lime-500 dark:text-lime-400",
        "hover" => "hover:bg-lime-100 dark:hover:bg-lime-800/60"
    ],
    "handout.answer" => [
        "bg" => "bg-gradient-to-r from-amber-50 to-amber-100 dark:from-amber-900/40 dark:to-amber-800/40",
        "text" => "text-amber-800 dark:text-amber-200",
        "border" => "border-amber-200 dark:border-amber-700",
        "icon" => "text-amber-500 dark:text-amber-400",
        "hover" => "hover:bg-amber-100 dark:hover:bg-amber-800/60"
    ],
    "seiue.scms.election.student" => [
        "bg" => "bg-gradient-to-r from-fuchsia-50 to-fuchsia-100 dark:from-fuchsia-900/40 dark:to-fuchsia-800/40",
        "text" => "text-fuchsia-800 dark:text-fuchsia-200",
        "border" => "border-fuchsia-200 dark:border-fuchsia-700",
        "icon" => "text-fuchsia-500 dark:text-fuchsia-400",
        "hover" => "hover:bg-fuchsia-100 dark:hover:bg-fuchsia-800/60"
    ],
    "seiue.direction.start_notification" => [
        "bg" => "bg-gradient-to-r from-sky-50 to-sky-100 dark:from-sky-900/40 dark:to-sky-800/40",
        "text" => "text-sky-800 dark:text-sky-200",
        "border" => "border-sky-200 dark:border-sky-700",
        "icon" => "text-sky-500 dark:text-sky-400",
        "hover" => "hover:bg-sky-100 dark:hover:bg-sky-800/60"
    ]
];

$typeIcons = [
    "seiue.class_homework_task" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>',
    "seiue.class_document_task" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
    "seiue.item_afterthought_todo" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
    "seiue.class_questionnaire_task" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>',
    "seiue.questionnaire.answer" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
    "seiue.evaluation.answer" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
    "seiue.psychological.assessment" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>',
    "online_class.unbind_to_reflection" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>',
    "chat.submit_evaluation" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>',
    "handout.answer" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>',
    "seiue.scms.election.student" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>',
    "seiue.direction.start_notification" => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>'
];

$typeNames = [
    "seiue.class_homework_task" => "作业",
    "seiue.class_document_task" => "资料",
    "seiue.item_afterthought_todo" => "成绩",
    "seiue.class_questionnaire_task" => "问卷任务",
    "seiue.questionnaire.answer" => "问卷",
    "seiue.evaluation.answer" => "评教评学",
    "seiue.psychological.assessment" => "心理",
    "online_class.unbind_to_reflection" => "插件",
    "chat.submit_evaluation" => "约谈",
    "handout.answer" => "学案",
    "seiue.scms.election.student" => "选课",
    "seiue.direction.start_notification" => "选科"
];

$statuses = [
    '' => 'All',
    'ignored' => 'Ignored',
    'completed' => 'Completed',
    'pending' => 'Pending',
    'invalid' => 'Invalid'
];
//print_r($items);
$ohno = ($_REQUEST["from_login"]==="yes") ? 1 : 0;

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>任务管理系统</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ===== 设计系统 - 极简主义美学 ===== */
        :root {
            /* 主色调 - 高级灰与蓝色调和 */
            --primary-hue: 210;
            --primary-saturation: 60%;
            --primary-lightness: 50%;
            
            /* 辅助色调 - 温暖点缀 */
            --accent-hue: 25;
            --accent-saturation: 80%;
            --accent-lightness: 55%;
            
            /* 状态色调 */
            --success-hue: 145;
            --error-hue: 358;
            --warning-hue: 35;
            --info-hue: 200;
            
            /* 中性色调 - 精致灰阶 */
            --gray-50: hsl(210, 20%, 98%);
            --gray-100: hsl(210, 16%, 96%);
            --gray-200: hsl(210, 14%, 91%);
            --gray-300: hsl(210, 12%, 86%);
            --gray-400: hsl(210, 10%, 70%);
            --gray-500: hsl(210, 8%, 50%);
            --gray-600: hsl(210, 10%, 40%);
            --gray-700: hsl(210, 12%, 30%);
            --gray-800: hsl(210, 14%, 20%);
            --gray-900: hsl(210, 16%, 12%);
            --gray-950: hsl(210, 18%, 8%);
            
            /* 亮色模式 */
            --text-primary: var(--gray-900);
            --text-secondary: var(--gray-700);
            --text-tertiary: var(--gray-500);
            --bg-primary: #ffffff;
            --bg-secondary: var(--gray-50);
            --bg-tertiary: var(--gray-100);
            --border-color: var(--gray-200);
            --border-color-hover: var(--gray-300);
            
            /* 暗色模式 */
            --dark-text-primary: var(--gray-100);
            --dark-text-secondary: var(--gray-300);
            --dark-text-tertiary: var(--gray-400);
            --dark-bg-primary: var(--gray-950);
            --dark-bg-secondary: var(--gray-900);
            --dark-bg-tertiary: var(--gray-800);
            --dark-border-color: var(--gray-800);
            --dark-border-color-hover: var(--gray-700);
            
            /* 精致阴影系统 */
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.03);
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.03), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.07);
            --shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.03);
            
            /* 暗色模式阴影 */
            --dark-shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.2);
            --dark-shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.25), 0 1px 2px rgba(0, 0, 0, 0.3);
            --dark-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            --dark-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
            --dark-shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            --dark-shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            --dark-shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.2);
            
            /* 圆角系统 - 精致圆角 */
            --radius-xs: 0.125rem;
            --radius-sm: 0.25rem;
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
            --radius-xl: 0.75rem;
            --radius-2xl: 1rem;
            --radius-3xl: 1.5rem;
            --radius-full: 9999px;
            
            /* 动画时间 */
            --transition-fast: 150ms;
            --transition-normal: 250ms;
            --transition-slow: 350ms;
            --transition-very-slow: 500ms;
            
            /* 动画曲线 */
            --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-out: cubic-bezier(0, 0, 0.2, 1);
            --ease-in: cubic-bezier(0.4, 0, 1, 1);
            --ease-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
            --ease-elastic: cubic-bezier(0.68, -0.55, 0.265, 1.55);
            
            /* 间距系统 */
            --spacing-1: 0.25rem;
            --spacing-2: 0.5rem;
            --spacing-3: 0.75rem;
            --spacing-4: 1rem;
            --spacing-5: 1.25rem;
            --spacing-6: 1.5rem;
            --spacing-8: 2rem;
            --spacing-10: 2.5rem;
            --spacing-12: 3rem;
            --spacing-16: 4rem;
            --spacing-20: 5rem;
            
            /* 字体系统 */
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            --font-serif: Georgia, Cambria, "Times New Roman", Times, serif;
            --font-mono: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            
            /* 字体大小 */
            --text-xs: 0.75rem;
            --text-sm: 0.875rem;
            --text-base: 1rem;
            --text-lg: 1.125rem;
            --text-xl: 1.25rem;
            --text-2xl: 1.5rem;
            --text-3xl: 1.875rem;
            --text-4xl: 2.25rem;
            
            /* 字体粗细 */
            --font-thin: 100;
            --font-extralight: 200;
            --font-light: 300;
            --font-normal: 400;
            --font-medium: 500;
            --font-semibold: 600;
            --font-bold: 700;
            --font-extrabold: 800;
            --font-black: 900;
            
            /* 行高 */
            --leading-none: 1;
            --leading-tight: 1.25;
            --leading-snug: 1.375;
            --leading-normal: 1.5;
            --leading-relaxed: 1.625;
            --leading-loose: 2;
            
            /* Z-index系统 */
            --z-0: 0;
            --z-10: 10;
            --z-20: 20;
            --z-30: 30;
            --z-40: 40;
            --z-50: 50;
            --z-modal: 100;
            --z-tooltip: 110;
            --z-toast: 120;
        }

        /* ===== 基础样式 ===== */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        html {
            font-size: 16px;
            scroll-behavior: smooth;
        }
        
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 100 900;
            font-display: swap;
            src: url(https://fonts.gstatic.com/s/inter/v12/UcC73FwrK3iLTeHuS_fvQtMwCp50KnMa1ZL7.woff2) format('woff2');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        
        body {
            font-family: var(--font-sans);
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: var(--leading-normal);
            transition: background-color var(--transition-normal) var(--ease-in-out),
                        color var(--transition-normal) var(--ease-in-out);
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* 暗色模式基础样式 */
        .dark {
            color-scheme: dark;
        }
        
        .dark body {
            background-color: var(--dark-bg-secondary);
            color: var(--dark-text-primary);
        }
        
        /* ===== 布局容器 ===== */
        .container {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 var(--spacing-4);
        }
        
        @media (min-width: 640px) {
            .container {
                padding: 0 var(--spacing-6);
            }
        }
        
        @media (min-width: 1024px) {
            .container {
                padding: 0 var(--spacing-8);
            }
        }
        
        /* ===== 顶部导航栏 ===== */
        .navbar {
            position: sticky;
            top: 0;
            z-index: var(--z-30);
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            transition: all var(--transition-normal) var(--ease-in-out);
        }
        
        .dark .navbar {
            background-color: rgba(10, 12, 16, 0.8);
            border-bottom: 1px solid var(--dark-border-color);
        }
        
        .navbar-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 4.5rem;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
        }
        
        .navbar-logo {
            font-size: var(--text-xl);
            font-weight: var(--font-semibold);
            letter-spacing: -0.025em;
            color: var(--text-primary);
            text-decoration: none;
            transition: all var(--transition-normal) var(--ease-in-out);
            position: relative;
            padding-left: var(--spacing-4);
        }
        
        .navbar-logo::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 1.5em;
            background: linear-gradient(
                to bottom,
                hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness)),
                hsl(var(--accent-hue), var(--accent-saturation), var(--accent-lightness))
            );
            border-radius: var(--radius-full);
            transition: all var(--transition-normal) var(--ease-in-out);
        }
        
        .dark .navbar-logo {
            color: var(--dark-text-primary);
        }
        
        .navbar-logo:hover::before {
            height: 2em;
        }
        
        .navbar-actions {
            display: flex;
            align-items: center;
            gap: var(--spacing-4);
        }
        
        /* ===== 主内容区域 ===== */
        .main {
            flex: 1;
            padding: var(--spacing-10) 0;
        }
        
        /* ===== 页面标题 ===== */
        .page-header {
            margin-bottom: var(--spacing-10);
            text-align: center;
            position: relative;
        }
        
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -1.5rem;
            left: 50%;
            transform: translateX(-50%);
            width: 3rem;
            height: 3px;
            background: linear-gradient(
                to right,
                hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness)),
                hsl(var(--accent-hue), var(--accent-saturation), var(--accent-lightness))
            );
            border-radius: var(--radius-full);
        }
        
        .page-title {
            font-size: var(--text-3xl);
            font-weight: var(--font-bold);
            letter-spacing: -0.025em;
            margin-bottom: var(--spacing-3);
            color: var(--text-primary);
            position: relative;
            display: inline-block;
        }
        
        .dark .page-title {
            color: var(--dark-text-primary);
        }
        
        .page-subtitle {
            font-size: var(--text-lg);
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
            font-weight: var(--font-light);
            line-height: var(--leading-relaxed);
        }
        
        .dark .page-subtitle {
            color: var(--dark-text-secondary);
        }
        
        /* ===== 过滤器区域 ===== */
        .filters-container {
            position: relative;
            margin-bottom: var(--spacing-10);
            padding-bottom: var(--spacing-4);
            overflow: hidden;
        }
        
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-3);
            justify-content: center;
            position: relative;
        }
        
        .filter-item {
            padding: var(--spacing-2) var(--spacing-4);
            border-radius: var(--radius-full);
            background-color: transparent;
            color: var(--text-secondary);
            font-weight: var(--font-medium);
            font-size: var(--text-sm);
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all var(--transition-normal) var(--ease-in-out);
            user-select: none;
            position: relative;
            overflow: hidden;
        }
        
        .dark .filter-item {
            color: var(--dark-text-secondary);
            border-color: var(--dark-border-color);
        }
        
        .filter-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                135deg,
                hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.1),
                hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0)
            );
            opacity: 0;
            transition: opacity var(--transition-normal) var(--ease-in-out);
        }
        
        .filter-item:hover {
            transform: translateY(-2px);
            border-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.3);
            color: var(--text-primary);
            box-shadow: var(--shadow-sm);
        }
        
        .dark .filter-item:hover {
            border-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.3);
            color: var(--dark-text-primary);
            box-shadow: var(--dark-shadow-sm);
        }
        
        .filter-item:hover::before {
            opacity: 1;
        }
        
        .filter-item.active {
            background-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.1);
            color: hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness));
            border-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.3);
            font-weight: var(--font-semibold);
        }
        
        .dark .filter-item.active {
            background-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.1);
            color: hsl(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%));
            border-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.3);
        }
        
        /* ===== 任务卡片网格 ===== */
        .task-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--spacing-6);
        }
        
        @media (min-width: 640px) {
            .task-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            }
        }
        
        @media (min-width: 1024px) {
            .task-grid {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
        }
        
        /* ===== 任务卡片 - 极简主义设计 ===== */
        .task-card {
            position: relative;
            border-radius: var(--radius-lg);
            background-color: var(--bg-primary);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: all var(--transition-normal) var(--ease-in-out);
            transform: translateZ(0);
            will-change: transform, box-shadow;
            height: 100%;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            border: 1px solid var(--border-color);
        }
        
        .dark .task-card {
            background-color: var(--dark-bg-primary);
            border-color: var(--dark-border-color);
            box-shadow: var(--dark-shadow-md);
        }
        
        .task-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                135deg,
                hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.05),
                hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0)
            );
            opacity: 0;
            transition: opacity var(--transition-normal) var(--ease-in-out);
            pointer-events: none;
        }
        
        .task-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .dark .task-card:hover {
            box-shadow: var(--dark-shadow-lg);
        }
        
        .task-card:hover::after {
            opacity: 1;
        }
        
        .task-card-header {
            padding: var(--spacing-5);
            position: relative;
        }
        
        .task-card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: var(--spacing-5);
            right: var(--spacing-5);
            height: 1px;
            background: linear-gradient(
                to right,
                hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.2),
                hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0)
            );
            opacity: 0;
            transition: opacity var(--transition-normal) var(--ease-in-out);
        }
        
        .task-card:hover .task-card-header::before {
            opacity: 1;
        }
        
        .task-card-title {
            font-size: var(--text-lg);
            font-weight: var(--font-semibold);
            color: var(--text-primary);
            margin-bottom: var(--spacing-3);
            line-height: var(--leading-snug);
            letter-spacing: -0.01em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color var(--transition-normal) var(--ease-in-out);
        }
        
        .dark .task-card-title {
            color: var(--dark-text-primary);
        }
        
        .task-card:hover .task-card-title {
            color: hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness));
        }
        
        .dark .task-card:hover .task-card-title {
            color: hsl(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%));
        }
        
        .task-card-meta {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            margin-bottom: var(--spacing-3);
        }
        
        .task-card-type {
            display: inline-flex;
            align-items: center;
            padding: var(--spacing-1) var(--spacing-3);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: var(--font-medium);
            background-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.08);
            color: hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness));
            transition: all var(--transition-normal) var(--ease-in-out);
        }
        
        .dark .task-card-type {
            background-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.08);
            color: hsl(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%));
        }
        
        .task-card:hover .task-card-type {
            background-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.12);
            transform: translateY(-1px);
        }
        
        .dark .task-card:hover .task-card-type {
            background-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.12);
        }
        
        .task-card-date {
            font-size: var(--text-xs);
            color: var(--text-tertiary);
            display: flex;
            align-items: center;
            gap: var(--spacing-1);
        }
        
        .dark .task-card-date {
            color: var(--dark-text-tertiary);
        }
        
        .task-card-body {
            padding: 0 var(--spacing-5) var(--spacing-5);
            flex: 1;
        }
        
        .task-card-description {
            color: var(--text-secondary);
            font-size: var(--text-sm);
            line-height: var(--leading-relaxed);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color var(--transition-normal) var(--ease-in-out);
        }
        
        .dark .task-card-description {
            color: var(--dark-text-secondary);
        }
        
        .task-card-footer {
            padding: var(--spacing-5);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--border-color);
            transition: border-color var(--transition-normal) var(--ease-in-out);
        }
        
        .dark .task-card-footer {
            border-color: var(--dark-border-color);
        }
        
        .task-card:hover .task-card-footer {
            border-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.1);
        }
        
        .dark .task-card:hover .task-card-footer {
            border-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.1);
        }
        
        .task-card-deadline {
            font-size: var(--text-xs);
            font-weight: var(--font-medium);
            display: flex;
            align-items: center;
            gap: var(--spacing-1);
        }
        
        .deadline-normal {
            color: var(--text-secondary);
        }
        
        .dark .deadline-normal {
            color: var(--dark-text-secondary);
        }
        
        .deadline-soon {
            color: hsl(var(--warning-hue), 90%, 45%);
        }
        
        .dark .deadline-soon {
            color: hsl(var(--warning-hue), 90%, 60%);
        }
        
        .deadline-urgent {
            color: hsl(var(--error-hue), 90%, 50%);
            animation: pulse 2s infinite;
        }
        
        .dark .deadline-urgent {
            color: hsl(var(--error-hue), 90%, 60%);
        }
        
        @keyframes pulse {
            0% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
            100% {
                opacity: 1;
            }
        }
        
        /* ===== 任务卡片操作按钮 - 始终可见 ===== */
        .task-card-actions {
            position: absolute;
            bottom: var(--spacing-5);
            right: var(--spacing-5);
            display: flex;
            gap: var(--spacing-2);
            opacity: 1; /* 始终可见 */
            z-index: var(--z-10);
        }
        
        .task-card-action {
            width: 2rem;
            height: 2rem;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-primary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: all var(--transition-normal) var(--ease-in-out);
            position: relative;
            overflow: hidden;
        }
        
        .dark .task-card-action {
            background-color: var(--dark-bg-primary);
            color: var(--dark-text-secondary);
            border-color: var(--dark-border-color);
            box-shadow: var(--dark-shadow-sm);
        }
        
        .task-card-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, 
                hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.15) 0%, 
                transparent 70%);
            opacity: 0;
            transform: scale(0.5);
            transition: all var(--transition-normal) var(--ease-out);
        }
        
        .dark .task-card-action::before {
            background: radial-gradient(circle at center, 
                hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.15) 0%, 
                transparent 70%);
        }
        
        .task-card-action:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness));
            border-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.3);
        }
        
        .dark .task-card-action:hover {
            box-shadow: var(--dark-shadow-md);
            color: hsl(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%));
            border-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.3);
        }
        
        .task-card-action:hover::before {
            opacity: 1;
            transform: scale(1.5);
        }
        
        .task-card-action:active {
            transform: translateY(0);
        }
        
        /* ===== 工具提示 ===== */
        .tooltip {
            position: relative;
        }
        
        .tooltip::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%) scale(0.8);
            background-color: var(--text-primary);
            color: var(--bg-primary);
            padding: var(--spacing-1) var(--spacing-2);
            border-radius: var(--radius-md);
            font-size: var(--text-xs);
            font-weight: var(--font-medium);
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-fast) var(--ease-out);
            z-index: var(--z-tooltip);
            pointer-events: none;
            box-shadow: var(--shadow-md);
        }
        
        .dark .tooltip::before {
            background-color: var(--dark-text-primary);
            color: var(--dark-bg-primary);
            box-shadow: var(--dark-shadow-md);
        }
        
        .tooltip:hover::before {
            transform: translateX(-50%) scale(1);
            opacity: 1;
            visibility: visible;
        }
        
        /* ===== 模态框设计 ===== */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: var(--z-modal);
            overflow-y: auto;
            padding: var(--spacing-4);
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity var(--transition-normal) var(--ease-in-out);
        }
        
        .dark .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.6);
        }
        
        .modal.active .modal-backdrop {
            opacity: 1;
        }
        
        .modal-content {
            position: relative;
            background-color: var(--bg-primary);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-2xl);
            width: 100%;
            max-width: 42rem;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transform: scale(0.95);
            opacity: 0;
            transition: transform var(--transition-slow) var(--ease-bounce), 
                        opacity var(--transition-normal) var(--ease-in-out);
            z-index: var(--z-10);
            border: 1px solid var(--border-color);
        }
        
        .dark .modal-content {
            background-color: var(--dark-bg-primary);
            box-shadow: var(--dark-shadow-2xl);
            border-color: var(--dark-border-color);
        }
        
        .modal.active .modal-content {
            transform: scale(1);
            opacity: 1;
        }
        
        .modal-header {
            padding: var(--spacing-5);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .dark .modal-header {
            border-color: var(--dark-border-color);
        }
        
        .modal-title {
            font-size: var(--text-xl);
            font-weight: var(--font-semibold);
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }
        
        .dark .modal-title {
            color: var(--dark-text-primary);
        }
        
        .modal-close {
            width: 2rem;
            height: 2rem;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-secondary);
            color: var(--text-secondary);
            border: none;
            cursor: pointer;
            transition: all var(--transition-normal) var(--ease-in-out);
        }
        
        .dark .modal-close {
            background-color: var(--dark-bg-secondary);
            color: var(--dark-text-secondary);
        }
        
        .modal-close:hover {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            transform: rotate(90deg);
        }
        
        .dark .modal-close:hover {
            background-color: var(--dark-bg-tertiary);
            color: var(--dark-text-primary);
        }
        
        .modal-body {
            padding: var(--spacing-5);
            overflow-y: auto;
            flex: 1;
        }
        
        .modal-footer {
            padding: var(--spacing-5);
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: var(--spacing-3);
        }
        
        .dark .modal-footer {
            border-color: var(--dark-border-color);
        }
        
        /* ===== 按钮样式 ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-2) var(--spacing-4);
            border-radius: var(--radius-md);
            font-weight: var(--font-medium);
            font-size: var(--text-sm);
            transition: all var(--transition-normal) var(--ease-in-out);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: none;
            outline: none;
        }
        
        .btn:active {
            transform: translateY(1px);
        }
        
        .btn-primary {
            background-color: hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness));
            color: white;
            box-shadow: 0 2px 4px hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.3);
        }
        
        .btn-primary:hover {
            background-color: hsl(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) - 5%));
            box-shadow: 0 4px 8px hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.4);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }
        
        .dark .btn-secondary {
            background-color: var(--dark-bg-primary);
            color: var(--dark-text-primary);
            border-color: var(--dark-border-color);
            box-shadow: var(--dark-shadow-sm);
        }
        
        .btn-secondary:hover {
            background-color: var(--bg-secondary);
            border-color: var(--border-color-hover);
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }
        
        .dark .btn-secondary:hover {
            background-color: var(--dark-bg-secondary);
            border-color: var(--dark-border-color-hover);
            box-shadow: var(--dark-shadow-md);
        }
        
        /* 按钮涟漪效果 */
        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }
        
        .dark .btn-secondary::after {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0) translate(-50%, -50%);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20) translate(-50%, -50%);
                opacity: 0;
            }
        }
        
        /* ===== 表单元素 ===== */
        .form-group {
            margin-bottom: var(--spacing-4);
        }
        
        .form-label {
            display: block;
            margin-bottom: var(--spacing-2);
            font-weight: var(--font-medium);
            color: var(--text-secondary);
            font-size: var(--text-sm);
        }
        
        .dark .form-label {
            color: var(--dark-text-secondary);
        }
        
        .form-input {
            width: 100%;
            padding: var(--spacing-3) var(--spacing-4);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: var(--text-sm);
            transition: all var(--transition-normal) var(--ease-in-out);
            box-shadow: var(--shadow-inner);
        }
        
        .dark .form-input {
            background-color: var(--dark-bg-primary);
            border-color: var(--dark-border-color);
            color: var(--dark-text-primary);
            box-shadow: var(--dark-shadow-inner);
        }
        
        .form-input:focus {
            outline: none;
            border-color: hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness));
            box-shadow: 0 0 0 3px hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.2);
        }
        
        .dark .form-input:focus {
            border-color: hsl(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%));
            box-shadow: 0 0 0 3px hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.2);
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        /* ===== 文件上传区域 ===== */
        .file-upload-area {
            border: 1px dashed var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-8);
            text-align: center;
            transition: all var(--transition-normal) var(--ease-in-out);
            background-color: var(--bg-secondary);
            cursor: pointer;
        }
        
        .dark .file-upload-area {
            border-color: var(--dark-border-color);
            background-color: var(--dark-bg-secondary);
        }
        
        .file-upload-area:hover, .file-upload-area.dragging {
            border-color: hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness));
            background-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.05);
        }
        
        .dark .file-upload-area:hover, .dark .file-upload-area.dragging {
            border-color: hsl(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%));
            background-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.05);
        }
        
        .file-upload-icon {
            font-size: 2.5rem;
            color: var(--text-tertiary);
            margin-bottom: var(--spacing-3);
        }
        
        .dark .file-upload-icon {
            color: var(--dark-text-tertiary);
        }
        
        .file-upload-text {
            color: var(--text-secondary);
            margin-bottom: var(--spacing-2);
        }
        
        .dark .file-upload-text {
            color: var(--dark-text-secondary);
        }
        
        .file-upload-hint {
            font-size: var(--text-xs);
            color: var(--text-tertiary);
        }
        
        .dark .file-upload-hint {
            color: var(--dark-text-tertiary);
        }
        
        /* ===== 文件列表 ===== */
        .file-list {
            margin-top: var(--spacing-4);
        }
        
        .file-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-3) var(--spacing-4);
            border-radius: var(--radius-md);
            background-color: var(--bg-secondary);
            margin-bottom: var(--spacing-2);
            transition: all var(--transition-normal) var(--ease-in-out);
            border: 1px solid var(--border-color);
        }
        
        .dark .file-list-item {
            background-color: var(--dark-bg-secondary);
            border-color: var(--dark-border-color);
        }
        
        .file-list-item:hover {
            background-color: var(--bg-tertiary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .dark .file-list-item:hover {
            background-color: var(--dark-bg-tertiary);
            box-shadow: var(--dark-shadow-sm);
        }
        
        /* ===== 分页 ===== */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-2);
            margin-top: var(--spacing-10);
        }
        
        .pagination-item {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius-full);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-weight: var(--font-medium);
            transition: all var(--transition-normal) var(--ease-in-out);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            cursor: pointer;
        }
        
        .dark .pagination-item {
            background-color: var(--dark-bg-primary);
            color: var(--dark-text-primary);
            border-color: var(--dark-border-color);
            box-shadow: var(--dark-shadow-sm);
        }
        
        .pagination-item:hover:not(.pagination-active, [aria-disabled="true"]) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness));
            border-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.3);
        }
        
        .dark .pagination-item:hover:not(.pagination-active, [aria-disabled="true"]) {
            box-shadow: var(--dark-shadow-md);
            color: hsl(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%));
            border-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.3);
        }
        
        .pagination-active {
            background-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.1);
            color: hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness));
            border-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.3);
            transform: scale(1.05);
        }
        
        .dark .pagination-active {
            background-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.1);
            color: hsl(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%));
            border-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.3);
        }
        
        .pagination-item[aria-disabled="true"] {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* ===== 主题切换按钮 ===== */
        .theme-toggle {
            position: fixed;
            bottom: var(--spacing-6);
            right: var(--spacing-6);
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            z-index: var(--z-30);
            transition: all var(--transition-normal) var(--ease-in-out);
            cursor: pointer;
        }
        
        .dark .theme-toggle {
            background-color: var(--dark-bg-primary);
            color: var(--dark-text-primary);
            border-color: var(--dark-border-color);
            box-shadow: var(--dark-shadow-lg);
        }
        
        .theme-toggle:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }
        
        .dark .theme-toggle:hover {
            box-shadow: var(--dark-shadow-xl);
        }
        
        .theme-toggle svg {
            width: 1.25rem;
            height: 1.25rem;
            transition: all var(--transition-normal) var(--ease-in-out);
        }
        
        /* ===== 通知提示 ===== */
        #toastContainer {
            position: fixed;
            bottom: var(--spacing-6);
            left: var(--spacing-6);
            display: flex;
            flex-direction: column;
            gap: var(--spacing-3);
            z-index: var(--z-toast);
            pointer-events: none;
        }
        
        .toast {
            background-color: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-4);
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-3);
            transform: translateX(-100%);
            opacity: 0;
            transition: all var(--transition-normal) var(--ease-in-out);
            pointer-events: auto;
            max-width: 24rem;
            border-left: 3px solid transparent;
        }
        
        .dark .toast {
            background-color: var(--dark-bg-primary);
            box-shadow: var(--dark-shadow-lg);
        }
        
        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .toast-success {
            border-left-color: hsl(var(--success-hue), 90%, 45%);
        }
        
        .toast-error {
            border-left-color: hsl(var(--error-hue), 90%, 50%);
        }
        
        .toast-warning {
            border-left-color: hsl(var(--warning-hue), 90%, 45%);
        }
        
        .toast-info {
            border-left-color: hsl(var(--info-hue), 90%, 50%);
        }
        
        .toast-icon {
            width: 1.5rem;
            height: 1.5rem;
            flex-shrink: 0;
        }
        
        .toast-success .toast-icon {
            color: hsl(var(--success-hue), 90%, 45%);
        }
        
        .toast-error .toast-icon {
            color: hsl(var(--error-hue), 90%, 50%);
        }
        
        .toast-warning .toast-icon {
            color: hsl(var(--warning-hue), 90%, 45%);
        }
        
        .toast-info .toast-icon {
            color: hsl(var(--info-hue), 90%, 50%);
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-1);
            color: var(--text-primary);
        }
        
        .dark .toast-title {
            color: var(--dark-text-primary);
        }
        
        .toast-message {
            font-size: var(--text-sm);
            color: var(--text-secondary);
        }
        
        .dark .toast-message {
            color: var(--dark-text-secondary);
        }
        
        .toast-close {
            background: transparent;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            padding: var(--spacing-1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition-normal) var(--ease-in-out);
            border-radius: var(--radius-full);
        }
        
        .dark .toast-close {
            color: var(--dark-text-tertiary);
        }
        
        .toast-close:hover {
            color: var(--text-primary);
            background-color: var(--bg-secondary);
        }
        
        .dark .toast-close:hover {
            color: var(--dark-text-primary);
            background-color: var(--dark-bg-secondary);
        }
        
        /* ===== 徽章 ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-1) var(--spacing-2);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: var(--font-medium);
            transition: all var(--transition-normal) var(--ease-in-out);
        }
        
        .badge-primary {
            background-color: hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.1);
            color: hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness));
        }
        
        .dark .badge-primary {
            background-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.1);
            color: hsl(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%));
        }
        
        .badge-success {
            background-color: hsla(var(--success-hue), 90%, 45%, 0.1);
            color: hsl(var(--success-hue), 90%, 45%);
        }
        
        .dark .badge-success {
            background-color: hsla(var(--success-hue), 90%, 60%, 0.1);
            color: hsl(var(--success-hue), 90%, 60%);
        }
        
        .badge-error {
            background-color: hsla(var(--error-hue), 90%, 50%, 0.1);
            color: hsl(var(--error-hue), 90%, 50%);
        }
        
        .dark .badge-error {
            background-color: hsla(var(--error-hue), 90%, 60%, 0.1);
            color: hsl(var(--error-hue), 90%, 60%);
        }
        
        .badge-warning {
            background-color: hsla(var(--warning-hue), 90%, 45%, 0.1);
            color: hsl(var(--warning-hue), 90%, 45%);
        }
        
        .dark .badge-warning {
            background-color: hsla(var(--warning-hue), 90%, 60%, 0.1);
            color: hsl(var(--warning-hue), 90%, 60%);
        }
        
        /* ===== 滚动条 ===== */
        ::-webkit-scrollbar {
            width: 0.375rem;
            height: 0.375rem;
        }
        
        ::-webkit-scrollbar-track {
            background-color: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background-color: var(--gray-300);
            border-radius: var(--radius-full);
        }
        
        .dark ::-webkit-scrollbar-thumb {
            background-color: var(--gray-700);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background-color: var(--gray-400);
        }
        
        .dark ::-webkit-scrollbar-thumb:hover {
            background-color: var(--gray-600);
        }
        
        /* ===== 空状态 ===== */
        .empty-state {
            text-align: center;
            padding: var(--spacing-12) var(--spacing-4);
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: var(--text-tertiary);
            margin-bottom: var(--spacing-4);
            opacity: 0.7;
        }
        
        .dark .empty-state-icon {
            color: var(--dark-text-tertiary);
        }
        
        .empty-state-title {
            font-size: var(--text-xl);
            font-weight: var(--font-semibold);
            color: var(--text-primary);
            margin-bottom: var(--spacing-2);
            letter-spacing: -0.01em;
        }
        
        .dark .empty-state-title {
            color: var(--dark-text-primary);
        }
        
        .empty-state-description {
            color: var(--text-secondary);
            max-width: 24rem;
            margin: 0 auto var(--spacing-6);
            font-size: var(--text-base);
            line-height: var(--leading-relaxed);
        }
        
        .dark .empty-state-description {
            color: var(--dark-text-secondary);
        }
        
        /* ===== 加载动画 ===== */
        .loading-spinner {
            width: 2rem;
            height: 2rem;
            border: 2px solid hsla(var(--primary-hue), var(--primary-saturation), var(--primary-lightness), 0.1);
            border-top-color: hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness));
            border-radius: var(--radius-full);
            animation: spin 0.8s linear infinite;
        }
        
        .dark .loading-spinner {
            border-color: hsla(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%), 0.1);
            border-top-color: hsl(var(--primary-hue), var(--primary-saturation), calc(var(--primary-lightness) + 20%));
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        
        /* ===== 骨架屏 ===== */
        .skeleton {
            background: linear-gradient(
                90deg,
                var(--bg-secondary) 0%,
                var(--bg-tertiary) 50%,
                var(--bg-secondary) 100%
            );
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: var(--radius-md);
        }
        
        .dark .skeleton {
            background: linear-gradient(
                90deg,
                var(--dark-bg-secondary) 0%,
                var(--dark-bg-tertiary) 50%,
                var(--dark-bg-secondary) 100%
            );
        }
        
        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }
        
        /* ===== 彩色纸屑动画 ===== */
        #confettiContainer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: var(--z-50);
            overflow: hidden;
        }
        
        .confetti {
            position: absolute;
            top: -10px;
            border-radius: 0;
            animation-name: confetti-fall, confetti-shake;
            animation-timing-function: linear, ease-in-out;
            animation-iteration-count: infinite, infinite;
            animation-play-state: running, running;
        }
        
        .confetti--animation-slow {
            animation-duration: 4.25s, 4s;
        }
        
        .confetti--animation-medium {
            animation-duration: 3.75s, 3s;
        }
        
        .confetti--animation-fast {
            animation-duration: 3.25s, 2s;
        }
        
        @keyframes confetti-fall {
            0% {
                transform: translateY(-10px) rotate(0deg);
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
            }
        }
        
        @keyframes confetti-shake {
            0%, 100% {
                transform: translateX(0%);
            }
            25% {
                transform: translateX(25%);
            }
            50% {
                transform: translateX(-25%);
            }
            75% {
                transform: translateX(15%);
            }
        }
        
        /* ===== 自适应媒体查询 ===== */
        @media (max-width: 768px) {
            .task-grid {
                grid-template-columns: 1fr;
            }
            
            .filters {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: var(--spacing-2);
                justify-content: flex-start;
                -webkit-overflow-scrolling: touch;
            }
            
            .filter-item {
                flex-shrink: 0;
            }
            
            .theme-toggle {
                bottom: var(--spacing-4);
                right: var(--spacing-4);
                width: 2.75rem;
                height: 2.75rem;
            }
            
            .modal-content {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <header class="navbar">
        <div class="container navbar-container">
            <div class="navbar-brand">
                <a href="#" class="navbar-logo">任务管理系统</a>
            </div>
            <div class="navbar-actions">
                <div class="filter-item active" onclick="filterTasks('all')">全部任务</div>
            </div>
        </div>
    </header>

    <!-- 主内容区域 -->
    <main class="main">
        <div class="container">
            <!-- 页面标题 -->
            <div class="page-header">
                <h1 class="page-title">任务管理中心</h1>
                <p class="page-subtitle">高效管理您的所有任务，提高工作效率</p>
            </div>
            
            <!-- 过滤器 -->
            <div class="filters-container">
                <div class="filters">
                    <div class="filter-item active" onclick="filterTasks('all')">全部</div>
                    <div class="filter-item" onclick="filterTasks('questionnaire')">问卷</div>
                    <div class="filter-item" onclick="filterTasks('evaluation')">评价</div>
                    <div class="filter-item" onclick="filterTasks('exam')">考试</div>
                    <div class="filter-item" onclick="filterTasks('material')">资料</div>
                </div>
            </div>
            
            <!-- 任务卡片网格 -->
            <div class="task-grid" id="taskGrid">
                <?php if (empty($items)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h2 class="empty-state-title">暂无任务</h2>
                    <p class="empty-state-description">当前没有任何任务需要处理，请稍后再查看。</p>
                </div>
                <?php else: ?>
                <?php foreach ($items as $index => $item): ?>
                <div class="task-card" data-type="<?php echo $item['type']; ?>" onclick="openModal(<?php echo $index; ?>)">
                    <div class="task-card-header">
                        <h2 class="task-card-title"><?php echo $item['title']; ?></h2>
                        <div class="task-card-meta">
                            <span class="task-card-type">
                                <?php echo isset($typeNames[$item['type']]) ? $typeNames[$item['type']] : '未知'; ?>
                            </span>
                            <span class="task-card-date">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <?php echo $item['created_at']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="task-card-body">
                        <div class="task-card-description">
                            <?php echo strip_tags($item['content']); ?>
                        </div>
                    </div>
                    <div class="task-card-footer">
                        <div class="task-card-deadline <?php echo strpos($item['expired_at'], 'Today') !== false ? 'deadline-urgent' : (strpos($item['expired_at'], 'Tomorrow') !== false ? 'deadline-soon' : 'deadline-normal'); ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php echo strip_tags($item['expired_at']); ?>
                        </div>
                    </div>
                    
                    <!-- 任务卡片操作按钮 - 始终可见 -->
                    <div class="task-card-actions">
                        <?php if ($item['type'] === 'seiue.exam.answer'): ?>
                        <button class="task-card-action tooltip" data-tooltip="全对" onclick="event.stopPropagation(); submit_all_ok('<?php echo $item['biz_id']; ?>')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($item['type'] === 'seiue.material'): ?>
                        <button class="task-card-action tooltip" data-tooltip="下载" ondblclick="event.stopPropagation(); window.open('<?php echo $item['download_url']; ?>', '_blank')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($item['type'] === 'seiue.questionnaire.answer' || $item['type'] === 'seiue.evaluation.answer'): ?>
                        <button class="task-card-action tooltip" data-tooltip="跳转" onclick="event.stopPropagation(); window.location.href='<?php echo $item['type'] === 'seiue.questionnaire.answer' ? "https://go-c3.seiue.com/questionnaire-submit?id={$item['biz_id']}" : "https://go-c3.seiue.com/evaluations-screen?id={$item['biz_id']}"; ?>'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                        
                        <button class="task-card-action tooltip" data-tooltip="忽略" onclick="event.stopPropagation(); ignoreTask('<?php echo $item['id']; ?>')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <a href="?page=1" class="pagination-item" <?php echo $currentPage == 1 ? 'aria-disabled="true"' : ''; ?>>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                </a>
                <a href="?page=<?php echo max(1, $currentPage - 1); ?>" class="pagination-item" <?php echo $currentPage == 1 ? 'aria-disabled="true"' : ''; ?>>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $startPage + 4);
                if ($endPage - $startPage < 4) {
                    $startPage = max(1, $endPage - 4);
                }
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                <a href="?page=<?php echo $i; ?>" class="pagination-item <?php echo $i == $currentPage ? 'pagination-active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <a href="?page=<?php echo min($totalPages, $currentPage + 1); ?>" class="pagination-item" <?php echo $currentPage == $totalPages ? 'aria-disabled="true"' : ''; ?>>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
                <a href="?page=<?php echo $totalPages; ?>" class="pagination-item" <?php echo $currentPage == $totalPages ? 'aria-disabled="true"' : ''; ?>>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- 模态框 -->
    <div id="modal" class="modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle" class="modal-title">任务详情</h2>
                <button class="modal-close" onclick="closeModal()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalCreated" class="text-sm text-gray-500 mb-4"></div>
                <div id="modalContent" class="prose prose-indigo dark:prose-invert max-w-none"></div>
                
                <div id="upload" class="mt-6">
                    <h3 class="text-lg font-semibold mb-3">提交回复</h3>
                    
                    <div class="form-group">
                        <label for="textarea" class="form-label">文本回复</label>
                        <textarea id="textarea" class="form-input form-textarea" placeholder="请输入您的回复..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">文件上传</label>
                        <div class="file-upload-area" id="dropArea">
                            <div class="file-upload-icon">
                                <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                            <p class="file-upload-text">拖放文件到此处或点击上传</p>
                            <p class="file-upload-hint">支持的文件格式: PDF, DOC, DOCX, JPG, PNG</p>
                            <input type="file" id="fileInput" class="hidden" multiple>
                        </div>
                    </div>
                    
                    <div id="fileList" class="file-list"></div>
                    
                    <div class="flex justify-between mt-4">
                        <div id="uploadStatus" class="text-sm text-gray-500"></div>
                        <button id="onlysubmittext" class="btn btn-primary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            提交回复
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 主题切换按钮 -->
    <button id="themeToggle" class="theme-toggle" aria-label="切换主题">
        <svg id="sunIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
        <svg id="moonIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
        </svg>
    </button>
    
    <!-- 通知容器 -->
    <div id="toastContainer"></div>
    
    <!-- 彩色纸屑容器 -->
    <div id="confettiContainer"></div>
    
    <!-- JavaScript -->
    <script>
        // 全局变量
        let this_biz_id = "";
        let title = "";
        let max_file_id = -1;
        let clearTimer = null;
        const items = <?php echo json_encode($items); ?>;
        
        // 主题管理
        function setTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                document.getElementById('moonIcon').style.display = 'block';
                document.getElementById('sunIcon').style.display = 'none';
            } else {
                document.documentElement.classList.remove('dark');
                document.getElementById('moonIcon').style.display = 'none';
                document.getElementById('sunIcon').style.display = 'block';
            }
            localStorage.setItem('theme', theme);
        }
        
        function checkDarkMode() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                setTheme('dark');
            } else {
                setTheme('light');
            }
        }
        
        // 页面加载时检查主题
        checkDarkMode();
        
        // 监听系统主题变化
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });
        
        // 主题切换按钮
        document.getElementById('themeToggle').addEventListener('click', function() {
            const isDark = document.documentElement.classList.contains('dark');
            
            // 添加切换动画
            this.classList.add('animate-pulse');
            setTimeout(() => {
                this.classList.remove('animate-pulse');
            }, 300);
            
            // 切换主题并添加过渡动画
            document.documentElement.style.transition = 'background-color 0.5s ease, color 0.5s ease';
            
            if (isDark) {
                setTheme('light');
                showToast('info', '主题已切换', '已启用亮色模式');
            } else {
                setTheme('dark');
                showToast('info', '主题已切换', '已启用暗色模式');
            }
            
            // 移除过渡动画以避免不必要的过渡
            setTimeout(() => {
                document.documentElement.style.transition = '';
            }, 500);
        });
        
        // 通知提示系统
        function showToast(type, title, message, duration = 5000) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            let iconSvg = '';
            if (type === 'success') {
                iconSvg = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            } else if (type === 'error') {
                iconSvg = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            } else if (type === 'warning') {
                iconSvg = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
            } else if (type === 'info') {
                iconSvg = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            }
            
            toast.innerHTML = `
                <div class="toast-icon">${iconSvg}</div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            
            const toastContainer = document.getElementById('toastContainer');
            toastContainer.appendChild(toast);
            
            // 触发重排以启用过渡动画
            toast.offsetHeight;
            
            // 显示通知
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // 自动移除通知
            const timeout = setTimeout(() => {
                removeToast(toast);
            }, duration);
            
            // 关闭按钮
            const closeButton = toast.querySelector('.toast-close');
            closeButton.addEventListener('click', () => {
                clearTimeout(timeout);
                removeToast(toast);
            });
            
            return toast;
        }
        
        function removeToast(toast) {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
        
        // 任务过滤功能
        function filterTasks(type) {
            const filterItems = document.querySelectorAll('.filter-item');
            const taskCards = document.querySelectorAll('.task-card');
            
            // 更新过滤器状态
            filterItems.forEach(item => {
                item.classList.remove('active');
                if (item.textContent.toLowerCase().includes(type) || 
                    (type === 'all' && item.textContent.includes('全部'))) {
                    item.classList.add('active');
                }
            });
            
            // 过滤任务卡片
            taskCards.forEach(card => {
                const cardType = card.dataset.type;
                
                // 重置卡片样式
                card.style.display = 'flex';
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                if (type === 'all' || 
                    (type === 'questionnaire' && cardType === 'seiue.questionnaire.answer') ||
                    (type === 'evaluation' && cardType === 'seiue.evaluation.answer') ||
                    (type === 'exam' && cardType === 'seiue.exam.answer') ||
                    (type === 'material' && cardType === 'seiue.material')) {
                    
                    // 显示匹配的卡片并添加动画
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                } else {
                    card.style.display = 'none';
                }
            });
            
            // 检查是否有可见的卡片
            setTimeout(() => {
                const visibleCards = document.querySelectorAll('.task-card[style*="display: flex"]');
                const taskGrid = document.getElementById('taskGrid');
                
                if (visibleCards.length === 0) {
                    // 如果没有可见卡片，显示空状态
                    const emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = `
                        <div class="empty-state-icon">🔍</div>
                        <h2 class="empty-state-title">没有找到任务</h2>
                        <p class="empty-state-description">当前筛选条件下没有任何任务，请尝试其他筛选条件。</p>
                    `;
                    
                    // 移除之前的空状态（如果有）
                    const existingEmptyState = taskGrid.querySelector('.empty-state');
                    if (existingEmptyState) {
                        existingEmptyState.remove();
                    }
                    
                    taskGrid.appendChild(emptyState);
                    
                    // 添加动画
                    emptyState.style.opacity = '0';
                    emptyState.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        emptyState.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        emptyState.style.opacity = '1';
                        emptyState.style.transform = 'translateY(0)';
                    }, 50);
                } else {
                    // 如果有可见卡片，移除空状态
                    const existingEmptyState = taskGrid.querySelector('.empty-state');
                    if (existingEmptyState) {
                        existingEmptyState.remove();
                    }
                }
            }, 100);
        }
        
        // 打开模态框
        function openModal(index) {
            let is_read = 0;
            const item = items[index];
            this_biz_id = item["biz_id"];
            title = item["title"];
            const typeName = <?= json_encode($typeNames) ?>[item.type] || "未知";
            
            // 添加加载动画
            document.getElementById('modalContent').innerHTML = `
                <div class="flex justify-center items-center py-12">
                    <div class="loading-spinner"></div>
                </div>
            `;
            
            // 标记任务为已读
            async function read_it(biz_id, title) {
                let formData_read = new FormData();
                formData_read.append("the_biz_id", this_biz_id);
                formData_read.append("the_title", title);
                const options = {
                    method: 'POST',
                    body: formData_read
                };
                try {
                    const response = await fetch("./read-task.php", options);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const result = await response.json();
                    console.log(result);
                    if (result.message === "Completed") {
                        is_read = true;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('error', '错误', '无法标记任务为已读');
                }
            }
            
            read_it(this_biz_id, title);
            
            // 处理特殊任务类型的跳转
            if (item.type === "seiue.questionnaire.answer") {
                showToast('info', '正在跳转', '正在打开问卷提交页面...');
                window.location.href = `https://go-c3.seiue.com/questionnaire-submit?id=${this_biz_id}`;
                return 1;
            }
            if (item.type === "seiue.evaluation.answer") {
                showToast('info', '正在跳转', '正在打开评价页面...');
                window.location.href = `https://go-c3.seiue.com/evaluations-screen?id=${this_biz_id}`;
                return 1;
            }
            
            // 设置模态框标题
            document.getElementById('modalTitle').innerHTML = item.title;
            
            // 为即将到期的任务添加徽章
            if (item.expired_at.includes('Today')) {
                document.getElementById('modalTitle').innerHTML += '&nbsp;&nbsp;<span class="badge badge-error">今日到期</span>';
            }
            else if (item.expired_at.includes('Tomorrow')) {
                document.getElementById('modalTitle').innerHTML += '&nbsp;&nbsp;<span class="badge badge-warning">明日到期</span>';
            }
            
            document.getElementById("modalCreated").innerHTML = "创建时间: " + ((item.created_at) ? item.created_at : "未设置");         

            let content = item.content;
            if (is_read) {
                content += '<div class="mt-4"><span class="badge badge-success">已读</span></div>';
            }
            
            // 检查任务类型是否为"资料"
            if (typeName === "资料" || typeName === "未知") {
                document.getElementById('upload').classList.add('hidden');
            } else {
                document.getElementById('upload').classList.remove('hidden');
            }
            
            document.getElementById('modalContent').innerHTML = content;
            
            // 增强模态框打开动画
            const modal = document.getElementById('modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // 添加短暂延迟以实现更平滑的动画
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
            
            document.body.style.overflow = 'hidden';
        }
        
        // 关闭模态框
        function closeModal() {
            const modal = document.getElementById('modal');
            
            // 增强关闭动画
            modal.classList.remove('active');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
                this_biz_id = "";
            }, 300);
        }
        
        // 忽略任务
        async function ignoreTask(taskId) {
            if (!taskId) return;
            
            // 使用SweetAlert2请求确认
            const willIgnore = await Swal.fire({
                title: "忽略此任务?",
                text: "此任务将被标记为已忽略，不会再出现在您的待处理任务中。",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "忽略",
                cancelButtonText: "取消",
                confirmButtonColor: "hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness))",
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'swal2-dark' : ''
                }
            });
            
            if (!willIgnore.isConfirmed) return;
            
            try {
                // 显示加载通知
                const loadingToast = showToast('info', '处理中', '正在忽略任务...');
                
                const response = await fetch('./ignore.php?id='+taskId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${taskId}`
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Ignore API Response:', result);
                
                // 移除加载通知
                removeToast(loadingToast);
                
                if (result.success) {
                    // 为任务卡片添加淡出动画
                    const taskIndex = items.findIndex(item => item.id === taskId);
                    const taskCard = document.querySelector(`.task-card[onclick="openModal(${taskIndex})"]`);
                    if (taskCard) {
                        taskCard.style.transition = 'all 0.5s ease';
                        taskCard.style.transform = 'translateX(100px)';
                        taskCard.style.opacity = '0';
                    }
                    
                    showToast('success', '任务已忽略', '任务已成功标记为已忽略');
                    
                    // 短暂延迟后刷新页面
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast('error', '错误', result.message || "无法忽略任务");
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('error', '错误', '忽略任务失败，请重试');
            }
        }
        
        // 文件上传功能
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('fileInput');
            const dropArea = document.getElementById('dropArea');
            const fileList = document.getElementById('fileList');
            const uploadStatus = document.getElementById('uploadStatus');
            const ost = document.getElementById("onlysubmittext");
            const txtarea = document.getElementById("textarea");
            
            // 点击上传区域触发文件选择
            dropArea.addEventListener('click', () => {
                fileInput.click();
            });
            
            // 文件选择变化处理
            fileInput.addEventListener('change', function(e) {
                updateFileList(e.target.files);
            });
            
            // 拖放功能
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropArea.classList.add('dragging');
            }
            
            function unhighlight() {
                dropArea.classList.remove('dragging');
            }
            
            dropArea.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                updateFileList(files);
            }
            
            // 更新文件列表
            function updateFileList(files) {
                fileList.innerHTML = '';
                
                if (files.length === 0) {
                    return;
                }
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-list-item';
                    
                    // 根据文件类型确定图标
                    let fileIcon = '<svg class="w-5 h-5 text-indigo-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                    
                    if (file.type.includes('image')) {
                        fileIcon = '<svg class="w-5 h-5 text-pink-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>';
                    } else if (file.type.includes('pdf')) {
                        fileIcon = '<svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>';
                    } else if (file.type.includes('word') || file.name.endsWith('.doc') || file.name.endsWith('.docx')) {
                        fileIcon = '<svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                    }
                    
                    fileItem.innerHTML = `
                        <div class="flex items-center">
                            ${fileIcon}
                            <span class="truncate max-w-[200px] text-gray-700 dark:text-gray-300">${file.name}</span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">${fileSize} MB</span>
                    `;
                    
                    // 添加交错进入动画
                    fileItem.style.opacity = '0';
                    fileItem.style.transform = 'translateY(10px)';
                    
                    fileList.appendChild(fileItem);
                    
                    // 触发动画
                    setTimeout(() => {
                        fileItem.style.opacity = '1';
                        fileItem.style.transform = 'translateY(0)';
                        fileItem.style.transition = 'all 0.3s ease';
                    }, 50 * i);
                }
            }
            
            // 文本提交
            ost.addEventListener('click', async function(e) {
                if (!txtarea.value.trim()) {
                    showToast('warning', '空文本', '请在提交前输入一些文本');
                    txtarea.focus();
                    
                    // 添加聚焦动画
                    txtarea.classList.add('ring-2', 'ring-red-500', 'ring-opacity-50');
                    setTimeout(() => {
                        txtarea.classList.remove('ring-2', 'ring-red-500', 'ring-opacity-50');
                    }, 1000);
                    
                    return;
                }
                
                uploadStatus.textContent = '提交中...';
                uploadStatus.classList.add('animate-pulse');
                
                // 显示加载通知
                const loadingToast = showToast('info', '处理中', '正在提交您的回复...');
                
                const formData = new FormData();
                formData.append("biz_id", this_biz_id);
                formData.append("SUBMIT_TEXT", txtarea.value);
                formData.append("max_file_id", -1);
                max_file_id = -1;
                
                try {
                    const response = await fetch('./up-and-submit.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    console.log('Server API Response:', result);
                    
                    uploadStatus.textContent = result.message;
                    uploadStatus.classList.remove('animate-pulse');
                    
                    // 移除加载通知
                    removeToast(loadingToast);
                    
                    // 显示成功通知
                    showToast('success', '提交成功', '您的回复已成功提交！');
                    
                    // 触发彩色纸屑动画
                    createConfetti();
                    
                    if (clearTimer) {
                        clearTimeout(clearTimer);
                    }

                    clearTimer = setTimeout(() => {
                        uploadStatus.textContent = '';
                        fileList.innerHTML = '';
                        txtarea.value = '';
                        closeModal();
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }, 2000);
                } catch (error) {
                    console.error('Error:', error);
                    uploadStatus.textContent = '提交过程中发生错误';
                    uploadStatus.classList.remove('animate-pulse');
                    
                    // 移除加载通知
                    removeToast(loadingToast);
                    
                    // 显示错误通知
                    showToast('error', '错误', '提交过程中发生错误，请重试');
                    
                    if (clearTimer) {
                        clearTimeout(clearTimer);
                    }

                    clearTimer = setTimeout(() => {
                        uploadStatus.textContent = '';
                    }, 2000);
                }
            });
        });
        
        // 彩色纸屑动画
        function createConfetti() {
            const confettiContainer = document.getElementById('confettiContainer');
            confettiContainer.innerHTML = '';
            
            const colors = ['#f94144', '#f3722c', '#f8961e', '#f9c74f', '#90be6d', '#43aa8b', '#577590', '#4361ee', '#7209b7', '#3a0ca3'];
            
            for (let i = 0; i < 150; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                
                const color = colors[Math.floor(Math.random() * colors.length)];
                const size = Math.random() * 10 + 5;
                const left = Math.random() * 100;
                
                confetti.style.backgroundColor = color;
                confetti.style.width = `${size}px`;
                confetti.style.height = `${size}px`;
                confetti.style.left = `${left}%`;
                
                const animationDuration = Math.random() * 3 + 2;
                const animationDelay = Math.random() * 2;
                
                confetti.style.animationDuration = `${animationDuration}s`;
                confetti.style.animationDelay = `${animationDelay}s`;
                
                const animationClass = Math.random() > 0.6 
                    ? 'confetti--animation-slow' 
                    : Math.random() > 0.5 
                        ? 'confetti--animation-medium' 
                        : 'confetti--animation-fast';
                
                confetti.classList.add(animationClass);
                
                confettiContainer.appendChild(confetti);
            }
            
            setTimeout(() => {
                confettiContainer.innerHTML = '';
            }, 5000);
        }
        
        // 提交"全对"功能
        async function submit_all_ok(arg_biz_id) {
            const result = await Swal.fire({
                title: '确认继续?',
                text: '强烈建议在考试后进行反思；如果继续，系统将为此任务提交"全对"文本。',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '确认',
                cancelButtonText: '取消',
                confirmButtonColor: "hsl(var(--primary-hue), var(--primary-saturation), var(--primary-lightness))",
                customClass: {
                    popup: document.documentElement.classList.contains('dark') ? 'swal2-dark' : ''
                }
            });
            
            if (result.isConfirmed) {
                // 显示加载通知
                const loadingToast = showToast('info', '处理中', '正在提交"全对"...');
                
                const formData = new FormData();
                formData.append("biz_id", arg_biz_id);
                formData.append("SUBMIT_TEXT", "全对");
                formData.append("max_file_id", -1);
                max_file_id = -1;
                
                try {
                    const response = await fetch('./up-and-submit.php', {
                        method: 'POST',
                        body: formData
                    });
            
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
            
                    const result = await response.json();
                    console.log('Server API Response:', result);
                    
                    // 移除加载通知
                    removeToast(loadingToast);
                    
                    // 显示成功通知
                    showToast('success', '提交成功', '已成功提交"全对"');
                    
                    // 触发彩色纸屑动画
                    createConfetti();
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } catch (error) {
                    console.error('Error:', error);
                    
                    // 移除加载通知
                    removeToast(loadingToast);
                    
                    // 显示错误通知
                    showToast('error', '错误', '提交过程中发生错误');
                }
            }
        }
        
        // 页面加载时的动画效果
        document.addEventListener('DOMContentLoaded', function() {
            // 为任务卡片添加入场动画
            const taskCards = document.querySelectorAll('.task-card');
            taskCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 50 + index * 50); // 交错动画
            });
            
            // 为过滤器添加入场动画
            const filterItems = document.querySelectorAll('.filter-item');
            filterItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(-10px)';
                
                setTimeout(() => {
                    item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, 300 + index * 50); // 交错动画
            });
            
            // 为页面标题添加入场动画
            const pageTitle = document.querySelector('.page-title');
            const pageSubtitle = document.querySelector('.page-subtitle');
            
            if (pageTitle) {
                pageTitle.style.opacity = '0';
                pageTitle.style.transform = 'translateY(-10px)';
                
                setTimeout(() => {
                    pageTitle.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                    pageTitle.style.opacity = '1';
                    pageTitle.style.transform = 'translateY(0)';
                }, 100);
            }
            
            if (pageSubtitle) {
                pageSubtitle.style.opacity = '0';
                pageSubtitle.style.transform = 'translateY(-10px)';
                
                setTimeout(() => {
                    pageSubtitle.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                    pageSubtitle.style.opacity = '1';
                    pageSubtitle.style.transform = 'translateY(0)';
                }, 200);
            }
        });
    </script>
    
    <!-- SweetAlert2暗色模式支持 -->
    <style>
        .swal2-dark {
            background-color: var(--dark-bg-primary) !important;
            color: var(--dark-text-primary) !important;
        }
        
        .swal2-dark .swal2-title,
        .swal2-dark .swal2-html-container {
            color: var(--dark-text-primary) !important;
        }
        
        .swal2-dark .swal2-cancel {
            background-color: var(--dark-bg-secondary) !important;
            color: var(--dark-text-primary) !important;
            border: 1px solid var(--dark-border-color) !important;
        }
        
        .swal2-dark .swal2-icon.swal2-warning {
            border-color: hsl(var(--warning-hue), 90%, 60%) !important;
            color: hsl(var(--warning-hue), 90%, 60%) !important;
        }
    </style>
</body>
</html>
