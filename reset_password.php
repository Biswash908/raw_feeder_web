<?php
require 'db.php';

$msg = "";
$email = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Debugging: Print the token received
    // echo "Token received: " . htmlspecialchars($token);

    // Validate token and check expiration
    $stmt = $conn->prepare("SELECT email, token FROM password_resets WHERE expires_at > NOW()");
    $stmt->execute();
    $result = $stmt->get_result();

    $validToken = false;

    while ($row = $result->fetch_assoc()) {
        if (password_verify($token, $row['token'])) {
            $email = $row['email'];
            $validToken = true;
            break;
        }
    }

    if (!$validToken) {
        $msg = "Invalid or expired token.";
    } elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
        $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Update the user's password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $new_password, $email);
        $stmt->execute();

        // Delete token after successful reset
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $msg = "Password has been reset. <a href='index.php'>Login</a>";
        $validToken = false; // Prevent showing the form again
    }
} else {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center">Reset Password</h2>
                        <?php if (!empty($msg)): ?>
                            <div class="alert alert-info"><?php echo $msg; ?></div>
                        <?php endif; ?>
                        <?php if ($validToken): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label>New Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                            </form>
                        <?php endif; ?>
                        <p class="mt-3 text-center"><a href="index.php">Back to Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
