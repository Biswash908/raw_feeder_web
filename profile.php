<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db.php';
$user_id = $_SESSION['user_id'];

/**
 * Daily activity map: each key has a label, feeding_percent, and weight_status.
 */
$dailyActivityMap = [
    'weightloss' => [
        'label'           => 'Weightloss',
        'feeding_percent' => 2.0,
        'weight_status'   => 'Overweight (weight loss plan)'
    ],
    'inactiveObeseProne' => [
        'label'           => 'Inactive & Obese Prone',
        'feeding_percent' => 2.5,
        'weight_status'   => 'Overweight or Obesity Prone'
    ],
    'inactive' => [
        'label'           => 'Inactive',
        'feeding_percent' => 3.0,
        'weight_status'   => 'Average Weight / Low Activity'
    ],
    'neuteredLow' => [
        'label'           => 'Neutered Adult & Low Activity (0.5-1hr daily)',
        'feeding_percent' => 3.5,
        'weight_status'   => 'Average Weight'
    ],
    'intactLow' => [
        'label'           => 'Intact Adult & Low Activity (0.5-1hr daily)',
        'feeding_percent' => 4.0,
        'weight_status'   => 'Average Weight'
    ],
    'aboveWeightAvg' => [
        'label'           => 'Above Weight & Average Activity (1-2 hours daily)',
        'feeding_percent' => 4.5,
        'weight_status'   => 'Slightly Overweight'
    ],
    'aboveAvg1' => [
        'label'           => 'Above Average Activity (1-2 hours daily)',
        'feeding_percent' => 5.0,
        'weight_status'   => 'Underweight or Very Active'
    ],
    'highActivity' => [
        'label'           => 'High Activity & High Intensity (3hr+ daily)',
        'feeding_percent' => 6.0,
        'weight_status'   => 'Underweight / High Activity'
    ],
    'aboveAvg2' => [
        'label'           => 'Above Average Activity (3hrs daily)',
        'feeding_percent' => 5.5,
        'weight_status'   => 'Underweight or Above Average Activity'
    ],
];

// ----------------------------------------------------------------
// (1) Handle Deletion if delete_id is in the URL
// ----------------------------------------------------------------
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];

    // Make sure the cat belongs to this user
    $stmt_del = $conn->prepare("DELETE FROM cat_info WHERE id = ? AND user_id = ?");
    $stmt_del->bind_param("ii", $delete_id, $user_id);
    $stmt_del->execute();
    $stmt_del->close();

    header("Location: profile.php");
    exit;
}

// ----------------------------------------------------------------
// (2) If edit_id is provided, fetch that specific cat for editing
// ----------------------------------------------------------------
$cat_to_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];

    // Fetch the cat that belongs to this user
    $stmt_edit = $conn->prepare("SELECT * FROM cat_info WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt_edit->bind_param("ii", $edit_id, $user_id);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    if ($result_edit->num_rows > 0) {
        $cat_to_edit = $result_edit->fetch_assoc();
    }
    $stmt_edit->close();
}

// ----------------------------------------------------------------
// (3) Handle the Add / Update form submission
// ----------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $cat_id         = isset($_POST['cat_id']) ? (int) $_POST['cat_id'] : null; // hidden input if editing
    $cat_name       = trim($_POST['cat_name']);
    $weight         = floatval($_POST['weight']);
    $unit           = trim($_POST['unit']);
    $daily_activity = trim($_POST['daily_activity']);

    // Derive feeding_percent from dailyActivityMap
    if (isset($dailyActivityMap[$daily_activity])) {
        $feeding_percent = $dailyActivityMap[$daily_activity]['feeding_percent'];
    } else {
        $daily_activity  = 'inactive';
        $feeding_percent = 3.0;
    }

    // If we have a cat_id, do an UPDATE, otherwise INSERT a new record
    if ($cat_id) {
        // Update existing cat
        $stmt_update = $conn->prepare(
            "UPDATE cat_info
             SET cat_name = ?, weight = ?, unit = ?, daily_activity = ?, feeding_percent = ?
             WHERE id = ? AND user_id = ?"
        );
        $stmt_update->bind_param(
            "sdssdii",
            $cat_name, $weight, $unit, $daily_activity, $feeding_percent,
            $cat_id, $user_id
        );
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // Insert new cat
        $stmt_insert = $conn->prepare(
            "INSERT INTO cat_info (user_id, cat_name, weight, unit, daily_activity, feeding_percent)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt_insert->bind_param(
            "isdssd",
            $user_id, $cat_name, $weight, $unit, $daily_activity, $feeding_percent
        );
        $stmt_insert->execute();
        $stmt_insert->close();
    }

    header("Location: profile.php");
    exit;
}

// ----------------------------------------------------------------
// (4) Fetch ALL cat profiles for this user to display
// ----------------------------------------------------------------
$stmt_cats = $conn->prepare("SELECT * FROM cat_info WHERE user_id = ? ORDER BY id DESC");
$stmt_cats->bind_param("i", $user_id);
$stmt_cats->execute();
$all_cats_result = $stmt_cats->get_result();
$all_cats = $all_cats_result->fetch_all(MYSQLI_ASSOC);
$stmt_cats->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Profile - Raw Feeding App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/style.css">

  <style>
    /* -----------------------------------------
       MAIN CONTAINER & GENERAL PAGE STYLES
    ----------------------------------------- */
    body {
      background-color: #f8f9fa;
      font-family: 'Helvetica Neue', Arial, sans-serif;
      color: #333;
    }
    .profile-container {
      max-width: 900px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    h1, h2 {
      margin-bottom: 20px;
    }
    .navbar {
      background-color: #343a40; /* Darker navbar */
      padding: 0.75rem 1rem;
    }
    .navbar .logo {
      font-size: 1.25rem;
      font-weight: bold;
      color: #fff;
      margin-right: auto;
      text-transform: uppercase;
      letter-spacing: 1px;
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
      font-weight: 500;
      padding: 0.5rem 0.75rem;
      border-radius: 4px;
      transition: background-color 0.2s;
    }
    .navbar ul li a:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }

    /* -----------------------------------------
       TABLE STYLES
    ----------------------------------------- */
    table.table {
      margin-bottom: 2rem;
      background-color: #fff;
    }
    table thead {
      background-color: #f8f9fa;
      font-weight: 600;
    }
    table thead th {
      border-bottom: 2px solid #dee2e6;
    }
    table td, table th {
      vertical-align: middle !important;
    }

    /* -----------------------------------------
       BUTTON STYLES
    ----------------------------------------- */
    .btn-warning {
      color: #fff;
      background-color: #fd7e14; /* Bootstrap Orange */
      border-color: #fd7e14;
    }
    .btn-warning:hover {
      background-color: #e06c0a;
      border-color: #d8690a;
    }
    .btn-danger {
      color: #fff;
      background-color: #dc3545; /* Bootstrap Red */
      border-color: #dc3545;
    }
    .btn-danger:hover {
      background-color: #bd2130;
      border-color: #b21f2d;
    }
    .btn-primary {
      background-color: #0d6efd;
      border-color: #0d6efd;
    }
    .btn-primary:hover {
      background-color: #0b5ed7;
      border-color: #0a58ca;
    }

    /* -----------------------------------------
       FORM & CARD STYLES
    ----------------------------------------- */
    .card {
      border: none;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      margin-bottom: 2rem;
    }
    .card-header {
      background-color: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
      font-weight: 600;
    }
    .card-body .form-label {
      font-weight: 600;
    }
  </style>
</head>
<body>
  <!-- NAVBAR -->
  <div class="navbar d-flex align-items-center">
    <div class="logo">Raw Meat Feeding Calculator</div>
    <ul class="ms-auto d-flex">
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="calculator.php">Calculator</a></li>
      <li><a href="recipes.php">Recipes</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </div>
  <!-- END NAVBAR -->

  <div class="container profile-container">
    <h1>Your Profile</h1>

    <!-- (A) List of All Cats -->
    <h2>All Your Cats</h2>
    <?php if (count($all_cats) > 0): ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered align-middle">
          <thead>
            <tr>
              <th>Cat Name</th>
              <th>Weight</th>
              <th>Activity</th>
              <th>Feeding %</th>
              <th>Weight Status</th>
              <th class="text-center" style="width:150px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($all_cats as $cat): ?>
              <?php
                $actKey = $cat['daily_activity'];
                $activityLabel = isset($dailyActivityMap[$actKey]) 
                                 ? $dailyActivityMap[$actKey]['label'] 
                                 : "Not specified";
                $feedingPercent = isset($dailyActivityMap[$actKey]) 
                                 ? $dailyActivityMap[$actKey]['feeding_percent'] 
                                 : $cat['feeding_percent'];
                $weightStatus = isset($dailyActivityMap[$actKey]) 
                                ? $dailyActivityMap[$actKey]['weight_status'] 
                                : "Unknown";
              ?>
              <tr>
                <td><?php echo htmlspecialchars($cat['cat_name']); ?></td>
                <td>
                  <?php echo htmlspecialchars($cat['weight']); ?>
                  <?php echo htmlspecialchars($cat['unit']); ?>
                </td>
                <td><?php echo htmlspecialchars($activityLabel); ?></td>
                <td><?php echo htmlspecialchars($cat['feeding_percent']) . '%'; ?></td>
                <td><?php echo htmlspecialchars($weightStatus); ?></td>
                <td class="text-center">
                  <a href="profile.php?edit_id=<?php echo $cat['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                  <a href="profile.php?delete_id=<?php echo $cat['id']; ?>"
                     class="btn btn-danger btn-sm"
                     onclick="return confirm('Are you sure you want to delete this cat?');">
                    Delete
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p>You haven't added any cat info yet.</p>
    <?php endif; ?>

    <!-- (B) Add or Edit Cat Form -->
    <div class="card">
      <div class="card-header">
        <?php echo ($cat_to_edit ? "Edit This Cat" : "Add a New Cat"); ?>
      </div>
      <div class="card-body">
        <form method="POST" action="">
          <!-- If editing, include hidden cat_id -->
          <?php if ($cat_to_edit): ?>
            <input type="hidden" name="cat_id" value="<?php echo $cat_to_edit['id']; ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label for="cat_name" class="form-label">Cat Name:</label>
            <input type="text" class="form-control" id="cat_name" name="cat_name" required
              value="<?php echo $cat_to_edit ? htmlspecialchars($cat_to_edit['cat_name']) : ''; ?>">
          </div>

          <div class="mb-3">
            <label for="weight" class="form-label">Weight:</label>
            <input type="number" class="form-control" id="weight" name="weight" step="0.1" required
              value="<?php echo $cat_to_edit ? htmlspecialchars($cat_to_edit['weight']) : ''; ?>">
          </div>

          <div class="mb-3">
            <label for="unit" class="form-label">Unit:</label>
            <select class="form-select" id="unit" name="unit" required>
              <option value="" disabled <?php echo (!$cat_to_edit ? "selected" : ""); ?>>Select unit</option>
              <option value="kg" <?php echo ($cat_to_edit && $cat_to_edit['unit'] === 'kg' ? "selected" : ""); ?>>
                Kilograms (kg)
              </option>
              <option value="lb" <?php echo ($cat_to_edit && $cat_to_edit['unit'] === 'lb' ? "selected" : ""); ?>>
                Pounds (lb)
              </option>
            </select>
          </div>

          <div class="mb-3">
            <label for="daily_activity" class="form-label">Daily Activity:</label>
            <select class="form-select" id="daily_activity" name="daily_activity" required>
              <option value="" disabled <?php echo (!$cat_to_edit ? "selected" : ""); ?>>
                Select daily activity level
              </option>
              <?php foreach ($dailyActivityMap as $key => $info): ?>
                <option value="<?php echo htmlspecialchars($key); ?>"
                  <?php
                    if ($cat_to_edit && $cat_to_edit['daily_activity'] === $key) {
                        echo "selected";
                    }
                  ?>>
                  <?php echo htmlspecialchars($info['label']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn btn-primary">
            <?php echo ($cat_to_edit ? "Update Cat" : "Add Cat"); ?>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- FOOTER (OPTIONAL) -->
  <div class="text-center mt-4 mb-4 text-muted">
    <small>&copy; <?php echo date('Y'); ?> Raw Feeding App. All rights reserved.</small>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
