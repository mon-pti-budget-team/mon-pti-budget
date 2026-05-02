<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Connexion base de données (à adapter avec tes identifiants WAMP)
$host = 'localhost';
$dbname = 'mon_pti_budget';
$user = 'root';
$pass = '';

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $montant    = floatval($_POST['montant']);
        $categorie  = htmlspecialchars($_POST['categorie']);
        $type       = htmlspecialchars($_POST['type']); // Personnel ou Professionnel (US8)
        $date       = $_POST['date'];
        $description = htmlspecialchars($_POST['description']);

        if ($montant <= 0) {
            $erreur = "Le montant doit être supérieur à 0.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO depenses (montant, categorie, type, date, description, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$montant, $categorie, $type, $date, $description, $_SESSION['user_id']]);
            $message = "Dépense ajoutée avec succès !";
        }

    } catch (PDOException $e) {
        $erreur = "Erreur base de données : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Déclarer une dépense - Mon Pti' Budget</title>
  <link rel="stylesheet" href="asset/CSS/style.css">
  <style>
    .alert-success {
      background: #d1fae5;
      color: #065f46;
      border: 1px solid #6ee7b7;
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      text-align: center;
    }
    .alert-error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      text-align: center;
    }
    select {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 0.95rem;
      color: var(--text);
      background: var(--bg);
      transition: border 0.2s;
      appearance: none;
      cursor: pointer;
    }
    select:focus {
      outline: none;
      border-color: var(--violet);
      background: var(--white);
    }
    textarea {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 0.95rem;
      color: var(--text);
      background: var(--bg);
      resize: vertical;
      min-height: 80px;
      font-family: 'Segoe UI', sans-serif;
      transition: border 0.2s;
    }
    textarea:focus {
      outline: none;
      border-color: var(--violet);
      background: var(--white);
    }
    .card {
      max-width: 460px;
    }
    .nav-links {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 20px;
    }
    .nav-links a {
      font-size: 0.85rem;
      color: var(--violet);
      text-decoration: none;
    }
    .nav-links a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="card">
    <h1 class="logo">💰 Mon Pti' Budget</h1>
    <h2>Déclarer une dépense</h2>
    <p class="subtitle">Renseigne ta nouvelle dépense</p>

    <?php if ($message): ?>
      <div class="alert-success"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($erreur): ?>
      <div class="alert-error"><?= $erreur ?></div>
    <?php endif; ?>

    <form action="depenses.php" method="POST">

      <div class="field">
        <label for="montant">Montant (€)</label>
        <input type="number" id="montant" name="montant" placeholder="Ex: 45.00" step="0.01" min="0.01" required>
      </div>

      <div class="field">
        <label for="categorie">Catégorie</label>
        <select id="categorie" name="categorie" required>
          <option value="" disabled selected>Choisir une catégorie</option>
          <option value="Alimentation">🍔 Alimentation</option>
          <option value="Transport">🚗 Transport</option>
          <option value="Logement">🏠 Logement</option>
          <option value="Santé">💊 Santé</option>
          <option value="Loisirs">🎮 Loisirs</option>
          <option value="Vêtements">👕 Vêtements</option>
          <option value="Fournisseurs">📦 Fournisseurs</option>
          <option value="Maintenance">🔧 Maintenance</option>
          <option value="Autre">📌 Autre</option>
        </select>
      </div>

      <!-- US8 : type Personnel / Professionnel -->
      <div class="field">
        <label for="type">Type de dépense</label>
        <select id="type" name="type" required>
          <option value="" disabled selected>Personnel ou Professionnel ?</option>
          <option value="Personnel">👤 Personnel</option>
          <option value="Professionnel">💼 Professionnel</option>
        </select>
      </div>

      <div class="field">
        <label for="date">Date</label>
        <input type="date" id="date" name="date" required value="<?= date('Y-m-d') ?>">
      </div>

      <div class="field">
        <label for="description">Description (optionnel)</label>
        <textarea id="description" name="description" placeholder="Ex: Courses au supermarché..."></textarea>
      </div>

      <button type="submit" class="btn">✅ Valider la dépense</button>

    </form>

    <div class="nav-links">
      <a href="dashboard.php">← Retour au tableau de bord</a>
      <a href="historique.php">Voir l'historique →</a>
    </div>
  </div>

</body>
</html>