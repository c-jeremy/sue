<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header("Location: ../login.php");
    exit();
}
$include_src = "timetable";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.png">
    <title>Timetable - Sue</title>
    <script src='../twind.js'></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
            color: white;
            position: relative;
        }
        
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            z-index: -1;
        }
        
        .backdrop-blur {
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        
        .schedule-container {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transition: opacity 0.3s ease;
            height: calc(100vh - 180px);
        }
        
        .schedule-container.loading {
            opacity: 0.6;
        }
        
        .schedule-header {
            display: grid;
            grid-template-columns: 100px repeat(7, 1fr) 50px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .schedule-header > div {
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .schedule-body {
            position: relative;
            height: calc(100% - 60px);
            overflow-y: auto;
        }
        
        .time-slots, .time-labels {
            position: absolute;
            top: 0;
            height: 1200px;
        }
        
        .time-slots {
            left: 0;
            width: 100px;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .time-labels {
            right: 0;
            width: 50px;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .time-slot, .time-slot-morning, .time-slot-break-big, .time-slot-break-large, .time-slot-break-small, .time-slot-break-noon {
            padding: 5px;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .time-slot { height: 5.56%; }
        .time-slot-morning { height: 8.33%; }
        .time-slot-break-big { height: 2.08%; }
        .time-slot-break-large { height: 2.78%; }
        .time-slot-break-small { height: 1.39%; }
        .time-slot-break-noon { height: 9.03%; }
        
        .time-label {
            position: absolute;
            left: 0;
            right: 0;
            text-align: center;
            transform: translateY(-50%);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            padding: 2px 0;
            margin-top: -1px;
        }
        
        .course-container {
            position: absolute;
            left: 100px;
            right: 50px;
            top: 0;
            height: 1200px;
        }
        
        .course {
            position: absolute;
            padding: 10px;
            font-size: 12px;
            overflow: hidden;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            z-index: 2;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            opacity: 0;
            transform: translateY(20px);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .course.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .course:hover {
            z-index: 3;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        
        .course.expanded {
            height: auto !important;
            min-height: 150px;
            z-index: 1000;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            transform: scale(1.05);
        }
        
        .course-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: white;
        }
        
        .course-info {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2px;
        }
        
        .course-container::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            pointer-events: none;
            background-image: linear-gradient(to bottom, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 100% calc(100% / 12);
            background-position: 0 -1px;
        }
        
        .now-time-indicator {
            position: absolute;
            left: 100px;
            right: 50px;
            height: 2px;
            background-color: #3b82f6;
            z-index: 1000;
            pointer-events: none;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.6);
        }
        
        .now-time-indicator::before {
            content: '';
            position: absolute;
            left: -5px;
            top: -4px;
            width: 10px;
            height: 10px;
            background-color: #3b82f6;
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.6);
        }
        
        /* Task styles */
        .task-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .task-item {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            margin-bottom: 8px;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .task-item:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .task-item.completed {
            background-color: rgba(74, 222, 128, 0.1);
            border-color: rgba(74, 222, 128, 0.3);
        }
        
        .task-item.completed .task-title {
            text-decoration: line-through;
            opacity: 0.7;
        }
        
        .task-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .task-modal.active {
            opacity: 1;
            pointer-events: auto;
        }
        
        .task-modal-content {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        
        .task-modal.active .task-modal-content {
            transform: translateY(0);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .animate-fade-in {
            animation: fade-in 0.5s ease-out forwards;
        }
        
        @keyframes fade-in {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="min-h-screen w-full overflow-hidden">
        <div class="container mx-auto px-4 py-8 max-w-6xl">
        <?php require "../global-header.php"; ?>
        </div>

        <!-- Main Content -->
        <main class="relative w-full pt-4 px-4 flex flex-col md:flex-row gap-4">
            <!-- Sidebar with Personal Tasks -->
            <div class="w-full md:w-64 bg-white/10 backdrop-blur-lg p-4 shadow-xl border border-white/20 rounded-xl animate-fade-in flex flex-col" style="animation-delay: 0.2s">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-white font-medium" id="weekNumber">Loading...</h3>
                    <div class="flex gap-1">
                        <button id="prevWeek" class="p-1 rounded-full hover:bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button id="nextWeek" class="p-1 rounded-full hover:bg-white/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <h3 class="text-white font-medium mb-3">Personal Tasks</h3>
                
                <button id="createTaskBtn" class="mb-4 flex items-center justify-center gap-2 rounded-full bg-blue-500 px-4 py-3 text-white w-full hover:bg-blue-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create Task</span>
                </button>
                
                <div id="taskList" class="task-list space-y-2 flex-grow">
                    <!-- Tasks will be populated here -->
                </div>
            </div>

            <!-- Calendar View -->
            <div class="flex-1 animate-fade-in backdrop-blur-lg" style="animation-delay: 0.4s">
                <!-- Schedule Container -->
                <div class="schedule-container backdrop-blur-lg">
                    <div class="schedule-header">
                        <div>时间</div>
                        <div>MON<br><span id="date-0" class="text-xs text-white/70"></span></div>
                        <div>TUE<br><span id="date-1" class="text-xs text-white/70"></span></div>
                        <div>WED<br><span id="date-2" class="text-xs text-white/70"></span></div>
                        <div>THU<br><span id="date-3" class="text-xs text-white/70"></span></div>
                        <div>FRI<br><span id="date-4" class="text-xs text-white/70"></span></div>
                        <div>SAT<br><span id="date-5" class="text-xs text-white/70"></span></div>
                        <div>SUN<br><span id="date-6" class="text-xs text-white/70"></span></div>
                        <div></div>
                    </div>
                    <div class="schedule-body">
                        <div class="time-slots">
                            <div class="time-slot-morning"><br></div>
                            <div class="time-slot"><strong>第1节</strong><br>08:00-08:40</div>
                            <div class="time-slot-break-big"><br></div>
                            <div class="time-slot"><strong>第2节</strong><br>08:55-09:35</div>
                            <div class="time-slot-break-large"><br></div>
                            <div class="time-slot"><strong>第3节</strong><br>09:55-10:35</div>
                            <div class="time-slot-break-small"><br></div>
                            <div class="time-slot"><strong>第4节</strong><br>10:45-11:25</div>
                            <div class="time-slot-break-noon"><br></div>
                            <div class="time-slot"><strong>中午</strong><br>12:30-13:10</div>
                            <div class="time-slot-break-small"><br></div>
                            <div class="time-slot"><strong>第5节</strong><br>13:20-14:00</div>
                            <div class="time-slot-break-small"><br></div>
                            <div class="time-slot"><strong>第6节</strong><br>14:10-14:50</div>
                            <div class="time-slot-break-small"><br></div>
                            <div class="time-slot"><strong>第7节</strong><br>15:00-15:40</div>
                            <div class="time-slot-break-small"><br></div>
                            <div class="time-slot"><strong>第8节</strong><br>15:50-16:30</div>
                            <div class="time-slot-break-small"><br></div>
                            <div class="time-slot"><strong>第9节</strong><br>16:40-17:20</div>
                            <div class="time-slot-break-small"><br></div>
                            <div class="time-slot"><strong>第10节</strong><br>17:30-18:10</div>
                        </div>
                        <div class="time-labels"></div>
                        <div class="course-container"></div>
                        <div class="now-time-indicator"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Task Modal -->
    <div id="taskModal" class="task-modal">
        <div class="task-modal-content">
            <h3 class="text-white text-xl font-semibold mb-4">Create New Task</h3>
            <form id="taskForm">
                <div class="mb-4">
                    <label for="taskTitle" class="block text-white text-sm font-medium mb-2">Task Title</label>
                    <input type="text" id="taskTitle" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-md text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter task title" required>
                </div>
                <div class="mb-4">
                    <label for="taskDueDate" class="block text-white text-sm font-medium mb-2">Due Date</label>
                    <input type="datetime-local" id="taskDueDate" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="taskPriority" class="block text-white text-sm font-medium mb-2">Priority</label>
                    <select id="taskPriority" class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="low" class="bg-gray-800">Low</option>
                        <option value="medium" class="bg-gray-800" selected>Medium</option>
                        <option value="high" class="bg-gray-800">High</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" id="cancelTaskBtn" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-md transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md transition-colors">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <div class="loading-spinner fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 hidden">
        <svg class="animate-spin h-10 w-10 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <?php require "../global-footer.php";?>
    
     <script src="/hammer.js?nn=1" type="text/javascript"></script>
    
    <script>
    
    const bodyElement = document.querySelector(".sue-navbar");
    const hammer = new Hammer(bodyElement);

    // 监听 swipe 事件
    hammer.on('swiperight', () => {
      // 跳转到指定的 URL
      window.location.href = '/cloud-demo.php'; // 替换为你想要跳转的 URL
    });
    
    hammer.on('swipeleft', () => {
      // 跳转到指定的 URL
      window.location.href = '/msg/index.php'; // 替换为你想要跳转的 URL
    });
        let currentWeek = 0;

        // Function to calculate time position as percentage
        function getTimePosition(time) {
            const [hours, minutes] = time.split(':').map(Number);
            return ((hours - 7) * 60 + minutes) / (12 * 60) * 100;
        }

        function getDayIndex(date) {
            return new Date(date).getDay() - 1;
        }

        function formatTime(timeString) {
            return timeString.split(' ')[1].substring(0, 5);
        }

        function createCourseElement(course, index) {
            const startPos = getTimePosition(formatTime(course.start_time));
            const endPos = getTimePosition(formatTime(course.end_time));
            let dayIndex = getDayIndex(course.start_time.split(' ')[0]);
            if (dayIndex == -1) dayIndex = 6;
            const height = `${(endPos - startPos) * 0.95}%`;
            const top = `${startPos}%`;
            const left = `${(dayIndex / 7) * 100 + 0.25}%`;
            const width = "13.28%";

            // Generate a color based on the course type or index
            const colors = [
                'bg-blue-500/30 border-blue-300/50',
                'bg-green-500/30 border-green-300/50',
                'bg-purple-500/30 border-purple-300/50',
                'bg-pink-500/30 border-pink-300/50',
                'bg-yellow-500/30 border-yellow-300/50',
                'bg-indigo-500/30 border-indigo-300/50',
                'bg-cyan-500/30 border-cyan-300/50',
                'bg-orange-500/30 border-orange-300/50',
                'bg-teal-500/30 border-teal-300/50',
                'bg-red-500/30 border-red-300/50'
            ];
            
            const colorClass = colors[index % colors.length];

            const courseElement = document.createElement('div');
            courseElement.className = `course ${colorClass}`;
            courseElement.style.top = top;
            courseElement.style.left = left;
            courseElement.style.height = height;
            courseElement.style.width = width;

            const availableHeight = parseFloat(height) * 12;
            
            const titleElement = document.createElement('div');
            titleElement.className = 'course-title';
            titleElement.textContent = course.title;
            courseElement.appendChild(titleElement);

            const infoElements = [
                { text: course.address, priority: 1 },
                { text: `${formatTime(course.start_time)}-${formatTime(course.end_time)}`, priority: 2 },
                { text: course.class_name, priority: 3 },
                { text: `教师: ${course.initiators.join(', ')}`, priority: 4 }
            ];

            let remainingHeight = availableHeight - titleElement.offsetHeight;

            infoElements.forEach(info => {
                if (remainingHeight > 0) {
                    const infoElement = document.createElement('div');
                    infoElement.className = 'course-info';
                    infoElement.textContent = info.text;
                    courseElement.appendChild(infoElement);
                    
                    if (infoElement.offsetHeight > remainingHeight) {
                        infoElement.style.overflow = 'hidden';
                        infoElement.style.textOverflow = 'ellipsis';
                        infoElement.style.whiteSpace = 'nowrap';
                    }
                    
                    remainingHeight -= infoElement.offsetHeight;
                }
            });

            courseElement.originalContent = courseElement.innerHTML;

            courseElement.addEventListener('click', function() {
                this.classList.toggle('expanded');
                if (this.classList.contains('expanded')) {
                    this.innerHTML = `
                        <div class="course-title">${course.title}</div>
                        <div class="course-info">${course.address}</div>
                        <div class="course-info">${formatTime(course.start_time)}-${formatTime(course.end_time)}</div>
                        <div class="course-info">${course.class_name}</div>
                        <div class="course-info">教师: ${course.initiators.join(', ')}</div>
                    `;
                } else {
                    this.innerHTML = this.originalContent;
                }
            });

            return courseElement;
        }

        function initializeSchedule(courses) {
            const courseContainer = document.querySelector('.course-container');
            const timeLabels = document.querySelector('.time-labels');

            courseContainer.innerHTML = '';
            timeLabels.innerHTML = '';

            for (let i = 7; i <= 19; i++) {
                const timeLabel = document.createElement('div');
                timeLabel.className = 'time-label';
                timeLabel.textContent = `${i}:00`;
                timeLabel.style.top = `${((i - 7) / 12) * 100}%`;
                if (i == 19) {
                    timeLabel.style.visibility = 'hidden';
                }
                timeLabels.appendChild(timeLabel);
            }

            courses.forEach((course, index) => {
                const courseElement = createCourseElement(course, index);
                courseContainer.appendChild(courseElement);
            });

            // Trigger reflow to ensure all courses are added before animation
            courseContainer.offsetHeight;

            // Add visible class to all courses simultaneously
            document.querySelectorAll('.course').forEach(course => {
                course.classList.add('visible');
            });
            
            // Update the time indicator after loading the schedule
            updateNowTimeIndicator();
        }

        function loadSchedule(week) {
            const scheduleContainer = document.querySelector('.schedule-container');
            const loadingSpinner = document.querySelector('.loading-spinner');

            scheduleContainer.classList.add('loading');
            loadingSpinner.classList.remove('hidden');

            fetch(`get-events-ajax.php?week=${week}`)
                .then(response => response.json())
                .then(data => {
                    scheduleContainer.classList.remove('loading');
                    initializeSchedule(data.events);
                    document.getElementById('weekNumber').textContent = data.weekNumber ? `第${data.weekNumber}周` : '假期';
                    data.weekDates.forEach((date, index) => {
                        document.getElementById(`date-${index}`).textContent = date;
                    });
                })
                .catch(error => console.error('Error:', error))
                .finally(() => {
                    scheduleContainer.classList.remove('loading');
                    loadingSpinner.classList.add('hidden');
                });
        }

        document.getElementById('prevWeek').addEventListener('click', () => {
            currentWeek--;
            loadSchedule(currentWeek);
        });

        document.getElementById('nextWeek').addEventListener('click', () => {
            currentWeek++;
            loadSchedule(currentWeek);
        });

        // Function to update the now-time indicator
        function updateNowTimeIndicator() {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes();
            const dayOfWeek = now.getDay(); // 0 is Sunday, 1 is Monday, etc.
            
            // Only show the indicator if it's between 7am and 7pm and it's a weekday (1-7)
            if (hours >= 7 && hours < 19 && dayOfWeek >= 1 && dayOfWeek <= 7) {
                const indicator = document.querySelector('.now-time-indicator');
                
                // Calculate position in pixels based on the fixed height of the time container (1200px)
                // This ensures the indicator is positioned correctly regardless of window size
                const totalMinutesInDay = 12 * 60; // 12 hours (7am to 7pm) in minutes
                const currentMinutesSince7am = (hours - 7) * 60 + minutes;
                const pixelPosition = (currentMinutesSince7am / totalMinutesInDay) * 1200; // 1200px is the fixed height
                
                // Set the indicator position in pixels instead of percentage
                indicator.style.top = `${pixelPosition}px`;
                indicator.style.display = 'block';
                
                // Scroll to make the indicator visible if it's within the current day
                if (dayOfWeek === new Date().getDay()) {
                    // Calculate scroll position to center the indicator in the viewport
                    const scheduleBody = document.querySelector('.schedule-body');
                    const viewportHeight = scheduleBody.clientHeight;
                    const scrollPosition = Math.max(0, pixelPosition - (viewportHeight / 2));
                    
                    // Only scroll if we're loading the schedule for the first time
                    if (!window.hasScrolledToTime) {
                        scheduleBody.scrollTop = scrollPosition;
                        window.hasScrolledToTime = true;
                    }
                }
            } else {
                document.querySelector('.now-time-indicator').style.display = 'none';
            }
        }

        // Initial load
        loadSchedule(currentWeek);
        
        // Update the now-time indicator immediately and then every 20 seconds
        updateNowTimeIndicator();
        setInterval(updateNowTimeIndicator, 20000);
        
        // Update indicator when window is resized
        window.addEventListener('resize', updateNowTimeIndicator);

        // Task Management
        const taskModal = document.getElementById('taskModal');
        const createTaskBtn = document.getElementById('createTaskBtn');
        const cancelTaskBtn = document.getElementById('cancelTaskBtn');
        const taskForm = document.getElementById('taskForm');
        const taskList = document.getElementById('taskList');

        // Set default due date to tomorrow
        function setDefaultDueDate() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(12, 0, 0, 0);
            
            const year = tomorrow.getFullYear();
            const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
            const day = String(tomorrow.getDate()).padStart(2, '0');
            const hours = String(tomorrow.getHours()).padStart(2, '0');
            const minutes = String(tomorrow.getMinutes()).padStart(2, '0');
            
            document.getElementById('taskDueDate').value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        // Open task modal
        createTaskBtn.addEventListener('click', () => {
            taskForm.reset();
            setDefaultDueDate();
            taskModal.classList.add('active');
        });

        // Close task modal
        cancelTaskBtn.addEventListener('click', () => {
            taskModal.classList.remove('active');
        });

        // Close modal when clicking outside
        taskModal.addEventListener('click', (e) => {
            if (e.target === taskModal) {
                taskModal.classList.remove('active');
            }
        });

        // Save task
        taskForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const taskTitle = document.getElementById('taskTitle').value;
            const taskDueDate = document.getElementById('taskDueDate').value;
            const taskPriority = document.getElementById('taskPriority').value;
            
            if (!taskTitle || !taskDueDate) return;
            
            const task = {
                id: Date.now(),
                title: taskTitle,
                dueDate: taskDueDate,
                priority: taskPriority,
                completed: false,
                createdAt: new Date().toISOString()
            };
            
            // Save to localStorage
            const tasks = getTasks();
            tasks.push(task);
            localStorage.setItem('personalTasks', JSON.stringify(tasks));
            
            // Close modal and refresh task list
            taskModal.classList.remove('active');
            renderTasks();
        });
        
        // Get tasks from localStorage
        function getTasks() {
            const tasksJSON = localStorage.getItem('personalTasks');
            return tasksJSON ? JSON.parse(tasksJSON) : [];
        }
        
        // Render tasks
        function renderTasks() {
            const tasks = getTasks();
            taskList.innerHTML = '';
            
            if (tasks.length === 0) {
                taskList.innerHTML = '<p class="text-white/70 text-center py-4">No tasks yet. Create one!</p>';
                return;
            }
            
            // Sort tasks: first by completion status, then by due date, then by priority
            tasks.sort((a, b) => {
                if (a.completed !== b.completed) return a.completed ? 1 : -1;
                
                const dateA = new Date(a.dueDate);
                const dateB = new Date(b.dueDate);
                
                if (dateA.getTime() !== dateB.getTime()) return dateA - dateB;
                
                const priorityValues = { high: 0, medium: 1, low: 2 };
                return priorityValues[a.priority] - priorityValues[b.priority];
            });
            
            tasks.forEach(task => {
                const taskElement = document.createElement('div');
                taskElement.className = `task-item ${task.completed ? 'completed' : ''}`;
                
                // Format due date
                const dueDate = new Date(task.dueDate);
                const formattedDate = dueDate.toLocaleDateString() + ' ' + dueDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                // Priority color
                const priorityColors = {
                    high: 'bg-red-500/20',
                    medium: 'bg-yellow-500/20',
                    low: 'bg-green-500/20'
                };
                
                taskElement.innerHTML = `
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full ${priorityColors[task.priority]}"></div>
                            <h4 class="task-title text-white font-medium">${task.title}</h4>
                        </div>
                        <p class="text-white/70 text-xs mt-1">Due: ${formattedDate}</p>
                    </div>
                    <div class="flex gap-1">
                        <button class="toggle-task p-1 rounded hover:bg-white/10" data-id="${task.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ${task.completed ? 'text-green-400' : 'text-white/70'}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        <button class="delete-task p-1 rounded hover:bg-white/10" data-id="${task.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                `;
                
                taskList.appendChild(taskElement);
            });
            
            // Add event listeners to toggle and delete buttons
            document.querySelectorAll('.toggle-task').forEach(button => {
                button.addEventListener('click', toggleTask);
            });
            
            document.querySelectorAll('.delete-task').forEach(button => {
                button.addEventListener('click', deleteTask);
            });
        }
        
        // Toggle task completion
        function toggleTask(e) {
            const taskId = parseInt(e.currentTarget.dataset.id);
            const tasks = getTasks();
            
            const taskIndex = tasks.findIndex(task => task.id === taskId);
            if (taskIndex !== -1) {
                tasks[taskIndex].completed = !tasks[taskIndex].completed;
                localStorage.setItem('personalTasks', JSON.stringify(tasks));
                renderTasks();
            }
        }
        
        // Delete task
        function deleteTask(e) {
            const taskId = parseInt(e.currentTarget.dataset.id);
            const tasks = getTasks();
            
            const updatedTasks = tasks.filter(task => task.id !== taskId);
            localStorage.setItem('personalTasks', JSON.stringify(updatedTasks));
            renderTasks();
        }
        
        // Initialize task list
        renderTasks();
    </script>
</body>
</html>