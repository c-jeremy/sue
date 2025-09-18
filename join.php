
<?php

$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();

// 检查会话变量
if (isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header('Location: tasks.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Sue - Reach Out to Us</title>
     <link rel="icon" href="./favicon.png" type="image/png">
    <script src="./twind.js"></script>
    <style>
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .gradient-text {
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
         @keyframes backgroundTransition {
    0% { 
        background-color: black; 
    }
     2% {

         background-color: white;
         filter: brightness(0%);
     }
   
  
    100% { 
        background-color: white;
        filter: brightness(100%);
    }
}
        .background-transition {
            animation: backgroundTransition 1.5s forwards ease-in-out;
        }
    </style>
</head>
<body class="bg-black text-white font-sans" style="transition: background-color 1.5s ease;">
    <nav class="sticky top-0 bg-opacity-80 backdrop-filter backdrop-blur-lg z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="text-2xl font-bold gradient-text">Sue</div>
            <div class="space-x-6">
                <a href="/" class="hover:text-gray-300 transition duration-300">Home</a>
              
        </div>
    </nav>

    <main class="container mx-auto px-6 py-12">
        <h1 class="text-5xl font-bold text-center mb-12 gradient-text">Sign Up Now</h1>
        
           
        <div class="max-w-4xl mx-auto bg-white/5 backdrop-blur-sm rounded-3xl p-8 shadow-2xl">
            <form class="space-y-6" action="./slogin.php" method="post" id="loginForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Name*</label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-4 py-2 rounded-lg bg-white/10 border border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none transition duration-200 text-white placeholder-gray-400"
                               placeholder="XiaoBei123">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email*</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-2 rounded-lg bg-white/10 border border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none transition duration-200 text-white placeholder-gray-400"
                               placeholder="xiaobei2026@i.pkuschool.edu.cn">
                    </div>
                </div>
                 <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Password*</label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2 rounded-lg bg-white/10 border border-gray-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 outline-none transition duration-200 text-white placeholder-gray-400"
                               placeholder="Password for SEIUE Ultra (not necessarily the same as the one for Seiue)">
                    </div>
              
                <div class="flex items-center space-x-2">
                    <label for="terms" class="text-sm text-gray-300">
                        Signing up implies that you agree to our <a href="./terms.php" class="text-blue-400 hover:text-blue-300 underline">Terms of Use</a> and  <a href="./privacy.php" class="text-blue-400 hover:text-blue-300 underline">Privacy Policy</a>
                    </label>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                            class="px-6 py-3 bg-blue-600 text-white rounded-full hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900 transition duration-300 transform hover:scale-105">
                        Next
                    </button>
                </div>
            </form>
            
                 <div class="text-blue-600 text-center"><a href="./login.php"><u>Already have an account?</u></a></div>
        </div>

        <div class="mt-16 text-center">
            <h2 class="text-3xl font-bold mb-4">Other Ways to Connect</h2>
            <div class="flex justify-center space-x-8">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mb-2 animate-float">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-lg font-medium">Email Us</span>
                    <a href="mailto:support@Seiue-Ultra.com" class="text-blue-400 hover:text-blue-300 transition duration-300">support@sue.com</a>
                </div>
                <div class="flex flex-col items-center">
    <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mb-2 animate-float" style="animation-delay: 0.2s;">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
    </div>
    <span class="text-lg font-medium">Message Us</span>
    <a href="http://123.56.160.48:2000/" class="text-green-400 hover:text-green-300 transition duration-300">Open Chat</a>
</div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-900 py-12 mt-20">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; <?= date("Y");?> Sue. Redefining the limits of human learning.</p>
        </div>
    </footer>
  
</body>
</html>

