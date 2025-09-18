<?php
$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header('Location: ../login.php');
    exit;
}

date_default_timezone_set('Asia/Shanghai');

// Logging
$time = date('Y-m-d H:i:s');
require_once "./logger.php";
__($_SESSION["user_id"], $time, "View timetable", $_ENV["ENV"], 1);

// Fetch authentication keys
$ares = file_get_contents("../credentials/activeref-" . $_SESSION['user_id'] . ".auth");
$res = file_get_contents("../credentials/keys-" . $_SESSION['user_id'] . ".auth");

if ($ares === false || $res === false) {
    die("Unexpected error: could not get the latest keys.");
}

// Date calculations
$now = new DateTime();
$week = isset($_GET['week']) && is_numeric($_GET['week']) ? intval($_GET['week']) * 7 : 0;

if ($week > 10000) {
    die("I promise, you won't live long enough to get there.");
} elseif ($week < -10000) {
    die("Nah, dinosaurs don't learn math.");
}

$startOfWeek = clone $now;
$startOfWeek->modify('this week')->modify($week . ' day');
$endOfWeek = clone $startOfWeek;
$endOfWeek->modify('+6 days');

$startOfWeekStr = $startOfWeek->format('Y-m-d');
$endOfWeekStr = $endOfWeek->format('Y-m-d');

function get_events() {
    global $res, $ares, $startOfWeekStr, $endOfWeekStr;
    
    $ch = curl_init();
    
    $url = "https://api.seiue.com/chalk/calendar/personals/$ares/events?end_time=$endOfWeekStr%2023%3A59%3A59&expand=address%2Cinitiators&start_time=$startOfWeekStr%2000%3A00%3A00";
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Pragma: no-cache',
            'Accept: application/json, text/plain, */*',
            'Authorization: Bearer '. $res,
            'Sec-Fetch-Site: same-site',
            'Accept-Language: zh-CN,zh-Hans;q=0.9',
            'Cache-Control: no-cache',
            'Sec-Fetch-Mode: cors',
            'Origin: https://chalk-c3.seiue.com',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
            'Referer: https://chalk-c3.seiue.com/',
            'Connection: keep-alive',
            'Host: api.seiue.com',
            'Sec-Fetch-Dest: empty',
            'X-Reflection-Id: '.$ares,
            'X-School-Id: 3',
            'X-Role: student'
        ],
        CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        die('Error:' . curl_error($ch));
    }
    
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (isset($data['status']) && $data['status'] == 400) {
        die("Unexpected error.");
    }
    
    return array_map(function($event) {
        return [
            'start_time' => $event['start_time'],
            'end_time' => $event['end_time'],
            'remark' => $event['custom']['week'],
            'title' => $event['title'],
            'class_name' => $event['subject']['class_name'] ?? "",
            'address' => $event['address'],
            'initiators' => array_column($event['initiators'], 'name')
        ];
    }, $data);
}

$data = get_events();
$data2 = json_encode($data);
$currentWeek = $week / 7;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2024-2025学年第一学期 学生课表</title>
    <script src="../twind.js"></script>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px;
        box-sizing: border-box;
    }
    .schedule-container {
        max-width: 1200px;
        margin: 0 auto;
        background-color: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .schedule-header {
        display: grid;
        grid-template-columns: 100px repeat(7, 1fr) 50px;
    }
    .schedule-header > div {
        padding: 10px;
        text-align: center;
        font-weight: bold;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border-bottom: 1px solid #e5e5e5;
    }
    .schedule-body {
        position: relative;
        height: 1200px;
    }
    .time-slots, .time-labels {
        position: absolute;
        top: 0;
        height: 100%;
    }
    .time-slots {
        left: 0;
        width: 100px;
        border-right: 1px solid #e5e5e5;
    }
    .time-labels {
        right: 0;
        width: 50px;
        border-left: 1px solid #e5e5e5;
    }
    .time-slot, .time-slot-morning, .time-slot-break-big, .time-slot-break-large, .time-slot-break-small, .time-slot-break-noon {
        padding: 5px;
        font-size: 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    .time-slot { height: 5.56%; }
    .time-slot-morning { height: 8.33%}
    .time-slot-break-big { height: 2.08%; }
    .time-slot-break-large { height: 2.78%; }
    .time-slot-break-small { height: 1.39%; }
    .time-slot-break-noon { height: 9.03%; }
    .time-label {
        position: absolute;
        left: 0;
        right: 0;
        text-align: center;
        transform: translateY(-50%);
        font-size: 12px;
        color: #666;
        background-color: white;
        padding: 2px 0;
        margin-top: -1px;
    }
    .course-container {
        position: absolute;
        left: 100px;
        right: 50px;
        top: 0;
        height: 100%;
    }
    .course {
        position: absolute;
        padding: 5px;
        font-size: 12px;
        overflow: hidden;
        background-color: white;
        border-radius: 4px;
        transition: all 0.3s ease;
        z-index: 2;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        cursor: pointer;
    }
    .course:hover {
        z-index: 3;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .course.expanded {
        height: auto !important;
        min-height: 150px;
        z-index: 1000;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    .course-title {
        font-weight: bold;
        margin-bottom: 2px;
    }
    .course-info {
        color: #666;
    }
    .course-container::before,
    .course-container::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        pointer-events: none;
    }
    .course-container::before {
        width: 100%;
        height: 100%;
        background-image: repeating-linear-gradient(
            to right,
            transparent 0,
            transparent calc(100% / 7 - 1px),
            #e5e5e5 calc(100% / 7 - 1px),
            #e5e5e5 calc(100% / 7)
        );
        background-position: 1px 0;
        z-index: -1;
    }
    .course-container::after {
        background-image: linear-gradient(to bottom, #e5e5e5 1px, transparent 1px);
        background-size: 100% calc(100% / 12);
        background-position: 0 -1px;
    }
    .navigation {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 20px;
    }
    .nav-button {
        padding: 10px 20px;
        margin: 0 10px;
        font-size: 16px;
        cursor: pointer;
        background-color: #f0f0f0;
        border: none;
        border-radius: 4px;
        transition: background-color 0.3s;
    }
    .nav-button:hover {
        background-color: #e0e0e0;
    }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <?php 
        $include_src = "timetable";
        require "../global-header.php";
        ?>
        <div class="navigation">
            <button id="prevWeek" class="nav-button shadow-lg">上一周</button>
            <p class="text-xl font-semibold mx-4 text-gray-600">第<span id="weekNumber"></span>周</p>
            <button id="nextWeek" class="shadow-lg nav-button">下一周</button>
        </div>
        <div class="schedule-container">
            <div class="schedule-header">
                <div>时间</div>
                <div id="monday">周一</div>
                <div id="tuesday">周二</div>
                <div id="wednesday">周三</div>
                <div id="thursday">周四</div>
                <div id="friday">周五</div>
                <div id="saturday">周六</div>
                <div id="sunday">周日</div>
                <div></div>
            </div>
            <div class="schedule-body">
                <div class="time-slots">
                    <div class="time-slot-morning"><br></div>
                    <div class="time-slot"><strong>第1节</strong><br>08:00-08:40</div>
                    <div class="time-slot-break-big"><br></div>
                    <div class="time-slot"><strong>第2节</strong><br>08:55-09:35</div>
                    <div class="time-slot-break-large"><br></div>
                    <div class="time-slot"><strong>第3节</strong><br>09:55-10:35</div>
                    <div class="time-slot-break-small"><br></div>
                    <div class="time-slot"><strong>第4节</strong><br>10:45-11:25</div>
                    <div class="time-slot-break-noon"><br></div>
                    <div class="time-slot"><strong>中午</strong><br>12:30-13:10</div>
                    <div class="time-slot-break-small"><br></div>
                    <div class="time-slot"><strong>第5节</strong><br>13:20-14:00</div>
                    <div class="time-slot-break-small"><br></div>
                    <div class="time-slot"><strong>第6节</strong><br>14:10-14:50</div>
                    <div class="time-slot-break-small"><br></div>
                    <div class="time-slot"><strong>第7节</strong><br>15:00-15:40</div>
                    <div class="time-slot-break-small"><br></div>
                    <div class="time-slot"><strong>第8节</strong><br>15:50-16:30</div>
                    <div class="time-slot-break-small"><br></div>
                    <div class="time-slot"><strong>第9节</strong><br>16:40-17:20</div>
                    <div class="time-slot-break-small"><br></div>
                    <div class="time-slot"><strong>第10节</strong><br>17:30-18:10</div>
                </div>
                <div class="time-labels"></div>
                <div class="course-container"></div>
            </div>
        </div>
    </div>

    <script id="coursesData" type="application/json"><?= $data2 ?></script>
    <script id="currentWeek" type="application/json"><?= $currentWeek ?></script>
    <script src="schedule.js"></script>
</body>
</html>

