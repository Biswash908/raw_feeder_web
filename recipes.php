<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}
include 'db.php';
$user_id = $_SESSION['user_id'];

// Fetch recipes for the logged-in user
$sql = "SELECT * FROM recipes WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Saved Recipes</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .container {
      max-width: 900px;
      margin: 40px auto;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    table, th, td {
      border: 1px solid #ddd;
    }
    th, td {
      padding: 10px;
      text-align: left;
    }
    th {
      background-color: #f2f2f2;
    }
    
  </style>
</head>
<body>
  <!-- NAVBAR -->
  <div class="navbar">
    <div class="logo">Raw Meat Feeding Calculator</div>
    <ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="calculator.php">Calculator</a></li>
      <li><a href="ingredients.php">Ingredients</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="container">
    <h1>Your Saved Recipes</h1>
    <button onclick="goBack()" class="btn" style="border: none; background: none; cursor: pointer;">
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M15 19l-7-7 7-7" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
</button>

<script>
  function goBack() {
    window.history.back();
  }
</script>


    <?php if($result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Recipe Name</th>
            <th>Cat Weight</th>
            <th>Feeding %</th>
            <th>Total Food</th>
            <th>Muscle Meat</th>
            <th>Bones</th>
            <th>Liver</th>
            <th>Other Organs</th>
            <th>Saved On</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['recipe_name']); ?></td>
              <td><?php echo htmlspecialchars($row['cat_weight']) . ' ' . htmlspecialchars($row['unit']); ?></td>
              <td><?php echo htmlspecialchars($row['feeding_percent']) . '%'; ?></td>
              <td><?php echo htmlspecialchars($row['total_food']) . ' g'; ?></td>
              <td><?php echo htmlspecialchars($row['muscle']) . ' g'; ?></td>
              <td><?php echo htmlspecialchars($row['bone']) . ' g'; ?></td>
              <td><?php echo htmlspecialchars($row['liver']) . ' g'; ?></td>
              <td><?php echo htmlspecialchars($row['other_organs']) . ' g'; ?></td>
              <td><?php echo htmlspecialchars($row['created_at']); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>You have no saved recipes yet. Use the calculator to create one!</p>
    <?php endif; ?>
  </div>
</body>
</html>
