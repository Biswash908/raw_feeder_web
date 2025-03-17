<?php
session_start();

// OPTIONAL: Hide notices & warnings during development (not recommended in production)
// error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// ini_set('display_errors', 0);

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'db.php';
    
    // Trim the username to remove extra spaces
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
       $row = $result->fetch_assoc();

       // Check if the password matches
       if (password_verify($password, $row['password'])) {
           // Store user session data
           $_SESSION['user_id'] = $row['id'];
           $_SESSION['username'] = $row['username'];

           // Hard-coded admin check:
           if (strtolower($row['username']) === 'admin') {
               $_SESSION['role'] = 'admin';
           } else {
               $_SESSION['role'] = 'user';
           }
           
           header("Location: dashboard.php");
           exit;
       } else {
           $error = "Invalid username or password.";
       }
    } else {
       $error = "User not found.";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cat Feeding Calculator - Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS (from CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
         <div class="card shadow">
           <div class="card-body">
             <h2 class="card-title text-center mb-4">Login</h2>
             <?php if($error != ""): ?>
               <div class="alert alert-danger" role="alert">
                 <?php echo $error; ?>
               </div>
             <?php endif; ?>
             <form method="POST" action="">
                <div class="mb-3">
                   <label for="username" class="form-label">Username</label>
                   <input type="text" class="form-control" name="username" id="username" required>
                </div>
                <div class="mb-3">
                   <label for="password" class="form-label">Password</label>
                   <input type="password" class="form-control" name="password" id="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
             </form>
             <p class="mt-3 text-center">
  Don't have an account? <a href="register.php">Register Here</a><br>
  <a href="forgot_password.php">Forgot Password?</a>
</p>

           </div>
         </div>
      </div>
    </div>
  </div>

  <footer class="footer mt-5 py-3 bg-light">
    <div class="container text-center">
       <span class="text-muted">&copy; <?php echo date('Y'); ?> Raw Feeding App. All rights reserved.</span>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
