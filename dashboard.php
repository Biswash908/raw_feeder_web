<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}
include 'db.php';

$user_id = $_SESSION['user_id'];

// Define daily activity mapping
$dailyActivityMap = [
  'weightloss' => [
    'label' => 'Weightloss',
    'feeding_percent' => 2.0,
    'weight_status' => 'Overweight (weight loss plan)'
  ],
  'inactiveObeseProne' => [
    'label' => 'Inactive & Obese Prone',
    'feeding_percent' => 2.5,
    'weight_status' => 'Overweight or Obesity Prone'
  ],
  'inactive' => [
    'label' => 'Inactive',
    'feeding_percent' => 3.0,
    'weight_status' => 'Average Weight / Low Activity'
  ],
  'neuteredLow' => [
    'label' => 'Neutered Adult & Low Activity (0.5-1hr daily)',
    'feeding_percent' => 3.5,
    'weight_status' => 'Average Weight'
  ],
  'intactLow' => [
    'label' => 'Intact Adult & Low Activity (0.5-1hr daily)',
    'feeding_percent' => 4.0,
    'weight_status' => 'Average Weight'
  ],
  'aboveWeightAvg' => [
    'label' => 'Above Weight & Average Activity (1-2 hours daily)',
    'feeding_percent' => 4.5,
    'weight_status' => 'Slightly Overweight'
  ],
  'aboveAvg1' => [
    'label' => 'Above Average Activity (1-2 hours daily)',
    'feeding_percent' => 5.0,
    'weight_status' => 'Underweight or Very Active'
  ],
  'highActivity' => [
    'label' => 'High Activity & High Intensity (3hr+ daily)',
    'feeding_percent' => 6.0,
    'weight_status' => 'Underweight / High Activity'
  ],
  'aboveAvg2' => [
    'label' => 'Above Average Activity (3hrs daily)',
    'feeding_percent' => 5.5,
    'weight_status' => 'Underweight or Above Average Activity'
  ],
];

// Fetch the latest recipe
$recipe_sql = "SELECT * FROM recipes WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1";
$recipe_result = $conn->query($recipe_sql);
$latest_recipe = $recipe_result->fetch_assoc();

// Fetch cat profile
$profile_sql = "SELECT * FROM cat_info WHERE user_id = $user_id LIMIT 1";
$profile_result = $conn->query($profile_sql);
$cat_profile = $profile_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    /* General Reset & Body */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Helvetica Neue', Arial, sans-serif;
      background: #f0f2f5; 
      color: #333;
    }
    /* Container */
    .container {
      max-width: 900px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    /* Heading */
    h1 {
      margin-bottom: 20px;
      font-size: 1.8rem;
      font-weight: 600;
      text-align: center;
    }
    /* Go Back Button */
    .back-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border: none;
      background: none;
      cursor: pointer;
      margin-bottom: 10px;
    }
    .back-btn svg {
      width: 24px;
      height: 24px;
    }
    /* Section Cards */
    .section {
      background: #fff;
      padding: 20px;
      border-radius: 6px;
      margin-bottom: 20px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .section h2 {
      margin-bottom: 10px;
      font-size: 1.4rem;
      border-bottom: 1px solid #eee;
      padding-bottom: 8px;
    }
    .section p {
      margin: 8px 0;
      line-height: 1.6;
    }
    /* Buttons */
    .btn {
      display: inline-block;
      padding: 10px 15px;
      background-color: #4da6ff;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      transition: background 0.3s;
      font-size: 0.95rem;
      margin-top: 10px;
    }
    .btn:hover {
      background-color: #1a8cff;
    }
    /* Recipe or Profile placeholders */
    .placeholder {
      font-style: italic;
      color: #555;
    }
  </style>
</head>
<body>
  <!-- NAVBAR -->
  <div class="navbar">
    <div class="logo">Raw Meat Feeding Calculator</div>
    <ul>
  <li><a href="calculator.php">Calculator</a></li>
  <li><a href="recipes.php">Recipes</a></li>
  <li><a href="ingredients.php">Ingredients</a></li>
  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <li><a href="admin.php">Admin Dashboard</a></li>
  <?php endif; ?>
  <li><a href="logout.php">Logout</a></li>
</ul>

  </div>

  <!-- MAIN CONTENT -->
  <div class="container">
    <button onclick="goBack()" class="back-btn">
      <svg viewBox="0 0 24 24" fill="none"
           xmlns="http://www.w3.org/2000/svg">
        <path d="M15 19l-7-7 7-7" stroke="black" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>

    <h1>Welcome to Your Dashboard</h1>

    <script>
      function goBack() {
        window.history.back();
      }
    </script>

    <!-- CAT PROFILE SECTION -->
    <div class="section">
      <h2>Your Cat's Profile</h2>
      <?php if ($cat_profile): ?>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($cat_profile['cat_name']); ?></p>
        <p><strong>Weight:</strong> <?php echo htmlspecialchars($cat_profile['weight']) . ' ' . htmlspecialchars($cat_profile['unit']); ?></p>
        <p><strong>Daily Activity:</strong> 
          <?php 
            $activityKey = $cat_profile['daily_activity'];
            if (!empty($activityKey) && isset($dailyActivityMap[$activityKey])) {
              echo htmlspecialchars($dailyActivityMap[$activityKey]['label']);
            } else {
              echo "Not specified";
            }
          ?>
        </p>
        <p><strong>Feeding %:</strong> <?php echo htmlspecialchars($cat_profile['feeding_percent']) . '%'; ?></p>
        <p><strong>Weight Status:</strong> 
          <?php 
            if (!empty($activityKey) && isset($dailyActivityMap[$activityKey])) {
              echo htmlspecialchars($dailyActivityMap[$activityKey]['weight_status']);
            } else {
              echo "Unknown";
            }
          ?>
        </p>
        <a href="profile.php" class="btn">Edit Profile</a>
      <?php else: ?>
        <p class="placeholder">No cat profile found. Add your cat's info now!</p>
        <a href="profile.php" class="btn">Add Profile</a>
      <?php endif; ?>
    </div>

    <!-- LATEST RECIPE SECTION -->
    <div class="section">
      <h2>Latest Saved Recipe</h2>
      <?php if ($latest_recipe): ?>
        <p><strong>Recipe Name:</strong> <?php echo htmlspecialchars($latest_recipe['recipe_name']); ?></p>
        <p><strong>Muscle Meat:</strong> <?php echo htmlspecialchars($latest_recipe['muscle']) . ' g'; ?></p>
        <p><strong>Bones:</strong> <?php echo htmlspecialchars($latest_recipe['bone']) . ' g'; ?></p>
        <p><strong>Liver:</strong> <?php echo htmlspecialchars($latest_recipe['liver']) . ' g'; ?></p>
        <p><strong>Other Organs:</strong> <?php echo htmlspecialchars($latest_recipe['other_organs']) . ' g'; ?></p>
        <a href="recipes.php" class="btn">View All Recipes</a>
      <?php else: ?>
        <p class="placeholder">No recipes saved yet. Use the calculator to create one!</p>
        <a href="calculator.php" class="btn">Go to Calculator</a>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
