<?php
$the_toilet="";
$flag =1;
$session_lifetime = 90 * 24 * 60 * 60; 
$externalPACanSpeak ="shut_up";
session_set_cookie_params($session_lifetime);
session_start();

require "./create_conn.php";

function userExistsBySeiueUid($conn, $seiueUid) {
    // 防止SQL注入
    $seiueUid = $conn->real_escape_string($seiueUid);

    // 准备SQL查询语句
    $sql = "SELECT COUNT(*) AS count FROM `users` WHERE `seiue_uid` = '$seiueUid'";

    // 执行查询
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        // 检查计数是否大于0
        $conn->close();
        return (int)($row['count'] > 0);
    }
    $conn->close();
    // 如果查询失败或者没有结果，返回0
    return 0;
}

function insertUser($conn, $email, $username, $password, $seiue_sessid, $seiue_uid) {
    // 防止SQL注入
    $email = $conn->real_escape_string($email);
    $username = $conn->real_escape_string($username);
    $password = $conn->real_escape_string($password);
    $seiue_sessid = $conn->real_escape_string($seiue_sessid);

    $seiue_uid = $conn->real_escape_string($seiue_uid);

    // 准备SQL插入语句
    $sql = "INSERT INTO `users` (`email`, `username`, `password`, `seiue_sessid`, `user_type`, `seiue_uid`) 
            VALUES ('$email', '$username', '$password', '$seiue_sessid', 'std', '$seiue_uid')";

    // 执行插入
    if ($conn->query($sql) === TRUE) {
        return true; // 插入成功
    } else {
        return false; // 插入失败
    }
    $conn->close();

}

$conn = create_conn();


if ($_SERVER["REQUEST_METHOD"]==="POST"){
    if(isset($_POST["email"])){
        $flag=0;
        $_SESSION["su_name"]=$_POST["name"];
        $_SESSION["su_email"]=$_POST["email"];
        $_SESSION["su_password"]=$_POST["password"];
    }
    elseif(isset($_POST["sid"])){
        //echo $_POST["spw"];
        
        $_SESSION["sid"]=$_POST["sid"];
        $_SESSION["spw"]=$_POST["spw"];
        require "./auth.php";
        $the_try = try_auth($_SESSION["sid"],$_SESSION["spw"]);
        if($the_try === []){
        
            $the_toilet="用户名或者密码错误。";
        } else {

            if(userExistsBySeiueUid($conn, $the_try["ares"])){
                $the_toilet = "抱歉，已经存在该用户。";
                $flag=0;
                $_SESSION["psi"]=$the_try["psi"];
            } elseif($flag) {
                $conn = create_conn();
                // 调用函数并打印结果
                if (insertUser($conn, $_SESSION["su_email"], $_SESSION["su_name"], hash("sha256", hash("sha256",$_SESSION["su_password"])), $the_try["psi"], $the_try["ares"])) {
                    //echo $the_try["ares"]."/".$_POST["spw"]."/".$the_try["psi"];
                    $the_toilet = "OK!";
                    $externalUserIndicator = $the_try["ares"];
                    require "./pseudo-auth.php";
                    header("location: oauth.html");
                    exit;
                } else {
                    $the_toilet = "抱歉，已存在该用户";
                    //echo "Error: " . $conn->error;
                }

                // 关闭连接
                $conn->close();
            }

        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>希悦校园登录</title>
    <script src="./twind.js"></script>
    <link rel="icon" href="./favicon.png" type="image/png">
    <link href="https://cdn.bootcdn.net/ajax/libs/remixicon/4.1.0/remixicon.min.css" rel="stylesheet">
    <style>
        .input-icon {
            color: #999;
        }
        body {
            background-color: white;
        }
    </style>

  
</head>
<body class="min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="flex justify-between items-center mb-16">
            <div class="flex items-center gap-2">
                <img src="./sfavicon.png" alt="Logo" class="h-10">
                <span class="text-xl">北大附中(高中本部) | 希悦校园</span>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-md mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-normal">账号登录</h1>
                <h2 class="xl font-semibold">将你的希悦账号绑定至第三方应用Sue</h2>
                
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="space-y-4" action="./slogin.php" method="post">
                <div class="relative">
                    <i class="ri-user-line absolute left-4 top-1/2 -translate-y-1/2 input-icon"></i>
                    <input type="text" name="sid"
                           placeholder="账号/手机号/邮箱" 
                           class="w-full pl-12 pr-4 py-3 border rounded-lg focus:outline-none focus:border-blue-500"
                           required>
                </div>

                <div class="relative">
                    <i class="ri-lock-line absolute left-4 top-1/2 -translate-y-1/2 input-icon"></i>
                    <input type="password" 
                           id="password"
                           placeholder="密码" name="spw" 
                           class="w-full pl-12 pr-12 py-3 border rounded-lg focus:outline-none focus:border-blue-500"
                           required>
                    <button type="button" 
                            id="togglePassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2">
                        <i class="ri-eye-off-line input-icon"></i>
                    </button>
                </div>

                <div class="text-right">
                    <a href="https://passport.seiue.com/reset-password?school_id=3" class="text-gray-500 text-sm">忘记密码?</a>
                </div>

                <button type="submit" 
                        class="w-full bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 transition-colors">
                    登录
                </button>

            </form>
            <span><?=$the_toilet ?></span>
        </main>
       
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle icon
            const icon = this.querySelector('i');
            icon.classList.toggle('ri-eye-off-line');
            icon.classList.toggle('ri-eye-line');
        });

        // Handle form submission
        
    </script>
    
</body>
</html>