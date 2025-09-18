<?php

session_start();
require_once "./credentials.php";

if(!isset($_SESSION["user_id"])){
    header("location: /login.php");
}

?>

<!DOCTYPE html>
<html class="bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./favicon.png" type="image/png">
    <title>Aurora - Sue</title>
    <script src="/twind.js"></script>
    <script src="/up.js.php"></script>
    <script src="/infobtn.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up {
            animation: fadeInUp 1.5s ease-out;
            opacity: 0;
            animation-fill-mode: forwards;
        }
        .modal-open .task-item { opacity: .5; pointer-events: none; }
        .dropdown { transition: all 0.3s ease-in-out; }
        
        /* File upload styling */
        .file-drop-area {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            border: 2px dashed #cbd5e0;
            border-radius: 0.5rem;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }
        
        .file-drop-area:hover, .file-drop-area.is-active {
            background-color: #edf2f7;
            border-color: #4299e1;
        }
        
        .file-input {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 100%;
            cursor: pointer;
            opacity: 0;
        }
        
        .file-msg {
            font-size: 0.875rem;
            color: #4a5568;
            margin-top: 0.5rem;
            text-align: center;
        }
        
        .file-list {
            margin-top: 1rem;
            width: 100%;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            background-color: #edf2f7;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
        }
        
        .file-item-name {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <?php $include_src="aurora"; require("./global-header.php");?>
        <div class="fade-in-up flex justify-between items-center mb-12">
            <h1 class="text-4xl font-light text-gray-800 tracking-tight" id="taskTitle">
            The Aurora System&nbsp;<span class="info-icon w-6 h-6 bg-blue-500 rounded-full text-white text-xs font-semibold inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-blue-300 transition duration-300 ease-in-out transform hover:scale-110 ml-1" data-info="Aurora is a sub-project by the Sue Team which allows you to submit your task and upload your related files <em>asynchronously</em>. <a href='/what-is-aurora.html' class='text-sky-600 underline'>Learn more</a>" onclick="e.stopPropagation();">
                    i
                </span>
            </h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
            <?php 
            if ($_REQUEST["biz_id"]){
            ?>
            <div class="task-item bg-white rounded-lg shadow-sm p-6 fade-in-up transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-md cursor-pointer relative">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-medium text-gray-800 truncate flex items-center">
                        <?= $_REQUEST["to_do_name"]; ?>
                    </h2>
                </div>
                <div class="space-y-2 text-sm text-gray-600">
                   
                    <p class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <b>Submission Time:</b>&nbsp; <span id="currentTime">If you submit now, this will be the recorded time</span>
                    </p>
                    <p class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        *The teacher would believe you have completed the task before that.
                    </p>
                </div>
                <div class="mt-6">
                    <button id="submitBtn" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        Submit Now
                    </button>
                </div>
            </div>
            <?php }else { 

// 指定要遍历的目录
$directory = "./aurora-records"; 
if (is_dir($directory)) {
    // 使用 scandir 获取目录中的所有文件和子目录
    $files = scandir($directory);

    foreach ($files as $file) {
        // 过滤掉 . 和 .. 以及非文件的内容
        if ($file === '.' || $file === '..' || !is_file("$directory/$file")) {
            continue;
        }

        // 检查文件名是否以 $ares 开头
        if (strpos($file, "$ares") === 0) {
            // 提取文件名中 - 后面的部分
            $parts = explode('-', $file);
            if (count($parts) > 1) {
                $suffix = $parts[1]; 

                // 打开文件并读取内容
                $filePath = "$directory/$file";
                $content = file_get_contents($filePath);

                // 将 JSON 内容解码为数组
                $decodedData = json_decode($content, true);

                // 检查解码是否成功
                if (json_last_error() === JSON_ERROR_NONE) {
                    ?>
                    
        <div class="task-item bg-white rounded-lg shadow-sm p-6 fade-in-up transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-md cursor-pointer relative">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-medium text-gray-800 truncate flex items-center">
                        <?= $decodedData["task_name"]; ?>
                    </h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap bg-orange-100 text-orange-800">
                            <?= $decodedData["status"];?>
                        </span>
                </div>
                <div class="space-y-2 text-sm text-gray-600">
                   
                    <p class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <b>Submission Time:</b>&nbsp; <?= date("Y-m-d H:i:s", filemtime($filePath));  ?>
                    </p>
                   
                </div>
                
                    <?php if($decodedData["status"] === "pending") { 
                        // Convert hash array to JSON for JavaScript
                        $hashesJson = json_encode($decodedData["hash"]);
                        $taskId = $suffix; // Using the suffix as task ID
                    ?>
                    <div class="mt-6">
                    <button 
                        class="upload-btn w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out"
                        data-hashes='<?= $hashesJson ?>'
                        data-task-id="<?= $taskId ?>"
                        data-task-name="<?= htmlspecialchars($decodedData["task_name"]) ?>"
                    >
                        Upload Now
                    </button>
                    </div>
                    <?php }?>
                
            </div>
                <?php
                    
                } else {
                    die("Aurora Fatal Error.");
                }
            }
        }
    }
} else {
    die();
}


            
            ?>
            
            
            <?php } ?>
        </div>
    </div>

    <!-- Modal for initial submission -->
    <div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-8 m-4 max-w-md w-full">
            <h2 class="text-2xl font-semibold mb-6 text-gray-800">File Submission</h2>
            
            <!-- Initial submission form -->
            <div id="submissionForm">
                <p class="mb-4 text-gray-600">How many files would you like to submit?</p>
                <p class="mb-4 text-gray-500"><em>Note that this is immutable. You would only be able to submit such number of files later.</em></p>
                <div class="mb-6">
                    <input type="number" id="fileCount" min="1" max="10" value="2" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex justify-end space-x-4">
                    <button id="cancelBtn" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button id="confirmBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Confirm
                    </button>
                </div>
            </div>
            
            <!-- Status message area (hidden by default) -->
            <div id="statusMessage" class="hidden">
                <div id="statusContent" class="mb-6 text-center"></div>
                <div class="flex justify-center">
                    <button id="doneBtn" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Done
                    </button>
                </div>
            </div>
            
            <!-- Loading indicator (hidden by default) -->
            <div id="loadingIndicator" class="hidden">
                <div class="flex justify-center items-center mb-4">
                    <svg class="animate-spin h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <p class="text-center text-gray-600">Processing your submission...</p>
            </div>
        </div>
    </div>

    <!-- File Upload Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-8 m-4 max-w-xl w-full">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Upload Files</h2>
                <button id="closeUploadBtn" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="uploadForm">
                <p class="mb-4 text-gray-600">Please select the files to upload for <span id="taskNameDisplay" class="font-semibold"></span></p>
                <p class="mb-4 text-gray-500"><em>You need to upload <span id="fileCountDisplay">0</span> file(s).</em></p>
                
                <!-- Improved file upload area -->
                <div class="file-drop-area mb-6">
                    <svg class="w-12 h-12 text-blue-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <span class="file-msg">Drag & drop files here or click to browse</span>
                    <input type="file" id="fileInput" class="file-input" multiple>
                </div>
                
                <!-- Selected files list -->
                <div id="fileList" class="file-list mb-6 hidden"></div>
                
                <!-- Progress container -->
                <div id="progressContainer" class="mb-6 hidden">
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                        <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                    <p id="progressText" class="text-sm text-gray-600">0 of 0 files uploaded (0%)</p>
                </div>
                
                <div class="flex justify-end space-x-4">
                    <button id="cancelUploadBtn" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button id="startUploadBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Start Upload
                    </button>
                </div>
            </div>
            
            <!-- Upload status message (hidden by default) -->
            <div id="uploadStatusMessage" class="hidden">
                <div id="uploadStatusContent" class="mb-6 text-center"></div>
                <div class="flex justify-center">
                    <button id="uploadDoneBtn" class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>



<?php 
            if ($_REQUEST["biz_id"]){
            ?>
    <script>

        // Update current time
        function updateTime() {
            const now = new Date();
            const formattedTime = now.toLocaleString('en-US', { 
                year: 'numeric', 
                month: '2-digit', 
                day: '2-digit',
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: false 
            });
            document.getElementById('currentTime').textContent = formattedTime;
        }
        
        // Update time every second
        updateTime();
        setInterval(updateTime, 1000);

        // Modal functionality
        const modal = document.getElementById('modal');
        const submissionForm = document.getElementById('submissionForm');
        const statusMessage = document.getElementById('statusMessage');
        const statusContent = document.getElementById('statusContent');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const submitBtn = document.getElementById('submitBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        const doneBtn = document.getElementById('doneBtn');
        const fileCountInput = document.getElementById('fileCount');

        submitBtn.addEventListener('click', function() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('modal-open');
            
            // Reset modal state
            submissionForm.classList.remove('hidden');
            statusMessage.classList.add('hidden');
            loadingIndicator.classList.add('hidden');
        });

        cancelBtn.addEventListener('click', function() {
            closeModal();
        });

        doneBtn.addEventListener('click', function() {
            closeModal();
        });

        confirmBtn.addEventListener('click', function() {
            const fileCount = fileCountInput.value;
            
            // Show loading indicator
            submissionForm.classList.add('hidden');
            loadingIndicator.classList.remove('hidden');
            
            submitToAurora(fileCount);
        });

        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('modal-open');
        }

        function submitToAurora(fileCount) {
            // Create FormData to send to aurora-handle.php
            const formData = new FormData();
            formData.append('fileCount', fileCount);
            formData.append("biz_id", '<?= $_REQUEST["biz_id"];?>');
            formData.append("title", '<?= $_REQUEST["to_do_name"];?>');
            
            // Send data to aurora-handle.php
            fetch('aurora-handle.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Hide loading indicator
                loadingIndicator.classList.add('hidden');
                
                // Show success message
                statusMessage.classList.remove('hidden');
                statusContent.innerHTML = `
                    <div class="flex justify-center mb-4">
                        <svg class="w-16 h-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Submission Successful!</h3>
                    <p class="text-gray-600">You have set up to submit <span class="font-semibold">${fileCount}</span> file(s) for this task.</p>
                    <p class="text-gray-500 mt-2">You now have time to complete your task and submit the files.</p>
                `;
                
                console.log('Success:', data);
                window.location.href = "/aurora.php";
            })
            .catch(error => {
                // Hide loading indicator
                loadingIndicator.classList.add('hidden');
                
                // Show error message
                statusMessage.classList.remove('hidden');
                statusContent.innerHTML = `
                    <div class="flex justify-center mb-4">
                        <svg class="w-16 h-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Submission Failed</h3>
                    <p class="text-gray-600">There was an error processing your submission. Please try again.</p>
                `;
                
                console.error('Error:', error);
            });
        }
    </script>
    
    <?php } else { ?>
        <script src="/hammer.js?nn=1" type="text/javascript"></script>
    
    <script>
    
    const bodyElement = document.querySelector(".sue-navbar");
    const hammer = new Hammer(bodyElement);

    // 监听 swipe 事件
    hammer.on('swipeleft', () => {
      // 跳转到指定的 URL
      window.location.href = '/tasks.php'; // 替换为你想要跳转的 URL
    });
        // File Upload Modal functionality
        const uploadModal = document.getElementById('uploadModal');
        const uploadForm = document.getElementById('uploadForm');
        const uploadStatusMessage = document.getElementById('uploadStatusMessage');
        const uploadStatusContent = document.getElementById('uploadStatusContent');
        const closeUploadBtn = document.getElementById('closeUploadBtn');
        const cancelUploadBtn = document.getElementById('cancelUploadBtn');
        const startUploadBtn = document.getElementById('startUploadBtn');
        const uploadDoneBtn = document.getElementById('uploadDoneBtn');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const taskNameDisplay = document.getElementById('taskNameDisplay');
        const fileCountDisplay = document.getElementById('fileCountDisplay');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const fileDropArea = document.querySelector('.file-drop-area');
        
        // Current task data
        let currentTaskId = '';
        let currentHashes = [];
        let currentTaskName = '';
        let selectedFiles = [];
        
        // File input change handler
        fileInput.addEventListener('change', function() {
            updateFileList();
        });
        
        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            fileDropArea.classList.add('is-active');
        }
        
        function unhighlight() {
            fileDropArea.classList.remove('is-active');
        }
        
        fileDropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            updateFileList();
        }
        
        // Update file list display
        function updateFileList() {
            selectedFiles = Array.from(fileInput.files);
            
            if (selectedFiles.length > 0) {
                fileList.innerHTML = '';
                fileList.classList.remove('hidden');
                
                selectedFiles.forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    fileItem.innerHTML = `
                        <div class="file-item-name">${file.name}</div>
                        <div class="text-xs text-gray-500">${formatFileSize(file.size)}</div>
                    `;
                    fileList.appendChild(fileItem);
                });
                
                // Check if file count matches required count
                if (selectedFiles.length !== currentHashes.length) {
                    const warningMsg = document.createElement('div');
                    warningMsg.className = 'text-amber-600 text-sm mt-2';
                    warningMsg.textContent = `Please select exactly ${currentHashes.length} file(s).`;
                    fileList.appendChild(warningMsg);
                }
            } else {
                fileList.classList.add('hidden');
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Add event listeners to all upload buttons
        document.querySelectorAll('.upload-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Get task data from button attributes
                currentHashes = JSON.parse(this.getAttribute('data-hashes'));
                currentTaskId = this.getAttribute('data-task-id');
                currentTaskName = this.getAttribute('data-task-name');
                
                // Update modal with task info
                taskNameDisplay.textContent = currentTaskName;
                fileCountDisplay.textContent = currentHashes.length;
                
                // Reset file input and progress
                fileInput.value = '';
                fileList.innerHTML = '';
                fileList.classList.add('hidden');
                progressBar.style.width = '0%';
                progressText.textContent = '0 of 0 files uploaded (0%)';
                progressContainer.classList.add('hidden');
                
                // Show modal
                uploadModal.classList.remove('hidden');
                uploadModal.classList.add('flex');
                uploadForm.classList.remove('hidden');
                uploadStatusMessage.classList.add('hidden');
                document.body.classList.add('modal-open');
            });
        });
        
        // Close modal buttons
        closeUploadBtn.addEventListener('click', closeUploadModal);
        cancelUploadBtn.addEventListener('click', closeUploadModal);
        uploadDoneBtn.addEventListener('click', closeUploadModal);
        
        uploadModal.addEventListener('click', function(event) {
            if (event.target === uploadModal) {
                closeUploadModal();
            }
        });
        
        function closeUploadModal() {
            uploadModal.classList.add('hidden');
            uploadModal.classList.remove('flex');
            document.body.classList.remove('modal-open');
        }
        
        // Start upload button
        startUploadBtn.addEventListener('click', async function() {
            const files = fileInput.files;
            
            if (!files.length) {
                alert("Please select files to upload.");
                return;
            }
            
            if (files.length !== currentHashes.length) {
                alert(`Please select exactly ${currentHashes.length} file(s) to upload.`);
                return;
            }
            
            // Show progress container
            progressContainer.classList.remove('hidden');
            
            try {
                // Use the existing hashes instead of generating new ones
                await uploadFilesWithExistingHashes(files, currentHashes);
                
                // Show success message
                uploadForm.classList.add('hidden');
                uploadStatusMessage.classList.remove('hidden');
                uploadStatusContent.innerHTML = `
                    <div class="flex justify-center mb-4">
                        <svg class="w-16 h-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Upload Successful!</h3>
                    <p class="text-gray-600">All ${files.length} file(s) have been uploaded successfully.</p>
                    <p class="text-gray-500 mt-2">Your task has been completed.</p>
                `;
                
                // Update the task status in the UI
                const taskButton = document.querySelector(`[data-task-id="${currentTaskId}"]`);
                if (taskButton) {
                    const taskCard = taskButton.closest('.task-item');
                    const statusBadge = taskCard.querySelector('.inline-flex');
                    if (statusBadge) {
                        statusBadge.textContent = 'submitted';
                    }
                    taskButton.parentNode.innerHTML = '<p class="text-green-600 text-center mt-2">Files uploaded successfully</p>';
                }
                
                // Update the task status on the server
                updateTaskStatus(currentTaskId);
                
            } catch (error) {
                console.error('Upload error:', error);
                
                // Show error message
                uploadForm.classList.add('hidden');
                uploadStatusMessage.classList.remove('hidden');
                uploadStatusContent.innerHTML = `
                    <div class="flex justify-center mb-4">
                        <svg class="w-16 h-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Upload Failed</h3>
                    <p class="text-gray-600">There was an error uploading your files. Please try again.</p>
                    <p class="text-gray-500 mt-2">${error.message || ''}</p>
                `;
            }
        });
        
        // Upload files using existing hashes
        async function uploadFilesWithExistingHashes(files, hashes) {
            let completedUploads = 0;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const md5Hash = hashes[i];
                
                try {
                    // Get file ID using the existing hash
                    const id = await getID(file, md5Hash);
                    
                    // Get upload policy
                    const policy = await getPolicy(id);
                    
                    // Upload the file
                    await uploadFileWithProgress(policy, file);
                    
                    // Update progress
                    completedUploads++;
                    updateProgress(completedUploads, files.length);
                    
                } catch (error) {
                    console.error(`Error processing file ${file.name}:`, error);
                    throw new Error(`Error uploading ${file.name}: ${error.message}`);
                }
            }
            
            return completedUploads;
        }
        
        // Update progress UI
        function updateProgress(completed, total) {
            const percentage = Math.round((completed / total) * 100);
            progressBar.style.width = `${percentage}%`;
            progressText.textContent = `${completed} of ${total} files uploaded (${percentage}%)`;
        }
        
        // Update task status on the server
        function updateTaskStatus(taskId) {
            // Use aurora-handle.php with status=submitted parameter
            fetch(`aurora-handle.php?status=submitted&biz_id=${taskId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Status updated:', data);
                })
                .catch(error => {
                    console.error('Error updating status:', error);
                });
        }
        
        // Fixed getID function to handle file and hash properly
        async function getID(file, md5Hash) {
            if (!file || !md5Hash) {
                throw new Error("File or hash is missing");
            }
            
            try {
                const res = '<?php echo $res; ?>'; // Access token from PHP
                const ares = '<?php echo $ares; ?>'; // Reflection ID from PHP
                
                const url = 'https://api.seiue.com/chalk/netdisk/files';
                const file_name = file.name;

                const headers = new Headers({
                    'accept': 'application/json, text/plain, */*',
                    'authorization': 'Bearer ' + res,
                    'content-type': 'application/json',
                    'x-reflection-id': ares
                });

                const data = JSON.stringify({
                    'netdisk_owner_id': 0,
                    'name': file_name,
                    'parent_id': 0,
                    'path': '/',
                    'mime': file.type || 'application/octet-stream',
                    'type': 'other',
                    'size': file.size || 112,
                    'hash': md5Hash,
                    'status': 'uploading'
                });

                const response = await fetch(url, {
                    method: 'POST',
                    headers: headers,
                    body: data
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }

                const responseData = await response.json();
                return responseData.id;
                
            } catch (error) {
                console.error('Error getting file ID:', error);
                throw error;
            }
        }
        
        // Fixed getPolicy function
        async function getPolicy(the_id) {
            if (!the_id) {
                throw new Error("File ID is missing");
            }
            
            const res = '<?php echo $res; ?>'; // Access token from PHP
            const ares = '<?php echo $ares; ?>'; // Reflection ID from PHP
            const url = `https://api.seiue.com/chalk/netdisk/files/${the_id}/policy`;

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'accept': 'application/json, text/plain, */*',
                        'Authorization': 'Bearer ' + res,
                        'content-type': 'application/json',
                        'X-Reflection-Id': ares
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }

                const responseData = await response.json();
                return responseData;
            } catch (error) {
                console.error('Error getting policy:', error);
                throw error;
            }
        }
        
        // Fixed uploadFileWithProgress function
        function uploadFileWithProgress(policyData, file) {
            if (!policyData || !file) {
                return Promise.reject(new Error("Policy data or file is missing"));
            }
            
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                const formData = new FormData();

                // Append form data
                formData.append('key', policyData.object_key);
                formData.append('OSSAccessKeyId', policyData.access_key_id);
                formData.append('policy', policyData.policy);
                formData.append('signature', policyData.signature);
                formData.append('expire', policyData.expire);
                formData.append('callback', policyData.callback);
                formData.append('file', file);

                // Set up request
                xhr.open('POST', policyData.host, true);

                // Listen for load events (success)
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const responseData = JSON.parse(xhr.responseText);
                            resolve(responseData);
                        } catch (e) {
                            // If response is not JSON, still consider it a success
                            resolve({ success: true });
                        }
                    } else {
                        reject(new Error('Network response was not ok ' + xhr.statusText));
                    }
                };

                // Listen for error events
                xhr.onerror = function() {
                    reject(new Error('There has been a problem with your fetch operation'));
                };

                // Send the request with form data
                xhr.send(formData);
            });
        }
    </script>
    <?php } ?>
</body>
</html>