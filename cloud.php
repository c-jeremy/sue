<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PublicCloud - Sue</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <script src="./twind.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
    
            min-height: 100vh;
            margin: 0;
            padding: 32px;
            box-sizing: border-box;
        }
        .container {
            max-width: 1024px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 48px;
        }
        h1 {
            font-size: 36px;
            font-weight: bold;
            color: #1f2937;
        }
        .content {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            padding: 24px;
            transition: transform 0.3s ease-in-out;
        }
        .content:hover {
            transform: scale(1.02);
        }
        h2 {
            font-size: 24px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 24px;
        }
        ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        li:last-child {
            border-bottom: none;
        }
        .download-btn {
            background-color: #ec4899;
            color: white;
            padding: 8px 16px;
            border-radius: 9999px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .download-btn:hover {
            background-color: #9d174d;
        }
        footer {
            text-align: center;
            margin-top: 48px;
            color: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container">
        <?php 
        $include_src = "cloud";
        require "./global-header.php";
        
require_once "./logger.php";
 __($_SESSION["user_id"], "View Public Cloud", $_ENV["ENV"], 1);
        ?>
        <main class="content">
            <ul>
                <li>
                    <span>Git For Windows</span>
                    <a href="https://api.seiue.com/chalk/netdisk/files/ceee36c1a922a546df83f5b8b225b6d6/url" target="_blank" rel="noopener noreferrer" class="download-btn">下载</a>
                </li>
                <li>
                    <span>Python 3.11.5</span>
                    <a href="https://api.seiue.com/chalk/netdisk/files/3afd5b0ba1549f5b9a90c1e3aa8f041e/url" target="_blank" rel="noopener noreferrer" class="download-btn">下载</a>
                </li>
                <li>
                    <span>Visual Studio Code</span>
                    <a href="https://api.seiue.com/chalk/netdisk/files/f79adea3c54b03dd7d3113f86cd88049/url" target="_blank" rel="noopener noreferrer" class="download-btn">下载</a>
                </li>
                <li>
                    <span>200MB无损音乐</span>
                    <a href="https://api.seiue.com/chalk/netdisk/files/6f88e40346a24f295748ce5aeaa68820/url" target="_blank" rel="noopener noreferrer" class="download-btn">下载</a>
                </li> <li>
                    <span>40MB全损音乐</span>
                    <a href="https://api.seiue.com/chalk/netdisk/files/b8b8eb835def786a511eb4a1ca55aefe/url" target="_blank" rel="noopener noreferrer" class="download-btn">下载</a>
                </li>
            </ul>
            
        </main>
        <?php require "./global-footer.php";?>
    </div>
</body>
</html>