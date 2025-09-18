<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header("Location: ./login.php");
    exit();
}
$include_src = "cloud-demo";
require "./create_conn.php";
require_once "./logger.php";
__($_SESSION["user_id"], "View Cloud", $_ENV["ENV"], 1);

$conn = create_conn();

function truncateString($string, $length = 10) {
    if (mb_strlen($string, 'UTF-8') > $length) {
        return mb_substr($string, 0, $length, 'UTF-8') . '...';
    } else {
        return $string;
    }
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB','EB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $user_id = $_SESSION['user_id'];

    if ($action === 'delete' && isset($_GET['id'])) {
        $file_id = $_GET['id'];
        $delete_sql = "DELETE FROM user_files WHERE id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $file_id, $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'get_files') {
        $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'upload_date';
        $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        $sql = "SELECT id, file_name, file_url, upload_date, file_size FROM user_files WHERE user_id = ?";
        
        if (!empty($search)) {
            $sql .= " AND file_name LIKE ?";
            $search_param = "%$search%";
        }
        
        $sql .= " ORDER BY $sort_by $order";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($search)) {
            $stmt->bind_param("is", $user_id, $search_param);
        } else {
            $stmt->bind_param("i", $user_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();

        $files = [];
        while ($row = $result->fetch_assoc()) {
            $files[] = $row;
        }

        $stmt->close();
        echo json_encode($files);
        exit;
    } elseif ($action === 'get_stats') {
        // New endpoint to get overall stats regardless of search
        $stats_sql = "SELECT COUNT(*) as total_files, COALESCE(SUM(file_size), 0) as total_size, MAX(upload_date) as last_upload FROM user_files WHERE user_id = ?";
        $stats_stmt = $conn->prepare($stats_sql);
        $stats_stmt->bind_param("i", $user_id);
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result();
        $stats = $stats_result->fetch_assoc();
        $stats_stmt->close();
        
        echo json_encode($stats);
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./favicon.png" type="image/png">
    <title>Cloud Storage - Sue</title>
    <script src="./twind.js"></script>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link href="https://lf9-cdn-tos.bytecdntp.com/cdn/expire-1-M/font-awesome/6.0.0/css/all.min.css" type="text/css" rel="stylesheet" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            /* Core colors */
            --primary: #8b5cf6;
            --primary-light: #a78bfa;
            --primary-dark: #7c3aed;
            --primary-gradient: linear-gradient(135deg, #8b5cf6, #7c3aed);
            --primary-glow: rgba(139, 92, 246, 0.5);
            
            --secondary: #ec4899;
            --secondary-light: #f472b6;
            --secondary-dark: #db2777;
            --secondary-gradient: linear-gradient(135deg, #ec4899, #db2777);
            
            --success: #10b981;
            --success-light: #34d399;
            --success-dark: #059669;
            --success-gradient: linear-gradient(135deg, #10b981, #059669);
            
            --danger: #ef4444;
            --danger-light: #f87171;
            --danger-dark: #dc2626;
            --danger-gradient: linear-gradient(135deg, #ef4444, #dc2626);
            
            --warning: #f59e0b;
            --warning-light: #fbbf24;
            --warning-dark: #d97706;
            --warning-gradient: linear-gradient(135deg, #f59e0b, #d97706);
            
            --info: #3b82f6;
            --info-light: #60a5fa;
            --info-dark: #2563eb;
            --info-gradient: linear-gradient(135deg, #3b82f6, #2563eb);
            
            /* UI colors */
            --background: #f8fafc;
            --foreground: #0f172a;
            --card: #ffffff;
            --card-foreground: #1e293b;
            --card-hover: #f1f5f9;
            --border: #e2e8f0;
            --input: #e2e8f0;
            --ring: rgba(139, 92, 246, 0.3);
            
            /* Dark mode colors */
            --dark-background: #0f172a;
            --dark-foreground: #f8fafc;
            --dark-card: #1e293b;
            --dark-card-foreground: #e2e8f0;
            --dark-card-hover: #334155;
            --dark-border: #334155;
            --dark-input: #334155;
            --dark-ring: rgba(148, 163, 184, 0.3);
            
            /* Dimensions */
            --radius: 0.75rem;
            --radius-sm: 0.375rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
            --radius-full: 9999px;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
            
            /* Glassmorphism */
            --glass-background: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.18);
            --glass-highlight: rgba(255, 255, 255, 0.25);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
            
            /* Dark Glassmorphism */
            --dark-glass-background: rgba(15, 23, 42, 0.7);
            --dark-glass-border: rgba(30, 41, 59, 0.18);
            --dark-glass-highlight: rgba(30, 41, 59, 0.25);
            
            /* Timing */
            --transition-fast: 150ms;
            --transition-normal: 250ms;
            --transition-slow: 350ms;
            --transition-very-slow: 500ms;
            
            /* Easing */
            --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-in: cubic-bezier(0.4, 0, 1, 1);
            --ease-out: cubic-bezier(0, 0, 0.2, 1);
            --ease-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
        }

/* Add these styles to enhance the dark mode appearance */
.dark {
    --background: var(--dark-background);
    --foreground: var(--dark-foreground);
    --card: var(--dark-card);
    --card-foreground: var(--dark-card-foreground);
    --card-hover: var(--dark-card-hover);
    --border: var(--dark-border);
    --input: var(--dark-input);
    --ring: var(--dark-ring);
}

.dark body {
    background-image: 
        radial-gradient(circle at 10% 10%, rgba(139, 92, 246, 0.08) 0%, transparent 30%),
        radial-gradient(circle at 90% 90%, rgba(236, 72, 153, 0.08) 0%, transparent 30%),
        linear-gradient(to right, rgba(30, 41, 59, 0.5) 0%, rgba(15, 23, 42, 0.5) 100%);
}

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Update the body background styles to be more elegant */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: var(--background);
            color: var(--foreground);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(139, 92, 246, 0.05) 0%, transparent 30%),
                radial-gradient(circle at 90% 90%, rgba(236, 72, 153, 0.05) 0%, transparent 30%),
                linear-gradient(to right, rgba(139, 92, 246, 0.01) 0%, rgba(236, 72, 153, 0.01) 100%);
            background-attachment: fixed;
            transition: background-color var(--transition-normal) var(--ease-out),
                        color var(--transition-normal) var(--ease-out);
            position: relative;
        }

        /* Add animated background elements */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%239C92AC' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
    opacity: 0.5;
    z-index: -1;
}

.dark body::before {
    opacity: 0.2;
}

        /* ===== Animations ===== */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes breathe {
            0%, 100% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0); }
            50% { box-shadow: 0 0 15px 5px rgba(139, 92, 246, 0.3); }
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes ripple {
            0% { transform: scale(0); opacity: 1; }
            100% { transform: scale(2.5); opacity: 0; }
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        @keyframes fadeInBlur {
            from { opacity: 0; filter: blur(10px); }
            to { opacity: 1; filter: blur(0); }
        }

        @keyframes fadeOutBlur {
            from { opacity: 1; filter: blur(0); }
            to { opacity: 0; filter: blur(10px); }
        }

        @keyframes rotate3d {
            0% { transform: perspective(1000px) rotateY(0deg); }
            100% { transform: perspective(1000px) rotateY(360deg); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* New animations for enhanced UI */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 0.7; transform: translateY(0); }
        }

        @keyframes downloadPulse {
            0% { opacity: 0.3; transform: scale(0.95); }
            50% { opacity: 0.2; }
            100% { opacity: 0; transform: scale(1.05); }
        }

        /* ===== Utility Classes ===== */
        .fade-in {
            animation: fadeIn 0.5s var(--ease-out) forwards;
        }

        .fade-in-scale {
            animation: fadeInScale 0.4s var(--ease-bounce) forwards;
        }

        .slide-in-right {
            animation: slideInRight 0.4s var(--ease-out) forwards;
        }

        .slide-in-left {
            animation: slideInLeft 0.4s var(--ease-out) forwards;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        .shimmer {
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        .float {
            animation: float 6s ease-in-out infinite;
        }

        .breathe {
            animation: breathe 3s ease-in-out infinite;
        }

        .gradient-shift {
            background-size: 200% 200%;
            animation: gradientShift 5s ease infinite;
        }

        .bounce {
            animation: bounce 2s ease-in-out infinite;
        }

        /* ===== Glassmorphism ===== */
        .glass {
            background: var(--glass-background);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
        }

        .dark .glass {
            background: var(--dark-glass-background);
            border: 1px solid var(--dark-glass-border);
        }

/* Enhanced card appearance */
.card {
    transition: all var(--transition-normal) var(--ease-out);
    position: relative;
    overflow: hidden;
    background-color: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    will-change: transform, box-shadow;
    border: 1px solid var(--border);
    transform-style: preserve-3d;
    perspective: 1000px;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(139, 92, 246, 0.3);
}

/* Add a subtle glow effect to cards on hover in dark mode */
.dark .card:hover {
    box-shadow: 
        0 10px 15px -3px rgba(0, 0, 0, 0.3), 
        0 4px 6px -2px rgba(0, 0, 0, 0.15),
        0 0 0 1px rgba(139, 92, 246, 0.3),
        0 0 15px 2px rgba(139, 92, 246, 0.15);
}

/* Add a subtle animation for the download hint */
.file-item .absolute {
    animation: fadeInUp 0.5s ease-out forwards;
    animation-delay: 0.3s;
    opacity: 0;
}

/* Add a ripple animation for double-click feedback */
@keyframes downloadPulse {
    0% { opacity: 0.3; transform: scale(0.95); }
    50% { opacity: 0.2; }
    100% { opacity: 0; transform: scale(1.05); }
}

/* Enhance file icon transitions */
.file-icon {
    transition: all 0.3s var(--ease-bounce);
}

.file-item:hover .file-icon {
    transform: scale(1.1) translateZ(10px);
}

/* Add a subtle background pattern to the card on hover */
.file-item:hover::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: radial-gradient(circle at var(--x) var(--y), rgba(139, 92, 246, 0.1) 0%, transparent 60%);
    pointer-events: none;
    z-index: 1;
}

        /* ===== Button Styles ===== */
        .btn {
            position: relative;
            overflow: hidden;
            transition: all var(--transition-normal) var(--ease-out);
            z-index: 1;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            padding: 0.625rem 1.25rem;
            cursor: pointer;
            user-select: none;
            will-change: transform, box-shadow;
            letter-spacing: 0.01em;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
            z-index: -1;
        }

        .btn:hover::after {
            width: 300%;
            height: 300%;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }

        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
        }

        .btn-secondary {
            background-color: var(--card);
            color: var(--card-foreground);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .btn-secondary:hover {
            background-color: var(--card-hover);
            box-shadow: var(--shadow);
        }

        .btn-danger {
            background: var(--danger-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }

        .btn-icon {
            width: 2.5rem;
            height: 2.5rem;
            padding: 0;
            border-radius: var(--radius-full);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon-sm {
            width: 2rem;
            height: 2rem;
            font-size: 0.875rem;
        }

        /* Ripple effect */
        .ripple {
            position: relative;
            overflow: hidden;
        }

        .ripple::after {
            content: "";
            display: block;
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            background-image: radial-gradient(circle, #fff 10%, transparent 10.01%);
            background-repeat: no-repeat;
            background-position: 50%;
            transform: scale(10, 10);
            opacity: 0;
            transition: transform .5s, opacity 1s;
        }

        .ripple:active::after {
            transform: scale(0, 0);
            opacity: .3;
            transition: 0s;
        }

        /* ===== Loading Spinner ===== */
        .loading-spinner {
            width: 2.5rem;
            height: 2.5rem;
            border: 3px solid rgba(139, 92, 246, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        .loading-dots {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .loading-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background-color: var(--primary);
            animation: pulse 1.5s var(--ease-in-out) infinite;
        }

        .loading-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .loading-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        /* ===== File Type Colors ===== */
        .file-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            margin-right: 12px;
            transition: all var(--transition-normal);
        }

        .file-image { background-color: rgba(168, 85, 247, 0.1); color: #a855f7; }
        .file-document { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .file-spreadsheet { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
        .file-presentation { background-color: rgba(249, 115, 22, 0.1); color: #f97316; }
        .file-archive { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .file-code { background-color: rgba(236, 72, 153, 0.1); color: #ec4899; }
        .file-audio { background-color: rgba(14, 165, 233, 0.1); color: #0ea5e9; }
        .file-video { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .file-default { background-color: rgba(30, 41, 59, 0.1); color: #1e293b; }

        /* ===== Custom Scrollbar ===== */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(241, 245, 249, 0.8);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.5);
            border-radius: 4px;
            transition: background var(--transition-normal);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(100, 116, 139, 0.7);
        }

        .dark ::-webkit-scrollbar-track {
            background: rgba(30, 41, 59, 0.8);
        }

        .dark ::-webkit-scrollbar-thumb {
            background: rgba(100, 116, 139, 0.5);
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.7);
        }

        /* ===== Toast Notification ===== */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .toast {
            padding: 16px 20px;
            border-radius: var(--radius);
            background: var(--card);
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateY(100px);
            opacity: 0;
            transition: all var(--transition-normal) var(--ease-bounce);
            pointer-events: auto;
            max-width: 400px;
            border-left: 4px solid transparent;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast-success { border-color: var(--success); }
        .toast-error { border-color: var(--danger); }
        .toast-info { border-color: var(--info); }
        .toast-warning { border-color: var(--warning); }

        /* ===== Tooltip ===== */
        .tooltip {
            position: relative;
        }

        .tooltip::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(-5px);
            padding: 6px 10px;
            background: var(--foreground);
            color: white;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-fast);
            pointer-events: none;
            z-index: 10;
            box-shadow: var(--shadow-md);
        }

        .tooltip::after {
            content: '';
            position: absolute;
            bottom: calc(100% - 5px);
            left: 50%;
            transform: translateX(-50%) translateY(-5px);
            border-width: 5px;
            border-style: solid;
            border-color: var(--foreground) transparent transparent transparent;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-fast);
            pointer-events: none;
            z-index: 10;
        }

        .tooltip:hover::before,
        .tooltip:hover::after {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        /* ===== Dropdown Menu ===== */
        .dropdown {
            position: relative;
            z-index: 50;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 5px);
            right: 0;
            min-width: 220px;
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow-xl);
            padding: 8px 0;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px) scale(0.98);
            transition: all var(--transition-normal) var(--ease-bounce);
            transform-origin: top right;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            color: var(--card-foreground);
            transition: all var(--transition-fast);
            cursor: pointer;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .dropdown-item:hover {
            background-color: rgba(139, 92, 246, 0.08);
            color: var(--primary);
        }

        .dropdown-item i {
            margin-right: 12px;
            font-size: 1rem;
            width: 20px;
            text-align: center;
            color: #64748b;
            transition: color var(--transition-fast);
        }

        .dropdown-item:hover i {
            color: var(--primary);
        }

        .dropdown-divider {
            height: 1px;
            background-color: var(--border);
            margin: 6px 0;
        }

        /* ===== View Toggle ===== */
        .view-toggle {
            display: flex;
            border-radius: var(--radius-full);
            overflow: hidden;
            border: 1px solid var(--border);
            background-color: var(--card);
            box-shadow: var(--shadow-sm);
        }

        .view-toggle-btn {
            padding: 8px 12px;
            background: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition-normal);
            color: #64748b;
        }

        .view-toggle-btn.active {
            background: var(--primary-gradient);
            color: white;
        }

        /* File Container Transitions */
        .files-container {
            position: relative;
            min-height: 300px;
            transition: height var(--transition-normal) var(--ease-out);
        }

        .grid-view, .list-view {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            transition: opacity var(--transition-normal) var(--ease-out),
                        transform var(--transition-normal) var(--ease-out);
        }

        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .list-view {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .view-hidden {
            opacity: 0;
            pointer-events: none;
            transform: scale(0.95);
        }

        .grid-view .file-item {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .grid-view .file-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            align-items: center;
            text-align: center;
        }

        .grid-view .file-icon {
            width: 60px;
            height: 60px;
            margin-bottom: 1rem;
            margin-right: 0;
            font-size: 1.5rem;
            transform: translateZ(0);
            transition: transform var(--transition-normal) var(--ease-bounce);
        }

        .grid-view .file-item:hover .file-icon {
            transform: scale(1.1) translateZ(0);
        }

        .grid-view .file-details {
            width: 100%;
        }

        .grid-view .file-footer {
            background-color: rgba(243, 244, 246, 0.5);
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border);
        }

        .list-view .file-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
        }

        .list-view .file-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
            transition: transform var(--transition-normal) var(--ease-bounce);
        }

        .list-view .file-item:hover .file-icon {
            transform: scale(1.1);
        }

        .list-view .file-details {
            flex: 1;
            min-width: 0;
            max-width: calc(100% - 120px);
        }
        
        .list-view .file-actions {
            display: flex;
            align-items: center;
            margin-left: auto;
            min-width: 100px;
            justify-content: flex-end;
            gap: 0.5rem;
        }

/* Enhance the empty state in dark mode */
.dark .empty-state-icon {
    background: linear-gradient(135deg, var(--primary-light), var(--primary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    opacity: 0.8;
}

        /* ===== Empty State ===== */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 1.5rem;
            text-align: center;
            background-color: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
            position: relative;
        }

        .empty-state::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .empty-state-icon {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: float 6s ease-in-out infinite;
        }

        /* ===== Search Input ===== */
        .search-container {
            position: relative;
            z-index: 10;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1.25rem 0.75rem 3rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-full);
            background-color: var(--card);
            transition: all var(--transition-normal);
            font-family: inherit;
            font-size: 1rem;
            color: var(--foreground);
            box-shadow: var(--shadow-sm);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px var(--ring);
        }

        .search-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            transition: color var(--transition-normal);
            pointer-events: none;
        }

        .search-input:focus + .search-icon {
            color: var(--primary);
        }

        .search-clear {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            opacity: 0.7;
            transition: all var(--transition-normal);
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .search-clear:hover {
            opacity: 1;
            background-color: rgba(243, 244, 246, 0.8);
            color: var(--foreground);
        }

        /* ===== Stats Modal ===== */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            visibility: hidden;
            transition: opacity var(--transition-normal) var(--ease-out),
                        visibility var(--transition-normal) var(--ease-out);
        }

        .modal-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background-color: var(--card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            transform: scale(0.95);
            opacity: 0;
            transition: transform var(--transition-normal) var(--ease-bounce),
                        opacity var(--transition-normal) var(--ease-out);
            border: 1px solid var(--border);
        }

        .modal-backdrop.show .modal {
            transform: scale(1);
            opacity: 1;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--foreground);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-title i {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .modal-close {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .modal-close:hover {
            background-color: rgba(243, 244, 246, 0.8);
            color: var(--foreground);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            background-color: rgba(243, 244, 246, 0.5);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            border-top: 1px solid var(--border);
        }

        /* ===== Stats Cards ===== */
        .stats-card {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            background-color: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: all var(--transition-normal);
            overflow: hidden;
            position: relative;
            border: 1px solid var(--border);
            margin-bottom: 1rem;
        }

        .stats-card:last-child {
            margin-bottom: 0;
        }

        .stats-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            border-color: rgba(139, 92, 246, 0.2);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
            opacity: 0;
            transition: opacity var(--transition-normal);
        }

        .stats-card:hover::before {
            opacity: 1;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(124, 58, 237, 0.1));
            color: var(--primary);
            transition: all var(--transition-normal);
        }

        .stats-card:hover .stats-icon {
            transform: scale(1.1);
        }

        .stats-info {
            flex: 1;
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--foreground);
            transition: all var(--transition-normal);
        }

        .stats-card:hover .stats-value {
            color: var(--primary);
        }

        .stats-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* ===== Context Menu ===== */
        .context-menu {
            position: absolute;
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow-xl);
            padding: 0.5rem 0;
            z-index: 1000;
            min-width: 220px;
            animation: fadeInScale 0.2s var(--ease-bounce) forwards;
            transform-origin: top left;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .context-menu-item {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all var(--transition-fast);
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--card-foreground);
        }

        .context-menu-item:hover {
            background-color: rgba(139, 92, 246, 0.08);
            color: var(--primary);
        }

        .context-menu-item i {
            margin-right: 0.75rem;
            width: 1.25rem;
            text-align: center;
            font-size: 1rem;
            transition: all var(--transition-fast);
        }

        .context-menu-item:hover i {
            transform: scale(1.1);
        }

        .context-menu-divider {
            height: 1px;
            background-color: var(--border);
            margin: 0.375rem 0;
        }
        
        /* ===== File Name Truncation ===== */
        .file-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            display: block;
            font-weight: 600;
            color: var(--card-foreground);
            transition: color var(--transition-fast);
        }
        
        .file-item:hover .file-name {
            color: var(--primary);
        }
        
        /* ===== Upload Button ===== */
        .upload-btn {
            position: relative;
            z-index: 20;
        }
        
/* Improve the action bar appearance */
.action-bar {
    position: relative;
    z-index: 15;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    background-color: rgba(255, 255, 255, 0.7);
    border: 1px solid var(--border);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.dark .action-bar {
    background-color: rgba(15, 23, 42, 0.7);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}
        
        /* Fix the files wrapper to prevent footer overlap */
        .files-wrapper {
            position: relative;
            z-index: 5;
            min-height: 300px;
            margin-bottom: 3rem; /* Add margin to prevent footer overlap */
        }
        
        /* ===== Hidden Textarea for Clipboard ===== */
        .clipboard-textarea {
            position: absolute;
            left: -9999px;
            top: 0;
            opacity: 0;
        }

        /* ===== File Metadata ===== */
        .file-meta {
            display: flex;
            align-items: center;
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .file-meta-item {
            display: flex;
            align-items: center;
        }

        .file-meta-item:not(:last-child)::after {
            content: '•';
            margin: 0 0.5rem;
            opacity: 0.5;
        }

        .file-meta-icon {
            margin-right: 0.25rem;
            font-size: 0.75rem;
            opacity: 0.7;
        }

/* Add a subtle animation to the page title */
.page-title {
    font-size: 2rem;
    font-weight: 800;
    color: var(--foreground);
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    background-size: 200% 200%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.02em;
    animation: gradientShift 5s ease infinite;
}

        /* ===== Page Header ===== */
        .page-header {
            position: relative;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(to right, var(--border), transparent);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--foreground);
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 1rem;
            max-width: 600px;
            font-weight: 500;
        }

        /* ===== Confirm Dialog ===== */
        .dialog-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            animation: fadeIn 0.3s var(--ease-out) forwards;
        }

        .dialog {
            background-color: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            animation: fadeInScale 0.3s var(--ease-bounce) forwards;
            transform-origin: center;
            border: 1px solid var(--border);
        }

        .dialog-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .dialog-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--foreground);
        }

        .dialog-body {
            padding: 1.5rem;
            color: #4b5563;
        }

        .dialog-footer {
            padding: 1rem 1.5rem;
            background-color: rgba(243, 244, 246, 0.5);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            border-top: 1px solid var(--border);
        }

        /* ===== File Actions ===== */
        .file-action {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            transition: all var(--transition-fast);
            background-color: transparent;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .file-action:hover {
            background-color: rgba(243, 244, 246, 0.8);
            color: var(--foreground);
        }

        .file-action-primary:hover {
            background-color: rgba(139, 92, 246, 0.1);
            color: var(--primary);
        }

        .file-action-danger:hover {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Ripple effect for file actions */
        .file-action::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }

        .file-action:active::after {
            animation: ripple 0.6s ease-out;
        }

        /* ===== Theme Toggle ===== */
        .theme-toggle {
            position: relative;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            transition: all var(--transition-fast);
            background-color: transparent;
            border: none;
            cursor: pointer;
            overflow: hidden;
        }

        .theme-toggle:hover {
            background-color: rgba(243, 244, 246, 0.8);
            color: var(--foreground);
        }

        .dark .theme-toggle:hover {
            background-color: rgba(30, 41, 59, 0.8);
        }

        /* ===== Responsive Adjustments ===== */
        @media (max-width: 768px) {
            .grid-view {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }
            
            .stats-value {
                font-size: 1.25rem;
            }
            
            .page-title {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 640px) {
            .grid-view {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 0.75rem;
            }
            
            .grid-view .file-content {
                padding: 1rem;
            }
            
            .grid-view .file-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
            
            .stats-card {
                padding: 1rem;
            }
            
            .stats-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php require "./global-header.php"; ?>
        
        <!-- Page Header -->
        <div class="page-header fade-in">
            <h1 class="page-title">Your Cloud Storage</h1>
            <p class="page-subtitle">Securely store, manage, and share your files in the cloud</p>
        </div>
        
        <!-- Action Bar -->
        <div class="rounded-xl p-4 mb-6 fade-in action-bar glass" style="animation-delay: 0.2s">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="ups.php" class="btn btn-primary upload-btn ripple">
                        <i class="fas fa-cloud-upload-alt mr-2"></i>
                        <span>Upload Files</span>
                    </a>
                    
                    <button id="refresh-btn" class="btn btn-secondary ripple">
                        <i class="fas fa-sync-alt mr-2"></i>
                        <span>Refresh</span>
                    </button>
                    
                    <button id="stats-btn" class="btn btn-secondary ripple">
                        <i class="fas fa-chart-pie mr-2"></i>
                        <span>Storage Stats</span>
                    </button>
                    
                    <div class="dropdown">
                        <button id="sort-btn" class="btn btn-secondary ripple">
                            <i class="fas fa-sort-amount-down mr-2"></i>
                            <span>Sort By</span>
                            <i class="fas fa-chevron-down text-xs ml-2 opacity-70"></i>
                        </button>
                        <div class="dropdown-menu" id="sort-menu">
                            <div class="dropdown-item sort-option" data-sort="upload_date" data-order="DESC">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Date (Newest)</span>
                            </div>
                            <div class="dropdown-item sort-option" data-sort="upload_date" data-order="ASC">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Date (Oldest)</span>
                            </div>
                            <div class="dropdown-item sort-option" data-sort="file_name" data-order="ASC">
                                <i class="fas fa-sort-alpha-down"></i>
                                <span>Name (A-Z)</span>
                            </div>
                            <div class="dropdown-item sort-option" data-sort="file_name" data-order="DESC">
                                <i class="fas fa-sort-alpha-up"></i>
                                <span>Name (Z-A)</span>
                            </div>
                            <div class="dropdown-item sort-option" data-sort="file_size" data-order="DESC">
                                <i class="fas fa-weight"></i>
                                <span>Size (Largest)</span>
                            </div>
                            <div class="dropdown-item sort-option" data-sort="file_size" data-order="ASC">
                                <i class="fas fa-weight"></i>
                                <span>Size (Smallest)</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="view-toggle">
                        <button id="grid-view-btn" class="view-toggle-btn active tooltip" data-tooltip="Grid View">
                            <i class="fas fa-th"></i>
                        </button>
                        <button id="list-view-btn" class="view-toggle-btn tooltip" data-tooltip="List View">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    
                    <button id="theme-toggle" class="theme-toggle tooltip" data-tooltip="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Search Bar -->
        <div class="mb-6 fade-in" style="animation-delay: 0.3s">
            <div class="search-container">
                <input 
                    type="text" 
                    id="search-input" 
                    class="search-input" 
                    placeholder="Search your files..."
                    autocomplete="off"
                >
                <i class="fas fa-search search-icon"></i>
                <i class="fas fa-times search-clear" id="search-clear"></i>
            </div>
        </div>
        
        <!-- Files Container -->
        <div id="files-wrapper" class="fade-in files-wrapper" style="animation-delay: 0.4s">
            <div class="files-container" id="files-container">
                <div id="grid-view" class="grid-view">
                    <!-- Grid files will be dynamically inserted here -->
                </div>
                <div id="list-view" class="list-view view-hidden">
                    <!-- List files will be dynamically inserted here -->
                </div>
            </div>
        </div>
        
        <!-- Stats Modal -->
        <div id="stats-modal" class="modal-backdrop">
            <div class="modal">
                <div class="modal-header">
                    <div class="modal-title">
                        <i class="fas fa-chart-pie"></i>
                        <span>Storage Statistics</span>
                    </div>
                    <button id="close-stats-modal" class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="stats-info">
                            <div class="stats-value" id="total-files">-</div>
                            <div class="stats-label">Total Files</div>
                        </div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="stats-info">
                            <div class="stats-value" id="total-storage">-</div>
                            <div class="stats-label">Storage Used</div>
                        </div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stats-info">
                            <div class="stats-value" id="last-upload">-</div>
                            <div class="stats-label">Last Upload</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="close-stats-btn" class="btn btn-secondary ripple">
                        Close
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Toast Container -->
        <div class="toast-container" id="toast-container">
            <!-- Toasts will be dynamically inserted here -->
        </div>
        
        <!-- Context Menu -->
        <div id="context-menu" class="context-menu" style="display: none;">
            <div class="context-menu-item" id="ctx-download">
                <i class="fas fa-download text-primary"></i>
                <span>Download</span>
            </div>
            <div class="context-menu-item" id="ctx-share">
                <i class="fas fa-share-alt text-info"></i>
                <span>Copy Link</span>
            </div>
            <div class="context-menu-divider"></div>
            <div class="context-menu-item" id="ctx-delete">
                <i class="fas fa-trash-alt text-danger"></i>
                <span>Delete</span>
            </div>
        </div>
        
        <!-- Hidden textarea for clipboard operations -->
        <textarea id="clipboard-textarea" class="clipboard-textarea"></textarea>
        
        <?php require "./global-footer.php";?>
    </div>
    
   <script src="/hammer.js?nn=1" type="text/javascript"></script>
    
    <script>
    
    const bodyElement = document.querySelector(".sue-navbar");
    const hammer = new Hammer(bodyElement);

    // 监听 swipe 事件
    hammer.on('swiperight', () => {
      // 跳转到指定的 URL
      window.location.href = '/notices.php'; // 替换为你想要跳转的 URL
    });
    
    hammer.on('swipeleft', () => {
      // 跳转到指定的 URL
      window.location.href = '/timetable/timetable.php'; // 替换为你想要跳转的 URL
    });

// ===== State Management =====
let currentSort = 'upload_date';
let currentOrder = 'DESC';
let currentSearch = '';
let currentView = 'grid';
let currentFiles = [];
let contextMenuTarget = null;
let globalStats = null; // Store global stats separately from search results
let isTransitioning = false; // Flag to prevent multiple view transitions
let isDarkMode = false; // Track dark mode state

// ===== DOM Elements =====
let filesContainer = document.getElementById('files-container');
let gridView = document.getElementById('grid-view');
let listView = document.getElementById('list-view');
let searchInput = document.getElementById('search-input');
let searchClear = document.getElementById('search-clear');
let refreshBtn = document.getElementById('refresh-btn');
let sortBtn = document.getElementById('sort-btn');
let sortMenu = document.getElementById('sort-menu');
let gridViewBtn = document.getElementById('grid-view-btn');
let listViewBtn = document.getElementById('list-view-btn');
let toastContainer = document.getElementById('toast-container');
let contextMenu = document.getElementById('context-menu');
let ctxDownload = document.getElementById('ctx-download');
let ctxShare = document.getElementById('ctx-share');
let ctxDelete = document.getElementById('ctx-delete');
let totalFilesEl = document.getElementById('total-files');
let totalStorageEl = document.getElementById('total-storage');
let lastUploadEl = document.getElementById('last-upload');
let clipboardTextarea = document.getElementById('clipboard-textarea');
let statsBtn = document.getElementById('stats-btn');
let statsModal = document.getElementById('stats-modal');
let closeStatsModal = document.getElementById('close-stats-modal');
let closeStatsBtn = document.getElementById('close-stats-btn');
let themeToggle = document.getElementById('theme-toggle');

// ===== Event Listeners =====
document.addEventListener('DOMContentLoaded', () => {
    // Check if all required DOM elements exist
    if (!filesContainer || !gridView || !listView) {
        console.error('Required DOM elements are missing');
        return;
    }
    
    // Check for system color scheme preference
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Check for saved theme preference, or use system preference
    if (localStorage.getItem('darkMode') === 'true' || (prefersDarkMode && localStorage.getItem('darkMode') === null)) {
        enableDarkMode();
    } else {
        disableDarkMode();
    }
    
    // Listen for system theme changes
    if (window.matchMedia) {
        try {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (localStorage.getItem('darkMode') === null) {
                    if (e.matches) {
                        enableDarkMode();
                    } else {
                        disableDarkMode();
                    }
                }
            });
        } catch (error) {
            console.error('Media query event listener not supported', error);
        }
    }
    
    refreshFiles();
    fetchGlobalStats(); // Fetch stats separately from files
    setupEventListeners();
});

function setupEventListeners() {
    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            currentSearch = searchInput.value.trim();
            refreshFiles();
        }, 300));
    }
    
    if (searchClear) {
        searchClear.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                currentSearch = '';
                refreshFiles();
            }
        });
    }
    
    // Refresh button
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            showLoading();
            refreshFiles();
            fetchGlobalStats(); // Refresh stats when manually refreshing
        });
    }
    
    // Stats button
    if (statsBtn) {
        statsBtn.addEventListener('click', () => {
            openStatsModal();
        });
    }
    
    // Close stats modal
    if (closeStatsModal) {
        closeStatsModal.addEventListener('click', () => {
            closeModal();
        });
    }
    
    if (closeStatsBtn) {
        closeStatsBtn.addEventListener('click', () => {
            closeModal();
        });
    }
    
    // Close modal when clicking outside
    if (statsModal) {
        statsModal.addEventListener('click', (e) => {
            if (e.target === statsModal) {
                closeModal();
            }
        });
    }
    
    // Theme toggle
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            toggleDarkMode();
        });
    }
    
    // Sort dropdown
    if (sortBtn) {
        sortBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sortBtn.parentElement) {
                sortBtn.parentElement.classList.toggle('active');
            }
        });
    }
    
    // Sort options
    document.querySelectorAll('.sort-option').forEach(option => {
        option.addEventListener('click', () => {
            currentSort = option.dataset.sort || 'upload_date';
            currentOrder = option.dataset.order || 'DESC';
            updateSortButtonText(option.textContent.trim());
            if (sortBtn && sortBtn.parentElement) {
                sortBtn.parentElement.classList.remove('active');
            }
            refreshFiles();
        });
    });
    
    // View toggle
    if (gridViewBtn) {
        gridViewBtn.addEventListener('click', () => {
            if (currentView !== 'grid' && !isTransitioning) {
                setViewMode('grid');
            }
        });
    }
    
    if (listViewBtn) {
        listViewBtn.addEventListener('click', () => {
            if (currentView !== 'list' && !isTransitioning) {
                setViewMode('list');
            }
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            dropdown.classList.remove('active');
        });
        
        // Hide context menu
        if (contextMenu) {
            contextMenu.style.display = 'none';
        }
    });
    
    // Context menu items
    if (ctxDownload) {
        ctxDownload.addEventListener('click', () => {
            if (contextMenuTarget) {
                window.open(contextMenuTarget.dataset.url, '_blank', 'noreferrer=yes');
            }
            if (contextMenu) {
                contextMenu.style.display = 'none';
            }
        });
    }
    
    if (ctxShare) {
        ctxShare.addEventListener('click', () => {
            if (contextMenuTarget) {
                copyToClipboard(contextMenuTarget.dataset.url);
                showToast('success', 'Link Copied', 'File link has been copied to clipboard');
            }
            if (contextMenu) {
                contextMenu.style.display = 'none';
            }
        });
    }
    
    if (ctxDelete) {
        ctxDelete.addEventListener('click', () => {
            if (contextMenuTarget) {
                deleteFile(contextMenuTarget.dataset.id);
            }
            if (contextMenu) {
                contextMenu.style.display = 'none';
            }
        });
    }
    
    // Prevent context menu from closing when clicking inside it
    if (contextMenu) {
        contextMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        // Escape key to close modals
        if (e.key === 'Escape') {
            closeModal();
            if (contextMenu) {
                contextMenu.style.display = 'none';
            }
        }
        
        // Ctrl+F to focus search
        if (e.ctrlKey && e.key === 'f' && searchInput) {
            e.preventDefault();
            searchInput.focus();
        }
    });
}

// ===== Theme Functions =====
function toggleDarkMode() {
    if (isDarkMode) {
        disableDarkMode();
        localStorage.setItem('darkMode', 'false');
    } else {
        enableDarkMode();
        localStorage.setItem('darkMode', 'true');
    }
}

function enableDarkMode() {
    document.body.classList.add('dark');
    if (themeToggle) {
        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        themeToggle.setAttribute('data-tooltip', 'Light Mode');
    }
    isDarkMode = true;
}

function disableDarkMode() {
    document.body.classList.remove('dark');
    if (themeToggle) {
        themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        themeToggle.setAttribute('data-tooltip', 'Dark Mode');
    }
    isDarkMode = false;
}

// ===== Modal Functions =====
function openStatsModal() {
    if (!statsModal) return;
    
    statsModal.classList.add('show');
    // Ensure stats are up to date
    fetchGlobalStats();
}

function closeModal() {
    if (!statsModal) return;
    
    statsModal.classList.remove('show');
}

// ===== File Management Functions =====
function refreshFiles() {
    showLoading(); // Always show loading state when refreshing
    
    fetch(`?action=get_files&sort=${currentSort}&order=${currentOrder}&search=${encodeURIComponent(currentSearch)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(files => {
            currentFiles = files;
            renderFiles(files);
        })
        .catch(error => {
            showToast('error', 'Error', 'Failed to load files. Please try again.');
            console.error('Error fetching files:', error);
            
            // Render empty state on error
            gridView.innerHTML = '';
            listView.innerHTML = '';
            renderEmptyState();
        });
}

function fetchGlobalStats() {
    fetch('?action=get_stats')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(stats => {
            globalStats = stats;
            updateStatsDisplay(stats);
        })
        .catch(error => {
            console.error('Error fetching stats:', error);
            
            // Set default values on error
            if (totalFilesEl) totalFilesEl.textContent = '0';
            if (totalStorageEl) totalStorageEl.textContent = '0 B';
            if (lastUploadEl) lastUploadEl.textContent = 'Never';
        });
}

function updateContainerHeight() {
    if (!filesContainer) return;
    
    // Set container height to match the current view
    const activeView = currentView === 'grid' ? gridView : listView;
    if (!activeView) return;
    
    const viewHeight = activeView.offsetHeight;
    
    // Ensure minimum height and add padding for footer
    const minHeight = 300;
    const footerPadding = 60;
    filesContainer.style.height = `${Math.max(viewHeight, minHeight) + footerPadding}px`;
}

function renderFiles(files) {
    if (!gridView || !listView) {
        console.error('View elements not found');
        return;
    }
    
    // Clear both views
    gridView.innerHTML = '';
    listView.innerHTML = '';
    
    if (!files || files.length === 0) {
        renderEmptyState();
        return;
    }
    
    // Render files in both views
    files.forEach((file, index) => {
        if (!file) return;
        
        try {
            const gridFileElement = createFileElement(file, index, 'grid');
            const listFileElement = createFileElement(file, index, 'list');
            
            if (gridFileElement) gridView.appendChild(gridFileElement);
            if (listFileElement) listView.appendChild(listFileElement);
        } catch (error) {
            console.error('Error creating file element:', error, file);
        }
    });
    
    // Set up double-click download functionality
    setupDoubleClickDownload();
    
    // Update container height with footer consideration
    updateContainerHeight();
}

// Add double-click functionality to file items
function setupDoubleClickDownload() {
    // Get all file items in both grid and list views
    const fileItems = document.querySelectorAll('.file-item');
    
    fileItems.forEach(item => {
        item.addEventListener('dblclick', () => {
            const fileUrl = item.dataset.url;
            if (fileUrl) {
                // Create visual feedback for the download action
                const ripple = document.createElement('div');
                ripple.className = 'absolute inset-0 bg-primary-light rounded-xl opacity-0';
                ripple.style.animation = 'downloadPulse 0.6s ease-out forwards';
                item.appendChild(ripple);
                
                // Show download toast
                showToast('info', 'Downloading File', 'Your file download has started');
                
                // Open the file URL in a new tab
                window.open(fileUrl, '_blank', 'noreferrer=yes');
                
                // Remove the ripple effect after animation
                setTimeout(() => {
                    if (item.contains(ripple)) {
                        item.removeChild(ripple);
                    }
                }, 600);
            }
        });
    });
}

function setViewMode(mode) {
    if (isTransitioning) return;
    
    const currentViewEl = currentView === 'grid' ? gridView : listView;
    const newViewEl = mode === 'grid' ? gridView : listView;
    
    if (!currentViewEl || !newViewEl) {
        console.error('View elements not found');
        return;
    }
    
    isTransitioning = true;
    
    // Update button states
    if (gridViewBtn && listViewBtn) {
        if (mode === 'grid') {
            gridViewBtn.classList.add('active');
            listViewBtn.classList.remove('active');
        } else {
            gridViewBtn.classList.remove('active');
            listViewBtn.classList.add('active');
        }
    }
    
    // Fade out current view
    currentViewEl.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    currentViewEl.style.opacity = '0';
    currentViewEl.style.transform = 'scale(0.95)';
    
    // After current view fades out, show new view
    setTimeout(() => {
        currentViewEl.classList.add('view-hidden');
        newViewEl.classList.remove('view-hidden');
        
        // Update container height with footer consideration
        updateContainerHeight();
        
        // Fade in new view
        newViewEl.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        newViewEl.style.opacity = '1';
        newViewEl.style.transform = 'scale(1)';
        
        // Update current view state
        currentView = mode;
        
        // Reset transition flag after animation completes
        setTimeout(() => {
            isTransitioning = false;
        }, 300);
    }, 300);
}

function renderEmptyState() {
    const emptyState = document.createElement('div');
    emptyState.className = 'empty-state fade-in-scale';
    
    if (currentSearch) {
        emptyState.innerHTML = `
            <div class="empty-state-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">No files found</h3>
            <p class="mb-6">We couldn't find any files matching "${currentSearch}"</p>
            <button id="clear-search-btn" class="btn btn-primary ripple">
                Clear Search
            </button>
        `;
    } else {
        emptyState.innerHTML = `
            <div class="empty-state-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <h3 class="text-xl font-semibold mb-2">No files yet</h3>
            <p class="mb-6">Upload your first file to get started</p>
            <a href="ups.php" class="btn btn-primary ripple">
                <i class="fas fa-cloud-upload-alt mr-2"></i>
                Upload Files
            </a>
        `;
    }
    
    // Add to both views
    gridView.appendChild(emptyState.cloneNode(true));
    listView.appendChild(emptyState);
    
    // Update container height with footer consideration
    const footerPadding = 60;
    filesContainer.style.height = `${emptyState.offsetHeight + 40 + footerPadding}px`;
    
    // Add event listener to clear search button if it exists
    const clearSearchBtns = document.querySelectorAll('#clear-search-btn');
    clearSearchBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            searchInput.value = '';
            currentSearch = '';
            refreshFiles();
        });
    });
}

function deleteFile(id) {
    if (!id) {
        showToast('error', 'Error', 'Invalid file ID');
        return;
    }
    
    showConfirmDialog('Delete File', 'Are you sure you want to delete this file? This action cannot be undone.', () => {
        // Ensure id is a number for the API
        const fileId = parseInt(id, 10);
        
        if (isNaN(fileId)) {
            showToast('error', 'Error', 'Invalid file ID format');
            return;
        }
        
        fetch(`?action=delete&id=${fileId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('success', 'File Deleted', 'The file has been successfully deleted.');
                    refreshFiles();
                    fetchGlobalStats(); // Update stats after deletion
                } else {
                    showToast('error', 'Error', 'Failed to delete the file. Please try again.');
                }
            })
            .catch(error => {
                showToast('error', 'Error', 'An unexpected error occurred. Please try again.');
                console.error('Error deleting file:', error);
            });
    });
}

// ===== Helper Functions =====
function createFileElement(file, index, viewType) {
    try {
        if (!file || !file.file_name) {
            console.error('Invalid file data', file);
            return null;
        }
        
        const div = document.createElement('div');
        div.className = 'card file-item';
        div.dataset.id = file.id || '';
        div.dataset.url = file.file_url || '';
        div.dataset.name = file.file_name || '';
        div.style.position = 'relative'; // Add this for ripple effect
        div.style.cursor = 'pointer'; // Add pointer cursor to indicate clickable
        
        // Add animation delay based on index
        div.style.animationDelay = `${0.05 * (index % 10)}s`;
        
        const fileType = getFileType(file.file_name);
        const fileIcon = getFileIcon(fileType);
        const fileIconClass = getFileIconClass(fileType);
        
        // Format date for better display
        let formattedDate = 'Unknown date';
        let formattedTime = '';
        
        if (file.upload_date) {
            try {
                const uploadDate = new Date(file.upload_date);
                formattedDate = uploadDate.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
                
                formattedTime = uploadDate.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
            } catch (error) {
                console.error('Error formatting date', error);
            }
        }
        
        // Truncate filename for list view to 30 characters
        const truncatedFileName = viewType === 'list' 
            ? truncateString(file.file_name, 30) 
            : file.file_name;
        
        // Safely escape HTML content
        const safeFileName = escapeHtml(file.file_name);
        const safeTruncatedFileName = escapeHtml(truncatedFileName);
        
        if (viewType === 'grid') {
            div.classList.add('fade-in-scale');
            div.innerHTML = `
                <div class="file-content">
                    <div class="file-icon ${fileIconClass}">
                        <i class="fas ${fileIcon}"></i>
                    </div>
                    <div class="file-details">
                        <h3 class="file-name mb-2" title="${safeFileName}">
                            ${safeFileName}
                        </h3>
                        <div class="file-meta">
                            <div class="file-meta-item">
                                <i class="fas fa-weight file-meta-icon"></i>
                                <span>${formatFileSize(file.file_size || 0)}</span>
                            </div>
                            <div class="file-meta-item">
                                <i class="fas fa-calendar-alt file-meta-icon"></i>
                                <span>${formattedDate}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="file-footer">
                    <a href="${file.file_url || '#'}" target="_blank" rel="noopener noreferrer" 
                       class="file-action file-action-primary tooltip"
                       data-tooltip="Download">
                        <i class="fas fa-download"></i>
                    </a>
                    <button onclick="deleteFile('${file.id || ''}')" 
                            class="file-action file-action-danger tooltip"
                            data-tooltip="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;
        } else {
            div.classList.add('slide-in-right');
            div.innerHTML = `
                <div class="file-icon ${fileIconClass}">
                    <i class="fas ${fileIcon}"></i>
                </div>
                <div class="file-details">
                    <h3 class="file-name" title="${safeFileName}">
                        ${safeTruncatedFileName}
                    </h3>
                    <div class="file-meta">
                        <div class="file-meta-item">
                            <i class="fas fa-weight file-meta-icon"></i>
                            <span>${formatFileSize(file.file_size || 0)}</span>
                        </div>
                        <div class="file-meta-item">
                            <i class="fas fa-calendar-alt file-meta-icon"></i>
                            <span>${formattedDate} ${formattedTime ? 'at ' + formattedTime : ''}</span>
                        </div>
                    </div>
                </div>
                <div class="file-actions">
                    <a href="${file.file_url || '#'}" target="_blank" rel="noopener noreferrer" 
                       class="file-action file-action-primary tooltip"
                       data-tooltip="Download">
                        <i class="fas fa-download"></i>
                    </a>
                    <button onclick="deleteFile('${file.id || ''}')" 
                            class="file-action file-action-danger tooltip"
                            data-tooltip="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;
        }
        
        // Add context menu event
        div.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            showContextMenu(e, div);
        });
        
        // Add hover effect
        div.addEventListener('mousemove', (e) => {
            const rect = div.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            div.style.setProperty('--x', `${x}px`);
            div.style.setProperty('--y', `${y}px`);
        });
        
        return div;
    } catch (error) {
        console.error('Error creating file element', error);
        return null;
    }
}

function getFileType(fileName) {
    const extension = fileName.split('.').pop().toLowerCase();
    
    const imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'ico'];
    const documentTypes = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'md', 'odt'];
    const spreadsheetTypes = ['xls', 'xlsx', 'csv', 'ods'];
    const presentationTypes = ['ppt', 'pptx', 'odp'];
    const archiveTypes = ['zip', 'rar', 'tar', 'gz', '7z'];
    const codeTypes = ['html', 'css', 'js', 'php', 'py', 'java', 'c', 'cpp', 'h', 'json', 'xml'];
    const audioTypes = ['mp3', 'wav', 'ogg', 'flac', 'aac'];
    const videoTypes = ['mp4', 'avi', 'mov', 'wmv', 'mkv', 'webm'];
    
    if (imageTypes.includes(extension)) return 'image';
    if (documentTypes.includes(extension)) return 'document';
    if (spreadsheetTypes.includes(extension)) return 'spreadsheet';
    if (presentationTypes.includes(extension)) return 'presentation';
    if (archiveTypes.includes(extension)) return 'archive';
    if (codeTypes.includes(extension)) return 'code';
    if (audioTypes.includes(extension)) return 'audio';
    if (videoTypes.includes(extension)) return 'video';
    
    return 'default';
}

function getFileIcon(fileType) {
    const iconMap = {
        'image': 'fa-file-image',
        'document': 'fa-file-alt',
        'spreadsheet': 'fa-file-excel',
        'presentation': 'fa-file-powerpoint',
        'archive': 'fa-file-archive',
        'code': 'fa-file-code',
        'audio': 'fa-file-audio',
        'video': 'fa-file-video',
        'default': 'fa-file'
    };
    
    return iconMap[fileType] || 'fa-file';
}

function getFileIconClass(fileType) {
    const classMap = {
        'image': 'file-image',
        'document': 'file-document',
        'spreadsheet': 'file-spreadsheet',
        'presentation': 'file-presentation',
        'archive': 'file-archive',
        'code': 'file-code',
        'audio': 'file-audio',
        'video': 'file-video',
        'default': 'file-default'
    };
    
    return classMap[fileType] || 'file-default';
}

function formatFileSize(bytes) {
    const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
    let i = 0;
    while (bytes >= 1024 && i < units.length - 1) {
        bytes /= 1024;
        i++;
    }
    return `${bytes.toFixed(2)} ${units[i]}`;
}

function truncateString(string, length = 10) {
    return string.length > length ? string.substring(0, length) + '...' : string;
}

// Helper function to escape HTML content
function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Updated to use the global stats instead of current files
function updateStatsDisplay(stats) {
    if (!stats) return;
    
    // Update total files count
    if (totalFilesEl) {
        totalFilesEl.textContent = stats.total_files || '0';
    }
    
    // Update total storage used
    if (totalStorageEl) {
        totalStorageEl.textContent = formatFileSize(stats.total_size || 0);
    }
    
    // Find last upload date
    if (lastUploadEl) {
        if (stats.last_upload) {
            const lastUploadDate = new Date(stats.last_upload);
            const timeAgo = getTimeAgo(lastUploadDate);
            lastUploadEl.textContent = timeAgo;
        } else {
            lastUploadEl.textContent = 'Never';
        }
    }
}

function getTimeAgo(date) {
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Just now';
    }
    
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
        return `${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''} ago`;
    }
    
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
        return `${diffInHours} hour${diffInHours > 1 ? 's' : ''} ago`;
    }
    
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 30) {
        return `${diffInDays} day${diffInDays > 1 ? 's' : ''} ago`;
    }
    
    const diffInMonths = Math.floor(diffInDays / 30);
    if (diffInMonths < 12) {
        return `${diffInMonths} month${diffInMonths > 1 ? 's' : ''} ago`;
    }
    
    const diffInYears = Math.floor(diffInMonths / 12);
    return `${diffInYears} year${diffInYears > 1 ? 's' : ''} ago`;
}

function updateSortButtonText(text) {
    if (!sortBtn) return;
    
    const sortBtnText = sortBtn.querySelector('span');
    if (sortBtnText) {
        sortBtnText.textContent = text;
    }
}

function showLoading() {
    // Clear both views and show loading spinner
    if (gridView) {
        gridView.innerHTML = `
            <div class="flex justify-center items-center py-20 w-full">
                <div class="loading-dots">
                    <div class="loading-dot"></div>
                    <div class="loading-dot"></div>
                    <div class="loading-dot"></div>
                </div>
            </div>
        `;
    }
    
    if (listView) {
        listView.innerHTML = '';
    }
    
    // Set container height for the loading state
    if (filesContainer) {
        filesContainer.style.height = '300px';
    }
}

function showContextMenu(e, target) {
    if (!contextMenu) return;
    
    contextMenuTarget = target;
    
    // Position the menu exactly at the mouse cursor position
    contextMenu.style.top = `${e.pageY}px`;
    contextMenu.style.left = `${e.pageX}px`;
    
    // Show the menu
    contextMenu.style.display = 'block';
    
    // Adjust position if menu goes off screen
    const menuRect = contextMenu.getBoundingClientRect();
    const windowWidth = window.innerWidth;
    const windowHeight = window.innerHeight;
    
    if (menuRect.right > windowWidth) {
        contextMenu.style.left = `${e.pageX - menuRect.width}px`;
    }
    
    if (menuRect.bottom > windowHeight) {
        contextMenu.style.top = `${e.pageY - menuRect.height}px`;
    }
}

function copyToClipboard(text) {
    if (!clipboardTextarea) {
        // Fallback for modern browsers
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text)
                .then(() => {
                    showToast('success', 'Link Copied', 'File link has been copied to clipboard');
                })
                .catch(err => {
                    console.error('Failed to copy text: ', err);
                    showToast('error', 'Copy Failed', 'Could not copy the link to clipboard');
                });
            return;
        }
        
        showToast('error', 'Copy Failed', 'Could not copy the link to clipboard');
        return;
    }
    
    clipboardTextarea.hidden = false;
    clipboardTextarea.value = text;
    clipboardTextarea.select();
    
    try {
        document.execCommand('copy');
    } catch (err) {
        console.error('Failed to copy text: ', err);
        showToast('error', 'Copy Failed', 'Could not copy the link to clipboard');
    }
    clipboardTextarea.hidden = true;
}

function showToast(type, title, message) {
    if (!toastContainer) return;
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Set toast content
    toast.innerHTML = `
        <i class="fas ${getToastIcon(type)}"></i>
        <div>
            <div class="font-medium">${title}</div>
            <div class="text-sm text-gray-500">${message}</div>
        </div>
    `;
    
    // Add to container
    toastContainer.appendChild(toast);
    
    // Show toast (after a small delay to allow for DOM update)
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        
        // Remove from DOM after animation completes
        setTimeout(() => {
            if (toastContainer.contains(toast)) {
                toastContainer.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

function getToastIcon(type) {
    const iconMap = {
        'success': 'fa-check-circle text-green-500',
        'error': 'fa-exclamation-circle text-red-500',
        'warning': 'fa-exclamation-triangle text-yellow-500',
        'info': 'fa-info-circle text-blue-500'
    };
    
    return iconMap[type] || iconMap.info;
}

function showConfirmDialog(title, message, onConfirm) {
    // Create dialog backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'dialog-backdrop';
    document.body.appendChild(backdrop);
    
    // Create dialog content
    const dialog = document.createElement('div');
    dialog.className = 'dialog';
    backdrop.appendChild(dialog);
    
    // Add dialog content
    dialog.innerHTML = `
        <div class="dialog-header">
            <h3 class="dialog-title">${title}</h3>
        </div>
        <div class="dialog-body">
            <p>${message}</p>
        </div>
        <div class="dialog-footer">
            <button id="cancel-btn" class="btn btn-secondary ripple">
                Cancel
            </button>
            <button id="confirm-btn" class="btn btn-danger ripple">
                Delete
            </button>
        </div>
    `;
    
    // Add event listeners
    const cancelBtn = dialog.querySelector('#cancel-btn');
    const confirmBtn = dialog.querySelector('#confirm-btn');
    
    cancelBtn.addEventListener('click', () => {
        document.body.removeChild(backdrop);
    });
    
    confirmBtn.addEventListener('click', () => {
        onConfirm();
        document.body.removeChild(backdrop);
    });
}

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add window resize event listener to update container height
window.addEventListener('resize', debounce(() => {
    updateContainerHeight();
}, 200));

/* Update the dark mode detection in the script section */
// Check for saved theme preference
if (localStorage.getItem('darkMode') === 'true') {
    enableDarkMode();
}

</script>
</body>
</html>