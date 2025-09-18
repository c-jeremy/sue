<?php
/*Get the bearer and ares for FC for uploading.*/

// 检查会话变量
if (!isset($_GET['user_id'])) {
    echo json_encode(["message" => "Must give user_id"]);
    exit;
}

header('Content-Type: application/json');


$res = file_get_contents("./credentials/keys-".$_GET['user_id'].".auth");
if ($res === false) {
    echo json_encode(['response_code' => 500, 'message' => 'Cannot get latest SEIUE Sign In key.']);
    exit;
}

$ares = file_get_contents("./credentials/activeref-".$_GET['user_id'].".auth");
if ($ares === false) {
    echo json_encode(['response_code' => 500, 'message' => 'Cannot get latest SEIUE Sign In key - active ref.']);
    exit;
}

echo json_encode(["message" => "OK", "bearer" => $res, "ares" => $ares]);
?>