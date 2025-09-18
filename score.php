<?php

// 从URL参数中获取 assessment_id, item_id, owner_id
session_start();
require 'credentials.php';
$assessment_id = $_GET['assessment_id'] ?? null;
$item_id = $_GET['item_id'] ?? null;
$owner_id = $ares;

if (!$assessment_id || !$item_id || !$owner_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// 引入 credentials.php 文件以获取 res 和 ares

// 初始化 cURL 会话
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json, text/plain, */*',
    'accept-language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
    'authorization: Bearer ' . $res,
    'x-reflection-id: ' . $ares
]);

// 第一个请求：获取成绩分布信息

// IMPORTANT: Accuracy can be set to any positive number, even max score itself!!! 
$accuracy = $_GET['acc'] ?? 20; // If you set this to 100.... HAHAHHHAA
$distribution_url = "https://api.seiue.com/vnas/klass/items/{$item_id}/stats-distribution?as_owner=true&equal_parts=$accuracy&owner_id={$ares}";
curl_setopt($ch, CURLOPT_URL, $distribution_url);
$distribution_response = curl_exec($ch);
$distribution_data = json_decode($distribution_response, true);

if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch distribution data']);
    exit;
}

// 提取满分（最后一个区间的最大值）
$last_interval = end($distribution_data)['group'];
preg_match('/[\d\.]+/', $last_interval, $matches); // 匹配数字和小数点
$full_score = (float)$matches[0]; // 转换为浮点数
$full_score = $full_score * $accuracy / ($accuracy - 1);
// 第二个请求：获取成绩摘要信息
$summary_url = "https://api.seiue.com/vnas/klass/items/{$item_id}/stats-summary?as_owner=true&owner_id={$ares}";
curl_setopt($ch, CURLOPT_URL, $summary_url);
$summary_response = curl_exec($ch);
$summary_data = json_decode($summary_response, true);

if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch summary data']);
    exit;
}

// 第三个请求：获取个人成绩信息
$personal_url = "https://api.seiue.com/vnas/personal/assessments/{$assessment_id}/achieved-scores?item_id={$item_id}&owner_id={$ares}";
curl_setopt($ch, CURLOPT_URL, $personal_url);
$personal_response = curl_exec($ch);
$personal_data = json_decode($personal_response, true);

if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch personal data']);
    exit;
}

// 关闭 cURL 会话
curl_close($ch);

// 提取个人成绩信息
$gained_score = $personal_data[0]['gained_score'] ?? null;
$gained_score_rank = $personal_data[0]['gained_score_rank'] ?? null;

// 构建最终输出
$output = [
    'distribution_counts' => $distribution_data, // 原封不动返回区间数据
    'full_score' => $full_score, // 满分
    'summary' => $summary_data, // 成绩摘要
    'personal_score' => [
        'gained_score' => $gained_score,
        'gained_score_rank' => $gained_score_rank
    ]
];

// 返回 JSON 响应
header('Content-Type: application/json');
echo json_encode($output);

?>