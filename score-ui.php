<?php
// Get URL parameters using PHP
$assessment_id = isset($_GET['assessment_id']) ? $_GET['assessment_id'] : '';
$item_id = isset($_GET['item_id']) ? $_GET['item_id'] : '';

// API URL with parameters
$api_url = "/score.php?assessment_id={$assessment_id}&item_id={$item_id}";
$api_url .= isset($_GET['acc']) ? "&acc=".$_GET['acc'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Score - Sue</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="/twind.js"></script>
    <link rel="icon" href="./favicon.png" type="image/png">
    <script src="https://lf6-cdn-tos.bytecdntp.com/cdn/expire-1-M/Chart.js/3.7.1/chart.min.js" type="application/javascript"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif']
                    },
                    colors: {
                        primary: '#4F46E5',
                        secondary: '#10B981',
                        accent: '#F59E0B',
                        background: '#F9FAFB',
                        card: '#FFFFFF'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F9FAFB;
        }
        
        /* Skeleton loading animation */
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
        
        
        .chart-container {
            position: relative;
        }
        
        /* Badge container */
        .badge-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
            min-height: 2.5rem;
            margin-top: 1rem;
        }
        
        /* Score display - ensure it's wide enough for any score */
        .score-display {
            min-width: 200px;
            display: inline-block;
            text-align: center;
        }
    </style>
</head>
<body class="bg-background min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <?php
        $include_src= "score_display";
        require_once "./global-header.php";
        ?>
        <div class="max-w-5xl mx-auto">
            <header class="mb-8">
    <div class="flex items-center gap-3">
        <button 
            onclick="window.history.go(-1);" 
            class="text-gray-600 hover:text-primary transition-colors p-1 rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/20"
            aria-label="Go back"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left">
                <path d="m15 18-6-6 6-6"/>
            </svg>
        </button>
        <h1 class="text-4xl font-light text-gray-800 tracking-tight">Score Details</h1>
    </div>
</header>

            <div class="grid grid-cols-1 gap-6 mb-8">
                <!-- Personal Score Card -->
                <div class="bg-card rounded-xl shadow-md p-6 flex flex-col items-center justify-center">
                    <h2 class="text-gray-600 font-medium mb-1">Your Score</h2>
                    
                    <!-- Combined score display with extra wide skeleton -->
                    <div class="text-5xl font-bold text-primary mb-2 text-center">
                        <span id="scoreDisplay" class="skeleton score-display h-12"></span>
                    </div>
                    
                    <!-- Rank with proper spacing -->
                    <div class="flex items-center justify-center mb-2">
                        <span class="text-gray-600">Rank:</span>
                        <span class="ml-2 font-semibold" id="scoreRank"><span class="skeleton inline-block w-8 h-6"></span></span>
                        <span class="ml-1 text-gray-600">of</span>
                        <span class="ml-1 font-semibold" id="totalStudents"><span class="skeleton inline-block w-8 h-6"></span></span>
                    </div>
                    
                    <!-- Message container with proper spacing -->
                    <div id="msg-container" class="badge-container w-full">
                        <div id="msg" class="skeleton w-3/4 h-8 rounded-md"></div>
                    </div>
                </div>

                <!-- Min/Max Card -->
                <div class="bg-card rounded-xl shadow-md p-6">
                    <h2 class="text-gray-600 font-medium mb-3 text-center">Score Range</h2>
                    <div class="flex justify-between items-center">
                        <div class="text-center">
                            <div class="text-gray-600 text-sm">Min</div>
                            <div class="text-2xl font-bold text-gray-800" id="minScore"><span class="skeleton inline-block w-12 h-8"></span></div>
                        </div>
                        <div class="h-0.5 flex-1 bg-gray-200 mx-4 relative">
                            <div class="absolute -top-1 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-accent rounded-full"></div>
                            <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-sm font-medium text-gray-600">
                                Med <span id="medScore"><span class="skeleton inline-block w-10 h-4"></span></span> / 
                                Avg <span id="avgScore"><span class="skeleton inline-block w-10 h-4"></span></span>
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-gray-600 text-sm">Max</div>
                            <div class="text-2xl font-bold text-gray-800" id="maxScore"><span class="skeleton inline-block w-12 h-8"></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Score Distribution Chart -->
            <div class="bg-card rounded-xl shadow-md p-6 mb-8 chart-container">
                <div id="chart-skeleton" class="skeleton h-64 w-full"></div>
                <div class="h-64" id="chart-container" style="display: none;">
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
        <?php require_once "./global-footer.php"; ?>
    

    <script>
        // Fetch data from API
        const fetchScoreData = async () => {
            try {
                const response = await fetch('<?php echo $api_url; ?>');
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return await response.json();
            } catch (error) {
                console.error('Error fetching score data:', error);
                return null;
            }
        };

        // Format decimal number to display nicely
        const formatDecimal = (value) => {
            return parseFloat(value).toFixed(2);
        };

        // Calculate average if not provided
        const calculateAverage = (data) => {
            if (data.summary.avg === null) {
                return (data.summary.sum / data.summary.count).toFixed(2);
            }
            return formatDecimal(data.summary.avg);
        };

        // Replace skeleton loaders with actual data
        const replaceSkeletonWithData = (elementId, data) => {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerHTML = data;
                element.classList.remove('skeleton');
            }
        };

        // Update UI with data
        const updateUI = (data) => {
            // Combined score display
            const personalScore = formatDecimal(data.personal_score.gained_score);
            const fullScore = formatDecimal(data.full_score);
            replaceSkeletonWithData('scoreDisplay', `${personalScore} / ${fullScore}`);
            
            // Rank information
            replaceSkeletonWithData('scoreRank', data.personal_score.gained_score_rank);
            replaceSkeletonWithData('totalStudents', data.summary.count);
            
            // Min/Max card
            replaceSkeletonWithData('minScore', formatDecimal(data.summary.min));
            replaceSkeletonWithData('maxScore', formatDecimal(data.summary.max));
            replaceSkeletonWithData('medScore', formatDecimal(data.summary.med));
            replaceSkeletonWithData('avgScore', calculateAverage(data));
            
            // Prepare badges or encouragement message
            let readyformsg = "";
            
            if(data.personal_score.gained_score_rank <= 3){
                readyformsg += '<span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Outstanding</span>&nbsp;';
            }
            
            const personalScoreValue = parseFloat(data.personal_score.gained_score);
            const medValue = parseFloat(data.summary.med);
            const avgValue = parseFloat(calculateAverage(data));
            
            if(personalScoreValue >= medValue){
                readyformsg += '<span class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-green-600/20">Above Med</span>&nbsp;';
            }
            if(personalScoreValue >= avgValue){
                readyformsg += '<span class="inline-flex items-center rounded-md bg-fuchsia-50 px-2 py-1 text-xs font-medium text-fuchsia-700 ring-1 ring-inset ring-green-600/20">Above Avg</span>&nbsp;';
            }
            
            let encouragementMessages = [
                "I know this test didn't go as planned, but remember, one setback doesn't define your journey. You're capable of amazing things!",
                "It's okay to feel down after a tough exam. But don't forget, every experience is a learning opportunity. Keep pushing forward, you've got this!",
                "Exams are just a small part of your story. Your hard work and dedication will shine through in the end. Stay strong and keep believing in yourself!",
                "Don't let this result discourage you. Challenges make us stronger. Use this as fuel to ignite your determination and aim higher next time!",
                "Remember, it's not about failing; it's about how you rise after you fall. You have the strength to bounce back even stronger. Keep going!",
                "Setbacks are temporary, but your potential is limitless. This is just a bump in the road on your way to success. Believe in yourself!",
                "Sometimes we need a reminder that it's okay to stumble. You've come so far already, and this is just another step on your path to greatness.",
                "Your effort and perseverance mean more than any single test score. Take a deep breath, regroup, and tackle the next challenge with confidence!",
                "This isn't the end of the road, it's just a detour. You have all the tools you need to succeed. Focus on your strengths and keep moving forward!",
                "You're stronger than you think, and every challenge makes you more resilient. Pick yourself up, dust off, and keep chasing your dreams!",
                "Even when things don't go as planned, your resilience and determination are what truly matter. Keep pushing, brighter days are ahead!",
                "Every setback is an opportunity for growth. Learn from this experience and use it to propel yourself to new heights. You can do it!",
                "Your journey is unique and filled with potential. Don't let one test define your worth or your future. Keep striving and achieving!",
                "Failure is not the opposite of success; it's part of the process. Embrace it, learn from it, and continue to grow stronger each day!",
                "The only real failure is not trying at all. You've shown great courage by putting in the effort. Keep that spirit alive and keep going!",
                "Think of this as a stepping stone to something greater. With each step, you're getting closer to your goals. Stay positive and keep working!",
                "Success is built on persistence and resilience. You have both in abundance. Keep believing in yourself and watch the magic happen!",
                "Your attitude and perseverance are inspiring. Even when faced with challenges, you continue to shine. Keep that light bright and never give up!",
                "You may not have gotten the score you wanted this time, but that doesn't change the fact that you're capable of great things. Keep aiming high!",
                "Every great success story includes moments of struggle. You're writing yours now, and soon enough, you'll look back on this as a turning point."
            ];
            
            if(readyformsg.length < 10){
                readyformsg = "<i class='block w-full text-center px-4 py-2'>" + encouragementMessages[Math.floor(Math.random() * encouragementMessages.length)]+ "</i>";
            }
            
            // Update message container with proper spacing
            const msgContainer = document.getElementById('msg-container');
            msgContainer.innerHTML = readyformsg;
            
            // Show chart container and hide skeleton
            document.getElementById('chart-skeleton').style.display = 'none';
            const chartContainer = document.getElementById('chart-container');
            chartContainer.style.display = 'block';
            chartContainer.classList.add('chart-animate');
            
            // Create distribution chart
            const ctx = document.getElementById('distributionChart').getContext('2d');
            const labels = data.distribution_counts.map(item => item.group);
            const counts = data.distribution_counts.map(item => item.count);
            
            // Find which bar contains the user's score
            const userScore = parseFloat(data.personal_score.gained_score);
            let userScoreBarIndex = -1;
            
            for (let i = 0; i < labels.length; i++) {
                const matches = labels[i].match(/\[([\d.]+),\s*([\d.]+)[\)\]]/);
                if (matches && matches.length >= 3) {
                    const lowerBound = parseFloat(matches[1]);
                    const upperBound = parseFloat(matches[2]);
                    
                    // Check if user's score falls within this range
                    if (i == labels.length - 1){
                        userScoreBarIndex = i;
                        break;
                    }
                    if (userScore >= lowerBound && userScore < upperBound ) {
                        userScoreBarIndex = i;
                        break;
                    }
                }
            }
            
            // Create background colors array with orange for user's bar
            const backgroundColors = counts.map((_, index) => 
                index === userScoreBarIndex ? 'rgba(245, 158, 11, 0.7)' : 'rgba(79, 70, 229, 0.7)'
            );
            
            // Create border colors array with orange for user's bar
            const borderColors = counts.map((_, index) => 
                index === userScoreBarIndex ? 'rgba(245, 158, 11, 1)' : 'rgba(79, 70, 229, 1)'
            );
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Number of Students',
                        data: counts,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.7,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    family: 'Poppins'
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Poppins'
                                },
                                callback: function(value, index, values) {
                                    // Shorten the labels for better display
                                    const label = this.getLabelForValue(value);
                                    if (label) {
                                        // Extract just the numbers from the range
                                        const matches = label.match(/\[([\d.]+),\s*([\d.]+)[\)\]]/);
                                        if (matches && matches.length >= 3) {
                                            return `${matches[1]}-${matches[2]}`;
                                        }
                                        return label;
                                    }
                                    return '';
                                }
                            }
                        }
                    }
                }
            });
        };

        // Initialize the page
        document.addEventListener('DOMContentLoaded', async function() {
            // Simulate a minimum loading time for better UX
            const minLoadingTime = 800; // milliseconds
            const startTime = Date.now();
            
            const data = await fetchScoreData();
            
            if (data) {
                // Ensure minimum loading time for skeleton effect
                const elapsedTime = Date.now() - startTime;
                if (elapsedTime < minLoadingTime) {
                    setTimeout(() => {
                        updateUI(data);
                    }, minLoadingTime - elapsedTime);
                } else {
                    updateUI(data);
                }
            } else {
                console.error('Failed to load score data');
                // Show error message
                document.querySelector('.grid').innerHTML = `
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-6 text-center">
                        <h3 class="text-lg font-medium mb-2">Unable to load data</h3>
                        <p>There was a problem loading your score data. Please try refreshing the page or contact support if the problem persists.</p>
                    </div>
                `;
                document.getElementById('chart-skeleton').style.display = 'none';
            }
        });
    </script>
</body>
</html>