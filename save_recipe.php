<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Feline PMR Calculator & Recipe Builder</title>
  <style>
    /* ----------------------------------------------------
       Basic Reset & Body Styles
    ---------------------------------------------------- */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: "Helvetica Neue", Arial, sans-serif;
      background-color: #fafafa;
      color: #333;
    }

    /* ----------------------------------------------------
       NAVBAR
    ---------------------------------------------------- */
    .navbar {
  /* Semi-transparent gradient background */
  background: linear-gradient(
    90deg,
    rgba(51, 51, 51, 0.7) 0%,
    rgba(68, 68, 68, 0.7) 100%
  );

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
  text-transform: uppercase; /* optional for a modern look */
  letter-spacing: 1px;       /* optional for more spaced-out text */
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


    /* ----------------------------------------------------
       Container & Main Card
    ---------------------------------------------------- */
    .container {
      max-width: 900px;
      margin: 40px auto;
      background-color: #fff;
      padding: 20px 30px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h1, h2, h3 {
      margin-bottom: 15px;
    }
    p {
      margin-bottom: 10px;
      line-height: 1.6;
    }

    /* ----------------------------------------------------
       Form & Layout
    ---------------------------------------------------- */
    label {
      font-weight: 600;
      margin-bottom: 5px;
      display: inline-block;
    }
    input[type="number"], select {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .form-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }
    .form-group {
      flex: 1;
      min-width: 200px;
    }
    button {
      padding: 12px 20px;
      background-color: #4da6ff;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1rem;
      transition: background-color 0.3s;
      margin-right: 10px;
    }
    button:hover {
      background-color: #1a8cff;
    }

    /* ----------------------------------------------------
       Results & Recipe Section
    ---------------------------------------------------- */
    .results, .recipe-section {
      margin-top: 30px;
      padding: 15px;
      border-left: 5px solid #4da6ff;
      background-color: #f9f9f9;
      border-radius: 4px;
    }
    .results h3, .recipe-section h3 {
      margin-bottom: 10px;
    }

    .ingredient-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    .ingredient-table th,
    .ingredient-table td {
      padding: 8px;
      border: 1px solid #ddd;
      text-align: left;
    }
    .ingredient-table th {
      background-color: #f2f2f2;
    }

    /* ----------------------------------------------------
       Footer
    ---------------------------------------------------- */
    .footer {
      text-align: center;
      margin: 20px auto;
      color: #777;
      font-size: 0.9rem;
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
  <h1>Feline PMR Calculator & Recipe Builder</h1>
  <p>
    This tool calculates daily feeding amounts for a PMR (Prey Model Raw) diet and lets you select which ingredients to use. 
    Results are estimates—always consult a veterinarian for personalized advice.
  </p>
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


  <!-- Step 1: Basic Cat Info Form -->
  <form id="catForm">
    <div class="form-row">
      <div class="form-group">
        <label for="catWeight">Cat's Weight:</label>
        <input type="number" id="catWeight" name="catWeight" step="0.1" min="0" required>
      </div>
      <div class="form-group">
        <label for="unit">Unit of Measurement:</label>
        <select id="unit" name="unit" required>
          <option value="" disabled selected>Select option</option>
          <option value="kg">Kilograms (kg)</option>
          <option value="lb">Pounds (lb)</option>
        </select>
      </div>
      <div class="form-group">
        <label for="feedingPercent">Feeding % (of Body Weight):</label>
        <select id="feedingPercent" name="feedingPercent" required>
          <option value="" disabled selected>Select option</option>
          <option value="2">2% (Weight Loss / Low Activity)</option>
          <option value="2.5">2.5% (Maintenance)</option>
          <option value="3">3% (Slightly Active)</option>
          <option value="3.5">3.5% (Active)</option>
          <option value="4">4% (Kittens / Underweight)</option>
        </select>
      </div>
    </div>
    <button type="submit">Calculate PMR</button>
  </form>

  <!-- Results Section (Step 2) -->
  <div class="results" id="pmrResults" style="display:none;">
    <h3>Daily Feeding Breakdown</h3>
    <p><strong>Total Food:</strong> <span id="totalFood"></span></p>
    <p><strong>Muscle Meat (80%):</strong> <span id="muscleMeat"></span></p>
    <p><strong>Bones (10%):</strong> <span id="bone"></span></p>
    <p><strong>Liver (5%):</strong> <span id="liver"></span></p>
    <p><strong>Other Organs (5%):</strong> <span id="otherOrgans"></span></p>
  </div>

  <!-- Step 3: Ingredient Selection / Recipe -->
  <div class="recipe-section" id="recipeSection" style="display:none;">
    <h3>Select Ingredients for Your Recipe</h3>
    <p>Choose which muscle meat, bones, liver, and other organs you want to feed.</p>
    <form id="recipeForm">
      <?php
        /*
         * Example: Static arrays of ingredients by category.
         * In a real app, you might fetch these from a database.
         */
        $muscleMeats = ["Chicken Breast", "Turkey Thigh", "Beef Chunk", "Pork Loin"];
        $bones       = ["Chicken Wings", "Chicken Necks", "Duck Wings"];
        $livers      = ["Chicken Liver", "Beef Liver", "Duck Liver"];
        $otherOrgans = ["Chicken Heart", "Beef Kidney", "Pork Spleen"];

        // A helper function to render a <select> for a category:
        function renderIngredientSelect($label, $name, $options) {
          echo '<div class="form-group">';
          echo "<label for=\"$name\">$label:</label>";
          echo "<select id=\"$name\" name=\"$name\">";
          echo "<option value=\"\" disabled selected>Select an ingredient</option>";
          foreach ($options as $option) {
            echo "<option value=\"$option\">$option</option>";
          }
          echo "</select>";
          echo '</div>';
        }
      ?>
      <div class="form-row">
        <?php renderIngredientSelect("Muscle Meat", "muscleSelect", $muscleMeats); ?>
        <?php renderIngredientSelect("Bones", "boneSelect", $bones); ?>
      </div>
      <div class="form-row">
        <?php renderIngredientSelect("Liver", "liverSelect", $livers); ?>
        <?php renderIngredientSelect("Other Organs", "organSelect", $otherOrgans); ?>
      </div>
      <button type="submit">Generate Recipe</button>
    </form>

    <!-- Final Recipe Display -->
    <table class="ingredient-table" id="recipeTable" style="display:none;">
      <thead>
        <tr>
          <th>Category</th>
          <th>Ingredient</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        <!-- Filled by JavaScript after user selects ingredients -->
      </tbody>
    </table>
  </div>
</div>

<!-- FOOTER -->
<div class="footer">
  <p>&copy; <?php echo date('Y'); ?> Raw Feeding App. All rights reserved.</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const catForm = document.getElementById('catForm');
  const pmrResults = document.getElementById('pmrResults');
  const recipeSection = document.getElementById('recipeSection');

  // Spans for breakdown
  const totalFoodSpan = document.getElementById('totalFood');
  const muscleMeatSpan = document.getElementById('muscleMeat');
  const boneSpan = document.getElementById('bone');
  const liverSpan = document.getElementById('liver');
  const otherOrgansSpan = document.getElementById('otherOrgans');

  // Variables to store the final amounts
  let totalFoodGrams = 0;
  let muscleGrams = 0;
  let boneGrams = 0;
  let liverGrams = 0;
  let otherOrgansGrams = 0;

  // Step 1: Handle PMR Calculation
  catForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const weight = parseFloat(document.getElementById('catWeight').value);
    const unit = document.getElementById('unit').value;
    const feedingPercent = parseFloat(document.getElementById('feedingPercent').value);

    if (!weight || !unit || !feedingPercent) {
      alert("Please fill in all fields.");
      return;
    }

    // Convert weight to kilograms if user selects pounds
    // 1 lb ~ 0.453592 kg
    let weightKg = (unit === 'lb') ? weight * 0.453592 : weight;

    // total daily food in grams = weightKg * (feedingPercent% of body weight) * 1000
    // e.g., if feedingPercent is 3, then 3% => 0.03
    totalFoodGrams = weightKg * (feedingPercent / 100) * 1000;

    // Breakdown for PMR (80% muscle, 10% bone, 5% liver, 5% other organs)
    muscleGrams = totalFoodGrams * 0.80;
    boneGrams   = totalFoodGrams * 0.10;
    liverGrams  = totalFoodGrams * 0.05;
    otherOrgansGrams = totalFoodGrams * 0.05;

    // Update the result text
    totalFoodSpan.textContent     = totalFoodGrams.toFixed(1) + " g";
    muscleMeatSpan.textContent    = muscleGrams.toFixed(1)    + " g";
    boneSpan.textContent          = boneGrams.toFixed(1)      + " g";
    liverSpan.textContent         = liverGrams.toFixed(1)     + " g";
    otherOrgansSpan.textContent   = otherOrgansGrams.toFixed(1)+ " g";

    // Show results + recipe selection
    pmrResults.style.display = 'block';
    recipeSection.style.display = 'block';
  });

  // Step 2: Handle Ingredient Selection & Build Recipe
  const recipeForm = document.getElementById('recipeForm');
  const recipeTable = document.getElementById('recipeTable');
  const recipeTableBody = recipeTable.querySelector('tbody');

  recipeForm.addEventListener('submit', function(e) {
    e.preventDefault();

    // Gather selected ingredients
    const muscleSelect = document.getElementById('muscleSelect').value;
    const boneSelect   = document.getElementById('boneSelect').value;
    const liverSelect  = document.getElementById('liverSelect').value;
    const organSelect  = document.getElementById('organSelect').value;

    // Clear existing table rows
    recipeTableBody.innerHTML = "";

    // Helper function to add a row
    function addRow(category, ingredient, amountGrams) {
      if (ingredient) {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${category}</td>
          <td>${ingredient}</td>
          <td>${amountGrams.toFixed(1)} g</td>
        `;
        recipeTableBody.appendChild(row);
      }
    }

    // Build the rows
    addRow("Muscle Meat", muscleSelect, muscleGrams);
    addRow("Bones", boneSelect, boneGrams);
    addRow("Liver", liverSelect, liverGrams);
    addRow("Other Organs", organSelect, otherOrgansGrams);

    // Finally, show the table
    recipeTable.style.display = 'table';
  });
});
</script>
</body>
</html>
