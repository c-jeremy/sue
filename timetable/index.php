<?php

//终于写完了，累死了！！！！！！！！！
//@CZW快点把鲁棒性加上

$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();

// 检查会话变量
 if (!isset($_SESSION['user_id'])) {
     header('HTTP/1.1 302 Found');
     header('Location: login.php');
     exit;
 }


 require_once "../logger.php";
  __($_SESSION["user_id"], "View Timetable", $_ENV["ENV"], 1);



$ares = file_get_contents("../credentials/activeref-".$_SESSION['user_id'].".auth");
if ($ares === false) {
    die("Unexpected error: could not get the latest keys.");
}

$res = file_get_contents("../credentials/keys-".$_SESSION['user_id'].".auth");
if ($res === false) {
    die("Unexpected error: could not get the latest keys.");
}


// 获取当前日期
$now = new DateTime();

if (is_numeric($_GET['week'])){
    $week = ceil($_GET['week']) * 7;
    if ($week > 10000){
             die("I promise, you won't live long enough to get there.");
        } elseif ($week < -10000){

            die("Nah, dinosaurs don't learn math.");
        }
} else {
    $week = 0;
}

// 获取本周的开始日期（周一）
$startOfWeek = clone $now;
$startOfWeek->modify('this week')->format('Y-m-d');
$startOfWeek->modify($week. ' day')->format('Y-m-d');

// 获取本周的结束日期（周日）
$endOfWeek = clone $now;
$endOfWeek->modify('next week -1 day')->format('Y-m-d');
$endOfWeek->modify($week. ' day')->format('Y-m-d');
// 将日期格式化为字符串
$startOfWeekStr = $startOfWeek->format('Y-m-d');
$endOfWeekStr = $endOfWeek->format('Y-m-d');

function get_events(){
    //Initializing
    global $res;
    global $ares;
    global $startOfWeekStr;
    global $endOfWeekStr;
    
    // 初始化 cURL 会话
    $ch = curl_init();
    
    // 设置请求的 URL
    $url = "https://api.seiue.com/chalk/calendar/personals/$ares/events?end_time=$endOfWeekStr%2023%3A59%3A59&expand=address%2Cinitiators&start_time=$startOfWeekStr%2000%3A00%3A00";
    
    // 设置 cURL 选项
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 将响应数据作为字符串返回，而不是直接输出
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Pragma: no-cache',
        'Accept: application/json, text/plain, */*',
        'Authorization: Bearer '. $res ,
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
    ));
    curl_setopt($ch, CURLOPT_HEADER, false); // 不返回 HTTP 头部信息
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 如果需要忽略 SSL 证书验证
    
    // 执行 cURL 会话
    $response = curl_exec($ch);
    
    // 检查是否有错误发生
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        // 输出响应数据
        //echo $response;
    }
    // 关闭 cURL 会话
    curl_close($ch);
    // 解析 JSON 响应
    $data = json_decode($response, true);
    if ($data['status'] == 400){
        die ("Unexpected error.");

        
    }
    // 提取有用的数据
    $events = [];
    foreach ($data as $event) {
        
        $events[] = [
            'start_time' => $event['start_time'],
            'end_time' => $event['end_time'],
            'remark' => $event['custom']['week'],
            'title' => $event['title'],
            'class_name' => empty($event['subject']['class_name']) ? "" : $event['subject']['class_name'],
            'address' => $event['address'],
            'initiators' => array_column($event['initiators'], 'name')
        ];
    }

    return $events;
    
}

$data = get_events();
$data2 = json_encode($data);
//print_r(json_encode($data[0]['remark']));

//print_r($data)





?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2024-2025学年第一学期 学生课表</title>
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
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
        }
        .schedule-header {
            display: grid;
            grid-template-columns: 100px repeat(7, 1fr) 50px;
            
        }
        .schedule-header > div {
            padding: 10px;
            text-align: center;
            border-right: 1px solid #ccc;
            font-weight: bold;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-bottom: 1px solid #ccc;
        }
        .schedule-header > div:last-child {
            border-right: none;
            border-bottom: none;
            
        }
        .schedule-body {
            position: relative;
            height: 1200px;
        }
        .time-slots {
            position: absolute;
            left: 0;
            top: 0;
            width: 100px;
            height: 100%;
            border-right: 1px solid #ccc;
        }
        .time-slot {
            height: 56.6px;
            
            padding: 5px;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .time-slot-morning {
            height: 90px;
            
            padding: 5px;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .time-slot-break-big {
            height: 15px;
            
            padding: 5px;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .time-slot-break-large {
            height: 23.3px;
            
            padding: 5px;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .time-slot-break-small {
            height: 6px;
            
            padding: 5px;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }.time-slot-break-noon {
            height: 98.3px;
            
            padding: 5px;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .time-labels {
            position: absolute;
            right: 0;
            top: 0;
            width: 50px;
            height: 100%;
            border-left: 1px solid #ccc;
        }
        .time-label {
            position: absolute;
            left: 0;
            right: 0;
            text-align: center;
            transform: translateY(-50%);
            font-size: 12px;
            color: #666;
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
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 4px;
            transition: box-shadow 0.3s;
            z-index: 2;
        }
        .course:hover {
            z-index: 3;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .course-title {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .course-info {
            color: #666;
        }
        /* Grid lines */
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
            width: 100%; /* 宽度占满整个容器 */
            height: 100%; /* 高度占满整个容器 */
            background-image: repeating-linear-gradient(
                to right,
                transparent 0,
                transparent calc(100% / 7 - 1px),
                #e5e5e5 calc(100% / 7 - 1px),
                #e5e5e5 calc(100% / 7)
            );
            background-position: 1px 0; /* 将背景图像向右移动 1 像素 */
            z-index: -1; /* 确保背景在内容之下 */
        }
        .course-container::after {
            background-image: linear-gradient(to bottom, #e5e5e5 1px, transparent 1px);
            background-size: 100% calc(100% / 12);
            background-position: 0 -1px;
        }
        .navigation {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .nav-button {
            padding: 10px 20px;
            margin: 0 10px;
            font-size: 16px;
            cursor: pointer;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>2024-2025学年第一学期 学生课表</h1>
    <!-- Add navigation buttons -->
    <div class="navigation">
        <button id="prevWeek" class="nav-button" onclick="window.location.href='?week=<?=$week / 7 - 1?>'">上一周</button>
        <p> 第<?= !empty($data[0]['remark']) ? $data[0]['remark'] : end($data)['remark']?>周 </p>
        <button id="nextWeek" class="nav-button" onclick="window.location.href='?week=<?=$week / 7 + 1?>'">下一周</button>
    </div>
    <div class="schedule-container">
        <div class="schedule-header">
            <div>时间</div>
            <div>周一<br><span style="font-size: 12px; color: #666;"><?php echo $startOfWeek->format('m-d'); ?></span></div>
            <div>周二<br><span style="font-size: 12px; color: #666;"><?php echo $startOfWeek->modify('1 day')->format('m-d'); ?></span></div>
            <div>周三<br><span style="font-size: 12px; color: #666;"><?php echo $startOfWeek->modify('1 day')->format('m-d'); ?></span></div>
            <div>周四<br><span style="font-size: 12px; color: #666;"><?php echo $startOfWeek->modify('1 day')->format('m-d'); ?></span></div>
            <div>周五<br><span style="font-size: 12px; color: #666;"><?php echo $startOfWeek->modify('1 day')->format('m-d'); ?></span></div>
            <div>周六<br><span style="font-size: 12px; color: #666;"><?php echo $startOfWeek->modify('1 day')->format('m-d'); ?></span></div>
            <div>周日<br><span style="font-size: 12px; color: #666;"><?php echo $startOfWeek->modify('1 day')->format('m-d'); ?></span></div>
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

    <script>
        
        const courses = <?= $data2?>;
        

        function getTimePosition(time) {
            const [hours, minutes] = time.split(':').map(Number);
            return ((hours - 7) * 60 + minutes) / (12 * 60) * 100;
        }

        function getDayIndex(date) {
            return new Date(date).getDay() - 1;
        }

        function formatTime(timeString) {
            return timeString.split(' ')[1].substring(0, 5);
        }

        function createCourseElement(course, index) {
            const startPos = getTimePosition(formatTime(course.start_time));
            const endPos = getTimePosition(formatTime(course.end_time));
            let dayIndex = getDayIndex(course.start_time.split(' ')[0]);
            if (dayIndex == -1){dayIndex=6}
            const height = `${(endPos - startPos)*0.95}%`;
            const top = `${startPos}%`;
            const left = `${(dayIndex / 7) * 100 + 0.25}%`;
            //console.log("left:",dayIndex);
            const width = "12.28%";

            const courseElement = document.createElement('div');
            courseElement.className = 'course';
            courseElement.style.top = top;
            courseElement.style.left = left;
            courseElement.style.height = height;
            courseElement.style.width = width;
            courseElement.style.backgroundColor = `hsl(${(index * 60) % 360}, 70%, 95%)`;

            courseElement.innerHTML = `
                <div class="course-title">${course.title}</div>
                <div class="course-info"> ${course.class_name} ${course.address}</div>
                <div class="course-info">${formatTime(course.start_time)}-${formatTime(course.end_time)}</div>
                <!-- <div class="course-info">${course.initiators.join(', ')}</div> -->
            `;

            return courseElement;
        }

        function initializeSchedule() {
            const courseContainer = document.querySelector('.course-container');
            const timeLabels = document.querySelector('.time-labels');

            // Add time labels
            for (let i = 7; i <= 19; i++) {
                const timeLabel = document.createElement('div');
                timeLabel.className = 'time-label';
                timeLabel.textContent = `${i}:00`;
                timeLabel.style.top = `${((i - 7) / 12) * 100}%`;
                if (i == 19){
                    timeLabel.style.visibility = 'hidden';
                }
                timeLabels.appendChild(timeLabel);
            }

            // Add courses
            courses.forEach((course, index) => {
                const courseElement = createCourseElement(course, index);
                courseContainer.appendChild(courseElement);
            });
        }

        initializeSchedule();
        
        
    </script>
</body>
</html>