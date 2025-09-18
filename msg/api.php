<?php
$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();
/*
Only for test
if (isset($_GET['nastyhackerisnotme'])){
    $_SESSION['user_id'] = $_GET['nastyhackerisnotme'];
    header('Location: /tasks.php');
    exit;
}
*/
// 检查会话变量
if (!isset($_SESSION['user_id'])) {
    
    header('HTTP/1.1 302 Found');
    header('Location: /login.php');
    exit;
}
$ares = file_get_contents("../credentials/activeref-".$_SESSION['user_id'].".auth");

if ($ares === false) {
    die("Unexpected error: could not get the latest keys.");
}
$res = file_get_contents("../credentials/keys-".$_SESSION['user_id'].".auth");
if ($res === false) {
    die("Unexpected error: could not get the latest keys.");
}
// Set the content type to JSON
header('Content-Type: application/json');

// Ensure the request method is POST, otherwise terminate the script
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    //die(json_encode(array("error" => "Invalid request method")));
}
//echo $res."分隔符分隔符";
//echo $ares;
// 获取POST请求的数据
$postData = file_get_contents("php://input");
$data = json_decode($postData, true);
// Retrieve and sanitize POST parameters
$stdate = isset($data['stdate']) ? filter_var($data['stdate'], FILTER_SANITIZE_STRING) : null;
$edate = isset($data['edate']) ? filter_var($data['edate'], FILTER_SANITIZE_STRING) : null;
$keyword = isset($data['keyword']) ? filter_var($data['keyword'], FILTER_SANITIZE_STRING) : null;
$isread = isset($data['isread']) ? filter_var($data['isread'], FILTER_VALIDATE_BOOLEAN) : false;
$currentPage = isset($data['currentPage']) ? filter_var($data['currentPage'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1))) : 1;
$currentCategory = isset($data['currentCategory']) ? filter_var($data['currentCategory'], FILTER_SANITIZE_STRING) : "ALL";
$messagesPerPage = isset($data['messagesPerPage']) ? filter_var($data['messagesPerPage'], FILTER_VALIDATE_INT) : 20;
$keyword = urlencode($keyword);
// Fetch authentication key


/**
 * Fetch message counts
 * @param string $res Authentication key
 * @param string|null $stdate Start date
 * @param string|null $edate End date
 * @param bool $isread Whether to fetch read or unread messages
 * @return array Array containing message counts and total count
 */
function countsE($res, $ares,$stdate, $edate, $isread,$keyword) {
    $url2 = "https://api.seiue.com/chalk/me/received-messages-counts?count_fields=domain&domain_in=class_assessment%2Cdorm_assessment%2Ctask%2Cchat%2Cpsy_chat%2Cgroup%2Cdorm%2Cattendance%2Cleave_flow%2Ccalendar%2Cschcal%2Cnotification%2Cclass_adjustment%2Cevaluation%2Cmember_type%2Cclass%2Cdirection%2Cvenue%2Cdeclaration%2Cexam_schedule%2Ccertification%2Cclass_stop%2Celection%2Cschool_plugin%2Cevent%2Cgroup_notice%2Csignup_submission%2Ccustom_group%2Cworkflow%2Cpsychological%2Cclassin%2Cteacher_profile%2Cvisitor%2Cquestionnaire%2Chandout%2Cadmin_class%2Cexam%2Ccontract%2Csz_homework_survey%2Cintl_goal%2Cmoral_assessment%2Cteacher_assessment%2Cclass_review%2Cai-teacher&notice=true&owner.id=".$ares."&readed=" . ($isread ? "true" : "false");
    
    if ($stdate && $edate) {
        $url2 .= "&start_at_egt=" . urlencode($stdate)."%2000%3A00%3A00" . "&end_in_elt=" . urlencode($edate)."%2023%3A59%3A59";
    }
    if ($keyword) {
        $url2 .= "&keyword=" . $keyword;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Pragma: no-cache',
        'Accept: application/json, text/plain, */*',
        'Authorization: Bearer ' . $res,
        'X-Reflection-Id:'.$ares
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON decoding error: ' . json_last_error_msg());
    }

    $config = [];
    $totalCount = 0;

    foreach ($data as $item) {
        $content = substr($item['group'], strlen('domain/'));
        $count = $item['count'];
        $config[$content] = $count;
        $totalCount += $count;
    }
    arsort($config);

    return array($config, $totalCount);
}

/**
 * Fetch messages
 * @param string $res Authentication key
 * @param array $result Message counts
 * @param int $count Total count
 * @param int $page Current page
 * @param string $currentCategory Current category
 * @param bool $isread Whether to fetch read or unread messages
 * @return string JSON encoded message data
 */

function fetch_url($res, $ares,$result, $count, $page, $currentCategory, $isread,$stdate, $edate,$keyword,$messagesPerPage) {
    $url2 = "https://api.seiue.com/chalk/me/received-messages?" .
        "expand=aggregated_messages&" .
        "notice=true&" .
        "owner.id=".$ares."&" .
        "page=" . $page . "&" .
        "per_page=".$messagesPerPage."&" .
        "readed=" . ($isread ? "true" : "false");
    
    if ($currentCategory !== "ALL") {
        $url2 .= "&domain=" . urlencode($currentCategory);
    }
    if ($stdate && $edate) {
        $url2 .= "&start_at_egt=" . urlencode($stdate)."%2000%3A00%3A00" . "&end_in_elt=" . urlencode($edate)."%2023%3A59%3A59";
    }
    if ($keyword) {
        $url2 .= "&keyword=" . $keyword;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Pragma: no-cache',
        'Accept: application/json, text/plain, */*',
        'Authorization: Bearer ' . $res,
        'X-Reflection-Id:'.$ares
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON decoding error: ' . json_last_error_msg());
    }

    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

try {
    // Fetch message counts
    $result = countsE($res, $ares,$stdate, $edate, $isread,$keyword);

    // Fetch messages
    $jsonData = fetch_url($res, $ares,$result[0], $result[1], $currentPage, $currentCategory, $isread,$stdate, $edate,$keyword,$messagesPerPage);

    // Prepare and send the response
    $response = [
        "jsonData" => json_decode($jsonData, true),
        "result" => $result[0],
        "total" => $result[1]
    ];
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(array("error" => $e->getMessage()));
}

exit;
?>