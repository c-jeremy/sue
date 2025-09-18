<?php

$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();

// 检查会话变量
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header('Location: ../login.php');
    exit;
}

date_default_timezone_set('Asia/Shanghai');

// 获取当前时间并格式化
$time = date('Y-m-d H:i:s');
require_once "./logger.php";
__($_SESSION["user_id"], $time, "View messages",$_ENV["ENV"],1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Sue</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <script src="../twind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .content-wrapper {
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .main-content {
            flex-grow: 1;
            overflow-y: auto;
        }
        b {
            text-decoration: underline;
            text-decoration-style: dashed;
            text-decoration-color: pink;
        }
        @media (max-width: 640px) {
            .pagination-container {
                justify-content: center;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }
            .pagination-container::-webkit-scrollbar {
                display: none;
            }
            .pagination-buttons {
                display: flex;
                justify-content: center;
                min-width: max-content;
                padding: 0 1rem;
            }
        }
        
        /* Skeleton Loading Animation */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
        }
        
        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }
        
        .skeleton-title {
            height: 24px;
            margin-bottom: 12px;
            width: 70%;
        }
        
        .skeleton-content {
            height: 16px;
            margin-bottom: 8px;
            width: 90%;
        }
        
        .skeleton-content-short {
            height: 16px;
            margin-bottom: 8px;
            width: 60%;
        }
        
        .skeleton-date {
            height: 14px;
            width: 40%;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="content-wrapper" id="czmaltx">
        <div class="main-content p-4 sm:p-8 max-w-6xl mx-auto">
            <?php
            $include_src ="msg";
            require "../global-header.php";
            ?>
            <div class="mb-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="relative w-full sm:w-2/3">
                        <input type="text" id="keyword-filter" class="w-full p-2 pl-10 border border-purple-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Search...">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <button id="toggle-read" class="w-full sm:w-auto bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 transition duration-300 flex items-center justify-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Show Read</span>
                    </button>
                </div>
            </div>
            
            <div id="messages" class="space-y-4"></div>
            
            <div class="flex flex-col items-center gap-4 mt-8">
                <div class="pagination-container w-full">
                    <div class="pagination-buttons flex items-center justify-center space-x-2" id="pagination"></div>
                </div>
                
                <div class="flex items-center space-x-2">
                    <input type="number" id="page-jump" class="p-2 border border-purple-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 w-20" min="1" placeholder="Page">
                    <button id="jump-button" class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 transition duration-300">
                        Jump
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require "../global-footer.php"; ?>
    

  <script src="/hammer.js?nn=1" type="text/javascript"></script>
    
    <script>
    
    
    
    
    
       function golgi(inputString) {
           
  // Pattern 1: "a中b的成绩是c，评语：d"
  let pattern1 = /^(.+)的成绩是(.+)星\/(.+)星$/;
  if (pattern1.test(inputString)) {
     
    return 1;
  }
  
  // Pattern 2: "a中b的成绩是c"
  let pattern2 = /^(.+)中个性化评价的成绩是(.+)分，评语：(.+)$/;
  if (pattern2.test(inputString)) {
    return 2;
  }
  
    // Pattern 3: "a中b的成绩是c"
  let pattern3 = /^(.+)的总成绩是(.+)分，(.+)等级$/;
  if (pattern3.test(inputString)) {
    return 3;
  }
  
  // If none of the patterns match, return the original string
  return 0;
} 
    function parseAndFormatString(inputString) {
  // Pattern 1: "a中b的成绩是c，评语：d"
  let pattern1 = /^(.+)中(.+?)的成绩是(.+)，评语(.+)$/;
  if (pattern1.test(inputString)) {
    return inputString.replace(pattern1, '<b>$2</b>的成绩是<b>$3</b>，评语：$4（$1）');
  }
  
  // Pattern 2: "a中b的成绩是c"
  let pattern2 = /^(.+?)中(.+?)的成绩是(.+)$/;
  if (pattern2.test(inputString)) {
    return inputString.replace(pattern2, '<b>$2</b>的成绩是<b>$3</b>（$1）');
  }
  
  // Pattern 3: "a中b的成绩从c修改为d"
  let pattern3 = /^(.+?)中(.+)的成绩从(.+)修改为(.+)$/;
  if (pattern3.test(inputString)) {
    return inputString.replace(pattern3, '<b>$2</b>的成绩从$3修改为<b>$4</b>（$1）');
  }
  
  // Pattern 4: "a在b中对你提交的成果表示了认可"
  let pattern4 = /^(.+?)在(.+)中对你提交的成果表示了认可$/;
  if (pattern4.test(inputString)) {
    return inputString.replace(pattern4, '$2中的成果已被认可');
  }
  
  // Pattern 5: "a修改了b的c"
  let pattern5 = /^(.+?)修改了(.+)的(.+)$/;
  if (pattern5.test(inputString)) {
    return inputString.replace(pattern5, '$1修改了<b>$2</b>的$3');
  }
  
  // If none of the patterns match, return the original string
  return inputString;
}
        // Global variables
        let jsonData;
        let filteredData;
        let categories;
        let count;
        let messagesPerPage = 15;
        let currentPage = 1;
        let currentCategory = 'ALL';
        let isRead = false;
        let startDate = "";
        let endDate = "";
        let keyword = "";
        let isLoading = false;

        // Function to show skeleton loading UI
        function showSkeletonLoaders() {
            const messagesContainer = document.getElementById('messages');
            messagesContainer.innerHTML = '';
            
            // Create skeleton loaders for the number of messages per page
            for (let i = 0; i < messagesPerPage; i++) {
                const skeletonElement = document.createElement('div');
                skeletonElement.className = 'message bg-white rounded-lg shadow-md p-6 mb-4';
                skeletonElement.innerHTML = `
                    <div class="skeleton skeleton-title"></div>
                    <div class="skeleton skeleton-content"></div>
                    <div class="skeleton skeleton-content-short"></div>
                    <div class="skeleton skeleton-date"></div>
                `;
                messagesContainer.appendChild(skeletonElement);
            }
        }

        function fetch_data(stdate, edate, isRead, currentPage, currentCategory) {
            // Show skeleton loaders before fetching data
            isLoading = true;
            showSkeletonLoaders();
            
            let data = { 
                stdate: stdate, 
                edate: edate, 
                isread: isRead, 
                currentPage: currentPage, 
                currentCategory: currentCategory,
                keyword: keyword,
                messagesPerPage: messagesPerPage
            };
            
            fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                console.log('API response:', data);
                if (data.error) {
                    throw new Error(data.error);
                }
                
                jsonData = data.jsonData;
                categories = data.result;
                count = data.total;
                filteredData = jsonData;
                
                // Hide skeleton loaders and display actual data
                isLoading = false;
                displayMessages(currentPage);
            })
            .catch((error) => {
                console.error('Error:', error);
                isLoading = false;
                
                // Clear skeleton loaders and show error message
                const messagesContainer = document.getElementById('messages');
                messagesContainer.innerHTML = `
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline"> An error occurred while fetching data. Please try again.</span>
                    </div>
                `;
                
                // Still update pagination in case of error
                updatePagination();
            });
        }

        function displayMessages(page) {
            const messagesToDisplay = filteredData;
            
            const messagesContainer = document.getElementById('messages');
            messagesContainer.innerHTML = '';

            // If no messages, show a message
            if (messagesToDisplay.length === 0) {
                const noMessagesElement = document.createElement('div');
                noMessagesElement.className = 'bg-white rounded-lg shadow-md p-6 text-center';
                noMessagesElement.innerHTML = `
                    <p class="text-gray-600">No messages found.</p>
                `;
                messagesContainer.appendChild(noMessagesElement);
            } else {
                messagesToDisplay.forEach(message => {
                    const messageElement = document.createElement('div');
                    messageElement.className = 'message bg-white rounded-lg shadow-md p-6 mb-4 hover:shadow-lg transition duration-300 cursor-pointer transform hover:scale-102';
                    messageElement.innerHTML = `
                        <h3 class="text-lg font-bold mb-2 text-purple-800">${message.title}</h3>
                        <p class="text-gray-600 mb-3">${parseAndFormatString(message.content)}</p>
                        <p class="text-sm text-purple-500">${message.created_at}</p>
                    `;
                    messageElement.addEventListener('click', () => handleMessageClick(message));
                    messagesContainer.appendChild(messageElement);
                });
            }

            updatePagination();
        }

        function updatePagination() {
            const totalPages = Math.ceil(count / messagesPerPage) + !count;
            /* v0: don't edit the line above!!!!!!!! don't delete the +!count there!!! */
            //敢删？删了就把你删了！
            const paginationContainer = document.getElementById('pagination');
            paginationContainer.innerHTML = '';

            const createButton = (text, disabled, onClick, className = '') => {
                const button = document.createElement('button');
                button.textContent = text;
                button.className = `min-w-[3rem] px-3 py-2 rounded-md text-sm ${disabled ? 'bg-gray-300 cursor-not-allowed' : 'bg-purple-600 text-white hover:bg-purple-700'} transition duration-300 ${className}`;
                button.disabled = disabled;
                button.addEventListener('click', onClick);
                return button;
            };

            paginationContainer.appendChild(createButton('First', currentPage === 1, () => goToPage(1)));
            paginationContainer.appendChild(createButton('Prev', currentPage === 1, () => goToPage(currentPage - 1)));

            const pageRange = getPageRange(currentPage, totalPages);
            pageRange.forEach(pageNum => {
                if (pageNum === '...') {
                    const ellipsis = document.createElement('span');
                    ellipsis.textContent = '...';
                    ellipsis.className = 'px-2';
                    paginationContainer.appendChild(ellipsis);
                } else {
                    paginationContainer.appendChild(createButton(pageNum.toString(), pageNum === currentPage, () => goToPage(pageNum), pageNum === currentPage ? 'font-bold bg-pink-600' : ''));
                }
            });

            paginationContainer.appendChild(createButton('Next', currentPage === totalPages, () => goToPage(currentPage + 1)));
            paginationContainer.appendChild(createButton('Last', currentPage === totalPages, () => goToPage(totalPages)));
        }

        function getPageRange(currentPage, totalPages) {
            const delta = 2;
            const range = [];
            const rangeWithDots = [];
            let l;

            range.push(1);
            
            for (let i = currentPage - delta; i <= currentPage + delta; i++) {
                if (i < totalPages && i > 1) {
                    range.push(i);
                }
            }
            
            range.push(totalPages);

            for (let i of range) {
                if (l) {
                    if (i - l === 2) {
                        rangeWithDots.push(l + 1);
                    } else if (i - l !== 1) {
                        rangeWithDots.push('...');
                    }
                }
                rangeWithDots.push(i);
                l = i;
            }

            return rangeWithDots;
        }

        function goToPage(page) {
            // Prevent multiple requests while loading
            if (isLoading) return;
            
            currentPage = page;
            fetch_data(startDate, endDate, isRead, currentPage, currentCategory);
        }

        function isread(id) {
            let formData = new FormData();
            formData.append("id", id);
            console.log(id);
            try {
                fetch('./isreadmsg.php', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('An error has occurred:', error);
            }
        }

        function handleMessageClick(message) {
            console.log('Message clicked:', message.domain);
            isread(message._id);
            if (message.domain == "task") {
                window.location.href = `../fetch_data.php?id=${JSON.stringify(message.attributes.id)}`;
            } else if (message.domain == "class_assessment") {
                
                let switchCode = golgi(message.content);
                if (switchCode == 1 || switchCode == 2) {
                    window.location.href = `https://go-c3.seiue.com/classes/${JSON.stringify(message.attributes.scope_id)}/analyses?itemId=${JSON.stringify(message.attributes.item_id)}`;
                
                } else if (switchCode == 3){
                window.location.href = `https://go-c3.seiue.com/classes/${JSON.stringify(message.attributes.scope_id)}/analyses`;
                } else {
                   window.location.href = `../score-ui.php?assessment_id=${JSON.stringify(message.attributes.assessment_id)}&item_id=${JSON.stringify(message.attributes.item_id)}`;
                
                }
            } else if (message.domain == "exam") {
                window.location.href = `https://go-c3.seiue.com/student-exam-analysis-report-modal?examineeId=${JSON.stringify(message.owner.rid)}&examId=${JSON.stringify(message.attributes.exam.id)}&examType=subject`;
            } else if (message.domain == "leave_flow") {
                window.location.href = `https://go-c3.seiue.com/plugin/absence/personal-stat?reflectionId=${JSON.stringify(message.owner.rid)}`;
            } else if (message.domain == "election") {
                window.location.href = `https://election.seiue.com/electives/${JSON.stringify(message.attributes.election_id)}/result`;
            } else if (message.domain == "exam_schedule") {
                window.location.href = `https://go-c3.seiue.com/plugin-exam-rooms-preview-examinee?examId=${JSON.stringify(message.attributes.exam_id)}`;
            } else if (message.domain == "questionnaire") {
                window.location.href = `https://go-c3.seiue.com/questionnaire-submit?id=${JSON.stringify(message.attributes.id)}`;
            }
        }

        function applyKeywordFilter() {
            // Prevent multiple requests while loading
            if (isLoading) return;
            
            currentPage = 1;
            fetch_data(startDate, endDate, isRead, currentPage, currentCategory);
        }

        document.getElementById('toggle-read').addEventListener('click', () => {
            // Prevent multiple requests while loading
            if (isLoading) return;
            
            isRead = !isRead;
            const toggleButton = document.getElementById('toggle-read');
            toggleButton.innerHTML = isRead ? 
                '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor"><path d="M13.875 7.375l-3.75 3.75-1.25-1.25a.75.75 0 00-1.06 1.06l1.78 1.78a.75.75 0 001.06 0l4.28-4.28a.75.75 0 00-1.06-1.06z"/><path fill-rule="evenodd" d="M3 10a7 7 0 1114 0 7 7 0 01-14 0zm7-8a8 8 0 100 16 8 8 0 000-16z" clip-rule="evenodd"/></svg>Show Unread' : 
                '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>Show Read';
            toggleButton.classList.toggle('bg-purple-600');
            toggleButton.classList.toggle('bg-purple-600');
            currentPage = 1;
            fetch_data(startDate, endDate, isRead, currentPage, currentCategory);
        });

        document.getElementById('keyword-filter').addEventListener('input', (e) => {
            keyword = e.target.value;
            if (keyword === '') {
                applyKeywordFilter();
            }
        });

        document.getElementById('keyword-filter').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                keyword = e.target.value;
                applyKeywordFilter();
            }
        });

        document.getElementById('jump-button').addEventListener('click', () => {
            // Prevent multiple requests while loading
            if (isLoading) return;
            
            const pageInput = document.getElementById('page-jump');
            const page = parseInt(pageInput.value);
            if (page && page > 0 && page <= Math.ceil(count / messagesPerPage)) {
                goToPage(page);
            } else {
                alert('Invalid page number');
            }
            pageInput.value = '';
        });

        // Initial data fetch
        fetch_data("", "", false, 1, "ALL");
        const bodyElement = document.querySelector(".sue-navbar");
    const hammer = new Hammer(bodyElement);
    
    // 监听 swipe 事件
    hammer.on('swiperight', () => {
      // 跳转到指定的 URL
      window.location.href = '../timetable/timetable.php'; // 替换为你想要跳转的 URL
    });
    
    hammer.on('swipeleft', () => {
      // 跳转到指定的 URL
      window.location.href = '/tasks.php'; // 替换为你想要跳转的 URL
    });
    </script>
    
</body>
</html>