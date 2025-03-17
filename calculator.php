<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// ----------------------------------------------------------------
// 1. Connect to the Database
// ----------------------------------------------------------------
$host = 'localhost';       
$db   = 'cat_feeding_db'; 
$user = 'root';          
$pass = '';             

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ----------------------------------------------------------------
// 2. Handle "Save Recipe" Form Submission
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_recipe') {
    $user_id         = $_SESSION['user_id']; 
    $recipe_name     = $_POST['recipe_name']     ?? 'Unnamed Recipe';
    $cat_weight      = $_POST['cat_weight']      ?? 0;
    $unit            = $_POST['unit']            ?? '';
    $feeding_percent = $_POST['feeding_percent'] ?? 0;  // Derived from Daily Activity
    $total_food      = $_POST['total_food']      ?? 0;
    $muscle          = $_POST['muscle']          ?? 0;
    $bone            = $_POST['bone']            ?? 0;
    $liver           = $_POST['liver']           ?? 0;
    $other_organs    = $_POST['other_organs']    ?? 0;

    $sql = "INSERT INTO recipes 
            (user_id, recipe_name, cat_weight, unit, feeding_percent, total_food, muscle, bone, liver, other_organs, created_at) 
            VALUES 
            (:user_id, :recipe_name, :cat_weight, :unit, :feeding_percent, :total_food, :muscle, :bone, :liver, :other_organs, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id'         => $user_id,
        ':recipe_name'     => $recipe_name,
        ':cat_weight'      => $cat_weight,
        ':unit'            => $unit,
        ':feeding_percent' => $feeding_percent,
        ':total_food'      => $total_food,
        ':muscle'          => $muscle,
        ':bone'            => $bone,
        ':liver'           => $liver,
        ':other_organs'    => $other_organs
    ]);

    echo "<script>alert('Recipe saved successfully!');</script>";
}

// ----------------------------------------------------------------
// 3. Fetch Ingredients from the Database
// ----------------------------------------------------------------
try {
    $stmt = $pdo->query("SELECT id, name, meat, bone, organ FROM ingredients");
    $defaultIngredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $defaultIngredients = []; // Fallback if error occurs
}

// ----------------------------------------------------------------
// 4. Fetch User's Cat Profiles
// ----------------------------------------------------------------
$user_id = $_SESSION['user_id'];
$stmt2 = $pdo->prepare("SELECT id, cat_name, weight, unit, daily_activity 
                        FROM cat_info 
                        WHERE user_id = :user_id");
$stmt2->execute([':user_id' => $user_id]);
$catProfiles = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------------------------------------------
// 5. Group and Sort Ingredients by Category
// ----------------------------------------------------------------
function categorizeIngredient($ingredient) {
    $name = strtolower($ingredient['name']);
    if (strpos($name, 'liver') !== false) {
        return 'Liver';
    }
    $meat  = $ingredient['meat'];
    $bone  = $ingredient['bone'];
    $organ = $ingredient['organ'];

    if ($meat >= $bone && $meat >= $organ) {
        return 'Muscle Meat';
    } elseif ($bone >= $meat && $bone >= $organ) {
        return 'Bones';
    } else {
        return 'Other Organs';
    }
}

$groupedIngredients = [
    'Muscle Meat' => [],
    'Bones'       => [],
    'Liver'       => [],
    'Other Organs'=> []
];

foreach ($defaultIngredients as $ingredient) {
    $category = categorizeIngredient($ingredient);
    $groupedIngredients[$category][] = $ingredient['name'];
}

foreach ($groupedIngredients as &$group) {
    sort($group);
}
unset($group);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Feline PMR Calculator & Recipe Builder</title>
  <style>
    /* Basic Reset & Body Styles */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: "Helvetica Neue", Arial, sans-serif; background-color: #fafafa; color: #333; }
    /* NAVBAR */
    .navbar { background: linear-gradient(90deg, rgba(51, 51, 51, 0.7) 0%, rgba(68, 68, 68, 0.7) 100%); backdrop-filter: blur(5px); color: #fff; height: 60px; display: flex; align-items: center; padding: 0 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); }
    .navbar .logo { font-size: 1.25rem; font-weight: bold; margin-right: auto; text-transform: uppercase; letter-spacing: 1px; }
    .navbar ul { list-style: none; display: flex; gap: 20px; }
    .navbar ul li a { color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 4px; font-weight: 500; transition: background-color 0.3s; }
    .navbar ul li a:hover { background-color: rgba(255, 255, 255, 0.1); }
    /* Button */
    .btn { display: inline-block; padding: 10px 15px; background-color: #4da6ff; color: white; text-decoration: none; border-radius: 5px; transition: background 0.3s; font-size: 0.95rem; margin-top: 10px; }
    .btn:hover { background-color: #1a8cff; }
    /* Container & Main Card */
    .container { max-width: 900px; margin: 40px auto; background-color: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1, h2, h3 { margin-bottom: 15px; }
    p { margin-bottom: 10px; line-height: 1.6; }
    /* Form & Layout */
    label { font-weight: 600; margin-bottom: 5px; display: inline-block; }
    input[type="number"], select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
    .form-row { display: flex; flex-wrap: wrap; gap: 20px; }
    .form-group { flex: 1; min-width: 200px; margin-bottom: 15px; }
    button { padding: 12px 20px; background-color: #4da6ff; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; transition: background-color 0.3s; margin-right: 10px; }
    button:hover { background-color: #1a8cff; }
    .alt-btn { margin-left: 10px; font-size: 0.85rem; background-color: #000; color: #fff; border: 1px solid #000; padding: 5px 10px; border-radius: 4px; cursor: pointer; display: none; }
    .alt-btn:hover { background-color: #333; }
    .alternatives ul { list-style: none; padding-left: 10px; }
    .alternatives li:hover { text-decoration: underline; }
    /* Results & Recipe Section */
    .results, .recipe-section { margin-top: 30px; padding: 15px; border-left: 5px solid #4da6ff; background-color: #f9f9f9; border-radius: 4px; }
    .results h3, .recipe-section h3 { margin-bottom: 10px; }
    .ingredient-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .ingredient-table th, .ingredient-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    .ingredient-table th { background-color: #f2f2f2; }
    #saveRecipeBtn { display: none; margin: 15px auto 0; background-color: #4da6ff; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; transition: background-color 0.3s; padding: 12px 20px; display: block; }
    #saveRecipeBtn:hover { background-color: #1a8cff; }
    /* Calculation Steps Box */
    #calculationSteps { margin-top: 20px; padding: 15px; border-left: 5px solid #999; background-color: #f2f2f2; border-radius: 4px; display: none; }
    #calculationSteps h4 { margin-bottom: 10px; }
    /* Footer */
    .footer { text-align: center; margin: 20px auto; color: #777; font-size: 0.9rem; }
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
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" 
         xmlns="http://www.w3.org/2000/svg">
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
      <!-- (A) Dropdown to select a cat profile -->
      <div class="form-group">
        <label for="catProfileSelect">Select a Cat Profile:</label>
        <select id="catProfileSelect" onchange="fillCatData()">
          <option value="">--Select Cat Profile--</option>
          <?php 
          foreach ($catProfiles as $profile): 
              $jsonValue = htmlspecialchars(json_encode($profile));
          ?>
            <option value="<?php echo $jsonValue; ?>">
              <?php echo htmlspecialchars($profile['cat_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- (B) Cat's Weight -->
      <div class="form-group">
        <label for="catWeight">Cat's Weight:</label>
        <input type="number" id="catWeight" name="catWeight" step="0.1" min="0" required>
      </div>

      <!-- (C) Unit of Measurement -->
      <div class="form-group">
        <label for="unit">Unit of Measurement:</label>
        <select id="unit" name="unit" required>
          <option value="" disabled selected>Select option</option>
          <option value="kg">Kilograms (kg)</option>
          <option value="lb">Pounds (lb)</option>
        </select>
      </div>

      <!-- (D) Daily Activity Level -->
      <div class="form-group">
        <label for="dailyActivity">Daily Activity Level:</label>
        <select id="dailyActivity" name="dailyActivity" required>
          <option value="" disabled selected>Select daily activity level</option>
          <option value="weightloss">Weightloss</option>
          <option value="inactiveObeseProne">Inactive & Obese Prone</option>
          <option value="inactive">Inactive</option>
          <option value="neuteredLow">Neutered Adult & Low Activity (0.5-1hr daily)</option>
          <option value="intactLow">Intact Adult & Low Activity (0.5-1hr daily)</option>
          <option value="aboveWeightAvg">Above Weight & Average Activity (1-2 hours daily)</option>
          <option value="aboveAvg1">Above Average Activity (1-2 hours daily)</option>
          <option value="highActivity">High Activity & High Intensity (3hr+ daily)</option>
          <option value="aboveAvg2">Above Average Activity (3hrs daily)</option>
        </select>
      </div>

      <!-- (E) Cat Category -->
      <div class="form-group">
        <label for="catCategory">Cat Category:</label>
        <select id="catCategory" name="catCategory" required>
          <option value="" disabled selected>Select cat category</option>
          <option value="adult">Adult</option>
          <option value="kitten">Kitten / Pregnant / Nursing</option>
        </select>
      </div>
    </div>
    <button type="submit">Calculate PMR</button>
  </form>

  <!-- Calculation Steps (will show after user clicks "Calculate PMR") -->
  <div id="calculationSteps">
    <h4>Calculation Steps:</h4>
    <p>Weight in kg: <span id="weightInKg"></span></p>
    <p>Daily Activity => <strong>Feeding %:</strong> <span id="selectedPercent"></span>%</p>
  </div>

  <!-- Results Section (Step 2) -->
  <div class="results" id="pmrResults" style="display:none;">
    <h3>Daily Feeding Breakdown</h3>
    <p><strong>Cat Weight Status:</strong> <span id="catWeightStatus"></span></p>
    <p><strong>Total Food:</strong> <span id="totalFood"></span></p>
    <p><strong>Muscle Meat (<span id="muscleLabel">80%</span>):</strong> <span id="muscleMeat"></span></p>
    <p><strong>Bones (<span id="boneLabel">10%</span>):</strong> <span id="bone"></span></p>
    <p><strong>Liver (5%):</strong> <span id="liver"></span></p>
    <p><strong>Other Organs (5%):</strong> <span id="otherOrgans"></span></p>
  </div>

  <!-- Step 3: Ingredient Selection / Recipe -->
  <div class="recipe-section" id="recipeSection" style="display:none;">
    <h3>Select Ingredients for Your Recipe</h3>
    <p>
      Choose which muscle meat, bones, liver, and other organs you want to feed. 
      The <em>“Show Alternatives”</em> button only appears once you have selected something.
    </p>

    <!-- Ingredient Selection Form -->
    <form id="recipeForm">
      <?php
        function renderIngredientSelect($label, $name, $options) {
            echo '<div class="form-group">';
            echo "<label for=\"$name\">$label:</label>";
            echo "<select id=\"$name\" name=\"$name\" onchange=\"handleIngredientSelectChange('$name')\">";
            echo "<option value=\"\" disabled selected>Select an ingredient</option>";
            foreach ($options as $option) {
                echo "<option value=\"$option\">$option</option>";
            }
            echo "</select>";
            echo "<button id=\"altBtn_$name\" type=\"button\" class=\"alt-btn\" onclick=\"toggleAlternatives('$name')\">Show Alternatives</button>";
            echo "<div id=\"alternatives_$name\" class=\"alternatives\" style=\"display: none; margin-top: 5px;\">";
            echo "<strong>Alternative suggestions:</strong>";
            echo "<ul>";
            foreach ($options as $option) {
                echo "<li onclick=\"selectAlternative('$name', '$option')\" style=\"cursor: pointer;\">$option</li>";
            }
            echo "</ul>";
            echo "</div>";
            echo '</div>';
        }
        renderIngredientSelect("Muscle Meat", "muscleSelect", $groupedIngredients['Muscle Meat']);
        renderIngredientSelect("Bones",       "boneSelect",   $groupedIngredients['Bones']);
        renderIngredientSelect("Liver",       "liverSelect",  $groupedIngredients['Liver']);
        renderIngredientSelect("Other Organs","organSelect",  $groupedIngredients['Other Organs']);
      ?>
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
      </tbody>
    </table>

    <!-- Hidden form for saving to DB -->
    <form id="saveRecipeForm" method="POST" action="">
      <input type="hidden" name="action" value="save_recipe">
      <input type="hidden" name="recipe_name"    id="recipe_name">
      <input type="hidden" name="cat_weight"     id="cat_weight">
      <input type="hidden" name="unit"           id="unit_hidden">
      <input type="hidden" name="feeding_percent"id="feeding_percent">
      <input type="hidden" name="total_food"     id="total_food">
      <input type="hidden" name="muscle"         id="muscle">
      <input type="hidden" name="bone"           id="bone_hidden">
      <input type="hidden" name="liver"          id="liver_hidden">
      <input type="hidden" name="other_organs"   id="other_organs_hidden">
    </form>

    <!-- Save Recipe Button -->
    <button id="saveRecipeBtn">Save Recipe</button>
  </div>
</div>

<!-- FOOTER -->
<div class="footer">
  <p>&copy; <?php echo date('Y'); ?> Raw Feeding App. All rights reserved.</p>
</div>

<script>
// 1) dailyActivityMap => feedingPercent & catWeightStatus
const dailyActivityMap = {
  weightloss: { feedingPercent: 2,   catStatus: "Overweight (weight loss plan)" },
  inactiveObeseProne: { feedingPercent: 2.5, catStatus: "Overweight or Obesity Prone" },
  inactive: { feedingPercent: 3,   catStatus: "Average Weight / Low Activity" },
  neuteredLow: { feedingPercent: 3.5, catStatus: "Average Weight" },
  intactLow: { feedingPercent: 4,   catStatus: "Average Weight" },
  aboveWeightAvg: { feedingPercent: 4.5, catStatus: "Slightly Overweight" },
  aboveAvg1: { feedingPercent: 5,   catStatus: "Underweight or Very Active" },
  highActivity: { feedingPercent: 6,  catStatus: "Underweight / High Activity" },
  aboveAvg2: { feedingPercent: 5.5, catStatus: "Underweight or Above Average Activity" }
};

// 2) Fill Cat Data from Profile Select
function fillCatData() {
  const select = document.getElementById('catProfileSelect');
  if (!select.value) return;
  const profile = JSON.parse(select.value);
  document.getElementById('catWeight').value = profile.weight;
  document.getElementById('unit').value = profile.unit;
  document.getElementById('dailyActivity').value = profile.daily_activity;
}

// 3) Ingredient Alternative Buttons
function toggleAlternatives(selectId) {
  var altDiv = document.getElementById("alternatives_" + selectId);
  altDiv.style.display = (altDiv.style.display === "none") ? "block" : "none";
}

function selectAlternative(selectId, value) {
  document.getElementById(selectId).value = value;
  toggleAlternatives(selectId);
}

function handleIngredientSelectChange(selectId) {
  const selectElem = document.getElementById(selectId);
  const altBtn = document.getElementById("altBtn_" + selectId);
  altBtn.style.display = selectElem.value ? 'inline-block' : 'none';
}

// 4) Main Script to Handle Calculation & Recipe
document.addEventListener('DOMContentLoaded', function() {
  const catForm         = document.getElementById('catForm');
  const pmrResults      = document.getElementById('pmrResults');
  const recipeSection   = document.getElementById('recipeSection');
  const recipeForm      = document.getElementById('recipeForm');
  const recipeTable     = document.getElementById('recipeTable');
  const recipeTableBody = recipeTable.querySelector('tbody');
  const saveRecipeBtn   = document.getElementById('saveRecipeBtn');
  const saveRecipeForm  = document.getElementById('saveRecipeForm');

  const totalFoodSpan    = document.getElementById('totalFood');
  const muscleMeatSpan   = document.getElementById('muscleMeat');
  const boneSpan         = document.getElementById('bone');
  const liverSpan        = document.getElementById('liver');
  const otherOrgansSpan  = document.getElementById('otherOrgans');

  const calculationStepsDiv = document.getElementById('calculationSteps');
  const weightInKgSpan      = document.getElementById('weightInKg');
  const selectedPercentSpan = document.getElementById('selectedPercent');
  const catWeightStatusSpan = document.getElementById('catWeightStatus');

  let totalFoodGrams, muscleGrams, boneGrams, liverGrams, otherOrgansGrams;

  // A) Handle PMR Calculation
  catForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const weight = parseFloat(document.getElementById('catWeight').value);
    const unit = document.getElementById('unit').value;
    const dailyActivity = document.getElementById('dailyActivity').value;
    const catCategory = document.getElementById('catCategory').value;

    if (!weight || !unit || !dailyActivity || !catCategory) {
      alert("Please fill in all fields (Weight, Unit, Daily Activity, and Cat Category).");
      return;
    }

    const activityData = dailyActivityMap[dailyActivity];
    if (!activityData) {
      alert("Invalid activity selection.");
      return;
    }
    const feedingPercent = activityData.feedingPercent;
    const catStatus = activityData.catStatus;

    let weightKg = (unit === 'lb') ? weight * 0.453592 : weight;
    totalFoodGrams = weightKg * (feedingPercent / 100) * 1000;

    // Use new ratios based on cat category:
    if (catCategory === "kitten") {
       muscleGrams = totalFoodGrams * 0.75;
       boneGrams   = totalFoodGrams * 0.15;
    } else { // Default to Adult
       muscleGrams = totalFoodGrams * 0.80;
       boneGrams   = totalFoodGrams * 0.10;
    }
    // Organs remain constant: 10% total (5% liver, 5% other organs)
    liverGrams       = totalFoodGrams * 0.05;
    otherOrgansGrams = totalFoodGrams * 0.05;

    // Update result display (update labels if desired)
    document.getElementById('muscleLabel').textContent = (catCategory === "kitten" ? "75%" : "80%");
    document.getElementById('boneLabel').textContent = (catCategory === "kitten" ? "15%" : "10%");

    catWeightStatusSpan.textContent = catStatus;
    totalFoodSpan.textContent = totalFoodGrams.toFixed(1) + " g";
    muscleMeatSpan.textContent = muscleGrams.toFixed(1) + " g";
    boneSpan.textContent = boneGrams.toFixed(1) + " g";
    liverSpan.textContent = liverGrams.toFixed(1) + " g";
    otherOrgansSpan.textContent = otherOrgansGrams.toFixed(1) + " g";

    weightInKgSpan.textContent = weightKg.toFixed(3) + " kg";
    selectedPercentSpan.textContent = feedingPercent;
    calculationStepsDiv.style.display = 'block';
    pmrResults.style.display = 'block';
    recipeSection.style.display = 'block';
  });

  // B) Handle Ingredient Selection & Build Recipe
  recipeForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const muscleSelect = document.getElementById('muscleSelect').value;
    const boneSelect = document.getElementById('boneSelect').value;
    const liverSelect = document.getElementById('liverSelect').value;
    const organSelect = document.getElementById('organSelect').value;
    recipeTableBody.innerHTML = "";
    function addRow(category, ingredient, amountGrams) {
      if (ingredient) {
        const row = document.createElement('tr');
        row.innerHTML = `<td>${category}</td><td>${ingredient}</td><td>${amountGrams.toFixed(1)} g</td>`;
        recipeTableBody.appendChild(row);
      }
    }
    addRow("Muscle Meat", muscleSelect, muscleGrams);
    addRow("Bones", boneSelect, boneGrams);
    addRow("Liver", liverSelect, liverGrams);
    addRow("Other Organs", organSelect, otherOrgansGrams);
    recipeTable.style.display = 'table';
    saveRecipeBtn.style.display = 'block';
  });

  // C) Save Recipe button => fill hidden form and submit
  saveRecipeBtn.addEventListener('click', function() {
    const recipeName = prompt('What should the recipe name be?');
    if (!recipeName || recipeName.trim() === '') {
      alert('Recipe name cannot be empty.');
      return;
    }
    document.getElementById('recipe_name').value = recipeName;
    document.getElementById('cat_weight').value = document.getElementById('catWeight').value;
    document.getElementById('unit_hidden').value = document.getElementById('unit').value;
    const dailyActivityValue = document.getElementById('dailyActivity').value;
    const feedingPercent = dailyActivityMap[dailyActivityValue].feedingPercent;
    document.getElementById('feeding_percent').value = feedingPercent;
    document.getElementById('total_food').value = totalFoodGrams.toFixed(1);
    document.getElementById('muscle').value = muscleGrams.toFixed(1);
    document.getElementById('bone_hidden').value = boneGrams.toFixed(1);
    document.getElementById('liver_hidden').value = liverGrams.toFixed(1);
    document.getElementById('other_organs_hidden').value = otherOrgansGrams.toFixed(1);
    saveRecipeForm.submit();
  });
});
</script>
</body>
</html>
