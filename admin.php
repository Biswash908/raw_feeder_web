<?php
session_start();

// (A) Verify the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// (B) Connect to DB (adjust your credentials/path as needed)
$host = 'localhost';
$db   = 'cat_feeding_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// -------------------------------
// (C1) Handle "Update Ingredient" Form Submission
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_ingredient') {
    $id    = intval($_POST['id']);
    $name  = trim($_POST['name']);
    $meat  = floatval($_POST['meat']);
    $bone  = floatval($_POST['bone']);
    $organ = floatval($_POST['organ']);

    $sql = "UPDATE ingredients SET name = :name, meat = :meat, bone = :bone, organ = :organ WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'  => $name,
        ':meat'  => $meat,
        ':bone'  => $bone,
        ':organ' => $organ,
        ':id'    => $id
    ]);
    echo "<script>alert('Ingredient updated successfully!');</script>";
    header("Location: admin.php");
    exit;
}

// -------------------------------
// (C2) Handle "Delete Ingredient" Action
// -------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete_ingredient' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("DELETE FROM ingredients WHERE id = :id");
    $stmt->execute([':id' => $id]);
    echo "<script>alert('Ingredient deleted successfully!');</script>";
    header("Location: admin.php");
    exit;
}

// -------------------------------
// (C3) Handle "Add Ingredient" Form Submission
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_ingredient') {
    $name  = trim($_POST['name']);
    $meat  = floatval($_POST['meat']);
    $bone  = floatval($_POST['bone']);
    $organ = floatval($_POST['organ']);

    // Insert new ingredient
    $sql = "INSERT INTO ingredients (name, meat, bone, organ) VALUES (:name, :meat, :bone, :organ)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'  => $name,
        ':meat'  => $meat,
        ':bone'  => $bone,
        ':organ' => $organ
    ]);
    echo "<script>alert('Ingredient added successfully!');</script>";
}

// -------------------------------
// (C4) Check if Edit Ingredient is requested (via GET)
// -------------------------------
$editIngredient = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit_ingredient' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM ingredients WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $editIngredient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// -------------------------------
// (D) Fetch All Data for Admin View
// -------------------------------

// 1) All users
$sqlUsers = "SELECT id, username, role FROM users ORDER BY id DESC";
$users = $pdo->query($sqlUsers)->fetchAll(PDO::FETCH_ASSOC);

// 2) All cat_info (with user info)
$sqlCats = "SELECT c.id AS cat_id, c.cat_name, c.weight, c.unit, c.daily_activity, c.feeding_percent,
                   u.username AS owner_name, u.id AS owner_id
            FROM cat_info c
            JOIN users u ON c.user_id = u.id
            ORDER BY c.id DESC";
$cats = $pdo->query($sqlCats)->fetchAll(PDO::FETCH_ASSOC);

// 3) All recipes (with user info)
$sqlRecipes = "SELECT r.id AS recipe_id, r.recipe_name, r.cat_weight, r.unit, r.feeding_percent, 
                      r.total_food, r.muscle, r.bone, r.liver, r.other_organs, r.created_at,
                      u.username AS owner_name, u.id AS owner_id
               FROM recipes r
               JOIN users u ON r.user_id = u.id
               ORDER BY r.id DESC";
$recipes = $pdo->query($sqlRecipes)->fetchAll(PDO::FETCH_ASSOC);

// 4) All ingredients
$sqlIngredients = "SELECT id, name, meat, bone, organ FROM ingredients ORDER BY id DESC";
$ingredients = $pdo->query($sqlIngredients)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Raw Feeding App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Basic styling for a professional look */
    body {
      background-color: #f8f9fa;
      font-family: 'Helvetica Neue', Arial, sans-serif;
      color: #333;
    }
    .navbar {
      background-color: #343a40;
      padding: 0.75rem 1rem;
      margin-bottom: 30px;
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
    h1 {
      margin-bottom: 20px;
    }
    .section-title {
      margin-top: 40px;
      margin-bottom: 20px;
      font-weight: 600;
    }
    table th,
    table td {
      vertical-align: middle !important;
    }
    .footer {
      text-align: center;
      margin: 20px auto;
      color: #777;
      font-size: 0.9rem;
    }
  </style>
  <script>
    function confirmDelete(id) {
      if (confirm("Are you sure you want to delete this ingredient?")) {
        window.location.href = "admin.php?action=delete_ingredient&id=" + id;
      }
    }
  </script>
</head>
<body>
<!-- NAVBAR -->
<div class="navbar d-flex align-items-center">
  <div class="logo">Raw Feeding Admin</div>
  <ul class="ms-auto d-flex">
    <li><a href="dashboard.php">Dashboard</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</div>
<!-- END NAVBAR -->

<div class="container">
  <h1>Welcome, Admin!</h1>
  <p class="text-muted">Use this panel to manage users, view their cats & recipes, and add/edit/delete ingredients.</p>

  <!-- ======================= USERS SECTION ======================= -->
  <h2 class="section-title">All Users</h2>
  <?php if (count($users) > 0): ?>
    <div class="table-responsive">
      <table class="table table-striped table-hover table-bordered align-middle">
        <thead class="table-dark">
          <tr>
            <th>User ID</th>
            <th>Username</th>
            <th>Role</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?php echo htmlspecialchars($u['id']); ?></td>
              <td><?php echo htmlspecialchars($u['username']); ?></td>
              <td><?php echo htmlspecialchars($u['role']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p>No users found.</p>
  <?php endif; ?>

  <!-- ======================= CAT INFO SECTION ======================= -->
  <h2 class="section-title">All Cats (cat_info)</h2>
  <?php if (count($cats) > 0): ?>
    <div class="table-responsive">
      <table class="table table-striped table-hover table-bordered align-middle">
        <thead class="table-dark">
          <tr>
            <th>Cat ID</th>
            <th>Cat Name</th>
            <th>Weight</th>
            <th>Unit</th>
            <th>Daily Activity</th>
            <th>Feeding %</th>
            <th>Owner (User)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cats as $c): ?>
            <tr>
              <td><?php echo htmlspecialchars($c['cat_id']); ?></td>
              <td><?php echo htmlspecialchars($c['cat_name']); ?></td>
              <td><?php echo htmlspecialchars($c['weight']); ?></td>
              <td><?php echo htmlspecialchars($c['unit']); ?></td>
              <td><?php echo htmlspecialchars($c['daily_activity']); ?></td>
              <td><?php echo htmlspecialchars($c['feeding_percent']); ?>%</td>
              <td><?php echo htmlspecialchars($c['owner_name']); ?> (ID: <?php echo $c['owner_id']; ?>)</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p>No cat profiles found.</p>
  <?php endif; ?>

  <!-- ======================= RECIPES SECTION ======================= -->
  <h2 class="section-title">All Recipes</h2>
  <?php if (count($recipes) > 0): ?>
    <div class="table-responsive">
      <table class="table table-striped table-hover table-bordered align-middle">
        <thead class="table-dark">
          <tr>
            <th>Recipe ID</th>
            <th>Recipe Name</th>
            <th>Cat Weight</th>
            <th>Unit</th>
            <th>Feeding %</th>
            <th>Total Food (g)</th>
            <th>Muscle (g)</th>
            <th>Bone (g)</th>
            <th>Liver (g)</th>
            <th>Other Organs (g)</th>
            <th>Created At</th>
            <th>Owner (User)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recipes as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars($r['recipe_id']); ?></td>
              <td><?php echo htmlspecialchars($r['recipe_name']); ?></td>
              <td><?php echo htmlspecialchars($r['cat_weight']); ?></td>
              <td><?php echo htmlspecialchars($r['unit']); ?></td>
              <td><?php echo htmlspecialchars($r['feeding_percent']); ?>%</td>
              <td><?php echo htmlspecialchars($r['total_food']); ?></td>
              <td><?php echo htmlspecialchars($r['muscle']); ?></td>
              <td><?php echo htmlspecialchars($r['bone']); ?></td>
              <td><?php echo htmlspecialchars($r['liver']); ?></td>
              <td><?php echo htmlspecialchars($r['other_organs']); ?></td>
              <td><?php echo htmlspecialchars($r['created_at']); ?></td>
              <td><?php echo htmlspecialchars($r['owner_name']); ?> (ID: <?php echo $r['owner_id']; ?>)</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p>No recipes found.</p>
  <?php endif; ?>

  <!-- ======================= INGREDIENTS SECTION ======================= -->
  <h2 class="section-title">All Ingredients</h2>
  <?php if (count($ingredients) > 0): ?>
    <div class="table-responsive">
      <table class="table table-striped table-hover table-bordered align-middle">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Meat %</th>
            <th>Bone %</th>
            <th>Organ %</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ingredients as $ing): ?>
            <tr>
              <td><?php echo htmlspecialchars($ing['id']); ?></td>
              <td><?php echo htmlspecialchars($ing['name']); ?></td>
              <td><?php echo htmlspecialchars($ing['meat']); ?></td>
              <td><?php echo htmlspecialchars($ing['bone']); ?></td>
              <td><?php echo htmlspecialchars($ing['organ']); ?></td>
              <td>
                <a href="admin.php?action=edit_ingredient&id=<?php echo $ing['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                <button onclick="confirmDelete(<?php echo $ing['id']; ?>)" class="btn btn-sm btn-danger">Delete</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p>No ingredients found yet.</p>
  <?php endif; ?>

  <!-- ======================= EDIT INGREDIENT FORM (if requested) ======================= -->
  <?php if ($editIngredient): ?>
    <div class="card mt-4">
      <div class="card-header">Edit Ingredient (ID: <?php echo htmlspecialchars($editIngredient['id']); ?>)</div>
      <div class="card-body">
        <form method="POST" action="">
          <input type="hidden" name="action" value="update_ingredient">
          <input type="hidden" name="id" value="<?php echo htmlspecialchars($editIngredient['id']); ?>">
          <div class="mb-3">
            <label for="edit-name" class="form-label">Ingredient Name</label>
            <input type="text" class="form-control" id="edit-name" name="name" value="<?php echo htmlspecialchars($editIngredient['name']); ?>" required>
          </div>
          <div class="mb-3">
            <label for="edit-meat" class="form-label">Meat (%)</label>
            <input type="number" step="0.1" class="form-control" id="edit-meat" name="meat" value="<?php echo htmlspecialchars($editIngredient['meat']); ?>" required>
          </div>
          <div class="mb-3">
            <label for="edit-bone" class="form-label">Bone (%)</label>
            <input type="number" step="0.1" class="form-control" id="edit-bone" name="bone" value="<?php echo htmlspecialchars($editIngredient['bone']); ?>" required>
          </div>
          <div class="mb-3">
            <label for="edit-organ" class="form-label">Organ (%)</label>
            <input type="number" step="0.1" class="form-control" id="edit-organ" name="organ" value="<?php echo htmlspecialchars($editIngredient['organ']); ?>" required>
          </div>
          <button type="submit" class="btn btn-primary">Update Ingredient</button>
          <a href="admin.php" class="btn btn-secondary">Cancel</a>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- ======================= ADD NEW INGREDIENT FORM ======================= -->
  <div class="card mt-4">
    <div class="card-header">Add a New Ingredient</div>
    <div class="card-body">
      <form method="POST" action="">
        <input type="hidden" name="action" value="add_ingredient">
        <div class="mb-3">
          <label for="name" class="form-label">Ingredient Name</label>
          <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
          <label for="meat" class="form-label">Meat (%)</label>
          <input type="number" step="0.1" class="form-control" id="meat" name="meat" value="0" required>
        </div>
        <div class="mb-3">
          <label for="bone" class="form-label">Bone (%)</label>
          <input type="number" step="0.1" class="form-control" id="bone" name="bone" value="0" required>
        </div>
        <div class="mb-3">
          <label for="organ" class="form-label">Organ (%)</label>
          <input type="number" step="0.1" class="form-control" id="organ" name="organ" value="0" required>
        </div>
        <button type="submit" class="btn btn-primary">Add Ingredient</button>
      </form>
    </div>
  </div>
</div>

<!-- FOOTER -->
<div class="footer">
  <p>&copy; <?php echo date('Y'); ?> Raw Feeding App. All rights reserved.</p>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
