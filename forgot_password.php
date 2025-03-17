<?php
session_start();
require 'db.php';
require 'phpmailer/vendor/autoload.php'; // Load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if email exists in the users table
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(50)); // Generate reset token
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Insert or update token in password_resets table
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) 
                                ON DUPLICATE KEY UPDATE token=?, expires_at=?");
        $stmt->bind_param("sssss", $email, $token, $expiry, $token, $expiry);
        $stmt->execute();

        // Send password reset email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'rage33210@gmail.com';
            $mail->Password   = 'tntt ltci bvum hppu';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('your_email@gmail.com', 'Your Website');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $mail->Body    = "Click <a href='http://localhost/Project-I/reset_password.php?token=$token'>here</a> to reset your password.";

            $mail->send();
            $msg = "A password reset link has been sent to your email.";
        } catch (Exception $e) {
            $msg = "Error sending email: " . $mail->ErrorInfo;
        }
    } else {
        $msg = "No account found with that email.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center">Forgot Password</h2>
                        <?php if (!empty($msg)): ?>
                            <div class="alert alert-info"><?php echo $msg; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                        </form>
                        <p class="mt-3 text-center"><a href="index.php">Back to Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
