<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner de Badge RFID</title>
    <style>
        /* Style général pour le formulaire */
        form {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #f9f9f9;
        }

        /* Style pour les labels */
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #333;
        }

        /* Style pour les champs de saisie */
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }

        /* Style pour le bouton de soumission */
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        /* Effet au survol du bouton */
        input[type="submit"]:hover {
            background-color: #45a049;
        }

        /* Style pour le message d'erreur (si nécessaire) */
        .error-message {
            color: red;
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php
    // Paramètres de connexion à la base de données
    $servername = "localhost";
    $username = "dawson";  // Nom d'utilisateur MySQL
    $password = "Dawson11@";  // Mot de passe MySQL
    $dbname = "IronBox";  // Nom de la base de données

    // Connexion à la base de données
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Vérification de la connexion
    if ($conn->connect_error) {
        die("Connexion échouée : " . $conn->connect_error);
    }

    // Vérifier si un ID de badge RFID a été envoyé via POST
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['badge_id'])) {
        $badgeId = trim($_POST['badge_id']); // Supprimer les espaces blancs

        // Vérifier si l'ID du badge existe dans la base de données
        $sql = "SELECT * FROM Technicien WHERE TechBadge = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $badgeId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Enregistrer l'historique du badge scanné
            $insertSql = "INSERT INTO Historique (HistoriqueDate, HistoriqueHeure, HistoriqueTypeEvent, badge) VALUES (CURDATE(), CURTIME(), 'Scan Badge', ?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("s", $badgeId);
            $insertStmt->execute();

            // Vérifier le rôle du technicien et rediriger vers la page
            switch ($row['TechRole']) {
                case 'Admin':
                    // Rediriger vers la page pour les administrateurs
                    header("Location: ouverture.php");
                    exit;
                case 'Technicien':
                    // Rediriger vers la page pour les techniciens
                    header("Location: technicien_page.php");
                    exit;
                default:
                    // Rediriger vers une page d'erreur
                    header("Location: error_page.php");
                    exit;
            }
        } else {
            // Si aucun technicien n'est trouvé avec ce badge, rediriger vers la page d'erreur
            header("Location: https://www.onisep.fr/ressources/univers-formation/formations/post-bac/bts-cybersecurite-informatique-et-reseaux-electronique-option-a-informatique-et-reseaux");
            exit;
        }
    }

    // Fermeture de la connexion
    $conn->close();
    ?>

    <!-- Formulaire HTML pour l'ID du badge RFID -->
    <form method="POST">
        <label for="badge_id">Scanner le badge RFID :</label><br>
        <input type="text" id="badge_id" name="badge_id" maxlength="10" autofocus>
        <input type="submit" value="Valider">
    </form>
</body>
</html>


