<?php
// Check if PHPMailer is installed
require_once __DIR__ . '/vendor/autoload.php';

if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "PHPMailer is installed correctly!<br>";
    echo "Path: " . __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
} else {
    echo "PHPMailer not found. Please run: composer install";
}