<?php header("content-type: text/javascript");
const CREDENTIALS_PATH ="./credentials/";
?>

// Global configuration
const MAX_SINGLE_UPLOAD_LENGTH = 2 * 1024 * 1024 * 1024; // 2GB
const MAX_PART_LENGTH = 100 * 1024 * 1024; // 100MB
const MAX_RETRIES = 5; // Increased retries
const RETRY_DELAY = 1000; // 1 second delay between retries
const BASE_URL = 'https://api.seiue.com';

// Authentication credentials
const AUTH_TOKEN = '<?php echo file_get_contents(CREDENTIALS_PATH . "keys-19.auth"); ?>';
const REFLECTION_ID = '3535471';

/**
 * Generate a random string of specified length
 */
function generateRandomString(length) {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

/**
 * Get standard headers for API requests
 */
function getStandardHeaders() {
    return {
        'accept': 'application/json, text/plain, */*',
        'authorization': 'Bearer ' + AUTH_TOKEN,
        'content-type': 'application/json',
        'x-reflection-id': REFLECTION_ID,
        'x-role': 'student'
    };
}

/**
 * Register a file in the system and get its ID
 */
async function getFileID(file) {
    console.log(`Getting file ID for ${file.name} (${file.size} bytes)`);
    
    // Generate a unique hash for the file
    const fileHash = generateRandomString(32);
    
    try {
        const url = `${BASE_URL}/chalk/netdisk/files`;
        const data = {
            'netdisk_owner_id': 0,
            'name': file.name,
            'parent_id': 0,
            'path': '/',
            'mime': file.type || 'application/octet-stream',
            'type': 'other',
            'size': file.size,
            'hash': fileHash,
            'status': 'uploading'
        };
        
        const response = await fetch(url, {
            method: 'POST',
            headers: getStandardHeaders(),
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Failed to get file ID: ${response.status} ${errorText}`);
        }
        
        const responseData = await response.json();
        console.log(`Got file ID: ${responseData.id}, hash: ${fileHash}`);
        
        // Return both the ID and hash
        return {
            id: responseData.id,
            hash: fileHash
        };
    } catch (error) {
        console.error(`Error getting file ID for ${file.name}:`, error);
        throw error;
    }
}

/**
 * Get upload policy for a file
 */
async function getUploadPolicy(fileId) {
    console.log(`Getting upload policy for file ID: ${fileId}`);
    
    try {
        const url = `${BASE_URL}/chalk/netdisk/files/${fileId}/policy`;
        const response = await fetch(url, {
            method: 'GET',
            headers: getStandardHeaders()
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Failed to get upload policy: ${response.status} ${errorText}`);
        }
        
        const policy = await response.json();
        console.log(`Got upload policy for file ID ${fileId}`);
        return policy;
    } catch (error) {
        console.error(`Error getting upload policy for file ID ${fileId}:`, error);
        throw error;
    }
}

/**
 * Upload a file using the provided policy
 */
function uploadFile(policy, file) {
    console.log(`Uploading file ${file.name} to ${policy.host}`);
    
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        
        // Add policy fields to form data
        formData.append('key', policy.object_key);
        formData.append('OSSAccessKeyId', policy.access_key_id);
        formData.append('policy', policy.policy);
        formData.append('signature', policy.signature);
        formData.append('expire', policy.expire);
        formData.append('callback', policy.callback);
        formData.append('file', file);
        
        // Create and configure XHR
        const xhr = new XMLHttpRequest();
        xhr.open('POST', policy.host, true);
        
        // Track upload progress
        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                const percentComplete = (event.loaded / event.total) * 100;
                console.log(`Upload progress: ${percentComplete.toFixed(2)}%`);
            }
        };
        
        // Handle completion
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    // Try to parse response as JSON
                    const responseData = JSON.parse(xhr.responseText);
                    console.log(`Upload successful for ${file.name}`);
                    resolve({
                        success: true,
                        response: responseData
                    });
                } catch (e) {
                    // If not JSON, still consider it successful
                    console.log(`Upload successful for ${file.name} (non-JSON response)`);
                    resolve({
                        success: true,
                        rawResponse: xhr.responseText
                    });
                }
            } else {
                console.error(`Upload failed for ${file.name}: ${xhr.status} ${xhr.statusText}`);
                reject(new Error(`Upload failed: ${xhr.status} ${xhr.statusText}`));
            }
        };
        
        // Handle network errors
        xhr.onerror = function() {
            console.error(`Network error during upload of ${file.name}`);
            reject(new Error('Network error during upload'));
        };
        
        // Send the request
        xhr.send(formData);
    });
}

/**
 * Get the public URL for a file with retry logic
 */
async function getFileUrl(fileHash, retryCount = 0) {
    console.log(`Getting URL for file hash: ${fileHash} (attempt ${retryCount + 1}/${MAX_RETRIES})`);
    
    try {
        const url = `${BASE_URL}/chalk/netdisk/files/${fileHash}/url`;
        const response = await fetch(url, {
            method: 'GET',
            headers: getStandardHeaders()
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error(`Failed to get URL: ${response.status} ${errorText}`);
            
            if (retryCount < MAX_RETRIES - 1) {
                console.log(`Retrying URL fetch for hash ${fileHash} in ${RETRY_DELAY}ms`);
                await new Promise(resolve => setTimeout(resolve, RETRY_DELAY));
                return getFileUrl(fileHash, retryCount + 1);
            }
            throw new Error(`Failed to get URL after ${MAX_RETRIES} attempts: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.url) {
            console.error(`No URL in response for hash ${fileHash}:`, data);
            
            if (retryCount < MAX_RETRIES - 1) {
                console.log(`Response missing URL, retrying in ${RETRY_DELAY}ms`);
                await new Promise(resolve => setTimeout(resolve, RETRY_DELAY));
                return getFileUrl(fileHash, retryCount + 1);
            }
            throw new Error(`No URL in response after ${MAX_RETRIES} attempts`);
        }
        
        console.log(`Got URL for file hash ${fileHash}: ${data.url}`);
        return data.url;
    } catch (error) {
        console.error(`Error getting file URL for hash ${fileHash}:`, error);
        
        if (retryCount < MAX_RETRIES - 1) {
            console.log(`Retrying URL fetch after error in ${RETRY_DELAY}ms`);
            await new Promise(resolve => setTimeout(resolve, RETRY_DELAY));
            return getFileUrl(fileHash, retryCount + 1);
        }
        throw error;
    }
}

/**
 * Update file status to 'ready'
 */
async function updateFileStatus(fileId) {
    console.log(`Updating file status for ID: ${fileId}`);
    
    try {
        const url = `${BASE_URL}/chalk/netdisk/files/${fileId}`;
        const data = {
            'status': 'ready'
        };
        
        const response = await fetch(url, {
            method: 'PATCH',
            headers: getStandardHeaders(),
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error(`Failed to update file status: ${response.status} ${errorText}`);
            return false;
        }
        
        console.log(`Successfully updated file status for ID ${fileId}`);
        return true;
    } catch (error) {
        console.error(`Error updating file status for ID ${fileId}:`, error);
        return false;
    }
}

/**
 * Main function to handle file uploads
 */
async function submitFileInfos() {
    console.log("submitFileInfos called");
    
    try {
        // Get files from input element
        const fileInput = document.getElementById('fileInput');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            console.error("No files selected or file input not found");
            return [];
        }
        
        const files = fileInput.files;
        console.log(`Processing ${files.length} file(s)`);
        
        // Array to store file URLs
        const fileUrls = [];
        
        // Process each file
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            console.log(`Processing file ${i+1}/${files.length}: ${file.name} (${file.size} bytes)`);
            
            try {
                // Get file ID and hash
                const fileInfo = await getFileID(file);
                const fileId = fileInfo.id;
                const fileHash = fileInfo.hash;
                
                // Get upload policy
                const policy = await getUploadPolicy(fileId);
                
                // Upload the file
                await uploadFile(policy, file);
                
                // Update file status to 'ready'
                await updateFileStatus(fileId);
                
                // Wait a moment for the server to process the file
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                // Get the file URL - use the hash we generated
                const fileUrl = await getFileUrl(fileHash);
                
                // Add URL to the array
                fileUrls.push(fileUrl);
                console.log(`Successfully processed file ${file.name}, URL: ${fileUrl}`);
            } catch (fileError) {
                console.error(`Error processing file ${file.name}:`, fileError);
                // Continue with next file instead of failing completely
            }
        }
        
        // Log results
        if (fileUrls.length > 0) {
            console.log(`Successfully uploaded ${fileUrls.length} file(s). URLs:`, fileUrls);
        } else {
            console.error("No files were successfully uploaded");
        }
        
        // Always return the array, even if empty
        return fileUrls;
    } catch (error) {
        console.error("Fatal error in submitFileInfos:", error);
        // Return empty array in case of error to avoid undefined
        return [];
    }
}

// Add a global error handler to catch any unhandled promise rejections
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
});

// Log when the script is loaded
console.log("File upload script loaded successfully");