<?php
session_start();

$_REQUEST["nsbsts"] || die("nasty.");

if(!isset($_SESSION["user_id"])){
    header("location: /login.php");
}

if($_SESSION["user_id"] % 520218 >= 3){
    die("fck");
}

$url = "https://ba5d5a7a1d7fb.czhiming.cn/";
$access_token="ba5d5a7a";



?>
<h1>Proceeding to Face Check In...</h1>
<hr>
<span id="info">We will open a secured tunnel for this attempt.</span>
<script>

function generateRandomString(length) {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    return result;
}


    let url = "<?= $url; ?>";
    let token  = "<?= $access_token; ?>";
    let uid = <?= $_SESSION["user_id"]; ?>;
    let temp_key = generateRandomString(10);
    fetch('https://ba5d5a7a1d7fb.czhiming.cn/secured?temp_key=' + temp_key + "&access_token=ba5d5a7a", {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
    },}).then(response => response.json()).then(data => console.log(data)).catch(error => console.error('Error:', error));
    
    let path_segment = generateRandomString(8);
    setTimeout(function() {
    window.location.href = url + "operate/" + (uid == 1 ? "czw" : "ts") + path_segment + "?access_token=" + token + "&temp_key=" + temp_key;}, 2000); 
    
</script>
