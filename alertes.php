<?php
// ============================================================
// alertes.php — US5 : Alertes système + US9 : Barre progression budget pro
// Développé par : Lamine
// ============================================================

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$host   = 'localhost';
$dbname = 'mon_pti_budget';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];

// Revenu mensuel
$stmt = $pdo->prepare("SELECT montant FROM revenus WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$revenu = floatval($stmt->fetchColumn() ?: 0);

// Total toutes dépenses du mois
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(montant), 0) FROM depenses
    WHERE user_id = ?
    AND MONTH(date) = MONTH(CURDATE())
    AND YEAR(date) = YEAR(CURDATE())
");
$stmt->execute([$user_id]);
$total_depenses = floatval($stmt->fetchColumn());

// Total dépenses professionnelles du mois
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(montant), 0) FROM depenses
    WHERE user_id = ? AND type = 'Professionnel'
    AND MONTH(date) = MONTH(CURDATE())
    AND YEAR(date) = YEAR(CURDATE())
");
$stmt->execute([$user_id]);
$total_pro = floatval($stmt->fetchColumn());

// Total dépenses personnelles du mois
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(montant), 0) FROM depenses
    WHERE user_id = ? AND type = 'Personnel'
    AND MONTH(date) = MONTH(CURDATE())
    AND YEAR(date) = YEAR(CURDATE())
");
$stmt->execute([$user_id]);
$total_perso = floatval($stmt->fetchColumn());

// Calculs
$reste       = $revenu - $total_depenses;
$pct_global  = ($revenu > 0) ? round(($total_depenses / $revenu) * 100) : 0;
$budget_pro_max = ($revenu > 0) ? $revenu * 0.5 : 1000;
$pct_pro     = ($budget_pro_max > 0) ? round(($total_pro / $budget_pro_max) * 100) : 0;
$pct_pro_aff = min($pct_pro, 100);

// Niveau alerte globale
if ($revenu <= 0) {
    $niv = 'info';    $titre = 'Revenu non déclaré';
    $msg = 'Déclarez votre revenu mensuel pour activer les alertes.';
} elseif ($total_depenses > $revenu) {
    $niv = 'danger';  $titre = 'Budget dépassé';
    $msg = 'Vos dépenses dépassent votre revenu ce mois-ci.';
} elseif ($pct_global >= 80) {
    $niv = 'warning'; $titre = 'Budget sous tension';
    $msg = 'Vous avez utilisé ' . $pct_global . '% de votre budget. Soyez prudent.';
} else {
    $niv = 'success'; $titre = 'Tout va bien';
    $msg = 'Votre budget est équilibré ce mois-ci. Continuez comme ça !';
}

// Niveau alerte pro
if ($pct_pro > 100) {
    $niv_pro = 'danger';  $msg_pro = 'Budget professionnel dépassé (' . $pct_pro . '% utilisé)';
} elseif ($pct_pro >= 80) {
    $niv_pro = 'warning'; $msg_pro = 'Budget professionnel bientôt atteint (' . $pct_pro . '% utilisé)';
} else {
    $niv_pro = 'success'; $msg_pro = 'Budget professionnel sous contrôle (' . $pct_pro . '% utilisé)';
}

// Couleur barre header
$pct_header = ($revenu > 0) ? min(round(($total_depenses / $revenu) * 100), 100) : 0;
if ($pct_header >= 100)    { $coul = '#dc2626'; }
elseif ($pct_header >= 80) { $coul = '#d97706'; }
else                        { $coul = '#2563eb'; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alertes — Mon Pti' Budget</title>
  <link rel="stylesheet" href="asset/CSS/style.css">
</head>
<body>

<header class="topbar">
  <div class="topbar-inner">
    <span class="topbar-logo">MON PTI' BUDGET</span>
    <div class="topbar-budget">
      <div class="topbar-revenu">
        <span class="topbar-meta">MON REVENU (€)</span>
        <span class="topbar-chiffre"><?= number_format($revenu, 2, ',', ' ') ?></span>
        <a href="revenu.php" class="topbar-btn-modifier">MODIFIER</a>
      </div>
      <div class="topbar-barre-bloc">
        <span class="topbar-meta">BUDGET ENTREPRISE</span>
        <div class="topbar-barre-fond">
          <div class="topbar-barre-remplie" style="width:<?= $pct_header ?>%; background:<?= $coul ?>;"></div>
        </div>
        <span class="topbar-meta">Budget utilisé : <?= $pct_header ?>%</span>
      </div>
    </div>
    <div class="topbar-user">
      <span class="topbar-nom"><?= htmlspecialchars($_SESSION['user_nom']) ?></span>
      <a href="logout.php" class="topbar-logout">Déconnexion</a>
    </div>
  </div>
</header>

<main class="page">

  <!-- Titre page -->
  <div style="margin-bottom:.5rem">
    <p style="font-size:.65rem;font-weight:700;color:#94a3b8;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.25rem">ALERTES SYSTÈME</p>
    <p style="font-size:.85rem;color:#64748b">Suivi de votre situation financière du mois de <?= date('F Y') ?>.</p>
  </div>

  <!-- Cartes stats -->
  <div class="cards-grid">
    <div class="card-stat">
      <p class="card-label">REVENU MENSUEL</p>
      <p class="card-val"><?= number_format($revenu, 2, ',', ' ') ?> €</p>
    </div>
    <div class="card-stat">
      <p class="card-label">TOTAL DÉPENSES</p>
      <p class="card-val <?= $total_depenses > $revenu ? 'val-neg' : '' ?>">
        <?= number_format($total_depenses, 2, ',', ' ') ?> €
      </p>
    </div>
    <div class="card-stat">
      <p class="card-label">RESTE À VIVRE</p>
      <p class="card-val <?= $reste >= 0 ? 'val-pos' : 'val-neg' ?>">
        <?= number_format($reste, 2, ',', ' ') ?> €
      </p>
    </div>
  </div>

  <!-- US5 : Alerte budget global -->
  <section class="bloc">
    <h2 class="bloc-titre">ALERTE BUDGET GLOBAL</h2>

    <!-- Bandeau coloré selon le niveau -->
    <div class="alerte-bande alerte-bande-<?= $niv ?>">
      <strong><?= htmlspecialchars($titre) ?> :</strong>
      <?= htmlspecialchars($msg) ?>
    </div>

    <!-- Barre de progression globale -->
    <?php if ($revenu > 0): ?>
    <div style="margin-top:1rem">
      <div style="display:flex;justify-content:space-between;margin-bottom:.35rem">
        <span style="font-size:.65rem;font-weight:600;color:#94a3b8;letter-spacing:.08em;text-transform:uppercase">BUDGET GLOBAL UTILISÉ</span>
        <span style="font-size:.8rem;font-weight:700;color:#1e293b"><?= $pct_global ?>%</span>
      </div>
      <div class="topbar-barre-fond" style="height:10px">
        <div class="topbar-barre-remplie" style="width:<?= min($pct_global,100) ?>%;background:<?= $coul ?>;height:10px"></div>
      </div>
      <p style="font-size:.75rem;color:#64748b;margin-top:.35rem">
        <?= number_format($total_depenses,2,',',' ') ?> € dépensés sur <?= number_format($revenu,2,',',' ') ?> € de revenu
      </p>
    </div>
    <?php endif; ?>
  </section>

  <!-- US9 : Barre progression budget professionnel -->
  <section class="bloc">
    <h2 class="bloc-titre">BUDGET PROFESSIONNEL</h2>

    <div class="alerte-bande alerte-bande-<?= $niv_pro ?>">
  <strong>Dépenses professionnelles du mois :</strong>
  <?= htmlspecialchars($msg_pro) ?>
</div>

    <div style="margin-top:1rem">
      <div style="display:flex;justify-content:space-between;margin-bottom:.35rem">
        <span style="font-size:.65rem;font-weight:600;color:#94a3b8;letter-spacing:.08em;text-transform:uppercase">BUDGET PRO UTILISÉ</span>
        <span style="font-size:.8rem;font-weight:700;color:#1e293b"><?= $pct_pro ?>%</span>
      </div>
      <div class="topbar-barre-fond" style="height:10px">
        <?php
          if ($pct_pro > 100)    $c_pro = '#dc2626';
          elseif ($pct_pro >= 80) $c_pro = '#d97706';
          else                    $c_pro = '#2563eb';
        ?>
        <div class="topbar-barre-remplie" style="width:<?= $pct_pro_aff ?>%;background:<?= $c_pro ?>;height:10px"></div>
      </div>
      <p style="font-size:.75rem;color:#64748b;margin-top:.35rem">
        <?= number_format($total_pro,2,',',' ') ?> € dépenses pro
        sur un budget estimé de <?= number_format($budget_pro_max,2,',',' ') ?> € (50% du revenu)
      </p>
    </div>

    <!-- Répartition perso / pro -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1.25rem">
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:.875rem 1rem">
        <p style="font-size:.65rem;font-weight:600;color:#94a3b8;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.4rem">DÉPENSES PERSONNELLES</p>
        <p style="font-size:1.25rem;font-weight:700;color:#1e293b"><?= number_format($total_perso,2,',',' ') ?> €</p>
      </div>
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:.875rem 1rem">
        <p style="font-size:.65rem;font-weight:600;color:#94a3b8;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.4rem">DÉPENSES PROFESSIONNELLES</p>
        <p style="font-size:1.25rem;font-weight:700;color:#1e293b"><?= number_format($total_pro,2,',',' ') ?> €</p>
      </div>
    </div>
  </section>

  <!-- Lien retour -->
  <div style="text-align:center">
    <a href="dashboard.php" style="font-size:.8rem;color:#64748b">← Retour au tableau de bord</a>
  </div>

</main>
</body>
</html>