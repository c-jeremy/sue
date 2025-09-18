<?php
// Include database configuration
require_once 'config.php';

// Function to get user count
function getUserCount($conn) {
    $sql = "SELECT COUNT(*) as total FROM users";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Function to get active users (users with activity in the last 30 days)
function getActiveUserCount($conn) {
    $sql = "SELECT COUNT(DISTINCT user_id) as active FROM operation_log WHERE time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['active'];
}

// Function to get total file count
function getFileCount($conn) {
    $sql = "SELECT COUNT(*) as total FROM user_files";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Function to get total storage used (in MB)
function getTotalStorage($conn) {
    $sql = "SELECT SUM(file_size) as total FROM user_files";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return round($row['total'] / (1024 * 1024), 2); // Convert bytes to MB
}

// Function to get user type distribution
function getUserTypeDistribution($conn) {
    $sql = "SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type";
    $result = $conn->query($sql);
    $distribution = [];
    while ($row = $result->fetch_assoc()) {
        $distribution[$row['user_type']] = $row['count'];
    }
    return $distribution;
}

// Function to get user registration over time (monthly)
function getUserRegistrationOverTime($conn) {
    $sql = "SELECT DATE_FORMAT(join_date, '%Y-%m') as month, COUNT(*) as count 
            FROM users 
            GROUP BY DATE_FORMAT(join_date, '%Y-%m') 
            ORDER BY month";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['month']] = $row['count'];
    }
    return $data;
}

// Function to get activity by type
function getActivityByType($conn) {
    $sql = "SELECT action_type, COUNT(*) as count FROM operation_log GROUP BY action_type ORDER BY count DESC LIMIT 10";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['action_type']] = $row['count'];
    }
    return $data;
}

// Function to get environment distribution
function getEnvironmentDistribution($conn) {
    $sql = "SELECT environment, COUNT(*) as count FROM operation_log GROUP BY environment";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['environment']] = $row['count'];
    }
    return $data;
}

// Function to get success rate
function getSuccessRate($conn) {
    $sql = "SELECT is_success, COUNT(*) as count FROM operation_log GROUP BY is_success";
    $result = $conn->query($sql);
    $data = [0 => 0, 1 => 0]; // Initialize with zeros for both success and failure
    while ($row = $result->fetch_assoc()) {
        $data[$row['is_success']] = $row['count'];
    }
    $total = $data[0] + $data[1];
    return $total > 0 ? round(($data[1] / $total) * 100, 2) : 0;
}

// Function to get most active users
function getMostActiveUsers($conn, $limit = 5) {
    $sql = "SELECT u.id, u.username, u.email, u.user_type, COUNT(o.id) as activity_count 
            FROM users u 
            JOIN operation_log o ON u.id = o.user_id 
            GROUP BY u.id 
            ORDER BY activity_count DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

// Function to get users with most storage
function getUsersWithMostStorage($conn, $limit = 5) {
    $sql = "SELECT u.id, u.username, u.email, u.user_type, SUM(f.file_size) as total_size, COUNT(f.id) as file_count 
            FROM users u 
            JOIN user_files f ON u.id = f.user_id 
            GROUP BY u.id 
            ORDER BY total_size DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $row['total_size_mb'] = round($row['total_size'] / (1024 * 1024), 2);
        $users[] = $row;
    }
    return $users;
}

// Function to get recent activities
function getRecentActivities($conn, $limit = 10) {
    $sql = "SELECT o.id, o.user_id, u.username, o.time, o.action_type, o.environment, o.is_success 
            FROM operation_log o 
            JOIN users u ON o.user_id = u.id 
            ORDER BY o.time DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    return $activities;
}

// Function to get recent file uploads
function getRecentFileUploads($conn, $limit = 5) {
    $sql = "SELECT f.id, f.user_id, u.username, f.file_name, f.upload_date, f.file_size 
            FROM user_files f 
            JOIN users u ON f.user_id = u.id 
            ORDER BY f.upload_date DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $files = [];
    while ($row = $result->fetch_assoc()) {
        $row['file_size_mb'] = round($row['file_size'] / (1024 * 1024), 2);
        $files[] = $row;
    }
    return $files;
}

// Get data for the dashboard
$userCount = getUserCount($conn);
$activeUserCount = getActiveUserCount($conn);
$fileCount = getFileCount($conn);
$totalStorage = getTotalStorage($conn);
$userTypeDistribution = getUserTypeDistribution($conn);
$userRegistrationOverTime = getUserRegistrationOverTime($conn);
$activityByType = getActivityByType($conn);
$environmentDistribution = getEnvironmentDistribution($conn);
$successRate = getSuccessRate($conn);
$mostActiveUsers = getMostActiveUsers($conn);
$usersWithMostStorage = getUsersWithMostStorage($conn);
$recentActivities = getRecentActivities($conn);
$recentFileUploads = getRecentFileUploads($conn);

// Convert data to JSON for JavaScript
$userTypeDistributionJSON = json_encode($userTypeDistribution);
$userRegistrationOverTimeJSON = json_encode($userRegistrationOverTime);
$activityByTypeJSON = json_encode($activityByType);
$environmentDistributionJSON = json_encode($environmentDistribution);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户分析仪表板</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-1-M/Chart.js/3.7.1/chart.min.js" type="application/javascript"></script>
    <style>
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
        
        /* Dashboard grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-5px);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .card-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
            font-size: 1.25rem;
        }
        
        .card-icon.blue {
            background-color: var(--primary-color);
        }
        
        .card-icon.purple {
            background-color: var(--secondary-color);
        }
        
        .card-icon.cyan {
            background-color: var(--success-color);
        }
        
        .card-icon.pink {
            background-color: var(--warning-color);
        }
        
        .card-value {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .card-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        /* Chart cards */
        .chart-card {
            grid-column: span 2;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }
        
        /* Table styles */
        .table-card {
            grid-column: span 2;
            overflow: hidden;
        }
        
        .table-responsive {
            overflow-x: auto;
            margin-top: 1rem;
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
        
        /* Filter controls */
        .filter-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 200px;
        }
        
        .filter-label {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }
        
        .filter-input {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: var(--font-sans);
            transition: var(--transition);
        }
        
        .filter-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .filter-button {
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius-sm);
            font-family: var(--font-sans);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            align-self: flex-end;
        }
        
        .filter-button:hover {
            background-color: var(--secondary-color);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-card, .table-card {
                grid-column: 1;
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
            
            .filter-controls {
                flex-direction: column;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .dashboard-grid .card:nth-child(1) { animation-delay: 0.1s; }
        .dashboard-grid .card:nth-child(2) { animation-delay: 0.2s; }
        .dashboard-grid .card:nth-child(3) { animation-delay: 0.3s; }
        .dashboard-grid .card:nth-child(4) { animation-delay: 0.4s; }
        .dashboard-grid .card:nth-child(5) { animation-delay: 0.5s; }
        .dashboard-grid .card:nth-child(6) { animation-delay: 0.6s; }
        .dashboard-grid .card:nth-child(7) { animation-delay: 0.7s; }
        .dashboard-grid .card:nth-child(8) { animation-delay: 0.8s; }
        
        /* Utilities */
        .text-success { color: var(--success-color); }
        .text-warning { color: var(--warning-color); }
        .text-danger { color: var(--danger-color); }
        .text-primary { color: var(--primary-color); }
        .text-secondary { color: var(--secondary-color); }
        
        .bg-success { background-color: var(--success-color); }
        .bg-warning { background-color: var(--warning-color); }
        .bg-danger { background-color: var(--danger-color); }
        .bg-primary { background-color: var(--primary-color); }
        .bg-secondary { background-color: var(--secondary-color); }
        
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 1rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        .mb-5 { margin-bottom: 3rem; }
        
        .mt-1 { margin-top: 0.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-3 { margin-top: 1rem; }
        .mt-4 { margin-top: 1.5rem; }
        .mt-5 { margin-top: 3rem; }
        
        .d-flex { display: flex; }
        .align-items-center { align-items: center; }
        .justify-content-between { justify-content: space-between; }
        .flex-column { flex-direction: column; }
        .gap-1 { gap: 0.25rem; }
        .gap-2 { gap: 0.5rem; }
        .gap-3 { gap: 1rem; }
        
        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--gray-500);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-600);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-chart-line fa-lg text-primary"></i>
                <h1>用户分析系统</h1>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> 仪表板</a></li>
                <li><a href="#" onclick="showSection('users')"><i class="fas fa-users"></i> 用户管理</a></li>
                <li><a href="#" onclick="showSection('activities')"><i class="fas fa-history"></i> 活动日志</a></li>
                <li><a href="#" onclick="showSection('files')"><i class="fas fa-file-alt"></i> 文件管理</a></li>
                <li><a href="#" onclick="showSection('settings')"><i class="fas fa-cog"></i> 设置</a></li>
            </ul>
            <div class="sidebar-footer">
                <p>© 2025 用户分析系统</p>
                <p>版本 1.0.0</p>
            </div>
        </aside>
        
        <!-- Main content -->
        <main class="main-content">
            <div class="header">
                <h1>用户分析仪表板</h1>
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <!-- Filter controls -->
            <div class="filter-controls">
                <div class="filter-group">
                    <label class="filter-label">日期范围</label>
                    <input type="date" class="filter-input" id="startDate">
                </div>
                <div class="filter-group">
                    <label class="filter-label">至</label>
                    <input type="date" class="filter-input" id="endDate">
                </div>
                <div class="filter-group">
                    <label class="filter-label">用户类型</label>
                    <select class="filter-input" id="userType">
                        <option value="">全部</option>
                        <option value="super">超级用户</option>
                        <option value="std">标准用户</option>
                        <option value="std-beta">测试用户</option>
                        <option value="suspended">已停用</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">环境</label>
                    <select class="filter-input" id="environment">
                        <option value="">全部</option>
                        <option value="production">生产环境</option>
                        <option value="devtest">开发测试环境</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">搜索用户</label>
                    <input type="text" class="filter-input" id="searchUser" placeholder="用户名或邮箱">
                </div>
                <button class="filter-button" id="applyFilters">应用筛选</button>
            </div>
            
            <!-- Dashboard grid -->
            <div class="dashboard-grid">
                <!-- Key metrics cards -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">总用户数</h2>
                        <div class="card-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $userCount; ?></div>
                    <div class="card-description">
                        <span class="text-success"><i class="fas fa-arrow-up"></i> 12%</span> 较上月增长
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">活跃用户</h2>
                        <div class="card-icon purple">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $activeUserCount; ?></div>
                    <div class="card-description">
                        过去30天内有活动的用户
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">文件总数</h2>
                        <div class="card-icon cyan">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $fileCount; ?></div>
                    <div class="card-description">
                        <span class="text-success"><i class="fas fa-arrow-up"></i> 8%</span> 较上月增长
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">存储使用量</h2>
                        <div class="card-icon pink">
                            <i class="fas fa-database"></i>
                        </div>
                    </div>
                    <div class="card-value"><?php echo $totalStorage; ?> MB</div>
                    <div class="card-description">
                        总存储空间使用量
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="card chart-card">
                    <div class="card-header">
                        <h2 class="card-title">用户注册趋势</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="userRegistrationChart"></canvas>
                    </div>
                </div>
                
                <div class="card chart-card">
                    <div class="card-header">
                        <h2 class="card-title">用户类型分布</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="userTypeChart"></canvas>
                    </div>
                </div>
                
                <div class="card chart-card">
                    <div class="card-header">
                        <h2 class="card-title">活动类型分布</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="activityTypeChart"></canvas>
                    </div>
                </div>
                
                <div class="card chart-card">
                    <div class="card-header">
                        <h2 class="card-title">环境使用分布</h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="environmentChart"></canvas>
                    </div>
                </div>
                
                <!-- Tables -->
                <div class="card table-card">
                    <div class="card-header">
                        <h2 class="card-title">最活跃用户</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>用户名</th>
                                    <th>邮箱</th>
                                    <th>用户类型</th>
                                    <th>活动次数</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mostActiveUsers as $user): ?>
                                <tr>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = '';
                                        switch ($user['user_type']) {
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
                                    </td>
                                    <td><?php echo $user['activity_count']; ?></td>
                                    <td>
                                        <button class="badge badge-primary" onclick="viewUserDetails(<?php echo $user['id']; ?>)">查看详情</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card table-card">
                    <div class="card-header">
                        <h2 class="card-title">存储空间使用最多的用户</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>用户名</th>
                                    <th>邮箱</th>
                                    <th>用户类型</th>
                                    <th>文件数量</th>
                                    <th>存储使用量</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usersWithMostStorage as $user): ?>
                                <tr>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = '';
                                        switch ($user['user_type']) {
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
                                    </td>
                                    <td><?php echo $user['file_count']; ?></td>
                                    <td><?php echo $user['total_size_mb']; ?> MB</td>
                                    <td>
                                        <button class="badge badge-primary" onclick="viewUserFiles(<?php echo $user['id']; ?>)">查看文件</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card table-card">
                    <div class="card-header">
                        <h2 class="card-title">最近活动</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>用户</th>
                                    <th>活动类型</th>
                                    <th>环境</th>
                                    <th>时间</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivities as $activity): ?>
                                <tr>
                                    <td><?php echo $activity['username']; ?></td>
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
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card table-card">
                    <div class="card-header">
                        <h2 class="card-title">最近上传的文件</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>文件名</th>
                                    <th>上传者</th>
                                    <th>上传时间</th>
                                    <th>文件大小</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentFileUploads as $file): ?>
                                <tr>
                                    <td class="text-truncate" style="max-width: 200px;"><?php echo $file['file_name']; ?></td>
                                    <td><?php echo $file['username']; ?></td>
                                    <td><?php echo $file['upload_date']; ?></td>
                                    <td><?php echo $file['file_size_mb']; ?> MB</td>
                                    <td>
                                        <button class="badge badge-primary" onclick="viewFileDetails(<?php echo $file['id']; ?>)">查看详情</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- JavaScript -->
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
        let userRegistrationChart, userTypeChart, activityTypeChart, environmentChart;
        
        function initCharts() {
            const colors = getThemeColors();
            
            // User registration chart
            const userRegistrationCtx = document.getElementById('userRegistrationChart').getContext('2d');
            const userRegistrationData = <?php echo $userRegistrationOverTimeJSON; ?>;
            
            const months = Object.keys(userRegistrationData);
            const registrationCounts = Object.values(userRegistrationData);
            
            userRegistrationChart = new Chart(userRegistrationCtx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: '新注册用户',
                        data: registrationCounts,
                        backgroundColor: 'rgba(67, 97, 238, 0.2)',
                        borderColor: '#4361ee',
                        borderWidth: 2,
                        pointBackgroundColor: '#4361ee',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: colors.textColor
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: colors.gridColor
                            },
                            ticks: {
                                color: colors.textColor
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
            
            // User type chart
            const userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
            const userTypeData = <?php echo $userTypeDistributionJSON; ?>;
            
            const userTypes = Object.keys(userTypeData).map(type => {
                switch(type) {
                    case 'super': return '超级用户';
                    case 'std': return '标准用户';
                    case 'std-beta': return '测试用户';
                    case 'suspended': return '已停用';
                    default: return type;
                }
            });
            
            const userTypeCounts = Object.values(userTypeData);
            
            userTypeChart = new Chart(userTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: userTypes,
                    datasets: [{
                        data: userTypeCounts,
                        backgroundColor: [
                            '#4361ee',
                            '#4cc9f0',
                            '#f72585',
                            '#ff5a5f',
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
                            position: 'right',
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
            const environmentData = <?php echo $environmentDistributionJSON; ?>;
            
            const environments = Object.keys(environmentData).map(env => env === 'production' ? '生产环境' : '开发测试环境');
            const environmentCounts = Object.values(environmentData);
            
            environmentChart = new Chart(environmentCtx, {
                type: 'pie',
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
                    }
                }
            });
        }
        
        // Update charts when theme changes
        function updateChartsTheme() {
            const colors = getThemeColors();
            
            // Update user registration chart
            userRegistrationChart.options.scales.x.grid.color = colors.gridColor;
            userRegistrationChart.options.scales.x.ticks.color = colors.textColor;
            userRegistrationChart.options.scales.y.grid.color = colors.gridColor;
            userRegistrationChart.options.scales.y.ticks.color = colors.textColor;
            userRegistrationChart.options.plugins.legend.labels.color = colors.textColor;
            userRegistrationChart.update();
            
            // Update user type chart
            userTypeChart.options.plugins.legend.labels.color = colors.textColor;
            userTypeChart.data.datasets[0].borderColor = colors.backgroundColor;
            userTypeChart.update();
            
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
        
        // Filter functionality
        document.getElementById('applyFilters').addEventListener('click', function() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const userType = document.getElementById('userType').value;
            const environment = document.getElementById('environment').value;
            const searchUser = document.getElementById('searchUser').value;
            
            // Here you would typically make an AJAX request to filter the data
            // For demonstration, we'll just log the filter values
            console.log('Applying filters:', {
                startDate,
                endDate,
                userType,
                environment,
                searchUser
            });
            
            alert('筛选功能将在完整版中实现。');
        });
        
        // View user details
        function viewUserDetails(userId) {
            // Here you would typically make an AJAX request to get user details
            console.log('Viewing user details for ID:', userId);
            alert('用户详情功能将在完整版中实现。');
        }
        
        // View user files
        function viewUserFiles(userId) {
            // Here you would typically make an AJAX request to get user files
            console.log('Viewing files for user ID:', userId);
            alert('用户文件功能将在完整版中实现。');
        }
        
        // View file details
        function viewFileDetails(fileId) {
            // Here you would typically make an AJAX request to get file details
            console.log('Viewing file details for ID:', fileId);
            alert('文件详情功能将在完整版中实现。');
        }
        
        // Show different sections
        function showSection(sectionName) {
            console.log('Showing section:', sectionName);
            alert(`${sectionName} 功能将在完整版中实现。`);
        }
    </script>
</body>
</html>