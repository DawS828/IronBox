pi@raspberrypi:/var/www/html/IronBox/Client $ cat clientVRAI.php
<?php
session_start();
include 'connexion.php';

//$badge_file = 'badge.json';

//if (file_exists($badge_file)) {
  //  $json = json_decode(file_get_contents($badge_file), true);
    //$badge = $json['badge'] ?? null;

    //if ($badge) {
        $stmt = $bdd->prepare("SELECT * FROM Technicien WHERE TechBadge = 3905237909920305987");
        $stmt->execute([$badge]);
        $tech = $stmt->fetch();

        if ($tech) {
            $_SESSION['client_id'] = $tech['TechnicienID'];
            $_SESSION['client_nom'] = $tech['TechPrenom'] . ' ' . $tech['TechNom'];
            sleep(5);
       //     unlink($badge_file);
        }
   // }
//}

//if (!isset($_SESSION['client_id'])) {
//    header('Location: login_client.php');
//    exit();
//}

$techID = $_SESSION['client_id'];
$clientNom = $_SESSION['client_nom'];

$casiers = $bdd->prepare("
    SELECT Casier.CasierID, Casier.CasierType, Casier.CasierQte
    FROM Acceder
    JOIN Casier ON Casier.CasierID = Acceder.CasierID
    WHERE Acceder.TechnicienID = ?
");
$casiers->execute([$techID]);
$casierList = $casiers->fetchAll();

$badge = $bdd->prepare("SELECT TechBadge FROM Technicien WHERE TechnicienID = ?");
$badge->execute([$techID]);
$badge = $badge->fetchColumn();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retirer_id']) && isset($_POST['quantite'])) {
    $casierID = intval($_POST['retirer_id']);
    $quantite = intval($_POST['quantite']);

    if ($quantite <= 0) {
        $message = "❌ Quantité invalide.";
    } else {
        $qCheck = $bdd->prepare("SELECT CasierQte FROM Casier WHERE CasierID = ?");
        $qCheck->execute([$casierID]);
        $qte_dispo = $qCheck->fetchColumn();

        if ($qte_dispo >= $quantite) {
            $stmt = $bdd->prepare("SELECT MaterielID FROM Contenir WHERE CasierID = ?");
            $stmt->execute([$casierID]);
            $materielID = $stmt->fetchColumn();

            $bdd->prepare("UPDATE Materiel SET Quantite = Quantite - ? WHERE MaterielID = ?")
                ->execute([$quantite, $materielID]);
            $bdd->prepare("UPDATE Casier SET CasierQte = CasierQte - ? WHERE CasierID = ?")
                ->execute([$quantite, $casierID]);

            $insertHist = $bdd->prepare("INSERT INTO Historique (HistoriqueDate, HistoriqueHeure, HistoriqueTypeEvent, badge, TechID)
                                         VALUES (CURDATE(), CURTIME(), ?, ?, ?)");
            $insertHist->execute(["Retrait $quantite matériel(s) - Casier $casierID", $badge, $techID]);
            $histID = $bdd->lastInsertId();

            $bdd->prepare("INSERT INTO Lister (HistoriqueID, MaterielID) VALUES (?, ?)")
                ->execute([$histID, $materielID]);

            // Exécution du script shell associé au casier
            $scriptPath = "/home/pi/init/open_cas{$casierID}.sh";
            if (file_exists($scriptPath)) {
                shell_exec("bash " . escapeshellarg($scriptPath));
            }

            $message = "✅ Casier $casierID ouvert ($quantite matériel(s) retiré(s))";
        } else {
            $message = "❌ Stock insuffisant pour le casier $casierID. Quantité demandée : $quantite, Quantité disponible : $qte_dispo";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>IronBox - BONJOUR Accès</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { background: #f2f4f8; padding: 20px; font-size: 18px; }
    .casier-card {
      background: white; padding: 20px; border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    .casier-card h5 { font-size: 22px; }
    .btn-retirer { font-size: 20px; padding: 10px 20px; }
    .header { text-align: center; margin-bottom: 30px; }
    .message { text-align: center; font-size: 20px; color: green; margin-bottom: 20px; }
  </style>
</head>
<body>

<div class="header">
  <h3>Bienvenue <?= htmlspecialchars($clientNom) ?></h3>
  <a href="logout_client.php" class="btn btn-outline-danger mt-2">Déconnexion</a>
</div>

<?php if ($message): ?>
  <div class="message"><?= $message ?></div>
<?php endif; ?>

<div class="row g-4">
  <?php foreach ($casierList as $casier): ?>
    <div class="col-12 col-md-6">
      <div class="casier-card">
        <h5>Casier <?= $casier['CasierID'] ?> - <?= $casier['CasierType'] ?></h5>
        <p>Quantité dispo : <?= $casier['CasierQte'] ?></p>
        <form method="POST">
          <input type="hidden" name="retirer_id" value="<?= $casier['CasierID'] ?>">
          <div class="mb-3">
            <label for="quantite" class="form-label">Quantité à retirer :</label>
            <input type="number" name="quantite" class="form-control" min="1" max="<?= $casier['CasierQte'] ?>" value="1" required>
          </div>
          <button type="submit" class="btn btn-primary btn-retirer w-100">Retirer</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

</body>
</html>
