<?php
session_start();


// 允许所有域名访问
header("Access-Control-Allow-Origin: *");


// 允许的 HTTP 方法
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// 允许的请求头
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 是否允许发送 Cookie
header("Access-Control-Allow-Credentials: true");

// 预检请求缓存时间
header("Access-Control-Max-Age: 86400");

// 处理 OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}



sleep(1);
if($_SESSION["user_id"] === 1 or $_SESSION["user_id"] === 2 or $_SESSION["user_id"] === 19){

    header("location: http://123.56.160.48:3000/authorized?key=521102&uid=" . $_SESSION["user_id"]);
    exit();
}
else {
    header("location: http://123.56.160.48:3000/");
    exit();
}





?>