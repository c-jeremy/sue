<?php
$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();
require_once "./logger.php";
__($_SESSION["user_id"], "View AI Gallery", $_ENV["ENV"], 1);

// Check session variable
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header('Location: login.php');
    exit;
}

// Read JSON file
$json_data = file_get_contents('AI.json');
$ai_data = json_decode($json_data, true);

// Handle search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
if ($search) {
    $ai_data = array_filter($ai_data, function($ai) use ($search) {
        return stripos($ai['id'], $search) !== false || stripos($ai['name'], $search) !== false;
    });
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI - Sue</title>
    <script src="./twind.js"></script>
    <link rel="icon" href="./favicon.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Modal styles with animations */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            opacity: 0;
            backdrop-filter: blur(5px);
            transition: opacity 0.3s ease;
        }
        
        .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-30px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .modal.show {
            opacity: 1;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }
        
        /* Card hover effects */
        .ai-card {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border-radius: 16px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .ai-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
        }
        
        .ai-card img {
            transition: transform 0.7s ease;
        }
        
        .ai-card:hover img {
            transform: scale(1.05);
        }
        
        /* Button effects */
        .btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: -100%;
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
            transition: all 0.6s ease;
        }
        
        .btn:hover::after {
            left: 100%;
        }
        
        /* Search input focus effect */
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.3);
        }
        
        /* Shimmer loading effect */
        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }
        
        .shimmer {
            animation: shimmer 2s infinite linear;
            background: linear-gradient(to right, #f6f7f8 8%, #edeef1 18%, #f6f7f8 33%);
            background-size: 1000px 100%;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #ec4899;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #d61f69;
        }
        
        /* Tooltip */
        .tooltip {
            position: relative;
        }
        
        .tooltip::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            padding: 5px 10px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .tooltip:hover::before {
            opacity: 1;
            visibility: visible;
        }
        
        /* Badge styles */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <?php $include_src="AI-gallery"; require "./global-header.php"; ?>
        
        <!-- Hero section -->
        <div class="mb-12 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 text-gray-800 tracking-tight">AI Gallery</h1>
            <p class="text-gray-600 max-w-2xl mx-auto">Discover and interact with our collection of intelligent AI assistants designed to help with various tasks.</p>
        </div>
        
        <!-- Search form -->
        <form action="" method="GET" class="mb-12 max-w-2xl mx-auto relative group">
            <div class="flex shadow-lg rounded-full overflow-hidden transition-all duration-300 group-hover:shadow-xl">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search by ID or name..." 
                    value="<?php echo htmlspecialchars($search); ?>" 
                    class="search-input px-6 py-4 w-full border-0 focus:outline-none text-gray-700"
                >
                <button 
                    type="submit" 
                    class="btn bg-gradient-to-r from-pink-500 to-pink-600 text-white px-8 py-4 font-medium hover:from-pink-600 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2 transition duration-300"
                >
                    Search
                </button>
            </div>
            <?php if ($search): ?>
            <div class="mt-4 text-center">
                <a href="AI-gallery.php" class="text-pink-600 hover:text-pink-700 inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Clear search
                </a>
            </div>
            <?php endif; ?>
        </form>

        <?php if (empty($ai_data) || count(array_filter($ai_data, function($ai) { return $ai['school_id'] == 3; })) === 0): ?>
        <!-- Empty state -->
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-gray-300 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 001.5 2.25m0 0v5.8a2.25 2.25 0 01-2.25 2.25H6.75a2.25 2.25 0 01-2.25-2.25V8.25a2.25 2.25 0 011.5-2.25m7.5 0c.251.023.501.05.75.082m-1.5-.082a24.301 24.301 0 00-4.5 0m12 0v5.714a2.25 2.25 0 01-.659 1.591L18 14.5m-7.5 0h7.5m-7.5 0c-.276 0-.5.224-.5.5v7m3.25-2.25a.75.75 0 100-1.5.75.75 0 000 1.5z" />
            </svg>
            <h2 class="text-2xl font-semibold text-gray-700 mb-2">No AI assistants found</h2>
            <p class="text-gray-500 mb-6">We couldn't find any AI assistants matching your search criteria.</p>
            <a href="AI-gallery.php" class="btn bg-pink-600 text-white px-6 py-3 rounded-full hover:bg-pink-700 transition duration-300">View all assistants</a>
        </div>
        <?php else: ?>
        
        <!-- AI Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php 
            $count = 0;
            foreach ($ai_data as $ai): 
                if ($ai['school_id'] == 3): 
                    $count++;
            ?>
            <div class="ai-card">
                <div class="relative overflow-hidden h-64">
                    <img 
                        src="<?php echo htmlspecialchars($ai['avatar']); ?>" 
                        alt="<?php echo htmlspecialchars($ai['name']); ?>" 
                        class="w-full h-full object-cover"
                        loading="lazy"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex items-end">
                        <div class="p-6 text-white">
                            <h2 class="text-xl font-bold mb-1 line-clamp-1"><?php echo htmlspecialchars($ai['name']); ?></h2>
                            <p class="text-white/80 text-sm">ID: <?php echo htmlspecialchars($ai['id']); ?></p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 text-sm mb-4 line-clamp-3 h-[4.5rem]">
                        <?php echo htmlspecialchars(mb_substr($ai['description'], 0, 120)); ?>
                        <?php if (mb_strlen($ai['description']) > 120): ?>...<?php endif; ?>
                    </p>
                    
                    <div class="flex justify-between items-center mb-5">
                        <div class="flex items-center space-x-2">
                            <span class="badge bg-pink-100 text-pink-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <?php echo htmlspecialchars($ai['group_count']); ?> Groups
                            </span>
                        </div>
                        <div>
                            <span class="badge bg-blue-100 text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <?php echo htmlspecialchars($ai['user_count']); ?> Users
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <?php if ($_SESSION['user_id'] < 3): ?>
                        <a 
                            href=<?php if($ai['group_ids'][0]): ?>"ai-chat.php?bot=<?php echo htmlspecialchars($ai['id']); ?>&group_id=<?php echo htmlspecialchars($ai['group_ids'][0]); ?>&school=<?php echo htmlspecialchars($ai['school_id']); ?>"<?php else: echo "#"; endif;?> 
                            class="btn bg-gradient-to-r from-pink-500 to-pink-600 text-white px-6 py-2.5 rounded-full hover:from-pink-600 hover:to-pink-700 transition duration-300 shadow-md hover:shadow-lg flex-1 mr-2 text-center"
                            <?php if(!$ai['group_ids'][0]): ?>disabled<?php endif; ?>
                        >
                            Chat
                        </a>
                        <?php else: ?>
                        <a 
                            href=<?php if($ai['group_ids'][0]): ?>'https://chalk-c3.seiue.com/403?modal=Plugin.AITeacher.Conversation&modalQuery={"id":"<?php echo htmlspecialchars($ai['id']); ?>","groupId":"<?php echo htmlspecialchars($ai['group_ids'][0]); ?>"}' <?php else: echo "#"; endif;?> 
                            class="btn bg-gradient-to-r from-pink-500 to-pink-600 text-white px-6 py-2.5 rounded-full hover:from-pink-600 hover:to-pink-700 transition duration-300 shadow-md hover:shadow-lg flex-1 mr-2 text-center"
                            <?php if(!$ai['group_ids'][0]): ?>disabled<?php endif; ?>
                        >
                            Chat
                        </a>
                        <?php endif ?>
                        <button 
                            onclick="openModal(<?php echo htmlspecialchars(json_encode($ai)); ?>)" 
                            class="btn bg-white text-gray-700 border border-gray-200 px-6 py-2.5 rounded-full hover:bg-gray-50 transition duration-300 shadow-sm hover:shadow flex-1 ml-2"
                        >
                            Details
                        </button>
                    </div>
                </div>
            </div>
            <?php 
                endif; 
            endforeach; 
            
            if ($count === 0 && $search): 
            ?>
            <div class="col-span-full empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-gray-300 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <h2 class="text-2xl font-semibold text-gray-700 mb-2">No results found</h2>
                <p class="text-gray-500 mb-6">We couldn't find any AI assistants matching "<?php echo htmlspecialchars($search); ?>"</p>
                <a href="AI-gallery.php" class="btn bg-pink-600 text-white px-6 py-3 rounded-full hover:bg-pink-700 transition duration-300">View all assistants</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php require "./global-footer.php";?>
    </div>

    <!-- Modal -->
    <div id="aiModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 id="modalTitle" class="text-3xl font-bold text-gray-800"></h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition duration-300 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div class="md:col-span-1">
                    <img id="modalAvatar" src="/placeholder.svg" alt="AI Avatar" class="w-full h-auto rounded-lg shadow-lg">
                </div>
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center space-x-2">
                        <span class="badge bg-gray-100 text-gray-800 px-3 py-1">ID: <span id="modalId"></span></span>
                        <span id="modalGroupBadge" class="badge bg-pink-100 text-pink-800"></span>
                        <span id="modalUserBadge" class="badge bg-blue-100 text-blue-800"></span>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Description</h3>
                        <p id="modalDescription" class="text-gray-600 bg-gray-50 p-4 rounded-lg"></p>
                    </div>
                </div>
            </div>
            
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-3 text-gray-800 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                    Prologue
                </h3>
                <div id="modalPrologue" class="bg-gradient-to-r from-pink-50 to-white p-6 rounded-lg text-gray-700 border-l-4 border-pink-500 shadow-sm"></div>
            </div>
            
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-3 text-gray-800 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Suggested Questions
                </h3>
                <ul id="modalQuestions" class="grid grid-cols-1 md:grid-cols-2 gap-3"></ul>
            </div>
            
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-3 text-gray-800 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Additional Information
                </h3>
                <div id="modalInfo" class="bg-gray-50 p-6 rounded-lg overflow-x-auto text-gray-700 border border-gray-200 shadow-sm"></div>
            </div>
            
            <div class="flex justify-between items-center mt-8">
                <button onclick="closeModal()" class="btn bg-white text-gray-700 border border-gray-200 px-6 py-3 rounded-full hover:bg-gray-50 transition duration-300">
                    Close
                </button>
                
                <div id="modalChatButton">
                    <!-- Chat button will be inserted here by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script>
    // Modal functionality with enhanced animations and UX
    const modal = document.getElementById("aiModal");
    let currentAiData = null;
    
    function openModal(aiData) {
        currentAiData = aiData;
        
        // Set basic information
        document.getElementById("modalTitle").textContent = aiData.name;
        document.getElementById("modalId").textContent = aiData.id;
        document.getElementById("modalDescription").textContent = aiData.description;
        document.getElementById("modalAvatar").src = aiData.avatar;
        document.getElementById("modalAvatar").alt = aiData.name;
        
        // Set badges
        document.getElementById("modalGroupBadge").innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            ${aiData.group_count} Groups
        `;
        
        document.getElementById("modalUserBadge").innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            ${aiData.user_count} Users
        `;
        
        // Parse onboarding data
        let onboarding = {};
        try {
            onboarding = JSON.parse(aiData.external_bot.work_info.onboarding || '{}');
        } catch (e) {
            console.error("Error parsing onboarding data:", e);
            onboarding = {};
        }
        
        // Set prologue
        const prologueEl = document.getElementById("modalPrologue");
        if (onboarding.prologue) {
            prologueEl.textContent = onboarding.prologue;
            prologueEl.parentElement.style.display = "block";
        } else {
            prologueEl.textContent = "No prologue available.";
            prologueEl.parentElement.style.display = "block";
        }
        
        // Set suggested questions
        const questionsList = document.getElementById("modalQuestions");
        questionsList.innerHTML = '';
        
        if (onboarding.suggested_questions && onboarding.suggested_questions.length > 0) {
            onboarding.suggested_questions.forEach(function(question) {
                const li = document.createElement("div");
                li.className = "bg-white p-4 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300 text-gray-700";
                li.innerHTML = `
                    <div class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-pink-500 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>${question}</span>
                    </div>
                `;
                questionsList.appendChild(li);
            });
            questionsList.parentElement.style.display = "block";
        } else {
            const li = document.createElement("div");
            li.className = "col-span-full bg-gray-50 p-4 rounded-lg text-gray-500 text-center";
            li.textContent = "No suggested questions available.";
            questionsList.appendChild(li);
            questionsList.parentElement.style.display = "block";
        }
        
        // Set additional info
        let infoData = '';
        try {
            const systemAllInfo = JSON.parse(aiData.external_bot.work_info.system_all_info || '[]');
            infoData = systemAllInfo[0]?.data || '';
        } catch (e) {
            console.error("Error parsing system info:", e);
            infoData = '';
        }
        
        const infoEl = document.getElementById("modalInfo");
        if (infoData) {
            infoEl.innerHTML = parseMarkdown(infoData);
            infoEl.parentElement.style.display = "block";
        } else {
            infoEl.innerHTML = "<p class='text-center text-gray-500'>No additional information available.</p>";
            infoEl.parentElement.style.display = "block";
        }
        
        // Set chat button
        const chatButtonContainer = document.getElementById("modalChatButton");
        if (aiData.group_ids && aiData.group_ids.length > 0) {
            let chatUrl = '';
            if (<?php echo $_SESSION['user_id']; ?> < 3) {
                chatUrl = `ai-chat.php?bot=${aiData.id}&group_id=${aiData.group_ids[0]}&school=${aiData.school_id}`;
            } else {
                chatUrl = `https://chalk-c3.seiue.com/403?modal=Plugin.AITeacher.Conversation&modalQuery={"id":"${aiData.id}","groupId":"${aiData.group_ids[0]}"}`;
            }
            
            chatButtonContainer.innerHTML = `
                <a href="${chatUrl}" class="btn bg-gradient-to-r from-pink-500 to-pink-600 text-white px-8 py-3 rounded-full hover:from-pink-600 hover:to-pink-700 transition duration-300 shadow-md hover:shadow-lg flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    Start Chatting
                </a>
            `;
        } else {
            chatButtonContainer.innerHTML = `
                <button disabled class="btn bg-gray-300 text-gray-600 px-8 py-3 rounded-full cursor-not-allowed flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    No Chat Available
                </button>
            `;
        }
        
        // Show modal with animation
        modal.style.display = "block";
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
        
        // Prevent body scrolling when modal is open
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = "none";
            currentAiData = null;
        }, 300);
        
        // Re-enable body scrolling
        document.body.style.overflow = '';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });

    // Enhanced Markdown parser with better styling
    function parseMarkdown(markdown) {
        if (!markdown) return '<p class="text-center text-gray-500">No content available.</p>';
        
        // Decode Unicode escape sequences
        let decodedMarkdown = markdown.replace(/\\u[\dA-F]{4}/gi, 
            function (match) {
                return String.fromCharCode(parseInt(match.replace(/\\u/g, ''), 16));
            }
        );
        
        // Decode newlines
        decodedMarkdown = decodedMarkdown.replace(/\\n/g, '\n');
        
        // Parse headers with enhanced styling
        decodedMarkdown = decodedMarkdown.replace(/^### (.*$)/gim, '<h3 class="text-xl font-semibold my-4 text-gray-800 border-b pb-2">$1</h3>');
        decodedMarkdown = decodedMarkdown.replace(/^## (.*$)/gim, '<h2 class="text-2xl font-semibold my-5 text-gray-800 border-b pb-2">$1</h2>');
        decodedMarkdown = decodedMarkdown.replace(/^# (.*$)/gim, '<h1 class="text-3xl font-bold my-6 text-gray-800 border-b pb-3">$1</h1>');
        
        // Parse bold and italic with enhanced styling
        decodedMarkdown = decodedMarkdown.replace(/\*\*(.*)\*\*/gim, '<strong class="font-semibold text-gray-900">$1</strong>');
        decodedMarkdown = decodedMarkdown.replace(/\*(.*)\*/gim, '<em class="italic text-gray-800">$1</em>');
        
        // Parse links with enhanced styling
        decodedMarkdown = decodedMarkdown.replace(/\[([^\]]+)\]$$([^)]+)$$/gim, 
            '<a href="$2" class="text-pink-600 hover:text-pink-700 hover:underline transition duration-300" target="_blank" rel="noopener noreferrer">$1</a>'
        );
        
        // Parse lists with enhanced styling
        decodedMarkdown = decodedMarkdown.replace(/^\s*\n\*/gm, '<ul class="list-disc list-inside my-4 space-y-2">\n*');
        decodedMarkdown = decodedMarkdown.replace(/^(\*\s.*)\n([^\*])/gm, '$1\n</ul>\n\n$2');
        decodedMarkdown = decodedMarkdown.replace(/^\*\s(.*)/gm, '<li class="text-gray-700">$1</li>');
        
        // Parse numbered lists
        decodedMarkdown = decodedMarkdown.replace(/^\s*\n\d\./gm, '<ol class="list-decimal list-inside my-4 space-y-2">\n1.');
        decodedMarkdown = decodedMarkdown.replace(/^(\d\.\s.*)\n([^\d\.])/gm, '$1\n</ol>\n\n$2');
        decodedMarkdown = decodedMarkdown.replace(/^\d\.\s(.*)/gm, '<li class="text-gray-700">$1</li>');
        
        // Parse blockquotes with enhanced styling
        decodedMarkdown = decodedMarkdown.replace(/^\>(.*)$/gim, 
            '<blockquote class="border-l-4 border-pink-500 pl-4 py-2 my-4 bg-pink-50 rounded-r-lg text-gray-700 italic">$1</blockquote>'
        );
        
        // Parse code blocks with enhanced styling
        decodedMarkdown = decodedMarkdown.replace(/```([\s\S]*?)```/g, 
            '<pre class="bg-gray-800 text-gray-200 rounded-lg p-4 my-4 overflow-x-auto font-mono text-sm">$1</pre>'
        );
        
        // Parse inline code with enhanced styling
        decodedMarkdown = decodedMarkdown.replace(/`([^`]+)`/g, 
            '<code class="bg-gray-100 text-pink-600 px-1.5 py-0.5 rounded font-mono text-sm">$1</code>'
        );
        
        // Parse horizontal rules
        decodedMarkdown = decodedMarkdown.replace(/^\-\-\-(\s*)$/gm, '<hr class="my-6 border-t border-gray-300">');
        
        // Parse paragraphs with enhanced styling
        decodedMarkdown = decodedMarkdown.replace(/^\s*(\n)?(.+)/gm, function(m){
            return /\<(\/)?(h\d|ul|ol|li|blockquote|pre|hr|code)/.test(m) ? m : '<p class="my-3 text-gray-700 leading-relaxed">'+m+'</p>';
        });
        
        // Clean up empty paragraphs
        decodedMarkdown = decodedMarkdown.replace(/<p>\s*<\/p>/g, '');
        
        return decodedMarkdown;
    }
    
    // Add loading animation for images
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('.ai-card img');
        
        images.forEach(img => {
            // Add placeholder shimmer effect
            img.classList.add('shimmer');
            
            // Remove shimmer when image loads
            img.onload = function() {
                this.classList.remove('shimmer');
            };
            
            // Handle error
            img.onerror = function() {
                this.classList.remove('shimmer');
                this.src = 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns="http://www.w3.org/2000/svg" width="300" height="150" viewBox="0 0 300 150"%3E%3Crect fill="%23f8f9fa" width="300" height="150"/%3E%3Ctext fill="%23dee2e6" font-family="sans-serif" font-size="30" dy="10.5" font-weight="bold" x="50%25" y="50%25" text-anchor="middle"%3EImage%3C/text%3E%3C/svg%3E';
            };
        });
    });
    </script>
</body>
</html>