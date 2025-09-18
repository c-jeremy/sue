<?php
// file_detail.php - View detailed information about a file
require_once 'config.php';

// Check if file ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$fileId = intval($_GET['id']);

// Get file information
function getFileInfo($conn, $fileId) {
    $sql = "SELECT f.*, u.username, u.email, u.user_type FROM user_files f JOIN users u ON f.user_id = u.id WHERE f.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $file = $result->fetch_assoc();
    $file['file_size_mb'] = round($file['file_size'] / (1024 * 1024), 2);
    $file['file_size_kb'] = round($file['file_size'] / 1024, 2);
    
    return $file;
}

// Get file extension
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Get file type icon
function getFileTypeIcon($extension) {
    switch ($extension) {
        case 'pdf':
            return '<i class="fas fa-file-pdf text-danger"></i>';
        case 'doc':
        case 'docx':
            return '<i class="fas fa-file-word text-primary"></i>';
        case 'xls':
        case 'xlsx':
            return '<i class="fas fa-file-excel text-success"></i>';
        case 'ppt':
        case 'pptx':
            return '<i class="fas fa-file-powerpoint text-warning"></i>';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'bmp':
            return '<i class="fas fa-file-image text-info"></i>';
        case 'mp3':
        case 'wav':
        case 'ogg':
            return '<i class="fas fa-file-audio text-primary"></i>';
        case 'mp4':
        case 'avi':
        case 'mov':
        case 'wmv':
            return '<i class="fas fa-file-video text-danger"></i>';
        case 'zip':
        case 'rar':
        case 'tar':
        case 'gz':
            return '<i class="fas fa-file-archive text-warning"></i>';
        case 'html':
        case 'css':
        case 'js':
            return '<i class="fas fa-file-code text-success"></i>';
        case 'txt':
            return '<i class="fas fa-file-alt text-secondary"></i>';
        default:
            return '<i class="fas fa-file text-secondary"></i>';
    }
}

// Get file preview
function getFilePreview($file) {
    $extension = getFileExtension($file['file_name']);
    $preview = '';
    
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'bmp':
            $preview = '<div class="file-preview-image"><img src="' . $file['file_url'] . '" alt="' . $file['file_name'] . '"></div>';
            break;
        case 'pdf':
            $preview = '<div class="file-preview-pdf"><iframe src="' . $file['file_url'] . '" frameborder="0"></iframe></div>';
            break;
        case 'mp4':
        case 'webm':
        case 'ogg':
            $preview = '<div class="file-preview-video"><video controls><source src="' . $file['file_url'] . '" type="video/' . $extension . '">您的浏览器不支持视频标签。</video></div>';
            break;
        case 'mp3':
        case 'wav':
            $preview = '<div class="file-preview-audio"><audio controls><source src="' . $file['file_url'] . '" type="audio/' . $extension . '">您的浏览器不支持音频标签。</audio></div>';
            break;
        default:
            $preview = '<div class="file-preview-icon">' . getFileTypeIcon($extension) . '<p>无法预览此文件类型</p></div>';
    }
    
    return $preview;
}

// Get file
$file = getFileInfo($conn, $fileId);

if (!$file) {
    header('Location: index.php');
    exit;
}

$fileExtension = getFileExtension($file['file_name']);
$fileIcon = getFileTypeIcon($fileExtension);
$filePreview = getFilePreview($file);
?>

&lt;!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件详情 - <?php echo $file['file_name']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same CSS as index.php and user_detail.php */
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --danger-color: #ff5a5f;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --font-mono: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            --border-radius: 0.375rem;
            --border-radius-lg: 0.5rem;
            --border-radius-sm: 0.25rem;
            
            --transition: all 0.3s ease;
            
            /* Light theme variables */
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #dee2e6;
            --card-bg: #ffffff;
        }
        
        /* Dark theme variables */
        [data-theme="dark"] {
            --bg-primary: #121212;
            --bg-secondary: #1e1e1e;
            --text-primary: #f8f9fa;
            --text-secondary: #adb5bd;
            --border-color: #343a40;
            --card-bg: #1e1e1e;
        }
        
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 400;
            src: url('https://fonts.gstatic.com/s/inter/v12/UcC73FwrK3iLTeHuS_fvQtMwCp50KnMa1ZL7.woff2') format('woff2');
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 600;
            src: url('https://fonts.gstatic.com/s/inter/v12/UcC73FwrK3iLTeHuS_fvQtMwCp50KnMa1ZL7.woff2') format('woff2');
            font-display: swap;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-sans);
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.5;
            transition: var(--transition);
        }
        
        .container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            background-color: var(--bg-primary);
            border-right: 1px solid var(--border-color);
            padding: 1.5rem;
            position: fixed;
            width: 250px;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition);
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .sidebar-header h1 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-left: 0.75rem;
            color: var(--primary-color);
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .sidebar-menu i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .sidebar-footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        /* Main content */
        .main-content {
            grid-column: 2;
            padding: 2rem;
            transition: var(--transition);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 1.75rem;
            font-weight: 600;
        }
        
        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.25rem;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .theme-toggle:hover {
            color: var(--primary-color);
        }
        
        /* Back button */
        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: var(--bg-primary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }
        
        .back-button:hover {
            background-color: var(--bg-secondary);
            color: var(--primary-color);
        }
        
        .back-button i {
            margin-right: 0.5rem;
        }
        
        /* File detail section */
        .file-detail {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .file-info-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            transition: var(--transition);
        }
        
        .file-icon {
            width: 120px;
            height: 120px;
            border-radius: var(--border-radius);
            background-color: var(--bg-secondary);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1.5rem;
        }
        
        .file-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            word-break: break-all;
        }
        
        .file-meta {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .file-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .file-action-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }
        
        .file-action-button:hover {
            background-color: var(--secondary-color);
        }
        
        .file-action-button i {
            margin-right: 0.5rem;
        }
        
        .file-action-button.secondary {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .file-action-button.secondary:hover {
            background-color: var(--bg-primary);
            color: var(--primary-color);
        }
        
        .file-preview-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: var(--transition);
        }
        
        .file-preview-card h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .file-preview-container {
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-secondary);
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .file-preview-image img {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
        }
        
        .file-preview-pdf iframe {
            width: 100%;
            height: 500px;
            border: none;
        }
        
        .file-preview-video video {
            max-width: 100%;
            max-height: 400px;
        }
        
        .file-preview-audio audio {
            width: 100%;
        }
        
        .file-preview-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: var(--text-secondary);
        }
        
        .file-preview-icon i {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        
        /* File details section */
        .file-details-section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .details-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: var(--transition);
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .detail-value {
            font-size: 1rem;
            word-break: break-all;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }
        
        .badge-warning {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--warning-color);
        }
        
        .badge-danger {
            background-color: rgba(255, 90, 95, 0.1);
            color: var(--danger-color);
        }
        
        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .badge-secondary {
            background-color: rgba(58, 12, 163, 0.1);
            color: var(--secondary-color);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .file-detail {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .main-content {
                grid-column: 1;
                padding: 1rem;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        &lt;!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-chart-line fa-lg text-primary"></i>
                <h1>用户分析系统</h1>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> 仪表板</a></li>
                <li><a href="#"><i class="fas fa-users"></i> 用户管理</a></li>
                <li><a href="#"><i class="fas fa-history"></i> 活动日志</a></li>
                <li><a href="#" class="active"><i class="fas fa-file-alt"></i> 文件管理</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> 设置</a></li>
            </ul>
            <div class="sidebar-footer">
                <p>© 2025 用户分析系统</p>
                <p>版本 1.0.0</p>
            </div>
        </aside>
        
        &lt;!-- Main content -->
        <main class="main-content">
            <a href="index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> 返回仪表板
            </a>
            
            <div class="header">
                <h1>文件详情</h1>
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            &lt;!-- File detail -->
            <div class="file-detail">
                <div class="file-info-card">
                    <div class="file-icon">
                        <?php echo $fileIcon; ?>
                    </div>
                    <h2 class="file-name"><?php echo $file['file_name']; ?></h2>
                    <p class="file-meta">
                        上传于 <?php echo $file['upload_date']; ?><br>
                        <?php echo $file['file_size_mb']; ?> MB (<?php echo $file['file_size']; ?> 字节)
                    </p>
                    
                    <div class="file-actions">
                        <a href="<?php echo $file['file_url']; ?>" target="_blank" class="file-action-button">
                            <i class="fas fa-download"></i> 下载文件
                        </a>
                        <a href="user_detail.php?id=<?php echo $file['user_id']; ?>" class="file-action-button secondary">
                            <i class="fas fa-user"></i> 查看上传者
                        </a>
                    </div>
                </div>
                
                <div class="file-preview-card">
                    <h2>文件预览</h2>
                    <div class="file-preview-container">
                        <?php echo $filePreview; ?>
                    </div>
                </div>
            </div>
            
            &lt;!-- File details -->
            <div class="file-details-section">
                <h2 class="section-title">文件详情</h2>
                
                <div class="details-card">
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">文件名</div>
                            <div class="detail-value"><?php echo $file['file_name']; ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">文件类型</div>
                            <div class="detail-value"><?php echo strtoupper($fileExtension); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">文件大小</div>
                            <div class="detail-value">
                                <?php echo $file['file_size_mb']; ?> MB<br>
                                <?php echo $file['file_size_kb']; ?> KB<br>
                                <?php echo $file['file_size']; ?> 字节
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">上传时间</div>
                            <div class="detail-value"><?php echo $file['upload_date']; ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">上传者</div>
                            <div class="detail-value">
                                <a href="user_detail.php?id=<?php echo $file['user_id']; ?>">
                                    <?php echo $file['username']; ?>
                                </a>
                                <br>
                                <?php echo $file['email']; ?>
                                <br>
                                <?php
                                $badgeClass = '';
                                switch ($file['user_type']) {
                                    case 'super':
                                        $badgeClass = 'badge-primary';
                                        $userType = '超级用户';
                                        break;
                                    case 'std':
                                        $badgeClass = 'badge-success';
                                        $userType = '标准用户';
                                        break;
                                    case 'std-beta':
                                        $badgeClass = 'badge-warning';
                                        $userType = '测试用户';
                                        break;
                                    case 'suspended':
                                        $badgeClass = 'badge-danger';
                                        $userType = '已停用';
                                        break;
                                    default:
                                        $badgeClass = 'badge-secondary';
                                        $userType = '未知';
                                }
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $userType; ?></span>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">文件URL</div>
                            <div class="detail-value text-truncate">
                                <a href="<?php echo $file['file_url']; ?>" target="_blank">
                                    <?php echo $file['file_url']; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    &lt;!-- JavaScript -->
    <script>
        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = themeToggle.querySelector('i');
        
        // Check for saved theme preference or use device preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.body.setAttribute('data-theme', 'dark');
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        }
        
        // Toggle theme
        themeToggle.addEventListener('click', () => {
            if (document.body.getAttribute('data-theme') === 'dark') {
                document.body.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                themeIcon.classList.replace('fa-sun', 'fa-moon');
            } else {
                document.body.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeIcon.classList.replace('fa-moon', 'fa-sun');
            }
        });
    </script>
</body>
</html>