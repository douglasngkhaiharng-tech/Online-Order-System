<?php
require_once dirname(__DIR__) . '/psr/Log/LoggerInterface.php';
require_once dirname(__DIR__) . '/phpmailer/src/OAuthTokenProvider.php';
require_once dirname(__DIR__) . '/phpmailer/src/PHPMailer.php';
require_once dirname(__DIR__) . '/phpmailer/src/SMTP.php';
require_once dirname(__DIR__) . '/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendResetEmail($toEmail, $toName, $resetLink) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'yourgmail@gmail.com';
        $mail->Password = '你的16位AppPassword'; // ❗不是普通密码

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('yourgmail@gmail.com', 'Online Order System');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Password';
        $mail->Body = "Click here: $resetLink";

        $mail->send();
        return true;

    } catch (Exception $e) {
        return $mail->ErrorInfo; // 👈 很重要
    }
}