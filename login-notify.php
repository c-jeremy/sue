<?php
(isset($fromlogin)) || die("shoo. n.h.");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require './mailer/phpmailer/src/Exception.php';
require './mailer/phpmailer/src/PHPMailer.php';
require './mailer/phpmailer/src/SMTP.php';

date_default_timezone_set('Asia/Shanghai');

// 获取当前日期和时间
$currentDateTime = date('F j, Y • g:i A');
// 输出格式化的日期时间
$formatted= $currentDateTime . ' CST';
// 获取 User-Agent 字符串
$userAgent = $_SERVER['HTTP_USER_AGENT'];



$clientIp = $_SERVER['REMOTE_ADDR'];

// 使用 ipinfo.io API 获取地理位置信息
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://ipinfo.io/$clientIp/json");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

// 解析 JSON 响应
$geoData = json_decode($response, true);

// 提取所需信息
$city = isset($geoData['city']) ? $geoData['city'] : 'Unknown';
$country = isset($geoData['country']) ? $geoData['country'] : 'Unknown';

$formatted_geo = $city . ", " . $country . " ($clientIp)";

// 定义一个函数来解析 User-Agent 字符串
function parseUserAgent($userAgent) {
    $device = '';
    $browser = '';

    // 检测设备类型
    if (preg_match('/(iPhone|iPad|iPod)/i', $userAgent)) {
        $device = 'iOS Device';
    } elseif (preg_match('/Android/i', $userAgent)) {
        $device = 'Android Device';
    } elseif (preg_match('/Macintosh/i', $userAgent)) {
        $device = 'Mac';
    } elseif (preg_match('/Windows NT/i', $userAgent)) {
        $device = 'Windows PC';
    } else {
        $device = 'Unknown Device';
    }

    // 检测浏览器类型
    if (preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Edge/i', $userAgent)) {
        $browser = 'Edge';
    } elseif (preg_match('/Trident/i', $userAgent)) {
        $browser = 'Internet Explorer';
    } else {
        $browser = 'Unknown Browser';
    }

    return "$device • $browser";
}
$parsedua =parseUserAgent($userAgent);

$template = <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
                padding: 20px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #000000; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 600px; margin: auto; background-color: #000000;">
        <tr>
            <td style="padding: 80px 40px 60px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td align="left">
                            <h1 align="center" style="margin: 0; font-size: 48px; font-weight: 600; color: #808080; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Hey <span style="color: #FF99CC;">$username</span></h1>
                            <p style="margin: 20px 0 0; color: #bfbfbf; font-size: 16px; line-height: 1.5;">Howdy! How's everything going lately? We have just detected a new login on your account. Its general information is listed below.</p>
                            <p style="margin: 20px 0 0; color: #bfbfbf; font-size: 16px; line-height: 1.5;">If this was you, it would be safe to ignore this email; otherwise it would be likely that your Seiue Ultra account is hacked. In such case, you should take actions immediately.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 0 40px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background: rgba(30, 30, 30, 0.4); border-radius: 16px; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);">
                    <tr>
                        <td style="padding: 30px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 20px; background: rgba(40, 40, 40, 0.5); border-radius: 12px; margin-bottom: 15px; display: block;">
                                        <p style="margin: 0; color: #808080; font-size: 14px;">Time</p>
                                        <p style="margin: 8px 0 0; color: #FF99CC; font-size: 16px;">$formatted</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="15"></td>
                                </tr>
                                <tr>
                                    <td style="padding: 20px; background: rgba(40, 40, 40, 0.5); border-radius: 12px; margin-bottom: 15px; display: block;">
                                        <p style="margin: 0; color: #808080; font-size: 14px;">Location</p>
                                        <p style="margin: 8px 0 0; color: #FF99CC; font-size: 16px;">$formatted_geo</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="15"></td>
                                </tr>
                                <tr>
                                    <td style="padding: 20px; background: rgba(40, 40, 40, 0.5); border-radius: 12px; display: block;">
                                        <p style="margin: 0; color: #808080; font-size: 14px;">Device</p>
                                        <p style="margin: 8px 0 0; color: #FF99CC; font-size: 16px;">$parsedua</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="30"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="mailto:cao_zhiming2019@163.com?cc=tangziyan2026@i.pkuschool.edu.cn&subject=Help%20me%20secure%20my%20account&body=My%20account%20($username%20&lt;$email&gt;)%20has%20an%20unidentified%20login.%20" style="display: block; padding: 16px 24px; background-color: #FF99CC; color: #ffffff; text-decoration: none; text-align: center; border-radius: 12px; font-size: 16px; font-weight: 500;">I don't recognize this login</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 40px; text-align: center;">
                <p style="margin: 0; font-size: 13px; color: #666666;">This is an automated message, please do not reply. You received this email because someone logged in to the account with binded email address $email.  </p>
                <p style="margin: 8px 0 0; font-size: 13px; color: #666666;">© 2024 Seiue Ultra. All rights reserved.</p>
            </td>
        </tr>
    </table>
</body>
</html>
EOT;


$name = "Seiue Ultra Team";
$from = "cao_zhiming2019@163.com";
$subj = "[SEIUE ULTRA] Please review this login";


$mail = new PHPMailer(true);

try {
    //Server settings                     //Enable verbose debug output
    $mail->isSMTP();        
    $mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';
    //Send using SMTP
    $mail->Host       = 'smtp.163.com';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = 'czhimingcn@163.com';                     //SMTP username
    $mail->Password   = 'HONYARKEXLEZHKVV';                               //SMTP password
   $mail->SMTPSecure = 'ssl';            //Enable implicit TLS encryp
    $mail->Port       = 994;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //Recipients
    $mail->setFrom('czhimingcn@163.com', $name);
    $mail->addAddress($email);     //Add a recipient    //Name is optional
    $mail->addReplyTo($from, $name);
    

    //Attachments
   // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
  //  $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = $subj;
    $mail->Body    = $template;
    $mail->AltBody = 'Please use a mail client supporting html.';

    $mail->send();
} catch (Exception $e) {
    echo "<script>alert('发送失败。错误： {$mail->ErrorInfo})';</script>";
}

?>