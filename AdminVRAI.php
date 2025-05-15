<?php
session_start();
include 'connexion.php';

function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function logEvent($bdd, $eventType, $badge, $techID) {
    $stmt = $bdd->prepare("INSERT INTO Historique (HistoriqueDate, HistoriqueHeure, HistoriqueTypeEvent, badge, TechID) VALUES (CURDATE(), CURTIME(), ?, ?, ?)");
    $stmt->execute([$eventType, $badge, $techID]);
}

if (!isset($_SESSION['TechID'])) {
    header('Location: login.php');
    exit();
}

$techID = $_SESSION['TechID'];
$adminCheck = $bdd->prepare("SELECT * FROM Technicien WHERE TechnicienID = ? AND TechRole = 'Admin'");
$adminCheck->execute([$techID]);

if ($adminCheck->rowCount() === 0) {
    echo '<div class="alert alert-danger text-center mt-5">Accès réservé aux administrateurs.</div>';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajouter_stock'])) {
        $casierID = validateInput($_POST['casierID']);
        $quantite = validateInput($_POST['quantite']);

        $stmt = $bdd->prepare("SELECT MaterielID FROM Contenir WHERE CasierID = ?");
        $stmt->execute([$casierID]);
        $result = $stmt->fetch();

        if ($result) {
            $materielID = $result['MaterielID'];
            $bdd->prepare("UPDATE Materiel SET Quantite = Quantite + ? WHERE MaterielID = ?")->execute([$quantite, $materielID]);
            $bdd->prepare("UPDATE Casier SET CasierQte = CasierQte + ? WHERE CasierID = ?")->execute([$quantite, $casierID]);
            logEvent($bdd, 'Ajout matériel - Casier ' . $casierID, $_SESSION['TechBadge'], $techID);
            echo '<div class="alert alert-success">Stock ajouté avec succès.</div>';
        }
    }

    if (isset($_POST['ajouter_technicien'])) {
        $nom = validateInput($_POST['tech_nom']);
        $prenom = validateInput($_POST['tech_prenom']);
        $role = validateInput($_POST['tech_role']);
        $badge = validateInput($_POST['tech_badge']);
        $mdp = password_hash($_POST['tech_password'], PASSWORD_DEFAULT);

        $bdd->prepare("INSERT INTO Technicien (TechNom, TechPrenom, TechRole, TechBadge, TechPassword) VALUES (?, ?, ?, ?, ?)")->execute([$nom, $prenom, $role, $badge, $mdp]);
        logEvent($bdd, 'Ajout technicien - ' . $badge, $_SESSION['TechBadge'], $techID);
        echo '<div class="alert alert-success">Technicien ajouté avec succès.</div>';
    }

    if (isset($_POST['set_acces'])) {
        $tech_id = validateInput($_POST['access_tech_id']);
        $access_casiers = $_POST['access_casiers'] ?? [];

        $bdd->prepare("DELETE FROM Acceder WHERE TechnicienID = ?")->execute([$tech_id]);
        foreach ($access_casiers as $casier_id) {
            $bdd->prepare("INSERT INTO Acceder (TechnicienID, CasierID) VALUES (?, ?)")->execute([$tech_id, $casier_id]);
        }
        logEvent($bdd, 'Mise à jour accès - Technicien ' . $tech_id, $_SESSION['TechBadge'], $techID);
        echo '<div class="alert alert-success">Accès mis à jour avec succès.</div>';
    }

    if (isset($_POST['ouvrir_tous'])) {
        shell_exec("sudo /home/pi/init/imp.sh");
        logEvent($bdd, 'Ouverture de tous les casiers', $_SESSION['TechBadge'], $techID);
        echo '<div class="alert alert-success">Tous les casiers ont été ouverts.</div>';
    }
}

$casiers = $bdd->query("SELECT CasierID, CasierType FROM Casier ORDER BY CasierID")->fetchAll();
$techs = $bdd->query("SELECT TechnicienID, TechPrenom, TechNom FROM Technicien ORDER BY TechPrenom")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - IronBox</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
    }
    .sidebar {
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      width: 250px;
      background-color: #2c3e50;
      padding: 20px;
      color: white;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }
    .sidebar h4 {
      margin-bottom: 20px;
      font-weight: 600;
    }
    .sidebar a, .sidebar button {
      display: block;
      width: 100%;
      padding: 10px;
      color: white;
      text-decoration: none;
      margin: 5px 0;
      border: none;
      background: none;
      text-align: left;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .sidebar a:hover, .sidebar button:hover {
      background-color: #34495e;
    }
    .sidebar a i, .sidebar button i {
      margin-right: 10px;
    }
    .main {
      margin-left: 270px;
      padding: 30px;
    }
    .card {
      margin-bottom: 30px;
      border: none;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .card-header {
      background-color: #3498db;
      color: white;
      border-radius: 10px 10px 0 0 !important;
      padding: 15px;
      font-weight: 600;
    }
    .card-body {
      padding: 20px;
    }
    .form-control, .form-select {
      border-radius: 5px;
      border: 1px solid #ced4da;
    }
    .btn {
      border-radius: 5px;
      padding: 10px 20px;
      font-weight: 600;
    }
    .btn-success {
      background-color: #2ecc71;
      border-color: #2ecc71;
    }
    .btn-primary {
      background-color: #3498db;
      border-color: #3498db;
    }
    .btn-warning {
      background-color: #f39c12;
      border-color: #f39c12;
    }
    .btn-danger {
      background-color: #e74c3c;
      border-color: #e74c3c;
    }
    .table {
      margin-top: 20px;
    }
    .table th {
      background-color: #3498db;
      color: white;
    }
    .table-hover tbody tr:hover {
      background-color: rgba(0, 0, 0, 0.05);
    }
    .form-check {
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <h4><i class="bi bi-gear"></i> Admin IronBox</h4>
  <hr>
  <a href="#stock"><i class="bi bi-box-seam"></i> Stock</a>
  <a href="#tech"><i class="bi bi-people"></i> Techniciens</a>
  <a href="#access"><i class="bi bi-lock"></i> Accès</a>
  <a href="#historique"><i class="bi bi-clock-history"></i> Historique</a>
  <form method="POST" style="margin: 0;">
    <button type="submit" name="ouvrir_tous" class="btn btn-danger" onclick="return confirm('Ouvrir tous les casiers ?');">
      <i class="bi bi-unlock"></i> Ouvrir Tous les Casiers
    </button>
  </form>
  <a href="logout.php" class="btn btn-danger"><i class="bi bi-box-arrow-left"></i> Déconnexion</a>
</div>

<div class="main">
  <h2 class="mb-4">Tableau de bord</h2>

  <div id="stock" class="card">
    <div class="card-header">Ajouter du Stock</div>
    <div class="card-body">
      <form method="POST">
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Casier</label>
            <select name="casierID" class="form-select">
              <?php foreach ($casiers as $casier): ?>
                <option value="<?= $casier['CasierID'] ?>">#<?= $casier['CasierID'] ?> - <?= $casier['CasierType'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Quantité</label>
            <input type="number" name="quantite" class="form-control" required>
          </div>
          <div class="col-md-2 d-grid align-items-end">
            <button type="submit" name="ajouter_stock" class="btn btn-success">Ajouter</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div id="tech" class="card">
    <div class="card-header">Ajouter un Technicien</div>
    <div class="card-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-4"><input name="tech_nom" class="form-control" placeholder="Nom" required></div>
          <div class="col-md-4"><input name="tech_prenom" class="form-control" placeholder="Prénom" required></div>
          <div class="col-md-4">
            <select name="tech_role" class="form-select">
              <option value="Admin">Admin</option>
              <option value="Technicien">Technicien</option>
            </select>
          </div>
          <div class="col-md-6"><input name="tech_badge" class="form-control" placeholder="Badge ID" required></div>
          <div class="col-md-6"><input type="password" name="tech_password" class="form-control" placeholder="Mot de passe" required></div>
          <div class="col-md-12 d-grid">
            <button type="submit" name="ajouter_technicien" class="btn btn-primary">Ajouter Technicien</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div id="access" class="card">
    <div class="card-header">Gérer les Accès aux Casiers</div>
    <div class="card-body">
      <form method="POST">
        <div class="row mb-3">
          <div class="col-md-6">
            <label>Technicien</label>
            <select name="access_tech_id" class="form-select">
              <?php foreach ($techs as $tech): ?>
                <option value="<?= $tech['TechnicienID'] ?>"><?= $tech['TechPrenom'] ?> <?= $tech['TechNom'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <label>Casiers autorisés :</label>
        <div class="row">
          <?php foreach ($casiers as $casier): ?>
            <div class="col-md-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="access_casiers[]" value="<?= $casier['CasierID'] ?>">
                <label class="form-check-label">#<?= $casier['CasierID'] ?> - <?= $casier['CasierType'] ?></label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-3">
          <button type="submit" name="set_acces" class="btn btn-warning">Mettre à jour les accès</button>
        </div>
      </form>
    </div>
  </div>

  <div id="historique" class="card">
    <div class="card-header">Historique</div>
    <div class="card-body">
      <div class="mb-3">
        <input type="text" id="filter" class="form-control" placeholder="Filtrer l'historique...">
      </div>
      <table class="table table-striped table-hover">
        <thead class="table-dark">
          <tr>
            <th>Date</th>
            <th>Heure</th>
            <th>Évènement</th>
            <th>Badge</th>
            <th>Technicien</th>
          </tr>
        </thead>
        <tbody id="historique-body"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
  function fetchHistorique(filter = '') {
    fetch(`fetch_historique.php?filter=${encodeURIComponent(filter)}`)
      .then(res => res.json())
      .then(data => {
        const tbody = document.getElementById('historique-body');
        tbody.innerHTML = '';
        data.forEach(item => {
          tbody.innerHTML += `<tr>
            <td>${item.HistoriqueDate}</td>
            <td>${item.HistoriqueHeure}</td>
            <td>${item.HistoriqueTypeEvent}</td>
            <td>${item.badge ?? '-'}</td>
            <td>${item.TechPrenom ?? ''} ${item.TechNom ?? ''}</td>
          </tr>`;
        });
      });
  }

  document.getElementById('filter').addEventListener('input', function() {
    fetchHistorique(this.value);
  });

  setInterval(() => {
    fetchHistorique(document.getElementById('filter').value);
  }, 5000);
</script>

</body>
</html>
