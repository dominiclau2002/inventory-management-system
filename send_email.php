<?php
//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require_once 'vendor/autoload.php';

// Load environment variables from .env file
function loadEnvVariables() {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

/**
 * Send email using PHPMailer with environment configuration
 *
 * @param string $to_email Recipient email address
 * @param string $to_name Recipient name (optional)
 * @param string $subject Email subject
 * @param string $html_body HTML email body
 * @param string $alt_body Plain text alternative body (optional)
 * @param bool $debug Enable debug mode (optional, default: false)
 * @return array Result array with 'success' boolean and 'message' string
 */
function sendEmail($to_email, $to_name = '', $subject = '', $html_body = '', $alt_body = '', $debug = false) {
    // Load environment variables
    loadEnvVariables();

    // Validate required environment variables
    $required_vars = ['EMAIL_HOST', 'EMAIL_USERNAME', 'EMAIL_PASSWORD', 'EMAIL_FROM_NAME'];
    foreach ($required_vars as $var) {
        if (!isset($_ENV[$var]) || empty($_ENV[$var])) {
            return ['success' => false, 'message' => "Missing environment variable: $var"];
        }
    }

    // Set default values for optional company variables
    if (!isset($_ENV['COMPANY_NAME'])) $_ENV['COMPANY_NAME'] = 'Company';
    if (!isset($_ENV['TEAM_NAME'])) $_ENV['TEAM_NAME'] = 'IT Team';
    if (!isset($_ENV['SYSTEM_NAME'])) $_ENV['SYSTEM_NAME'] = 'Inventory Management System';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        if ($debug) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        }
        $mail->isSMTP();
        $mail->Host       = $_ENV['EMAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['EMAIL_USERNAME'];
        $mail->Password   = $_ENV['EMAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom($_ENV['EMAIL_USERNAME'], $_ENV['EMAIL_FROM_NAME']);
        $mail->addAddress($to_email, $to_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        if (!empty($alt_body)) {
            $mail->AltBody = $alt_body;
        }

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];

    } catch (Exception $e) {
        return ['success' => false, 'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"];
    }
}


?>