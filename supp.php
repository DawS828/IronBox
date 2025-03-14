<?php
// Connexion à la base de données
$host = 'localhost';
$dbname = 'IronBox';
$user = 'dawson';
$password = 'Dawson11@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}

// Suppression d'un technicien
if (isset($_POST['delete_technicien'])) {
    $technicien_id = $_POST['technicien_id'];

    // Vérifier si le technicien existe
    $stmt = $pdo->prepare("SELECT TechnicienID FROM Technicien WHERE TechnicienID = ?");
    $stmt->execute([$technicien_id]);
    if ($stmt->fetch()) {
        // Supprimer le technicien
        $stmt = $pdo->prepare("DELETE FROM Technicien WHERE TechnicienID = ?");
        $stmt->execute([$technicien_id]);

        echo "<p class='success'>Le technicien a été supprimé avec succès.</p>";
    } else {
        echo "<p class='error'>Le technicien sélectionné n'existe pas.</p>";
    }
}

// Suppression d'une IronBox
if (isset($_POST['delete_ironbox'])) {
    $ironbox_id = $_POST['ironbox_id'];

    // Vérifier si l'IronBox existe
    $stmt = $pdo->prepare("SELECT IronBoxID FROM IronBox WHERE IronBoxID = ?");
    $stmt->execute([$ironbox_id]);
    if ($stmt->fetch()) {
        // Supprimer les casiers associés à l'IronBox
        $stmt = $pdo->prepare("DELETE FROM Casier WHERE IronBoxID = ?");
        $stmt->execute([$ironbox_id]);

        // Supprimer l'IronBox
        $stmt = $pdo->prepare("DELETE FROM IronBox WHERE IronBoxID = ?");
        $stmt->execute([$ironbox_id]);

        echo "<p class='success'>L'IronBox et ses casiers associés ont été supprimés avec succès.</p>";
    } else {
        echo "<p class='error'>L'IronBox sélectionnée n'existe pas.</p>";
    }
}

// Suppression d'un Casier
if (isset($_POST['delete_casier'])) {
    $casier_id = $_POST['casier_id'];

    // Vérifier si le casier existe
    $stmt = $pdo->prepare("SELECT CasierID FROM Casier WHERE CasierID = ?");
    $stmt->execute([$casier_id]);
    if ($stmt->fetch()) {
        // Supprimer les matériels associés au casier
        $stmt = $pdo->prepare("DELETE FROM Contenir WHERE CasierID = ?");
        $stmt->execute([$casier_id]);

        // Supprimer le casier
        $stmt = $pdo->prepare("DELETE FROM Casier WHERE CasierID = ?");
        $stmt->execute([$casier_id]);

        echo "<p class='success'>Le casier et ses matériels associés ont été supprimés avec succès.</p>";
    } else {
        echo "<p class='error'>Le casier sélectionné n'existe pas.</p>";
    }
}

// Suppression d'un Matériel
if (isset($_POST['delete_materiel'])) {
    $materiel_id = $_POST['materiel_id'];

    // Vérifier si le matériel existe
    $stmt = $pdo->prepare("SELECT MaterielID FROM Materiel WHERE MaterielID = ?");
    $stmt->execute([$materiel_id]);
    if ($stmt->fetch()) {
        // Supprimer les associations du matériel avec les casiers
        $stmt = $pdo->prepare("DELETE FROM Contenir WHERE MaterielID = ?");
        $stmt->execute([$materiel_id]);

        // Supprimer le matériel
        $stmt = $pdo->prepare("DELETE FROM Materiel WHERE MaterielID = ?");
        $stmt->execute([$materiel_id]);

        echo "<p class='success'>Le matériel et ses associations ont été supprimés avec succès.</p>";
    } else {
        echo "<p class='error'>Le matériel sélectionné n'existe pas.</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Casiers</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; }
        header { background-color: #333; color: white; text-align: center; padding: 15px; }
        h1 { margin: 0; }
        .container { width: 80%; margin: 20px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        input, select, button { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; }
        button { background-color: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #218838; }
        .success { color: green; }
        .error { color: red; }
        nav { background-color: #444; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        nav a { color: white; margin: 0 15px; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content { display: none; position: absolute; background-color: #555; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; }
        .dropdown-content a { color: white; padding: 12px 16px; text-decoration: none; display: block; }
        .dropdown-content a:hover { background-color: #666; }
        .dropdown:hover .dropdown-content { display: block; }
    </style>
</head>
<body>

<header>
    <h1>Gestion des Casiers - Supprimer des Données</h1>
</header>

<nav>
    <div class="dropdown">
        <a href="#">Menu ▼</a>
        <div class="dropdown-content">
            <a href="#delete-technicien">Supprimer un Technicien</a>
            <a href="#delete-ironbox">Supprimer une IronBox</a>
            <a href="#delete-casier">Supprimer un Casier</a>
            <a href="#delete-materiel">Supprimer du Matériel</a>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Formulaire pour supprimer un Technicien -->
    <h3 id="delete-technicien">Supprimer un Technicien</h3>
    <form method="POST">
        <label for="technicien_id">Sélectionner un Technicien :</label>
        <select id="technicien_id" name="technicien_id" required>
            <?php
                $stmt = $pdo->query("SELECT TechnicienID, CONCAT(TechNom, ' ', TechPrenom) AS nom FROM Technicien");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$row['TechnicienID']}'>{$row['nom']}</option>";
                }
            ?>
        </select>
        <button type="submit" name="delete_technicien">Supprimer Technicien</button>
    </form>

    <!-- Formulaire pour supprimer une IronBox -->
    <h3 id="delete-ironbox">Supprimer une IronBox</h3>
    <form method="POST">
        <label for="ironbox_id">Sélectionner une IronBox :</label>
        <select id="ironbox_id" name="ironbox_id" required>
            <?php
                $stmt = $pdo->query("SELECT IronBoxID, IronBoxPos FROM IronBox");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$row['IronBoxID']}'>ID: {$row['IronBoxID']} - {$row['IronBoxPos']}</option>";
                }
            ?>
        </select>
        <button type="submit" name="delete_ironbox">Supprimer IronBox</button>
    </form>

    <!-- Formulaire pour supprimer un Casier -->
    <h3 id="delete-casier">Supprimer un Casier</h3>
    <form method="POST">
        <label for="casier_id">Sélectionner un Casier :</label>
        <select id="casier_id" name="casier_id" required>
            <?php
                $stmt = $pdo->query("SELECT CasierID, CONCAT('Dimensions: ', CasierType, ' - État: ', CasierEtat) AS details FROM Casier");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$row['CasierID']}'>{$row['details']}</option>";
                }
            ?>
        </select>
        <button type="submit" name="delete_casier">Supprimer Casier</button>
    </form>

    <!-- Formulaire pour supprimer du Matériel -->
    <h3 id="delete-materiel">Supprimer du Matériel</h3>
    <form method="POST">
        <label for="materiel_id">Sélectionner un Matériel :</label>
        <select id="materiel_id" name="materiel_id" required>
            <?php
                $stmt = $pdo->query("SELECT MaterielID, MaterielNom FROM Materiel");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$row['MaterielID']}'>{$row['MaterielNom']}</option>";
                }
            ?>
        </select>
        <button type="submit" name="delete_materiel">Supprimer Matériel</button>
    </form>
</div>

</body>
</html>

