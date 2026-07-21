<?php
/**
 * Pharmacy Email Sender
 * POST pharmacy_view/send_email.php
 * Body: { email_to, subject, body }
 */
function sendJSON($data) {
    if (ob_get_length()) ob_clean();
    echo json_encode($data);
    exit;
}

// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
error_log("send_email.php accessed. Session ID: " . session_id() . " | user_id isset: " . (isset($_SESSION['user_id']) ? 'Yes' : 'No') . " | Cookies: " . json_encode($_COOKIE));
if (!isset($_SESSION['user_id'])) { 
    http_response_code(401); 
    sendJSON(['success'=>false,'message'=>'Unauthorized', 'session_id'=>session_id(), 'cookies'=>$_COOKIE]); 
}

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ob_start();

$input = json_decode(file_get_contents('php://input'), true);
$to      = trim($input['email_to'] ?? '');
$subject = trim($input['subject']  ?? 'Indent Request Notification');
$body    = trim($input['body']     ?? '');

if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    sendJSON(['success'=>false,'message'=>'Invalid email address']);
}
if (!$body) {
    sendJSON(['success'=>false,'message'=>'Message body is empty']);
}

// ── Try PHPMailer (Manual Load) ────────────────────────────────────
$phpMailerDir = __DIR__ . '/../vendor/phpmailer/phpmailer/src/';
if (file_exists($phpMailerDir . 'PHPMailer.php')) {
    require_once $phpMailerDir . 'Exception.php';
    require_once $phpMailerDir . 'PHPMailer.php';
    require_once $phpMailerDir . 'SMTP.php';

    $envPath = __DIR__ . '/../config/.env';
    $env = [];
    if (file_exists($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strpos(trim($line),'#')===0 || strpos($line,'=')===false) continue;
            [$k,$v] = explode('=', $line, 2);
            $v = trim($v);
            if ((strpos($v, '"') === 0 && strrpos($v, '"') === strlen($v) - 1) || 
                (strpos($v, "'") === 0 && strrpos($v, "'") === strlen($v) - 1)) {
                $v = substr($v, 1, -1);
            }
            $env[trim($k)] = $v;
        }
    }

    if (!empty($env['SMTP_USERNAME']) && !empty($env['SMTP_PASSWORD'])) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $env['SMTP_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $env['SMTP_USERNAME'];
            $mail->Password   = $env['SMTP_PASSWORD'];
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($env['SMTP_PORT'] ?? 587);
            $mail->setFrom($env['SMTP_FROM_EMAIL'] ?? $env['SMTP_USERNAME'], $env['SMTP_FROM_NAME'] ?? 'GM Pharmacy');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->isHTML(true);
            $mail->send();
            sendJSON(['success'=>true,'message'=>'Email sent successfully via SMTP','method'=>'phpmailer']);
        } catch (Exception $e) {
            sendJSON([
                'success'  => false,
                'message'  => 'SMTP Error: ' . $e->getMessage(),
                'fallback' => 'mailto'
            ]);
        }
    } else {
        sendJSON([
            'success'  => false,
            'message'  => 'SMTP credentials missing in .env (SMTP_USERNAME or SMTP_PASSWORD is empty)',
            'fallback' => 'mailto'
        ]);
    }
}

// ── PHPMailer not found ────────────────────────────────────────────
sendJSON([
    'success'  => false,
    'message'  => 'PHPMailer files not found in vendor/phpmailer/phpmailer/src/',
    'fallback' => 'mailto'
]);
