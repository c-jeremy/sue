<?php
// user_detail.php - View detailed information about a user
require_once 'config.php';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$userId = intval($_GET['id']);

// Get user information
function getUserInfo($conn, $userId) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

// Get user activity
function getUserActivity($conn, $userId, $limit = 20) {
    $sql = "SELECT * FROM operation_log WHERE user_id = ? ORDER BY time DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    return $activities;
}

// Get user files
function getUserFiles($conn, $userId, $limit = 20) {
    $sql = "SELECT * FROM user_files WHERE user_id = ? ORDER BY upload_date DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $files = [];
    while ($row = $result->fetch_assoc()) {
        $row['file_size_mb'] = round($row['file_size'] / (1024 * 1024), 2);
        $files[] = $row;
    }
    
    return $files;
}

// Get user activity statistics
function getUserActivityStats($conn, $userId) {
    // Get activity count by type
    $sql = "SELECT action_type, COUNT(*) as count FROM operation_log WHERE user_id = ? GROUP BY action_type ORDER BY count DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activityByType = [];
    while ($row = $result->fetch_assoc()) {
        $activityByType[$row['action_type']] = $row['count'];
    }
    
    // Get activity count by environment
    $sql = "SELECT environment, COUNT(*) as count FROM operation_log WHERE user_id = ? GROUP BY environment";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activityByEnvironment = [];
    while ($row = $result->fetch_assoc()) {
        $activityByEnvironment[$row['environment']] = $row['count'];
    }
    
    // Get success rate
    $sql = "SELECT is_success, COUNT(*) as count FROM operation_log WHERE user_id = ? GROUP BY is_success";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $successRate = [0 => 0, 1 => 0];
    while ($row = $result->fetch_assoc()) {
        $successRate[$row['is_success']] = $row['count'];
    }
    
    $totalActivities = $successRate[0] + $successRate[1];
    $successRatePercent = $totalActivities > 0 ? round(($successRate[1] / $totalActivities) * 100, 2) : 0;
    
    return [
        'activityByType' => $activityByType,
        'activityByEnvironment' => $activityByEnvironment,
        'successRate' => $successRatePercent,
        'totalActivities' => $totalActivities
    ];
}

// Get user file statistics
function getUserFileStats($conn, $userId) {
    // Get total file size
    $sql = "SELECT SUM(file_size) as total_size FROM user_files WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalSize = $row['total_size'] ? round($row['total_size'] / (1024 * 1024), 2) : 0;
    
    // Get file count
    $sql = "SELECT COUNT(*) as count FROM user_files WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $fileCount = $row['count'];
    
    return [
        'totalSize' => $totalSize,
        'fileCount' => $fileCount
    ];
}

// Get data
$user = getUserInfo($conn, $userId);

if (!$user) {
    header('Location: index.php');
    exit;
}

$activities = getUserActivity($conn, $userId);
$files = getUserFiles($conn, $userId);
$activityStats = getUserActivityStats($conn, $userId);
$fileStats = getUserFileStats($conn, $userId);

// Convert data to JSON for JavaScript
$activityByTypeJSON = json_encode($activityStats['activityByType']);
$activityByEnvironmentJSON = json_encode($activityStats['activityByEnvironment']);
?>

&lt;!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户详情 - <?php echo $user['username']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-1-M/Chart.js/3.7.1/chart.min.js" type="application/javascript"></script>
    <style>
        /* Same CSS as index.php */
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
        
        /* User profile section */
        .user-profile {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .profile-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            text-align: center;
            transition: var(--transition);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1.5rem;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .profile-email {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        
        .profile-type {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .profile-type.super {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .profile-type.std {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }
        
        .profile-type.std-beta {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--warning-color);
        }
        
        .profile-type.suspended {
            background-color: rgba(255, 90, 95, 0.1);
            color: var(--danger-color);
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .stat-item {
            padding: 1rem;
            background-color: var(--bg-secondary);
            border-radius: var(--border-radius);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .user-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .detail-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: var(--transition);
        }
        
        .detail-card h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .detail-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .detail-value {
            text-align: right;
        }
        
        /* Activity and files sections */
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: var(--transition);
        }
        
        .chart-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }
        
        .chart-container {
            position: relative;
            height: 250px;
        }
        
        .table-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        
        .table-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            font-weight: 600;
            color: var(--text-secondary);
            background-color: var(--bg-secondary);
        }
        
        tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
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
        
        /* Responsive */
        @media (max-width: 992px) {
            .user-profile {
                grid-template-columns: 1fr;
            }
            
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .user-details {
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
                <li><a href="#" class="active"><i class="fas fa-users"></i> 用户管理</a></li>
                <li><a href="#"><i class="fas fa-history"></i> 活动日志</a></li>
                <li><a href="#"><i class="fas fa-file-alt"></i> 文件管理</a></li>
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
                <h1>用户详情</h1>
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            &lt;!-- User profile -->
            <div class="user-profile">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <h2 class="profile-name"><?php echo $user['username']; ?></h2>
                    <p class="profile-email"><?php echo $user['email']; ?></p>
                    
                    <?php
                    $typeClass = '';
                    $typeLabel = '';
                    switch ($user['user_type']) {
                        case 'super':
                            $typeClass = 'super';
                            $typeLabel = '超级用户';
                            break;
                        case 'std':
                            $typeClass = 'std';
                            $typeLabel = '标准用户';
                            break;
                        case 'std-beta':
                            $typeClass = 'std-beta';
                            $typeLabel = '测试用户';
                            break;
                        case 'suspended':
                            $typeClass = 'suspended';
                            $typeLabel = '已停用';
                            break;
                        default:
                            $typeClass = '';
                            $typeLabel = '未知';
                    }
                    ?>
                    
                    <div class="profile-type <?php echo $typeClass; ?>">
                        <?php echo $typeLabel; ?>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $activityStats['totalActivities']; ?></div>
                            <div class="stat-label">总活动次数</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $fileStats['fileCount']; ?></div>
                            <div class="stat-label">文件数量</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $activityStats['successRate']; ?>%</div>
                            <div class="stat-label">成功率</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $fileStats['totalSize']; ?> MB</div>
                            <div class="stat-label">存储使用量</div>
                        </div>
                    </div>
                </div>
                
                <div class="user-details">
                    <div class="detail-card">
                        <h2>基本信息</h2>
                        <div class="detail-item">
                            <div class="detail-label">用户ID</div>
                            <div class="detail-value"><?php echo $user['id']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">希悦UID</div>
                            <div class="detail-value"><?php echo $user['seiue_uid']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">注册时间</div>
                            <div class="detail-value"><?php echo $user['join_date']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">会话ID</div>
                            <div class="detail-value text-truncate" style="max-width: 150px;"><?php echo $user['seiue_sessid'] ?: '无'; ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <h2>活动统计</h2>
                        <div class="detail-item">
                            <div class="detail-label">最近活动时间</div>
                            <div class="detail-value"><?php echo !empty($activities) ? $activities[0]['time'] : '无活动记录'; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">生产环境活动</div>
                            <div class="detail-value"><?php echo isset($activityStats['activityByEnvironment']['production']) ? $activityStats['activityByEnvironment']['production'] : 0; ?> 次</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">开发测试环境活动</div>
                            <div class="detail-value"><?php echo isset($activityStats['activityByEnvironment']['devtest']) ? $activityStats['activityByEnvironment']['devtest'] : 0; ?> 次</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">最常用活动</div>
                            <div class="detail-value">
                                <?php
                                $mostCommonActivity = !empty($activityStats['activityByType']) ? array_key_first($activityStats['activityByType']) : '无';
                                echo $mostCommonActivity;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            &lt;!-- Activity charts -->
            <h2 class="section-title">活动分析</h2>
            
            <div class="chart-grid">
                <div class="chart-card">
                    <h3>活动类型分布</h3>
                    <div class="chart-container">
                        <canvas id="activityTypeChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3>环境使用分布</h3>
                    <div class="chart-container">
                        <canvas id="environmentChart"></canvas>
                    </div>
                </div>
            </div>
            
            &lt;!-- Recent activities -->
            <div class="table-card">
                <h3>最近活动</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>活动类型</th>
                                <th>环境</th>
                                <th>时间</th>
                                <th>状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activities)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">无活动记录</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td><?php echo $activity['action_type']; ?></td>
                                <td>
                                    <span class="badge <?php echo $activity['environment'] === 'production' ? 'badge-primary' : 'badge-secondary'; ?>">
                                        <?php echo $activity['environment'] === 'production' ? '生产环境' : '开发测试环境'; ?>
                                    </span>
                                </td>
                                <td><?php echo $activity['time']; ?></td>
                                <td>
                                    <span class="badge <?php echo $activity['is_success'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $activity['is_success'] ? '成功' : '失败'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            &lt;!-- User files -->
            <h2 class="section-title">文件管理</h2>
            
            <div class="table-card">
                <h3>用户文件</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>文件名</th>
                                <th>上传时间</th>
                                <th>文件大小</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($files)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">无文件记录</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td class="text-truncate" style="max-width: 300px;"><?php echo $file['file_name']; ?></td>
                                <td><?php echo $file['upload_date']; ?></td>
                                <td><?php echo $file['file_size_mb']; ?> MB</td>
                                <td>
                                    <a href="<?php echo $file['file_url']; ?>" target="_blank" class="badge badge-primary">查看文件</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
            
            // Update charts with new theme
            updateChartsTheme();
        });
        
        // Chart.js global defaults
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 6;
        Chart.defaults.plugins.tooltip.titleFont.weight = 'bold';
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        
        // Function to get current theme colors
        function getThemeColors() {
            const isDark = document.body.getAttribute('data-theme') === 'dark';
            return {
                textColor: isDark ? '#f8f9fa' : '#212529',
                gridColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                backgroundColor: isDark ? '#1e1e1e' : '#ffffff'
            };
        }
        
        // Initialize charts
        let activityTypeChart, environmentChart;
        
        function initCharts() {
            const colors = getThemeColors();
            
            // Activity type chart
            const activityTypeCtx = document.getElementById('activityTypeChart').getContext('2d');
            const activityTypeData = <?php echo $activityByTypeJSON; ?>;
            
            const activityTypes = Object.keys(activityTypeData);
            const activityCounts = Object.values(activityTypeData);
            
            activityTypeChart = new Chart(activityTypeCtx, {
                type: 'bar',
                data: {
                    labels: activityTypes,
                    datasets: [{
                        label: '活动次数',
                        data: activityCounts,
                        backgroundColor: 'rgba(76, 201, 240, 0.7)',
                        borderColor: '#4cc9f0',
                        borderWidth: 1,
                        borderRadius: 4,
                        maxBarThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                color: colors.gridColor
                            },
                            ticks: {
                                color: colors.textColor,
                                maxRotation: 45,
                                minRotation: 45
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: colors.gridColor
                            },
                            ticks: {
                                color: colors.textColor
                            }
                        }
                    }
                }
            });
            
            // Environment chart
            const environmentCtx = document.getElementById('environmentChart').getContext('2d');
            const environmentData = <?php echo $activityByEnvironmentJSON; ?>;
            
            const environments = Object.keys(environmentData).map(env => env === 'production' ? '生产环境' : '开发测试环境');
            const environmentCounts = Object.values(environmentData);
            
            environmentChart = new Chart(environmentCtx, {
                type: 'doughnut',
                data: {
                    labels: environments,
                    datasets: [{
                        data: environmentCounts,
                        backgroundColor: [
                            '#4361ee',
                            '#3a0ca3'
                        ],
                        borderWidth: 2,
                        borderColor: colors.backgroundColor
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: colors.textColor,
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
        
        // Update charts when theme changes
        function updateChartsTheme() {
            const colors = getThemeColors();
            
            // Update activity type chart
            activityTypeChart.options.scales.x.grid.color = colors.gridColor;
            activityTypeChart.options.scales.x.ticks.color = colors.textColor;
            activityTypeChart.options.scales.y.grid.color = colors.gridColor;
            activityTypeChart.options.scales.y.ticks.color = colors.textColor;
            activityTypeChart.update();
            
            // Update environment chart
            environmentChart.options.plugins.legend.labels.color = colors.textColor;
            environmentChart.data.datasets[0].borderColor = colors.backgroundColor;
            environmentChart.update();
        }
        
        // Initialize charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', initCharts);
    </script>
</body>
</html>