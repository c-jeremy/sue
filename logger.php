<?php
// 定义 .env 文件的路径
$envFilePath = '.env';

// 检查 .env 文件是否存在
if (file_exists($envFilePath)) {
    // 读取 .env 文件内容
    $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // 跳过注释行
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // 分割键值对
        list($key, $value) = explode('=', $line, 2);

        // 去除多余的空格
        $key = trim($key);
        $value = trim($value);

        // 将环境变量加载到 $_ENV 中
        $_ENV[$key] = $value;
    }
}

require_once "create_conn.php";

if(!isset($conn)){$conn = create_conn();}

function __($user_id, $action_type, $environment, $is_success) {
    
    if(!isset($conn)){$conn = create_conn();}
    // 准备SQL语句
        
    date_default_timezone_set('Asia/Shanghai');
    
    // 获取当前时间并格式化
    $time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO operation_log (user_id, time, action_type, environment, is_success) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $user_id, $time, $action_type, $environment, $is_success);
    $stmt->execute();
    
    // 关闭连接
    $stmt->close();
    $conn->close();
}

//__(1, $currentDateTime, "View messages", $_ENV["env"],1);
//logOperation($conn, 1,"2024-11-22 20:31:59", "SHITTED", "PROD", 1);
?>