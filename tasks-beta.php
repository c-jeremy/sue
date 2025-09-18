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

// Calculate task statistics
$taskStats = [
    'total' => count($items),
    'completed' => 0,
    'pending' => 0,
    'expired' => 0,
    'today' => 0,
    'tomorrow' => 0
];

foreach ($items as $item) {
    if ($item['status'] === 'completed') {
        $taskStats['completed']++;
    } else {
        $taskStats['pending']++;
    }
    
    if (strpos($item['expired_at'], 'Expired') !== false) {
        $taskStats['expired']++;
    } elseif (strpos($item['expired_at'], 'Today') !== false) {
        $taskStats['today']++;
    } elseif (strpos($item['expired_at'], 'Tomorrow') !== false) {
        $taskStats['tomorrow']++;
    }
}

// Calculate completion percentage
$completionPercentage = $taskStats['total'] > 0 ? round(($taskStats['completed'] / $taskStats['total']) * 100) : 0;

// Group tasks by type for the chart
$tasksByType = [];
foreach ($items as $item) {
    $type = $item['type'];
    if (!isset($tasksByType[$type])) {
        $tasksByType[$type] = 0;
    }
    $tasksByType[$type]++;
}

// Sort tasks by due date
usort($items, function($a, $b) {
    // First, prioritize tasks due today
    $aIsToday = strpos($a['expired_at'], 'Today') !== false;
    $bIsToday = strpos($b['expired_at'], 'Today') !== false;
    
    if ($aIsToday && !$bIsToday) return -1;
    if (!$aIsToday && $bIsToday) return 1;
    
    // Then, prioritize tasks due tomorrow
    $aIsTomorrow = strpos($a['expired_at'], 'Tomorrow') !== false;
    $bIsTomorrow = strpos($b['expired_at'], 'Tomorrow') !== false;
    
    if ($aIsTomorrow && !$bIsTomorrow) return -1;
    if (!$aIsTomorrow && $bIsTomorrow) return 1;
    
    // Then, prioritize expired tasks
    $aIsExpired = strpos($a['expired_at'], 'Expired') !== false;
    $bIsExpired = strpos($b['expired_at'], 'Expired') !== false;
    
    if ($aIsExpired && !$bIsExpired) return -1;
    if (!$aIsExpired && $bIsExpired) return 1;
    
    // Default to sorting by status (pending first)
    if ($a['status'] === 'pending' && $b['status'] !== 'pending') return -1;
    if ($a['status'] !== 'pending' && $b['status'] === 'pending') return 1;
    
    return 0;
});

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

$typeColors = [
    "seiue.class_homework_task" => "#3b82f6",
    "seiue.class_document_task" => "#8b5cf6",
    "seiue.item_afterthought_todo" => "#eab308",
    "seiue.class_questionnaire_task" => "#22c55e",
    "seiue.questionnaire.answer" => "#14b8a6",
    "seiue.evaluation.answer" => "#f97316",
    "seiue.psychological.assessment" => "#6366f1",
    "online_class.unbind_to_reflection" => "#06b6d4",
    "chat.submit_evaluation" => "#84cc16",
    "handout.answer" => "#f59e0b",
    "seiue.scms.election.student" => "#d946ef",
    "seiue.direction.start_notification" => "#0ea5e9"
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

$ohno = ($_REQUEST["from_login"]==="yes") ? 1 : 0;

// Get current time for greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour < 18) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

// Generate random colors for the ambient background
$colors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f97316', '#84cc16', '#14b8a6'];
$randomColor1 = $colors[array_rand($colors)];
$randomColor2 = $colors[array_rand($colors)];
$randomColor3 = $colors[array_rand($colors)];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./favicon.png" type="image/png">
    <title>Tasks - Sue</title>
    <script src="./twind.js"></script>
    <!-- Add Three.js for WebGL effects -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <!-- Add GSAP for advanced animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/ScrollTrigger.min.js"></script>
    <!-- Add Particles.js for particle effects -->
    <script src="https://lf3-cdn-tos.bytecdntp.com/cdn/expire-1-M/particles.js/2.0.0/particles.min.js" type="application/javascript"></script>
    <!-- Add Lottie for vector animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.7.8/lottie.min.js"></script>
    <!-- Add Howler.js for spatial audio -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.3/howler.min.js"></script>
    <!-- Add Variable fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Plus+Jakarta+Sans:wght@200..800&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        
        /* CSS Variables */
        :root {
            /* Color Palette */
            --primary: 111, 114, 241; /* #6F72F1 */
            --primary-light: 143, 146, 243; /* #8F92F3 */
            --primary-dark: 79, 82, 209; /* #4F52D1 */
            --secondary: 236, 72, 153; /* #EC4899 */
            --secondary-light: 244, 114, 182; /* #F472B6 */
            --secondary-dark: 219, 39, 119; /* #DB2777 */
            --tertiary: 20, 184, 166; /* #14B8A6 */
            --success: 16, 185, 129; /* #10B981 */
            --warning: 245, 158, 11; /* #F59E0B */
            --danger: 239, 68, 68; /* #EF4444 */
            --info: 59, 130, 246; /* #3B82F6 */
            
            /* Neutral Colors */
            --white: 255, 255, 255; /* #FFFFFF */
            --black: 0, 0, 0; /* #000000 */
            --gray-50: 249, 250, 251; /* #F9FAFB */
            --gray-100: 243, 244, 246; /* #F3F4F6 */
            --gray-200: 229, 231, 235; /* #E5E7EB */
            --gray-300: 209, 213, 219; /* #D1D5DB */
            --gray-400: 156, 163, 175; /* #9CA3AF */
            --gray-500: 107, 114, 128; /* #6B7280 */
            --gray-600: 75, 85, 99; /* #4B5563 */
            --gray-700: 55, 65, 81; /* #374151 */
            --gray-800: 31, 41, 55; /* #1F2937 */
            --gray-900: 17, 24, 39; /* #111827 */
            
            /* Spacing */
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-10: 2.5rem;
            --space-12: 3rem;
            --space-16: 4rem;
            --space-20: 5rem;
            --space-24: 6rem;
            --space-32: 8rem;
            --space-40: 10rem;
            --space-48: 12rem;
            --space-56: 14rem;
            --space-64: 16rem;
            
            /* Border Radius */
            --radius-sm: 0.125rem;
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
            --radius-xl: 0.75rem;
            --radius-2xl: 1rem;
            --radius-3xl: 1.5rem;
            --radius-full: 9999px;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
            
            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 300ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-bounce: 500ms cubic-bezier(0.34, 1.56, 0.64, 1);
            --transition-elastic: 600ms cubic-bezier(0.68, -0.6, 0.32, 1.6);
            
            /* Z-index */
            --z-0: 0;
            --z-10: 10;
            --z-20: 20;
            --z-30: 30;
            --z-40: 40;
            --z-50: 50;
            --z-auto: auto;
            
            /* Theme */
            --bg-primary: 255, 255, 255;
            --bg-secondary: 249, 250, 251;
            --bg-tertiary: 243, 244, 246;
            --text-primary: 17, 24, 39;
            --text-secondary: 75, 85, 99;
            --text-tertiary: 156, 163, 175;
            --border-color: 229, 231, 235;
            
            /* Advanced 3D */
            --perspective: 1000px;
            --transform-origin: center;
            --depth-factor: 30px;
            
            /* Neumorphism */
            --neu-light: rgba(255, 255, 255, 0.5);
            --neu-dark: rgba(0, 0, 0, 0.05);
            --neu-flat: 0px 0px 0px rgba(0, 0, 0, 0.05);
            --neu-concave: inset 5px 5px 10px var(--neu-dark), 
                           inset -5px -5px 10px var(--neu-light);
            --neu-convex: 5px 5px 10px var(--neu-dark), 
                          -5px -5px 10px var(--neu-light);
            --neu-pressed: inset 2px 2px 5px var(--neu-dark), 
                           inset -2px -2px 5px var(--neu-light);
        }
        
        .dark-mode {
            --bg-primary: 17, 24, 39;
            --bg-secondary: 31, 41, 55;
            --bg-tertiary: 55, 65, 81;
            --text-primary: 249, 250, 251;
            --text-secondary: 209, 213, 219;
            --text-tertiary: 156, 163, 175;
            --border-color: 75, 85, 99;
            
            /* Neumorphism Dark Mode */
            --neu-light: rgba(255, 255, 255, 0.05);
            --neu-dark: rgba(0, 0, 0, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            font-size: 16px;
            scroll-behavior: smooth;
            font-feature-settings: "ss01", "ss02", "cv01", "cv02";
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            background-color: rgba(var(--bg-primary), 1);
            color: rgba(var(--text-primary), 1);
            transition: background-color var(--transition-normal), color var(--transition-normal);
            overflow-x: hidden;
            min-height: 100vh;
            position: relative;
        }
        
        /* WebGL Canvas */
        #webgl-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            pointer-events: none;
        }
        
        /* Particles Container */
        #particles-js {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }
        
        /* Ambient Background */
        .ambient-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            opacity: 0.5;
        }
        
        .ambient-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: float 20s infinite alternate ease-in-out;
            mix-blend-mode: screen;
        }
        
        .ambient-blob:nth-child(1) {
            width: 500px;
            height: 500px;
            background-color: <?= $randomColor1 ?>;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }
        
        .ambient-blob:nth-child(2) {
            width: 600px;
            height: 600px;
            background-color: <?= $randomColor2 ?>;
            bottom: -200px;
            right: -100px;
            animation-delay: -5s;
        }
        
        .ambient-blob:nth-child(3) {
            width: 400px;
            height: 400px;
            background-color: <?= $randomColor3 ?>;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -10s;
        }
        
        .dark-mode .ambient-blob {
            opacity: 0.15;
            mix-blend-mode: overlay;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(var(--bg-tertiary), 0.5);
            border-radius: var(--radius-full);
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(var(--primary), 0.7);
            border-radius: var(--radius-full);
            border: 2px solid rgba(var(--bg-primary), 1);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(var(--primary), 0.9);
        }
        
        /* Animations */
        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(5%, 5%) rotate(5deg); }
            50% { transform: translate(0%, 10%) rotate(0deg); }
            75% { transform: translate(-5%, 5%) rotate(-5deg); }
            100% { transform: translate(0, 0) rotate(0deg); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
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
        
        @keyframes fadeInDown {
            from { 
                opacity: 0; 
                transform: translateY(-20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        @keyframes fadeInLeft {
            from { 
                opacity: 0; 
                transform: translateX(-20px); 
            }
            to { 
                opacity: 1; 
                transform: translateX(0); 
            }
        }
        
        @keyframes fadeInRight {
            from { 
                opacity: 0; 
                transform: translateX(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateX(0); 
            }
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0);
                opacity: 1;
            }
            100% {
                transform: scale(1);
                opacity: 0;
            }
        }
        
        @keyframes morphing {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
            25% { border-radius: 58% 42% 75% 25% / 76% 46% 54% 24%; }
            50% { border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%; }
            75% { border-radius: 33% 67% 58% 42% / 63% 68% 32% 37%; }
            100% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes text-wave {
            0%, 100% { transform: translateY(0); }
            25% { transform: translateY(-5px); }
            75% { transform: translateY(5px); }
        }
        
        @keyframes text-blur {
            0%, 100% { filter: blur(0px); }
            50% { filter: blur(4px); }
        }
        
        /* Animation Classes */
        .animate-float { animation: float 6s infinite ease-in-out; }
        .animate-pulse { animation: pulse 2s infinite ease-in-out; }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
        .animate-fade-in-up { animation: fadeInUp 0.5s ease-out forwards; }
        .animate-fade-in-down { animation: fadeInDown 0.5s ease-out forwards; }
        .animate-fade-in-left { animation: fadeInLeft 0.5s ease-out forwards; }
        .animate-fade-in-right { animation: fadeInRight 0.5s ease-out forwards; }
        .animate-spin { animation: spin 2s linear infinite; }
        .animate-shimmer { animation: shimmer 1.5s infinite; }
        .animate-bounce { animation: bounce 2s infinite; }
        .animate-morphing { animation: morphing 8s infinite; }
        .animate-gradient { animation: gradient-shift 3s ease infinite; }
        .animate-text-wave { animation: text-wave 2s ease-in-out infinite; }
        .animate-text-blur { animation: text-blur 3s ease-in-out infinite; }
        
        /* Animation Delays */
        .delay-0 { animation-delay: 0s; }
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        .delay-500 { animation-delay: 0.5s; }
        .delay-600 { animation-delay: 0.6s; }
        .delay-700 { animation-delay: 0.7s; }
        .delay-800 { animation-delay: 0.8s; }
        .delay-900 { animation-delay: 0.9s; }
        .delay-1000 { animation-delay: 1s; }
        
        /* Glassmorphism */
        .glass {
            background: rgba(var(--bg-primary), 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(var(--white), 0.2);
            box-shadow: 0 8px 32px 0 rgba(var(--black), 0.1);
        }
        
        .glass-intense {
            background: rgba(var(--bg-primary), 0.5);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(var(--white), 0.3);
            box-shadow: 
                0 8px 32px 0 rgba(var(--black), 0.1),
                inset 0 0 0 1px rgba(var(--white), 0.1);
        }
        
        .dark-mode .glass {
            background: rgba(var(--bg-primary), 0.7);
            border: 1px solid rgba(var(--white), 0.05);
        }
        
        /* Neumorphism */
        .neu-flat {
            background: rgba(var(--bg-primary), 1);
            box-shadow: var(--neu-flat);
            transition: box-shadow var(--transition-normal);
        }
        
        .neu-convex {
            background: rgba(var(--bg-primary), 1);
            box-shadow: var(--neu-convex);
            transition: box-shadow var(--transition-normal);
        }
        
        .neu-concave {
            background: rgba(var(--bg-primary), 1);
            box-shadow: var(--neu-concave);
            transition: box-shadow var(--transition-normal);
        }
        
        .neu-pressed {
            background: rgba(var(--bg-primary), 1);
            box-shadow: var(--neu-pressed);
            transition: box-shadow var(--transition-normal);
        }
        
        /* 3D Card Effect */
        .card-3d {
            transform-style: preserve-3d;
            perspective: var(--perspective);
            transition: transform var(--transition-normal);
        }
        
        .card-3d-content {
            transition: transform var(--transition-normal);
            transform: translateZ(0);
            will-change: transform;
            backface-visibility: hidden;
        }
        
        .card-3d:hover .card-3d-content {
            transform: translateZ(var(--depth-factor));
        }
        
        .card-3d-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            transform: translateZ(calc(var(--layer-index) * -5px));
            z-index: calc(-1 * var(--layer-index));
            pointer-events: none;
        }
        
        /* Task Cards */
        .task-card {
            background-color: rgba(var(--bg-primary), 1);
            border-radius: var(--radius-2xl);
            box-shadow: 
                0 10px 15px -3px rgba(var(--black), 0.1),
                0 4px 6px -2px rgba(var(--black), 0.05);
            transition: 
                transform var(--transition-bounce),
                box-shadow var(--transition-normal),
                background-color var(--transition-normal);
            position: relative;
            overflow: hidden;
            transform: translateZ(0);
            will-change: transform, box-shadow;
            border: 1px solid rgba(var(--border-color), 0.5);
        }
        
        .task-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: rgba(var(--primary), 1);
            opacity: 0;
            transition: opacity var(--transition-normal);
        }
        
        .task-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 20px 25px -5px rgba(var(--black), 0.1),
                0 10px 10px -5px rgba(var(--black), 0.04);
        }
        
        .task-card:hover::before {
            opacity: 1;
        }
        
        .task-card.completed {
            opacity: 0.8;
        }
        
        .task-card.completed::before {
            background-color: rgba(var(--success), 1);
            opacity: 1;
        }
        
        .task-card.expired::before {
            background-color: rgba(var(--danger), 1);
            opacity: 1;
        }
        
        /* Morphing Cards */
        .morphing-card {
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            animation: morphing 8s infinite;
            background-image: linear-gradient(
                45deg, 
                rgba(var(--primary), 0.8),
                rgba(var(--secondary), 0.8)
            );
            transition: all var(--transition-normal);
            overflow: hidden;
        }
        
        .morphing-card:hover {
            animation-play-state: paused;
            transform: scale(1.05);
        }
        
        /* Progress Bar */
        .progress-bar {
            height: 8px;
            background-color: rgba(var(--bg-tertiary), 0.5);
            border-radius: var(--radius-full);
            overflow: hidden;
            position: relative;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, 
                rgba(var(--primary-light), 1), 
                rgba(var(--primary), 1));
            border-radius: var(--radius-full);
            transition: width 1s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }
        
        .progress-bar-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.4) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            animation: shimmer 2s infinite;
        }
        
        /* Buttons */
        .btn {
            position: relative;
            overflow: hidden;
            transition: all var(--transition-normal);
            transform: translateZ(0);
            will-change: transform, box-shadow, background-color;
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
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }
        
        .btn:active::after {
            animation: ripple 0.6s ease-out;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(1px);
        }
        
        /* Magnetic Buttons */
        .btn-magnetic {
            transition: transform var(--transition-normal);
            transform-style: preserve-3d;
        }
        
        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition-normal);
            transform: translateZ(0);
            will-change: transform;
        }
        
        .badge:hover {
            transform: scale(1.1);
        }
        
        /* Modal */
        .modal {
            transition: opacity var(--transition-normal);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        
        .modal-content {
            transition: 
                transform var(--transition-bounce),
                opacity var(--transition-normal);
            transform: translateY(20px) scale(0.95);
            opacity: 0;
            will-change: transform, opacity;
        }
        
        .modal.active .modal-content {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        
        .modal-open .task-card {
            filter: blur(4px);
            pointer-events: none;
            transform: scale(0.95);
        }
        
        /* Tooltip */
        .tooltip {
            position: relative;
            z-index: 10;
        }
        
        .tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%) scale(0);
            background: rgba(var(--bg-primary), 0.9);
            color: rgba(var(--text-primary), 1);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-lg);
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
            pointer-events: none;
            z-index: 20;
            border: 1px solid rgba(var(--border-color), 0.5);
        }
        
        .tooltip:hover::after {
            transform: translateX(-50%) scale(1);
            opacity: 1;
        }
        
        /* Dashboard Cards */
        .dashboard-card {
            background-color: rgba(var(--bg-primary), 1);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
            overflow: hidden;
            border: 1px solid rgba(var(--border-color), 0.5);
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        /* Custom Cursor */
        .custom-cursor {
            position: fixed;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: rgba(var(--primary), 0.5);
            pointer-events: none;
            z-index: 9999;
            transform: translate(-50%, -50%);
            transition: 
                width 0.2s ease,
                height 0.2s ease,
                background-color 0.2s ease;
            mix-blend-mode: difference;
            filter: drop-shadow(0 0 5px rgba(var(--primary), 0.3));
        }
        
        .custom-cursor.hover {
            width: 40px;
            height: 40px;
            background-color: rgba(var(--primary), 0.3);
        }
        
        .custom-cursor.click {
            transform: translate(-50%, -50%) scale(0.8);
            background-color: rgba(var(--primary), 0.8);
        }
        
        .custom-cursor-trail {
            position: fixed;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: rgba(var(--primary), 0.3);
            pointer-events: none;
            z-index: 9998;
            transform: translate(-50%, -50%);
            transition: opacity 0.5s ease;
        }
        
        /* Dark Mode Toggle */
        .dark-mode-toggle {
            position: relative;
            width: 60px;
            height: 30px;
            border-radius: 15px;
            background-color: rgba(var(--bg-tertiary), 1);
            cursor: pointer;
            transition: background-color var(--transition-normal);
            overflow: hidden;
        }
        
        .dark-mode-toggle::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: rgba(var(--bg-primary), 1);
            box-shadow: var(--shadow-md);
            transition: transform var(--transition-normal);
        }
        
        .dark-mode-toggle::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(40deg, #ff0080, #ff8c00 70%);
            opacity: 0;
            transition: opacity var(--transition-normal);
            z-index: -1;
        }
        
        .dark-mode .dark-mode-toggle {
            background-color: rgba(var(--primary), 1);
        }
        
        .dark-mode .dark-mode-toggle::after {
            transform: translateX(30px);
        }
        
        .dark-mode .dark-mode-toggle::before {
            opacity: 1;
        }
        
        /* Scroll Progress Indicator */
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: rgba(var(--primary), 0.2);
            z-index: 1000;
        }
        
        .scroll-progress-bar {
            height: 100%;
            background: linear-gradient(
                90deg,
                rgba(var(--primary), 1) 0%,
                rgba(var(--secondary), 1) 100%
            );
            width: 0%;
            transition: width 0.1s ease;
        }
        
        /* Parallax Scrolling */
        .parallax-container {
            position: relative;
            overflow: hidden;
            height: 100%;
            perspective: 1px;
        }
        
        .parallax-layer {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            transform-origin: 0 0;
        }
        
        .parallax-layer-back {
            transform: translateZ(-1px) scale(2);
        }
        
        .parallax-layer-base {
            transform: translateZ(0);
        }
        
        .parallax-layer-front {
            transform: translateZ(1px) scale(0.5);
        }
        
        /* Utility Classes */
        .text-gradient {
            background: linear-gradient(
                135deg, 
                rgba(var(--primary), 1) 0%, 
                rgba(var(--secondary), 1) 100%
            );
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .text-gradient-animated {
            background: linear-gradient(
                270deg, 
                rgba(var(--primary), 1), 
                rgba(var(--secondary), 1), 
                rgba(var(--tertiary), 1), 
                rgba(var(--secondary), 1), 
                rgba(var(--primary), 1)
            );
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: gradient-shift 5s linear infinite;
        }
        
        .bg-gradient {
            background: linear-gradient(
                135deg, 
                rgba(var(--primary), 1) 0%, 
                rgba(var(--secondary), 1) 100%
            );
        }
        
        .bg-gradient-animated {
            background: linear-gradient(
                270deg, 
                rgba(var(--primary), 1), 
                rgba(var(--secondary), 1), 
                rgba(var(--tertiary), 1), 
                rgba(var(--secondary), 1), 
                rgba(var(--primary), 1)
            );
            background-size: 200% auto;
            animation: gradient-shift 5s linear infinite;
        }
        
        /* Text Effects */
        .text-shadow {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .text-outline {
            -webkit-text-stroke: 1px rgba(var(--text-primary), 0.5);
            color: transparent;
        }
        
        .text-glitch {
            position: relative;
            text-shadow: 
                0.05em 0 0 rgba(255, 0, 0, 0.75),
                -0.025em -0.05em 0 rgba(0, 255, 0, 0.75),
                0.025em 0.05em 0 rgba(0, 0, 255, 0.75);
            animation: text-glitch 500ms infinite;
        }
        
        @keyframes text-glitch {
            0% {
                text-shadow: 
                    0.05em 0 0 rgba(255, 0, 0, 0.75),
                    -0.025em -0.05em 0 rgba(0, 255, 0, 0.75),
                    0.025em 0.05em 0 rgba(0, 0, 255, 0.75);
            }
            14% {
                text-shadow: 
                    0.05em 0 0 rgba(255, 0, 0, 0.75),
                    -0.025em -0.05em 0 rgba(0, 255, 0, 0.75),
                    0.025em 0.05em 0 rgba(0, 0, 255, 0.75);
            }
            15% {
                text-shadow: 
                    -0.05em -0.025em 0 rgba(255, 0, 0, 0.75),
                    0.025em 0.025em 0 rgba(0, 255, 0, 0.75),
                    -0.05em -0.05em 0 rgba(0, 0, 255, 0.75);
            }
            49% {
                text-shadow: 
                    -0.05em -0.025em 0 rgba(255, 0, 0, 0.75),
                    0.025em 0.025em 0 rgba(0, 255, 0, 0.75),
                    -0.05em -0.05em 0 rgba(0, 0, 255, 0.75);
            }
            50% {
                text-shadow: 
                    0.025em 0.05em 0 rgba(255, 0, 0, 0.75),
                    0.05em 0 0 rgba(0, 255, 0, 0.75),
                    0 -0.05em 0 rgba(0, 0, 255, 0.75);
            }
            99% {
                text-shadow: 
                    0.025em 0.05em 0 rgba(255, 0, 0, 0.75),
                    0.05em 0 0 rgba(0, 255, 0, 0.75),
                    0 -0.05em 0 rgba(0, 0, 255, 0.75);
            }
            100% {
                text-shadow: 
                    -0.025em 0 0 rgba(255, 0, 0, 0.75),
                    -0.025em -0.025em 0 rgba(0, 255, 0, 0.75),
                    -0.025em -0.05em 0 rgba(0, 0, 255, 0.75);
            }
        }
        
        /* Responsive Typography */
        @media (max-width: 640px) {
            html {
                font-size: 14px;
            }
        }
        
        @media (min-width: 1536px) {
            html {
                font-size: 18px;
            }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen" id="app">
    <!-- WebGL Background -->
    <canvas id="webgl-background"></canvas>
    
    <!-- Particles Background -->
    <div id="particles-js"></div>
    
    <!-- Custom Cursor -->
    <div class="custom-cursor hidden md:block"></div>
    <div id="cursor-trails-container"></div>
    
    <!-- Scroll Progress Indicator -->
    <div class="scroll-progress">
        <div class="scroll-progress-bar" id="scrollProgress"></div>
    </div>
    
    <!-- Ambient Background -->
    <div class="ambient-background">
        <div class="ambient-blob"></div>
        <div class="ambient-blob"></div>
        <div class="ambient-blob"></div>
    </div>
    
    <div class="container mx-auto px-4 py-6 max-w-7xl relative">
        <?php
        $include_src = "tasks";
        require "./global-header.php";
        ?>
        
        <!-- Dashboard Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12 mt-8">
            <div class="animate-fade-in-left delay-100">
                <h1 class="text-5xl font-extrabold mb-3 text-gradient-animated"><?= $greeting ?></h1>
                <p class="text-gray-500 text-lg">Your task management dashboard</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-4 self-end animate-fade-in-right delay-200">
                <!-- Voice Command Button -->
                <button id="voiceCommandBtn" class="btn btn-magnetic flex items-center gap-2 px-4 py-2 bg-white text-gray-700 rounded-full shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                    </svg>
                    <span>Voice</span>
                </button>
                
                <!-- Dark Mode Toggle -->
                <div class="dark-mode-toggle mr-2" id="darkModeToggle" title="Toggle Dark Mode"></div>
                
                <!-- Aurora Button -->
                <a href="/aurora.php" class="btn btn-magnetic flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-full shadow-md hover:shadow-lg transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Aurora</span>
                </a>
                
                <!-- Filter Button -->
                <button id="filterBtn" class="btn btn-magnetic flex items-center gap-2 px-4 py-2 bg-white text-gray-700 rounded-full shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <span>Filter</span>
                </button>
                
                <!-- View Toggle -->
                <?php if($_REQUEST["tasks"]!=="all"){ ?>
                <a href="./tasks.php?tasks=all&page=1" class="btn btn-magnetic flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-full shadow-md hover:shadow-lg transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    <span>View All Tasks</span>
                </a>
                <?php } else { ?>
                <a href="/tasks.php" class="btn btn-magnetic flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-full shadow-md hover:shadow-lg transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span>Pending Tasks</span>
                </a>
                <?php } ?>
                
                <!-- View Toggle Buttons -->
                <div class="flex rounded-full bg-gray-100 p-1 ml-2">
                    <button id="gridViewBtn" class="p-1.5 rounded-full bg-white shadow-sm" title="Grid View">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    </button>
                    <button id="listViewBtn" class="p-1.5 rounded-full" title="List View">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <!-- Total Tasks Card -->
            <div class="dashboard-card glass-intense p-6 animate-fade-in-up delay-100 card-3d">
                <div class="card-3d-content">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Total Tasks</h3>
                        <div class="p-2 bg-indigo-100 rounded-full">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <h2 class="text-4xl font-bold"><?= $taskStats['total'] ?></h2>
                            <p class="text-sm text-gray-500 mt-1">Tasks assigned to you</p>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-medium <?= $taskStats['pending'] > 0 ? 'text-amber-500' : 'text-green-500' ?>">
                                <?= $taskStats['pending'] ?> pending
                            </span>
                        </div>
                    </div>
                    <!-- 3D Layers for depth effect -->
                    <div class="card-3d-layer" style="--layer-index: 1;"></div>
                    <div class="card-3d-layer" style="--layer-index: 2;"></div>
                </div>
            </div>
            
            <!-- Completion Rate Card -->
            <div class="dashboard-card glass-intense p-6 animate-fade-in-up delay-200 card-3d">
                <div class="card-3d-content">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Completion Rate</h3>
                        <div class="p-2 bg-green-100 rounded-full">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <h2 class="text-4xl font-bold"><?= $completionPercentage ?>%</h2>
                            <p class="text-sm text-gray-500 mt-1">Tasks completed</p>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-medium text-green-500">
                                <?= $taskStats['completed'] ?> completed
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 progress-bar">
                        <div class="progress-bar-fill" style="width: <?= $completionPercentage ?>%;"></div>
                    </div>
                    <!-- 3D Layers for depth effect -->
                    <div class="card-3d-layer" style="--layer-index: 1;"></div>
                    <div class="card-3d-layer" style="--layer-index: 2;"></div>
                </div>
            </div>
            
            <!-- Due Today Card -->
            <div class="dashboard-card glass-intense p-6 animate-fade-in-up delay-300 card-3d">
                <div class="card-3d-content">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Due Today</h3>
                        <div class="p-2 bg-pink-100 rounded-full">
                            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <h2 class="text-4xl font-bold"><?= $taskStats['today'] ?></h2>
                            <p class="text-sm text-gray-500 mt-1">Tasks due today</p>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-medium text-pink-500">
                                <?= $taskStats['tomorrow'] ?> due tomorrow
                            </span>
                        </div>
                    </div>
                    <!-- 3D Layers for depth effect -->
                    <div class="card-3d-layer" style="--layer-index: 1;"></div>
                    <div class="card-3d-layer" style="--layer-index: 2;"></div>
                </div>
            </div>
            
            <!-- Expired Tasks Card -->
            <div class="dashboard-card glass-intense p-6 animate-fade-in-up delay-400 card-3d">
                <div class="card-3d-content">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Expired Tasks</h3>
                        <div class="p-2 bg-red-100 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <h2 class="text-4xl font-bold"><?= $taskStats['expired'] ?></h2>
                            <p class="text-sm text-gray-500 mt-1">Expired tasks</p>
                        </div>
                        <div class="text-right">
                            <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                                View all
                            </a>
                        </div>
                    </div>
                    <!-- 3D Layers for depth effect -->
                    <div class="card-3d-layer" style="--layer-index: 1;"></div>
                    <div class="card-3d-layer" style="--layer-index: 2;"></div>
                </div>
            </div>
        </div>
        
        <!-- Task View Tabs -->
        <div class="flex border-b border-gray-200 mb-8 animate-fade-in-up delay-500 overflow-x-auto pb-1 glass-intense rounded-t-xl px-4 pt-4">
            <button class="py-2 px-4 text-sm font-medium text-indigo-600 border-b-2 border-indigo-600 focus:outline-none" id="allTasksTab">
                All Tasks
            </button>
            <button class="py-2 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 focus:outline-none" id="todayTasksTab">
                Due Today
            </button>
            <button class="py-2 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 focus:outline-none" id="tomorrowTasksTab">
                Due Tomorrow
            </button>
            <button class="py-2 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 focus:outline-none" id="expiredTasksTab">
                Expired
            </button>
            <button class="py-2 px-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 focus:outline-none" id="completedTasksTab">
                Completed
            </button>
        </div>
        
        <!-- Task Grid View -->
        <div id="gridView" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-12">
            <?php foreach ($items as $index => $item): 
                $isCompleted = $item["status"] === "completed";
                $isExpired = strpos($item["expired_at"], "Expired") !== false;
                $isToday = strpos($item["expired_at"], "Today") !== false;
                $isTomorrow = strpos($item["expired_at"], "Tomorrow") !== false;
                
                $cardClass = "task-card p-6 cursor-pointer card-3d";
                if ($isCompleted) $cardClass .= " completed";
                elseif ($isExpired) $cardClass .= " expired";
                
                $typeClass = $typeClasses[$item["type"]] ?? "bg-gray-100 text-gray-800";
                $typeColor = $typeColors[$item["type"]] ?? "#9ca3af";
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
                
                // Calculate task priority
                $priority = "low";
                if ($isExpired) {
                    $priority = "high";
                } elseif ($isToday) {
                    $priority = "medium";
                }
                
                // Add data attributes for filtering
                $dataAttributes = 'data-status="' . $item['status'] . '" ';
                if ($isToday) $dataAttributes .= 'data-due="today" ';
                elseif ($isTomorrow) $dataAttributes .= 'data-due="tomorrow" ';
                elseif ($isExpired) $dataAttributes .= 'data-due="expired" ';
                else $dataAttributes .= 'data-due="future" ';
                
                // Animation delay based on index
                $delay = ($index % 10) * 100;
                
                // Generate a unique ID for this task
                $taskId = "task-" . $item['id'];
            ?>
            <div class="task-item animate-fade-in-up delay-<?= $delay ?>" <?= $dataAttributes ?> id="<?= $taskId ?>" onclick="openModal(<?= $index ?>)">
                <div class="<?= $cardClass ?> glass-intense">
                    <div class="card-3d-content">
                        <!-- Task Header -->
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold truncate max-w-[90%] flex items-center group-hover:text-indigo-600 transition-colors duration-300">
                                <?php
                                if ($isCompleted) {
                                    echo '<svg class="w-6 h-6 mr-2 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                                }
                                ?>
                                <span class="task-title-text"><?= htmlspecialchars(mb_strlen($item['title']) > 14 ? mb_substr($item['title'], 0, 14).'...' : $item['title']); ?></span>
                            </h2>
                            <span class="badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap <?= $typeClass ?>" style="background-color: <?= $typeColor ?>20; color: <?= $typeColor ?>;">
                                <?= $typeName ?>
                            </span>
                        </div>
                        
                        <!-- Task Details -->
                        <div class="space-y-3 text-sm">
                            <p class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="<?= $isToday ? 'text-pink-500 font-medium animate-pulse' : ($isTomorrow ? 'text-pink-300 font-medium' : '') ?>">
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
                        
                        <!-- Task Progress (for homework) -->
                        <?php if ($typeName == "作业" && !$isCompleted): ?>
                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Progress</span>
                                <span>0%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-bar-fill" style="width: 0%;"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Task Status Badge -->
                        <div class="absolute top-4 right-4">
                            <?php if ($isCompleted): ?>
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 animate-pulse">
                                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </span>
                            <?php elseif ($isExpired): ?>
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 animate-pulse">
                                    <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                            <?php elseif ($isToday): ?>
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-pink-100 animate-pulse">
                                    <svg class="h-5 w-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="absolute bottom-4 right-4 flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <?php if ($download_url): ?>
                            <a href="<?= htmlspecialchars($download_url) ?>" target="_blank" rel="noreferrer noopener" 
                               class="tooltip p-2 bg-blue-50 rounded-full text-blue-500 hover:text-blue-600 hover:bg-blue-100 transition-all duration-300 transform hover:scale-110" 
                               data-tooltip="Download File"
                               onclick="event.stopPropagation();">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                            </a>
                            <?php endif; ?>

                            <?php if ($external_url): ?>
                            <a href="<?= htmlspecialchars($external_url) ?>" target="_blank" rel="noreferrer noopener" 
                               class="tooltip p-2 bg-teal-50 rounded-full text-teal-500 hover:text-teal-600 hover:bg-teal-100 transition-all duration-300 transform hover:scale-110" 
                               data-tooltip="Open External Link"
                               onclick="event.stopPropagation();">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                            <?php endif; ?>

                            <?php if ($show_submit_all): ?>
                            <button onclick="submit_all_ok(<?= $item['biz_id']; ?>); event.stopPropagation();" 
                                    class="tooltip p-2 bg-pink-50 rounded-full text-pink-500 hover:text-pink-600 hover:bg-pink-100 transition-all duration-300 transform hover:scale-110"
                                    data-tooltip="Submit All Correct">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($item['status'] == 'pending'): ?>
                            <button onclick="ignoreTask(<?= $item['id']; ?>); event.stopPropagation();" 
                                    class="tooltip p-2 bg-gray-50 rounded-full text-gray-500 hover:text-gray-600 hover:bg-gray-100 transition-all duration-300 transform hover:scale-110"
                                    data-tooltip="Ignore Task">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 3D Layers for depth effect -->
                        <div class="card-3d-layer" style="--layer-index: 1;"></div>
                        <div class="card-3d-layer" style="--layer-index: 2;"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Task List View (Hidden by default) -->
        <div id="listView" class="hidden mb-12">
            <div class="glass-intense rounded-2xl shadow-xl overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-opacity-50 backdrop-blur-sm">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Task
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Due Date
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Group
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($items as $index => $item): 
                            $isCompleted = $item["status"] === "completed";
                            $isExpired = strpos($item["expired_at"], "Expired") !== false;
                            $isToday = strpos($item["expired_at"], "Today") !== false;
                            $isTomorrow = strpos($item["expired_at"], "Tomorrow") !== false;
                            
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
                            
                            // Calculate task priority
                            $priority = "low";
                            if ($isExpired) {
                                $priority = "high";
                            } elseif ($isToday) {
                                $priority = "medium";
                            }
                            
                            // Add data attributes for filtering
                            $dataAttributes = 'data-status="' . $item['status'] . '" ';
                            if ($isToday) $dataAttributes .= 'data-due="today" ';
                            elseif ($isTomorrow) $dataAttributes .= 'data-due="tomorrow" ';
                            elseif ($isExpired) $dataAttributes .= 'data-due="expired" ';
                            else $dataAttributes .= 'data-due="future" ';
                        ?>
                        <tr class="hover:bg-gray-50 cursor-pointer transition-colors duration-300 animate-fade-in-up" onclick="openModal(<?= $index ?>)" <?= $dataAttributes ?>>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if ($isCompleted): ?>
                                        <span class="flex-shrink-0 h-6 w-6 text-green-500">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </span>
                                    <?php elseif ($priority === "high"): ?>
                                        <span class="flex-shrink-0 h-6 w-6 text-red-500 animate-pulse">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                        </span>
                                    <?php elseif ($priority === "medium"): ?>
                                        <span class="flex-shrink-0 h-6 w-6 text-yellow-500 animate-pulse">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </span>
                                    <?php else: ?>
                                        <span class="flex-shrink-0 h-6 w-6 text-gray-400">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['title']); ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($item['creator_name']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap <?= $typeClass ?>">
                                    <?= $typeName ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="<?= $isToday ? 'text-pink-500 font-medium' : ($isTomorrow ? 'text-pink-300 font-medium' : ($isExpired ? 'text-red-500 font-medium' : 'text-gray-500')) ?>">
                                    <?= $item['expired_at'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($item['group_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($isCompleted): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Completed
                                    </span>
                                <?php elseif ($isExpired): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 animate-pulse">
                                        Expired
                                    </span>
                                <?php elseif ($isToday): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-pink-100 text-pink-800 animate-pulse">
                                        Due Today
                                    </span>
                                <?php elseif ($isTomorrow): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        Due Tomorrow
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2" onclick="event.stopPropagation();">
                                    <?php if ($download_url): ?>
                                    <a href="<?= htmlspecialchars($download_url) ?>" target="_blank" rel="noreferrer noopener" 
                                       class="tooltip text-blue-600 hover:text-blue-900 transform hover:scale-110 transition-transform" 
                                       data-tooltip="Download File">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ($external_url): ?>
                                    <a href="<?= htmlspecialchars($external_url) ?>" target="_blank" rel="noreferrer noopener" 
                                       class="tooltip text-teal-600 hover:text-teal-900 transform hover:scale-110 transition-transform" 
                                       data-tooltip="Open External Link">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                    </a>
                                    <?php endif; ?>

                                    <?php if ($show_submit_all): ?>
                                    <button onclick="submit_all_ok(<?= $item['biz_id']; ?>);" 
                                            class="tooltip text-pink-600 hover:text-pink-900 transform hover:scale-110 transition-transform"
                                            data-tooltip="Submit All Correct">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($item['status'] == 'pending'): ?>
                                    <button onclick="ignoreTask(<?= $item['id']; ?>);" 
                                            class="tooltip text-gray-600 hover:text-gray-900 transform hover:scale-110 transition-transform"
                                            data-tooltip="Ignore Task">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Empty State -->
        <?php if (empty($items)): ?>
        <div class="flex flex-col items-center justify-center py-16 text-center animate-fade-in-up">
            <div class="morphing-card w-24 h-24 flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-700 mb-3 text-gradient-animated">No Tasks Found</h3>
            <p class="text-gray-500 max-w-md mb-8">You don't have any tasks matching your current filters. Try adjusting your filters or check back later.</p>
            <button class="btn btn-magnetic px-6 py-3 bg-gradient-animated text-white rounded-full hover:shadow-xl transition-all duration-300 shadow-lg transform hover:scale-105">
                Refresh Tasks
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <div class="flex flex-wrap justify-center items-center mt-12 mb-16 gap-3 animate-fade-in-up delay-800">
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
            ?>
            
            <div class="glass-intense rounded-full shadow-xl p-1.5 flex items-center">
                <?php
                foreach ($page_links as $type => $page) {
                    $disabled = ($type == 'first' || $type == 'prev') ? $current_page == 1 : $current_page == $total_pages;
                    $href = "?$params" . ($params ? '&' : '') . "page={$page}";
                    
                    $class = "pagination-item flex items-center justify-center w-10 h-10 rounded-full transition-all duration-300 " . 
                             ($disabled ? "opacity-50 cursor-not-allowed text-gray-400" : "text-gray-700 hover:text-indigo-600 hover:bg-indigo-50");
                    
                    $icon = '';
                    if ($type == 'first') $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>';
                    elseif ($type == 'prev') $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>';
                    elseif ($type == 'next') $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>';
                    elseif ($type == 'last') $icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>';
                    
                    echo "<a href='" . ($disabled ? "javascript:void(0);" : $href) . "' class='{$class}' " . ($disabled ? "aria-disabled='true'" : "") . ">" . $icon . "</a>";
                }

                for ($i = max(1, $current_page - $range); $i <= min($total_pages, $current_page + $range); $i++) {
                    if ($i == $current_page) {
                        echo "<span class='pagination-item active flex items-center justify-center w-10 h-10 rounded-full bg-gradient-animated text-white shadow-md'>{$i}</span>";
                    } else {
                        echo "<a href='?{$params}" . ($params ? '&' : '') . "page={$i}' class='pagination-item flex items-center justify-center w-10 h-10 rounded-full text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-300'>{$i}</a>";
                    }
                }
                ?>
            </div>
            
            <form id="jumpForm" action="tasks.php" method="GET" class="flex items-center ml-4 glass-intense rounded-full shadow-xl px-3 py-1">
                <input type="number" name="page" min="1" max="<?php echo htmlspecialchars($total_pages); ?>" placeholder="Page" 
                       class="w-16 px-2 py-1 border-0 focus:ring-0 text-center text-gray-700 text-sm bg-transparent" required>
                <button type="submit" class="ml-1 bg-gradient-animated text-white rounded-full w-8 h-8 flex items-center justify-center transition-colors duration-300 transform hover:scale-105">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </form>
        </div>
        
        <?php require "./global-footer.php";?>
    </div>
    
    <!-- Task Detail Modal -->
    <div id="modal" class="modal fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50">
        <div class="modal-content glass-intense max-w-2xl w-full max-h-[90vh] flex flex-col relative overflow-hidden rounded-3xl shadow-2xl m-4">
            <!-- Modal Header -->
            <div class="p-6 border-b border-gray-200 border-opacity-30">
                <h2 id="modalTitle" class="text-2xl font-bold mb-2 text-gradient-animated pr-8"></h2>
                <div class="flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="modalCreated"></span>
                </div>
            </div>
            
            <!-- Modal Content -->
            <div id="modalContent" class="overflow-y-auto flex-grow p-6 prose prose-sm max-w-none" style="max-height: calc(90vh - 250px);"></div>
            
            <!-- Upload Section -->
            <div id="upload" class="space-y-6 border-t border-gray-200 border-opacity-30 p-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <button id="toggleInput" class="btn btn-magnetic flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all duration-300 transform hover:scale-105 neu-convex">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <span>Input Text</span>
                    </button>
                    
                    <label for="fileInput" class="btn btn-magnetic flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-animated text-white rounded-xl hover:shadow-lg transition-all duration-300 cursor-pointer transform hover:scale-105">
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
                    <textarea id="textarea" placeholder="Type your response here..." class="w-full min-h-[120px] px-4 py-3 text-gray-700 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-y transition-all duration-300 glass"></textarea>
                    <button id="onlysubmittext" class="btn btn-magnetic w-full px-4 py-3 bg-gradient-animated text-white rounded-xl hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-2 transform hover:scale-105">
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
            <button onclick="closeModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600 transition-colors duration-300 transform hover:rotate-90">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Advanced Filter Modal -->
    <div id="filterModal" class="modal fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50">
        <div class="modal-content glass-intense max-w-xl w-full rounded-3xl shadow-2xl m-4">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gradient-animated">Advanced Filters</h2>
                    <button id="closeFilterModal" class="text-gray-400 hover:text-gray-600 transition-colors duration-300 transform hover:rotate-90">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form action="./tasks.php" method="get" class="space-y-6">
                    <div>
                        <label for="keywordFilter" class="block text-sm font-medium mb-2">Keywords</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" id="keywordFilter" name="keyword" placeholder="Search by title" class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 bg-white bg-opacity-70">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="startDate" class="block text-sm font-medium mb-2">Start Date</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <input type="date" id="startDate" name="startDate" class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 bg-white bg-opacity-70">
                            </div>
                        </div>
                        
                        <div>
                            <label for="endDate" class="block text-sm font-medium mb-2">End Date</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <input type="date" id="endDate" name="endDate" class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 bg-white bg-opacity-70">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium mb-3">Status</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach ($statuses as $value => $label): ?>
                                <label class="relative flex items-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-white hover:bg-opacity-30 transition-all duration-300 group">
                                    <input type="radio" class="form-radio h-5 w-5 text-indigo-600 transition duration-150 ease-in-out" name="status" value="<?= $value ?>" <?= $value === '' ? 'checked' : '' ?>>
                                    <span class="ml-3 group-hover:text-indigo-600 transition-colors"><?= $label ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium mb-3">Task Type</h3>
                        <div class="grid grid-cols-2 gap-3 max-h-40 overflow-y-auto pr-2">
                            <?php foreach ($typeNames as $value => $label): ?>
                                <label class="relative flex items-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-white hover:bg-opacity-30 transition-all duration-300 group">
                                    <input type="checkbox" class="form-checkbox h-5 w-5 text-indigo-600 transition duration-150 ease-in-out" name="types[]" value="<?= $value ?>">
                                    <span class="ml-3 group-hover:text-indigo-600 transition-colors"><?= $label ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium mb-3">Sort By</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex items-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-white hover:bg-opacity-30 transition-all duration-300 group">
                                <input type="radio" class="form-radio h-5 w-5 text-indigo-600 transition duration-150 ease-in-out" name="sort" value="due_date" checked>
                                <span class="ml-3 group-hover:text-indigo-600 transition-colors">Due Date</span>
                            </label>
                            <label class="relative flex items-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-white hover:bg-opacity-30 transition-all duration-300 group">
                                <input type="radio" class="form-radio h-5 w-5 text-indigo-600 transition duration-150 ease-in-out" name="sort" value="created_at">
                                <span class="ml-3 group-hover:text-indigo-600 transition-colors">Created Date</span>
                            </label>
                            <label class="relative flex items-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-white hover:bg-opacity-30 transition-all duration-300 group">
                                <input type="radio" class="form-radio h-5 w-5 text-indigo-600 transition duration-150 ease-in-out" name="sort" value="priority">
                                <span class="ml-3 group-hover:text-indigo-600 transition-colors">Priority</span>
                            </label>
                            <label class="relative flex items-center p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-white hover:bg-opacity-30 transition-all duration-300 group">
                                <input type="radio" class="form-radio h-5 w-5 text-indigo-600 transition duration-150 ease-in-out" name="sort" value="title">
                                <span class="ml-3 group-hover:text-indigo-600 transition-colors">Title</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200 border-opacity-30">
                        <button type="button" id="resetFilter" class="btn btn-magnetic px-6 py-3 border border-gray-200 rounded-xl text-gray-700 bg-white bg-opacity-70 hover:bg-opacity-100 transition-all duration-300 transform hover:scale-105 neu-convex">
                            Reset
                        </button>
                        <button type="submit" name="filter" value="yes" class="btn btn-magnetic px-6 py-3 bg-gradient-animated text-white rounded-xl hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Voice Command Modal -->
    <div id="voiceCommandModal" class="modal fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50">
        <div class="modal-content glass-intense max-w-md w-full rounded-3xl shadow-2xl m-4 p-6 text-center">
            <h2 class="text-2xl font-bold mb-4 text-gradient-animated">Voice Commands</h2>
            <p class="mb-6 text-gray-600">Speak a command to navigate or filter tasks</p>
            
            <div class="voice-visualization mb-6 flex justify-center">
                <div class="voice-bars flex items-end space-x-1 h-20">
                    <div class="voice-bar w-1 bg-indigo-500 rounded-t-md" style="height: 20%;"></div>
                    <div class="voice-bar w-1 bg-indigo-500 rounded-t-md" style="height: 40%;"></div>
                    <div class="voice-bar w-1 bg-indigo-500 rounded-t-md" style="height: 60%;"></div>
                    <div class="voice-bar w-1 bg-indigo-500 rounded-t-md" style="height: 80%;"></div>
                    <div class="voice-bar w-1 bg-indigo-500 rounded-t-md" style="height: 100%;"></div>
                    <div class="voice-bar w-1 bg-indigo-500 rounded-t-md" style="height: 80%;"></div>
                    <div class="voice-bar w-1 bg-indigo-500 rounded-t-md" style="height: 60%;"></div>
                    <div class="voice-bar w-1 bg-indigo-500 rounded-t-md" style="height: 40%;"></div>
                    <div class="voice-bar w-1 bg-indigo-500 rounded-t-md" style="height: 20%;"></div>
                </div>
            </div>
            
            <div id="voiceResult" class="mb-6 text-lg font-medium">Listening...</div>
            
            <div class="text-sm text-gray-500 mb-6">
                <p>Try saying:</p>
                <ul class="mt-2 space-y-1">
                    <li>"Show today's tasks"</li>
                    <li>"Filter by homework"</li>
                    <li>"Show completed tasks"</li>
                    <li>"Switch to list view"</li>
                </ul>
            </div>
            
            <button id="closeVoiceModal" class="btn btn-magnetic px-6 py-3 bg-gradient-animated text-white rounded-xl hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                Close
            </button>
        </div>
    </div>
    
    <link rel="stylesheet" href="./djeh.css?version=shs">
    <script src="./confetti-1.js"></script>
    <script src="./up.js.php"></script>
    <script src="https://lf3-cdn-tos.bytecdntp.com/cdn/expire-5-M/sweetalert/2.1.2/sweetalert.min.js" type="application/javascript"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script src="/infobtn.js"></script>
    <script src="/hammer.js?nn=1" type="text/javascript"></script>
    
    <script>
    // Initialize the app
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize WebGL background
        initWebGLBackground();
        
        // Initialize particles
        initParticles();
        
        // Initialize custom cursor
        initCustomCursor();
        
        // Initialize dark mode
        initDarkMode();
        
        // Initialize view toggles
        initViewToggles();
        
        // Initialize task tabs
        initTaskTabs();
        
        // Initialize scroll progress
        initScrollProgress();
        
        // Initialize voice commands
        initVoiceCommands();
        
        // Initialize touch gestures
        initTouchGestures();
        
        // Initialize pagination
        initPagination();
        
        // Initialize filter modal
        initFilterModal();
        
        // Initialize file upload
        initFileUpload();
        
        // Initialize ambient background
        initAmbientBackground();
        
        // Initialize 3D card effects
        init3DCardEffects();
        
        // Animate voice bars
        animateVoiceBars();
        
        // Initialize magnetic buttons
        initMagneticButtons();
        
        // Initialize haptic feedback
        initHapticFeedback();
        
        // Initialize text effects
        initTextEffects();
        
        // Initialize spatial audio
        initSpatialAudio();
    });
    
    // WebGL Background
    function initWebGLBackground() {
        const canvas = document.getElementById('webgl-background');
        if (!canvas) return;
        
        const renderer = new THREE.WebGLRenderer({
            canvas,
            antialias: true,
            alpha: true
        });
        
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        camera.position.z = 30;
        
        // Create particles
        const particlesGeometry = new THREE.BufferGeometry();
        const particlesCount = 1000;
        
        const posArray = new Float32Array(particlesCount * 3);
        const colorsArray = new Float32Array(particlesCount * 3);
        
        for (let i = 0; i < particlesCount * 3; i++) {
            posArray[i] = (Math.random() - 0.5) * 100;
            colorsArray[i] = Math.random();
        }
        
        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
        particlesGeometry.setAttribute('color', new THREE.BufferAttribute(colorsArray, 3));
        
        const particlesMaterial = new THREE.PointsMaterial({
            size: 0.2,
            transparent: true,
            opacity: 0.4,
            vertexColors: true,
            blending: THREE.AdditiveBlending
        });
        
        const particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
        scene.add(particlesMesh);
        
        // Animation
        const clock = new THREE.Clock();
        
        const animate = () => {
            const elapsedTime = clock.getElapsedTime();
            
            particlesMesh.rotation.x = elapsedTime * 0.05;
            particlesMesh.rotation.y = elapsedTime * 0.03;
            
            renderer.render(scene, camera);
            requestAnimationFrame(animate);
        };
        
        animate();
        
        // Handle resize
        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    }
    
    // Particles Background
    function initParticles() {
        if (!window.particlesJS) return;
        
        particlesJS('particles-js', {
            particles: {
                number: {
                    value: 80,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: "#6F72F1"
                },
                shape: {
                    type: "circle",
                    stroke: {
                        width: 0,
                        color: "#000000"
                    },
                    polygon: {
                        nb_sides: 5
                    }
                },
                opacity: {
                    value: 0.3,
                    random: false,
                    anim: {
                        enable: false,
                        speed: 1,
                        opacity_min: 0.1,
                        sync: false
                    }
                },
                size: {
                    value: 3,
                    random: true,
                    anim: {
                        enable: false,
                        speed: 40,
                        size_min: 0.1,
                        sync: false
                    }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: "#6F72F1",
                    opacity: 0.2,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 2,
                    direction: "none",
                    random: false,
                    straight: false,
                    out_mode: "out",
                    bounce: false,
                    attract: {
                        enable: false,
                        rotateX: 600,
                        rotateY: 1200
                    }
                }
            },
            interactivity: {
                detect_on: "canvas",
                events: {
                    onhover: {
                        enable: true,
                        mode: "grab"
                    },
                    onclick: {
                        enable: true,
                        mode: "push"
                    },
                    resize: true
                },
                modes: {
                    grab: {
                        distance: 140,
                        line_linked: {
                            opacity: 0.5
                        }
                    },
                    push: {
                        particles_nb: 4
                    }
                }
            },
            retina_detect: true
        });
    }
    
    // Custom Cursor
    function initCustomCursor() {
        const cursor = document.querySelector('.custom-cursor');
        if (!cursor) return;
        
        // Create cursor trails
        const trailsContainer = document.getElementById('cursor-trails-container');
        const trailCount = 10;
        const trails = [];
        
        for (let i = 0; i < trailCount; i++) {
            const trail = document.createElement('div');
            trail.className = 'custom-cursor-trail';
            trail.style.opacity = 1 - (i / trailCount);
            trail.style.width = `${8 - (i * 0.5)}px`;
            trail.style.height = `${8 - (i * 0.5)}px`;
            trailsContainer.appendChild(trail);
            trails.push({
                element: trail,
                x: 0,
                y: 0
            });
        }
        
        // Mouse movement
        let mouseX = 0;
        let mouseY = 0;
        
        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
            
            cursor.style.left = `${mouseX}px`;
            cursor.style.top = `${mouseY}px`;
        });
        
        // Update trails with delay
        function updateTrails() {
            trails.forEach((trail, index) => {
                setTimeout(() => {
                    trail.x = mouseX;
                    trail.y = mouseY;
                    trail.element.style.left = `${trail.x}px`;
                    trail.element.style.top = `${trail.y}px`;
                }, index * 40);
            });
            
            requestAnimationFrame(updateTrails);
        }
        
        updateTrails();
        
        // Mouse events
        document.addEventListener('mousedown', () => {
            cursor.classList.add('click');
            
            // Trigger haptic feedback if available
            if (navigator.vibrate) {
                navigator.vibrate(20);
            }
        });
        
        document.addEventListener('mouseup', () => {
            cursor.classList.remove('click');
        });
        
        const interactiveElements = document.querySelectorAll('button, a, input, label, .task-card, .btn-magnetic');
        interactiveElements.forEach(element => {
            element.addEventListener('mouseenter', () => {
                cursor.classList.add('hover');
                
                // Play hover sound
                playSound('hover');
            });
            
            element.addEventListener('mouseleave', () => {
                cursor.classList.remove('hover');
            });
        });
    }
    
    // Magnetic Buttons
    function initMagneticButtons() {
        const magneticBtns = document.querySelectorAll('.btn-magnetic');
        
        magneticBtns.forEach(btn => {
            btn.addEventListener('mousemove', (e) => {
                const rect = btn.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                
                const strength = 15; // Adjust the magnetic strength
                
                btn.style.transform = `translate(${x / strength}px, ${y / strength}px)`;
            });
            
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'translate(0, 0)';
            });
        });
    }
    
    // Haptic Feedback
    function initHapticFeedback() {
        if (!navigator.vibrate) return;
        
        const buttons = document.querySelectorAll('button, .btn, .task-card');
        
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                navigator.vibrate(20);
            });
        });
    }
    
    // Text Effects
    function initTextEffects() {
        // Wave effect for task titles
        const taskTitles = document.querySelectorAll('.task-title-text');
        
        taskTitles.forEach(title => {
            const text = title.textContent;
            title.textContent = '';
            
            for (let i = 0; i < text.length; i++) {
                const span = document.createElement('span');
                span.textContent = text[i];
                span.style.display = 'inline-block';
                span.style.animationDelay = `${i * 0.05}s`;
                span.classList.add('animate-text-wave');
                title.appendChild(span);
            }
        });
    }
    
    // Spatial Audio
    function initSpatialAudio() {
        window.sounds = {
            hover: new Howl({
                src: ['https://assets.codepen.io/1468070/mouseclick.wav'],
                volume: 0.1
            }),
            click: new Howl({
                src: ['https://assets.codepen.io/1468070/click.wav'],
                volume: 0.2
            }),
            success: new Howl({
                src: ['https://assets.codepen.io/1468070/success.wav'],
                volume: 0.3
            })
        };
    }
    
    function playSound(name, position = null) {
        if (!window.sounds || !window.sounds[name]) return;
        
        const sound = window.sounds[name];
        
        if (position) {
            // Calculate spatial position based on screen coordinates
            const width = window.innerWidth;
            const height = window.innerHeight;
            
            const x = (position.x / width) * 2 - 1;
            const y = (position.y / height) * 2 - 1;
            
            sound.pos(x, y, 0);
        }
        
        sound.play();
    }
    
    // Scroll Progress
    function initScrollProgress() {
        const scrollProgress = document.getElementById('scrollProgress');
        
        window.addEventListener('scroll', () => {
            const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrollPercentage = (scrollTop / scrollHeight) * 100;
            
            scrollProgress.style.width = `${scrollPercentage}%`;
            
            // Parallax effect on scroll
            const parallaxElements = document.querySelectorAll('.parallax-layer');
            parallaxElements.forEach(element => {
                const speed = element.getAttribute('data-speed') || 0.5;
                element.style.transform = `translateY(${scrollTop * speed}px)`;
            });
        });
    }
    
    // Voice Commands
    function initVoiceCommands() {
        const voiceCommandBtn = document.getElementById('voiceCommandBtn');
        const voiceCommandModal = document.getElementById('voiceCommandModal');
        const closeVoiceModal = document.getElementById('closeVoiceModal');
        const voiceResult = document.getElementById('voiceResult');
        
        if (!voiceCommandBtn || !voiceCommandModal || !closeVoiceModal) return;
        
        voiceCommandBtn.addEventListener('click', () => {
            voiceCommandModal.classList.remove('hidden');
            voiceCommandModal.classList.add('flex');
            voiceCommandModal.classList.add('active');
            
            // Play sound
            playSound('click');
            
            // Check if browser supports speech recognition
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const recognition = new SpeechRecognition();
                
                recognition.continuous = false;
                recognition.interimResults = true;
                recognition.lang = 'en-US';
                
                recognition.onstart = () => {
                    voiceResult.textContent = 'Listening...';
                };
                
                recognition.onresult = (event) => {
                    const transcript = event.results[0][0].transcript.toLowerCase();
                    voiceResult.textContent = `"${transcript}"`;
                    
                    // Process commands
                    if (transcript.includes('today') || transcript.includes("today's tasks")) {
                        document.getElementById('todayTasksTab').click();
                        setTimeout(() => closeVoiceModal.click(), 1500);
                    } else if (transcript.includes('tomorrow') || transcript.includes("tomorrow's tasks")) {
                        document.getElementById('tomorrowTasksTab').click();
                        setTimeout(() => closeVoiceModal.click(), 1500);
                    } else if (transcript.includes('expired')) {
                        document.getElementById('expiredTasksTab').click();
                        setTimeout(() => closeVoiceModal.click(), 1500);
                    } else if (transcript.includes('completed')) {
                        document.getElementById('completedTasksTab').click();
                        setTimeout(() => closeVoiceModal.click(), 1500);
                    } else if (transcript.includes('all tasks')) {
                        document.getElementById('allTasksTab').click();
                        setTimeout(() => closeVoiceModal.click(), 1500);
                    } else if (transcript.includes('grid view')) {
                        document.getElementById('gridViewBtn').click();
                        setTimeout(() => closeVoiceModal.click(), 1500);
                    } else if (transcript.includes('list view')) {
                        document.getElementById('listViewBtn').click();
                        setTimeout(() => closeVoiceModal.click(), 1500);
                    } else if (transcript.includes('filter')) {
                        document.getElementById('filterBtn').click();
                        setTimeout(() => closeVoiceModal.click(), 1500);
                    } else if (transcript.includes('dark mode') || transcript.includes('light mode')) {
                        document.getElementById('darkModeToggle').click();
                        setTimeout(() => closeVoiceModal.click(), 1500);
                    } else {
                        voiceResult.textContent = 'Command not recognized. Try again.';
                    }
                };
                
                recognition.onerror = (event) => {
                    voiceResult.textContent = 'Error occurred in recognition: ' + event.error;
                };
                
                recognition.onend = () => {
                    // Auto restart recognition
                    if (voiceCommandModal.classList.contains('active')) {
                        recognition.start();
                    }
                };
                
                recognition.start();
                
                closeVoiceModal.addEventListener('click', () => {
                    recognition.stop();
                    voiceCommandModal.classList.remove('active');
                    setTimeout(() => {
                        voiceCommandModal.classList.add('hidden');
                        voiceCommandModal.classList.remove('flex');
                    }, 300);
                });
            } else {
                voiceResult.textContent = 'Speech recognition not supported in this browser.';
            }
        });
    }
    
    // Animate Voice Bars
    function animateVoiceBars() {
        const voiceBars = document.querySelectorAll('.voice-bar');
        if (!voiceBars.length) return;
        
        function animateBars() {
            voiceBars.forEach(bar => {
                const height = Math.floor(Math.random() * 100) + '%';
                bar.style.height = height;
                bar.style.transition = 'height 0.2s ease';
            });
        }
        
        // Initial animation
        animateBars();
        
        // Continue animation
        setInterval(animateBars, 200);
    }
    
    // Ambient Background
    function initAmbientBackground() {
        const blobs = document.querySelectorAll('.ambient-blob');
        if (!blobs.length) return;
        
        // Random movement for blobs
        blobs.forEach(blob => {
            setInterval(() => {
                const xPos = Math.random() * 10 - 5; // -5 to 5
                const yPos = Math.random() * 10 - 5; // -5 to 5
                const scale = 0.95 + Math.random() * 0.1; // 0.95 to 1.05
                
                blob.style.transform = `translate(${xPos}px, ${yPos}px) scale(${scale})`;
                blob.style.transition = 'transform 3s ease-in-out';
            }, 3000);
        });
    }
    
    // 3D Card Effects
    function init3DCardEffects() {
        const cards = document.querySelectorAll('.card-3d');
        if (!cards.length) return;
        
        cards.forEach(card => {
            card.addEventListener('mousemove', e => {
                const cardRect = card.getBoundingClientRect();
                const cardCenterX = cardRect.left + cardRect.width / 2;
                const cardCenterY = cardRect.top + cardRect.height / 2;
                
                const mouseX = e.clientX - cardCenterX;
                const mouseY = e.clientY - cardCenterY;
                
                // Calculate rotation based on mouse position
                const rotateY = mouseX / 20;
                const rotateX = -mouseY / 20;
                
                // Apply transform to card content
                const cardContent = card.querySelector('.card-3d-content');
                if (cardContent) {
                    cardContent.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(var(--depth-factor))`;
                }
                
                // Apply parallax effect to layers
                const layers = card.querySelectorAll('.card-3d-layer');
                layers.forEach(layer => {
                    const layerIndex = parseFloat(getComputedStyle(layer).getPropertyValue('--layer-index'));
                    const translateZ = layerIndex * -5;
                    const translateX = mouseX * 0.01 * layerIndex;
                    const translateY = mouseY * 0.01 * layerIndex;
                    
                    layer.style.transform = `translateX(${translateX}px) translateY(${translateY}px) translateZ(${translateZ}px)`;
                });
            });
            
            card.addEventListener('mouseleave', () => {
                const cardContent = card.querySelector('.card-3d-content');
                if (cardContent) {
                    cardContent.style.transform = 'rotateX(0) rotateY(0) translateZ(0)';
                }
                
                // Reset layers
                const layers = card.querySelectorAll('.card-3d-layer');
                layers.forEach(layer => {
                    const layerIndex = parseFloat(getComputedStyle(layer).getPropertyValue('--layer-index'));
                    const translateZ = layerIndex * -5;
                    
                    layer.style.transform = `translateX(0) translateY(0) translateZ(${translateZ}px)`;
                });
            });
        });
    }
    
    // Dark Mode Toggle
    function initDarkMode() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        const app = document.getElementById('app');
        
        // Check for saved theme preference or use preferred color scheme
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            app.classList.add('dark-mode');
            document.documentElement.classList.add('dark');
        }
        
        darkModeToggle.addEventListener('click', function() {
            app.classList.toggle('dark-mode');
            document.documentElement.classList.toggle('dark');
            
            // Play sound
            playSound('click');
            
            // Save preference
            if (app.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    }
    
    // View Toggles (Grid/List)
    function initViewToggles() {
        const gridViewBtn = document.getElementById('gridViewBtn');
        const listViewBtn = document.getElementById('listViewBtn');
        const gridView = document.getElementById('gridView');
        const listView = document.getElementById('listView');
        
        // Check for saved view preference
        const savedView = localStorage.getItem('taskView') || 'grid';
        
        if (savedView === 'list') {
            gridView.classList.add('hidden');
            listView.classList.remove('hidden');
            gridViewBtn.classList.remove('bg-white', 'shadow-sm');
            listViewBtn.classList.add('bg-white', 'shadow-sm');
            gridViewBtn.querySelector('svg').classList.remove('text-indigo-600');
            gridViewBtn.querySelector('svg').classList.add('text-gray-500');
            listViewBtn.querySelector('svg').classList.remove('text-gray-500');
            listViewBtn.querySelector('svg').classList.add('text-indigo-600');
        }
        
        gridViewBtn.addEventListener('click', function() {
            gridView.classList.remove('hidden');
            listView.classList.add('hidden');
            gridViewBtn.classList.add('bg-white', 'shadow-sm');
            listViewBtn.classList.remove('bg-white', 'shadow-sm');
            gridViewBtn.querySelector('svg').classList.add('text-indigo-600');
            gridViewBtn.querySelector('svg').classList.remove('text-gray-500');
            listViewBtn.querySelector('svg').classList.add('text-gray-500');
            listViewBtn.querySelector('svg').classList.remove('text-indigo-600');
            localStorage.setItem('taskView', 'grid');
            
            // Play sound
            playSound('click');
        });
        
        listViewBtn.addEventListener('click', function() {
            gridView.classList.add('hidden');
            listView.classList.remove('hidden');
            gridViewBtn.classList.remove('bg-white', 'shadow-sm');
            listViewBtn.classList.add('bg-white', 'shadow-sm');
            gridViewBtn.querySelector('svg').classList.remove('text-indigo-600');
            gridViewBtn.querySelector('svg').classList.add('text-gray-500');
            listViewBtn.querySelector('svg').classList.remove('text-gray-500');
            listViewBtn.querySelector('svg').classList.add('text-indigo-600');
            localStorage.setItem('taskView', 'list');
            
            // Play sound
            playSound('click');
        });
    }
    
    // Task Tabs
    function initTaskTabs() {
        const allTasksTab = document.getElementById('allTasksTab');
        const todayTasksTab = document.getElementById('todayTasksTab');
        const tomorrowTasksTab = document.getElementById('tomorrowTasksTab');
        const expiredTasksTab = document.getElementById('expiredTasksTab');
        const completedTasksTab = document.getElementById('completedTasksTab');
        
        const taskItems = document.querySelectorAll('.task-item');
        
        function setActiveTab(tab) {
            // Remove active class from all tabs
            [allTasksTab, todayTasksTab, tomorrowTasksTab, expiredTasksTab, completedTasksTab].forEach(t => {
                t.classList.remove('text-indigo-600', 'border-indigo-600');
                t.classList.add('text-gray-500', 'border-transparent');
            });
            
            // Add active class to selected tab
            tab.classList.remove('text-gray-500', 'border-transparent');
            tab.classList.add('text-indigo-600', 'border-indigo-600');
            
            // Play sound
            playSound('click');
        }
        
        allTasksTab.addEventListener('click', function() {
            setActiveTab(this);
            taskItems.forEach(item => {
                item.style.display = '';
                // Add fade-in animation
                item.classList.add('animate-fade-in-up');
                setTimeout(() => {
                    item.classList.remove('animate-fade-in-up');
                }, 500);
            });
        });
        
        todayTasksTab.addEventListener('click', function() {
            setActiveTab(this);
            taskItems.forEach(item => {
                if (item.getAttribute('data-due') === 'today') {
                    item.style.display = '';
                    // Add fade-in animation
                    item.classList.add('animate-fade-in-up');
                    setTimeout(() => {
                        item.classList.remove('animate-fade-in-up');
                    }, 500);
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        tomorrowTasksTab.addEventListener('click', function() {
            setActiveTab(this);
            taskItems.forEach(item => {
                if (item.getAttribute('data-due') === 'tomorrow') {
                    item.style.display = '';
                    // Add fade-in animation
                    item.classList.add('animate-fade-in-up');
                    setTimeout(() => {
                        item.classList.remove('animate-fade-in-up');
                    }, 500);
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        expiredTasksTab.addEventListener('click', function() {
            setActiveTab(this);
            taskItems.forEach(item => {
                if (item.getAttribute('data-due') === 'expired') {
                    item.style.display = '';
                    // Add fade-in animation
                    item.classList.add('animate-fade-in-up');
                    setTimeout(() => {
                        item.classList.remove('animate-fade-in-up');
                    }, 500);
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        completedTasksTab.addEventListener('click', function() {
            setActiveTab(this);
            taskItems.forEach(item => {
                if (item.getAttribute('data-status') === 'completed') {
                    item.style.display = '';
                    // Add fade-in animation
                    item.classList.add('animate-fade-in-up');
                    setTimeout(() => {
                        item.classList.remove('animate-fade-in-up');
                    }, 500);
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Touch Gestures
    function initTouchGestures() {
        const bodyElement = document.querySelector(".sue-navbar");
        if (!bodyElement) return;
        
        const hammer = new Hammer(bodyElement);
        
        // Listen for swipe events
        hammer.on('swipeleft', () => {
            window.location.href = '/notices.php';
        });
        
        hammer.on('swiperight', () => {
            window.location.href = '/aurora.php';
        });
        
        // Add touch gestures to task cards
        const taskCards = document.querySelectorAll('.task-card');
        taskCards.forEach(card => {
            const cardHammer = new Hammer(card);
            
            cardHammer.on('swipeleft', (e) => {
                // Prevent default behavior
                e.preventDefault();
                
                // Get task ID from parent element
                const taskItem = card.closest('.task-item');
                const taskIndex = Array.from(document.querySelectorAll('.task-item')).indexOf(taskItem);
                
                // Open modal for this task
                openModal(taskIndex);
            });
        });
    }
    
    // Pagination
    function initPagination() {
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
            
            // Add page transition animation
            document.body.classList.add('animate-fade-out');
            setTimeout(() => {
                window.location.href = newUrl;
            }, 300);
            
            // Play sound
            playSound('click');
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
            } else {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    if (href && href !== 'javascript:void(0);') {
                        // Add page transition animation
                        document.body.classList.add('animate-fade-out');
                        setTimeout(() => {
                            window.location.href = href;
                        }, 300);
                        
                        // Play sound
                        playSound('click');
                    }
                });
            }
        });
    }
    
    // Filter Modal
    function initFilterModal() {
        const filterBtn = document.getElementById('filterBtn');
        const filterModal = document.getElementById('filterModal');
        const closeFilterModal = document.getElementById('closeFilterModal');
        const resetFilterBtn = document.getElementById('resetFilter');

        filterBtn.addEventListener('click', () => {
            filterModal.classList.remove('hidden');
            filterModal.classList.add('flex');
            filterModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Play sound
            playSound('click');
        });

        closeFilterModal.addEventListener('click', () => {
            filterModal.classList.remove('active');
            setTimeout(() => {
                filterModal.classList.add('hidden');
                filterModal.classList.remove('flex');
                document.body.style.overflow = '';
            }, 300);
            
            // Play sound
            playSound('click');
        });

        filterModal.addEventListener('click', (e) => {
            if (e.target === filterModal) {
                closeFilterModal.click();
            }
        });
        
        if (resetFilterBtn) {
            resetFilterBtn.addEventListener('click', function() {
                const filterForm = this.closest('form');
                const inputs = filterForm.querySelectorAll('input:not([type="submit"])');
                inputs.forEach(input => {
                    if (input.type === 'radio') {
                        input.checked = input.value === '' || input.value === 'due_date';
                    } else if (input.type === 'checkbox') {
                        input.checked = false;
                    } else {
                        input.value = '';
                    }
                });
                
                // Add animation to reset button
                this.classList.add('animate-pulse');
                setTimeout(() => {
                    this.classList.remove('animate-pulse');
                }, 500);
                
                // Play sound
                playSound('click');
            });
        }
    }
    
    // File Upload
    function initFileUpload() {
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
                    <div class="flex justify-between items-center bg-white bg-opacity-70 p-3 rounded-lg border border-gray-200 animate-fade-in-up">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-indigo-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
                
                // Play success sound
                playSound('success');
                
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
                
                // Play success sound
                playSound('success');
                
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
    }
    
    // Global variables
    let this_biz_id = "";
    let clearTimer;
    let items = <?= json_encode($items) ?>;
    
    // Modal functions
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
        
        // Play sound
        playSound('click');
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
        
        // Play sound
        playSound('click');
    }
    
    // Toggle text input area
    document.getElementById('toggleInput').addEventListener('click', function() {
        var inputArea = document.getElementById('inputArea');
        var toggleButton = document.getElementById('toggleInput');
        if (inputArea.classList.contains('hidden')) {
            inputArea.classList.remove('hidden');
            inputArea.classList.add('animate-fade-in-up');
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
        
        // Play sound
        playSound('click');
    });
    
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
                
                // Play success sound
                playSound('success');
                
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
    
    // Function to submit all correct
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
                
                // Play success sound
                playSound('success');
                
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