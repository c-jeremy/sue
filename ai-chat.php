<?php
// 启用错误报告
$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();
require 'Parsedown.php';
require_once "./logger.php";
// 创建 Parsedown 实例

$Parsedown = new Parsedown();

// 检查会话变量
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header('Location: login.php');
    exit;
}

 __($_SESSION["user_id"], "View FakeAI", $_ENV["ENV"], 1);
require_once "./credentials.php";
if ($_GET['bot']){
    $_SESSION['bot'] = $_GET['bot'];
}

if ($_GET['bot']){
    $_SESSION['group_id'] = $_GET['group_id'];
}
if ($_GET['bot']){
    $_SESSION['school'] = $_GET['school'];
}

define('API_URL', 'https://api.seiue.com/ais/teacher-bot/teacher-bots/'.$_SESSION['bot']);
define('GROUP_ID', $_SESSION['group_id']);
define('USER_ID', $ares);
define('TOKEN', $res);

//echo GROUP_ID;
// functions.php
function callApi($url, $method = 'GET', $data = []) {
    $ch = curl_init();
    $fullUrl = API_URL . $url;
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = [
        'accept: application/json',
        'authorization: Bearer ' . TOKEN,
        'x-reflection-id: ' . USER_ID
    ];
    
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'content-type: application/json';
    } elseif ($method == 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
   
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // if ($method == 'POST') {
    //     file_put_contents("./ai.txt",$response."sdhvsruhvsdvsd");
    // }
    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }
    
    
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    } else {
        return false;
    }
}

// 路由处理
$route = $_GET['route'] ?? 'index';

// CSRF 保护
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conversation_id = filter_input(INPUT_GET, 'conversation_id', FILTER_SANITIZE_STRING);
$messages = [];
$error = null;
$success = null;

// 删除对话
if ($route == 'delete') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('CSRF token mismatch');
        }
        $conversation_id = filter_input(INPUT_POST, 'conversation_id', FILTER_SANITIZE_STRING);
        if ($conversation_id) {
            $deleteUrl = "/groups/" . GROUP_ID . "/conversations/" . $conversation_id;
            $response = callApi($deleteUrl, 'DELETE');
            if ($response !== false) {
                $success = "对话已成功删除！";
            } else {
                $error = "删除对话失败，请重试。";
            }
        } else {
            $error = "无效的对话ID。";
        }
    }
}

// 创建新对话
if ($route == 'create') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('CSRF token mismatch');
        }
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        if ($title) {
            $response = callApi("/groups/" . GROUP_ID . "/conversations", 'POST', ['title' => $title]);
            if ($response !== false && isset($response['id'])) {
                $success = "新对话创建成功！";
                // 重定向到新创建的对话
                header("Location: ?route=chat&conversation_id=" . urlencode($response['external_conversation_id']));
                exit;
            } else {
                $error = "创建对话失败，请重试。";
           }
        } else {
            $error = "标题不能为空。";
        }
    }
}

// 聊天功能
if ($route == 'chat') {
    if (!$conversation_id) {
        $error = "无效的对话ID。";
    } else {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                die('CSRF token mismatch');
            }
            $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
            $file_url = filter_input(INPUT_POST, 'file_url', FILTER_SANITIZE_STRING);
            if ($message && $file_url) {
                /*
{"title":"1+1=？","file":{"file_url":"https://api.seiue.com/chalk/netdisk/files/2d8623388f4df5579d7898aca4148ba1/url","file_type":1,"file_name":"2d8623388f4df5579d7898aca4148ba1_曹智铭项目二运行效果1.png","file_size":16167}}
*/
                $response = callApi("/groups/" . GROUP_ID . "/chat?conversion_id=$conversation_id&user_id=" . USER_ID, 'POST', ['title' => $message, "file" => ["file_url" => $file_url, "file_type"=>1, "file_name"=>"Unset", "file_size"=>114514]]);
                if ($response === false) {
                    $error = "发送消息失败。请重试。";
                } else {
                    $yes ="no";
                }
            }
            elseif($message){
                  $response = callApi("/groups/" . GROUP_ID . "/chat?conversion_id=$conversation_id&user_id=" . USER_ID, 'POST', ['title' => $message]);
                if ($response === false) {
                    $error = "发送消息失败。请重试。";
                } else {
                    $yes ="no";
                }
            }
            else {
                $error = "无效的消息。请重试。";
            }
        }
        
        // 获取最新的消息
        $messagesResponse = callApi("/messages?conversion_id=$conversation_id&order=1&page=1&size=50&user_id=" . USER_ID);
        if ($messagesResponse === false) {
            $error = "获取消息失败。请稍后重试。";
        } else {
            $messages = $messagesResponse['list'] ?? [];
            
          
        }
    }
}

// 对话列表功能
if ($route == 'index') {
    $conversationsResponse = callApi("/conversations?expand=reminders%2Cstudent_is_viewed&group_id=" . GROUP_ID . "&page=1&per_page=30&user_id=" . USER_ID);
 
    
    if ($conversationsResponse === false) {
        $error = "获取对话列表失败。请稍后重试。";
        $conversations = [];
    } else {
        // Check if 'list' key exists, if not, assume the entire response is the list
        $conversations = isset($conversationsResponse['list']) ? $conversationsResponse['list'] : $conversationsResponse;
        
        // Ensure $conversations is an array
        if (!is_array($conversations)) {
            $conversations = [];
        }
    
    }
}

// HTML 输出
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 对话系统</title>
    <script src="twind.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" type="image/png" href="/favicon.png">
  <link rel="stylesheet" href="hl-d.css">
  
  <style>
      
/* 复制按钮的基础样式 */
.copy-button {
    
    transform: translateY(-50%);
    background-color: #007bff; /* 蓝色背景 */
    color: white;
    border: none;
    padding: 5px 10px;
    margin: 20px 0px 0px 0px;
    font-size: 14px;
    cursor: pointer;
    border-radius: 3px;
    transition: background-color 0.2s ease, transform 0.2s ease;
    opacity: 0.85;
}

/* 悬停时的样式 */
.copy-button:hover {
    background-color: #0056b3;
    opacity: 1;
}

/* 激活（点击）状态下的样式 */
.copy-button:active {
    transform: translateY(-50%) scale(0.98);
}

/* 聚焦时的样式 */
.copy-button:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.5);
}

/* 当按钮被禁用时的样式 */
.copy-button:disabled {
    background-color: #ced4da;
    color: #adb5bd;
    cursor: not-allowed;
}

/* 已复制后的样式 */
.copy-button.copied {
    background-color: #28a745; /* 成功绿色 */
}

      
  </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <input type="text" id="textToCopy" hidden value="逆天就逆天吧，你能有什么办法">
    <div class="container mx-auto px-4 py-8">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($success) ?></span>
            </div>
            <a class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800" href="?route=index">
                        返回列表
                    </a>
        <?php endif; ?>

        <?php if ($route == 'index'): ?>
            <h1 class="text-3xl font-bold mb-6">对话列表</h1>
            <a href="?route=create" class="mb-4 inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                创建新对话
            </a>
            <?php if (!empty($conversations)): ?>
                <ul class="space-y-2">
                    <?php foreach ($conversations as $conversation): ?>
                        <li class="flex justify-between items-center bg-white p-4 rounded-lg shadow hover:shadow-md transition">
                            <a href="?route=chat&conversation_id=<?= urlencode($conversation['external_conversation_id'] ?? $conversation['id'] ?? '') ?>" class="flex-grow">
                                <?= htmlspecialchars($conversation['title'] ?? 'Untitled Conversation') ?>
                            </a>
                            <form action="?route=delete" method="post" class="inline" onsubmit="return confirm('确定要删除这个对话吗？');">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="conversation_id" value="<?= htmlspecialchars($conversation['id'] ?? '') ?>">
                                <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-sm">删除</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>暂无对话。</p>
            <?php endif; ?>
            <div class="mt-4 text-sm text-gray-600 ">
                Total conversations: <?= count($conversations) ?>
                <a class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800" href="AI-gallery.php">
                        返回主页
                    </a>
            </div>
        <?php endif; ?>
<?php if ($route == 'create'): ?>
            <h1 class="text-3xl font-bold mb-6">创建新对话</h1>
            <form action="?route=create" method="post" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                        对话标题
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="title" type="text" name="title" placeholder="输入对话标题" required>
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                        创建对话
                    </button>
                    <a class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800" href="?route=index">
                        返回列表
                    </a>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($route == 'chat' && $conversation_id): ?>
            <h1 class="text-3xl font-bold mb-6">与AI对话</h1>
            <div id="chat" class="bg-white p-6 rounded-lg shadow mb-6 space-y-4 h-96 overflow-y-auto">
                
                <?php $chunks = array_chunk($messages, 2); foreach (array_reverse($chunks) as $chunk): foreach ($chunk as $message): ?>
                    <div class="p-4 rounded-lg <?= $message['role'] == 'user' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?>">
                        <p class="whitespace-pre-wrap"><?= $Parsedown->text($message['content']) ?><?php if(!empty($message["file"]["file_name"])){echo "<br>[This message attaches <a style='text-decoration:underline' rel='noopener noreferrer' href='".$message["file"]["file_url"]."'>a file</a>]";}?></p>
                    </div>
                <?php endforeach; endforeach; ?>
                
            </div>
<form id="chatForm" method="post" class="mb-6 relative">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <div class="flex items-center">
        <a href="/ups.php?redirect=<?php
        $scriptName = $_SERVER['PHP_SELF'];

// 获取查询字符串
$queryString = $_SERVER['QUERY_STRING'];

// 构造相对URL
$relativeUrl = $scriptName;
if (!empty($queryString)) {
    $relativeUrl .= '?' . $queryString;
}

// 输出相对URL
echo urlencode($relativeUrl);
        ?>" class="mr-2">
            <?php
            if($_REQUEST["file"]){?>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500 hover:text-blue-500">
                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                <polyline points="13 2 13 9 20 9"></polyline>
            </svg>
            <?php }
            else{?>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500 hover:text-blue-500">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg><?php }?>
        </a>
        <textarea name="message" id="messageInput" placeholder="请输入您的问题..." required class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
    </div>
    <button id="msgSubmitButton" type="submit" class="mt-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">发送</button>
</form>
            <a href="?route=index" class="text-blue-500 hover:underline">返回列表</a>

    <script>
function startBlurAnimation(selector, duration = 200) {
    // 获取目标元素
    const $element = $(selector);

    if ($element.length === 0) {
        console.error(`Element with selector "${selector}" not found.`);
        return;
    }

    let blurValue = 9;  // 初始模糊值
    const maxBlur = 10;  // 最大模糊值
    const minBlur = 0;   // 最小模糊值
    const step = 0.1;      // 每次变化的步长
    const steps = maxBlur - minBlur;  // 总步骤数
    const stepDuration = duration / (2 * steps);  // 每一步所需的时间

    let increasing = false;  // 用于判断模糊值是增加还是减少

    function animateBlur() {
        // 更新模糊值
        $element.css('filter', `blur(${blurValue}px)`);

        // 调整下一个模糊值
        if (!increasing) {
            blurValue -= step;
            if (blurValue <= minBlur) {
                increasing = true;
            }
        } else {
            blurValue += step;
            if (blurValue >= maxBlur) {
                increasing = false;
            }
        }

        // 继续动画
        setTimeout(animateBlur, stepDuration);
    }

    // 开始动画
    animateBlur();
}

            $(document).ready(function() {
                var chatDiv = $('#chat');
                chatDiv.scrollTop(chatDiv[0].scrollHeight);

                $('#chatForm').submit(function(e) {
                    e.preventDefault();
                    var message = $('#messageInput').val();
                    
                    var csrf_token = $('input[name="csrf_token"]').val();
                    if (message.trim() !== '') {
                        $.ajax({
                            url: '?route=chat&conversation_id=<?= urlencode($conversation_id) ?>',
                            method: 'POST',
                            data: { message: message, csrf_token: csrf_token
                            
                                <?php 
                                if($_REQUEST["file"]){
                                    echo ", file_url: '" . $_REQUEST["file"] . "'";
                                }
                                
                                ?>
                            },
                            success: function(response) {
                                const url = new URL(window.location.href);
                                const cid= url.searchParams.get("conversation_id");
                                window.location.href = "/ai-chat.php?route=chat&conversation_id=" + cid;
                            },
                            error: function() {
                                alert('发送消息失败，请重试。');
                            }
                        });
                        // 初始化点的数量
let dots = 1;

function updateMessage() {
    // 根据当前点数更新输入框内容
    const message = 'ChatGPT is thinking' + '.'.repeat(dots);
    $('#messageInput').val(message);

    // 增加点数，如果达到3则重置为1
    dots = (dots < 3) ? dots + 1 : 1;

    // 设置下一次调用此函数的时间
    setTimeout(updateMessage, 300);  // 300毫秒 = 0.3秒
}

// 启动定时器
updateMessage();
startBlurAnimation('#chat');
$("#msgSubmitButton").disabled ="disabled";
                    }
                });
            });
            </script>
        <?php endif; ?>
    </div>
    <script src=hl.js></script>
   <script>
document.addEventListener("DOMContentLoaded", function() {
    hljs.highlightAll();

    // 为每个代码块添加复制按钮
    document.querySelectorAll('pre code').forEach((block) => {
        const pre = block.parentElement; // 获取 <pre> 元素
        pre.classList.add('code-block'); // 添加 class 以便定位
       

        const button = document.createElement('button');
        button.className = 'copy-button';
        button.innerText = '复制';
        //button.disabled='disabled';
        button.addEventListener('click', () => {
            if (window.location.protocol === 'https:') {
                navigator.clipboard.writeText(block.innerText).then(() => {
                    button.innerText = '已复制';
                    setTimeout(() => {
                        button.innerText = '复制';
                        
                    }, 2000);
                });
            } else if (window.location.protocol === 'http:') {
                var textBox = document.getElementById("textToCopy");
                textBox.value = block.innerText;
                textBox.hidden = false; // 暂时显示输入框以便选择
                textBox.select();       // 选择文本框中的文本
                textBox.setSelectionRange(0, 99999);
                document.execCommand('copy'); // 执行复制命令
                textBox.hidden = true; // 再次隐藏输入框
                //alert('已复制文本到剪贴板');
                button.innerText = '已复制';
                    setTimeout(() => {
                        button.innerText = '复制';
                        
                    }, 2000);
            } else {
                console.log('当前是其他协议，不理解');
            }
            
        });
        pre.appendChild(button); // 将按钮添加到 <pre> 元素中
    });
});
</script>

</body>
</html>