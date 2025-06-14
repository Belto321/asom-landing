<?php
/**
 * ASOM Studio Contact Form Handler
 * Secure PHP form processing for shared hosting environments
 * Compatible with Hostinger and similar providers
 */

// Start session for CSRF protection
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Configuration
$config = [
    'recipient_email' => 'alberto@asomstudio.ai',
    'from_email' => 'noreply@asomstudio.ai',
    'from_name' => 'ASOM Studio Contact Form',
    'subject_prefix' => '[ASOM Studio] ',
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'allowed_origins' => ['asomstudio.ai', 'www.asomstudio.ai', 'localhost'],
    'rate_limit' => [
        'max_attempts' => 5,
        'time_window' => 3600 // 1 hour
    ]
];

// Response function
function sendResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit;
}

// Input sanitization
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Email validation
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Rate limiting (simple file-based)
function checkRateLimit($ip, $config) {
    $rate_file = sys_get_temp_dir() . '/asom_rate_' . md5($ip);
    
    if (file_exists($rate_file)) {
        $data = json_decode(file_get_contents($rate_file), true);
        
        // Clean old attempts
        $data['attempts'] = array_filter($data['attempts'], function($time) use ($config) {
            return (time() - $time) < $config['rate_limit']['time_window'];
        });
        
        // Check if limit exceeded
        if (count($data['attempts']) >= $config['rate_limit']['max_attempts']) {
            return false;
        }
    } else {
        $data = ['attempts' => []];
    }
    
    // Add current attempt
    $data['attempts'][] = time();
    file_put_contents($rate_file, json_encode($data));
    
    return true;
}

// CSRF token generation and validation
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Origin validation
function validateOrigin($config) {
    if (!isset($_SERVER['HTTP_ORIGIN']) && !isset($_SERVER['HTTP_REFERER'])) {
        return true; // Allow direct access for testing
    }
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);
    $origin = str_replace(['http://', 'https://'], '', $origin);
    
    return in_array($origin, $config['allowed_origins']) || 
           in_array('localhost', $config['allowed_origins']) && 
           (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false);
}

// Main processing
try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
    }
    
    // Get client IP
    $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
                 $_SERVER['HTTP_X_REAL_IP'] ?? 
                 $_SERVER['REMOTE_ADDR'] ?? 
                 'unknown';
    
    // Validate origin
    if (!validateOrigin($config)) {
        sendResponse(false, 'Invalid origin');
    }
    
    // Rate limiting
    if (!checkRateLimit($client_ip, $config)) {
        sendResponse(false, 'Too many requests. Please try again later.');
    }
    
    // Get and validate input data
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $company = sanitizeInput($_POST['company'] ?? '');
    $service = sanitizeInput($_POST['service'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = 'Name must be between 2 and 100 characters';
    } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $name)) {
        $errors[] = 'Name contains invalid characters';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (!empty($company) && strlen($company) > 100) {
        $errors[] = 'Company name is too long';
    }
    
    if (empty($service)) {
        $errors[] = 'Please select a service';
    } elseif (!in_array($service, ['ai-development', 'web-development', 'digital-strategy', 'brand-identity', 'consultation'])) {
        $errors[] = 'Invalid service selection';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    } elseif (strlen($message) < 10 || strlen($message) > 2000) {
        $errors[] = 'Message must be between 10 and 2000 characters';
    }
    
    // Spam detection (simple heuristics)
    $spam_indicators = [
        'viagra', 'casino', 'loan', 'bitcoin', 'crypto', 'investment',
        'make money', 'click here', 'buy now', 'limited time'
    ];
    
    $full_text = strtolower($name . ' ' . $email . ' ' . $company . ' ' . $message);
    foreach ($spam_indicators as $indicator) {
        if (strpos($full_text, $indicator) !== false) {
            // Log spam attempt but don't inform user
            error_log("Spam attempt from IP: $client_ip, Content: " . substr($full_text, 0, 100));
            sendResponse(true, 'Thank you for your message. We will get back to you soon.');
        }
    }
    
    if (!empty($errors)) {
        sendResponse(false, implode('. ', $errors));
    }
    
    // Prepare email content
    $service_names = [
        'ai-development' => 'AI Development',
        'web-development' => 'Web Development',
        'digital-strategy' => 'Digital Strategy',
        'brand-identity' => 'Brand Identity',
        'consultation' => 'General Consultation'
    ];
    
    $email_subject = $config['subject_prefix'] . 'New Contact Form Submission - ' . $service_names[$service];
    
    $email_body = "
    <html>
    <head>
        <title>New Contact Form Submission</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #FF6B35; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .field { margin-bottom: 15px; }
            .field strong { color: #FF6B35; }
            .footer { background: #1A1A1A; color: white; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🚀 New Contact Form Submission</h2>
                <p>ASOM Studio - asomstudio.ai</p>
            </div>
            <div class='content'>
                <div class='field'>
                    <strong>Name:</strong> " . htmlspecialchars($name) . "
                </div>
                <div class='field'>
                    <strong>Email:</strong> " . htmlspecialchars($email) . "
                </div>
                <div class='field'>
                    <strong>Company:</strong> " . htmlspecialchars($company ?: 'Not provided') . "
                </div>
                <div class='field'>
                    <strong>Service Interest:</strong> " . htmlspecialchars($service_names[$service]) . "
                </div>
                <div class='field'>
                    <strong>Message:</strong><br>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                <div class='field'>
                    <strong>Submitted:</strong> " . date('Y-m-d H:i:s T') . "
                </div>
                <div class='field'>
                    <strong>IP Address:</strong> " . htmlspecialchars($client_ip) . "
                </div>
                <div class='field'>
                    <strong>User Agent:</strong> " . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "
                </div>
            </div>
            <div class='footer'>
                <p>This email was sent from the ASOM Studio contact form</p>
                <p>To reply, use: " . htmlspecialchars($email) . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
        'Reply-To: ' . $name . ' <' . $email . '>',
        'X-Mailer: PHP/' . phpversion(),
        'X-Priority: 3',
        'X-MSMail-Priority: Normal'
    ];
    
    // Send email
    $mail_sent = mail(
        $config['recipient_email'],
        $email_subject,
        $email_body,
        implode("\r\n", $headers)
    );
    
    if ($mail_sent) {
        // Log successful submission
        error_log("Contact form submission successful from: $email ($name)");
        
        // Send auto-reply to user
        $auto_reply_subject = 'Thank you for contacting ASOM Studio';
        $auto_reply_body = "
        <html>
        <head>
            <title>Thank you for contacting ASOM Studio</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FF6B35; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .footer { background: #1A1A1A; color: white; padding: 15px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Thank You, " . htmlspecialchars($name) . "!</h2>
                    <p>We've received your message</p>
                </div>
                <div class='content'>
                    <p>Thank you for reaching out to ASOM Studio. We've received your inquiry about <strong>" . htmlspecialchars($service_names[$service]) . "</strong> and will get back to you within 24 hours.</p>
                    
                    <p>In the meantime, feel free to:</p>
                    <ul>
                        <li>Explore our services at <a href='https://asomstudio.ai'>asomstudio.ai</a></li>
                        <li>Connect with us on social media</li>
                        <li>Check out our latest projects and insights</li>
                    </ul>
                    
                    <p><strong>Your message summary:</strong></p>
                    <p style='background: white; padding: 15px; border-left: 4px solid #FF6B35;'>
                        " . nl2br(htmlspecialchars(substr($message, 0, 200))) . 
                        (strlen($message) > 200 ? '...' : '') . "
                    </p>
                </div>
                <div class='footer'>
                    <p><strong>ASOM Studio</strong><br>
                    AI-Powered Creative Solutions<br>
                    Email: alberto@asomstudio.ai</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $auto_reply_headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        mail(
            $email,
            $auto_reply_subject,
            $auto_reply_body,
            implode("\r\n", $auto_reply_headers)
        );
        
        sendResponse(true, 'Thank you for your message! We\'ll get back to you within 24 hours.');
    } else {
        // Log email failure
        error_log("Failed to send contact form email from: $email ($name)");
        sendResponse(false, 'Sorry, there was a problem sending your message. Please try again or contact us directly.');
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Contact form error: " . $e->getMessage());
    sendResponse(false, 'An unexpected error occurred. Please try again later.');
}

// If accessed directly (GET request), return CSRF token for AJAX forms
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    sendResponse(true, 'Contact form ready', ['csrf_token' => generateCSRFToken()]);
}
?> 