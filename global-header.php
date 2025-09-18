<?php
(isset($include_src)) || die("This header file requires a main file as the main web page.");
if(isset($_REQUEST["use_vconsole"])){?>
<script src="/vconsole.js"></script>
<script>
    var vConsole = new VConsole();
    console.log('hello world');
</script><?php }

/* Temporarily closing SU AI for public use... */
$dontshowAI = 1;

if(!isset($dontshowAI)){
?>
   <link rel="stylesheet" href="/ai-assets/ai.css" />
    <script type="module" crossorigin src="https://g.alicdn.com/aliyun-documentation/web-chatbot-ui/0.0.21/index.js"></script>
    <script>
let userid='<?php echo $_SESSION["user_id"]; ?>';window.CHATBOT_CONFIG={endpoint:"http://123.56.160.48:2000/chat-su",displayByDefault:!1,aiChatOptions:{conversationOptions:{conversationStarters:[{prompt:'给我生物书第五章知识梳理'},{prompt:'数学语雀链接发我'},{prompt:'我想看看我的近期任务速览。'},]},displayOptions:{height:600,},personaOptions:{assistant:{name:'Hi, 我是凤欣悦',avatar:'/ai-assets/logo.apng',tagline:'凤凰为翼，心悦偕行',}},messageOptions:{waitTimeBeforeStreamCompletion:'never'}},dataProcessor:{rewritePrompt(prompt){if(prompt.indexOf("近期任务速览")>=0){return"请给出用户ID为"+userid+"的用户的近期任务速览。注意：回答时不要提到用户的ID，直接称呼该用户为\"你\"即可。"}else{return prompt}}}};</script>

<?php
}
?>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }


    .rippleG {
        position: relative;
        overflow: hidden;
    }

    .rippleG-effect {
        position: absolute;
        border-radius: 50%;
        background-color: rgba(0, 120, 255, 0.4);
        width: 100px;
        height: 100px;
        margin-top: -50px;
        margin-left: -50px;
        animation: rippleG 1s;
        opacity: 0;
    }

    @keyframes rippleG {
        from {
            transform: scale(0);
            opacity: 1;
        }
        to {
            transform: scale(2);
            opacity: 0;
        }
    }

    /* Media query for mobile devices */
    @media (max-width: 640px) {
        @keyframes rippleG {
            from {
                transform: scale(0);
                opacity: 1;
            }
            to {
                transform: scale(8);
                opacity: 0;
            }
        }
    }

    /* Icon styling */
    .nav-icon {
        width: 18px;
        height: 18px;
        margin-right: 6px;
    }
    
    .mobile-nav-icon {
        width: 20px;
        height: 20px;
        margin-right: 8px;
        vertical-align: middle;
    }

    </style>
    <div>
    <nav class="bg-white shadow-md rounded-lg <?php echo ($ohno) ? 'animate-fade-in-down' : ''; ?>">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 sue-navbar">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <img class="h-10 w-auto" src="/favicon.png">
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <a href="/tasks.php" class="rippleG inline-flex items-center px-1 pt-1 rounded-lg <?php echo $include_src === 'tasks' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                  <line x1="16" y1="13" x2="8" y2="13"></line>
                  <line x1="16" y1="17" x2="8" y2="17"></line>
                  <line x1="10" y1="9" x2="8" y2="9"></line>
                </svg>
                Tasks
              </a>
              <a href="/notices.php" class="rippleG inline-flex items-center px-1 pt-1 rounded-lg <?php echo $include_src === 'notices' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                Notices
              </a>
              <a href="/cloud-demo.php" class="rippleG inline-flex items-center px-1 pt-1 rounded-lg <?php echo $include_src === 'cloud-demo' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"></path>
                </svg>
                Cloud
              </a>
              <a href="/timetable/timetable.php" class="rippleG inline-flex items-center px-1 pt-1 rounded-lg <?php echo $include_src === 'timetable' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                  <line x1="16" x2="16" y1="2" y2="6"></line>
                  <line x1="8" x2="8" y1="2" y2="6"></line>
                  <line x1="3" x2="21" y1="10" y2="10"></line>
                </svg>
                Timetable
              </a>
              <a href="/AI-gallery.php" class="rippleG inline-flex items-center px-1 pt-1 rounded-lg <?php echo $include_src === 'AI-gallery' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                  <polyline points="3.29 7 12 12 20.71 7"></polyline>
                  <line x1="12" y1="22" x2="12" y2="12"></line>
                </svg>
                SeiueAI 
              </a>
            </div>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
    
            <button type="button" class="rippleG rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2" onclick="window.location.href='/msg/';">
              <span class="sr-only">View notifications</span>
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
              </svg>
            </button>
    
            <div class="ml-3 relative">
              <div>
                <button onclick="window.location.href='/logout.php';" type="button" class="rippleG flex rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                  <span class="sr-only">Logout</span>
                  <img class="h-8 w-8 rounded-full" src="/logout.webp" alt="Logout">
                </button>
              </div>
            </div>
          </div>
          <div class="-mr-2 flex items-center sm:hidden">
            <button id="mobile-menu-btn" type="button" class="rippleG inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-500" aria-controls="mobile-menu" aria-expanded="false">
              <span class="sr-only">Open main menu</span>
              <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    
      <div class="sue-navbar sm:hidden" id="mobile-menu" style="display: none;">
        <div class="pt-2 pb-3 space-y-1">
          <a href="/tasks.php" class="rippleG <?php echo $include_src === 'tasks' ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
            <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <line x1="10" y1="9" x2="8" y2="9"></line>
              </svg>
              Tasks
            </div>
          </a>
          <a href="/notices.php" class="rippleG <?php echo $include_src === 'notices' ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
            <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
              Notices
            </div>
          </a>
          <a href="/cloud-demo.php" class="rippleG <?php echo $include_src === 'cloud-demo' ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
            <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"></path>
              </svg>
              Cloud
            </div>
          </a>
          <a href="/timetable/timetable.php" class="rippleG <?php echo $include_src === 'timetable' ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
            <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                <line x1="16" x2="16" y1="2" y2="6"></line>
                <line x1="8" x2="8" y1="2" y2="6"></line>
                <line x1="3" x2="21" y1="10" y2="10"></line>
              </svg>
              Timetable
            </div>
          </a>
          <a href="/AI-gallery.php" class="rippleG <?php echo $include_src === 'AI-gallery' ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
            <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="mobile-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.29 7 12 12 20.71 7"></polyline>
                <line x1="12" y1="22" x2="12" y2="12"></line>
              </svg>
              SeiueAI
            </div>
          </a>
        </div>
        <div class="pt-4 pb-3 border-t border-gray-200">
          <div class="flex items-center px-4">
            <div class="flex-shrink-0">
              <img class="h-10 w-10 rounded-full" src="/logout.webp" alt="Logout">
            </div>
            <div class="ml-3">
              <button onclick="window.location.href='/logout.php';" class="rippleG block text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">Sign out</button>
            </div>
        
            <button type="button" class="rippleG ml-auto flex-shrink-0 bg-white p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500" onclick="window.location.href='/msg/';">
              <span class="sr-only">View notifications</span>
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </nav><br>
    </div>
    <script>
 
    var mobileMenu = document.getElementById('mobile-menu');
    document.addEventListener('DOMContentLoaded', function() {
        var mobileMenuBtn = document.getElementById('mobile-menu-btn');
        var icons = mobileMenuBtn.querySelectorAll('svg');
    
        mobileMenuBtn.addEventListener('click', function() {
            var expanded = this.getAttribute('aria-expanded') === 'true' || false;
            this.setAttribute('aria-expanded', !expanded);
            mobileMenu.style.display = expanded ? 'none' : 'block';
            icons[0].classList.toggle('hidden');
            icons[1].classList.toggle('hidden');
        });
            // Close menu when resizing to desktop width
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 640) { // Adjust the breakpoint as needed
                mobileMenu.style.display = 'none';
            }
        });
        // New rippleG effect code
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('rippleG')) {
            var rippleG = document.createElement('div');
            rippleG.className = 'rippleG-effect';
            
            e.target.appendChild(rippleG);

            var rect = e.target.getBoundingClientRect();
            var x = e.clientX - rect.left;
            var y = e.clientY - rect.top;

            rippleG.style.left = x + 'px';
            rippleG.style.top = y + 'px';
            

            setTimeout(function() {
                rippleG.remove();
            }, 1000);
        }
    });
    
    });
    </script>

