<?php
($_SERVER["REQUEST_METHOD"] === "POST") || die("Shoo. N.H.");
// Start session
session_start();
$fromlogin=1;

// Database connection parameters
$host = 'localhost';
$dbname = 'seiue_db';
$username = 'seiue_db';
$password = '12345678';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['stat' => 'failed', 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Get POST data
$data = $_POST;

// Check if email and password are provided
if (isset($data['email']) && isset($data['password'])) {
    $email = $conn->real_escape_string($data['email']);
    $password = $conn->real_escape_string($data['password']);

    // Query the database for the user with this email
    $query = "SELECT * FROM users WHERE (email = ? OR username = ? ) AND password = ? AND user_type != 'suspended'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $email, $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the query returns a result, set the session variable
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $username = $row["username"];
        $_SESSION["username"]= $row['username'];
        $email = $row['email'];
        
require_once "./logger.php";
__($_SESSION["user_id"], "Login",$_ENV["ENV"],1);
       // require "./login-notify.php";
        echo json_encode(['stat' => 'success']);
        
        
    } else {
        echo json_encode(['stat' => 'failed', 'message' => 'Invalid email or password']);
    }

    // Close statement and connection
    $stmt->close();
} else {
    echo json_encode(['stat' => 'failed', 'message' => 'Email and password required']);
}

$conn->close();
?>