<?php
header("content-type: text/javascript");
session_start();
// Check session variable
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header('Location: login.php');
    exit;
}
$ares = file_get_contents("./credentials/activeref-".$_SESSION['user_id'].".auth");
if ($ares === false) {
    die("Unexpected error: could not get the latest keys.");
}
$res = file_get_contents("./credentials/keys-".$_SESSION['user_id'].".auth");
if ($res === false) {
    die("Unexpected error: could not get the latest keys.");
}
?>
let arr_of_md5 = [];
let max_file_id = -1;
function generateRandomString(length) {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

async function getID(res, ares, file, md5Hash) {
    try {
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
            'mime': file.type,
            'type': 'other',
            'size': 112, // Use actual file size? no!
            'hash': md5Hash,
            'status': 'uploading'
        });

        try {
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
            console.error('There has been a problem with your fetch operation:', error);
            throw error;
        }
    } catch (error) {
        console.error('Error calculating MD5:', error);
        throw error;
    }
}

async function getPolicy(res, ares, the_id) {
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
        console.error('There has been a problem with your fetch operation:', error);
        throw error;
    }
}

function uploadFileWithProgress(policyData, file, progressElement) {
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

        let lastTime = 0;
        let lastLoaded = 0;

    

        // Listen for load events (success)
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                const responseData = JSON.parse(xhr.responseText);
                resolve(responseData);
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

async function submitFileInfos() {
  const fileInput = document.getElementById('fileInput');
  const files = fileInput.files;
  const progressContainer = document.getElementById('progressContainer');
  if (!files.length) {
    //alert("Please select one or more files first.");
    return;
  }
  max_file_id = files.length - 1;

  const res = '<?php echo $res; ?>'; // Replace with actual access token
  const ares = '<?php echo $ares; ?>'; // Replace with actual reflection ID
  //const fileListElement = document.getElementById('fileList');

  //fileListElement.innerHTML = ''; // Clear previous uploads
  let completedUploads = 0;
  if (progressContainer){
    progressContainer.classList.remove('hidden');
    
    updateProgress(completedUploads, files.length);
  }
  
  for (let i = 0; i < files.length; i++) {
    const file = files[i];
    let md5Hash = generateRandomString(32);
    arr_of_md5.push(md5Hash);

    try {
      const id = await getID(res, ares, file, md5Hash);
      const policy = await getPolicy(res, ares, id);
      const progressElement = document.createElement('p');
      progressElement.className = 'progress-container';
      //fileListElement.appendChild(progressElement);

      const msg = await uploadFileWithProgress(policy, file, progressElement);
      
      if (progressContainer){
          completedUploads++;
          updateProgress(completedUploads, files.length);
      }

    } catch (error) {
      console.error(`Error processing file ${file.name}:`, error);
      //fileListElement.innerHTML += `<p>Error processing file ${file.name}.</p>`;
    }
  }
  
  return arr_of_md5;
}

function updateProgress(completed, total) {
   const percentage = Math.round((completed / total) * 100);
   progressBar.style.width = `${percentage}%`;
   progressText.textContent = `${completed} of ${total} files uploaded (${percentage}%)`;
   if (completed === total) {
       setTimeout(() => {
           progressContainer.classList.add('hidden');
       }, 2000);
   }
}