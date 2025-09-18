<?php
/** 请使用这个文档来创建与数据库的连接 **/
function create_conn(){
    // 参数配置
    $servername = "localhost";
    $username = "seiue_db";
    $password = "12345678";
    $dbname = "seiue_db";
    // 创建连接
    $conn = new mysqli($servername, $username, $password, $dbname);
    // 检查连接
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

?>

