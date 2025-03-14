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

// Ajout d'un technicien
if (isset($_POST['add_technicien'])) {
    $nom = $_POST['tech_nom'];
    $prenom = $_POST['tech_prenom'];
    $role = $_POST['tech_role'];
    $badge = $_POST['tech_badge'];

    $stmt = $pdo->prepare("INSERT INTO Technicien (TechNom, TechPrenom, TechRole, TechBadge) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nom, $prenom, $role, $badge]);

    echo "<p class='success'>Le technicien $nom $prenom avec le rôle $role a été ajouté avec succès.</p>";
}

// Ajout d'une IronBox
if (isset($_POST['add_ironbox'])) {
    $position = $_POST['ironbox_pos'];

    $stmt = $pdo->prepare("INSERT INTO IronBox (IronBoxPos) VALUES (?)");
    $stmt->execute([$position]);

    echo "<p class='success'>La IronBox à la position $position a été ajoutée avec succès.</p>";
}

// Ajout d'un Casier avec dimensions
if (isset($_POST['add_casier'])) {
    $dimensions = $_POST['casier_dimensions'];
    $qte = $_POST['casier_qte'];
    $etat = $_POST['casier_etat'];
    $ironbox_id = $_POST['ironbox_id'];

    // Insérer le casier
    $stmt = $pdo->prepare("INSERT INTO Casier (CasierType, CasierQte, CasierEtat, IronBoxID) VALUES (?, ?, ?, ?)");
    $stmt->execute([$dimensions, $qte, $etat, $ironbox_id]);

    echo "<p class='success'>Le casier avec dimensions $dimensions a été ajouté avec succès dans l'IronBox sélectionnée.</p>";
}

// Ajout d'un Matériel dans un Casier
if (isset($_POST['add_materiel'])) {
    $nom = $_POST['materiel_nom'];
    $categorie = $_POST['materiel_categorie'];
    $casier_id = $_POST['casier_id'];

    // Validation des champs requis
    if (empty($nom) || empty($categorie) || empty($casier_id)) {
        echo "<p class='error'>Le nom, la catégorie et le casier sont requis.</p>";
    } else {
        // Vérifier si le casier existe
        $stmt = $pdo->prepare("SELECT CasierID FROM Casier WHERE CasierID = ?");
        $stmt->execute([$casier_id]);
        if ($stmt->fetch()) {
            // Ajouter le matériel
            $stmt = $pdo->prepare("INSERT INTO Materiel (MaterielNom, MaterielType) VALUES (?, ?)");
            $stmt->execute([$nom, $categorie]);

            // Obtenir l'ID du matériel nouvellement ajouté
            $materiel_id = $pdo->lastInsertId();

            // Associer le matériel au casier
            $stmt = $pdo->prepare("INSERT INTO Contenir (CasierID, MaterielID) VALUES (?, ?)");
            $stmt->execute([$casier_id, $materiel_id]);

            echo "<p class='success'>Le matériel $nom a été ajouté avec succès dans le casier ID: $casier_id.</p>";
        } else {
            echo "<p class='error'>Le casier sélectionné n'existe pas.</p>";
        }
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
    <h1>Gestion des Casiers - Ajouter des Données</h1>
</header>

<nav>
    <div class="dropdown">
        <a href="#">Menu ▼</a>
        <div class="dropdown-content">
            <a href="#add-technicien">Ajouter un Technicien</a>
            <a href="#add-ironbox">Ajouter une IronBox</a>
            <a href="#add-casier">Ajouter un Casier</a>
            <a href="#add-materiel">Ajouter du Matériel</a>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Formulaire pour ajouter un Technicien -->
    <h3 id="add-technicien">Ajouter un Technicien</h3>
    <form method="POST">
        <label for="tech_nom">Nom :</label>
        <input type="text" id="tech_nom" name="tech_nom" required>

        <label for="tech_prenom">Prénom :</label>
        <input type="text" id="tech_prenom" name="tech_prenom" required>

        <label for="tech_role">Rôle :</label>
        <select id="tech_role" name="tech_role" required>
            <option value="Technicien">Technicien</option>
            <option value="Admin">Admin</option>
        </select>

        <label for="tech_badge">Badge :</label>
        <input type="text" id="tech_badge" name="tech_badge" required>

        <button type="submit" name="add_technicien">Ajouter Technicien</button>
    </form>

    <!-- Formulaire pour ajouter une IronBox -->
    <h3 id="add-ironbox">Ajouter une IronBox</h3>
    <form method="POST">
        <label for="ironbox_pos">Position :</label>
        <select id="ironbox_pos" name="ironbox_pos" required>
            <option value="Mardyck">Mardyck</option>
            <option value="Dunkerque">Dunkerque</option>
        </select>

        <button type="submit" name="add_ironbox">Ajouter IronBox</button>
    </form>

    <!-- Formulaire pour ajouter un Casier avec dimensions -->
    <h3 id="add-casier">Ajouter un Casier</h3>
    <form method="POST">
        <label for="casier_dimensions">Dimensions (choisissez une option) :</label>
        <select id="casier_dimensions" name="casier_dimensions" required>
            <option value="47 x 23 x 34">47 x 23 x 34 cm</option>
            <option value="60 x 30 x 45">60 x 30 x 45 cm</option>
            <option value="80 x 50 x 60">80 x 50 x 60 cm</option>
            <!-- Ajoutez d'autres dimensions selon vos besoins -->
        </select>

        <label for="casier_qte">Quantité :</label>
        <input type="number" id="casier_qte" name="casier_qte" required>

        <label for="casier_etat">État du Casier :</label>
        <input type="text" id="casier_etat" name="casier_etat" required>

        <label for="ironbox_id">Sélectionner une IronBox :</label>
        <select id="ironbox_id" name="ironbox_id" required>
            <?php
                $stmt = $pdo->query("SELECT IronBoxID, IronBoxPos FROM IronBox");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$row['IronBoxID']}'>ID: {$row['IronBoxID']} - {$row['IronBoxPos']}</option>";
                }
            ?>
        </select>

        <button type="submit" name="add_casier">Ajouter Casier</button>
    </form>

    <!-- Formulaire pour ajouter du Matériel dans un Casier -->
    <h3 id="add-materiel">Ajouter du Matériel</h3>
    <form method="POST">
        <label for="materiel_nom">Nom du Matériel :</label>
        <input type="text" id="materiel_nom" name="materiel_nom" required>

        <label for="materiel_categorie">Catégorie :</label>
        <select id="materiel_categorie" name="materiel_categorie" required>
            <option value="Informatique">Informatique</option>
            <option value="Électronique">Électronique</option>
            <option value="Mécanique">Mécanique</option>
            <!-- Ajoutez d'autres catégories selon vos besoins -->
        </select>

        <label for="casier_id">Sélectionner un Casier :</label>
        <select id="casier_id" name="casier_id" required>
            <?php
                $stmt = $pdo->query("SELECT CasierID, CONCAT('Dimensions: ', CasierType, ' - État: ', CasierEtat) AS details FROM Casier");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$row['CasierID']}'>{$row['details']}</option>";
                }
            ?>
        </select>

        <button type="submit" name="add_materiel">Ajouter Matériel</button>
    </form>
</div>

</body>
</html>

