<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

function sendSystemEmail($to, $subject, $message) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        $mail->Username   = 'owner@gmail.com';

        $mail->Password   = 'gmail password';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('owner@gmail.com', 'Company Name');

        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;

        $mail->Body = "
        <div style='font-family:Segoe UI, Arial; background:#f4f6f9; padding:20px;'>
            <div style='max-width:600px; margin:auto; background:#ffffff; padding:20px; border-radius:8px; border-top:5px solid #0b3d91;'>

                <h2 style='color:#0b3d91; text-align:center;'>Office System</h2>

                <div style='font-size:14px; color:#333; margin-top:15px;'>
                    $message
                </div>

                <hr style='margin:20px 0;'>

                <p style='font-size:12px; color:#888; text-align:center;'>
                    This is an automated email. Please do not reply.
                </p>
            </div>
        </div>
        ";

        $mail->AltBody = strip_tags($message);

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);

        return false;
    }
}
?>