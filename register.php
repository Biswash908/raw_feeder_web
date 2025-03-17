<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'db.php';
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username already exists using a prepared statement
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            // Hash the password and insert the new user record
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $username, $hashed_password);
            
            if ($stmt_insert->execute()) {
                header("Location: index.php");
                exit;
            } else {
                $error = "Error: " . $conn->error;
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Raw Meat Feeding Calculator - Register</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS (from CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <!-- Navbar -->
  <!-- <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
      <a class="navbar-brand" href="#">Raw Feeding App</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
         <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
         <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="index.php">Login</a></li>
         </ul>
      </div>
    </div>
  </nav> -->
  <!-- End Navbar -->

  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
         <div class="card shadow">
           <div class="card-body">
             <h2 class="card-title text-center mb-4">Register</h2>
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
  <label for="email" class="form-label">Email Address</label>
  <input type="email" class="form-control" name="email" id="email" required>
</div>

                <div class="mb-3">
                   <label for="password" class="form-label">Password</label>
                   <input type="password" class="form-control" name="password" id="password" required>
                </div>
                <div class="mb-3">
                   <label for="confirm_password" class="form-label">Confirm Password</label>
                   <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
             </form>
             <p class="mt-3 text-center">Already have an account? <a href="index.php">Login Here</a></p>
           </div>
         </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer mt-5 py-3 bg-light">
    <div class="container text-center">
       <span class="text-muted">&copy; <?php echo date('Y'); ?> Raw Feeding App. All rights reserved.</span>
    </div>
  </footer>

  <!-- Bootstrap JS Bundle (from CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
