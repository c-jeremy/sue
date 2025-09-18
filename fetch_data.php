<?php
$session_lifetime = 90 * 24 * 60 * 60; 
session_set_cookie_params($session_lifetime);
session_start();
require_once "./credentials.php";
require_once "./logger.php";
__($_SESSION["user_id"], "Viewed public tasks", $_ENV["ENV"], 1);
// 检查是否有URL被提交
if (isset($_GET['url']) || (isset($_GET['id']))) {
    
    if (isset($_GET['url'])){
        $url = $_GET['url'];
    
    // 这里可以添加对URL的验证，确保它是有效的
    // 解析URL以获取查询字符串
    $parsedUrl = parse_url($url);
    $queryString = $parsedUrl['query'];
    
    // 解析查询字符串为数组
    parse_str($queryString, $queryParams);
    
    // 解码modalQuery参数
    $modalQuery = urldecode($queryParams['modalQuery']);
    $modalQueryArray = json_decode($modalQuery, true);
    
    // 获取id
    $id = $modalQueryArray['id'];
    }
    // 输出结果
    if (isset($_GET['id'])){$id=$_GET['id'];}
    
    $url2 = "https://api.seiue.com/chalk/task/v2/tasks/{$id}/assignments?expand=is_excellent,assignee,team,submission,review,assignee.photo,submission.reviewed_attachments,submission.reviewed_attachments.reviewed_data";

    // 初始化cURL会话
    $ch = curl_init();
    
    // 设置cURL选项
    curl_setopt($ch, CURLOPT_URL, $url2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Pragma: no-cache',
        'Accept: application/json, text/plain, */*',
        'Authorization: Bearer '. $res,
        'X-Reflection-Id: '.$ares
    ]);
    
    // 执行cURL请求
    $response = curl_exec($ch);
    
    
    // 检查是否有错误发生
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
    } else {
        // 解码响应数据
        $data = json_decode($response, true);
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // if (json_last_error() === JSON_ERROR_NONE) {
        //     // 将数据写入文件
        //     $file = 'assignments.json';
        //     file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
        //     // 输出成功消息
        //     echo "数据已成功写入文件: $file";
        // } else {
        //     echo 'JSON解码错误: ' . json_last_error_msg();
        // }
    }
    
    // 关闭cURL会话
    curl_close($ch);
    
    //header('Location: display.html');
    //exit;
} else {
    echo "没有提供URL！";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Assignment Directory</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="flex flex-col md:flex-row">
                <!-- Directory Panel -->
                <div class="w-full md:w-1/3 border-r border-gray-200">
                    <div class="p-6">
                        <h1 class="text-2xl font-semibold text-gray-700 mb-6">Assignment Directory</h1>
                        <div id="directory" class="space-y-2 max-h-[70vh] overflow-y-auto pr-2"></div>
                    </div>
                </div>
                
                <!-- Content Panel -->
                <div class="w-full md:w-2/3">
                    <div id="content" class="p-6">
                        <div class="flex items-center justify-center h-64">
                            <div class="text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h2 class="mt-4 text-xl font-medium text-gray-600">Select an assignment to view details</h2>
                                <p class="mt-2 text-gray-500">Click on an assignment from the directory to see its information</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        var data = <?php echo $jsonData; ?>;
        createDirectory(data);

        function createDirectory(assignments) {
            const directoryDiv = document.getElementById('directory');
            assignments.forEach(assignment => {
                const item = document.createElement('div');
                item.className = 'p-4 rounded-md cursor-pointer transition-colors hover:bg-gray-100 border border-gray-100';
                
                // Determine status color
                let statusColor = 'bg-gray-200 text-gray-800';
                if (assignment.status === 'submitted') {
                    statusColor = 'bg-blue-100 text-blue-800';
                } else if (assignment.status === 'completed') {
                    statusColor = 'bg-green-100 text-green-800';
                } else if (assignment.status === 'overdue') {
                    statusColor = 'bg-red-100 text-red-800';
                } else if (assignment.status === 'approved') {
                    statusColor = 'bg-fuchsia-100 text-fuchsia-800';
                }
                
                item.innerHTML = `
                    <div class="flex justify-between items-center">
                        <span class="font-medium">${assignment.assignee.name}</span>
                        <span class="px-2 py-1 text-xs rounded-full ${statusColor}">${assignment.status}</span>
                    </div>
                `;
                
                item.onclick = () => displayAssignmentDetails(assignment);
                directoryDiv.appendChild(item);
            });
        }

        function displayAssignmentDetails(assignment) {
            const contentDiv = document.getElementById('content');
            
            // Format dates
            const createdDate = new Date(assignment.created_at).toLocaleString();
            const updatedDate = new Date(assignment.updated_at).toLocaleString();
            
            // Determine status indicators
            const isReadStatus = assignment.is_read ? 
                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Yes</span>' : 
                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">No</span>';
                
            const isOverdueStatus = assignment.is_overdue ? 
                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Yes</span>' : 
                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">No</span>';
                
            const isExcellentStatus = assignment.is_excellent ? 
                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Yes</span>' : 
                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">No</span>';
            
            contentDiv.innerHTML = `
                <div class="space-y-6">
                    <div class="border-b border-gray-200 pb-4">
                        <h2 class="text-2xl font-semibold text-gray-700">Assignment Details</h2>
                        <p class="text-sm text-gray-500">ID: ${assignment.id}</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Status</span>
                                <p class="mt-1">${assignment.status}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Created at</span>
                                <p class="mt-1">${createdDate}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Updated at</span>
                                <p class="mt-1">${updatedDate}</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Assignee</span>
                                <p class="mt-1">${assignment.assignee.name} (${assignment.assignee.ename || 'N/A'})</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Is Read</span>
                                <div class="mt-1">${isReadStatus}</div>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Is Overdue</span>
                                <div class="mt-1">${isOverdueStatus}</div>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Is Excellent</span>
                                <div class="mt-1">${isExcellentStatus}</div>
                            </div>
                        </div>
                    </div>
            `;

            if (assignment.submission) {
                contentDiv.innerHTML += `
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <h3 class="text-lg font-medium text-gray-700 mb-3">Submission Information</h3>
                        
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Submission Status</span>
                                <p class="mt-1">${assignment.submission.status}</p>
                            </div>
                            
                            <div>
                                <span class="text-sm font-medium text-gray-500">Content</span>
                                <div class="mt-2 p-3 bg-gray-50 rounded-md text-sm">
                                    ${assignment.submission.content_text || 'No content provided'}
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Attachments</h4>
                                <div class="space-y-2">
                                    ${assignment.submission.attachments && assignment.submission.attachments.length > 0 ? 
                                        assignment.submission.attachments.map(attachment => {
                                            const sizeInMB = (attachment.size / (1024 * 1024)).toFixed(2);
                                            return `
                                                <a href="https://api.seiue.com/chalk/netdisk/files/${attachment.hash}/url" 
                                                   class="flex items-center p-2 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors"
                                                   rel="noreferrer noopener" target="_blank">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <div>
                                                        <span class="text-sm font-medium">${attachment.name}</span>
                                                        <span class="text-xs text-gray-500 ml-2">(${sizeInMB} MB)</span>
                                                    </div>
                                                </a>
                                            `;
                                        }).join('') : 
                                        '<p class="text-sm text-gray-500">No attachments</p>'
                                    }
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                contentDiv.innerHTML += `
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <div class="flex items-center justify-center h-32 bg-gray-50 rounded-md">
                            <div class="text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-2 text-gray-500">No submission yet</p>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            contentDiv.innerHTML += `</div>`;
        }
    </script>
</body>
</html>