<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seiue Ultra - Unleash Your Academic Potential</title>
     <link rel="icon" href="./favicon.png" type="image/png">
    <script src="./twind.js"></script>
    <style>
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-pulse { animation: pulse 2s ease-in-out infinite; }
        .sticky-nav { position: sticky; top: 0; backdrop-filter: blur(20px); }
        .parallax { background-attachment: fixed; background-position: center; background-repeat: no-repeat; background-size: cover; }
        .gradient-text {
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .fade-in-up {
            opacity: 0;
            transform: translateY(50px);
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
            transition-delay: 0.2s;
        }
        .fade-in-up.active {
            opacity: 1;
            transform: translateY(0);
        }
        .speed-bar {
            width: 0;
            transition: width 1s ease-out;
        }
    </style>
</head>
<body class="bg-black text-white font-sans">
    <nav class="sticky-nav bg-black bg-opacity-80 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="text-2xl font-bold gradient-text">Seiue Ultra</div>
            <div class="space-x-6">
                <a href="#features" class="hover:text-gray-300 transition duration-300">Features</a>
                <a href="#focus" class="hover:text-gray-300 transition duration-300">Focus</a>
                <a href="#performance" class="hover:text-gray-300 transition duration-300">Performance</a>
                <a href="/join.php" class="bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700 transition duration-300">Try Now</a>
            </div>
        </div>
    </nav>

    <main>
        <section class="h-screen flex items-center justify-center parallax" style="background-image: url('./index-bg.webp');">
            <div class="text-center">
                <h1 class="text-7xl font-bold mb-4 fade-in-up gradient-text">Seiue Ultra</h1>
                <p class="text-3xl mb-8 fade-in-up" style="transition-delay: 0.4s;">Quantum Leap in Online Learning Dynamics</p>
                <a href="/join.php" class="bg-blue-600 text-white px-8 py-3 rounded-full text-lg font-semibold hover:bg-blue-700 transition duration-300 fade-in-up animate-pulse" style="transition-delay: 0.6s;">Ignite Your Potential</a>
            </div>
        </section>

 <section id="features" class="py-20">
  <div class="container mx-auto px-6">
    <h2 class="text-5xl font-bold text-center mb-16 gradient-text fade-in-up">Why Seiue Ultra?</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
      <div class="feature-card text-center fade-in-up">
        <div class="text-6xl mb-6 animate-float">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
          </svg>
        </div>
        <h3 class="text-2xl font-bold mb-4">Ready, steady, go!</h3>
        <p class="text-gray-400">Blink, and you're there. Access materials at the speed of thought.</p>
      </div>
      <div class="feature-card text-center fade-in-up" style="transition-delay: 0.4s;">
        <div class="text-6xl mb-6 animate-float" style="animation-delay: 0.2s;">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
            <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
            <path d="M2 2l7.586 7.586"></path>
            <circle cx="11" cy="11" r="2"></circle>
          </svg>
        </div>
        <h3 class="text-2xl font-bold mb-4">Neural Interface Design</h3>
        <p class="text-gray-400">An interface so intuitive, it feels like an extension of your mind.</p>
      </div>
      <div class="feature-card text-center fade-in-up" style="transition-delay: 0.6s;">
        <div class="text-6xl mb-6 animate-float" style="animation-delay: 0.4s;">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M23 4v6h-6"></path>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
          </svg>
        </div>
        <h3 class="text-2xl font-bold mb-4">Dynamic Sync</h3>
        <p class="text-gray-400">Instantaneous updates across devices, defying the laws of data physics.</p>
      </div>
    </div>
  </div>
</section>

        <section id="focus" class="py-20 bg-gray-900">
            <div class="container mx-auto px-6">
                <h2 class="text-5xl font-bold text-center mb-16 gradient-text fade-in-up">Laser-Focus Technology</h2>
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <div class="md:w-1/2 mb-12 md:mb-0 fade-in-up">
                        <img src="./show-usage.jpg" loading="lazy" alt="Seiue Ultra Focus Interface" class="rounded-lg shadow-2xl animate-float">
                    </div>
                    <div class="md:w-1/2 md:pl-12 fade-in-up" style="transition-delay: 0.4s;">
                        <h3 class="text-3xl font-bold mb-6">One Type, One Page, Infinite Focus</h3>
                        <p class="text-xl text-gray-400 mb-8">While others clutter your mind with everything on the homepage, Seiue Ultra's revolutionary one-page-per-type approach creates a distraction-free zone for each of your academic pursuits. Dive deep into singular focus, emerging with mastery.</p>
                       <ul class="space-y-6 text-lg">
    <li class="flex items-center group">
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500/10 flex items-center justify-center mr-4 group-hover:bg-blue-500/20 transition-colors duration-300">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <span class="text-xl font-medium bg-gradient-to-r from-purple-400 via-pink-500 to-red-400 bg-clip-text text-transparent">
            Hyper-Focused Environment
        </span>
    </li>
    <li class="flex items-center group">
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500/10 flex items-center justify-center mr-4 group-hover:bg-blue-500/20 transition-colors duration-300">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <span class="text-xl font-medium bg-gradient-to-r from-purple-400 via-pink-500 to-red-400 bg-clip-text text-transparent">
            Task Isolation for Peak Performance
        </span>
    </li>
    <li class="flex items-center group">
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500/10 flex items-center justify-center mr-4 group-hover:bg-blue-500/20 transition-colors duration-300">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <span class="text-xl font-medium bg-gradient-to-r from-purple-400 via-pink-500 to-red-400 bg-clip-text text-transparent">
            Cognitive Load Optimization
        </span>
    </li>
</ul>
                    </div>
                </div>
            </div>
        </section>

        <section id="performance" class="py-20">
            <div class="container mx-auto px-6">
                <h2 class="text-5xl font-bold text-center mb-16 gradient-text fade-in-up">Ultra Performance</h2>
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <div class="md:w-1/2 md:pr-12 mb-12 md:mb-0 fade-in-up">
                        <h3 class="text-3xl font-bold mb-6">Speed That Breaks Reality</h3>
                        <p class="text-xl text-gray-400 mb-8">Seiue Ultra doesn't just outperform - it redefines the very concept of speed in digital learning. Witness the impossible as loading complete before you even think them.</p>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-base font-medium text-pink-500">Seiue Ultra</span>
                                    <span class="text-sm font-medium text-pink-500">1 Page /s</span>
                                </div>
                                <div class="w-full bg-gray-700 rounded-full h-2.5">
                                    <div class="bg-pink-500 h-2.5 rounded-full speed-bar" data-width="100%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-base font-medium text-gray-400">Seiue</span>
                                    <span class="text-sm font-medium text-gray-400">0.125 Page /s</span>
                                </div>
                                <div class="w-full bg-gray-700 rounded-full h-2.5">
                                    <div class="bg-gray-500 h-2.5 rounded-full speed-bar" data-width="12.5%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="md:w-1/2 fade-in-up" style="transition-delay: 0.4s;">
                        <img src="./speed.jpg" loading="lazy" alt="Seiue Ultra Performance Graph" class="rounded-lg shadow-2xl animate-float">
                    </div>
                </div>
            </div>
        </section>
        <section class="py-20">
  <div class="container mx-auto px-6">
    <h2 class="text-5xl font-bold text-center mb-16 gradient-text fade-in-up">And so much more.</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Card 1 -->
      <div class="feature-card rounded-3xl bg-white/5 backdrop-blur-sm transition-all duration-500 cursor-pointer fade-in-up relative overflow-hidden group" data-active="false">
        <div class="p-8 h-[400px] flex flex-col relative">
          <h3 class="text-teal-500 text-xl mb-8 transition-colors duration-500 group-data-[active=true]:text-white"><b>Security</b></h3>
          <div class="initial-content transition-all duration-500 opacity-100 group-data-[active=true]:opacity-0">
            <div class="text-4xl font-bold mb-4">
              Your information, 
              <div class="inline-block bg-purple-900/100 px-2 rounded">never</div>
            </div>
            <div class="text-4xl font-bold">
              <div class="inline-block bg-purple-900/100 px-2 rounded">safer.</div>
            </div>
          </div>
          <div class="active-content absolute inset-0 p-8 pt-20 transition-all duration-500 opacity-0 translate-y-4 group-data-[active=true]:opacity-100 group-data-[active=true]:translate-y-0">
            <p class="text-xl text-white/90">With Seiue Ultra's advanced security approaches, all the information you send to Seiue will be fully protected, not sent to anywhere else than Seiue main servers, and not even stored on Seiue Ultra.</p>
          </div>
          <button class="toggle-active absolute bottom-6 right-6 w-12 h-12 rounded-full bg-gray-500/10 flex items-center justify-center transition-all duration-500 hover:bg-gray-500/20 group-data-[active=true]:bg-white/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 transition-all duration-500 group-data-[active=true]:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
          </button>
        </div>
        <div class="absolute inset-0 bg-teal-500 transition-all duration-500 opacity-0 group-data-[active=true]:opacity-100 -z-10"></div>
      </div>

      <!-- Card 2 -->
      <div class="feature-card rounded-3xl bg-white/5 backdrop-blur-sm transition-all duration-500 cursor-pointer fade-in-up relative overflow-hidden group" data-active="false" style="transition-delay: 0.2s">
        <div class="p-8 h-[400px] flex flex-col relative">
          <h3 class="text-pink-500 text-xl mb-8 transition-colors duration-500 group-data-[active=true]:text-white"><b>Advanced Privileges</b></h3>
          <div class="initial-content transition-all duration-500 opacity-100 group-data-[active=true]:opacity-0">
            <div class="text-4xl font-bold mb-4">
              Do those
              <div class="inline-block bg-purple-900/100 px-2 rounded">you could only </div>
            </div>
            <div class="text-4xl font-bold">
              <div class="inline-block bg-purple-900/100 px-2 rounded">dream of.</div>
            </div>
          </div>
          <div class="active-content absolute inset-0 p-8 pt-20 transition-all duration-500 opacity-0 translate-y-4 group-data-[active=true]:opacity-100 group-data-[active=true]:translate-y-0">
            <p class="text-xl text-white/90">Mark to-do items as read in milliseconds, submitting a task without having to wait until the uploading finishes, even reading others' progress... As you wish.</p>
          </div>
          <button class="toggle-active absolute bottom-6 right-6 w-12 h-12 rounded-full bg-gray-500/10 flex items-center justify-center transition-all duration-500 hover:bg-gray-500/20 group-data-[active=true]:bg-white/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 transition-all duration-500 group-data-[active=true]:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
          </button>
        </div>
        <div class="absolute inset-0 bg-pink-500 transition-all duration-500 opacity-0 group-data-[active=true]:opacity-100 -z-10"></div>
      </div>

      <!-- Card 3 -->
      <div class="feature-card rounded-3xl bg-white/5 backdrop-blur-sm transition-all duration-500 cursor-pointer fade-in-up relative overflow-hidden group" data-active="false" style="transition-delay: 0.4s">
        <div class="p-8 h-[400px] flex flex-col relative">
          <h3 class="text-green-500 text-xl mb-8 transition-colors duration-500 group-data-[active=true]:text-white"><b>Environmental Responsibility</b></h3>
          <div class="initial-content transition-all duration-500 opacity-100 group-data-[active=true]:opacity-0">
            <div class="text-4xl font-bold mb-4">
              
              <div class="inline-block bg-purple-900/100 px-2 rounded">Play your part</div>
             on protecting our Earth.
       </div>
          </div>
          <div class="active-content absolute inset-0 p-8 pt-20 transition-all duration-500 opacity-0 translate-y-4 group-data-[active=true]:opacity-100 group-data-[active=true]:translate-y-0">
            <p class="text-xl text-white/90">With less unused requests and external media, Seiue Ultra not only offers a better experience for students, but also for the environment.</p>
          </div>
          <button class="toggle-active absolute bottom-6 right-6 w-12 h-12 rounded-full bg-gray-500/10 flex items-center justify-center transition-all duration-500 hover:bg-gray-500/20 group-data-[active=true]:bg-white/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 transition-all duration-500 group-data-[active=true]:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
          </button>
        </div>
        <div class="absolute inset-0 bg-green-500 transition-all duration-500 opacity-0 group-data-[active=true]:opacity-100 -z-10"></div>
      </div>

      <!-- Card 4 -->
      <div class="feature-card rounded-3xl bg-white/5 backdrop-blur-sm transition-all duration-500 cursor-pointer fade-in-up relative overflow-hidden group" data-active="false" style="transition-delay: 0.6s">
        <div class="p-8 h-[400px] flex flex-col relative">
          <h3 class="text-yellow-500 text-xl mb-8 transition-colors duration-500 group-data-[active=true]:text-white"><b>AI Assistant</b></h3>
          <div class="initial-content transition-all duration-500 opacity-100 group-data-[active=true]:opacity-0">
            <div class="text-4xl font-bold mb-4">
              Your personal
              <div class="inline-block bg-purple-900/100 px-2 rounded">study</div>
            </div>
            <div class="text-4xl font-bold">
              <div class="inline-block bg-purple-900/100 px-2 rounded">companion.</div>
            </div>
          </div>
          <div class="active-content absolute inset-0 p-8 pt-20 transition-all duration-500 opacity-0 translate-y-4 group-data-[active=true]:opacity-100 group-data-[active=true]:translate-y-0">
            <p class="text-xl text-white/90">Intelligent AI that helps you understand complex topics, suggests improvements, and enhances your learning experience.</p>
          </div>
          <button class="toggle-active absolute bottom-6 right-6 w-12 h-12 rounded-full bg-gray-500/10 flex items-center justify-center transition-all duration-500 hover:bg-gray-500/20 group-data-[active=true]:bg-white/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 transition-all duration-500 group-data-[active=true]:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
          </button>
        </div>
        <div class="absolute inset-0 bg-yellow-500 transition-all duration-500 opacity-0 group-data-[active=true]:opacity-100 -z-10"></div>
      </div>
    </div>
  </div>

  <script>
    document.querySelectorAll('.toggle-active').forEach(button => {
      button.addEventListener('click', (e) => {
        e.stopPropagation();
        const card = button.closest('.feature-card');
        const isActive = card.getAttribute('data-active') === 'true';
        card.setAttribute('data-active', !isActive);
      });
    });
  </script>
</section>

        <section class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-5xl font-bold mb-8 fade-in-up">Ready to Transcend Academic Boundaries?</h2>
                <p class="text-2xl mb-12 fade-in-up" style="transition-delay: 0.4s;">Join the elite minds who've unlocked their true potential with Seiue Ultra.</p>
                <a href="/join.php" class="bg-white text-blue-600 px-8 py-3 rounded-full text-lg font-semibold hover:bg-gray-100 transition duration-300 animate-pulse fade-in-up" style="transition-delay: 0.6s;">Ascend to Greatness Now</a>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 py-12">
        <div class="container mx-auto px-6 text-center">
            <p>&copy; 2024 Seiue Ultra. Redefining the limits of human learning.</p>
            <p class="text-gray-400"><small>Proudly Built Using Vercel v0 AI.</small></p>
        </div>
    </footer>

    <script>
        // Intersection Observer for fade-in-up animations
        const fadeObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('active');
                    }, 400); // Delay the animation start by 200ms
                } else {
                    entry.target.classList.remove('active');
                }
            });
        }, { threshold: 0.7 }); // Increase threshold to delay animation start

        document.querySelectorAll('.fade-in-up').forEach(el => {
            fadeObserver.observe(el);
        });

        // Intersection Observer for speed bar animations
        const speedObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const bar = entry.target;
                    const width = bar.getAttribute('data-width');
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 800); // Delay the animation start by 200ms
                } else {
                    entry.target.style.width = '0';
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.speed-bar').forEach(el => {
            speedObserver.observe(el);
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Parallax effect for hero section
        window.addEventListener('scroll', () => {
            const parallax = document.querySelector('.parallax');
            let scrollPosition = window.pageYOffset;
            parallax.style.backgroundPositionY = scrollPosition * 0.7 + 'px';
        });
    </script>
</body>
</html>