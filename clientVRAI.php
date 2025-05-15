<?php
session_start();
include 'connexion.php';
require 'vendor/autoload.php'; // Inclure l'autoloader de Composer POur les mqils

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

  //Fichier contenant le badge du technicien
$badge_file = 'badge.json';

//Vérification de l'existence du fichier badge.json
if (file_exists($badge_file)) {
    //  Lecture et décodage du fichier JSON
    $json = json_decode(file_get_contents($badge_file), true);
    $badge = $json['badge'] ?? null;

         // Si un badge est trouvé, recherche du technicien correspondant dans la base de données
    if ($badge) {
        $stmt = $bdd->prepare("SELECT * FROM Technicien WHERE TechBadge = ?");
        $stmt->execute([$badge]);
        $tech = $stmt->fetch();

                  // Si le technicien est trouvé, stockage de ses informations dans la session
        if ($tech) {
            $_SESSION['client_id'] = $tech['TechnicienID'];
            $_SESSION['client_nom'] = $tech['TechPrenom'] . ' ' . $tech['TechNom'];
            unlink($badge_file); // Suppression du fichier badge.json
        }
    }
}

// Redirection vers la page de connexion si l'utilisateur n'est pas connecté
if (!isset($_SESSION['client_id'])) {
    header('Location: login_client.php');
    exit();
}

$techID = $_SESSION['client_id'];
$clientNom = $_SESSION['client_nom'];

// Récupération du badge du technicien
$badge = $bdd->prepare("SELECT TechBadge FROM Technicien WHERE TechnicienID = ?");
$badge->execute([$techID]);
$badge = $badge->fetchColumn();

$message = '';

// Traitement du formulaire de retrait de matériel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retirer_id']) && isset($_POST['quantite'])) {
    $casierID = intval($_POST['retirer_id']);
    $quantite = intval($_POST['quantite']);

    if ($quantite <= 0) {
        $message = "❌ Quantité invalide.";
    } else {
        // Vérification de la quantité disponible dans le casier
        $qCheck = $bdd->prepare("SELECT CasierQte FROM Casier WHERE CasierID = ?");
        $qCheck->execute([$casierID]);
        $qte_dispo = $qCheck->fetchColumn();

        if ($qte_dispo >= $quantite) {
            // Récupération de l'ID du matériel dans le casier
            $stmt = $bdd->prepare("SELECT MaterielID FROM Contenir WHERE CasierID = ?");
            $stmt->execute([$casierID]);
            $materielID = $stmt->fetchColumn();

            // Mise à jour des quantités de matériel et de casier
            $bdd->prepare("UPDATE Materiel SET Quantite = Quantite - ? WHERE MaterielID = ?")
                ->execute([$quantite, $materielID]);
            $bdd->prepare("UPDATE Casier SET CasierQte = CasierQte - ? WHERE CasierID = ?")
                ->execute([$quantite, $casierID]);

            // Ajout d'un enregistrement dans l'historique
            $insertHist = $bdd->prepare("INSERT INTO Historique (HistoriqueDate, HistoriqueHeure, HistoriqueTypeEvent, badge, TechID)
                                         VALUES (CURDATE(), CURTIME(), ?, ?, ?)");
            $insertHist->execute(["Retrait $quantite matériel(s) - Casier $casierID", $badge, $techID]);
            $histID = $bdd->lastInsertId();

            // Ajout d'un enregistrement dans la liste des matériels retirés
            $bdd->prepare("INSERT INTO Lister (HistoriqueID, MaterielID) VALUES (?, ?)")
                ->execute([$histID, $materielID]);

            // Exécution du script shell associé au casier
            $scriptPath = "/home/pi/init/open_cas{$casierID}.sh";
            if (file_exists($scriptPath)) {
                shell_exec("bash " . escapeshellarg($scriptPath));
            }

            // Envoi d'un email à ironbox.imputation@gmail.com
            $mail = new PHPMailer(true);

            try {
                // Configuration du serveur SMTP (Gmail)
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ironbox.imputation@gmail.com';
                $mail->Password = 'pqeg vvxh hzid mdsv';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Destinataire
                $mail->setFrom('ironbox.imputation@gmail.com', 'IronBox');
                $mail->addAddress('ironbox.imputation@gmail.com', 'IronBox Imputation');

                // Contenu de l'email
                $mail->isHTML(true);
                $mail->Subject = "Retrait sur le - Casier $casierID";
                $mail->Body = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .container { width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #f8f9fa; padding: 10px; text-align: center; }
                            .content { padding: 20px; }
                            .footer { background-color: #f8f9fa; padding: 10px; text-align: center; font-size: 12px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>Retrait de matériel</h2>
                            </div>
                            <div class='content'>
                                <p><strong>Technicien:</strong> $clientNom</p>
                                <p><strong>Casier ID:</strong> $casierID</p>
                                <p><strong>Quantité retirée:</strong> $quantite</p>
                                <p><strong>Date et heure:</strong> " . date("Y-m-d H:i:s") . "</p>
                            </div>
                            <div class='footer'>
                                <p>© " . date("Y") . " IronBox. Merci</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";

                $mail->AltBody = "Un retrait de matériel a été effectué:\n\n";
                $mail->AltBody .= "Technicien: $clientNom\n";
                $mail->AltBody .= "Casier ID: $casierID\n";
                $mail->AltBody .= "Quantité retirée: $quantite\n";
                $mail->AltBody .= "Date et heure: " . date("Y-m-d H:i:s") . "\n";

                $mail->send();
                $message = "✅ Casier $casierID ouvert ($quantite matériel(s) retiré(s))";
            } catch (Exception $e) {
                $message = "❌ Erreur lors de l'envoi de l'email: {$mail->ErrorInfo}";
            }
        } else {
            $message = "❌ Stock insuffisant pour le casier $casierID. Quantité demandée : $quantite, Quantité disponible : $qte_dispo";
        }
    }
}

// Récupération de la liste des casiers accessibles par le technicien (mise à jour après retrait)
$casiers = $bdd->prepare("
    SELECT Casier.CasierID, Casier.CasierType, Casier.CasierQte
    FROM Acceder
    JOIN Casier ON Casier.CasierID = Acceder.CasierID
    WHERE Acceder.TechnicienID = ?
");
$casiers->execute([$techID]);
$casierList = $casiers->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>IronBox - Accès</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --primary-color: #007bff;
      --secondary-color: #6c757d;
      --success-color: #28a745;
      --danger-color: #dc3545;
      --light-color: #f8f9fa;
      --dark-color: #343a40;
    }

    body {
      background: var(--light-color);
      padding: 20px;
      font-size: 18px;
    }

    .casier-card {
      background: white;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      text-align: center;
      margin-bottom: 20px;
    }

    .casier-card h5 {
      font-size: 22px;
    }

    .btn-retirer {
      font-size: 20px;
      padding: 10px 20px;
    }

    .header {
      text-align: center;
      margin-bottom: 30px;
    }

    .message {
      text-align: center;
      font-size: 20px;
      color: var(--success-color);
      margin-bottom: 20px;
    }

    .quantity-control {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 15px;
    }

    .quantity-control button {
      width: 40px;
      height: 40px;
      font-size: 20px;
      border: 1px solid #ccc;
      background: #f8f9fa;
      cursor: pointer;
    }

    .quantity-control input {
      width: 60px;
      height: 40px;
      text-align: center;
      border: 1px solid #ccc;
      margin: 0 10px;
      font-size: 18px;
    }

    @media (max-width: 768px) {
      .casier-card {
        margin-bottom: 10px;
      }
    }
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
            <div class="quantity-control">
              <button type="button" class="btn-minus" onclick="changeQuantity(<?= $casier['CasierID'] ?>, -1)">-</button>
              <input type="number" id="quantite_<?= $casier['CasierID'] ?>" name="quantite" class="form-control" min="1" max="<?= $casier['CasierQte'] ?>" value="1" required>
              <button type="button" class="btn-plus" onclick="changeQuantity(<?= $casier['CasierID'] ?>, 1)">+</button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-retirer w-100">Retirer</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<script>
  function changeQuantity(casierId, change) {
    const input = document.getElementById('quantite_' + casierId);
    let value = parseInt(input.value) + change;
    const max = parseInt(input.max);

    if (value < 1) {
      value = 1;
    } else if (value > max) {
      value = max;
    }

    input.value = value;
  }
</script>

</body>
</html>
