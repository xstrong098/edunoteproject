<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'edunote_user');
define('DB_PASS', 'mypassword123');
define('DB_NAME', 'edunote');


define('APP_NAME', 'EduNote');
define('APP_URL', 'http://localhost/edunote');
define('APP_VERSION', '1.0.0');

define('API_KEY', '');

define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'noreply@edunote.com');
define('MAIL_PASSWORD', 'your_mail_password');
define('MAIL_FROM_NAME', 'EduNote App');

define('DEFAULT_TIMEZONE', 'Europe/Rome');
date_default_timezone_set(DEFAULT_TIMEZONE);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); 
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('SESSION_LIFETIME', 3600); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

define('HASH_COST', 12); 

define('OPENAI_API_KEY', '');
define('USE_AI_SUMMARY', true); 
?>