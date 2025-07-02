<?php
// Temporary debugging - remove for production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
* Requires the "PHP Email Form" library
* The "PHP Email Form" library is available only in the pro version of the template
* The library should be uploaded to: vendor/php-email-form/php-email-form.php
* For more info and help: https://bootstrapmade.com/php-email-form/
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../assets/vendor/phpmailer/src/Exception.php';
require '../assets/vendor/phpmailer/src/PHPMailer.php';
require '../assets/vendor/phpmailer/src/SMTP.php';

// Load local configuration for development - THIS FILE SHOULD BE IN .GITIGNORE
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize and validate input data
    $name = filter_var(trim($_POST["name"]), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $subject = filter_var(trim($_POST["subject"]), FILTER_SANITIZE_STRING);
    $message = filter_var(trim($_POST["message"]), FILTER_SANITIZE_STRING);

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Handle error - you can output a simple error message
        http_response_code(400);
        echo "Por favor, completa todos los campos y proporciona un email válido.";
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0;                                 // Disable verbose debug output for production
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.gmail.com';                       // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        
        // Use server environment variables from Hostinger.
        $mail->Username = getenv('SMTP_USER');
        $mail->Password = getenv('SMTP_PASSWORD');

        // If environment variables are not set, fall back to config.php for local development
        if (empty($mail->Username) && defined('SMTP_USER')) {
            $mail->Username = SMTP_USER;
        }
        if (empty($mail->Password) && defined('SMTP_PASSWORD')) {
            $mail->Password = SMTP_PASSWORD;
        }
        
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                                    // TCP port to connect to

        // Recipients
        $mail->setFrom($email, $name);
        $mail->addAddress('alberto@asomstudio.ai', 'ASOM Studio');     // Add a recipient
        $mail->addReplyTo($email, $name);

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Nuevo mensaje de contacto: ' . $subject;
        $mail->Body    = "<b>Nombre:</b> {$name}<br><b>Email:</b> {$email}<br><br><b>Mensaje:</b><br>{$message}";
        $mail->AltBody = "Nombre: {$name}\nEmail: {$email}\n\nMensaje:\n{$message}";

        $mail->send();
        // The script `validate.js` expects an "OK" response on success
        echo 'OK';
    } catch (Exception $e) {
        http_response_code(500);
        echo "El mensaje no pudo ser enviado. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    // Not a POST request
    http_response_code(403);
    echo "Hubo un problema con tu envío, por favor intenta de nuevo.";
}
?> 