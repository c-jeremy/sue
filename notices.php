<?php
$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();

require_once "./credentials.php";
require "./logger.php";
require 'djeh.php';
require "notices-fetch-data.php";

__($_SESSION["user_id"], "Viewed notices", $_ENV["ENV"], 1);

// Function to extract message data
function extractMessageData($message) {
    $fullContent = djeh($message['content']);

    return [
        'title' => $message['title'],
        'fullContent' => $fullContent,
        'sign' => $message['sign'],
        'id' => $message['_id'],
        'sender' => $message['sender']['name'],
        'isread' => $message['readed'],
    ];
}

// Check if it's an AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $items = array_map('extractMessageData', $messages);
    header('Content-Type: application/json');
    echo json_encode($items);
    exit;
}

// For the initial page load, prepare the first batch of items
$items = array_map('extractMessageData', $messages);
?>

<!DOCTYPE html>
<html lang="en" class="bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./favicon.png" type="image/png">
    <title>Notices - Sue</title>
    <script src="./twind.js"></script>
    <link rel="stylesheet" type="text/css" href="djeh.css?version=0.7">
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .dark body {
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(139, 92, 246, 0.08) 0%, transparent 30%),
                radial-gradient(circle at 90% 90%, rgba(236, 72, 153, 0.08) 0%, transparent 30%),
                linear-gradient(to right, rgba(30, 41, 59, 0.5) 0%, rgba(15, 23, 42, 0.5) 100%);
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

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
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

        .fade-out {
            animation: fadeOut 0.3s var(--ease-out) forwards;
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

        /* ===== Notice Card Styles ===== */
        .notice-card {
            background-color: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            position: relative;
            transition: all var(--transition-normal) var(--ease-out);
            border: 1px solid var(--border);
            transform-style: preserve-3d;
            perspective: 1000px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            will-change: transform, box-shadow;
        }

        .notice-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(139, 92, 246, 0.3);
        }

        /* Add a subtle glow effect to cards on hover in dark mode */
        .dark .notice-card:hover {
            box-shadow: 
                0 10px 15px -3px rgba(0, 0, 0, 0.3), 
                0 4px 6px -2px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(139, 92, 246, 0.3),
                0 0 15px 2px rgba(139, 92, 246, 0.15);
        }

        /* Add a subtle background pattern to the card on hover */
        .notice-card:hover::after {
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

        /* Unread indicator */
        .unread-indicator {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--primary);
            box-shadow: 0 0 0 rgba(139, 92, 246, 0.4);
            animation: pulse 2s infinite;
            z-index: 2;
        }

        /* Notice card content */
        .notice-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--foreground);
            margin-bottom: 0.5rem;
            padding-right: 1.5rem;
            line-height: 1.4;
        }

        .notice-meta {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }

        .notice-meta-icon {
            opacity: 0.7;
        }

        .notice-preview {
            font-size: 0.875rem;
            color: #475569;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.5;
        }

        /* Details styling */
        details summary {
            list-style: none;
            cursor: pointer;
        }

        details summary::-webkit-details-marker {
            display: none;
        }

        details[open] summary ~ * {
            animation: slideInRight 0.3s var(--ease-out);
        }

        .details-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--primary);
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
        }

        .details-summary:hover {
            background-color: rgba(139, 92, 246, 0.1);
            color: var(--primary-dark);
        }

        .details-content {
            margin-top: 0.75rem;
            padding-left: 0.75rem;
            border-left: 2px solid rgba(139, 92, 246, 0.2);
        }

        .details-body {
            font-size: 0.875rem;
            color: #475569;
            overflow-x: auto;
            margin-bottom: 0.75rem;
        }

        /* Tag styling */
        .tag {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 500;
            background: rgba(139, 92, 246, 0.1);
            color: var(--primary-dark);
            transition: all var(--transition-fast);
        }

        .tag:hover {
            background: rgba(139, 92, 246, 0.2);
        }

        .tag-secondary {
            background: rgba(100, 116, 139, 0.1);
            color: #64748b;
        }

        .tag-secondary:hover {
            background: rgba(100, 116, 139, 0.2);
        }

        /* Header styling */
        .header-container {
            display: flex;
            align116,139,0.2);
        }

        /* Header styling */
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 0;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--foreground);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
            animation: gradientShift 5s ease infinite;
        }

        /* Loading state */
        .loading-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        .loading-spinner {
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 0.8s linear infinite;
            margin-right: 0.75rem;
        }

        .loading-text {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--primary);
        }

        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .empty-state-icon {
            font-size: 3rem;
            color: rgba(139, 92, 246, 0.3);
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="custom-scrollbar">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
        <?php
        $include_src="notices";
        require "./global-header.php";
        ?>
        
        <div class="header-container fade-in">
            <h1 class="page-title">Your Notices</h1>
            <button id="markAllAsRead" class="btn btn-primary ripple">
                <i class="fas fa-check-circle mr-2"></i>
                Mark All as Read
            </button>
        </div>

        <div id="message-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php 
            $delay_counter = 1;
            foreach ($items as $item): 
                $delay_class = "card-animation-delay-" . min($delay_counter, 6);
                $delay_counter++;
            ?>
                <div class="notice-card p-5 relative fade-in" style="animation-delay: <?= 0.05 * ($delay_counter - 1) ?>s; opacity: 0;">
                    <?php if (!$item['isread']): ?>
                        <div class="unread-indicator"></div>
                    <?php endif; ?>
                    
                    <h2 class="notice-title"><?= htmlspecialchars($item['title']) ?></h2>
                    
                    <div class="notice-meta">
                        <i class="fas fa-user notice-meta-icon"></i>
                        <span><?= htmlspecialchars($item['sender']) ?></span>
                        <?php if ($item['sign']): ?>
                            <span class="mx-1">•</span>
                            <span><?= htmlspecialchars($item['sign']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notice-preview">
                        <?= strip_tags(mb_substr($item['fullContent'], 0, 120)) ?>...
                    </div>
                    
                    <details>
                        <summary class="details-summary" onclick="isread('<?= $item['id'] ?>')">
                            <span>View Details</span>
                            <i class="fas fa-chevron-down transform transition-transform duration-300"></i>
                        </summary>
                        <div class="details-content">
                            <div class="details-body prose prose-sm max-w-none prose-headings:text-primary-dark prose-a:text-primary prose-a:no-underline hover:prose-a:text-primary-dark">
                                <?= $item['fullContent'] ?>
                            </div>
                            <?php if ($item['sign']): ?>
                                <div class="tag tag-secondary inline-flex">
                                    <i class="fas fa-signature mr-1.5 text-xs"></i>
                                    <?= htmlspecialchars($item['sign']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div id="loading" class="loading-container hidden">
            <div class="loading-spinner"></div>
            <span class="loading-text">Loading more notices...</span>
        </div>
        
        <div id="no-more-notices" class="empty-state hidden">
            <div class="empty-state-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="text-lg font-semibold mb-2">You're all caught up!</h3>
            <p class="text-sm text-gray-500">No more notices to load.</p>
        </div>
        
        <!-- Toast Container -->
        <div class="toast-container" id="toast-container">
            <!-- Toasts will be dynamically inserted here -->
        </div>
        
        <?php require "./global-footer.php";?>
    </div>

    <script src="/hammer.js?nn=1" type="text/javascript"></script>
    
    <script>
    // Theme Management
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
        document.documentElement.classList.add('dark');
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
        isDarkMode = true;
    }
    
    function disableDarkMode() {
        document.documentElement.classList.remove('dark');
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        }
        isDarkMode = false;
    }
    
    // Animation for staggered card appearance
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.notice-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
            }, 50 * (index % 6));
        });
        
        // Add hover effect to cards
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty('--x', `${x}px`);
                card.style.setProperty('--y', `${y}px`);
            });
        });
        
        // Add rotation effect to details summary icons
        const summaries = document.querySelectorAll('details summary');
        summaries.forEach(summary => {
            summary.addEventListener('click', function() {
                const icon = this.querySelector('i');
                const details = this.parentElement;
                
                if (details.open) {
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });
    });
    
    // Hammer.js touch gestures
    const bodyElement = document.querySelector(".sue-navbar");
    if (bodyElement) {
        const hammer = new Hammer(bodyElement);

        // Listen for swipe events
        hammer.on('swiperight', () => {
            window.location.href = '/tasks.php';
        });
        
        hammer.on('swipeleft', () => {
            window.location.href = '/cloud-demo.php';
        });
    }
    
    // Infinite scroll and notice management
    let page = 1;
    let loading = false;
    let allMessagesLoaded = false;

    function isread(id) {
        let formData = new FormData();
        formData.append("id", id);
        
        try {
            fetch('./isread.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(() => {
                // Success - you could show a toast here if desired
            })
            .catch(error => {
                console.error('Error marking notice as read:', error);
                showToast('error', 'Error', 'Could not mark notice as read');
            });
        } catch (error) {
            console.error('An error has occurred:', error);
        }
    }

    function loadMoreMessages() {
        if (loading || allMessagesLoaded) return;
        loading = true;
        page++;

        const loadingElement = document.getElementById('loading');
        const noMoreElement = document.getElementById('no-more-notices');
        
        loadingElement.classList.remove('hidden');
        noMoreElement.classList.add('hidden');

        fetch(`/notices.php?page=${page}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const container = document.getElementById('message-container');
            if (data.length === 0) {
                allMessagesLoaded = true;
                loadingElement.classList.add('hidden');
                noMoreElement.classList.remove('hidden');
            } else {
                data.forEach((item, index) => {
                    const messageElement = document.createElement('div');
                    messageElement.className = 'notice-card p-5 relative fade-in';
                    messageElement.style.opacity = '0';
                    messageElement.style.animationDelay = `${(index % 6) * 0.05}s`;
                    
                    let unreadIndicator = '';
                    if (!item.isread) {
                        unreadIndicator = `<div class="unread-indicator"></div>`;
                    }
                    
                    let signHTML = '';
                    if (item.sign) {
                        signHTML = `
                            <div class="tag tag-secondary inline-flex">
                                <i class="fas fa-signature mr-1.5 text-xs"></i>
                                ${escapeHTML(item.sign)}
                            </div>`;
                    }
                    
                    const previewText = item.fullContent ? strip_tags(item.fullContent.substring(0, 120)) + '...' : '';
                    
                    messageElement.innerHTML = `
                        ${unreadIndicator}
                        <h2 class="notice-title">${escapeHTML(item.title)}</h2>
                        
                        <div class="notice-meta">
                            <i class="fas fa-user notice-meta-icon"></i>
                            <span>${escapeHTML(item.sender)}</span>
                            ${item.sign ? `<span class="mx-1">•</span><span>${escapeHTML(item.sign)}</span>` : ''}
                        </div>
                        
                        <div class="notice-preview">
                            ${previewText}
                        </div>
                        
                        <details>
                            <summary class="details-summary" onclick="isread('${item.id}')">
                                <span>View Details</span>
                                <i class="fas fa-chevron-down transform transition-transform duration-300"></i>
                            </summary>
                            <div class="details-content">
                                <div class="details-body prose prose-sm max-w-none prose-headings:text-primary-dark prose-a:text-primary prose-a:no-underline hover:prose-a:text-primary-dark">
                                    ${item.fullContent}
                                </div>
                                ${signHTML}
                            </div>
                        </details>
                    `;
                    container.appendChild(messageElement);
                    
                    // Add hover effect to the new card
                    messageElement.addEventListener('mousemove', (e) => {
                        const rect = messageElement.getBoundingClientRect();
                        const x = e.clientX - rect.left;
                        const y = e.clientY - rect.top;
                        messageElement.style.setProperty('--x', `${x}px`);
                        messageElement.style.setProperty('--y', `${y}px`);
                    });
                    
                    // Add rotation effect to details summary icon
                    const summary = messageElement.querySelector('details summary');
                    if (summary) {
                        summary.addEventListener('click', function() {
                            const icon = this.querySelector('i');
                            const details = this.parentElement;
                            
                            if (details.open) {
                                icon.style.transform = 'rotate(0deg)';
                            } else {
                                icon.style.transform = 'rotate(180deg)';
                            }
                        });
                    }
                    
                    // Trigger animation
                    setTimeout(() => {
                        messageElement.style.opacity = '1';
                    }, 50 * (index % 6));
                });
            }
            loading = false;
            loadingElement.classList.add('hidden');
        })
        .catch(error => {
            console.error('Error loading more messages:', error);
            loadingElement.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon text-red-400">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2 text-red-500">Error Loading Notices</h3>
                    <p class="text-sm text-gray-500 mb-4">There was a problem loading more notices.</p>
                    <button class="btn btn-primary ripple" onclick="loadMoreMessages()">
                        <i class="fas fa-redo mr-2"></i>
                        Try Again
                    </button>
                </div>
            `;
            loading = false;
        });
    }

    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }
    
    function strip_tags(html) {
        if (!html) return '';
        return html.replace(/<\/?[^>]+(>|$)/g, "");
    }

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

    const debouncedLoadMoreMessages = debounce(() => {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
            loadMoreMessages();
        }
    }, 200);

    window.addEventListener('scroll', debouncedLoadMoreMessages);
    window.addEventListener('resize', debouncedLoadMoreMessages);

    // Initial check in case the page doesn't have a scrollbar
    if (document.body.offsetHeight <= window.innerHeight) {
        loadMoreMessages();
    }

    // Mark all as read functionality
    document.getElementById('markAllAsRead').addEventListener('click', function() {
        const originalText = this.innerHTML;
        this.innerHTML = `
            <i class="fas fa-spinner fa-spin mr-2"></i>
            Processing...
        `;
        this.disabled = true;
        
        fetch('./nreadall.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'query=true'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(data => {
            // Remove all unread indicators with animation
            const unreadIndicators = document.querySelectorAll('.unread-indicator');
            unreadIndicators.forEach(indicator => {
                indicator.classList.add('fade-out');
                setTimeout(() => {
                    if (indicator.parentNode) {
                        indicator.parentNode.removeChild(indicator);
                    }
                }, 300);
            });
            
            // Update button
            this.innerHTML = `
                <i class="fas fa-check-circle mr-2"></i>
                All Marked as Read
            `;
            this.classList.add('bg-opacity-75', 'cursor-not-allowed');
            this.disabled = true;
            
            // Show success toast
            showToast('success', 'Success', 'All notices marked as read');
        })
        .catch(error => {
            console.error('Error marking all as read:', error);
            this.innerHTML = originalText;
            this.disabled = false;
            
            // Show error toast
            showToast('error', 'Error', 'Could not mark all notices as read');
        });
    });
    
    // Toast notification system
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
    </script>
</body>
</html>