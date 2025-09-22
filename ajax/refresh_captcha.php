<?php
// Start session
session_start();

// Include necessary files
require_once '../includes/config.php';

// Generate a new captcha code
$captchaCode = SimpleCaptcha::generateCode(6);

// Generate the captcha HTML
$captchaHTML = SimpleCaptcha::generateCaptchaHTML($captchaCode);

// Return the new captcha HTML
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'captcha_html' => $captchaHTML
]);
?>