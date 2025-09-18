<?php

//正在将文件封装为函数
    
header('Content-Type: application/json');
($_SERVER["REQUEST_METHOD"]==="POST") || die("{'Nasty hacker':'Go away'}");

session_start();

require_once "./credentials.php";
require "./create_conn.php";
$conn = create_conn();

// 获取 URL 参数，处理上传文件，允许可选参数（filename, filesize, hash, mimetype）
$file_name = isset($_POST['filename']) ? $conn->real_escape_string($_POST['filename']) : null;
$file_size = isset($_POST['filesize']) ? $_POST['filesize'] : null;
$the_hash = isset($_POST['hash']) ? $_POST['hash'] : null;

require_once "./logger.php";
 __($_SESSION["user_id"], "Uploaded files", $_ENV["ENV"], 1);
$user_id = $_SESSION["user_id"];

if (!$file_name) {
    $file_name = $conn->real_escape_string($file['name']);
}

$file_url = "https://api.seiue.com/chalk/netdisk/files/". $the_hash ."/url";// . $upload_path;

$sql = "INSERT INTO user_files (user_id, file_name, file_url, file_size) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issi", $user_id, $file_name, $file_url, $file_size);

if (!$stmt->execute()) {
    echo json_encode(["Error: " => $stmt->error]);
}

$stmt->close();
$conn->close();

echo json_encode(['response_code' => 200, 'message' => 'Upload Done.', 'file_name'=> $file_name, 'file_type' => $file["type"], "file_size"=>$file_size, "hash"=> $the_hash, "OSS_Upload_id"=> $the_id, "file_size"=>$file_size, "download_url"=>"https://api.seiue.com/chalk/netdisk/files/". $the_hash ."/url"]);

curl_close($ch);


?>
