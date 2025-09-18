<?php
// api.php - API endpoints for the dashboard
header('Content-Type: application/json');
require_once 'config.php';

// Check if action is provided
if (!isset($_GET['action'])) {
    echo json_encode(['error' => 'No action specified']);
    exit;
}

$action = $_GET['action'];

// Get filtered users
if ($action === 'getFilteredUsers') {
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
    $userType = isset($_GET['userType']) ? $_GET['userType'] : null;
    $searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : null;
    
    $sql = "SELECT * FROM users WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($startDate) {
        $sql .= " AND join_date >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate) {
        $sql .= " AND join_date &lt;= ?";
        $params[] = $endDate . " 23:59:59";
        $types .= "s";
    }
    
    if ($userType) {
        $sql .= " AND user_type = ?";
        $params[] = $userType;
        $types .= "s";
    }
    
    if ($searchTerm) {
        $sql .= " AND (username LIKE ? OR email LIKE ?)";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $types .= "ss";
    }
    
    $sql .= " ORDER BY id DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        // Remove sensitive information
        unset($row['password']);
        unset($row['seiue_sessid']);
        $users[] = $row;
    }
    
    echo json_encode(['users' => $users]);
    exit;
}

// Get filtered activities
if ($action === 'getFilteredActivities') {
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
    $userId = isset($_GET['userId']) ? intval($_GET['userId']) : null;
    $environment = isset($_GET['environment']) ? $_GET['environment'] : null;
    $actionType = isset($_GET['actionType']) ? $_GET['actionType'] : null;
    
    $sql = "SELECT o.*, u.username FROM operation_log o JOIN users u ON o.user_id = u.id WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($startDate) {
        $sql .= " AND o.time >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate) {
        $sql .= " AND o.time &lt;= ?";
        $params[] = $endDate . " 23:59:59";
        $types .= "s";
    }
    
    if ($userId) {
        $sql .= " AND o.user_id = ?";
        $params[] = $userId;
        $types .= "i";
    }
    
    if ($environment) {
        $sql .= " AND o.environment = ?";
        $params[] = $environment;
        $types .= "s";
    }
    
    if ($actionType) {
        $sql .= " AND o.action_type = ?";
        $params[] = $actionType;
        $types .= "s";
    }
    
    $sql .= " ORDER BY o.time DESC LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    echo json_encode(['activities' => $activities]);
    exit;
}

// Get filtered files
if ($action === 'getFilteredFiles') {
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
    $userId = isset($_GET['userId']) ? intval($_GET['userId']) : null;
    $searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : null;
    
    $sql = "SELECT f.*, u.username FROM user_files f JOIN users u ON f.user_id = u.id WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($startDate) {
        $sql .= " AND f.upload_date >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate) {
        $sql .= " AND f.upload_date &lt;= ?";
        $params[] = $endDate . " 23:59:59";
        $types .= "s";
    }
    
    if ($userId) {
        $sql .= " AND f.user_id = ?";
        $params[] = $userId;
        $types .= "i";
    }
    
    if ($searchTerm) {
        $sql .= " AND f.file_name LIKE ?";
        $params[] = "%$searchTerm%";
        $types .= "s";
    }
    
    $sql .= " ORDER BY f.upload_date DESC LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $files = [];
    while ($row = $result->fetch_assoc()) {
        $row['file_size_mb'] = round($row['file_size'] / (1024 * 1024), 2);
        $files[] = $row;
    }
    
    echo json_encode(['files' => $files]);
    exit;
}

// Get user activity stats
if ($action === 'getUserActivityStats') {
    $userId = isset($_GET['userId']) ? intval($_GET['userId']) : null;
    
    if (!$userId) {
        echo json_encode(['error' => 'No user ID provided']);
        exit;
    }
    
    // Get activity count by day for the last 30 days
    $sql = "SELECT DATE(time) as date, COUNT(*) as count 
            FROM operation_log 
            WHERE user_id = ? AND time >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
            GROUP BY DATE(time) 
            ORDER BY date";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activityByDay = [];
    while ($row = $result->fetch_assoc()) {
        $activityByDay[$row['date']] = $row['count'];
    }
    
    // Get activity count by type
    $sql = "SELECT action_type, COUNT(*) as count 
            FROM operation_log 
            WHERE user_id = ? 
            GROUP BY action_type 
            ORDER BY count DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activityByType = [];
    while ($row = $result->fetch_assoc()) {
        $activityByType[$row['action_type']] = $row['count'];
    }
    
    // Get activity count by environment
    $sql = "SELECT environment, COUNT(*) as count 
            FROM operation_log 
            WHERE user_id = ? 
            GROUP BY environment";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activityByEnvironment = [];
    while ($row = $result->fetch_assoc()) {
        $activityByEnvironment[$row['environment']] = $row['count'];
    }
    
    echo json_encode([
        'activityByDay' => $activityByDay,
        'activityByType' => $activityByType,
        'activityByEnvironment' => $activityByEnvironment
    ]);
    exit;
}

// Get dashboard stats
if ($action === 'getDashboardStats') {
    // Get user count
    $sql = "SELECT COUNT(*) as total FROM users";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $userCount = $row['total'];
    
    // Get active users (users with activity in the last 30 days)
    $sql = "SELECT COUNT(DISTINCT user_id) as active FROM operation_log WHERE time >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $activeUserCount = $row['active'];
    
    // Get file count
    $sql = "SELECT COUNT(*) as total FROM user_files";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $fileCount = $row['total'];
    
    // Get total storage
    $sql = "SELECT SUM(file_size) as total FROM user_files";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $totalStorage = round($row['total'] / (1024 * 1024), 2); // Convert to MB
    
    // Get user type distribution
    $sql = "SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type";
    $result = $conn->query($sql);
    $userTypeDistribution = [];
    while ($row = $result->fetch_assoc()) {
        $userTypeDistribution[$row['user_type']] = $row['count'];
    }
    
    // Get activity by environment
    $sql = "SELECT environment, COUNT(*) as count FROM operation_log GROUP BY environment";
    $result = $conn->query($sql);
    $environmentDistribution = [];
    while ($row = $result->fetch_assoc()) {
        $environmentDistribution[$row['environment']] = $row['count'];
    }
    
    // Get success rate
    $sql = "SELECT is_success, COUNT(*) as count FROM operation_log GROUP BY is_success";
    $result = $conn->query($sql);
    $successRate = [0 => 0, 1 => 0];
    while ($row = $result->fetch_assoc()) {
        $successRate[$row['is_success']] = $row['count'];
    }
    $total = $successRate[0] + $successRate[1];
    $successRatePercent = $total > 0 ? round(($successRate[1] / $total) * 100, 2) : 0;
    
    echo json_encode([
        'userCount' => $userCount,
        'activeUserCount' => $activeUserCount,
        'fileCount' => $fileCount,
        'totalStorage' => $totalStorage,
        'userTypeDistribution' => $userTypeDistribution,
        'environmentDistribution' => $environmentDistribution,
        'successRate' => $successRatePercent
    ]);
    exit;
}

// If no valid action is provided
echo json_encode(['error' => 'Invalid action']);
exit;