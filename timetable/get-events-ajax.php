<?php
// File: get-events-ajax.php

session_start();

// Check session variable
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

date_default_timezone_set('Asia/Shanghai');

require_once "./logger.php";
__($_SESSION["user_id"], date('Y-m-d H:i:s'), "View timetable", $_ENV["ENV"], 1);

$ares = file_get_contents("../credentials/activeref-".$_SESSION['user_id'].".auth");
$res = file_get_contents("../credentials/keys-".$_SESSION['user_id'].".auth");

if ($ares === false || $res === false) {
    http_response_code(500);
    exit('Error: Could not get the latest keys.');
}

$week = isset($_GET['week']) && is_numeric($_GET['week']) ? intval($_GET['week']) : 0;

if ($week > 10000) {
    http_response_code(400);
    exit('Error: Week value too large.');
} elseif ($week < -10000) {
    http_response_code(400);
    exit('Error: Week value too small.');
}

$now = new DateTime();
$startOfWeek = clone $now;
$startOfWeek->modify('this week')->modify($week * 7 . ' days');
$endOfWeek = clone $startOfWeek;
$endOfWeek->modify('+6 days');

$startOfWeekStr = $startOfWeek->format('Y-m-d');
$endOfWeekStr = $endOfWeek->format('Y-m-d');

function get_events($ares, $res, $startOfWeekStr, $endOfWeekStr) {
    $ch = curl_init();
    $url = "https://api.seiue.com/chalk/calendar/personals/$ares/events?end_time=$endOfWeekStr%2023%3A59%3A59&expand=address%2Cinitiators&start_time=$startOfWeekStr%2000%3A00%3A00";
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $res,
            'X-Reflection-Id:' . $ares
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        http_response_code(500);
        exit('Error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['status']) && $data['status'] == 400) {
        http_response_code(400);
        exit('Error: Unexpected error from API.');
    }
    
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

$data = get_events($ares, $res, $startOfWeekStr, $endOfWeekStr);
echo json_encode([
    'events' => $data,
    'weekDates' => [
        $startOfWeek->format('m-d'),
        $startOfWeek->modify('+1 day')->format('m-d'),
        $startOfWeek->modify('+1 day')->format('m-d'),
        $startOfWeek->modify('+1 day')->format('m-d'),
        $startOfWeek->modify('+1 day')->format('m-d'),
        $startOfWeek->modify('+1 day')->format('m-d'),
        $startOfWeek->modify('+1 day')->format('m-d'),
    ],
    'weekNumber' => !empty($data[0]['remark']) ? $data[0]['remark'] : end($data)['remark']
]);