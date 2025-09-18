<?php
//I do not know what this is....
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header("Location: ./login.php");
    exit();
}
$include_src = "file-preview";
require "./create_conn.php";
require_once "./logger.php";
__($_SESSION["user_id"], "View File Details", $_ENV["ENV"], 1);

$conn = create_conn();
$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$file_data = null;
$error_message = null;

// Get file data
if ($file_id > 0) {
    $sql = "SELECT id, file_name, file_url, upload_date, file_size, mime_type FROM user_files WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $file_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $file_data = $result->fetch_assoc();
        
        // Log file view
        __($_SESSION["user_id"], "View File: " . $file_data['file_name'], $_ENV["ENV"], 1);
    } else {
        $error_message = "File not found or you don't have permission to access it.";
    }
    
    $stmt->close();
}

// Handle file actions
if (isset($_GET['action']) && $file_data) {
    $action = $_GET['action'];
    
    if ($action === 'delete') {
        $delete_sql = "DELETE FROM user_files WHERE id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $file_id, $_SESSION['user_id']);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Log file deletion
        __($_SESSION["user_id"], "Delete File: " . $file_data['file_name'], $_ENV["ENV"], 1);
        
        header('Location: ./cloud.php');
        exit();
    }
}

// Get file extension and type
$file_extension = '';
$file_type = 'default';
if ($file_data) {
    $file_extension = pathinfo($file_data['file_name'], PATHINFO_EXTENSION);
    $file_type = getFileType($file_data['file_name']);
}

// Helper functions
function getFileType($fileName) {
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'ico'];
    $documentTypes = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'md', 'odt'];
    $spreadsheetTypes = ['xls', 'xlsx', 'csv', 'ods'];
    $presentationTypes = ['ppt', 'pptx', 'odp'];
    $archiveTypes = ['zip', 'rar', 'tar', 'gz', '7z'];
    $codeTypes = ['html', 'css', 'js', 'php', 'py', 'java', 'c', 'cpp', 'h', 'json', 'xml'];
    $audioTypes = ['mp3', 'wav', 'ogg', 'flac', 'aac'];
    $videoTypes = ['mp4', 'avi', 'mov', 'wmv', 'mkv', 'webm'];
    
    if (in_array($extension, $imageTypes)) return 'image';
    if (in_array($extension, $documentTypes)) return 'document';
    if (in_array($extension, $spreadsheetTypes)) return 'spreadsheet';
    if (in_array($extension, $presentationTypes)) return 'presentation';
    if (in_array($extension, $archiveTypes)) return 'archive';
    if (in_array($extension, $codeTypes)) return 'code';
    if (in_array($extension, $audioTypes)) return 'audio';
    if (in_array($extension, $videoTypes)) return 'video';
    
    return 'default';
}

function getFileIcon($fileType) {
    $iconMap = [
        'image' => 'fa-file-image',
        'document' => 'fa-file-alt',
        'spreadsheet' => 'fa-file-excel',
        'presentation' => 'fa-file-powerpoint',
        'archive' => 'fa-file-archive',
        'code' => 'fa-file-code',
        'audio' => 'fa-file-audio',
        'video' => 'fa-file-video',
        'default' => 'fa-file'
    ];
    
    return $iconMap[$fileType] ?? 'fa-file';
}

function getFileIconClass($fileType) {
    $classMap = [
        'image' => 'file-image',
        'document' => 'file-document',
        'spreadsheet' => 'file-spreadsheet',
        'presentation' => 'file-presentation',
        'archive' => 'file-archive',
        'code' => 'file-code',
        'audio' => 'file-audio',
        'video' => 'file-video',
        'default' => 'file-default'
    ];
    
    return $classMap[$fileType] ?? 'file-default';
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function formatDate($date) {
    return date('F j, Y \a\t g:i A', strtotime($date));
}

function isPreviewable($fileType, $extension) {
    $previewableImages = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
    $previewableAudio = ['mp3', 'wav', 'ogg'];
    $previewableVideo = ['mp4', 'webm'];
    $previewableText = ['txt', 'md', 'css', 'js', 'html', 'xml', 'json', 'csv'];
    
    if ($fileType === 'image' && in_array($extension, $previewableImages)) return true;
    if ($fileType === 'audio' && in_array($extension, $previewableAudio)) return true;
    if ($fileType === 'video' && in_array($extension, $previewableVideo)) return true;
    if (in_array($extension, $previewableText)) return true;
    
    return false;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./favicon.png" type="image/png">
    <title><?php echo $file_data ? htmlspecialchars($file_data['file_name']) : 'File Details'; ?> - Sue</title>
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

        /* ===== File Details Page Specific Styles ===== */
        .file-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .file-header-icon {
            width: 64px;
            height: 64px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1.5rem;
            transition: all var(--transition-normal) var(--ease-bounce);
        }

        .file-header-icon:hover {
            transform: scale(1.1);
        }

        .file-header-info {
            flex: 1;
        }

        .file-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--foreground);
            margin-bottom: 0.5rem;
            word-break: break-word;
        }

        .file-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        .file-meta-item {
            display: flex;
            align-items: center;
        }

        .file-meta-icon {
            margin-right: 0.5rem;
            opacity: 0.7;
        }

        .file-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .file-preview {
            background-color: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
            transition: all var(--transition-normal);
        }

        .file-preview:hover {
            box-shadow: var(--shadow-lg);
            border-color: rgba(139, 92, 246, 0.3);
        }

        .preview-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: rgba(243, 244, 246, 0.5);
        }

        .preview-title {
            font-weight: 600;
            color: var(--foreground);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .preview-content {
            padding: 1.5rem;
            min-height: 200px;
            max-height: 600px;
            overflow: auto;
        }

        /* Image preview */
        .preview-image {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
        }

        .preview-image img {
            max-width: 100%;
            max-height: 500px;
            object-fit: contain;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-normal);
        }

        .preview-image img:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-lg);
        }

        /* Audio preview */
        .preview-audio {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .audio-visualizer {
            width: 100%;
            height: 120px;
            background-color: rgba(139, 92, 246, 0.1);
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .audio-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 2px;
            padding: 10px;
        }

        .audio-bar {
            width: 4px;
            background-color: var(--primary);
            border-radius: 2px;
            transition: height 0.2s ease;
        }

        /* Video preview */
        .preview-video {
            padding: 1.5rem;
            display: flex;
            justify-content: center;
        }

        .preview-video video {
            max-width: 100%;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
        }

        /* Text preview */
        .preview-text {
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            word-break: break-all;
            font-size: 0.875rem;
            line-height: 1.6;
            color: var(--foreground);
            padding: 1.5rem;
            background-color: rgba(243, 244, 246, 0.5);
            border-radius: var(--radius);
            max-height: 500px;
            overflow: auto;
        }

        .dark .preview-text {
            background-color: rgba(30, 41, 59, 0.5);
        }

        /* File details section */
        .file-details-section {
            background-color: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }

        .details-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            color: var(--foreground);
            background-color: rgba(243, 244, 246, 0.5);
        }

        .details-content {
            padding: 1.5rem;
        }

        .details-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .details-item {
            display: flex;
            flex-direction: column;
        }

        .details-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 0.25rem;
        }

        .details-value {
            font-weight: 500;
            color: var(--foreground);
        }

        /* Error state */
        .error-container {
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

        .error-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--danger-gradient);
        }

        .error-icon {
            font-size: 3rem;
            color: var(--danger);
            margin-bottom: 1.5rem;
            animation: float 6s ease-in-out infinite;
        }

        /* Theme toggle */
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

        /* Back button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            background-color: transparent;
            color: #64748b;
            font-weight: 500;
            transition: all var(--transition-fast);
            border: none;
            cursor: pointer;
            margin-bottom: 1rem;
        }

        .back-button:hover {
            background-color: rgba(243, 244, 246, 0.8);
            color: var(--foreground);
        }

        .dark .back-button:hover {
            background-color: rgba(30, 41, 59, 0.8);
            color: var(--dark-foreground);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .file-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .file-header-icon {
                margin-bottom: 1rem;
                margin-right: 0;
            }
            
            .file-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .details-list {
                grid-template-columns: 1fr;
            }
        }

        /* Confirm dialog */
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
    </style>
</head>
<body>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php require "./global-header.php"; ?>
        
        <!-- Back button -->
        <button onclick="window.location.href='./cloud.php'" class="back-button fade-in">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Files</span>
        </button>
        
        <?php if ($error_message): ?>
            <!-- Error state -->
            <div class="error-container fade-in-scale">
                <div class="error-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Error</h3>
                <p class="mb-6"><?php echo htmlspecialchars($error_message); ?></p>
                <a href="./cloud.php" class="btn btn-primary ripple">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Files
                </a>
            </div>
        <?php elseif ($file_data): ?>
            <!-- File Header -->
            <div class="file-header fade-in">
                <div class="file-header-icon <?php echo getFileIconClass($file_type); ?>">
                    <i class="fas <?php echo getFileIcon($file_type); ?>"></i>
                </div>
                <div class="file-header-info">
                    <h1 class="file-title"><?php echo htmlspecialchars($file_data['file_name']); ?></h1>
                    <div class="file-meta">
                        <div class="file-meta-item">
                            <i class="fas fa-weight file-meta-icon"></i>
                            <span><?php echo formatFileSize($file_data['file_size']); ?></span>
                        </div>
                        <div class="file-meta-item">
                            <i class="fas fa-calendar-alt file-meta-icon"></i>
                            <span>Uploaded on <?php echo formatDate($file_data['upload_date']); ?></span>
                        </div>
                        <div class="file-meta-item">
                            <i class="fas fa-file-alt file-meta-icon"></i>
                            <span><?php echo strtoupper($file_extension); ?> File</span>
                        </div>
                    </div>
                    <div class="file-actions">
                        <a href="<?php echo htmlspecialchars($file_data['file_url']); ?>" target="_blank" class="btn btn-primary ripple">
                            <i class="fas fa-download mr-2"></i>
                            Download
                        </a>
                        <button id="share-btn" class="btn btn-secondary ripple">
                            <i class="fas fa-share-alt mr-2"></i>
                            Share
                        </button>
                        <button id="delete-btn" class="btn btn-danger ripple">
                            <i class="fas fa-trash-alt mr-2"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (isPreviewable($file_type, $file_extension)): ?>
                <!-- File Preview -->
                <div class="file-preview fade-in" style="animation-delay: 0.2s">
                    <div class="preview-header">
                        <div class="preview-title">
                            <i class="fas fa-eye"></i>
                            <span>Preview</span>
                        </div>
                        <a href="<?php echo htmlspecialchars($file_data['file_url']); ?>" target="_blank" class="btn btn-icon btn-icon-sm btn-secondary tooltip" data-tooltip="Open in new tab">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    
                    <?php if ($file_type === 'image'): ?>
                        <div class="preview-image">
                            <img src="<?php echo htmlspecialchars($file_data['file_url']); ?>" alt="<?php echo htmlspecialchars($file_data['file_name']); ?>" class="fade-in-scale" />
                        </div>
                    <?php elseif ($file_type === 'audio'): ?>
                        <div class="preview-audio">
                            <div class="audio-visualizer">
                                <div class="audio-wave" id="audio-wave">
                                    <?php for ($i = 0; $i < 50; $i++): ?>
                                        <div class="audio-bar" style="height: <?php echo rand(5, 50); ?>px;"></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <audio controls class="w-full" id="audio-player">
                                <source src="<?php echo htmlspecialchars($file_data['file_url']); ?>" type="audio/<?php echo $file_extension; ?>">
                                Your browser does not support the audio element.
                            </audio>
                        </div>
                    <?php elseif ($file_type === 'video'): ?>
                        <div class="preview-video">
                            <video controls class="w-full">
                                <source src="<?php echo htmlspecialchars($file_data['file_url']); ?>" type="video/<?php echo $file_extension; ?>">
                                Your browser does not support the video element.
                            </video>
                        </div>
                    <?php elseif (in_array($file_extension, ['txt', 'md', 'css', 'js', 'html', 'xml', 'json', 'csv'])): ?>
                        <div class="preview-content">
                            <pre id="text-preview" class="preview-text">Loading content...</pre>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- File Details -->
            <div class="file-details-section fade-in" style="animation-delay: 0.3s">
                <div class="details-header">
                    <i class="fas fa-info-circle mr-2"></i>
                    File Details
                </div>
                <div class="details-content">
                    <div class="details-list">
                        <div class="details-item">
                            <div class="details-label">File Name</div>
                            <div class="details-value"><?php echo htmlspecialchars($file_data['file_name']); ?></div>
                        </div>
                        <div class="details-item">
                            <div class="details-label">File Size</div>
                            <div class="details-value"><?php echo formatFileSize($file_data['file_size']); ?></div>
                        </div>
                        <div class="details-item">
                            <div class="details-label">File Type</div>
                            <div class="details-value"><?php echo strtoupper($file_extension); ?></div>
                        </div>
                        <div class="details-item">
                            <div class="details-label">Upload Date</div>
                            <div class="details-value"><?php echo formatDate($file_data['upload_date']); ?></div>
                        </div>
                        <?php if (isset($file_data['mime_type']) && $file_data['mime_type']): ?>
                            <div class="details-item">
                                <div class="details-label">MIME Type</div>
                                <div class="details-value"><?php echo htmlspecialchars($file_data['mime_type']); ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="details-item">
                            <div class="details-label">File URL</div>
                            <div class="details-value truncate">
                                <a href="<?php echo htmlspecialchars($file_data['file_url']); ?>" target="_blank" class="text-primary hover:underline">
                                    <?php echo htmlspecialchars($file_data['file_url']); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- No file selected -->
            <div class="error-container fade-in-scale">
                <div class="error-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">No File Selected</h3>
                <p class="mb-6">Please select a file to view its details.</p>
                <a href="./cloud.php" class="btn btn-primary ripple">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Files
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Toast Container -->
        <div class="toast-container" id="toast-container">
            <!-- Toasts will be dynamically inserted here -->
        </div>
        
        <!-- Hidden textarea for clipboard operations -->
        <textarea id="clipboard-textarea" class="hidden"></textarea>
        
        <?php require "./global-footer.php";?>
    </div>
    
    <script>
        // ===== Theme Management =====
        const themeToggle = document.getElementById('theme-toggle');
        let isDarkMode = false;
        
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
        
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                toggleDarkMode();
            });
        }
        
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
            }
            isDarkMode = true;
        }
        
        function disableDarkMode() {
            document.body.classList.remove('dark');
            if (themeToggle) {
                themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
            isDarkMode = false;
        }
        
        // ===== File Actions =====
        const deleteBtn = document.getElementById('delete-btn');
        const shareBtn = document.getElementById('share-btn');
        
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                showConfirmDialog('Delete File', 'Are you sure you want to delete this file? This action cannot be undone.', () => {
                    window.location.href = '?id=<?php echo $file_id; ?>&action=delete';
                });
            });
        }
        
        if (shareBtn) {
            shareBtn.addEventListener('click', () => {
                const fileUrl = '<?php echo $file_data ? $file_data['file_url'] : ''; ?>';
                copyToClipboard(fileUrl);
                showToast('success', 'Link Copied', 'File link has been copied to clipboard');
            });
        }
        
        // ===== Text Preview =====
        const textPreview = document.getElementById('text-preview');
        if (textPreview) {
            const fileUrl = '<?php echo $file_data ? $file_data['file_url'] : ''; ?>';
            
            fetch(fileUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    textPreview.textContent = text;
                })
                .catch(error => {
                    textPreview.textContent = 'Error loading file content: ' + error.message;
                    textPreview.classList.add('text-red-500');
                });
        }
        
        // ===== Audio Visualizer =====
        const audioPlayer = document.getElementById('audio-player');
        const audioWave = document.getElementById('audio-wave');
        
        if (audioPlayer && audioWave) {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const analyser = audioContext.createAnalyser();
                const source = audioContext.createMediaElementSource(audioPlayer);
                
                source.connect(analyser);
                analyser.connect(audioContext.destination);
                
                analyser.fftSize = 256;
                const bufferLength = analyser.frequencyBinCount;
                const dataArray = new Uint8Array(bufferLength);
                
                const audioBars = audioWave.querySelectorAll('.audio-bar');
                
                function updateVisualizer() {
                    requestAnimationFrame(updateVisualizer);
                    
                    analyser.getByteFrequencyData(dataArray);
                    
                    // Only update visualizer if audio is playing
                    if (!audioPlayer.paused) {
                        let index = 0;
                        const step = Math.floor(bufferLength / audioBars.length);
                        
                        for (let i = 0; i < audioBars.length; i++) {
                            index = i * step;
                            const value = dataArray[index] / 255;
                            const height = Math.max(5, value * 100);
                            audioBars[i].style.height = `${height}px`;
                        }
                    }
                }
                
                audioPlayer.addEventListener('play', () => {
                    // Resume audio context if it was suspended
                    if (audioContext.state === 'suspended') {
                        audioContext.resume();
                    }
                    updateVisualizer();
                });
                
            } catch (error) {
                console.error('Audio visualizer error:', error);
                // Fallback to static bars if Web Audio API is not supported
                const audioBars = audioWave.querySelectorAll('.audio-bar');
                
                audioPlayer.addEventListener('play', () => {
                    setInterval(() => {
                        if (!audioPlayer.paused) {
                            audioBars.forEach(bar => {
                                const height = Math.floor(Math.random() * 50) + 5;
                                bar.style.height = `${height}px`;
                            });
                        }
                    }, 100);
                });
            }
        }
        
        // ===== Helper Functions =====
        function showToast(type, title, message) {
            const toastContainer = document.getElementById('toast-container');
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
        
        function copyToClipboard(text) {
            const clipboardTextarea = document.getElementById('clipboard-textarea');
            
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
    </script>
</body>
</html>
