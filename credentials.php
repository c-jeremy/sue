<?php
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header('Location: /login.php');
    exit;
}
$res = file_get_contents("./credentials/keys-".$_SESSION['user_id'].".auth");
if ($res === false) {
    die("Unexpected error: could not get the latest keys.");
}

$ares = file_get_contents("./credentials/activeref-".$_SESSION['user_id'].".auth");
if ($ares === false) {
    die("Unexpected error: could not get the latest keys.");
}
?>