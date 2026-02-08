<?php
namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

class Mailer {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function send($toEmail, $toName, $subject, $body, $isHtml = true) {
        // Fetch settings
        $settings = $this->getSettings();

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $settings['smtp_host'] ?? '';
            $mail->SMTPAuth   = true;
            $mail->Username   = $settings['smtp_username'] ?? '';
            $mail->Password   = $settings['smtp_password'] ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $settings['smtp_port'] ?? 465;

            // Recipients
            $fromEmail = $settings['smtp_from_email'] ?? $settings['smtp_username'];
            $fromName = $settings['smtp_from_name'] ?? 'Online Exam Portal';
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);

            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"];
        }
    }

    private function getSettings() {
        $query = "SELECT * FROM settings WHERE setting_key LIKE 'smtp_%'";
        $result = $this->conn->query($query);
        $settings = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return $settings;
    }
}
