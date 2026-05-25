<?php
// ============================================================
// revenu.php — US3 : Déclarer son revenu mensuel
// Développé par : Mario
// ============================================================

// 1. On démarre la session pour savoir qui est connecté
session_start();

// 2. Si pas connecté → retour login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 3. Connexion base de données
$host   = 'localhost';
$dbname = 'mon_pti_budget';
$user   = 'root';
$pass   = '';

$message = '';
$erreur  = '';

// 4. Traitement du formulaire quand il est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $montant = $_POST['montant'] ?? '';

    // Vérification : le montant doit être un nombre positif
    if (!is_numeric($montant) || $montant <= 0) {
        $erreur = "Veuillez saisir un montant valide (nombre positif).";
    } else {
        $montant = floatval($montant);

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // On vérifie si un revenu existe déjà pour cet utilisateur ce mois-ci
            $mois = date('Y-m'); // ex : "2026-05"
            $stmt = $pdo->prepare("
                SELECT id FROM revenus
                WHERE user_id = ?
                AND DATE_FORMAT(created_at, '%Y-%m') = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $mois]);
            $existe = $stmt->fetch();

            if ($existe) {
                // Un revenu existe déjà ce mois → on le met à jour (UPDATE)
                $stmt = $pdo->prepare("
                    UPDATE revenus SET montant = ?
                    WHERE user_id = ?
                    AND DATE_FORMAT(created_at, '%Y-%m') = ?
                ");
                $stmt->execute([$montant, $_SESSION['user_id'], $mois]);
                $message = "Revenu mis à jour : " . number_format($montant, 2, ',', ' ') . " €";
            } else {
                // Pas encore de revenu ce mois → on crée (INSERT)
                $stmt = $pdo->prepare("
                    INSERT INTO revenus (user_id, montant, created_at)
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$_SESSION['user_id'], $montant]);
                $message = "Revenu déclaré : " . number_format($montant, 2, ',', ' ') . " €";
            }

        } catch (PDOException $e) {
            $erreur = "Erreur base de données : " . $e->getMessage();
        }
    }
}

// 5. On récupère le revenu actuel pour le pré-remplir dans le formulaire
$revenu_actuel = 0;
try {
    $pdo  = $pdo ?? new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $stmt = $pdo->prepare("SELECT montant FROM revenus WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $revenu_actuel = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    // table pas encore créée, pas grave
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Déclarer mon revenu — Mon Pti' Budget</title>
  <link rel="stylesheet" href="asset/CSS/style.css">
</head>
<body>

  <div class="card">

    <span class="logo">MON PTI' BUDGET</span>
    <h2>Déclarer mon revenu</h2>
    <p class="subtitle">Renseignez votre revenu mensuel net pour calculer votre reste à vivre.</p>

    <!-- Message de succès -->
    <?php if ($message): ?>
      <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Message d'erreur -->
    <?php if ($erreur): ?>
      <div class="alert-error"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <!-- Revenu actuel affiché si déjà déclaré -->
    <?php if ($revenu_actuel > 0): ?>
      <div style="
        background: #eff6ff; border: 1px solid #bfdbfe;
        border-radius: 6px; padding: .75rem 1rem;
        margin-bottom: 1rem; font-size: .85rem; color: #1d4ed8;
      ">
        Revenu actuel : <strong><?= number_format($revenu_actuel, 2, ',', ' ') ?> €</strong>
        — vous pouvez le modifier ci-dessous.
      </div>
    <?php endif; ?>

    <!-- FORMULAIRE -->
    <form method="POST" action="">

      <div class="field">
        <label for="montant">REVENU MENSUEL NET (€)</label>
        <input
          type="number"
          id="montant"
          name="montant"
          placeholder="Ex : 2500"
          min="0"
          step="0.01"
          value="<?= $revenu_actuel > 0 ? htmlspecialchars($revenu_actuel) : '' ?>"
          required
        >
      </div>

      <button type="submit" class="btn">
        <?= $revenu_actuel > 0 ? 'METTRE À JOUR' : 'VALIDER MON REVENU' ?>
      </button>

    </form>

    <div class="links">
      <a href="dashboard.php">← Retour au tableau de bord</a>
    </div>

  </div>

</body>
</html>
