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
    "seiue.class_homework_task" => "bg-blue-100 text-blue-800",
    "seiue.class_document_task" => "bg-purple-100 text-purple-800",
    "seiue.item_afterthought_todo" => "bg-yellow-100 text-yellow-800",
    "seiue.class_questionnaire_task" => "bg-green-100 text-green-800",
    "seiue.questionnaire.answer" => "bg-teal-100 text-teal-800",
    "seiue.evaluation.answer" => "bg-orange-100 text-orange-800",
    "seiue.psychological.assessment" => "bg-indigo-100 text-indigo-800",
    "online_class.unbind_to_reflection" => "bg-cyan-100 text-cyan-800",
    "chat.submit_evaluation" => "bg-lime-100 text-lime-800",
    "handout.answer" => "bg-amber-100 text-amber-800",
    "seiue.scms.election.student" => "bg-fuchsia-100 text-fuchsia-800",
    "seiue.direction.start_notification" => "bg-sky-100 text-sky-800"
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
<html lang="en" class="bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./favicon.png" type="image/png">
    <title>Tasks - Sue</title>
    <script src="./twind.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --secondary-color: #f3f4f6;
            --accent-color: #8b5cf6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --text-tertiary: #6b7280;
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --transition-normal: all 0.3s ease;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            transition: var(--transition-normal);
            scroll-behavior: smooth;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeInUp {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        @keyframes fadeInRight {
            from { 
                opacity: 0; 
                transform: translateX(-20px); 
            }
            to { 
                opacity: 1; 
                transform: translateX(0); 
            }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
            opacity: 0;
            animation-fill-mode: forwards;
            animation-delay: calc(var(--animation-order) * 0.1s);
        }
        
        .fade-in-right {
            animation: fadeInRight 0.5s ease-out forwards;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        /* Task Cards */
        .task-card {
            background-color: var(--bg-primary);
            border-radius: 1rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .task-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: var(--primary-color);
            opacity: 0;
            transition: var(--transition-normal);
        }
        
        .task-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .task-card:hover::before {
            opacity: 1;
        }
        
        .task-card.completed {
            
            opacity: 0.8;
        }
        
        .task-card.completed::before {
            background-color: var(--success-color);
            opacity: 1;
        }
        
        .task-card.expired::before {
            background-color: var(--danger-color);
            opacity: 1;
        }
        
        /* Buttons */
        .btn {
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
            transform: translateZ(0);
        }
        
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
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        .btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            20% {
                transform: scale(25, 25);
                opacity: 0.3;
            }
            100% {
                opacity: 0;
                transform: scale(40, 40);
            }
        }
        
        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-normal);
            transform: translateZ(0);
        }
        
        .badge:hover {
            transform: scale(1.05);
        }
        
        /* Modal */
        .modal {
            transition: var(--transition-normal);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            transition: var(--transition-normal);
            transform: translateY(20px);
            opacity: 0;
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
            opacity: 1;
        }
        
        .modal-open .task-card {
            filter: blur(2px);
            pointer-events: none;
        }
        
        /* Pagination */
        .pagination-item {
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
        }
        
        .pagination-item:hover {
            transform: translateY(-2px);
        }
        
        .pagination-item.active {
            transform: scale(1.05);
            font-weight: 600;
        }
        
        /* Dropdown */
        .dropdown {
            transition: var(--transition-normal);
            transform-origin: top;
            transform: scaleY(0);
            opacity: 0;
        }
        
        .dropdown.show {
            transform: scaleY(1);
            opacity: 1;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--text-tertiary);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }
        
        /* Form elements */
        input, select, textarea {
            transition: var(--transition-normal);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
        }
        
        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
        }
        
        /* Utility classes */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .text-gradient {
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            background-image: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }
        
        .hover-lift {
            transition: transform 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-3px);
        }
        
        .shadow-hover {
            transition: box-shadow 0.3s ease;
        }
        
        .shadow-hover:hover {
            box-shadow: var(--shadow-lg);
        }
        
        /* Specific animations for task items */
        .task-item {
            opacity: 0;
            animation: fadeInUp 0.5s ease-out forwards;
            animation-delay: calc(var(--delay) * 0.08s);
        }
        
        .modal-open .task-item {
            opacity: 0.5;
            filter: blur(2px);
            pointer-events: none;
            transition: var(--transition-normal);
        }
        
        /* Tooltip */
        .tooltip {
            position: relative;
        }
        
        .tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%) scale(0);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            transition: all 0.2s ease;
            pointer-events: none;
            z-index: 10;
        }
        
        .tooltip:hover::after {
            transform: translateX(-50%) scale(1);
            opacity: 1;
        }
        
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <?php
        $include_src = "tasks";
        require "./global-header.php";
        ?>
        
        <!-- Header Section -->
        <div class="<?php echo ($ohno) ? 'fade-in-up' : ''; ?> flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10 mt-6">
            <div>
                <h1 class="text-4xl font-light text-gray-800 tracking-tight mb-2 text-gradient">Your Tasks</h1>
                <p class="text-gray-500 text-sm md:text-base">Manage and track your assignments efficiently</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3 self-end">
                
                <!-- Added Aurora button -->
                <a href="/aurora.php" class="btn flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-full shadow-sm hover:shadow-md hover:bg-amber-600 transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Aurora</span>
                </a>
                
                <button id="filterBtn" class="btn flex items-center gap-2 px-4 py-2 bg-white text-gray-700 rounded-full shadow-sm hover:shadow-md transition-all duration-300 border border-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <span>Filter</span>
                </button>
                
                <?php if($_REQUEST["tasks"]!=="all"){ ?>
                <a href="./tasks.php?tasks=all&page=1" class="btn flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-full shadow-sm hover:shadow-md hover:bg-indigo-700 transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    <span>View All Tasks</span>
                </a>
                <?php } else { ?>
                <a href="/tasks.php" class="btn flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-full shadow-sm hover:shadow-md hover:bg-indigo-700 transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span>Pending Tasks</span>
                </a>
                <?php } ?>
            </div>
        </div>
        
        <!-- Task Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($items as $index => $item): 
                $isCompleted = $item["status"] === "completed";
                $isExpired = strpos($item["expired_at"], "Expired") !== false;
                $isToday = strpos($item["expired_at"], "Today") !== false;
                $isTomorrow = strpos($item["expired_at"], "Tomorrow") !== false;
                
                $cardClass = "task-card p-6 cursor-pointer";
                if ($isCompleted) $cardClass .= " completed";
                elseif ($isExpired) $cardClass .= " expired";
                
                $typeClass = $typeClasses[$item["type"]] ?? "bg-gray-100 text-gray-800";
                $typeName = $typeNames[$item["type"]] ?? "未知";
                
                // Check for direct download link
                $download_url = null;
                if (preg_match_all('/<a href="(https:\/\/api\.seiue\.com\/[^"]+)"/', $item['content'], $matches) && count($matches[1]) === 1) {
                    $download_url = $matches[1][0];
                }

                // Check for external link
                $external_url = null;
                if (preg_match_all('/https?:\/\/(?!api\.seiue\.com)[^\s<>"\']+/', $item['content'], $matches) && count($matches[0]) === 1) {
                    $external_url = $matches[0][0];
                }

                // Check for "改错" in title
                $show_submit_all = strpos($item['title'], '改错') !== false;
            ?>
            <div class="task-item" style="--delay: <?= $index ?>;" onclick="openModal(<?= $index ?>)">
                <div class="<?= $cardClass ?> group">
                    <!-- Task Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-medium text-gray-<?= ($isCompleted ? "400" : "800"); ?> truncate max-w-[90%] flex items-center group-hover:text-indigo-600 transition-colors duration-300">
                            <?php
                            if ($isCompleted) {
                                echo '<svg class="w-6 h-6 mr-2 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                            }
                            ?>
                            <?= htmlspecialchars(mb_strlen($item['title']) > 14 ? mb_substr($item['title'], 0, 14).'...' : $item['title']); ?>
                        </h2>
                        <span class="badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap <?= $typeClass ?>">
                            <?= $typeName ?>
                        </span>
                    </div>
                    
                    <!-- Task Details -->
                    <div class="space-y-3 text-sm text-gray-<?= ($isCompleted ? "400" : "600"); ?>">
                        <p class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="<?= $isToday ? 'text-pink-500 font-medium' : ($isTomorrow ? 'text-pink-300 font-medium' : '') ?>">
                                <?= $item['expired_at'] ?>
                            </span>
                            
                            <?php if(($isToday || $isExpired) && !$isCompleted && $typeName == "作业"): ?>
                            <a href="/aurora.php?biz_id=<?= $item['biz_id'];?>&to_do_name=<?= $item['title'];?>" 
                               class="ml-2 text-amber-500 hover:text-amber-600 font-medium transition-colors duration-300 underline"
                               onclick="event.stopPropagation();">
                                Open With Aurora
                                <svg class="w-4 h-4 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </a>
                            <?php endif; ?>
                        </p>
                        
                        <p class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="truncate max-w-[180px]" title="<?= htmlspecialchars($item['group_name']) ?>">
                                <?= htmlspecialchars($item['group_name']) ?>
                            </span>
                        </p>
                        
                        <p class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="truncate max-w-[180px]" title="<?= htmlspecialchars($item['creator_name']) ?>">
                                <?= htmlspecialchars($item['creator_name']) ?>
                            </span>
                        </p>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="absolute bottom-3 right-3 flex space-x-2 opacity-100">
                        <?php if ($download_url): ?>
                        <a href="<?= htmlspecialchars($download_url) ?>" target="_blank" rel="noreferrer noopener" 
                           class="tooltip p-1.5 bg-blue-50 rounded-full text-blue-500 hover:text-blue-600 hover:bg-blue-100 transition-all duration-300" 
                           data-tooltip="Download"
                           onclick="event.stopPropagation();">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                        </a>
                        <?php endif; ?>

                        <?php if ($external_url): ?>
                        <a href="<?= htmlspecialchars($external_url) ?>" target="_blank" rel="noreferrer noopener" 
                           class="tooltip p-1.5 bg-teal-50 rounded-full text-teal-500 hover:text-teal-600 hover:bg-teal-100 transition-all duration-300" 
                           data-tooltip="Link"
                           onclick="event.stopPropagation();">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                        <?php endif; ?>

                        <?php if ($show_submit_all): ?>
                        <button onclick="submit_all_ok(<?= $item['biz_id']; ?>); event.stopPropagation();" 
                                class="tooltip p-1.5 bg-pink-50 rounded-full text-pink-500 hover:text-pink-600 hover:bg-pink-100 transition-all duration-300"
                                data-tooltip="Submit全对">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($item['status'] == 'pending'): ?>
                        <button onclick="ignoreTask(<?= $item['id']; ?>); event.stopPropagation();" 
                                class="tooltip p-1.5 bg-gray-50 rounded-full text-gray-500 hover:text-gray-600 hover:bg-gray-100 transition-all duration-300"
                                data-tooltip="Ignore">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Empty State -->
        <?php if (empty($items)): ?>
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg class="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            <h3 class="text-xl font-medium text-gray-700 mb-2">No Tasks Found</h3>
            <p class="text-gray-500 max-w-md">You don't have any tasks matching your current filters. Try adjusting your filters or check back later.</p>
        </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <div class="flex flex-wrap justify-center items-center mt-12 mb-16 gap-2">
            <?php
            $total_pages = ceil((int)$headers['X-Pagination-Total-Count'] / 24);
            $current_page = isset($headers['X-Pagination-Current-Page']) ? (int)$headers['X-Pagination-Current-Page'] : 1;
            $range = 2;

            $page_links = [
                'first' => 1,
                'prev' => max(1, $current_page - 1),
                'next' => min($total_pages, $current_page + 1),
                'last' => $total_pages
            ];

            foreach ($page_links as $type => $page) {
                $disabled = ($type == 'first' || $type == 'prev') ? $current_page == 1 : $current_page == $total_pages;
                $href = "?$params" . ($params ? '&' : '') . "page={$page}";
                
                $class = "pagination-item flex items-center justify-center w-10 h-10 rounded-full bg-white shadow-sm hover:shadow-md transition-all duration-300 " . 
                         ($disabled ? "opacity-50 cursor-not-allowed text-gray-400" : "text-gray-700 hover:text-indigo-600");
                
                $icon = '';
                if ($type == 'first') $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>';
                elseif ($type == 'prev') $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>';
                elseif ($type == 'next') $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>';
                elseif ($type == 'last') $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>';
                
                echo "<a href='" . ($disabled ? "javascript:void(0);" : $href) . "' class='{$class}' " . ($disabled ? "aria-disabled='true'" : "") . ">" . $icon . "</a>";
            }

            for ($i = max(1, $current_page - $range); $i <= min($total_pages, $current_page + $range); $i++) {
                if ($i == $current_page) {
                    echo "<span class='pagination-item active flex items-center justify-center w-10 h-10 rounded-full bg-indigo-600 text-white shadow-md'>{$i}</span>";
                } else {
                    echo "<a href='?{$params}" . ($params ? '&' : '') . "page={$i}' class='pagination-item flex items-center justify-center w-10 h-10 rounded-full bg-white text-gray-700 shadow-sm hover:shadow-md hover:text-indigo-600 transition-all duration-300'>{$i}</a>";
                }
            }
            ?>
            
            <form id="jumpForm" action="tasks.php" method="GET" class="flex items-center ml-4 bg-white rounded-full shadow-sm px-2 py-1">
                <input type="number" name="page" min="1" max="<?php echo htmlspecialchars($total_pages); ?>" placeholder="Page" 
                       class="w-16 px-2 py-1 border-0 focus:ring-0 text-center text-gray-700 text-sm" required>
                <button type="submit" class="ml-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full w-8 h-8 flex items-center justify-center transition-colors duration-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </form>
        </div>
        
        <?php require "./global-footer.php";?>
    </div>
    
    <!-- Task Detail Modal -->
    <div id="modal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="modal-content bg-white rounded-2xl shadow-xl p-6 m-4 max-w-2xl w-full max-h-[90vh] flex flex-col relative overflow-hidden">
            <!-- Modal Header -->
            <div class="mb-6">
                <h2 id="modalTitle" class="text-2xl font-medium mb-2 text-gray-800 pr-8"></h2>
                <span id="modalCreated" class="text-sm text-gray-500"></span>
            </div>
            
            <!-- Modal Content -->
            <div id="modalContent" class="overflow-y-auto flex-grow mb-6 prose prose-sm max-w-none" style="max-height: calc(90vh - 250px);"></div>
            
            <!-- Upload Section -->
            <div id="upload" class="space-y-6 border-t border-gray-100 pt-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <button id="toggleInput" class="btn flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <span>Input Text</span>
                    </button>
                    
                    <label for="fileInput" class="btn flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all duration-300 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span>Upload & Submit</span>
                        <button class="info-icon w-5 h-5 bg-white bg-opacity-20 rounded-full text-white text-xs font-semibold inline-flex items-center justify-center focus:outline-none transition duration-300 ease-in-out transform hover:scale-110 ml-1" data-info="The task would be automatically submitted immediately after all the attachments are uploaded." onclick="event.stopPropagation();">
                            i
                        </button>
                    </label>
                    <input id="fileInput" type="file" class="hidden" multiple>
                </div>
                
                <div id="inputArea" class="hidden space-y-4">
                    <textarea id="textarea" placeholder="Type your response here..." class="w-full min-h-[120px] px-4 py-3 text-gray-700 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-y transition-all duration-300"></textarea>
                    <button id="onlysubmittext" class="btn w-full px-4 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all duration-300 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                        </svg>
                        <span>Submit Text Only</span>
                    </button>
                </div>
                
                <div id="fileList" class="text-sm mt-2 space-y-2"></div>
                <div id="uploadStatus" class="text-sm mt-2 text-indigo-600 font-medium"></div>
            </div>
            
            <!-- Close Button -->
            <button onclick="closeModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600 transition-colors duration-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Filter Modal -->
    <div id="filterModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div class="modal-content bg-white rounded-2xl shadow-xl p-6 m-4 max-w-xl w-full">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-medium text-gray-800">Filter Tasks</h2>
                <button id="closeFilterModal" class="text-gray-400 hover:text-gray-600 transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form action="./tasks.php" method="get" class="space-y-6">
                <div>
                    <label for="keywordFilter" class="block text-sm font-medium text-gray-700 mb-2">Keywords</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="keywordFilter" name="keyword" placeholder="Search by title" class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="startDate" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <input type="date" id="startDate" name="startDate" class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300">
                        </div>
                    </div>
                    
                    <div>
                        <label for="endDate" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <input type="date" id="endDate" name="endDate" class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300">
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Status</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach ($statuses as $value => $label): ?>
                            <label class="relative flex items-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition-all duration-300">
                                <input type="radio" class="form-radio h-5 w-5 text-indigo-600 transition duration-150 ease-in-out" name="status" value="<?= $value ?>" <?= $value === '' ? 'checked' : '' ?>>
                                <span class="ml-3 text-gray-700"><?= $label ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 pt-4 border-t border-gray-100">
                    <button type="button" id="resetFilter" class="btn px-6 py-3 border border-gray-200 rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition-all duration-300">
                        Reset
                    </button>
                    <button type="submit" name="filter" value="yes" class="btn px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all duration-300">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <link rel="stylesheet" href="./djeh.css?version=shs">
    <script src="./confetti-1.js"></script>
    <script src="./up.js.php"></script>
    <script src="https://lf3-cdn-tos.bytecdntp.com/cdn/expire-5-M/sweetalert/2.1.2/sweetalert.min.js" type="application/javascript"></script>
    <script src  type="application/javascript"></script>
    <script src="/infobtn.js"></script>
    <script src="/hammer.js?nn=1" type="text/javascript"></script>
    
    <script>
    // Touch gestures for mobile navigation
    const bodyElement = document.querySelector(".sue-navbar");
    const hammer = new Hammer(bodyElement);
    
    // Listen for swipe events
    hammer.on('swipeleft', () => {
      window.location.href = '/notices.php';
    });
    
    hammer.on('swiperight', () => {
      window.location.href = '/aurora.php';
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        // Pagination functionality
        const jumpForm = document.getElementById('jumpForm');
        const pageInput = jumpForm.querySelector('input[name="page"]');
        const jumpButton = jumpForm.querySelector('button[type="submit"]');
        const paginationLinks = document.querySelectorAll('.pagination-item');

        jumpForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const pageValue = parseInt(pageInput.value);
            const maxPage = parseInt(pageInput.getAttribute('max'));
            if (pageValue < 1 || pageValue > maxPage || isNaN(pageValue)) {
                swal({
                    title: "Invalid Page",
                    text: "Please enter a valid page number.",
                    icon: "warning",
                });
                return;
            }
            const currentParams = new URLSearchParams(location.search);
            currentParams.set('page', pageValue);
            const newUrl = jumpForm.action + '?' + currentParams.toString();
            window.location.href = newUrl;
        });

        pageInput.addEventListener('input', function() {
            const pageValue = parseInt(this.value);
            const maxPage = parseInt(this.getAttribute('max'));
            jumpButton.disabled = pageValue < 1 || pageValue > maxPage || isNaN(pageValue);
            jumpButton.classList.toggle('opacity-50', jumpButton.disabled);
            jumpButton.classList.toggle('cursor-not-allowed', jumpButton.disabled);
        });

        paginationLinks.forEach(link => {
            if (link.getAttribute('aria-disabled') === 'true') {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                });
            }
        });
        
        // Reset filter button
        const resetFilterBtn = document.getElementById('resetFilter');
        if (resetFilterBtn) {
            resetFilterBtn.addEventListener('click', function() {
                const filterForm = this.closest('form');
                const inputs = filterForm.querySelectorAll('input:not([type="submit"])');
                inputs.forEach(input => {
                    if (input.type === 'radio') {
                        input.checked = input.value === '';
                    } else {
                        input.value = '';
                    }
                });
            });
        }
    });

    let this_biz_id = "";
    let clearTimer;
    let items = <?= json_encode($items) ?>;
    
    function openModal(index) {
        let is_read = 0;
        const item = items[index];
        this_biz_id = item["biz_id"];
        title = item["title"];
        const typeName = <?= json_encode($typeNames) ?>[item.type] || "未知";
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
            }
        }
        
        read_it(this_biz_id, title);
        
        if (item.type === "seiue.questionnaire.answer") {
            window.location.href = `https://go-c3.seiue.com/questionnaire-submit?id=${this_biz_id}`;
            return 1;
        }
        if (item.type === "seiue.evaluation.answer") {
            window.location.href = `https://go-c3.seiue.com/evaluations-screen?id=${this_biz_id}`;
            return 1;
        }
        
        document.getElementById('modalTitle').innerHTML = item.title;
        if (item.expired_at.startsWith('<span class="text-pink-500">Today')) {
            document.getElementById('modalTitle').innerHTML += '&nbsp;&nbsp;<span class="inline-flex items-center rounded-md bg-pink-50 px-2 py-1 text-xs font-medium text-pink-700 ring-1 ring-inset ring-pink-700/10">Expires Today</span>';
        }
        else if (item.expired_at.startsWith('<span class="text-pink-300">Tomorrow')) {
            document.getElementById('modalTitle').innerHTML += '&nbsp;&nbsp;<span class="inline-flex items-center rounded-md bg-pink-40 px-2 py-1 text-xs font-medium text-pink-600 ring-1 ring-inset ring-pink-700/10">Expires Tomorrow</span>';
        }
        
        document.getElementById("modalCreated").innerHTML = "Created at: " + ((item.created_at) ? item.created_at : "Not Set");         
    
        let content = item.content;
        if (is_read) {
            content += '<div class="mt-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-green-100 text-green-800">Read</span></div>';
        }
        // Check if the task type is "资料" (document/material)
        if (typeName === "资料" || typeName === "未知") {
            document.getElementById('upload').classList.add('hidden');
        } else {
            document.getElementById('upload').classList.remove('hidden');
        }
        document.getElementById('modalContent').innerHTML = content;
        document.getElementById('modal').classList.remove('hidden');
        document.getElementById('modal').classList.add('flex');
        document.getElementById('modal').classList.add('active');
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('modal').classList.remove('active');
        setTimeout(() => {
            document.getElementById('modal').classList.add('hidden');
            document.getElementById('modal').classList.remove('flex');
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }, 300);
        this_biz_id = "";
    }

    // Function to handle ignoring a task
    async function ignoreTask(taskId) {
        if (!taskId) return;
        
        // Ask for confirmation using SweetAlert
        const willIgnore = await swal({
            title: "Ignore this task?",
            text: "This task will be marked as ignored and won't appear in your pending tasks.",
            icon: "warning",
            buttons: ["Cancel", "Ignore"],
            dangerMode: true,
        });
        
        if (!willIgnore) return;
        
        try {
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
            
            if (result.success) {
                swal({
                    title: "Task Ignored",
                    text: "The task has been successfully ignored.",
                    icon: "success",
                });
                
                // Refresh the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                swal({
                    title: "Error",
                    text: result.message || "Failed to ignore task",
                    icon: "error",
                });
            }
        } catch (error) {
            console.error('Error:', error);
            swal({
                title: "Error",
                text: "Failed to ignore task. Please try again.",
                icon: "error",
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const uploadStatus = document.getElementById('uploadStatus');
        const ost = document.getElementById("onlysubmittext");
        const txtarea = document.getElementById("textarea");
        
        fileInput.addEventListener('change', function(e) {
            updateFileList(e.target.files);
        });
        
        function updateFileList(files) {
            fileList.innerHTML = '';
            for (let file of files) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                fileList.innerHTML += `
                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-100">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="truncate max-w-[200px]">${file.name}</span>
                        </div>
                        <span class="text-xs text-gray-500 ml-2">${fileSize} MB</span>
                    </div>`;
            }
        }
        
        ost.addEventListener('click', async function(e) {
            if (!txtarea.value.trim()) {
                swal({
                    title: "Empty Text",
                    text: "Please enter some text before submitting.",
                    icon: "warning",
                });
                return;
            }
            
            uploadStatus.textContent = 'Submitting...';
            uploadStatus.classList.add('animate-pulse');
            
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
                
                confetti({
                    particleCount: 150,
                    spread: 70,
                    origin: { y: 0.6 }
                });
                
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
                uploadStatus.textContent = 'An error occurred during submit.';
                uploadStatus.classList.remove('animate-pulse');
                
                if (clearTimer) {
                    clearTimeout(clearTimer);
                }

                clearTimer = setTimeout(() => {
                    uploadStatus.textContent = '';
                }, 2000);
            }
        });

        fileInput.addEventListener('change', async function(e) {
            const files = e.target.files;
            if (files.length === 0) {
                uploadStatus.textContent = 'No files selected.';
                return;
            }

            uploadStatus.textContent = 'Uploading...';
            uploadStatus.classList.add('animate-pulse');
            
            const uploadify = await submitFileInfos();
            const formData = new FormData();
            formData.append("biz_id", this_biz_id);
            formData.append("max_file_id", max_file_id);
            formData.append("SUBMIT_TEXT", txtarea.value);

            for (let i = 0; i < files.length; i++) {
                formData.append(`filehash${i}`, arr_of_md5[i]);
                formData.append(`filename${i}`, files[i].name);
            }
            // 接下来的这行用于清空arr_of_md5，虽然看起来很奇怪
            arr_of_md5.length = 0;
            max_file_id = -1;
            
            try {
                const response = await fetch('up-and-submit.php', {
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
                
                confetti({
                    particleCount: 150,
                    spread: 70,
                    origin: { y: 0.6 }
                });

                if (clearTimer) {
                    clearTimeout(clearTimer);
                }

                clearTimer = setTimeout(() => {
                    uploadStatus.textContent = '';
                    fileList.innerHTML = '';
                    txtarea.value = "";
                    closeModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }, 2000);
            } catch (error) {
                console.error('Error:', error);
                uploadStatus.textContent = 'An error occurred during upload.';
                uploadStatus.classList.remove('animate-pulse');
                
                if (clearTimer) {
                    clearTimeout(clearTimer);
                }

                clearTimer = setTimeout(() => {
                    uploadStatus.textContent = '';
                    fileList.innerHTML = '';
                }, 2000);
            }

            fileInput.value = '';
        });
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        const filterBtn = document.getElementById('filterBtn');
        const filterModal = document.getElementById('filterModal');
        const closeFilterModal = document.getElementById('closeFilterModal');
        const applyFilter = document.getElementById('applyFilter');

        filterBtn.addEventListener('click', () => {
            filterModal.classList.remove('hidden');
            filterModal.classList.add('flex');
            filterModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        closeFilterModal.addEventListener('click', () => {
            filterModal.classList.remove('active');
            setTimeout(() => {
                filterModal.classList.add('hidden');
                filterModal.classList.remove('flex');
                document.body.style.overflow = '';
            }, 300);
        });

        filterModal.addEventListener('click', (e) => {
            if (e.target === filterModal) {
                closeFilterModal.click();
            }
        });
        
        const modal = document.getElementById('modal');
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    });

    document.getElementById('toggleInput').addEventListener('click', function() {
        var inputArea = document.getElementById('inputArea');
        var toggleButton = document.getElementById('toggleInput');
        if (inputArea.classList.contains('hidden')) {
            inputArea.classList.remove('hidden');
            toggleButton.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span>Hide Input</span>
            `;
        } else {
            inputArea.classList.add('hidden');
            toggleButton.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span>Input Text</span>
            `;
        }
    });
    
    async function submit_all_ok(arg_biz_id) {
        const result = await swal({
            title: 'Continue?',
            text: 'It is highly recommended to reflect after exams;\nIf continued, Sue will submit the text "全对" for this task item.',
            icon: 'warning',
            buttons: {
                cancel: {
                    text: "Cancel",
                    value: null,
                    visible: true,
                    closeModal: true,
                },
                confirm: {
                    text: "OK",
                    value: true,
                    visible: true,
                    closeModal: true
                }
            },
            dangerMode: true
        });
        
        if (result) {
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
                
                swal({
                    title: "Success!",
                    text: result.message,
                    icon: "success",
                });
                
                confetti({
                    particleCount: 150,
                    spread: 70,
                    origin: { y: 0.6 }
                });
                
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } catch (error) {
                console.error('Error:', error);
                swal({
                    title: "Error",
                    text: "An error occurred during submission.",
                    icon: "error",
                });
            }
        }
    }
    </script>
</body>
</html>