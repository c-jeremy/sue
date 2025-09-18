<?php

//正在将文件封装为函数
/* 重要！！必须修改162行左右的type，动态获取才能上传！！！！！！ */
 
    
header('Content-Type: application/json');
($_SERVER["REQUEST_METHOD"]==="POST") || die("{'Nasty hacker':'Go away'}");
session_start();
if (!isset($_SESSION['user_id'])) {
            header('HTTP/1.1 302 Found');
            header("Location: ./login.php");
            exit();
        }

require "./create_conn.php";
$conn = create_conn();

$ares = file_get_contents("./credentials/activeref-".$_SESSION['user_id'].".auth");
if ($ares === false) {
echo json_encode(['response_code' => 500, 'message' => 'Cannot get latest SEIUE Active key.']);
    exit;
}
$res = file_get_contents("./credentials/keys-".$_SESSION['user_id'].".auth");
if ($res === false) {
echo json_encode(['response_code' => 500, 'message' => 'Cannot get latest SEIUE Sign In key.']);
    exit;
}

// 获取 URL 参数，处理上传文件，允许可选参数（filename, filesize, hash, mimetype）
$file_name = isset($_POST['filename']) ? $conn->real_escape_string($_POST['filename']) : null;
$file_size = isset($_POST['filesize']) ? $_POST['filesize'] : null;
$the_hash = isset($_POST['hash']) ? $_POST['hash'] : null;
// $mimetype = isset($_POST['mimetype']) ? $_POST['mimetype'] : null;



require_once "./logger.php";
 __($_SESSION["user_id"], "Uploaded files", $_ENV["ENV"], 1);
$user_id = $_SESSION["user_id"];

// 获取上传的文件内容
// if (isset($_FILES["file"])) {
//     $file = $_FILES["file"];
// } else {
//     echo json_encode(['response_code' => 400, 'message' => 'No file uploaded with the provided filename']);
//     exit;
// }

if (!$file_name) {
    $file_name = $conn->real_escape_string($file['name']);
}
// 如果用户没有传递文件的大小或哈希值，计算它们
// if (!$file_size) {
//     $file_size = $file['size'];
// }

// if (!$the_hash) {
//     $the_hash = md5_file($file['tmp_name']);
//     if ($the_hash === false) {
//         echo json_encode(['response_code' => 500, 'message' => 'Unable to calculate file hash']);
//         exit;
//     }
// }

// // 初始化 cURL 会话
// $ch = curl_init();

// // 设置请求的 URL
// $url = 'https://api.seiue.com/chalk/netdisk/files';
// curl_setopt($ch, CURLOPT_URL, $url);

// // 设置请求方法为 POST
// curl_setopt($ch, CURLOPT_POST, true);

// // 设置请求头。注意，这三次请求的请求头不全一致，不许轻易修改，很可能报错！！亲测如此！！！
// $headers = [
//     'accept: application/json, text/plain, */*',
//     'accept-language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
//     'authorization: Bearer '. $res,
//     'cache-control: no-cache',
//     'content-type: application/json',
//     'x-reflection-id: '.$ares,
//     'x-role: student',
//     'x-school-id: 3',
// ];
// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// // 设置请求体
// $data = json_encode([
//     'netdisk_owner_id' => 0,
//     'name' => $file_name,
//     'parent_id' => 0,
//     'path' => '/',
//     'mime' => '',
//     'type' => 'other',
//     'size' =>  112,
//     'hash' => $the_hash,
//     'status' => 'uploading'
// ]);
// curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

// // 设置返回响应而不是直接输出
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// // 执行请求
// $response = curl_exec($ch);

// // 检查是否有错误发生
// if (curl_errno($ch)) {
//     echo json_encode(['response_code' => 520, 'message' =>'Failed to fetch Seiue on uploading file general info.']);
    
// // 关闭 cURL 资源，并释放系统资源
// curl_close($ch);

//     exit;
// }

// // 关闭 cURL 会话
// curl_close($ch);

// $responsed = json_decode($response);
// $the_id = $responsed->id;

// // 第二步——文件基础信息全部上报完毕。准备获取OSS临时上传凭证。


// $url = 'https://api.seiue.com/chalk/netdisk/files/'. $the_id .'/policy';

// $headers = [
//     'Accept: application/json, text/plain, */*',
//     'Accept-Language: zh-CN,zh;q=0.9',
//     'Authorization: Bearer ' . $res,
//     'Connection: keep-alive',
//     // 'Origin: https://chalk-c3.seiue.com',
//     // 'Referer: https://chalk-c3.seiue.com/',
//     // 'Sec-Fetch-Dest: empty',
//     // 'Sec-Fetch-Mode: cors',
//     // 'Sec-Fetch-Site: same-site',
//     // 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0',
//     'X-Reflection-Id:'. $ares,
//     'X-Role: student',
//     'X-School-Id: 3',
//     // 'sec-ch-ua: "Chromium";v="130", "Microsoft Edge";v="130", "Not?A_Brand";v="99"',
//     // 'sec-ch-ua-mobile: ?0',
//     // 'sec-ch-ua-platform: "Windows"'
// ];

// $ch = curl_init();

// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// $need_response = curl_exec($ch);

// if(curl_errno($ch)) {
//     echo json_encode(['response_code' => 520, 'message' => 'Failed to fetch Seiue when obtaining policy.']);
    
// // 关闭 cURL 资源，并释放系统资源
// curl_close($ch);

//     exit;
// }
// curl_close($ch);

// $free = 1;


// $user_id = $_SESSION['user_id'];
// $sql = "SELECT user_type FROM users WHERE id = ?";
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("i", $user_id);
// $stmt->execute();
// $result = $stmt->get_result();
// if ($result->num_rows === 1) {
// $row = $result->fetch_assoc();
// if ($row['user_type'] === "super"){ $free = 10; } 
// }

// // 验证文件大小（例如限制在10MB以内）
// $maxSize = 10* 1024 * 1024 * $free;  // 10MB
// if ($file['size'] > $maxSize) {
//     echo json_encode(['response_code' => 400, 'message' => 'File size exceeds limit.']);
//     exit;
// }


// // 解码 JSON
// $response_data = json_decode($need_response, true);

// // 定义上传所需的字段
// $fields = array(
//     'key' => $response_data['object_key'],
//     'OSSAccessKeyId' => $response_data['access_key_id'],
//     'policy' => $response_data['policy'],
//     'signature' => $response_data['signature'],
//     'expire' => $response_data['expire'],
//     'callback' => $response_data['callback'],
//     'Cache-Control' => 'max-age=31536000'  // 此处可以根据需要调整
// );




// // 创建一个新的 cURL 资源
// $ch = curl_init();

// // 设置 URL 和其他选项
// curl_setopt($ch, CURLOPT_URL, $response_data['host']);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);

// // 构建 POST 数据
// $postFields = array_merge($fields, [
//     'file' => new CURLFile($file['tmp_name'], $file['type'], basename($file['name']))
// ]);

// curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

// // 设置 HTTP 头
// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     'Accept: application/json, text/plain, */*',
//     'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
//     'Cache-Control: no-cache',
//     'Connection: keep-alive',
// ]);

// // 执行请求
// $response = curl_exec($ch);


    // 获取响应的 HTTP 状态码
        

        //$file_name = $_FILES['file']['name'];
        //$file_tmp = $_FILES['file']['tmp_name'];
        
        // Generate a unique file name
        //$file_name_new = uniqid('', true) . '_' . $file_name;
        //$upload_path = 'uploads/' . $file_name_new;
        
        //if (move_uploaded_file($file_tmp, $upload_path)) {
            $file_url = "https://api.seiue.com/chalk/netdisk/files/". $the_hash ."/url";// . $upload_path;
            
            $sql = "INSERT INTO user_files (user_id, file_name, file_url, file_size) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issi", $user_id, $file_name, $file_url, $file_size);
            
            if ($stmt->execute()) {
                
                //header("Location: cloud-demo.php");
                //exit();
            } else {
                echo json_encode(["Error: " => $stmt->error]);
            }
            
            $stmt->close();
        //} else {
          //  echo "Failed to upload your file.";
        //}
        
        $conn->close();
        
        echo json_encode(['response_code' => 200, 'message' => 'Upload Done.', 'file_name'=> $file_name, 'file_type' => $file["type"], "file_size"=>$file_size, "hash"=> $the_hash, "OSS_Upload_id"=> $the_id, "file_size"=>$file_size, "download_url"=>"https://api.seiue.com/chalk/netdisk/files/". $the_hash ."/url"]);
        
    


// 关闭 cURL 资源，并释放系统资源
curl_close($ch);


?>
