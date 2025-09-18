<?php

//终于写完了，累死了！！！！！！！！！
//@CZW快点把鲁棒性加上

$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();

// 检查会话变量
 if (!isset($_SESSION['user_id'])) {
     header('HTTP/1.1 302 Found');
     header('Location: ../login.php');
     exit;
 }

date_default_timezone_set('Asia/Shanghai');

// 获取当前时间并格式化
$time = date('Y-m-d H:i:s');
require_once "./logger.php";
__($_SESSION["user_id"], $time, "View timetable",$_ENV["ENV"],1);

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
            <button id="prevWeek" class="nav-button shadow-lg" onclick="window.location.href='?week=<?=$week / 7 - 1?>'">上一周</button>
            <p class="text-xl font-semibold mx-4 text-gray-600"> 第<?= !empty($data[0]['remark']) ? $data[0]['remark'] : end($data)['remark']?>周 </p>
            <button id="nextWeek" class="shadow-lg nav-button" onclick="window.location.href='?week=<?=$week / 7 + 1?>'">下一周</button>
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
            const width = "13.28%";

            const courseElement = document.createElement('div');
            courseElement.className = 'course';
            courseElement.style.top = top;
            courseElement.style.left = left;
            courseElement.style.height = height;
            courseElement.style.width = width;
            courseElement.style.backgroundColor = `hsl(${(index * 60) % 360}, 70%, 95%)`;

            // 计算可用高度（像素）
            const availableHeight = parseFloat(height) * 12; // 假设1%高度等于12像素
            
            // 创建并添加课程标题
            const titleElement = document.createElement('div');
            titleElement.className = 'course-title';
            titleElement.textContent = course.title;
            courseElement.appendChild(titleElement);

            // 创建并添加其他信息元素
            const infoElements = [
                { text: course.address, priority: 1 },
                { text: `${formatTime(course.start_time)}-${formatTime(course.end_time)}`, priority: 2 },
                { text: course.class_name, priority: 3 },
                { text: `教师: ${course.initiators.join(', ')}`, priority: 4 }
            ];

            let remainingHeight = availableHeight - titleElement.offsetHeight;

            infoElements.forEach(info => {
                if (remainingHeight > 0) {
                    const infoElement = document.createElement('div');
                    infoElement.className = 'course-info';
                    infoElement.textContent = info.text;
                    courseElement.appendChild(infoElement);
                    
                    if (infoElement.offsetHeight > remainingHeight) {
                        infoElement.style.overflow = 'hidden';
                        infoElement.style.textOverflow = 'ellipsis';
                        infoElement.style.whiteSpace = 'nowrap';
                    }
                    
                    remainingHeight -= infoElement.offsetHeight;
                }
            });

            // Store the original content
            courseElement.originalContent = courseElement.innerHTML;

            courseElement.addEventListener('click', function() {
                this.classList.toggle('expanded');
                if (this.classList.contains('expanded')) {
                    this.innerHTML = `
                        <div class="course-title">${course.title}</div>
                        <div class="course-info">${course.address}</div>
                        <div class="course-info">${formatTime(course.start_time)}-${formatTime(course.end_time)}</div>
                        <div class="course-info">${course.class_name}</div>
                        <div class="course-info">教师: ${course.initiators.join(', ')}</div>
                    `;
                } else {
                    // Restore the original content
                    this.innerHTML = this.originalContent;
                }
            });

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

