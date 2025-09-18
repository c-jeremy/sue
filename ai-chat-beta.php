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
    $_SESSION['group_id'] = $_GET['group_id'];
}

// Pass these values to JavaScript
$apiBot = $_SESSION['bot'] ?? '';
$groupId = $_SESSION['group_id'] ?? '';
$userId = $ares ?? '';
$token = $res ?? '';

// CSRF 保护
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$route = $_GET['route'] ?? 'index';
$conversation_id = filter_input(INPUT_GET, 'conversation_id', FILTER_SANITIZE_STRING);
$error = null;
$success = null;

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
    <style>
        /* Base styles and CSS variables */
        :root {
            --primary-hue: 250;
            --primary-color: hsl(var(--primary-hue), 90%, 65%);
            --primary-dark: hsl(var(--primary-hue), 80%, 45%);
            --primary-light: hsl(var(--primary-hue), 90%, 75%);
            --primary-gradient: linear-gradient(135deg, hsl(var(--primary-hue), 90%, 65%), hsl(calc(var(--primary-hue) + 40), 90%, 65%));
            
            --secondary-hue: 170;
            --secondary-color: hsl(var(--secondary-hue), 80%, 55%);
            --secondary-dark: hsl(var(--secondary-hue), 70%, 45%);
            --secondary-light: hsl(var(--secondary-hue), 80%, 75%);
            
            --accent-hue: 330;
            --accent-color: hsl(var(--accent-hue), 80%, 65%);
            
            --success-color: hsl(150, 80%, 45%);
            --warning-color: hsl(40, 90%, 55%);
            --danger-color: hsl(0, 80%, 60%);
            
            --bg-light: hsl(var(--primary-hue), 30%, 98%);
            --bg-dark: hsl(var(--primary-hue), 20%, 15%);
            --bg-gradient: linear-gradient(135deg, hsl(var(--primary-hue), 30%, 98%), hsl(calc(var(--primary-hue) + 20), 30%, 96%));
            
            --text-primary: hsl(var(--primary-hue), 20%, 20%);
            --text-secondary: hsl(var(--primary-hue), 15%, 40%);
            --text-tertiary: hsl(var(--primary-hue), 10%, 60%);
            --text-light: hsl(var(--primary-hue), 10%, 98%);
            
            --glass-bg: hsla(var(--primary-hue), 30%, 98%, 0.7);
            --glass-border: hsla(var(--primary-hue), 30%, 90%, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
            
            --shadow-sm: 0 2px 8px -1px hsla(var(--primary-hue), 36%, 10%, 0.1), 
                         0 2px 4px -1px hsla(var(--primary-hue), 36%, 10%, 0.06);
            --shadow-md: 0 4px 16px -2px hsla(var(--primary-hue), 36%, 10%, 0.1), 
                         0 2px 8px -2px hsla(var(--primary-hue), 36%, 10%, 0.05);
            --shadow-lg: 0 10px 25px -3px hsla(var(--primary-hue), 36%, 10%, 0.1), 
                         0 4px 12px -4px hsla(var(--primary-hue), 36%, 10%, 0.05);
            --shadow-xl: 0 20px 40px -4px hsla(var(--primary-hue), 36%, 10%, 0.15), 
                         0 8px 16px -6px hsla(var(--primary-hue), 36%, 10%, 0.1);
            
            --radius-sm: 0.375rem;
            --radius-md: 0.75rem;
            --radius-lg: 1.25rem;
            --radius-xl: 2rem;
            --radius-full: 9999px;
            
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 300ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-bounce: 500ms cubic-bezier(0.34, 1.56, 0.64, 1);
            
            --font-sans: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            
            --blur-sm: 4px;
            --blur-md: 8px;
            --blur-lg: 16px;
            
            --z-dropdown: 1000;
            --z-sticky: 1100;
            --z-fixed: 1200;
            --z-modal: 1300;
            --z-popover: 1400;
            --z-tooltip: 1500;
        }

        /* Global styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            font-size: 16px;
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-sans);
            background: var(--bg-gradient) fixed;
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.03'%3E%3Cpath opacity='.5' d='M96 95h4v1h-4v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9zm-1 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9z'/%3E%3Cpath d='M6 5V0H5v5H0v1h5v94h1V6h94V5H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            z-index: -1;
        }

        /* Animated background elements */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
            opacity: 0.4;
        }

        .bg-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(var(--blur-lg));
        }

        .bg-shape-1 {
            top: 20%;
            left: 10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, hsla(var(--primary-hue), 90%, 70%, 0.4), transparent 70%);
            animation: float 20s ease-in-out infinite;
        }

        .bg-shape-2 {
            bottom: 10%;
            right: 15%;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, hsla(var(--secondary-hue), 90%, 70%, 0.3), transparent 70%);
            animation: float 15s ease-in-out infinite reverse;
        }

        .bg-shape-3 {
            top: 60%;
            left: 30%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, hsla(var(--accent-hue), 90%, 70%, 0.3), transparent 70%);
            animation: float 18s ease-in-out infinite 2s;
        }

        /* Animations */
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(-20px, 20px) rotate(5deg); }
            50% { transform: translate(10px, -15px) rotate(-5deg); }
            75% { transform: translate(15px, 10px) rotate(3deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes wave {
            0% { transform: translateY(0); }
            25% { transform: translateY(-5px); }
            50% { transform: translateY(0); }
            75% { transform: translateY(5px); }
            100% { transform: translateY(0); }
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @keyframes ripple {
            0% { transform: scale(0); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }

        @keyframes cursorBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        /* Glassmorphism components */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--blur-md));
            -webkit-backdrop-filter: blur(var(--blur-md));
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--blur-md));
            -webkit-backdrop-filter: blur(var(--blur-md));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--glass-shadow);
            transition: all var(--transition-normal);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        /* Layout components */
        .app-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            width: 100%;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .app-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2.5rem;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            position: relative;
            overflow: hidden;
        }

        .app-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            opacity: 0.05;
            z-index: -1;
        }

        .app-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-icon {
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius-full);
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .logo-icon::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 70%);
            opacity: 0;
            transition: opacity var(--transition-normal);
        }

        .logo-icon:hover::after {
            opacity: 1;
            animation: pulse var(--transition-slow) infinite;
        }

        .logo-text {
            font-size: 1.75rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            position: relative;
        }

        .logo-text::after {
            content: attr(data-text);
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            opacity: 0.5;
            filter: blur(8px);
            z-index: -1;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: var(--radius-md);
            transition: all var(--transition-normal);
            cursor: pointer;
            outline: none;
            border: none;
            position: relative;
            overflow: hidden;
            font-family: var(--font-sans);
            gap: 0.5rem;
            z-index: 1;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(255,255,255,0.1), rgba(255,255,255,0));
            z-index: -1;
            opacity: 0;
            transition: opacity var(--transition-fast);
        }

        .btn:hover::before {
            opacity: 1;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .btn:active::after {
            animation: ripple 600ms ease-out;
        }

        .btn-primary {
            background: var(--primary-gradient);
            background-size: 200% 200%;
            animation: gradientShift 5s ease infinite;
            color: white;
            box-shadow: 0 4px 15px rgba(var(--primary-hue), 90%, 55%, 0.4);
        }

        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(var(--primary-hue), 90%, 55%, 0.6);
            transform: translateY(-2px);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(var(--primary-hue), 90%, 55%, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            color: var(--text-primary);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(var(--primary-hue), 20%, 90%, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .btn-secondary:active {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), hsl(350, 80%, 60%));
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }

        .btn-danger:hover {
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.6);
            transform: translateY(-2px);
        }

        .btn-icon {
            padding: 0.75rem;
            border-radius: var(--radius-full);
        }

        .btn-floating {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 4rem;
            height: 4rem;
            border-radius: var(--radius-full);
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-bounce);
            z-index: var(--z-fixed);
        }

        .btn-floating:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: var(--shadow-xl);
        }

        .btn-floating svg {
            width: 1.5rem;
            height: 1.5rem;
            transition: transform var(--transition-normal);
        }

        .btn-floating:hover svg {
            transform: rotate(45deg);
        }

        /* Conversation list */
        .conversations-container {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            margin-bottom: 3rem;
        }

        .conversation-card {
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .conversation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary-gradient);
            opacity: 0;
            transition: opacity var(--transition-normal);
        }

        .conversation-card:hover::before {
            opacity: 1;
        }

        .conversation-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            position: relative;
            z-index: 1;
        }

        .conversation-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-tertiary);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        .conversation-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: auto;
        }

        .conversation-preview {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            position: relative;
        }

        .conversation-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 50%;
            height: 1.5rem;
            background: linear-gradient(to right, transparent, var(--glass-bg));
        }

        /* Chat interface */
        .chat-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 12rem);
            max-height: 800px;
            border-radius: var(--radius-xl);
            overflow: hidden;
            position: relative;
            animation: fadeInScale var(--transition-normal);
        }

        .chat-header {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--blur-md));
            -webkit-backdrop-filter: blur(var(--blur-md));
            border-bottom: 1px solid var(--glass-border);
            z-index: 10;
        }

        .chat-title {
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chat-title-dot {
            width: 0.5rem;
            height: 0.5rem;
            background-color: var(--secondary-color);
            border-radius: 50%;
            display: inline-block;
            animation: blink 2s infinite;
        }

        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            scroll-behavior: smooth;
            background: rgba(255, 255, 255, 0.5);
            position: relative;
        }

        .chat-messages::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%239C92AC' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: -1;
        }

        .message {
            max-width: 85%;
            padding: 1.25rem 1.5rem;
            border-radius: var(--radius-lg);
            position: relative;
            line-height: 1.6;
            transform-origin: center bottom;
            transition: all var(--transition-normal);
        }

        .message-user {
            align-self: flex-end;
            background: var(--primary-gradient);
            color: white;
            border-bottom-right-radius: 0;
            animation: fadeInRight var(--transition-normal);
            box-shadow: var(--shadow-md);
        }

        .message-user::before {
            content: '';
            position: absolute;
            bottom: 0;
            right: -0.75rem;
            width: 0.75rem;
            height: 0.75rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color) 50%, transparent 50%, transparent 100%);
            transform: rotate(-45deg);
        }

        .message-ai {
            align-self: flex-start;
            background: var(--glass-bg);
            color: var(--text-primary);
            border-bottom-left-radius: 0;
            animation: fadeInLeft var(--transition-normal);
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(var(--blur-md));
            -webkit-backdrop-filter: blur(var(--blur-md));
            border: 1px solid var(--glass-border);
        }

        .message-ai::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: -0.75rem;
            width: 0.75rem;
            height: 0.75rem;
            background: linear-gradient(225deg, var(--glass-bg) 0%, var(--glass-bg) 50%, transparent 50%, transparent 100%);
            transform: rotate(45deg);
            border-left: 1px solid var(--glass-border);
            border-bottom: 1px solid var(--glass-border);
        }

        .message-content {
            white-space: pre-wrap;
            word-break: break-word;
            position: relative;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.75rem;
            text-align: right;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.25rem;
        }

        .message-file {
            margin-top: 0.75rem;
            padding: 0.75rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            transition: all var(--transition-normal);
        }

        .message-file:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .message-file-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .message-file-info {
            flex-grow: 1;
            overflow: hidden;
        }

        .message-file-name {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .message-file-size {
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .message-file a {
            color: inherit;
            text-decoration: none;
            display: flex;
            align-items: center;
            width: 100%;
        }

        /* Typing indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            background: var(--glass-bg);
            border-radius: var(--radius-lg);
            width: fit-content;
            margin-top: 0.5rem;
            animation: fadeIn var(--transition-normal);
            backdrop-filter: blur(var(--blur-md));
            -webkit-backdrop-filter: blur(var(--blur-md));
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-sm);
            align-self: flex-start;
        }

        .typing-indicator-text {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .typing-indicator-dots {
            display: flex;
            gap: 0.25rem;
        }

        .typing-dot {
            width: 0.5rem;
            height: 0.5rem;
            background-color: var(--primary-color);
            border-radius: 50%;
            opacity: 0.7;
        }

        .typing-dot:nth-child(1) {
            animation: wave 1.5s infinite ease-in-out;
        }

        .typing-dot:nth-child(2) {
            animation: wave 1.5s infinite ease-in-out 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation: wave 1.5s infinite ease-in-out 0.4s;
        }

        /* Chat input */
        .chat-input-container {
            padding: 1.25rem 1.5rem;
            background: var(--glass-bg);
            backdrop-filter: blur(var(--blur-md));
            -webkit-backdrop-filter: blur(var(--blur-md));
            border-top: 1px solid var(--glass-border);
            z-index: 10;
            position: relative;
        }

        .chat-input-wrapper {
            display: flex;
            align-items: flex-end;
            gap: 0.75rem;
            position: relative;
            background: white;
            border-radius: var(--radius-lg);
            padding: 0.5rem;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
        }

        .chat-input-wrapper:focus-within {
            box-shadow: var(--shadow-md), 0 0 0 3px rgba(var(--primary-hue), 90%, 70%, 0.2);
        }

        .chat-textarea {
            flex-grow: 1;
            padding: 0.75rem;
            border-radius: var(--radius-md);
            border: none;
            resize: none;
            min-height: 60px;
            max-height: 150px;
            outline: none;
            font-family: var(--font-sans);
            line-height: 1.5;
            background: transparent;
            color: var(--text-primary);
        }

        .chat-actions {
            display: flex;
            gap: 0.5rem;
        }

        .chat-upload {
            padding: 0.75rem;
            background: transparent;
            color: var(--text-tertiary);
            border-radius: var(--radius-full);
            transition: all var(--transition-normal);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
        }

        .chat-upload:hover {
            color: var(--primary-color);
            background: rgba(var(--primary-hue), 90%, 70%, 0.1);
        }

        .chat-submit {
            padding: 0.75rem 1.25rem;
            background: var(--primary-gradient);
            color: white;
            border-radius: var(--radius-full);
            font-weight: 500;
            transition: all var(--transition-normal);
            cursor: pointer;
            border: none;
            outline: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(var(--primary-hue), 90%, 55%, 0.4);
        }

        .chat-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(var(--primary-hue), 90%, 55%, 0.6);
        }

        .chat-submit:disabled {
            background: linear-gradient(135deg, #ccc, #aaa);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Form styles */
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            animation: fadeInScale var(--transition-normal);
        }

        .form-card {
            padding: 2.5rem;
            border-radius: var(--radius-xl);
            background: var(--glass-bg);
            backdrop-filter: blur(var(--blur-md));
            -webkit-backdrop-filter: blur(var(--blur-md));
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary-gradient);
        }

        .form-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--text-primary);
            position: relative;
            display: inline-block;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: -0.5rem;
            left: 0;
            width: 50%;
            height: 3px;
            background: var(--primary-gradient);
            border-radius: var(--radius-full);
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--glass-border);
            outline: none;
            transition: all var(--transition-normal);
            font-family: var(--font-sans);
            background: rgba(255, 255, 255, 0.8);
            color: var(--text-primary);
        }

        .form-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-hue), 90%, 70%, 0.2);
            background: white;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2.5rem;
        }

        /* Alerts */
        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            animation: fadeIn var(--transition-normal);
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: hsl(0, 70%, 45%);
        }

        .alert-error::before {
            background: var(--danger-color);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: hsl(160, 80%, 30%);
        }

        .alert-success::before {
            background: var(--success-color);
        }

        .alert-icon {
            flex-shrink: 0;
        }

        .alert-content {
            flex-grow: 1;
        }

        .alert-close {
            background: transparent;
            border: none;
            color: currentColor;
            opacity: 0.7;
            cursor: pointer;
            transition: opacity var(--transition-fast);
        }

        .alert-close:hover {
            opacity: 1;
        }

        /* Loading states */
        .loading-spinner {
            width: 1.5rem;
            height: 1.5rem;
            border: 3px solid rgba(var(--primary-hue), 90%, 70%, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            text-align: center;
            color: var(--text-secondary);
        }

        .loading-pulse {
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            background: var(--primary-gradient);
            margin-bottom: 1.5rem;
            position: relative;
            animation: pulse 2s infinite;
        }

        .loading-pulse::after {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            opacity: 0;
            animation: ripple 2s infinite;
        }

        .loading-text {
            font-weight: 500;
            margin-top: 1rem;
            position: relative;
        }

        .loading-text::after {
            content: '...';
            position: absolute;
            animation: blink 1.5s infinite;
        }

        .skeleton {
            background: linear-gradient(90deg, rgba(var(--primary-hue), 20%, 95%, 0.8) 25%, rgba(var(--primary-hue), 20%, 90%, 0.8) 50%, rgba(var(--primary-hue), 20%, 95%, 0.8) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: var(--radius-md);
        }

        /* Code blocks */
        pre {
            position: relative;
            background-color: #1E293B;
            color: #E2E8F0;
            padding: 1.25rem;
            border-radius: var(--radius-md);
            overflow-x: auto;
            margin: 1rem 0;
            font-family: var(--font-mono);
            font-size: 0.875rem;
            line-height: 1.7;
        }

        pre::before {
            content: attr(data-language);
            position: absolute;
            top: 0;
            right: 0;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            border-bottom-left-radius: var(--radius-md);
            color: rgba(255, 255, 255, 0.7);
            font-family: var(--font-sans);
        }

        .copy-button {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            border-radius: var(--radius-sm);
            transition: all var(--transition-normal);
            opacity: 0;
            transform: translateY(-5px);
        }

        pre:hover .copy-button {
            opacity: 1;
            transform: translateY(0);
        }

        .copy-button:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .copy-button:active {
            transform: scale(0.95);
        }

        .copy-button.copied {
            background: var(--success-color);
        }

        /* Utilities */
        .hidden {
            display: none;
        }

        .flex {
            display: flex;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .text-center {
            text-align: center;
        }

        .w-full {
            width: 100%;
        }

        .opacity-70 {
            opacity: 0.7;
        }

        .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Responsive styles */
        @media (max-width: 1024px) {
            .app-container {
                padding: 1.5rem;
            }

            .conversations-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .app-container {
                padding: 1rem;
            }

            .app-header {
                margin-bottom: 2rem;
                padding: 0.75rem 1rem;
            }

            .logo-icon {
                width: 2.5rem;
                height: 2.5rem;
                font-size: 1.25rem;
            }

            .logo-text {
                font-size: 1.5rem;
            }

            .chat-container {
                height: calc(100vh - 10rem);
            }

            .message {
                max-width: 90%;
            }

            .form-card {
                padding: 2rem;
            }
        }

        @media (max-width: 640px) {
            .conversations-container {
                grid-template-columns: 1fr;
            }

            .conversation-actions {
                flex-wrap: wrap;
            }

            .chat-input-wrapper {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }

            .chat-actions {
                display: flex;
                justify-content: space-between;
            }

            .form-actions {
                flex-direction: column;
                gap: 1rem;
            }

            .form-actions .btn {
                width: 100%;
            }

            .btn-floating {
                width: 3.5rem;
                height: 3.5rem;
                bottom: 1.5rem;
                right: 1.5rem;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-light: hsl(var(--primary-hue), 20%, 10%);
                --bg-dark: hsl(var(--primary-hue), 20%, 5%);
                --bg-gradient: linear-gradient(135deg, hsl(var(--primary-hue), 20%, 10%), hsl(calc(var(--primary-hue) + 20), 20%, 8%));
                
                --text-primary: hsl(var(--primary-hue), 10%, 90%);
                --text-secondary: hsl(var(--primary-hue), 10%, 70%);
                --text-tertiary: hsl(var(--primary-hue), 10%, 50%);
                
                --glass-bg: hsla(var(--primary-hue), 20%, 15%, 0.7);
                --glass-border: hsla(var(--primary-hue), 20%, 25%, 0.5);
            }

            .btn-secondary {
                background: rgba(255, 255, 255, 0.1);
                color: var(--text-primary);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .btn-secondary:hover {
                background: rgba(255, 255, 255, 0.15);
            }

            .form-input {
                background: rgba(255, 255, 255, 0.1);
                color: var(--text-primary);
                border-color: rgba(255, 255, 255, 0.1);
            }

            .form-input:focus {
                background: rgba(255, 255, 255, 0.15);
            }

            .chat-input-wrapper {
                background: rgba(255, 255, 255, 0.05);
            }

            .chat-textarea {
                color: var(--text-primary);
            }

            .message-ai {
                background: var(--glass-bg);
                color: var(--text-primary);
            }

            .message-ai::before {
                background: linear-gradient(225deg, var(--glass-bg) 0%, var(--glass-bg) 50%, transparent 50%, transparent 100%);
            }

            .skeleton {
                background: linear-gradient(90deg, rgba(255, 255, 255, 0.05) 25%, rgba(255, 255, 255, 0.1) 50%, rgba(255, 255, 255, 0.05) 75%);
            }
        }

        /* Animations for page transitions */
        .page-transition-enter {
            opacity: 0;
            transform: translateY(20px);
        }

        .page-transition-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 300ms, transform 300ms;
        }

        .page-transition-exit {
            opacity: 1;
            transform: translateY(0);
        }

        .page-transition-exit-active {
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 300ms, transform 300ms;
        }

        /* Particle effect */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.5;
            animation-name: float-particle;
            animation-timing-function: ease-in-out;
            animation-iteration-count: infinite;
            animation-direction: alternate;
        }

        @keyframes float-particle {
            0% {
                transform: translateY(0) translateX(0);
            }
            100% {
                transform: translateY(var(--float-y)) translateX(var(--float-x));
            }
        }
    </style>
</head>

<body>
    <input type="text" id="textToCopy" hidden value="逆天就逆天吧，你能有什么办法">
    
    <!-- Background shapes -->
    <div class="bg-shapes">
        <div class="bg-shape bg-shape-1"></div>
        <div class="bg-shape bg-shape-2"></div>
        <div class="bg-shape bg-shape-3"></div>
    </div>
    
    <!-- Particle effect -->
    <div class="particles" id="particles"></div>
    
    <div class="app-container">
        <!-- App Header -->
        <header class="app-header glass-card">
            <div class="app-logo">
                <div class="logo-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        <path d="M9 11h.01"></path>
                        <path d="M12 11h.01"></path>
                        <path d="M15 11h.01"></path>
                    </svg>
                </div>
                <h1 class="logo-text" data-text="AI 对话系统">AI 对话系统</h1>
            </div>
            <a href="AI-gallery.php" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                返回主页
            </a>
        </header>

        <!-- Alerts -->
        <div id="errorAlert" class="alert alert-error hidden">
            <div class="alert-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="alert-content">
                <span id="errorMessage"></span>
            </div>
            <button class="alert-close" id="closeErrorAlert">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2  stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div id="successAlert" class="alert alert-success hidden">
            <div class="alert-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="alert-content">
                <span id="successMessage"></span>
            </div>
            <button class="alert-close" id="closeSuccessAlert">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Conversation List -->
        <?php if ($route == 'index'): ?>
            <div class="flex items-center justify-between mb-4">
                <h1 class="logo-text" data-text="对话列表">对话列表</h1>
                <a href="?route=create" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    创建新对话
                </a>
            </div>
            
            <div id="conversationsList" class="conversations-container">
                <div class="loading-container">
                    <div class="loading-pulse"></div>
                    <div class="loading-text">加载对话列表中</div>
                </div>
            </div>
            
            <div class="flex items-center justify-between text-sm opacity-70 mt-4">
                <span>Total conversations: <span id="conversationCount">0</span></span>
                <a href="AI-gallery.php" class="btn btn-secondary btn-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </a>
            </div>
        <?php endif; ?>

        <!-- Create Conversation Form -->
        <?php if ($route == 'create'): ?>
            <div class="form-container">
                <div class="form-card">
                    <h1 class="form-title">创建新对话</h1>
                    <form id="createConversationForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="form-group">
                            <label class="form-label" for="title">对话标题</label>
                            <input class="form-input" id="title" type="text" name="title" placeholder="输入对话标题" required>
                        </div>
                        <div class="form-actions">
                            <button id="createButton" class="btn btn-primary" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                创建对话
                            </button>
                            <a class="btn btn-secondary" href="?route=index">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="19" y1="12" x2="5" y2="12"></line>
                                    <polyline points="12 19 5 12 12 5"></polyline>
                                </svg>
                                返回列表
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Chat Interface -->
        <?php if ($route == 'chat' && $conversation_id): ?>
            <div class="chat-container glass-card">
                <div class="chat-header">
                    <div class="chat-title">
                        <span class="chat-title-dot"></span>
                        <span id="chatTitle">AI 对话</span>
                    </div>
                    <a href="?route=index" class="btn btn-secondary btn-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                    </a>
                </div>
                
                <div id="chat" class="chat-messages">
                    <div class="loading-container">
                        <div class="loading-pulse"></div>
                        <div class="loading-text">加载消息中</div>
                    </div>
                </div>
                
                <div class="chat-input-container">
                    <form id="chatForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="chat-input-wrapper">
                            <a href="/ups.php?redirect=<?php
                            $scriptName = $_SERVER['PHP_SELF'];
                            $queryString = $_SERVER['QUERY_STRING'];
                            $relativeUrl = $scriptName;
                            if (!empty($queryString)) {
                                $relativeUrl .= '?' . $queryString;
                            }
                            echo urlencode($relativeUrl);
                            ?>" class="chat-upload">
                                <?php if($_REQUEST["file"]): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                    <polyline points="13 2 13 9 20 9"></polyline>
                                </svg>
                                <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <?php endif; ?>
                            </a>
                            <textarea name="message" id="messageInput" placeholder="请输入您的问题..." required class="chat-textarea"></textarea>
                            <button id="msgSubmitButton" type="submit" class="chat-submit">
                                <span>发送</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Floating action button for mobile -->
            <div class="btn-floating" id="scrollToBottom">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <polyline points="19 12 12 19 5 12"></polyline>
                </svg>
            </div>
        <?php endif; ?>
    </div>

    <script src="hl.js"></script>
    <script>
        // Store API configuration
        const apiConfig = {
            bot: '<?= $apiBot ?>',
            groupId: '<?= $groupId ?>',
            userId: '<?= $userId ?>',
            token: '<?= $token ?>',
            baseUrl: 'https://api.seiue.com/ais/teacher-bot/teacher-bots/'
        };

        // Create particle effect
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = window.innerWidth < 768 ? 15 : 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random properties
                const size = Math.random() * 5 + 2;
                const hue = Math.random() * 60 - 30 + parseInt(getComputedStyle(document.documentElement).getPropertyValue('--primary-hue'));
                const opacity = Math.random() * 0.3 + 0.1;
                
                // Random position
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                
                // Random float animation
                const floatX = (Math.random() * 60 - 30) + 'px';
                const floatY = (Math.random() * 60 - 30) + 'px';
                const duration = Math.random() * 10 + 10;
                
                // Apply styles
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.backgroundColor = `hsla(${hue}, 90%, 70%, ${opacity})`;
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                particle.style.setProperty('--float-x', floatX);
                particle.style.setProperty('--float-y', floatY);
                particle.style.animationDuration = `${duration}s`;
                
                particlesContainer.appendChild(particle);
            }
        }

        // API call function
        async function callApi(url, method = 'GET', data = null) {
            const fullUrl = apiConfig.baseUrl + apiConfig.bot + url;
            
            const options = {
                method: method,
                headers: {
                    'accept': 'application/json',
                    'authorization': 'Bearer ' + apiConfig.token,
                    'x-reflection-id': apiConfig.userId
                }
            };
            
            if (method === 'POST' && data) {
                options.headers['content-type'] = 'application/json';
                options.body = JSON.stringify(data);
            }
            
            try {
                const response = await fetch(fullUrl, options);
                
                if (response.ok) {
                    return await response.json();
                } else {
                    throw new Error('API request failed with status: ' + response.status);
                }
            } catch (error) {
                console.error('API call error:', error);
                showError('API请求失败: ' + error.message);
                return false;
            }
        }

        // Helper functions for UI
        function showError(message) {
            const errorAlert = document.getElementById('errorAlert');
            const errorMessage = document.getElementById('errorMessage');
            
            errorMessage.textContent = message;
            errorAlert.classList.remove('hidden');
            
            // Add animation
            errorAlert.style.animation = 'none';
            errorAlert.offsetHeight; // Trigger reflow
            errorAlert.style.animation = 'fadeIn var(--transition-normal)';
        }
        
        function showSuccess(message) {
            const successAlert = document.getElementById('successAlert');
            const successMessage = document.getElementById('successMessage');
            
            successMessage.textContent = message;
            successAlert.classList.remove('hidden');
            
            // Add animation
            successAlert.style.animation = 'none';
            successAlert.offsetHeight; // Trigger reflow
            successAlert.style.animation = 'fadeIn var(--transition-normal)';
        }

        function formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        // Load conversations list with enhanced UI
        async function loadConversations() {
            if (window.location.search.includes('route=index')) {
                const conversationsListElement = document.getElementById('conversationsList');
                
                try {
                    const response = await callApi(`/conversations?expand=reminders%2Cstudent_is_viewed&group_id=${apiConfig.groupId}&page=1&per_page=30&user_id=${apiConfig.userId}`);
                    
                    if (response) {
                        const conversations = response.list || response;
                        
                        if (Array.isArray(conversations)) {
                            let html = '';
                            
                            if (conversations.length === 0) {
                                html = `
                                    <div class="glass-card" style="grid-column: 1 / -1; padding: 2rem; text-align: center;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem; opacity: 0.5;">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="8" y1="12" x2="16" y2="12"></line>
                                            <line x1="8" y1="16" x2="16" y2="16"></line>
                                            <line x1="8" y1="8" x2="10" y2="8"></line>
                                        </svg>
                                        <p style="margin-bottom: 1.5rem;">暂无对话。创建一个新的对话开始聊天吧！</p>
                                        <a href="?route=create" class="btn btn-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                            </svg>
                                            创建新对话
                                        </a>
                                    </div>
                                `;
                            } else {
                                conversations.forEach((conversation, index) => {
                                    const conversationId = conversation.external_conversation_id || conversation.id || '';
                                    const title = conversation.title || 'Untitled Conversation';
                                    
                                    // Generate a random preview text
                                    const previewTexts = [
                                        "上次我们讨论了关于人工智能的应用...",
                                        "我想了解更多关于这个主题的信息...",
                                        "这个问题很有趣，让我们继续探讨...",
                                        "根据我的理解，这个概念可以这样解释...",
                                        "我们可以从不同的角度来看待这个问题..."
                                    ];
                                    const previewText = previewTexts[Math.floor(Math.random() * previewTexts.length)];
                                    
                                    // Add animation delay based on index
                                    const delay = index * 0.05;
                                    
                                    html += `
                                        <div class="glass-card conversation-card" style="animation: fadeInScale var(--transition-normal) ${delay}s both;">
                                            <div>
                                                <h3 class="conversation-title">${title}</h3>
                                                <div class="conversation-meta">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="10"></circle>
                                                        <polyline points="12 6 12 12 16 14"></polyline>
                                                    </svg>
                                                    <span>${formatTimestamp(conversation.created_at || new Date())}</span>
                                                </div>
                                                <p class="conversation-preview">${previewText}</p>
                                            </div>
                                            <div class="conversation-actions">
                                                <button 
                                                    class="delete-conversation btn btn-danger btn-icon"
                                                    data-id="${conversation.id || ''}"
                                                    aria-label="删除对话"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                </button>
                                                <a href="?route=chat&conversation_id=${encodeURIComponent(conversationId)}" class="btn btn-primary">
                                                    <span>打开对话</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    `;
                                });
                                
                                document.getElementById('conversationCount').textContent = conversations.length;
                            }
                            
                            conversationsListElement.innerHTML = html;
                            
                            // Add event listeners for delete buttons
                            document.querySelectorAll('.delete-conversation').forEach(button => {
                                button.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    const conversationId = this.getAttribute('data-id');
                                    const conversationCard = this.closest('.conversation-card');
                                    
                                    // Create confirmation dialog with animation
                                    const confirmDialog = document.createElement('div');
                                    confirmDialog.className = 'glass-card';
                                    confirmDialog.style.position = 'absolute';
                                    confirmDialog.style.zIndex = '100';
                                    confirmDialog.style.padding = '1.5rem';
                                    confirmDialog.style.borderRadius = 'var(--radius-lg)';
                                    confirmDialog.style.width = '280px';
                                    confirmDialog.style.boxShadow = 'var(--shadow-xl)';
                                    confirmDialog.style.animation = 'fadeInScale var(--transition-normal)';
                                    
                                    confirmDialog.innerHTML = `
                                        <h3 style="margin-bottom: 1rem; font-weight: 600; color: var(--text-primary);">确认删除</h3>
                                        <p style="margin-bottom: 1.5rem; color: var(--text-secondary);">确定要删除这个对话吗？此操作无法撤销。</p>
                                        <div class="flex gap-2" style="justify-content: flex-end;">
                                            <button class="btn btn-secondary cancel-delete">取消</button>
                                            <button class="btn btn-danger confirm-delete">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                                删除
                                            </button>
                                        </div>
                                    `;
                                    
                                    // Position the dialog
                                    const rect = conversationCard.getBoundingClientRect();
                                    confirmDialog.style.top = `${rect.top + window.scrollY}px`;
                                    confirmDialog.style.left = `${rect.left + window.scrollX}px`;
                                    
                                    document.body.appendChild(confirmDialog);
                                    
                                    // Add event listeners to the dialog buttons
                                    confirmDialog.querySelector('.confirm-delete').addEventListener('click', function() {
                                        deleteConversation(conversationId, conversationCard);
                                        confirmDialog.style.animation = 'fadeOut var(--transition-normal)';
                                        setTimeout(() => {
                                            document.body.removeChild(confirmDialog);
                                        }, 300);
                                    });
                                    
                                    confirmDialog.querySelector('.cancel-delete').addEventListener('click', function() {
                                        confirmDialog.style.animation = 'fadeOut var(--transition-normal)';
                                        setTimeout(() => {
                                            document.body.removeChild(confirmDialog);
                                        }, 300);
                                    });
                                    
                                    // Close dialog when clicking outside
                                    document.addEventListener('click', function closeDialog(e) {
                                        if (!confirmDialog.contains(e.target) && e.target !== button) {
                                            confirmDialog.style.animation = 'fadeOut var(--transition-normal)';
                                            setTimeout(() => {
                                                if (document.body.contains(confirmDialog)) {
                                                    document.body.removeChild(confirmDialog);
                                                }
                                            }, 300);
                                            document.removeEventListener('click', closeDialog);
                                        }
                                    });
                                });
                            });
                        }
                    } else {
                        conversationsListElement.innerHTML = `
                            <div class="glass-card" style="grid-column: 1 / -1; padding: 2rem; text-align: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem; color: var(--danger-color);">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <p style="margin-bottom: 1rem; color: var(--danger-color);">获取对话列表失败，请稍后重试。</p>
                                <button class="btn btn-primary" onclick="loadConversations()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 2v6h-6"></path>
                                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                                        <path d="M3 22v-6h6"></path>
                                        <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                                    </svg>
                                    重试
                                </button>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('Error loading conversations:', error);
                    conversationsListElement.innerHTML = `
                        <div class="glass-card" style="grid-column: 1 / -1; padding: 2rem; text-align: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem; color: var(--danger-color);">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <p style="margin-bottom: 1rem; color: var(--danger-color);">获取对话列表失败，请稍后重试。</p>
                            <button class="btn btn-primary" onclick="loadConversations()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 2v6h-6"></path>
                                    <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                                    <path d="M3 22v-6h6"></path>
                                    <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                                </svg>
                                重试
                            </button>
                        </div>
                    `;
                }
            }
        }

        // Delete conversation with animation
        async function deleteConversation(conversationId, conversationCard) {
            if (!conversationId) {
                showError('无效的对话ID。');
                return;
            }
            
            try {
                // Add deleting animation
                conversationCard.style.transition = 'all 0.5s ease';
                conversationCard.style.transform = 'scale(0.95)';
                conversationCard.style.opacity = '0.5';
                
                const deleteUrl = `/groups/${apiConfig.groupId}/conversations/${conversationId}`;
                const response = await callApi(deleteUrl, 'DELETE');
                
                if (response !== false) {
                    // Success animation
                    conversationCard.style.transform = 'translateX(100px)';
                    conversationCard.style.opacity = '0';
                    
                    setTimeout(() => {
                        conversationCard.remove();
                        
                        // Update conversation count
                        const count = document.querySelectorAll('.conversation-card').length;
                        document.getElementById('conversationCount').textContent = count;
                        
                        // Show "no conversations" message if needed
                        if (count === 0) {
                            document.getElementById('conversationsList').innerHTML = `
                                <div class="glass-card" style="grid-column: 1 / -1; padding: 2rem; text-align: center; animation: fadeInScale var(--transition-normal);">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem; opacity: 0.5;">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="8" y1="12" x2="16" y2="12"></line>
                                        <line x1="8" y1="16" x2="16" y2="16"></line>
                                        <line x1="8" y1="8" x2="10" y2="8"></line>
                                    </svg>
                                    <p style="margin-bottom: 1.5rem;">暂无对话。创建一个新的对话开始聊天吧！</p>
                                    <a href="?route=create" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                        创建新对话
                                    </a>
                                </div>
                            `;
                        }
                    }, 500);
                    
                    showSuccess('对话已成功删除！');
                } else {
                    // Restore card on error
                    conversationCard.style.transform = '';
                    conversationCard.style.opacity = '';
                    showError('删除对话失败，请重试。');
                }
            } catch (error) {
                console.error('Error deleting conversation:', error);
                // Restore card on error
                conversationCard.style.transform = '';
                conversationCard.style.opacity = '';
                showError('删除对话失败，请重试。');
            }
        }

        // Create new conversation with enhanced animation
        async function createConversation(title) {
            if (!title) {
                showError('标题不能为空。');
                return;
            }
            
            try {
                const createButton = document.getElementById('createButton');
                createButton.disabled = true;
                
                // Show loading animation
                createButton.innerHTML = `
                    <div class="loading-spinner"></div>
                    <span>创建中...</span>
                `;
                
                const response = await callApi(`/groups/${apiConfig.groupId}/conversations`, 'POST', { title: title });
                
                if (response !== false && response.id) {
                    // Success animation
                    createButton.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span>创建成功！</span>
                    `;
                    createButton.style.backgroundColor = 'var(--success-color)';
                    
                    // Add a page transition effect
                    const formCard = document.querySelector('.form-card');
                    formCard.style.transition = 'all 0.5s ease';
                    formCard.style.transform = 'translateY(-20px)';
                    formCard.style.opacity = '0';
                    
                    // Redirect with delay for animation
                    setTimeout(() => {
                        window.location.href = `?route=chat&conversation_id=${encodeURIComponent(response.external_conversation_id)}`;
                    }, 800);
                } else {
                    showError('创建对话失败，请重试。');
                    createButton.disabled = false;
                    createButton.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        创建对话
                    `;
                }
            } catch (error) {
                console.error('Error creating conversation:', error);
                showError('创建对话失败，请重试。');
                
                const createButton = document.getElementById('createButton');
                createButton.disabled = false;
                createButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    创建对话
                `;
            }
        }

        // Parse markdown with enhanced styling
        function parseMarkdown(text) {
            // Code blocks with language detection
            text = text.replace(/\`\`\`([a-z]*)\n([\s\S]*?)\`\`\`/g, (match, language, code) => {
                return `<pre data-language="${language || 'code'}"><code class="${language}">${code.trim()}</code></pre>`;
            });
            
            // Inline code with better styling
            text = text.replace(/`([^`]+)`/g, '<code style="background-color: rgba(0,0,0,0.05); padding: 0.2em 0.4em; border-radius: 3px; font-family: var(--font-mono);">$1</code>');
            
            // Bold with animation
            text = text.replace(/\*\*([^*]+)\*\*/g, '<strong style="font-weight: 600; color: var(--text-primary);">$1</strong>');
            
            // Italic with subtle styling
            text = text.replace(/\*([^*]+)\*/g, '<em style="font-style: italic; color: var(--text-primary);">$1</em>');
            
            // Links with hover effect
            text = text.replace(/\[([^\]]+)\]$$([^)]+)$$/g, '<a href="$2" style="color: var(--primary-color); text-decoration: underline; transition: all var(--transition-fast);" target="_blank" rel="noopener noreferrer">$1</a>');
            
            // Line breaks
            text = text.replace(/\n/g, '<br>');
            
            return text;
        }

        // Add typing indicator with enhanced animation
        function addTypingIndicator() {
            const chatElement = document.getElementById('chat');
            
            // Create typing indicator element
            const typingIndicator = document.createElement('div');
            typingIndicator.className = 'typing-indicator';
            typingIndicator.id = 'typingIndicator';
            typingIndicator.innerHTML = `
                <div class="typing-indicator-text">AI 正在思考</div>
                <div class="typing-indicator-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            `;
            
            chatElement.appendChild(typingIndicator);
            chatElement.scrollTop = chatElement.scrollHeight;
            
            return typingIndicator;
        }

        // Remove typing indicator with fade out animation
        function removeTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.style.animation = 'fadeOut var(--transition-normal)';
                setTimeout(() => {
                    if (typingIndicator.parentNode) {
                        typingIndicator.parentNode.removeChild(typingIndicator);
                    }
                }, 300);
            }
        }

        // Stream message content with realistic typing effect
        function streamMessageContent(messageElement, content) {
            const contentElement = messageElement.querySelector('.message-content');
            const fullContent = parseMarkdown(content);
            
            // Create a temporary div to parse the HTML content
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = fullContent;
            const textContent = tempDiv.textContent;
            
            // Start with empty content
            contentElement.innerHTML = '';
            
            // Add cursor element
            const cursor = document.createElement('span');
            cursor.className = 'typing-cursor';
            cursor.style.display = 'inline-block';
            cursor.style.width = '2px';
            cursor.style.height = '1.2em';
            cursor.style.backgroundColor = 'currentColor';
            cursor.style.verticalAlign = 'middle';
            cursor.style.animation = 'cursorBlink 1s infinite';
            contentElement.appendChild(cursor);
            
            // Variables for typing simulation
            let index = 0;
            let isTag = false;
            let html = '';
            
            // Function to add one character at a time with realistic typing
            function addNextChar() {
                if (index < fullContent.length) {
                    // Check if we're inside an HTML tag
                    if (fullContent[index] === '<') {
                        isTag = true;
                    }
                    
                    // Append the current character
                    html += fullContent[index];
                    
                    // Check if we're exiting an HTML tag
                    if (isTag && fullContent[index] === '>') {
                        isTag = false;
                    }
                    
                    // Only update the DOM and add delay if we're not inside a tag
                    if (!isTag) {
                        contentElement.innerHTML = html;
                        contentElement.appendChild(cursor);
                        
                        // Scroll to the latest content
                        messageElement.scrollIntoView({ behavior: 'smooth', block: 'end' });
                        
                        // Random typing speed for natural effect
                        // Faster for common characters, slower for punctuation
                        let delay;
                        const char = fullContent[index];
                        if ('.!?,:;'.includes(char)) {
                            delay = Math.random() * 200 + 100; // Longer pause for punctuation
                        } else if (' \n\r\t'.includes(char)) {
                            delay = Math.random() * 100 + 50; // Medium pause for spaces and line breaks
                        } else {
                            delay = Math.random() * 30 + 10; // Short delay for normal characters
                        }
                        
                        setTimeout(addNextChar, delay);
                    } else {
                        // If we're inside a tag, continue immediately without delay
                        addNextChar();
                    }
                    
                    index++;
                } else {
                    // When done, replace with the fully formatted HTML and remove cursor
                    contentElement.innerHTML = fullContent;
                    
                    // Apply syntax highlighting to code blocks
                    if (typeof hljs !== 'undefined') {
                        messageElement.querySelectorAll('pre code').forEach((block) => {
                            hljs.highlightElement(block);
                            
                            // Add copy button
                            const pre = block.parentElement;
                            const button = document.createElement('button');
                            button.className = 'copy-button';
                            button.innerText = '复制';
                            
                            button.addEventListener('click', () => {
                                if (window.location.protocol === 'https:') {
                                    navigator.clipboard.writeText(block.innerText).then(() => {
                                        button.innerText = '已复制';
                                        button.classList.add('copied');
                                        setTimeout(() => {
                                            button.innerText = '复制';
                                            button.classList.remove('copied');
                                        }, 2000);
                                    });
                                } else if (window.location.protocol === 'http:') {
                                    var textBox = document.getElementById("textToCopy");
                                    textBox.value = block.innerText;
                                    textBox.hidden = false;
                                    textBox.select();
                                    textBox.setSelectionRange(0, 99999);
                                    document.execCommand('copy');
                                    textBox.hidden = true;
                                    button.innerText = '已复制';
                                    button.classList.add('copied');
                                    setTimeout(() => {
                                        button.innerText = '复制';
                                        button.classList.remove('copied');
                                    }, 2000);
                                }
                            });
                            
                            pre.appendChild(button);
                        });
                    }
                }
            }
            
            // Start the streaming effect
            addNextChar();
        }

        // Load messages for a conversation with enhanced UI and animations
        async function loadMessages(conversationId) {
            if (!conversationId) {
                showError('无效的对话ID。');
                return;
            }
            
            const chatElement = document.getElementById('chat');
            
            try {
                const messagesResponse = await callApi(`/messages?conversion_id=${conversationId}&order=1&page=1&size=50&user_id=${apiConfig.userId}`);
                
                if (messagesResponse !== false) {
                    const messages = messagesResponse.list || [];
                    
                    // Clear chat container
                    chatElement.innerHTML = '';
                    
                    if (messages.length === 0) {
                        chatElement.innerHTML = `
                            <div style="text-align: center; padding: 3rem; color: var(--text-secondary); animation: fadeIn var(--transition-normal);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem; opacity: 0.5;">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <p style="margin-bottom: 1rem;">没有消息。发送第一条消息开始对话吧！</p>
                                <div style="animation: bounce 2s infinite; margin-top: 2rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <polyline points="19 12 12 19 5 12"></polyline>
                                    </svg>
                                </div>
                            </div>
                        `;
                        return;
                    }
                    
                    // Update chat title if available
                    if (messages.length > 0 && messages[0].conversation_title) {
                        document.getElementById('chatTitle').textContent = messages[0].conversation_title;
                    }
                    
                    // Process messages in order (oldest first)
                    const orderedMessages = [...messages].reverse();
                    
                    // Add messages with a slight delay between each
                    for (let i = 0; i < orderedMessages.length; i++) {
                        const message = orderedMessages[i];
                        const isUser = message.role === 'user';
                        const messageClass = isUser ? 'message-user' : 'message-ai';
                        
                        // Create message element
                        const messageElement = document.createElement('div');
                        messageElement.className = `message ${messageClass}`;
                        
                        // Add animation delay based on index
                        messageElement.style.animationDelay = `${i * 0.1}s`;
                        
                        // Create message content
                        let messageHTML = `<div class="message-content"></div>`;
                        
                        // Add timestamp
                        if (message.created_at) {
                            const time = formatTimestamp(message.created_at);
                            messageHTML += `
                                <div class="message-time">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    ${time}
                                </div>
                            `;
                        }
                        
                        // Add file attachment if present
                        if (message.file && message.file.file_name) {
                            messageHTML += `
                                <div class="message-file">
                                    <a href="${message.file.file_url}" target="_blank" rel="noopener noreferrer">
                                        <div class="message-file-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                                <polyline points="13 2 13 9 20 9"></polyline>
                                            </svg>
                                        </div>
                                        <div class="message-file-info">
                                            <div class="message-file-name">附件</div>
                                            <div class="message-file-size">点击查看</div>
                                        </div>
                                    </a>
                                </div>
                            `;
                        }
                        
                        messageElement.innerHTML = messageHTML;
                        chatElement.appendChild(messageElement);
                        
                        // Stream the content with a typing effect
                        streamMessageContent(messageElement, message.content);
                        
                        // Scroll to the latest message
                        chatElement.scrollTop = chatElement.scrollHeight;
                        
                        // Add a small delay between messages
                        await new Promise(resolve => setTimeout(resolve, 300));
                    }
                } else {
                    chatElement.innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: var(--danger-color); animation: fadeIn var(--transition-normal);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <p style="margin-bottom: 1rem;">获取消息失败，请稍后重试。</p>
                            <button class="btn btn-primary" onclick="loadMessages('${conversationId}')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 2v6h-6"></path>
                                    <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                                    <path d="M3 22v-6h6"></path>
                                    <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                                </svg>
                                重试
                            </button>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                chatElement.innerHTML = `
                    <div style="text-align: center; padding: 3rem; color: var(--danger-color); animation: fadeIn var(--transition-normal);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <p style="margin-bottom: 1rem;">获取消息失败，请稍后重试。</p>
                        <button class="btn btn-primary" onclick="loadMessages('${conversationId}')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 2v6h-6"></path>
                                <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                                <path d="M3 22v-6h6"></path>
                                <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                            </svg>
                            重试
                        </button>
                    </div>
                `;
            }
        }

        // Send a message with enhanced streaming response
        async function sendMessage(conversationId, message, fileUrl = null) {
            if (!conversationId) {
                showError('无效的对话ID。');
                return false;
            }
            
            if (!message) {
                showError('无效的消息。请重试。');
                return false;
            }
            
            try {
                // Add user message to chat immediately
                const chatElement = document.getElementById('chat');
                
                // Create user message element
                const userMessageElement = document.createElement('div');
                userMessageElement.className = 'message message-user';
                
                // Add message content
                let userMessageHTML = `<div class="message-content">${parseMarkdown(message)}</div>`;
                
                // Add timestamp
                const time = formatTimestamp(new Date());
                userMessageHTML += `
                    <div class="message-time">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        ${time}
                    </div>
                `;
                
                // Add file attachment if present
                if (fileUrl) {
                    userMessageHTML += `
                        <div class="message-file">
                            <a href="${fileUrl}" target="_blank" rel="noopener noreferrer">
                                <div class="message-file-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                        <polyline points="13 2 13 9 20 9"></polyline>
                                    </svg>
                                </div>
                                <div class="message-file-info">
                                    <div class="message-file-name">附件</div>
                                    <div class="message-file-size">点击查看</div>
                                </div>
                            </a>
                        </div>
                    `;
                }
                
                userMessageElement.innerHTML = userMessageHTML;
                chatElement.appendChild(userMessageElement);
                
                // Scroll to the latest message
                chatElement.scrollTop = chatElement.scrollHeight;
                
                // Add typing indicator
                const typingIndicator = addTypingIndicator();
                
                // Prepare data for API call
                let data = { title: message };
                
                if (fileUrl) {
                    data.file = {
                        file_url: fileUrl,
                        file_type: 1,
                        file_name: "Unset",
                        file_size: 114514
                    };
                }
                
                // Send message to API
                const response = await callApi(`/groups/${apiConfig.groupId}/chat?conversion_id=${conversationId}&user_id=${apiConfig.userId}`, 'POST', data);
                
                // Remove typing indicator
                removeTypingIndicator();
                
                if (response !== false) {
                    // Fetch latest messages to get AI response
                    const messagesResponse = await callApi(`/messages?conversion_id=${conversationId}&order=1&page=1&size=2&user_id=${apiConfig.userId}`);
                    
                    if (messagesResponse !== false && messagesResponse.list && messagesResponse.list.length > 0) {
                        // Get the AI response (should be the first message)
                        const aiMessage = messagesResponse.list.find(msg => msg.role !== 'user');
                        
                        if (aiMessage) {
                            // Create AI message element
                            const aiMessageElement = document.createElement('div');
                            aiMessageElement.className = 'message message-ai';
                            
                            // Add message content (empty initially for streaming effect)
                            let aiMessageHTML = `<div class="message-content"></div>`;
                            
                            // Add timestamp
                            const aiTime = formatTimestamp(aiMessage.created_at || new Date());
                            aiMessageHTML += `
                                <div class="message-time">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    ${aiTime}
                                </div>
                            `;
                            
                            // Add file attachment if present
                            if (aiMessage.file && aiMessage.file.file_name) {
                                aiMessageHTML += `
                                    <div class="message-file">
                                        <a href="${aiMessage.file.file_url}" target="_blank" rel="noopener noreferrer">
                                            <div class="message-file-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                                    <polyline points="13 2 13 9 20 9"></polyline>
                                                </svg>
                                            </div>
                                            <div class="message-file-info">
                                                <div class="message-file-name">附件</div>
                                                <div class="message-file-size">点击查看</div>
                                            </div>
                                        </a>
                                    </div>
                                `;
                            }
                            
                            aiMessageElement.innerHTML = aiMessageHTML;
                            chatElement.appendChild(aiMessageElement);
                            
                            // Stream the content with a typing effect
                            streamMessageContent(aiMessageElement, aiMessage.content);
                            
                            // Scroll to the latest message
                            chatElement.scrollTop = chatElement.scrollHeight;
                        }
                    }
                    
                    return true;
                } else {
                    showError('发送消息失败。请重试。');
                    return false;
                }
            } catch (error) {
                console.error('Error sending message:', error);
                showError('发送消息失败。请重试。');
                return false;
            }
        }

        // Auto-resize textarea with enhanced behavior
        function autoResizeTextarea(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(150, Math.max(60, textarea.scrollHeight)) + 'px';
        }

        // Initialize the page with enhanced animations
        document.addEventListener('DOMContentLoaded', function() {
            // Create particle effect
            createParticles();
            
            // Setup alert close buttons
            document.getElementById('closeErrorAlert')?.addEventListener('click', function() {
                document.getElementById('errorAlert').classList.add('hidden');
            });
            
            document.getElementById('closeSuccessAlert')?.addEventListener('click', function() {
                document.getElementById('successAlert').classList.add('hidden');
            });
            
            // Load conversations list
            loadConversations();
            
            // Load messages if on chat page
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('route') === 'chat') {
                const conversationId = urlParams.get('conversation_id');  === 'chat') {
                const conversationId = urlParams.get('conversation_id');
                if (conversationId) {
                    loadMessages(conversationId);
                    
                    // Setup scroll to bottom button
                    const scrollToBottomBtn = document.getElementById('scrollToBottom');
                    scrollToBottomBtn.addEventListener('click', function() {
                        const chatElement = document.getElementById('chat');
                        chatElement.scrollTop = chatElement.scrollHeight;
                    });
                    
                    // Hide scroll button when at bottom
                    const chatElement = document.getElementById('chat');
                    chatElement.addEventListener('scroll', function() {
                        const isAtBottom = chatElement.scrollHeight - chatElement.scrollTop <= chatElement.clientHeight + 100;
                        scrollToBottomBtn.style.opacity = isAtBottom ? '0' : '1';
                        scrollToBottomBtn.style.transform = isAtBottom ? 'scale(0.8)' : 'scale(1)';
                        scrollToBottomBtn.style.pointerEvents = isAtBottom ? 'none' : 'auto';
                    });
                }
            }
            
            // Handle create conversation form submission with enhanced validation
            const createForm = document.getElementById('createConversationForm');
            if (createForm) {
                const titleInput = document.getElementById('title');
                
                // Add input validation with visual feedback
                titleInput.addEventListener('input', function() {
                    if (this.value.trim().length > 0) {
                        this.style.borderColor = 'var(--success-color)';
                    } else {
                        this.style.borderColor = '';
                    }
                });
                
                createForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const title = titleInput.value.trim();
                    
                    if (title.length === 0) {
                        titleInput.style.borderColor = 'var(--danger-color)';
                        titleInput.style.animation = 'shake 0.5s';
                        setTimeout(() => {
                            titleInput.style.animation = '';
                        }, 500);
                        showError('请输入对话标题');
                        return;
                    }
                    
                    createConversation(title);
                });
            }
            
            // Handle chat form submission with enhanced UX
            const chatForm = document.getElementById('chatForm');
            if (chatForm) {
                const messageInput = document.getElementById('messageInput');
                
                // Auto-resize textarea
                if (messageInput) {
                    messageInput.addEventListener('input', function() {
                        autoResizeTextarea(this);
                    });
                    
                    // Initialize height
                    setTimeout(() => {
                        autoResizeTextarea(messageInput);
                    }, 0);
                    
                    // Add focus effects
                    messageInput.addEventListener('focus', function() {
                        this.parentElement.style.boxShadow = '0 0 0 3px rgba(var(--primary-hue), 90%, 70%, 0.3)';
                    });
                    
                    messageInput.addEventListener('blur', function() {
                        this.parentElement.style.boxShadow = '';
                    });
                }
                
                chatForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const message = messageInput.value.trim();
                    const conversationId = urlParams.get('conversation_id');
                    
                    // Check if there's a file URL in the query parameters
                    const fileUrl = urlParams.get('file');
                    
                    if (message !== '') {
                        // Disable the submit button and show loading state
                        const submitButton = document.getElementById('msgSubmitButton');
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<div class="loading-spinner"></div>';
                        
                        // Clear the input
                        messageInput.value = '';
                        autoResizeTextarea(messageInput);
                        
                        // Send the message
                        sendMessage(conversationId, message, fileUrl)
                            .then(success => {
                                if (success) {
                                    // Reset the button with animation
                                    submitButton.disabled = false;
                                    submitButton.innerHTML = `
                                        <span>发送</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="22" y1="2" x2="11" y2="13"></line>
                                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                        </svg>
                                    `;
                                    
                                    // Focus the input for next message
                                    messageInput.focus();
                                } else {
                                    // Restore the message on failure
                                    messageInput.value = message;
                                    autoResizeTextarea(messageInput);
                                    
                                    // Reset the button
                                    submitButton.disabled = false;
                                    submitButton.innerHTML = `
                                        <span>发送</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="22" y1="2" x2="11" y2="13"></line>
                                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                        </svg>
                                    `;
                                }
                            })
                            .catch(error => {
                                console.error('Error in send message flow:', error);
                                
                                // Restore the message on failure
                                messageInput.value = message;
                                autoResizeTextarea(messageInput);
                                
                                // Reset the button
                                submitButton.disabled = false;
                                submitButton.innerHTML = `
                                    <span>发送</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="22" y1="2" x2="11" y2="13"></line>
                                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                    </svg>
                                `;
                            });
                    } else {
                        // Shake animation for empty input
                        messageInput.style.animation = 'shake 0.5s';
                        setTimeout(() => {
                            messageInput.style.animation = '';
                        }, 500);
                    }
                });
                
                // Handle Enter key to submit (Shift+Enter for new line)
                messageInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        chatForm.dispatchEvent(new Event('submit'));
                    }
                });
            }
            
            // Add subtle hover effects to buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
                
                // Add ripple effect
                button.addEventListener('click', function(e) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    const ripple = document.createElement('span');
                    ripple.style.position = 'absolute';
                    ripple.style.width = '1px';
                    ripple.style.height = '1px';
                    ripple.style.borderRadius = '50%';
                    ripple.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;
                    ripple.style.animation = 'ripple 600ms ease-out forwards';
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 700);
                });
            });
            
            // Add theme color adjustment based on time of day
            const hour = new Date().getHours();
            let hue;
            
            if (hour >= 5 && hour < 10) {
                // Morning: Soft blue
                hue = 210;
            } else if (hour >= 10 && hour < 16) {
                // Midday: Vibrant purple
                hue = 270;
            } else if (hour >= 16 && hour < 20) {
                // Evening: Warm orange
                hue = 30;
            } else {
                // Night: Deep blue
                hue = 240;
            }
            
            document.documentElement.style.setProperty('--primary-hue', hue.toString());
        });
    </script>
</body>
</html>

