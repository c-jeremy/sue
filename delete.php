<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    require "create_conn.php";
    $conn = create_conn();

    $user_id = $_SESSION['user_id'];
    $file_id = $_GET['id'];
    
    // First, get the file information
    $sql = "SELECT file_name, file_url FROM user_files WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Delete the file from the server
        $file_path = str_replace('http://yourdomain.com/', '', $row['file_url']);
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete the record from the database
        $delete_sql = "DELETE FROM user_files WHERE id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $file_id, $user_id);
        
        if ($delete_stmt->execute()) {
            header("Location: cloud-demo.php");
            exit();
        } else {
            echo "Error deleting record: " . $delete_stmt->error;
        }
        
        $delete_stmt->close();
    } else {
        echo "File not found or you don't have permission to delete it.";
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: cloud-demo.php");
    exit();
}
?>