<?php
session_start();

// US6 : protection de la page, rediriger si non connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$host   = 'localhost';
$dbname = 'mon_pti_budget';
$user   = 'root';
$pass   = '';

$depenses = [];
$total_depenses = 0;
$revenu = 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // US2 : récupérer les dernières dépenses
    $stmt = $pdo->prepare("SELECT * FROM depenses WHERE user_id = ? ORDER BY date DESC LIMIT 20");
    $stmt->execute([$_SESSION['user_id']]);
    $depenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcul total dépenses
    $stmt2 = $pdo->prepare("SELECT SUM(montant) as total FROM depenses WHERE user_id = ?");
    $stmt2->execute([$_SESSION['user_id']]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);
    $total_depenses = $row['total'] ?? 0;

    // US3 : récupérer le revenu
    $stmt3 = $pdo->prepare("SELECT montant FROM revenus WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt3->execute([$_SESSION['user_id']]);
    $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
    $revenu = $row3['montant'] ?? 0;

} catch (PDOException $e) {
    // Si la table n'existe pas encore, on continue sans erreur
}

$reste = $revenu - $total_depenses;

// Icônes par catégorie
$icones = [
    'Alimentation' => '🍔',
    'Transport'    => '🚗',
    'Logement'     => '🏠',
    'Santé'        => '💊',
    'Loisirs'      => '🎮',
    'Vêtements'    => '👕',
    'Fournisseurs' => '📦',
    'Maintenance'  => '🔧',
    'Autre'        => '📌',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de bord - Mon Pti' Budget</title>
  <link rel="stylesheet" href="asset/CSS/style.css">
  <style>
    body {
      align-items: flex-start;
      padding: 30px 20px;
    }

    .dashboard {
      width: 100%;
      max-width: 900px;
      margin: 0 auto;
    }

    /* Header */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .header h1 {
      font-size: 1.4rem;
      color: var(--violet);
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .user-name {
      font-size: 0.9rem;
      color: var(--gris);
    }

    .btn-logout {
      padding: 8px 16px;
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--gris);
      font-size: 0.85rem;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
    }

    .btn-logout:hover {
      border-color: var(--violet);
      color: var(--violet);
    }

    /* Cartes résumé */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--white);
      border-radius: var(--radius);
      padding: 20px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }

    .stat-card .label {
      font-size: 0.8rem;
      color: var(--gris);
      margin-bottom: 8px;
    }

    .stat-card .value {
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--text);
    }

    .stat-card.danger .value { color: #dc2626; }
    .stat-card.success .value { color: #16a34a; }
    .stat-card.primary .value { color: var(--violet); }

    /* Section historique */
    .section {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      overflow: hidden;
      margin-bottom: 24px;
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 24px;
      border-bottom: 1px solid var(--border);
    }

    .section-header h2 {
      font-size: 1rem;
      color: var(--text);
      text-align: left;
    }

    .btn-small {
      padding: 8px 16px;
      background: var(--violet);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
    }

    .btn-small:hover {
      background: var(--violet-hover);
    }

    /* Tableau historique */
    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead th {
      padding: 12px 24px;
      text-align: left;
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--gris);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      background: var(--bg);
    }

    tbody tr {
      border-top: 1px solid var(--border);
      transition: background 0.15s;
    }

    tbody tr:hover {
      background: #fafafa;
    }

    tbody td {
      padding: 14px 24px;
      font-size: 0.9rem;
      color: var(--text);
    }

    .badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .badge-pro {
      background: #ede9fe;
      color: #6d28d9;
    }

    .badge-perso {
      background: #dbeafe;
      color: #1d4ed8;
    }

    .montant-cell {
      font-weight: 700;
      color: #dc2626;
    }

    .empty-state {
      text-align: center;
      padding: 40px;
      color: var(--gris);
      font-size: 0.9rem;
    }

    /* Bouton ajouter revenu */
    .btn-revenu {
      display: inline-block;
      padding: 8px 16px;
      background: #16a34a;
      color: white;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 600;
      text-decoration: none;
      transition: background 0.2s;
    }

    .btn-revenu:hover {
      background: #15803d;
    }

    @media (max-width: 600px) {
      .stats-grid { grid-template-columns: 1fr; }
      thead th:nth-child(3), tbody td:nth-child(3) { display: none; }
    }
  </style>
</head>
<body>

<div class="dashboard">

  <!-- Header -->
  <div class="header">
    <h1>💰 Mon Pti' Budget</h1>
    <div class="header-right">
      <span class="user-name">👋 <?= htmlspecialchars($_SESSION['user_nom']) ?></span>
      <a href="logout.php" class="btn-logout">Déconnexion</a>
    </div>
  </div>

  <!-- Cartes résumé -->
  <div class="stats-grid">
    <div class="stat-card primary">
      <div class="label">💼 Revenu déclaré</div>
      <div class="value"><?= number_format($revenu, 2, ',', ' ') ?> €</div>
    </div>
    <div class="stat-card danger">
      <div class="label">💸 Total dépenses</div>
      <div class="value"><?= number_format($total_depenses, 2, ',', ' ') ?> €</div>
    </div>
    <div class="stat-card <?= $reste >= 0 ? 'success' : 'danger' ?>">
      <div class="label">🏦 Reste à vivre</div>
      <div class="value"><?= number_format($reste, 2, ',', ' ') ?> €</div>
    </div>
  </div>

  <!-- Actions rapides -->
  <div style="display:flex; gap:12px; margin-bottom:24px; flex-wrap:wrap;">
    <a href="depenses.php" class="btn-small">+ Ajouter une dépense</a>
    <a href="revenu.php" class="btn-revenu">+ Déclarer mon revenu</a>
  </div>

  <!-- US2 : Historique des dépenses -->
  <div class="section">
    <div class="section-header">
      <h2>📋 Historique des dépenses</h2>
    </div>

    <?php if (empty($depenses)): ?>
      <div class="empty-state">
        Aucune dépense enregistrée pour l'instant.<br>
        <a href="depenses.php" style="color:var(--violet);">Ajouter ta première dépense →</a>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Catégorie</th>
            <th>Type</th>
            <th>Description</th>
            <th>Montant</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($depenses as $d): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($d['date'])) ?></td>
              <td><?= ($icones[$d['categorie']] ?? '📌') . ' ' . htmlspecialchars($d['categorie']) ?></td>
              <td>
                <span class="badge <?= $d['type'] === 'Professionnel' ? 'badge-pro' : 'badge-perso' ?>">
                  <?= htmlspecialchars($d['type']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($d['description'] ?: '—') ?></td>
              <td class="montant-cell">-<?= number_format($d['montant'], 2, ',', ' ') ?> €</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

</body>
</html>