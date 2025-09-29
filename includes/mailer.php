<?php
require_once __DIR__ . '/../config/mail.php';

// If SMTP is enabled and PHPMailer is installed via Composer, use it. Else fallback to mail().
function sendEmail($to, $subject, $message) {
    if (defined('SMTP_ENABLED') && SMTP_ENABLED) {
        // attempt PHPMailer
        try {
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                if (SMTP_ENCRYPTION === 'ssl') {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = SMTP_PORT ?: 465;
                } else {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = SMTP_PORT ?: 587;
                }
                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;
                $mail->AltBody = strip_tags(str_replace(['<br>','<br/>','<br />'],"\n", $message));
                return $mail->send();
            }
        } catch (Throwable $e) {
            // fall back to mail()
        }
    }
    $headers = "MIME-Version: 1.0\r\n"
             . "Content-type:text/html;charset=UTF-8\r\n"
             . 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>' . "\r\n";
    return @mail($to, $subject, $message, $headers);
}


