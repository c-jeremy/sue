<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seiue Ultra File Upload</title>
    <script src="./twind.js"></script>
    <script src="./up.js.php"></script>
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
      @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .spinner {
            display: inline-block;
            animation: spin 1s linear infinite;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md">
        <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Seiue Ultra Upload</h1>
        <form id="uploadForm" class="space-y-6">
            <div class="flex items-center justify-center w-full">
                <label for="fileInput" class="flex flex-col items-center justify-center w-full h-64 border-2 border-blue-300 border-dashed rounded-xl cursor-pointer bg-blue-50 hover:bg-blue-100 transition duration-300 ease-in-out">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-12 h-12 mb-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                        <p class="text-xs text-gray-500">Multiple files allowed (Max 2GB each)</p>
                    </div>
                    <input id="fileInput" type="file" class="hidden" multiple />
                </label>
            </div>
            <div id="fileList" class="text-sm text-gray-600 space-y-2"></div>
            <button type="submit" class="w-full bg-blue-500 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-600 transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                Upload Files
            </button>
        </form>
        <div id="message" class="mt-6 text-center text-sm font-medium"></div>
        <!-- 想用progress bar 就加这个 -->
        <div id="progressContainer" class="mt-4 hidden">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300 ease-in-out" style="width: 0%"></div>
            </div>
            <p id="progressText" class="text-center text-sm text-gray-600 mt-2"></p>
        </div>
    </div>
    
    <script>
       const form = document.getElementById('uploadForm');
       const fileInput = document.getElementById('fileInput');
       const fileList = document.getElementById('fileList');
       const messageDiv = document.getElementById('message');
       const progressBar = document.getElementById('progressBar');
       const progressText = document.getElementById('progressText');

       fileInput.addEventListener('change', updateFileList);

       function updateFileList() {
           fileList.innerHTML = '';
           for (let file of fileInput.files) {
               const fileSize = (file.size / 1024 / 1024).toFixed(2);
               fileList.innerHTML += `
                   <div class="flex justify-between items-center bg-gray-100 p-2 rounded">
                       <span class="truncate">${file.name}</span>
                       <span class="text-xs text-gray-500">${fileSize} MB</span>
                   </div>`;
           }
       }

       form.addEventListener('submit', async (e) => {
           e.preventDefault();
           
           messageDiv.innerHTML ='<svg class="spinner animate-spin h-5 w-5 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle> <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>  </svg>&nbsp;Uploading...';
               
           messageDiv.className = 'mt-6 text-center text-sm font-medium text-blue-500';
           
           let upload_md5s = await submitFileInfos();

           const files = fileInput.files;
           if (files.length === 0) {
               messageDiv.textContent = 'Please select at least one file.';
               messageDiv.className = 'mt-6 text-center text-sm font-medium text-red-500';
               return;
           }

           

           const uploadPromises = Array.from(files, (file, index) => {
               return (async () => {
                   const formData = new FormData();
                   formData.append('filename', file.name);
                   formData.append("filesize", file.size);
                   if (upload_md5s[index]) {
                       formData.append("hash", upload_md5s[index]);
                   } else {
                       console.error(`No MD5 hash found for file at index ${index}`);
                       return `No MD5 hash for ${file.name}`;
                   }

                   try {
                       const response = await fetch('/uploadify.php', {
                           method: 'POST',
                           body: formData
                       });

                       if (!response.ok) {
                           console.log('Server API Response:', response.ok);
                           throw new Error(`HTTP error! status: ${response.status}`);
                       }

                       const result = await response.json();
                       console.log('Server API Response:', result);

                       const url = new URL(window.location.href);
                       const redirectParam = url.searchParams.get('redirect');
                       if (redirectParam) {
                           window.location.href = redirectParam + "&file=" + result.download_url;
                       }

                       return `${file.name} uploaded successfully`;
                   } catch (error) {
                       console.error('Error:', error);
                       return `Failed to upload ${file.name}`;
                   }
               })();
           });

           try {
               const results = await Promise.all(uploadPromises);
               console.log('All upload results:', results);
               messageDiv.innerHTML = `
                   <svg class="inline-block w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                   </svg>
                   All files are uploaded.
               `;
               messageDiv.className = 'mt-6 text-center text-sm font-medium text-green-500 flex items-center justify-center';
               fileInput.value = '';
               fileList.innerHTML = '';
           } catch (error) {
               console.error('Error:', error);
               messageDiv.textContent = 'An error occurred during upload.';
               messageDiv.className = 'mt-6 text-center text-sm font-medium text-red-500';
           }
       });

       
    </script>
</body>
</html>