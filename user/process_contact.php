<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

session_start();

if (!isset($_SESSION['users'])) {
    die("You must be logged in to contact support.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    $name = $_SESSION['users']['name'];
    $email = $_SESSION['users']['email'];

    if (empty($subject) || empty($message)) {
        die("Please fill in the subject and message.");
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP Config
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ict-01-25-22@unilia.ac.mw';
        $mail->Password   = 'ifid zzgh jhik oync';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Set sender as the logged-in user
        $mail->setFrom($email, $name);
        
        // Send to system owner
        $mail->addAddress('leonardponjemlungu@gmail.com', 'Mungu Ni Dawa');
        
        // Allow replies to go back to the user
        $mail->addReplyTo($email, $name);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "📩 Support Message from $name: $subject";

        $mail->Body = '
        <div style="font-family: Arial, sans-serif; color: #333; padding: 20px; background-color: #f4f4f4;">
            <div style="max-width: 600px; margin: auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                <h2 style="color: #0a3d62; text-align: center;">User Contact Message</h2>
                <p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>
                <p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>
                <p><strong>Message:</strong><br>' . nl2br(htmlspecialchars($message)) . '</p>
                <hr style="margin: 20px 0;" />
                <p style="font-size: 0.9rem; color: #666;">This message was submitted via the Smart Printing System contact form.</p>
            </div>
        </div>';

        $mail->AltBody = "Name: $name\nEmail: $email\nSubject: $subject\n\n$message";

        $mail->send();

        echo "<script>
            alert('Your message has been sent to the system administrator.');
            window.location.href = 'contact.php';
        </script>";
        exit;

    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    header("Location: contact.php");
    exit();
}
