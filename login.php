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
    <title>Login to Sue - Unlock Your Potential</title>
     <link rel="icon" type="image/png" href="favicon.png">
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
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .animate-pulse { animation: pulse 2s ease-in-out infinite; }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading .spinner {
            display: inline-block;
            animation: spin 1s linear infinite;
        }
        .loading .button-text {
            display: none;
        }
        .input-focus-effect::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 2px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .input-focus-effect:focus-within::after {
            transform: scaleX(1);
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }
        .shake {
            animation: shake 0.9s cubic-bezier(.36,.07,.19,.97) both;
        }
      
        @keyframes fadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; }
        }
        .fade-out {
            animation: fadeOut 1s forwards;
        }
     @keyframes backgroundTransition {
    0% { 
        background-color: black; 
    }
     5% {
         background: white;
         filter: brightness(0%);
     }
    100% { 
        background: white;
        filter: brightness(90%);
    }
}
        .background-transition {
            animation: backgroundTransition 1.5s forwards ease-in-out;
        }
        .circle-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #3b82f6;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.5s ease;
        }
    </style>
</head>
<body class="bg-black text-white font-sans">
    <div id="app" class="min-h-screen flex flex-col">
        <nav class="sticky top-0 bg-black bg-opacity-80 backdrop-filter backdrop-blur-lg z-50">
            <div class="container mx-auto px-6 py-4 flex justify-between items-center">
                <div class="text-2xl font-bold gradient-text">Sue</div>
                <div class="space-x-6">
                    <a href="/" class="hover:text-gray-300 transition duration-300">Home</a>
                    <a href="#" class="hover:text-gray-300 transition duration-300">Features</a>
                    <a href="#" class="hover:text-gray-300 transition duration-300">Contact</a>
                    <a href="join.php" class="bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700 transition duration-300">Sign Up</a>
                </div>
            </div>
        </nav>

        <main class="flex-grow flex items-center justify-center px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-8">
                <div>
                    <h2 class="mt-6 text-center text-4xl font-extrabold gradient-text">
                        Welcome Back
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-400">
                        Enter your credentials to access your account
                    </p>
                </div>
                <form id="loginForm" class="mt-8 space-y-6">
                    <div class="space-y-4">
                        <div class="relative input-focus-effect">
                            <label for="email-address" class="sr-only">Email address</label>
                            <input id="email-address" name="email" type="text" autocomplete="on" required 
                                   class="appearance-none relative block w-full px-3 py-3 border border-gray-600 placeholder-gray-500 text-white bg-white/5 rounded-md focus:outline-none focus:ring-0 focus:border-transparent sm:text-sm transition duration-300" 
                                   placeholder="Email address or username">
                        </div>
                        <div class="relative input-focus-effect">
                            <label for="password" class="sr-only">Password</label>
                            <input id="password" name="password" type="password" autocomplete="current-password" required 
                                   class="appearance-none relative block w-full px-3 py-3 border border-gray-600 placeholder-gray-500 text-white bg-white/5 rounded-md focus:outline-none focus:ring-0 focus:border-transparent sm:text-sm transition duration-300" 
                                   placeholder="Password">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                    </div>

                    <div>
                        <button type="submit" id="loginButton" 
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-300 ease-in-out transform hover:-translate-y-1 hover:scale-105">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <span class="button-text">Sign in</span>
                            <svg class="spinner hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
                <div id="errorMessage" class="mt-4 text-center text-red-500 hidden"></div>
            </div>
        </main>

        <footer class="bg-gray-900 py-8">
            <div class="container mx-auto px-6 text-center">
                <p>&copy; <?= date("Y"); ?> Sue. Redefining the limits of human learning.</p>
            </div>
        </footer>
    </div>
       <script src="./enc.js"></script>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const errorMessage = document.getElementById('errorMessage');
            const emailInput = document.getElementById('email-address');
            const passwordInput = document.getElementById('password');
            const app = document.getElementById('app');

            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                loginButton.classList.add('loading');
                errorMessage.classList.add('hidden');

                // Simulate API call
                //await new Promise(resolve => setTimeout(resolve, 1000));
                const email = emailInput.value;
            const password = passwordInput.value;
            let formData = new FormData();
            formData.append("email", email);
            formData.append("password", sha256(sha256(password)));

            try {
                const response = await fetch('/login-handler.php', {
                    method: 'POST',
                   
                    body: formData,
                });

                const data = await response.json();


                // Simulate random login success/failure
                const success = ( data.stat === "success");

                if (success) {
                    console.log('Login successful');

                    // Remove spinner and transform button to circle
                    /*loginButton.classList.remove('loading');
                    loginButton.innerHTML = '';
                    loginButton.style.width = '50px';
                    loginButton.style.height = '50px';
                    loginButton.style.borderRadius = '50%';
                    loginButton.style.position = 'fixed';
                    loginButton.style.top = '50%';
                    loginButton.style.left = '50%';
                    loginButton.style.transform = 'translate(-50%, -50%)';

                    // Add heartbeat animation
                    loginButton.classList.add('heartbeat');
*/
                    // Fade out other elements
                    document.querySelectorAll('nav, main > div > *, footer').forEach(el => {
                        
                            el.classList.add('fade-out');
                        
                    });

                    // Wait for fade out
                    await new Promise(resolve => setTimeout(resolve, 1000));

                    // Start background transition
                    app.classList.add('background-transition');

                    // Wait for background transition
                    await new Promise(resolve => setTimeout(resolve, 900));


                    // Redirect to tasks page
                    window.location.href = '/tasks.php?from_login=yes';

                } else {
                    console.error('Login failed');
                    errorMessage.textContent = 'Invalid email username or password. Please try again. Or maybe you are in our suspended list.';
                    errorMessage.classList.remove('hidden');
                    emailInput.classList.add('shake');
                    passwordInput.classList.add('shake');
                    setTimeout(() => {
                        emailInput.classList.remove('shake');
                        passwordInput.classList.remove('shake');
                    }, 850);
                }
            } catch (error) {
                console.error('Error during login:', error);
                errorMessage.textContent = 'An error occurred. Please try again later.';
                errorMessage.classList.remove('hidden');
            } finally {
                loginButton.classList.remove('loading');
            }
            });
        });
    </script>
</body>
</html>