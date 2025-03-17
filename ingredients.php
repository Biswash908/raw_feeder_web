<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Database connection settings – update these with your actual credentials
$host     = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'cat_feeding_db';

// Create connection using MySQLi
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query the ingredients table
$sql = "SELECT id, name, meat, bone, organ FROM ingredients";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Ingredients List</title>
  <link rel="stylesheet" href="css/style.css">
  <!-- Optional: Include Bootstrap CSS for table styling -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Additional styling for the ingredients page */
    .navbar {
    /* Semi-transparent gradient background */
    background: linear-gradient( 90deg, rgba(51, 51, 51, 0.7) 0%, rgba(68, 68, 68, 0.7) 100%);
    /* If you prefer a solid color instead of a gradient, use:
       background: rgba(51, 51, 51, 0.7);
    */
    /* Blur effect for the background behind it */
    backdrop-filter: blur(5px);
    /* Common navbar styling */
    color: #fff;
    height: 60px;
    display: flex;
    align-items: center;
    padding: 0 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.navbar .logo {
    font-size: 1.25rem;
    font-weight: bold;
    margin-right: auto;
    text-transform: uppercase;
    /* optional for a modern look */
    letter-spacing: 1px;
    /* optional for more spaced-out text */
}

.navbar ul {
    list-style: none;
    display: flex;
    gap: 20px;
    margin: 0;
    padding: 0;
}

.navbar ul li a {
    color: #fff;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 4px;
    font-weight: 500;
    transition: background-color 0.3s;
}

.navbar ul li a:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

    .container {
      max-width: 1200px;
      margin: 40px auto;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 10px;
      border: 1px solid #ddd;
      text-align: center;
    }
    th {
      background-color: #f8f8f8;
    }
  </style>
</head>
<body>

<!-- Navbar (using your custom CSS) -->
<div class="navbar">
  <div class="logo">Raw Meat Feeding Calculator</div>
  <ul>
    <li><a href="dashboard.php">Dashboard</a></li>
    <li><a href="calculator.php">Calculator</a></li>
    <li><a href="ingredients.php">Ingredients</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</div>

<!-- Main Container -->
<div class="container">
  <h2>PMR Diet Ingredients List</h2>
  
  <!-- Display ingredients table -->
  <table class="table table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Ingredient Name</th>
        <th>Meat (%)</th>
        <th>Bone (%)</th>
        <th>Organ (%)</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      if ($result && $result->num_rows > 0):
          // Fetch and display each ingredient
          while ($ingredient = $result->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($ingredient['id']); ?></td>
        <td><?php echo htmlspecialchars($ingredient['name']); ?></td>
        <td><?php echo htmlspecialchars($ingredient['meat']); ?></td>
        <td><?php echo htmlspecialchars($ingredient['bone']); ?></td>
        <td><?php echo htmlspecialchars($ingredient['organ']); ?></td>
      </tr>
      <?php 
          endwhile;
      else: ?>
      <tr>
        <td colspan="5">No ingredients found.</td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>
  
  <p>Make sure to source high-quality ingredients and consult with a veterinarian for balanced nutrition.</p>
</div>

<!-- Footer -->
<div class="footer">
  <p>&copy; <?php echo date('Y'); ?> Raw Feeding App. All rights reserved.</p>
</div>

<?php 
// Close the database connection
$conn->close();
?>
</body>
</html>
