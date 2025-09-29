<?php
// SMTP configuration for outbound emails
// Fill these with your SMTP provider settings (e.g., Gmail/Office365/Mailtrap)

define('SMTP_ENABLED', true);           // set to true to enable SMTP via PHPMailer
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);               // 587 (TLS) or 465 (SSL)
define('SMTP_ENCRYPTION', 'tls');       // 'tls' or 'ssl'
define('SMTP_USERNAME', 'username@example.com');
define('SMTP_PASSWORD', 'your-app-password-here');
define('MAIL_FROM', 'no-reply@mkombozi.tz');
define('MAIL_FROM_NAME', APP_NAME);


