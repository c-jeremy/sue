<?php
// 登出处理脚本 logout.php

// 开始会话
session_start();

// 销毁会话数据
$_SESSION = array(); // 清空会话变量
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
// 销毁会话
session_destroy();

// 重定向到登录页面
header("Location: login.php");
exit;
?>