<?php
header("Access-Control-Allow-Origin: *");  // 允许所有域访问
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

header("content-type: application/json");

echo json_encode(["key" => file_get_contents("./credentials/keys-19.auth")]);



?>