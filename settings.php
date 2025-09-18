<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header("Location: ./login.php");
    exit();
}
// settings.php
$include_src = "settings";
require "./create_conn.php";
$conn = create_conn();
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();


// 准备SQL语句
$sql = "SELECT user_id, SUM(file_size) as total_storage 
        FROM user_files 
        WHERE user_id = ? 
        GROUP BY user_id";

// 准备和绑定
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("准备查询失败: " . $mysqli->error);
}

$stmt->bind_param("i", $_SESSION["user_id"]);
$total_storage=0;
$formatted_storage="0B";
// 执行查询
if ($stmt->execute()) {
    $result_c = $stmt->get_result();
    if ($result_c->num_rows > 0) {
        $row = $result_c->fetch_assoc();
        $total_storage = $row['total_storage'];
        
        // 格式化存储量
        if ($total_storage < 1024) {
            $formatted_storage = $total_storage . " B";
        } elseif ($total_storage < 1048576) {
            $formatted_storage = round($total_storage / 1024, 2) . " KB";
        } elseif ($total_storage < 1073741824) {
            $formatted_storage = round($total_storage / 1048576, 2) . " MB";
        } else {
            $formatted_storage = round($total_storage / 1073741824, 2) . " GB";
        }
        
        // echo "用户 ID " . $user_id . " 的总存储使用量: " . $formatted_storage;
    } 
} else {
    echo "查询执行失败: " . $stmt->error;
}
$percentage = $total_storage / (5*1024*1024*1024) *100;

// 关闭语句和连接
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Sue</title>
    <script src="./twind.js"></script>
    <script src="./infobtn.js"></script>
    <link rel="icon" href="./favicon.png" type="image/png">
</head>
<body class="bg-gray-100">
    

    <div class="container mx-auto px-4 py-8">
        <?php include 'global-header.php'; ?>
        <form class="bg-white shadow-md rounded-lg p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Settings</h1>
            <div class="mb-6">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                <input type="text" id="username" name="username" value="<?= $result['username']; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Enter your username">
            </div>

            <div class="mb-6">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                <input type="email" id="email" name="email" value="<?= $result['email']; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Enter your email">
            </div>
            
            <div class="mb-6">
                <label for="seiue_sessid" class="block text-gray-700 text-sm font-bold mb-2">Seiue Session ID</label>
                <p class="block text-gray-800 text-md mb-2"><?= substr($result['seiue_sessid'],0,18) . "..."; ?>&nbsp;<span class="info-icon w-5 h-5 bg-blue-500 rounded-full text-white text-xs font-semibold inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-blue-300 transition duration-300 ease-in-out transform hover:scale-110 ml-1" data-info="<b>Immutable</b>: Credential to show your Seiue identity; Equivalent to your Seiue password." onclick="e.stopPropagation();">
                    i
                </span></p>
            </div>
            <hr><br>
            <div class="mb-6">
                <span class="block text-gray-700 text-sm font-bold mb-2">Uploading Preferences</span>
                <div class="mt-2">
                    <label class="inline-flex items-center">
                        <input type="radio" checked="checked" class="form-radio" name="upload" value="yufeng">
                        <span class="ml-2">Yufeng Uploading&nbsp;<span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Recommended</span></span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" disabled="disabled" class="form-radio" name="upload" value="qingtian">
                        <span class="ml-2 text-gray-400">Qingtian Uploading</span>
                    </label>
                    <br>
                    <details><summary>What's this?</summary><i>Qingtian</i> was the first uploading strategy Sue used in the first couple of weeks; currently replaced by <i>Yufeng Uploading</i> on both task attachments and Cloud Storage, for better speed and less cost. To return to Qingtian Uploading, contact the Sue team.</details>
                </div>
            </div>

            <div class="mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" checked="checked" class="form-checkbox" name="shortcut-action">
                    <span class="ml-2">Use <i>Shortcut Actions</i> at tasks&nbsp;</span>
                    </label>
                    <span class="info-icon w-5 h-5 bg-blue-500 rounded-full text-white text-xs font-semibold inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-blue-300 transition duration-300 ease-in-out transform hover:scale-110 ml-1" data-info="Use shortcut actions to download files, follow links, or submit text without the necessity of even opening a task." onclick="e.stopPropagation();">
                    i
                </span>
            </div>
            <hr><br>
            <div class="mb-6">
                
                <span class="block text-gray-700 text-sm font-bold mb-2">Cloud Usage</span>
                <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700">Used <?php echo $formatted_storage; ?> / 5 GB</span>
            <span class="text-sm font-medium text-blue-700"><?php echo number_format($percentage, 1); ?>%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5">
            <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
        </div>
        <?php if ($percentage >= 90): ?>
            <p class="mt-2 text-red-500 text-sm">You are about to run out of storage.</p>
        <?php endif; ?>
    
            </div>

            <div class="flex items-center justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline ripple">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
    <?php require "./global-footer.php"; ?>

  
</body>
</html>